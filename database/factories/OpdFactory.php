<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Opd>
 */
class OpdFactory extends Factory
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
            'nama_opd' => 'OPD ' . fake()->city(),
            'nama_perangkat' => 'Router-' . fake()->numberBetween(1, 99),
            'ip_address' => fake()->ipv4(),
            'interface' => 'eth0',
            'keterangan' => 'Perangkat utama OPD',
        ];
    }
}
