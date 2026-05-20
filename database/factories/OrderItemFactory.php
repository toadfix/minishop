<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100, 20000);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_name' => fake()->words(3, true),
            'product_sku' => fake()->optional(0.6)->regexify('[A-Z]{3}-[0-9]{4}'),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }
}
