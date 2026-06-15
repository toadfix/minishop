<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Enums\ReviewStatus;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\Product;
use Minishop\Models\Review;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function buyerOf(Product $product): User
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $user->customer->id, 'payment_status' => 'paid']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        return $user;
    }

    public function test_verified_buyer_can_submit_a_pending_review(): void
    {
        $product = Product::factory()->create();
        $buyer = $this->buyerOf($product);

        $this->actingAs($buyer)
            ->post(route('storefront.products.reviews.store', $product->slug), [
                'rating' => 5,
                'title' => 'Great',
                'body' => 'Loved it.',
            ])
            ->assertRedirect(route('storefront.products.show', $product->slug));

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'rating' => 5,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_non_purchaser_cannot_submit_a_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->post(route('storefront.products.reviews.store', $product->slug), [
                'rating' => 4,
                'body' => 'Nice',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_review_the_same_product_twice(): void
    {
        $product = Product::factory()->create();
        $buyer = $this->buyerOf($product);
        Review::factory()->approved()->create(['product_id' => $product->id, 'user_id' => $buyer->id]);

        $this->assertFalse(Review::userMayReview($buyer->fresh(), $product));
    }

    public function test_only_approved_reviews_are_shown_on_the_product_page(): void
    {
        $product = Product::factory()->create();
        Review::factory()->approved()->create(['product_id' => $product->id, 'body' => 'APPROVED-REVIEW-BODY']);
        Review::factory()->create(['product_id' => $product->id, 'body' => 'PENDING-REVIEW-BODY']);

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee('APPROVED-REVIEW-BODY')
            ->assertDontSee('PENDING-REVIEW-BODY');
    }

    public function test_guest_is_redirected_to_login_when_submitting(): void
    {
        $product = Product::factory()->create();

        $this->post(route('storefront.products.reviews.store', $product->slug), [
            'rating' => 5,
            'body' => 'x',
        ])->assertRedirect(route('login'));
    }
}
