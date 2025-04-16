<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Transaction;

class TransferServiceTest extends TestCase
{
    // php artisan test --filter=TransferServiceTest

    use RefreshDatabase;

    protected TransferService $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Garante que há dados no banco para os testes
        $this->transferService = new TransferService();
    }

    /**     
     * Testa se uma exceção é lançada ao tentar transferir usando um usuário fornecedor.
     * 
     * Esse teste usa reflection para acessar o método privado `validateTransfer`.
     * Ele busca um usuário fornecedor da seed e espera a exceção com a mensagem específica.
     */
    public function testValidateTransferIsSupplier()
    {
        $payerUser = User::where('is_supplier', true)->firstOrFail();
        $payer = Account::where('user_id', $payerUser->id)->firstOrFail();
        $payee = Account::where('id', '!=', $payer->id)->firstOrFail();

        $service = $this->transferService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateTransfer');
        $method->setAccessible(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Fornecedores não podem realizar transferências.');

        $method->invoke($service, $payer, $payee, 50);
    }

    /**     
     * Testa se uma exceção é lançada ao tentar transferir com saldo insuficiente.
     */
    public function testValidateTransferInsufficientBalance()
    {
        $payerUser = User::where('is_supplier', false)->firstOrFail();
        $payer = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 10]);
        $payee = Account::factory()->create();

        $service = $this->transferService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateTransfer');
        $method->setAccessible(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Saldo insuficiente para realizar a transferência');

        $method->invoke($service, $payer, $payee, 50);
    }

    /**     
     * Testa se uma exceção é lançada ao tentar transferir para si mesmo.
     */
    public function testValidateTransferSelfTransfer()
    {
        $payerUser = User::where('is_supplier', false)->firstOrFail();
        $payer = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 100]);

        $service = $this->transferService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateTransfer');
        $method->setAccessible(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Não é permitido transferir para si mesmo.');

        $method->invoke($service, $payer, $payer, 50);
    }

    /**     
     * Testa a função principal `transfer` executando com sucesso.
     * 
     * Simula autorização via API externa e checa se o saldo é alterado corretamente,
     * além de verificar se a transação foi registrada no banco.
     */
    public function testTransferExecutesSuccessfully()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ]),
            'https://util.devi.tools/api/v1/notify' => Http::response(['success' => true]),
        ]);

        $payerUser = User::factory()->create(['is_supplier' => false]);
        $payeeUser = User::factory()->create();

        $payer = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
        $payee = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 500]);

        $transaction = $this->transferService->transfer($payer->id, $payee->id, 200);

        $this->assertDatabaseHas('transactions', [
            'origin_account_id' => $payer->id,
            'destination_account_id' => $payee->id,
            'amount' => 200,
            'type' => 'transfer',
        ]);

        $this->assertEquals(800, $payer->fresh()->balance);
        $this->assertEquals(700, $payee->fresh()->balance);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    /**     
     * Testa se uma exceção é lançada quando a API externa de autorização nega a operação.
     */
    public function testTransferFailsWhenAuthorizationDenied()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'error',
                'data' => ['authorization' => false],
            ]),
        ]);

        $payerUser = User::factory()->create(['is_supplier' => false]);
        $payeeUser = User::factory()->create();

        $payer = Account::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
        $payee = Account::factory()->create(['user_id' => $payeeUser->id, 'balance' => 500]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transferência não autorizada pelo serviço externo.');

        $this->transferService->transfer($payer->id, $payee->id, 200);
    }
}
