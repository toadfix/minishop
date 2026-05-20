<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Cart;
use Minishop\Models\CartItem;
use Minishop\Models\Product;
use Minishop\Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    private function withCartToken(string $token): static
    {
        return $this->withCredentials()->withUnencryptedCookie('cart_token', $token);
    }

    public function test_can_view_empty_cart(): void
    {
        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure(['id', 'item_count', 'subtotal', 'items'])
            ->assertJsonPath('item_count', 0);
    }

    public function test_can_add_item(): void
    {
        $product = Product::factory()->create(['price' => 1500, 'is_active' => true]);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('item_count', 1)
            ->assertJsonPath('subtotal', 1500);
    }

    public function test_can_update_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'api-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $item = $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'unit_price' => 500]);

        $this->withCartToken($token)->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('item_count', 3);
    }

    public function test_can_remove_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'api-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $item = $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2, 'unit_price' => 500]);

        $this->withCartToken($token)->deleteJson("/api/v1/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('item_count', 0);
    }

    public function test_can_clear_cart(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'api-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 5, 'unit_price' => 500]);

        $this->withCartToken($token)->deleteJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('item_count', 0);
    }

    public function test_cannot_modify_another_sessions_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);

        $otherCart = Cart::factory()->create(['session_id' => 'other-api-cart-token']);
        $otherItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $this->withCartToken('my-api-cart-token')
            ->patchJson("/api/v1/cart/items/{$otherItem->id}", ['quantity' => 99])
            ->assertForbidden();
    }
}
