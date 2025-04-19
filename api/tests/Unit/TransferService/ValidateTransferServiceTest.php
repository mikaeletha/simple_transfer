<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

# ./vendor/bin/pest --filter="ValidateTransferServiceTest"

# Função para simular a autorização externa
function fakeAuthorizationResponse(bool $authorizationStatus = true)
{
    Http::fake([
        'https://util.devi.tools/api/v2/authorize' => Http::response([
            'status' => 'success',
            'data' => [
                'authorization' => $authorizationStatus
            ]
        ], 200),
    ]);
}

# ./vendor/bin/pest --filter="throws exception when payer is a supplier"
test('throws exception when payer is a supplier', function () {
    // Deve lançar exceção se o usuário for fornecedor.
    $payerUser = User::factory()->create(['is_supplier' => true]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 200.0]);
    $payeeUser = User::factory()->create(['is_supplier' => false]);
    $payeeAccount = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 0]);
    $value = 100.0;

    $transferService = new TransferService();

    $this->expectException(\DomainException::class);
    $this->expectExceptionMessage('Fornecedores não podem realizar transferências.');

    $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);
});

# ./vendor/bin/pest --filter="throws exception when balance is insufficient"
test('throws exception when balance is insufficient', function () {
    // Deve lançar exceção se o saldo do pagador for insuficiente.
    $payerUser = User::factory()->create(['is_supplier' => false]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 200.0]);
    $payeeUser = User::factory()->create(['is_supplier' => false]);
    $payeeAccount = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 0]);
    $value = 100000.0;

    $transferService = new TransferService();

    $this->expectException(\DomainException::class);
    $this->expectExceptionMessage('Saldo insuficiente para realizar a transferência. Seu saldo é: R$' . $payerAccount->balance);

    $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);
});

# ./vendor/bin/pest --filter="throws exception when payer is the same as payee"
test('throws exception when payer is the same as payee', function () {
    // Deve lançar exceção se o pagador tentar transferir para si mesmo.
    $payerUser = User::factory()->create(['is_supplier' => false]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 200.0]);
    $value = 100.0;

    $transferService = new TransferService();

    $this->expectException(\DomainException::class);
    $this->expectExceptionMessage('Não é permitido transferir para si mesmo.');

    $transferService->transfer($payerAccount->id, $payerAccount->id, $value);
});

# ./vendor/bin/pest --filter="throws exception when transfer is unauthorized by external service"
test('throws exception when transfer is unauthorized by external service', function () {
    // Simula uma resposta de falha do serviço externo (autorização negada)
    fakeAuthorizationResponse(false);

    $payerUser = User::factory()->create(['is_supplier' => false]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 200.0]);
    $payeeUser = User::factory()->create(['is_supplier' => false]);
    $payeeAccount = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 0]);
    $value = 100.0;

    $transferService = new TransferService();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Transferência não autorizada pelo serviço externo.');

    $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);

    try {
        $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);
    } catch (\Exception $e) {
        $payerAccount->refresh();
        $payeeAccount->refresh();

        expect($payerAccount->balance)->toBe(200);
        expect($payeeAccount->balance)->toBe(0);

        $this->assertDatabaseMissing('transactions', [
            'origin_account_id' => $payerAccount->id,
            'destination_account_id' => $payeeAccount->id,
            'amount' => $value,
            'type' => 'transfer',
        ]);

        throw $e;
    }
});

# ./vendor/bin/pest --filter="performs a successful transfer"
test('performs a successful transfer', function () {
    // Simula uma resposta bem-sucedida do serviço externo. Deve passar sem exceção se todos os critérios forem válidos.
    fakeAuthorizationResponse(true);

    $payerUser = User::factory()->create(['is_supplier' => false]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 200.0]);
    $payeeUser = User::factory()->create(['is_supplier' => false]);
    $payeeAccount = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 0]);
    $value = 100.0;

    $transferService = new TransferService();
    $transaction = $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->origin_account_id)->toBe($payerAccount->id);
    expect($transaction->destination_account_id)->toBe($payeeAccount->id);
    expect($transaction->amount)->toBe($value);

    $updatedPayerAccount = $payerAccount->fresh();
    $updatedPayeeAccount = $payeeAccount->fresh();

    expect($updatedPayerAccount->balance)->toBe(100); // 200 - 100
    expect($updatedPayeeAccount->balance)->toBe(100); // 0 + 100

    // Verifica se a transação realmente foi salva no banco
    $this->assertDatabaseHas('transactions', [
        'origin_account_id' => $payerAccount->id,
        'destination_account_id' => $payeeAccount->id,
        'amount' => $value,
        'type' => 'transfer',
    ]);
});

# ./vendor/bin/pest --filter="records the transaction correctly in the database"
test('records the transaction correctly in the database', function () {
    // Fake da resposta de autorização como autorizada
    fakeAuthorizationResponse(true);

    $payerUser = User::factory()->create(['is_supplier' => false]);
    $payerAccount = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 500.0]);

    $payeeUser = User::factory()->create(['is_supplier' => false]);
    $payeeAccount = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 50.0]);

    $value = 150.0;

    $transferService = new TransferService();
    $transaction = $transferService->transfer($payerAccount->id, $payeeAccount->id, $value);

    // Verifica se a transação foi registrada no banco de dados com os dados corretos
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'origin_account_id' => $payerAccount->id,
        'destination_account_id' => $payeeAccount->id,
        'amount' => $value,
        'type' => 'transfer',
    ]);
});