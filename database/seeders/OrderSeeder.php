<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\Product;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->limit(10)->get();

        Customer::factory(3)->create()->each(function (Customer $customer) use ($products): void {
            $orderCount = fake()->numberBetween(2, 3);

            Order::factory($orderCount)
                ->for($customer)
                ->create()
                ->each(function (Order $order) use ($products): void {
                    $itemCount = fake()->numberBetween(2, 3);
                    $selectedProducts = $products->random(min($itemCount, $products->count()));

                    $subtotal = 0;

                    foreach ($selectedProducts as $product) {
                        $quantity = fake()->numberBetween(1, 3);
                        $unitPrice = $product->price;
                        $itemSubtotal = $unitPrice * $quantity;
                        $subtotal += $itemSubtotal;

                        OrderItem::factory()->for($order)->create([
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'product_sku' => $product->sku,
                            'unit_price' => $unitPrice,
                            'quantity' => $quantity,
                            'subtotal' => $itemSubtotal,
                        ]);
                    }

                    $shipping = $order->shipping_amount;
                    $tax = $order->tax_amount;
                    $order->update([
                        'subtotal' => $subtotal,
                        'total_amount' => $subtotal + $shipping + $tax,
                    ]);
                });
        });
    }
}
