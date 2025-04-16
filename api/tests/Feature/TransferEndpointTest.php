<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class TransferEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_successfully()
    {
        // simula que o serviço de autorização responde com sucesso
        Http::fake([
            'autorizador-exemplo.com/api/*' => Http::response(['message' => 'Autorizado'], 200),

            // Simula falha no serviço de notificação
            'notificacao-exemplo.com/api/*' => Http::response(null, 500),
        ]);

        $payer = User::factory()->create(['is_supplier' => false]);
        $payee = User::factory()->create(); 

        Account::factory()->create([
            'user_id' => $payer->id,
            'balance' => 1000.00, 
        ]);

        Account::factory()->create([
            'user_id' => $payee->id,
            'balance' => 0.00, 
        ]);

        $payload = [
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => 100.00,
        ];

        $response = $this->postJson('/api/transfer', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'amount',
                'origin_account_id',  
                'destination_account_id',  
                'created_at',
                'updated_at',
                'type'
            ]
        ]);
        $this->assertDatabaseHas('transactions', [
            'origin_account_id' => $payer->id,  
            'destination_account_id' => $payee->id,  
            'amount' => 100.00,
        ]);
    }

    public function test_transfer_validation_fails(): void
    {
        $payload = [
            'payer' => null,
            'payee' => null,
            'value' => null,
        ];

        $response = $this->postJson('/api/transfer', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payer', 'payee', 'value']);
    }
}
