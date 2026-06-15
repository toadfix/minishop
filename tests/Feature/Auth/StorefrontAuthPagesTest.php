<?php

namespace Minishop\Tests\Feature\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class StorefrontAuthPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in');
    }

    public function test_register_page_renders(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Forgot your password?');
    }

    public function test_reset_password_page_renders(): void
    {
        $this->get(route('password.reset', ['token' => 'a-token']))
            ->assertOk()
            ->assertSee('Reset your password');
    }

    public function test_password_reset_link_is_emailed(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => 'reset@example.com'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword!234', $user->fresh()->password));
    }
}
