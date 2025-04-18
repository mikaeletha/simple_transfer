<?php

use App\Models\Account;
use App\Models\User;
use function Pest\Laravel\postJson;

# ./vendor/bin/pest --filter="create a transfer, success"
test('create a transfer, success', function () {
    $payer = User::factory()
        ->has(Account::factory()->state(['balance' => 200.0]))
        ->create(['is_supplier' => false]);

    $payee = User::factory()
        ->has(Account::factory()->state(['balance' => 0]))
        ->create(['is_supplier' => true]);

    $data = [
        'value' => 100.0,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ];

    $response = postJson('/api/transfer', $data);

    try {
        $response->assertCreated();
    } catch (\Throwable $e) {
        dump('Status:', $response->status());
        dump('Body:', $response->json());
        throw $e; 
    }
});

# ./vendor/bin/pest --filter="create a transfer, fails when payer is a supplier"
test('create a transfer, fails when payer is a supplier', function () {
    $payer = User::factory()
        ->has(Account::factory()->state(['balance' => 200.0]))
        ->create(['is_supplier' => true]);

    $payee = User::factory()
        ->has(Account::factory()->state(['balance' => 0]))
        ->create(['is_supplier' => false]);

    $data = [
        'value' => 100.0,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ];

    $response = postJson('/api/transfer', $data);
    $response->assertStatus(422);
    // dump('Status:', $response->status());
    // dump('Body:', $response->json());
});

# ./vendor/bin/pest --filter="create a transfer, fails when insufficient balance"
test('create a transfer, fails when insufficient balance', function () {
    $payer = User::factory()
        ->has(Account::factory()->state(['balance' => 200.0]))
        ->create(['is_supplier' => false]);

    $payee = User::factory()
        ->has(Account::factory()->state(['balance' => 0]))
        ->create(['is_supplier' => true]);

    $data = [
        'value' => 1000.0,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ];

    $response = postJson('/api/transfer', $data);
    $response->assertStatus(422);
});

# ./vendor/bin/pest --filter="create a transfer, fails when negative balance"
test('create a transfer, fails when negative balance', function () {
    $payer = User::factory()
        ->has(Account::factory()->state(['balance' => 200.0]))
        ->create(['is_supplier' => false]);

    $payee = User::factory()
        ->has(Account::factory()->state(['balance' => 0]))
        ->create(['is_supplier' => true]);

    $data = [
        'value' => -100.0,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ];

    $response = postJson('/api/transfer', $data);
    $response->assertStatus(422);
    $response->assertJsonStructure([
        'errors' => [
            'value' => []
        ]
    ]);
    dump('Status:', $response->status());
        dump('Body:', $response->json());
});