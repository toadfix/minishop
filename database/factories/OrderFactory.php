<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Enums\OrderStatus;
use Minishop\Models\Customer;
use Minishop\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(500, 50000);
        $shipping = fake()->randomElement([0, 200, 500, 800]);
        $tax = (int) ($subtotal * 0.12);
        $total = $subtotal + $shipping + $tax;

        return [
            'order_number' => '',
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending->value,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'shipping_name' => fake()->name(),
            'shipping_address_line1' => fake()->streetAddress(),
            'shipping_address_line2' => fake()->optional(0.3)->secondaryAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->state(),
            'shipping_postcode' => fake()->postcode(),
            'shipping_country' => 'CA',
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Pending->value]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Processing->value]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Shipped->value]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Delivered->value]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Cancelled->value]);
    }
}
