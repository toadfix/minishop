<?php

namespace Minishop\Tests\Feature\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unverified_customer_is_redirected_from_the_account_area(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_customer_can_reach_the_account_area(): void
    {
        $user = User::factory()->customer()->create(); // factory verifies by default

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk();
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Jordan Lee',
            'email' => 'jordan@example.com',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
        ]);

        $user = User::query()->where('email', 'jordan@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_marks_the_email_verified(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
