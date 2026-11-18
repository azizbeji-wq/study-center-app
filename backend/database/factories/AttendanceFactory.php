<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'group_name' => 'Groupe Test',
            'subject_name' => 'Mathématiques',
            'session_date' => now()->toDateString(),
            'status' => 'present',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
