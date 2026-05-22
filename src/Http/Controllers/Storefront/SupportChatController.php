<?php

namespace Minishop\Http\Controllers\Storefront;

use Minishop\Ai\Agents\SupportAgent;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Requests\Storefront\StoreChatMessageRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportChatController extends Controller
{
    public function store(StoreChatMessageRequest $request): StreamedResponse
    {
        $agent = new SupportAgent;
        $user = $request->user();

        if ($user) {
            $conversationId = $request->string('conversation_id')->value();

            if ($conversationId) {
                $agent->continue($conversationId, as: $user);
            } else {
                $agent->forUser($user);
            }
        }

        $stream = $agent->stream($request->string('message'));

        return response()->stream(function () use ($stream, $user): void {
            foreach ($stream as $event) {
                echo "data: {$event}\n\n";
                ob_flush();
                flush();
            }

            if ($user && $stream->conversationId) {
                $payload = json_encode(['type' => 'conversation_id', 'id' => $stream->conversationId]);
                echo "data: {$payload}\n\n";
                ob_flush();
                flush();
            }

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
