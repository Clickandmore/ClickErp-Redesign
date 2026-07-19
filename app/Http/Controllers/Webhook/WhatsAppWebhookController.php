<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\admin\Controller;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppWebhookService;
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

        $expectedToken = $whatsAppService->webhookVerifyToken();

        if ($mode === 'subscribe'
            && $expectedToken !== ''
            && $verifyToken !== ''
            && hash_equals($expectedToken, $verifyToken)
        ) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta WhatsApp webhook verification failed', [
            'mode' => $mode,
            'provided_token' => $verifyToken,
            'ip' => $request->ip(),
        ]);

        return response('Forbidden', 403);
    }

    public function receive(
        Request $request,
        MetaWhatsAppService $whatsAppService,
        WhatsAppWebhookService $webhookService
    ): JsonResponse
    {
        if (!$whatsAppService->verifyWebhookSignature(
            (string) $request->getContent(),
            $request->header('X-Hub-Signature-256')
        )) {
            Log::warning('Rejected WhatsApp webhook with invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        $result = $webhookService->handle($payload);

        Log::info('Meta WhatsApp webhook received', [
            'entries_count' => count((array) data_get($payload, 'entry', [])),
            'messages_count' => $result['messages'],
            'statuses_count' => $result['statuses'],
        ]);

        return response()->json(['status' => 'received']);
    }
}
