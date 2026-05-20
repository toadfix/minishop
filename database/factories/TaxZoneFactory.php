<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\TaxZone;

/**
 * @extends Factory<TaxZone>
 */
class TaxZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'country_code' => fake()->countryCode(),
            'province_code' => null,
            'is_active' => true,
            'priority' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ontario(): static
    {
        return $this->state([
            'name' => 'Ontario',
            'country_code' => 'CA',
            'province_code' => 'ON',
            'is_active' => true,
            'priority' => 10,
        ]);
    }

    public function quebec(): static
    {
        return $this->state([
            'name' => 'Quebec',
            'country_code' => 'CA',
            'province_code' => 'QC',
            'is_active' => true,
            'priority' => 10,
        ]);
    }

    /**
     * Global fallback zone (country_code and province_code both NULL).
     */
    public function wildcard(): static
    {
        return $this->state([
            'name' => 'International',
            'country_code' => null,
            'province_code' => null,
            'is_active' => true,
            'priority' => 0,
        ]);
    }
}
