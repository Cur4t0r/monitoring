<?php

namespace Database\Factories;

use App\Models\Opd;
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
            'opd_id'    => Opd::factory(),

            // Timestamp acak dalam rentang 1 tahun terakhir
            // Seeder akan meng-override dengan timestamp yang sudah dipola
            'timestamp' => fake()->dateTimeBetween('-1 year', 'now'),

            // Range bandwidth realistis: 0.3 – 40 Mbps
            'in_bps'    => fake()->numberBetween(300_000, 40_000_000),
            'out_bps'   => fake()->numberBetween(300_000, 40_000_000),
        ];
    }
}
