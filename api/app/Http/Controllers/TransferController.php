<?php

// namespace App\Http\Controllers;
// use App\Http\Requests\TransferRequest;
// use App\Models\Account;
// use App\Http\Controllers\Controller;
// use App\Services\TransferService;
// use Illuminate\Http\JsonResponse;

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;


class TransferController extends Controller
{
    protected TransferService $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            // Dispara a transferência com as regras e validações
            // $transaction = $this->transferService->transfer(
            //     $request->payer,
            //     $request->payee,
            //     $request->value
            // );
            $validated = $request->validated();

            $transaction = $this->transferService->transfer(
                $validated['payer'],
                $validated['payee'],
                $validated['value']
            );

            return response()->json([
                'success' => true,
                'message' => 'Transferência realizada com sucesso.',
                'data' => $transaction
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            // Erro interno inesperado
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a transferência.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function transfer(TransferRequest $request): JsonResponse
    // {
    //     try {
    //         $amount = $request->input('value');
    //         $payer = Account::findOrFail($request->input('payer'));
    //         $payee = Account::findOrFail($request->input('payee'));

    //         $this->transferService->execute($amount, $payer, $payee);

    //         return response()->json(['message' => 'Transferência realizada com sucesso!'], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 400);
    //     }
    // }
}
