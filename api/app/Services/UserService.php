<?php

namespace App\Services;

use App\Models\User;
use App\Models\Account;

class UserService
{
    public function getCustomUserList()
    {
       $user = User::join('accounts', 'users.id', '=', 'accounts.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.is_supplier','accounts.balance')
            ->orderBy('users.name', 'asc')
            ->get();

        return $user;
    }
}
