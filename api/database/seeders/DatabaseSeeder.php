<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->hasAccounts(1)
            ->create();

        // Cria 20 transações entre contas
        Transaction::factory()
            ->count(20)
            ->create();
    }
}
