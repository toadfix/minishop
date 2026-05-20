<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Order;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_orders(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_their_orders(): void
    {
        $user = User::factory()->customer()->create();
        Order::factory(3)->create(['customer_id' => $user->customer->id]);

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'order_number', 'status', 'total_amount']]]);
    }

    public function test_user_only_sees_their_own_orders(): void
    {
        $userA = User::factory()->customer()->create();
        $userB = User::factory()->customer()->create();

        Order::factory(2)->create(['customer_id' => $userA->customer->id]);
        Order::factory(3)->create(['customer_id' => $userB->customer->id]);

        $token = $userA->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_without_customer_profile_gets_empty_list(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_authenticated_user_can_view_their_order(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $user->customer->id]);

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/orders/{$order->order_number}")
            ->assertOk()
            ->assertJsonPath('order_number', $order->order_number);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $userA = User::factory()->customer()->create();
        $userB = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $userB->customer->id]);

        $token = $userA->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/orders/{$order->order_number}")
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_order(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->order_number}")->assertUnauthorized();
    }
}
