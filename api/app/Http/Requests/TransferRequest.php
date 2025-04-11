<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'value' => 'required|numeric|min:0.01',
            'payer' => 'required|integer|exists:accounts,id',
            'payee' => 'required|integer|exists:accounts,id|different:payer',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'O valor da transferência é obrigatório.',
            'value.numeric' => 'O valor da transferência deve ser numérico.',
            'value.min' => 'O valor mínimo para transferência é R$0,01.',

            'payer.required' => 'O pagador é obrigatório.',
            'payer.exists' => 'O pagador informado não existe.',

            'payee.required' => 'O recebedor é obrigatório.',
            'payee.exists' => 'O recebedor informado não existe.',
            'payee.different' => 'Não é permitido transferir para si mesmo.',
        ];
    }
}
