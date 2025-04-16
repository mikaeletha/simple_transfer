<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\TransferController;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $transferServiceMock;
    protected TransferController $transferController;
    protected Account $payerAccount;
    protected Account $payeeAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferServiceMock = Mockery::mock(TransferService::class);
        $this->transferController = new TransferController($this->transferServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function testTransferSuccess(): void
    {
        $payerAccount = Account::factory()->create(['balance' => 100.00]);
        $payeeAccount = Account::factory()->create(['balance' => 50.00]);
        $requestData = [
            'payer' => $payerAccount->id,
            'payee' => $payeeAccount->id,
            'value' => 30.00,
        ];
        $request = new Request($requestData);
        $transactionData = new Transaction(['id' => 1, 'payer_account_id' => $payerAccount->id, 'payee_account_id' => $payeeAccount->id, 'amount' => 30.00]);

        $this->transferServiceMock->shouldReceive('transfer')
            ->once()
            ->with($payerAccount->id, $payeeAccount->id, 30.00)
            ->andReturn($transactionData);

        $response = $this->transferController->transfer($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Transferência realizada com sucesso!', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($transactionData->toArray(), $responseData['data']);
    }

    public function testTransferValidationFails(): void
    {
        $requestData = [
            'payee' => '1',
        ];
        $request = new Request($requestData);

        $response = $this->transferController->transfer($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
        $this->assertArrayHasKey('payer', $responseData['errors']);
        $this->assertArrayHasKey('value', $responseData['errors']);
    }

    public function testTransferDomainException(): void
    {
        $payerAccount = Account::factory()->create(['balance' => 10.00]);
        $payeeAccount = Account::factory()->create(['balance' => 50.00]);
        $requestData = [
            'payer' => $payerAccount->id,
            'payee' => $payeeAccount->id,
        ];
        $request = new Request($requestData);
        $errorMessage = 'Saldo insuficiente para a transferência.';

        $this->transferServiceMock->shouldReceive('transfer')
            ->once()
            ->with($payerAccount->id, $payeeAccount->id, 100.00)
            ->andThrow(new \DomainException($errorMessage));

        $response = $this->transferController->transfer($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals($errorMessage, $responseData['error']);
    }

    public function testTransferGenericException(): void
    {
        $payerAccount = Account::factory()->create();
        $payeeAccount = Account::factory()->create();
        $requestData = [
            'payer' => $payerAccount->id,
            'payee' => $payeeAccount->id,
            'value' => 50.00,
        ];
        $request = new Request($requestData);
        $errorMessage = 'Erro interno ao processar a transferência.';

        $this->transferServiceMock->shouldReceive('transfer')
            ->once()
            ->with($payerAccount->id, $payeeAccount->id, 50.00)
            ->andThrow(new \Exception($errorMessage));

        $response = $this->transferController->transfer($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Erro interno do servidor.', $responseData['error']);
    }
}
