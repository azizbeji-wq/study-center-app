<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'level' => fake()->randomElement(['Baccalauréat', '3ème Secondaire', '2ème Secondaire', '9ème de base']),
            'group_name' => null,
            'phone' => '+216 ' . fake()->numerify('## ### ###'),
            'status' => 'active',
            'parent_phone' => '+216 ' . fake()->numerify('## ### ###'),
            'is_remise' => false,
            'remise' => 0,
            'discount_type' => 'percentage',
            'base_fee' => fake()->randomElement([100.00, 120.00, 150.00]),
        ];
    }
}
