<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Enums\ReturnReason;
use Minishop\Enums\ReturnStatus;
use Minishop\Models\Order;
use Minishop\Models\OrderReturn;

/**
 * @extends Factory<OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'return_number' => '',
            'order_id' => Order::factory(),
            'status' => ReturnStatus::Requested->value,
            'reason' => fake()->randomElement(ReturnReason::cases())->value,
            'notes' => fake()->optional(0.6)->sentence(),
            'admin_notes' => null,
            'refund_amount' => 0,
            'stripe_refund_id' => null,
            'restocked' => false,
            'refunded_at' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReturnStatus::Requested->value]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReturnStatus::Approved->value]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Rejected->value,
            'admin_notes' => fake()->sentence(),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Received->value,
            'restocked' => true,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Refunded->value,
            'restocked' => true,
            'refund_amount' => fake()->numberBetween(100, 10000),
            'stripe_refund_id' => 're_'.fake()->regexify('[a-zA-Z0-9]{24}'),
            'refunded_at' => now(),
        ]);
    }
}
