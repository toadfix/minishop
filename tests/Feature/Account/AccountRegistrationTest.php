<?php

namespace Minishop\Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class AccountRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_storefront_register_page_can_be_rendered(): void
    {
        $this->get('/register/customer')->assertOk();
    }

    public function test_registration_assigns_customer_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_registration_creates_customer_profile(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertNotNull($user->customer);
        $this->assertTrue($user->customer->is_active);
    }

    public function test_customer_is_redirected_to_account_after_registration(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/account');
    }

    public function test_admin_login_redirects_to_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_customer_login_redirects_to_account(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/account');
    }
}
