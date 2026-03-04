<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, MetaWhatsAppService $whatsAppService)
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $verifyToken = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        if ($mode === 'subscribe' && hash_equals($whatsAppService->webhookVerifyToken(), $verifyToken)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta WhatsApp webhook verification failed', [
            'mode' => $mode,
            'provided_token' => $verifyToken,
            'ip' => $request->ip(),
        ]);

        return response('Forbidden', 403);
    }

    public function receive(Request $request): JsonResponse
    {
        $payload = $request->all();

        $entries = (array) data_get($payload, 'entry', []);
        $messagesCount = 0;
        $statusesCount = 0;

        foreach ($entries as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $messagesCount += count((array) data_get($change, 'value.messages', []));
                $statusesCount += count((array) data_get($change, 'value.statuses', []));
            }
        }

        Log::info('Meta WhatsApp webhook received', [
            'entries_count' => count($entries),
            'messages_count' => $messagesCount,
            'statuses_count' => $statusesCount,
        ]);

        return response()->json(['status' => 'received']);
    }
}
