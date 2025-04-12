<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use DomainException;
use Exception;

class TransferService
{
    public function transfer(int $payerId, int $payeeId, float $value): Transaction
    {
        $payer = Account::findOrFail($payerId);
        $payee = Account::findOrFail($payeeId);

        $this->validateTransfer($payer, $payee, $value);
        $this->authorizeTransfer();

        return DB::transaction(function () use ($payer, $payee, $value) {
            $this->performTransfer($payer, $payee, $value);
            $transaction = $this->recordTransaction($payer, $payee, $value);
            $this->notifyPayee($payee, $value);

            return $transaction;
        });
    }

    private function validateTransfer(Account $payer, Account $payee, float $value): void
    {
        $payer = User::find($payer->user_id);
        if ($payer->is_supplier) {
            throw new DomainException('Fornecedores não podem realizar transferências.');
        }

        if ($payer->balance < $value) {
            throw new DomainException('Saldo insuficiente para realizar a transferência.');
        }

        if ($payer->id === $payee->id) {
            throw new DomainException('Não é permitido transferir para si mesmo.');
        }
    }

    private function authorizeTransfer(): void
    {
        $response = Http::withoutVerifying()->get('https://util.devi.tools/api/v2/authorize');
        $responseData = $response->json();

        if (
            !$response->ok() ||
            !isset($responseData['status']) ||
            $responseData['status'] !== 'success' ||
            !isset($responseData['data']['authorization']) ||
            $responseData['data']['authorization'] !== true
        ) {
            logger()->error('Transferência não autorizada pelo serviço externo. Resposta: ' . json_encode($responseData));
            throw new Exception('Transferência não autorizada pelo serviço externo.');
        }
    }

    private function performTransfer(Account $payer, Account $payee, float $value): void
    {
        $payer->balance -= $value;
        $payer->save();

        $payee->balance += $value;
        $payee->save();
    }

    private function recordTransaction(Account $payer, Account $payee, float $value): Transaction
    {
        return Transaction::create([
            'origin_account_id' => $payer->id,
            'destination_account_id' => $payee->id,
            'amount' => $value,
            'type' => 'transfer',
        ]);
    }

    private function notifyPayee(Account $payee, float $value): void
    {
        try {
            Http::withoutVerifying()->post('https://util.devi.tools/api/v1/notify', [
                'user' => $payee->email,
                'message' => 'Você recebeu um pagamento de R$ ' . number_format($value, 2, ',', '.'),
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Erro ao enviar notificação: ' . $e->getMessage());
        }
    }
}
