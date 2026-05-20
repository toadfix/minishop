<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Cart;
use Minishop\Models\CartItem;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    /** Set up the cart token cookie for JSON requests (requires withCredentials to send cookies). */
    private function withCartToken(string $token): static
    {
        return $this->withCredentials()->withUnencryptedCookie('cart_token', $token);
    }

    public function test_guest_can_view_cart_page(): void
    {
        $this->get(route('storefront.cart.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Cart'));
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'is_active' => true]);

        $this->postJson(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('item_count', 2)
            ->assertJsonPath('items.0.product_id', $product->id);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1000,
        ]);
    }

    public function test_adding_same_item_increments_quantity(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'unit_price' => 500]);

        $this->withCartToken($token)->postJson(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 3,
        ])->assertJsonPath('item_count', 4);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    public function test_variant_price_is_used_when_adding_variant(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1500,
            'is_active' => true,
        ]);

        $this->postJson(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'unit_price' => 1500,
        ]);
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->postJson(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
        ])->assertStatus(422);
    }

    public function test_guest_can_update_cart_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $item = $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'unit_price' => 500]);

        $this->withCartToken($token)->patchJson(route('storefront.cart.items.update', $item->id), [
            'quantity' => 5,
        ])->assertOk()
            ->assertJsonPath('item_count', 5);
    }

    public function test_updating_quantity_to_zero_removes_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $item = $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2, 'unit_price' => 500]);

        $this->withCartToken($token)->patchJson(route('storefront.cart.items.update', $item->id), [
            'quantity' => 0,
        ])->assertOk()
            ->assertJsonPath('item_count', 0);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_guest_can_remove_cart_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $item = $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'unit_price' => 500]);

        $this->withCartToken($token)->deleteJson(route('storefront.cart.items.destroy', $item->id))
            ->assertOk()
            ->assertJsonPath('item_count', 0);
    }

    public function test_guest_can_clear_cart(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();
        $cart = Cart::factory()->create(['session_id' => $token]);
        $cart->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 3, 'unit_price' => 500]);

        $this->withCartToken($token)->deleteJson(route('storefront.cart.clear'))
            ->assertOk()
            ->assertJsonPath('item_count', 0);
    }

    public function test_guest_cannot_modify_another_sessions_cart_item(): void
    {
        $product = Product::factory()->create(['price' => 500, 'is_active' => true]);

        $otherCart = Cart::factory()->create(['session_id' => 'other-cart-token']);
        $otherItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $this->withCartToken('my-different-cart-token')
            ->patchJson(route('storefront.cart.items.update', $otherItem->id), [
                'quantity' => 99,
            ])->assertForbidden();
    }

    public function test_authenticated_user_cart_is_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 1000, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_sync_upserts_items_from_client(): void
    {
        $product1 = Product::factory()->create(['price' => 1000, 'is_active' => true]);
        $product2 = Product::factory()->create(['price' => 2000, 'is_active' => true]);
        $token = 'test-cart-token-'.uniqid();

        $this->withCartToken($token)->postJson(route('storefront.cart.sync'), [
            'items' => [
                ['product_id' => $product1->id, 'variant_id' => null, 'quantity' => 2],
                ['product_id' => $product2->id, 'variant_id' => null, 'quantity' => 1],
            ],
        ])->assertOk()
            ->assertJsonPath('item_count', 3);

        $this->assertDatabaseHas('cart_items', ['product_id' => $product1->id, 'quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product2->id, 'quantity' => 1]);
    }

    public function test_sync_skips_inactive_products(): void
    {
        $active = Product::factory()->create(['price' => 500, 'is_active' => true]);
        $inactive = Product::factory()->create(['price' => 500, 'is_active' => false]);

        $this->postJson(route('storefront.cart.sync'), [
            'items' => [
                ['product_id' => $active->id, 'variant_id' => null, 'quantity' => 1],
                ['product_id' => $inactive->id, 'variant_id' => null, 'quantity' => 1],
            ],
        ])->assertOk()
            ->assertJsonPath('item_count', 1);
    }
}
