<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;

/**
 * @extends Factory<TaxZoneRate>
 */
class TaxZoneRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tax_zone_id' => TaxZone::factory(),
            'name' => fake()->randomElement(['GST', 'HST', 'PST', 'QST']),
            'name_fr' => null,
            'rate' => fake()->randomFloat(4, 0, 20),
            'is_compound' => false,
            'is_shipping_taxable' => false,
            'sort_order' => 0,
        ];
    }

    public function compound(): static
    {
        return $this->state(['is_compound' => true, 'sort_order' => 2]);
    }

    public function gst(): static
    {
        return $this->state([
            'name' => 'GST',
            'name_fr' => 'TPS',
            'rate' => 5.0,
            'is_compound' => false,
            'sort_order' => 1,
        ]);
    }

    public function hst(): static
    {
        return $this->state([
            'name' => 'HST',
            'name_fr' => null,
            'rate' => 13.0,
            'is_compound' => false,
            'sort_order' => 1,
        ]);
    }
}
