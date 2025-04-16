<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TransferService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    private function validateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'is_supplier' => ['required', Rule::in([0, 1])],
            'cpf_cnpj' => [
                'required',
                'unique:users,cpf_cnpj',
                Rule::when($request->input('is_supplier') == 0, ['digits:11']),
                Rule::when($request->input('is_supplier') == 1, ['digits:14']),
            ],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Formato de e-mail inválido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'is_supplier.required' => 'Informe se é fornecedor ou não.',
            'is_supplier.in' => 'Valor inválido para fornecedor (use 0 ou 1).',
            'cpf_cnpj.required' => 'O CPF ou CNPJ é obrigatório.',
            'cpf_cnpj.unique' => 'Este CPF ou CNPJ já está cadastrado.',
            'cpf_cnpj.digits' => 'O :attribute deve conter :digits dígitos.',
        ]);

        return $validator;
    }

    public function getUsers(): JsonResponse
    {
        return response()->json([$this->userService->getCustomUserList()]);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = $this->validateUser($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $this->userService->createUser($request->all());
            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            logger()->error('Erro ao criar usuário:', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'Erro interno ao criar usuário.'
            ], 500);
        }
    }
}
