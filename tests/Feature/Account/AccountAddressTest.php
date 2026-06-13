<?php

namespace Minishop\Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class AccountAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_address_page_requires_authentication(): void
    {
        $this->get('/account/address')->assertRedirect(route('login'));
    }

    public function test_customer_can_view_address_page(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/account/address')
            ->assertOk()
            ->assertViewIs('minishop::storefront.account.address.edit');
    }

    public function test_customer_can_save_a_billing_address(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->put('/account/address', [
                'name' => 'Jane Doe',
                'line1' => '123 Sample St',
                'line2' => null,
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'country' => 'CA',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $user->customer->id,
            'type' => 'billing',
            'is_default' => true,
            'name' => 'Jane Doe',
            'city' => 'Makati',
        ]);
    }

    public function test_saving_address_again_updates_existing_record(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->put('/account/address', [
            'name' => 'Jane Doe',
            'line1' => '123 Sample St',
            'city' => 'Makati',
            'postal_code' => '1200',
            'country' => 'CA',
        ]);

        $this->actingAs($user)->put('/account/address', [
            'name' => 'Jane Updated',
            'line1' => '456 New Ave',
            'city' => 'Taguig',
            'postal_code' => '1634',
            'country' => 'CA',
        ]);

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseHas('addresses', ['name' => 'Jane Updated', 'city' => 'Taguig']);
    }

    public function test_address_validation_requires_required_fields(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->put('/account/address', [])
            ->assertSessionHasErrors(['name', 'line1', 'city', 'postal_code', 'country']);
    }
}
