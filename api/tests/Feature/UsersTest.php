<?php

use App\Models\Account;
use App\Models\User;
use function Pest\Laravel\postJson;

# ./vendor/bin/pest --filter="list users"
test('list users', function () {
    $users = User::factory()
    ->count(3)
    ->has(Account::factory())
    ->create();

    $this->get('/api/users')
        ->dump()
        ->assertStatus(200)
        ->assertJsonCount(3);
});

# ./vendor/bin/pest --filter="create user, success"
test('create user, success', function () {
    $data = [
        'name' => 'Ana Paula',
        'cpf_cnpj' => '12345678900',
        'email' => 'ana@example.com',
        'password' => 'senha123',
        'is_supplier' => 0
    ];

    postJson('/api/user', $data)
    ->assertCreated();
});
