<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Loyer du local', 'Facture STEG Électricité', 'Facture SONEDE Eau', 'Fournitures de bureau', 'Connexion Fibre Internet']),
            'amount' => fake()->randomElement([150.00, 250.00, 450.00, 800.00]),
            'category' => fake()->randomElement(['loyer', 'électricité', 'fournitures', 'entretien', 'autres']),
            'date' => now()->toDateString(),
            'payment_method' => fake()->randomElement(['especes', 'cheque', 'virement']),
            'is_recurring' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
