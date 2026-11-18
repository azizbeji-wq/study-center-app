<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => 'Groupe ' . fake()->unique()->word() . ' ' . fake()->randomLetter(),
            'level' => fake()->randomElement(['Baccalauréat', '3ème Secondaire', '2ème Secondaire']),
            'subject' => fake()->randomElement(['Mathématiques', 'Physique', 'Sciences']),
            'max_capacity' => fake()->numberBetween(8, 20),
            'notes' => fake()->sentence(),
        ];
    }
}
