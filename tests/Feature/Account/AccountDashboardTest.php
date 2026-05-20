<?php

namespace Minishop\Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class AccountDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
    }

    public function test_customers_can_access_account_dashboard(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Account/Dashboard')
                ->has('recentOrders')
                ->has('totalOrders')
            );
    }

    public function test_admin_cannot_access_customer_account_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/account')
            ->assertForbidden();
    }

    public function test_manager_cannot_access_customer_account_area(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/account')
            ->assertForbidden();
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertForbidden();
    }
}
