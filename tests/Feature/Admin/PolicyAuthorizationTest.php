<?php

namespace Minishop\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Coupon;
use Minishop\Models\Order;
use Minishop\Models\OrderReturn;
use Minishop\Models\Product;
use Minishop\Models\ShippingMethod;
use Minishop\Models\TaxZone;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

/**
 * Authorization coverage for the admin policies. Exercises the policy → Spatie
 * permission delegation and the super-admin Gate::before bypass across the
 * seeded role matrix, without rendering any Filament component.
 */
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_bypasses_every_policy_check(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('viewAny', Product::class));
        $this->assertTrue($user->can('delete', Product::factory()->create()));
        $this->assertTrue($user->can('delete', User::factory()->create()));
        $this->assertTrue($user->can('refund', OrderReturn::factory()->create()));
        $this->assertTrue($user->can('delete', Coupon::factory()->create()));
    }

    public function test_admin_has_full_product_and_user_management(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->can('viewAny', Product::class));
        $this->assertTrue($user->can('create', Product::class));
        $this->assertTrue($user->can('update', Product::factory()->create()));
        $this->assertTrue($user->can('delete', Product::factory()->create()));

        $this->assertTrue($user->can('create', User::class));
        $this->assertTrue($user->can('delete', User::factory()->create()));
        $this->assertTrue($user->can('delete', Coupon::factory()->create()));
        $this->assertTrue($user->can('refund', OrderReturn::factory()->create()));
    }

    public function test_manager_can_edit_but_not_delete_products(): void
    {
        $user = User::factory()->manager()->create();

        $this->assertTrue($user->can('viewAny', Product::class));
        $this->assertTrue($user->can('create', Product::class));
        $this->assertTrue($user->can('update', Product::factory()->create()));

        // Manager is intentionally not granted destructive or elevated permissions.
        $this->assertFalse($user->can('delete', Product::factory()->create()));
        $this->assertFalse($user->can('viewAny', Coupon::class));
        $this->assertFalse($user->can('viewAny', User::class));
        $this->assertFalse($user->can('delete', ShippingMethod::factory()->create()));
        $this->assertFalse($user->can('refund', OrderReturn::factory()->create()));
    }

    public function test_customer_is_denied_all_admin_abilities(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertFalse($user->can('viewAny', Product::class));
        $this->assertFalse($user->can('create', Product::class));
        $this->assertFalse($user->can('update', Product::factory()->create()));
        $this->assertFalse($user->can('viewAny', Order::class));
        $this->assertFalse($user->can('viewAny', TaxZone::class));
        $this->assertFalse($user->can('viewAny', User::class));
    }
}
