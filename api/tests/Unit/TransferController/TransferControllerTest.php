<?php

use App\Http\Controllers\TransferController;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;

# ./vendor/bin/pest --filter="TransferControllerTest"
uses(Tests\TestCase::class, RefreshDatabase::class)->in(__DIR__);

beforeEach(function () {
    $this->transferServiceMock = Mockery::mock(TransferService::class);
    $this->transferController = new TransferController($this->transferServiceMock);
});

afterEach(function () {
    Mockery::close();
});

# ./vendor/bin/pest --filter="transfer succeeds when all data is valid and sufficient balance"
test('transfer succeeds when all data is valid and sufficient balance', function () {
    $payer = Account::factory()->create(['balance' => 100.00]);
    $payee = Account::factory()->create(['balance' => 50.00]);

    $requestData = [
        'payer' => $payer->id,
        'payee' => $payee->id,
        'value' => 30.00,
    ];

    $request = new Request($requestData);

    $transaction = new Transaction([
        'id' => 1,
        'payer_account_id' => $payer->id,
        'payee_account_id' => $payee->id,
        'amount' => 30.00
    ]);

    $this->transferServiceMock->shouldReceive('transfer')
        ->once()
        ->with($payer->id, $payee->id, 30.00)
        ->andReturn($transaction);

    $response = $this->transferController->transfer($request);

    expect($response->getStatusCode())->toBe(201);

    $data = json_decode($response->getContent(), true);
    expect($data)
        ->toHaveKey('message', 'Transferência realizada com sucesso!')
        ->toHaveKey('data')
        ->and($data['data'])->toEqual($transaction->toArray());
});

# ./vendor/bin/pest --filter="returns 422 if value is below minimum and accounts are invalid"
test('returns 422 if value is below minimum and accounts are invalid', function () {
    $controller = new TransferController(Mockery::mock(TransferService::class));

    $request = new Request([
        'payer' => 1,
        'payee' => 2,
        'value' => 0.00
    ]);

    $response = $controller->transfer($request);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data['errors'])->toHaveKey('payer')
        ->and($data['errors'])->toHaveKey('payee')
        ->and($data['errors'])->toHaveKey('value');
});

# ./vendor/bin/pest --filter="returns 422 if value is non-numeric and payer equals payee"
test('returns 422 if value is non-numeric and payer equals payee', function () {
    $controller = new TransferController(Mockery::mock(TransferService::class));

    $request = new Request([
        'payer' => 1,
        'payee' => 1,
        'value' => 'value'
    ]);

    $response = $controller->transfer($request);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data['errors'])->toHaveKey('payee')
        ->and($data['errors'])->toHaveKey('value');
});

# ./vendor/bin/pest --filter="returns 422 if transfer fails due to insufficient funds"
test('returns 422 if transfer fails due to insufficient funds', function () {
    $payer = Account::factory()->create(['balance' => 100.00]);
    $payee = Account::factory()->create(['balance' => 50.00]);
    $value = 300.00;

    $request = new Request([
        'payer' => $payer->id,
        'payee' => $payee->id,
        'value' => $value,
    ]);

    $this->transferServiceMock->shouldReceive('transfer')
        ->once()
        ->with($payer->id, $payee->id, $value)
        ->andThrow(new DomainException('Saldo insuficiente para realizar a transferência. Seu saldo é: R$' . $payer->balance));

    $response = $this->transferController->transfer($request);

    expect($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data['message'])->toBe('Saldo insuficiente para realizar a transferência. Seu saldo é: R$' . $payer->balance);
});

# ./vendor/bin/pest --filter="returns 422 if payer or payee account ID does not exist"
test('returns 422 if payer or payee account ID does not exist', function () {
    $request = new Request([
        'payer' => 9999,
        'payee' => 8888,
        'value' => 50.00,
    ]);

    $response = $this->transferController->transfer($request);

    expect($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data['errors'])->toHaveKey('payer')
        ->and($data['errors'])->toHaveKey('payee');
});

# ./vendor/bin/pest --filter="returns 422 if required fields are missing"
test('returns 422 if required fields are missing', function () {
    $request = new Request([]); // sem dados

    $response = $this->transferController->transfer($request);

    expect($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data['errors'])->toHaveKey('payer')
        ->and($data['errors'])->toHaveKey('payee')
        ->and($data['errors'])->toHaveKey('value');
});
