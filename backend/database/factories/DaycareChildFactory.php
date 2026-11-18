<?php

namespace Database\Factories;

use App\Models\DaycareChild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DaycareChild>
 */
class DaycareChildFactory extends Factory
{
    protected $model = DaycareChild::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'age' => fake()->numberBetween(3, 10),
            'parent_name' => fake()->name(),
            'phone' => '+216 ' . fake()->numerify('## ### ###'),
            'schedule' => '8h - 17h',
            'status' => 'present',
            'monthly_fee' => fake()->randomElement([60.00, 80.00, 100.00]),
            'start_date' => now()->toDateString(),
        ];
    }
}
