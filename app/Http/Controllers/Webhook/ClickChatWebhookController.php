<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\admin\Controller;
use App\Jobs\ProcessClickChatWebhook;
use App\Services\WhatsApp\ClickChatWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives message.received / message.sent / message.status from Click Live Chat.
 * The signature is checked on the raw body before anything is read or saved.
 */
class ClickChatWebhookController extends Controller
{
    public function receive(Request $request, ClickChatWhatsAppService $clickChat): JsonResponse
    {
        $raw = (string) $request->getContent();

        if (!$clickChat->verifySignature($raw, $request->header('X-Webhook-Signature'))) {
            Log::warning('Rejected Click Live Chat webhook with invalid or expired signature', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON'], 400);
        }

        $event = (string) ($payload['event'] ?? $request->header('X-Webhook-Event', ''));
        if (in_array($event, ['message.received', 'message.sent', 'message.status'], true)) {
            $payload['event'] = $event;
            ProcessClickChatWebhook::dispatch($payload);
        }

        // test.ping and events we do not use are acknowledged so the platform stops retrying.
        return response()->json(['status' => 'received']);
    }
}
