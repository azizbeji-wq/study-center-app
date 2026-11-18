<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'subject' => fake()->randomElement(['Mathématiques', 'Physique', 'Sciences']),
            'phone' => '+216 ' . fake()->numerify('## ### ###'),
            'email' => fake()->unique()->safeEmail(),
            'weekly_lessons' => fake()->numberBetween(2, 10),
            'salary_per_lesson' => fake()->randomElement([30.00, 35.00, 40.00]),
            'fixed_student_rate' => 0,
            'center_fixed_per_student' => 0,
            'remuneration_type' => 'per_lesson',
            'rate_percentage' => 0,
            'payout_status' => 'none',
        ];
    }
}
