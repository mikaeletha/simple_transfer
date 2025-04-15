<?php

namespace App\Services;

use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;


class UserService
{
    public function getCustomUserList(): Collection
    {
        $user = User::join('accounts', 'users.id', '=', 'accounts.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.is_supplier', 'accounts.balance', 'accounts.account_number')
            ->orderBy('users.name', 'asc')
            ->get();

        return $user;
    }

    public function createUser(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $accountNumber = $this->generateUniqueAccountNumber();

            $account = Account::create([
                'user_id' => $user->id,
                'account_number' => $accountNumber,
                'balance' => 0.0,
            ]);

            return ['user' => $user, 'account' => $account];
        });
    }

    public function generateUniqueAccountNumber(): string
    {
        do {
            $number = 'BR' . mt_rand((int)1e9, (int)9e9) . Str::upper(Str::random(1));
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }
}
