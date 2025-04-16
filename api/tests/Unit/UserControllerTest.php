<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\UserController;
use App\Models\Account;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $userServiceMock;
    protected UserController $userController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->userController = new UserController($this->userServiceMock);
        Validator::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetUsersReturnsJson(): void
    {
        $this->userServiceMock->shouldReceive('getCustomUserList')
            ->once()
            ->andReturn(collect());

        $response = $this->userController->getUsers();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateUserSuccess()
    {
        $requestData = [
            'name' => 'New User',
            'email' => 'new.user@example.com',
            'password' => 'password123',
            'is_supplier' => 0,
            'cpf_cnpj' => '12345678901',
        ];
        $request = new Request($requestData);

        $createdUser = new User(['id' => 1, ...$requestData]);
        $createdAccount = new Account(['id' => 1, 'user_id' => 1, 'account_number' => '12345', 'balance' => 0.0]);

        $this->userServiceMock->shouldReceive('createUser')
            ->once()
            ->with($requestData)
            ->andReturn(['user' => $createdUser, 'account' => $createdAccount]);

        $response = $this->userController->create($request);

        $this->assertEquals(201, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Usuário criado com sucesso!', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('user', $responseData['data']);
        $this->assertArrayHasKey('account', $responseData['data']);
        $this->assertEquals($createdUser->toArray(), $responseData['data']['user']);
    }
}
