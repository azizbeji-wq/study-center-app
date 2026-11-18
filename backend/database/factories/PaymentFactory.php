<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'student_name' => fake()->name(),
            'amount' => fake()->randomElement([50.00, 100.00, 120.00, 150.00]),
            'date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
            'status' => 'paid',
            'payment_method' => fake()->randomElement(['especes', 'cheque', 'virement']),
        ];
    }
}
