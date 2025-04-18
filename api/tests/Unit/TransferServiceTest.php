<!-- <?php

use App\Models\Account;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->transferService = new TransferService();
});

// test('lança exceção ao transferir usando um usuário fornecedor', function () {
//     $payerUser = User::where('is_supplier', true)->firstOrFail();
//     $payer = Account::where('user_id', $payerUser->id)->firstOrFail();
//     $payee = Account::where('id', '!=', $payer->id)->firstOrFail();

//     $reflection = new ReflectionClass($this->transferService);
//     $method = $reflection->getMethod('validateTransfer');
//     $method->setAccessible(true);

//     $this->expectException(DomainException::class);
//     $this->expectExceptionMessage('Fornecedores não podem realizar transferências.');

//     $method->invoke($this->transferService, $payer, $payee, 50);
// });


## validateTransfer(Account $payer, Account $payee, float $value)
// Deve lançar exceção se o usuário for fornecedor.
// Deve lançar exceção se o saldo do pagador for insuficiente.
// Deve lançar exceção se o pagador tentar transferir para si mesmo.
// Deve passar sem exceção se todos os critérios forem válidos.

## authorizeTransfer()
// Deve lançar exceção se o serviço externo retornar erro HTTP.
// Deve lançar exceção se o status não for 'success'.
// Deve lançar exceção se authorization não for true.
// Deve não lançar exceção se a resposta for válida e autorizada.
// Obs: Aqui o ideal é usar um mock do Http::fake().

## performTransfer(Account $payer, Account $payee, float $value)
// Deve subtrair o valor do saldo do pagador.
// Deve adicionar o valor ao saldo do recebedor.
// Deve persistir ambos os saldos no banco de dados.
// Aqui você pode usar mocks dos modelos ou um banco em memória (como SQLite).

## recordTransaction(Account $payer, Account $payee, float $value)
// Deve criar uma transação com os dados corretos.
// Deve retornar a instância da transação criada.

## notifyPayee(Account $payee, float $value)
// Deve chamar a API de notificação com o e-mail e valor corretos.
// Deve registrar o erro, mas não lançar exceção, se a API falhar.
// Deve registrar erro, mas não lançar exceção, se ocorrer uma exceção durante a chamada.
// Aqui também é essencial o uso de Http::fake() para evitar chamadas reais.

## transfer($payerId, $payeeId, $value)
// Essa seria um pouco mais de teste de integração, mas ainda pode ser feita com mocks de métodos internos se quiser mantê-la como teste unitário:
// Deve realizar uma transferência com sucesso (todos os métodos internos devem ser chamados corretamente).
// Deve lançar exceção se qualquer validação falhar.
// Deve lançar exceção se a autorização falhar.
// Deve funcionar com valor decimal (ex: 10.75). -->