<?php

namespace Minishop\Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Order;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class AccountOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_orders_page_requires_authentication(): void
    {
        $this->get('/account/orders')->assertRedirect(route('login'));
    }

    public function test_customer_can_view_their_orders(): void
    {
        $user = User::factory()->customer()->create();
        Order::factory(3)->for($user->customer)->create();

        $this->actingAs($user)
            ->get('/account/orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Account/Orders/Index')
                ->has('orders.data', 3)
            );
    }

    public function test_customer_only_sees_their_own_orders(): void
    {
        $userA = User::factory()->customer()->create();
        $userB = User::factory()->customer()->create();

        Order::factory(2)->for($userA->customer)->create();
        Order::factory(5)->for($userB->customer)->create();

        $this->actingAs($userA)
            ->get('/account/orders')
            ->assertInertia(fn ($page) => $page->has('orders.data', 2));
    }

    public function test_customer_can_view_their_own_order(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->for($user->customer)->create();

        $this->actingAs($user)
            ->get("/account/orders/{$order->order_number}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Account/Orders/Show'));
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $userA = User::factory()->customer()->create();
        $userB = User::factory()->customer()->create();
        $order = Order::factory()->for($userB->customer)->create();

        $this->actingAs($userA)
            ->get("/account/orders/{$order->order_number}")
            ->assertForbidden();
    }
}
