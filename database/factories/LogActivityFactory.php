<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogActivity>
 */
class LogActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'timestamp' => now(),
            'in_bps' => fake()->numberBetween(200_000, 50_000_000),
            'out_bps' => fake()->numberBetween(200_000, 50_000_000),
        ];
    }
}
