<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\OrderItem;
use Minishop\Models\OrderReturn;
use Minishop\Models\ReturnItem;

/**
 * @extends Factory<ReturnItem>
 */
class ReturnItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100, 10000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'return_id' => OrderReturn::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ];
    }
}
