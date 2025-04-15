<?php

namespace App\Http\Controllers;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DomainException;
use Exception;
use Illuminate\Support\Facades\Validator;


class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    private function validateTransfer(Request $request)
    {
        return Validator::make($request->all(), [
            'payer' => 'required|exists:accounts,id',
            'payee' => 'required|exists:accounts,id|different:payer',
            'value' => 'required|numeric|min:0.01',
        ], [
            'payer.required' => 'O campo pagador é obrigatório.',
            'payer.exists' => 'O pagador informado não existe.',
            'payee.required' => 'O campo recebedor é obrigatório.',
            'payee.exists' => 'O recebedor informado não existe.',
            'payee.different' => 'O pagador e o recebedor devem ser diferentes.',
            'value.required' => 'O campo valor é obrigatório.',
            'value.numeric' => 'O campo valor deve ser numérico.',
            'value.min' => 'O valor mínimo para transferência é 0.01.',
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validator = $this->validateTransfer($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            logger()->info('Dados da requisição:', $request->all());
            $transaction = $this->transferService->transfer(
                $request->input('payer'),
                $request->input('payee'),
                $request->input('value')
            );

            return response()->json([
                'message' => 'Transferência realizada com sucesso!',
                'data' => $transaction,
            ]);
        } catch (\DomainException $e) {
            logger()->error('Erro de domínio:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            logger()->error('Erro interno do servidor:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno do servidor.'], 500);
        }
    }

}