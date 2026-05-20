<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Ai\Agents\SupportAgent;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_send_message_and_receives_event_stream(): void
    {
        SupportAgent::fake(['Hello! How can I help?']);

        $response = $this->postJson(route('storefront.chat.store'), [
            'message' => 'Hi there',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    }

    public function test_authenticated_user_can_send_message(): void
    {
        SupportAgent::fake(['Welcome back!']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('storefront.chat.store'), [
            'message' => 'What products do you have?',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    }

    public function test_empty_message_is_rejected(): void
    {
        $response = $this->postJson(route('storefront.chat.store'), [
            'message' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_message_exceeding_max_length_is_rejected(): void
    {
        $response = $this->postJson(route('storefront.chat.store'), [
            'message' => str_repeat('a', 2001),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_invalid_conversation_id_format_is_rejected(): void
    {
        $response = $this->postJson(route('storefront.chat.store'), [
            'message' => 'Hello',
            'conversation_id' => 'not-a-valid-uuid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['conversation_id']);
    }
}
