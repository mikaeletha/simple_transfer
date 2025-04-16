<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Exception;

class TransferServiceMockTest extends TestCase
{
    use RefreshDatabase;

    protected $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferService = new TransferService();
        $this->seed();
    }

    public function testAuthorizeTransferSuccess()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ], 200),
        ]);

        $reflection = new \ReflectionClass($this->transferService);
        $method = $reflection->getMethod('authorizeTransfer');
        $method->setAccessible(true);

        $method->invoke($this->transferService);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://util.devi.tools/api/v2/authorize';
        });
    }

    public function testAuthorizeTransferFailure()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'fail',
                'data' => ['authorization' => false],
            ], 200),
        ]);

        $reflection = new \ReflectionClass($this->transferService);
        $method = $reflection->getMethod('authorizeTransfer');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transferência não autorizada pelo serviço externo.');

        $method->invoke($this->transferService);
    }

    public function testAuthorizeTransferErrorResponse()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([], 500),
        ]);

        $reflection = new \ReflectionClass($this->transferService);
        $method = $reflection->getMethod('authorizeTransfer');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transferência não autorizada pelo serviço externo.');

        $method->invoke($this->transferService);
    }

    public function testNotifyPayeeSuccess()
    {
        Http::fake([
            'https://util.devi.tools/api/v1/notify' => Http::response([], 200),
        ]);

        $payee = Account::first();

        $reflection = new \ReflectionClass($this->transferService);
        $method = $reflection->getMethod('notifyPayee');
        $method->setAccessible(true);

        $method->invoke($this->transferService, $payee, 100.50);

        Http::assertSent(function ($request) use ($payee) {
            return $request->url() == 'https://util.devi.tools/api/v1/notify' &&
                $request['user'] == $payee->email &&
                $request['message'] == 'Você recebeu um pagamento de R$ 100,50';
        });
    }

    public function testNotifyPayeeFailure()
    {
        Http::fake([
            'https://util.devi.tools/api/v1/notify' => Http::response([], 500),
        ]);

        $payee = Account::first();

        $reflection = new \ReflectionClass($this->transferService);
        $method = $reflection->getMethod('notifyPayee');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Erro ao enviar notificação.');

        $method->invoke($this->transferService, $payee, 100.50);
    }
}
