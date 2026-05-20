<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->customer()->create();

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonStructure(['id', 'name', 'email', 'customer']);
    }

    public function test_profile_includes_customer_data_when_present(): void
    {
        $user = User::factory()->customer()->create();

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/user')->assertOk();

        $this->assertNotNull($response->json('customer'));
        $this->assertArrayHasKey('id', $response->json('customer'));
        $this->assertArrayHasKey('phone', $response->json('customer'));
    }

    public function test_profile_returns_null_customer_when_not_set(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('customer', null);
    }
}
