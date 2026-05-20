<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Enums\CouponType;
use Minishop\Models\Coupon;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CouponType::cases());

        return [
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'value' => $type === CouponType::Percentage ? fake()->numberBetween(5, 50) : fake()->numberBetween(500, 5000),
            'minimum_order_amount' => null,
            'expiry_date' => null,
            'usage_limit' => null,
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(['type' => CouponType::Percentage, 'value' => 10]);
    }

    public function fixed(): static
    {
        return $this->state(['type' => CouponType::Fixed, 'value' => 500]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(['expiry_date' => now()->subDay()]);
    }
}
