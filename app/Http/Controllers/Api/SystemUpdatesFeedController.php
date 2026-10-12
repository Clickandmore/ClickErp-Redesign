<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemUpdateClient;
use App\Services\SystemUpdates\SystemUpdateFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * GET /api/system-updates/feed — تسحب منه نسخ العملاء تحديثات هذه النسخة.
 * المفتاح يُنشأ لكل عميل من السوبر أدمن ويُرسل في X-System-Updates-Key.
 */
class SystemUpdatesFeedController extends Controller
{
    public function __invoke(Request $request, SystemUpdateFeed $feed): JsonResponse
    {
        $token = $request->header('X-System-Updates-Key') ?: $request->bearerToken();
        $client = SystemUpdateClient::findActiveByToken($token);

        if (! $client) {
            return response()->json(['message' => 'مفتاح الربط غير صحيح أو موقوف.'], 401);
        }

        $since = null;
        if (filled($request->query('since'))) {
            try {
                $since = Carbon::parse((string) $request->query('since'))->setTimezone(config('app.timezone'));
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'قيمة since غير صالحة.'], 422);
            }
        }

        $client->forceFill([
            'last_synced_at' => now(),
            'last_ip' => $request->ip(),
        ])->save();

        return response()->json($feed->payload($since))
            ->header('Cache-Control', 'no-store');
    }
}
