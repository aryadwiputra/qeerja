<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\KeyResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeyResult>
 */
class KeyResultFactory extends Factory
{
    protected $model = KeyResult::class;

    public function definition(): array
    {
        $targetValue = fake()->numberBetween(10, 1000);

        return [
            'goal_id' => Goal::factory(),
            'title' => fake()->sentence(3),
            'status' => fake()->randomElement(['not_started', 'in_progress', 'achieved']),
            'current_value' => fake()->numberBetween(0, $targetValue),
            'target_value' => $targetValue,
        ];
    }

    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'not_started',
            'current_value' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'current_value' => fake()->numberBetween(1, $attributes['target_value'] - 1),
        ]);
    }

    public function achieved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'achieved',
            'current_value' => $attributes['target_value'],
        ]);
    }
}
