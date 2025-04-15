<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

# Executa todos os testes dessa classe:
# php artisan test --filter=UserServiceTest
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->userService = new UserService();
    }

    # Testa se o método getCustomUserList retorna uma Collection
    # php artisan test --filter=UserServiceTest::testGetCustomUserListCollection
    public function testGetCustomUserListCollection()
    {
        $users = $this->userService->getCustomUserList();
        $this->assertInstanceOf(Collection::class, $users, "Deveria ser uma instância de Collection");
    }

    # Testa se os campos retornados por getCustomUserList não estão nulos
    # php artisan test --filter=UserServiceTest::testGetCustomUserListFields
    public function testGetCustomUserListFields()
    {
        $result = $this->userService->getCustomUserList();

        foreach ($result as $user) {
            $this->assertNotNull($user->id, 'O campo id está ausente');
            $this->assertNotNull($user->name, 'O campo name está ausente');
            $this->assertNotNull($user->email, 'O campo email está ausente');
            $this->assertNotNull($user->is_supplier, 'O campo is_supplier está ausente');
            $this->assertNotNull($user->account_number, 'O campo account_number está ausente');
            $this->assertNotNull($user->balance, 'O campo balance está ausente');
        }
    }

    # Testa se os IDs retornados por getCustomUserList são únicos
    # php artisan test --filter=UserServiceTest::testGetCustomUserListUniqueUserIds
    public function testGetCustomUserListUniqueUserIds()
    {
        $result = $this->userService->getCustomUserList();
        $userIds = $result->pluck('id')->toArray();

        $this->assertCount(count($userIds), array_unique($userIds), "Existem IDs duplicados de usuários.");
    }

    # Testa se getCustomUserList não gera exceções SQL ou outros erros
    # php artisan test --filter=UserServiceTest::testGetCustomUserListNoSqlErrors
    public function testGetCustomUserListNoSqlErrors()
    {
        try {
            $result = $this->userService->getCustomUserList();
            $this->assertNotNull($result, "O resultado da consulta é nulo.");
        } catch (\Exception $e) {
            $this->fail("A execução do método gerou uma exceção: " . $e->getMessage());
        }
    }

    # Testa se createUser cria corretamente um novo usuário
    # php artisan test --filter=UserServiceTest::testCreateUser
    public function testCreateUser()
    {
        $data = [
            'name' => 'João da Silva',
            'email' => 'joao@example.com',
            'cpf_cnpj' => '123.456.789-00',
            'password' => 'senha123',
            'is_supplier' => false,
        ];

        $result = $this->userService->createUser($data);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
            'name' => 'João da Silva',
        ]);

        $this->assertEquals($data['email'], $result['user']->email);
        $this->assertTrue(password_verify('senha123', $result['user']->password));
    }

    # Testa se createUser também cria a conta vinculada ao usuário
    # php artisan test --filter=UserServiceTest::testCreateUserCreatesAccount
    public function testCreateUserCreatesAccount()
    {
        $data = [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'cpf_cnpj' => '987.654.321-00',
            'password' => 'senha456',
            'is_supplier' => true,
        ];

        $result = $this->userService->createUser($data);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $result['user']->id,
            'account_number' => $result['account']->account_number,
            'balance' => 0.0,
        ]);

        $this->assertEquals($result['user']->id, $result['account']->user_id);
    }

    # Testa se createUser gera números de conta únicos para cada usuário
    # php artisan test --filter=UserServiceTest::testCreateUserGeneratesUniqueAccountNumber
    public function testCreateUserGeneratesUniqueAccountNumber()
    {
        $data1 = [
            'name' => 'Usuário 1',
            'email' => 'u1@example.com',
            'cpf_cnpj' => '000.000.000-01',
            'password' => 'teste123',
            'is_supplier' => false,
        ];

        $data2 = [
            'name' => 'Usuário 2',
            'email' => 'u2@example.com',
            'cpf_cnpj' => '000.000.000-02',
            'password' => 'teste456',
            'is_supplier' => false,
        ];

        $result1 = $this->userService->createUser($data1);
        $result2 = $this->userService->createUser($data2);

        $this->assertNotEquals(
            $result1['account']->account_number,
            $result2['account']->account_number,
            "Os números de conta devem ser únicos"
        );
    }

    # Testa se generateUniqueAccountNumber retorna uma string
    # php artisan test --filter=UserServiceTest::testGenerateUniqueAccountNumberReturnsString
    public function testGenerateUniqueAccountNumberReturnsString()
    {
        $number = $this->userService->generateUniqueAccountNumber();
        $this->assertIsString($number, "Deveria retornar uma string");
    }

    # Testa se o formato do número de conta está correto (BR + 10 dígitos + 1 letra)
    # php artisan test --filter=UserServiceTest::testGenerateUniqueAccountNumberHasCorrectFormat
    public function testGenerateUniqueAccountNumberHasCorrectFormat()
    {
        $number = $this->userService->generateUniqueAccountNumber();

        $this->assertMatchesRegularExpression(
            '/^BR\d{10}[A-Z]$/',
            $number,
            'O número da conta não está no formato BR + 10 dígitos + 1 letra maiúscula.'
        );
    }
}
