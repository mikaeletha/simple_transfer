<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isSupplier = $this->faker->boolean(30); // 30% de chance de ser fornecedor

        return [
            'name' => $this->faker->name,
            'cpf_cnpj' => $isSupplier
                ? $this->faker->numerify('##.###.###/####-##') // CNPJ
                : $this->faker->numerify('###.###.###-##'),    // CPF
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('12345678'),
            'is_supplier' => $isSupplier,
        ];
    }
}
