<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use DomainException;

class TransferService
{
    public function transfer(int $payerId, int $payeeId, float $amount): Transaction
    {
        $payer = Account::findOrFail($payerId);
        $payee = Account::findOrFail($payeeId);

        if ($payer->is_supplier) {
            throw new DomainException('Fornecedores não podem realizar transferências.');
        }

        if ($payer->balance < $amount) {
            throw new DomainException('Saldo insuficiente para realizar a transferência.');
        }

        if ($payer->id === $payee->id) {
            throw new DomainException('Não é permitido transferir para si mesmo.');
        }

        // Chamada externa para serviço autorizador
        $response = Http::withoutVerifying()->get('https://util.devi.tools/api/v2/authorize');
        $responseData = $response->json();

        if (
            !$response->ok() ||
            !isset($responseData['authorization']) ||
            $responseData['authorization'] !== true
        ) {
            'Transferência não autorizada pelo serviço externo. Resposta: ' . json_encode($responseData);
        }


        // Transação
        return DB::transaction(function () use ($payer, $payee, $amount) {
            // Debita e credita
            $payer->balance -= $amount;
            $payer->save();

            $payee->balance += $amount;
            $payee->save();

            // Registra a transação
            $transaction = Transaction::create([
                'origin_account_id' => $payer->id,
                'destination_account_id' => $payee->id,
                'amount' => $amount,
                'type' => 'transfer'
            ]);

            try {
                Http::withoutVerifying()->post('https://util.devi.tools/api/v1/notify', [
                    'user' => $payee->email,
                    'message' => 'Você recebeu um pagamento de R$ ' . number_format($amount, 2, ',', '.')
                ]);
            } catch (\Throwable $e) {
                logger()->warning('Erro ao enviar notificação: ' . $e->getMessage());
            }

            return $transaction;
        });
    }
}
