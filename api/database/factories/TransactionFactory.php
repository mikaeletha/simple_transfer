<?php

namespace Database\Factories;
use App\Models\User;
use app\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originUser = User::where('is_supplier', false)->inRandomOrder()->first();
        $originAccount = Account::where('user_id', $originUser->id)->inRandomOrder()->first();
        $destinationAccount = Account::inRandomOrder()->where('id', '!=', $originAccount->id)->first();

        return [
            'origin_account_id' => $originAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => 'transfer',
        ];
    }
}
