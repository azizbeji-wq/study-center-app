<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Mathématiques', 'Physique-Chimie', 'Sciences SVT', 'Français', 
            'Anglais', 'Philosophie', 'Informatique', 'Arabe', 'Économie'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->safeHexColor(),
            'is_active' => true,
        ];
    }
}
