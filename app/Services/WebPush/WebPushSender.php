<?php

namespace App\Services\WebPush;

use App\Models\ingaz\Notification;
use App\Models\ingaz\UserWebPushToken;
use App\Services\Firebase\FirebaseAccessToken;
use App\Services\Firebase\FirebaseCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sends each new customer notification to the customer's registered browsers.
 *
 * Deliberately separate from FirebasePushService (the mobile app path): it reads only
 * user_web_push_tokens, writes no message-log rows and shares nothing but the service-account
 * credentials. It runs after the response, and any failure is logged and swallowed, so it can
 * neither delay nor break saving the notification or the mobile push.
 */
class WebPushSender
{
    /** Codes meaning the browser registration is gone for good. */
    private const DEAD_TOKEN_CODES = ['UNREGISTERED', 'SENDER_ID_MISMATCH'];

    /**
     * Customers with at least one browser, so a broadcast to thousands does not query per notification.
     * Reloaded every minute because queue workers live long.
     *
     * @var array<int, true>|null
     */
    private static ?array $usersWithBrowsers = null;
    private static int $loadedAt = 0;

    public static function enabled(): bool
    {
        return (bool) config('webpush.enabled') && Schema::hasTable('user_web_push_tokens');
    }

    public static function afterCreated(Notification $notification): void
    {
        try {
            if (!$notification->user_id || !static::enabled()) return;
            if (static::$usersWithBrowsers === null || time() - static::$loadedAt > 60) {
                static::$usersWithBrowsers = array_fill_keys(UserWebPushToken::distinct()->pluck('user_id')->map(fn ($id) => (int) $id)->all(), true);
                static::$loadedAt = time();
            }
            if (!isset(static::$usersWithBrowsers[(int) $notification->user_id])) return;

            $message = [
                'user_id' => (int) $notification->user_id,
                'title' => (string) $notification->title,
                'body' => (string) $notification->description,
                'notification_id' => (string) $notification->id,
                'type' => (string) $notification->type,
            ];
            // يفتح الضغط على الإشعار طلب الساعات نفسه بدل قائمة الإشعارات.
            if (!empty($notification->hourly_order_id)) $message['hourly_order_id'] = (string) $notification->hourly_order_id;
            dispatch(fn () => app(WebPushSender::class)->send($message))->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('Web push not scheduled', ['notification_id' => $notification->id, 'error' => $e->getMessage()]);
        }
    }

    /** @param array{user_id:int,title:string,body:string,notification_id:string,type:string} $message */
    public function send(array $message): void
    {
        try {
            $tokens = UserWebPushToken::where('user_id', $message['user_id'])->pluck('token')->all();
            if ($tokens === []) return;

            $credentials = FirebaseCredentials::fromConfig();
            $accessToken = (new FirebaseAccessToken($credentials))->get();
            $url = 'https://fcm.googleapis.com/v1/projects/'.$credentials->projectId().'/messages:send';
            // Data only: the site's service worker shows the notification itself and refreshes open pages.
            $payload = [
                'data' => array_diff_key($message, ['user_id' => true]),
                'webpush' => ['headers' => ['Urgency' => 'high', 'TTL' => '86400']],
            ];

            $dead = [];
            foreach ($tokens as $token) {
                $response = Http::withToken($accessToken)->timeout((int) config('firebase.timeout', 15))
                    ->post($url, ['message' => ['token' => $token] + $payload]);
                if ($response->successful()) continue;
                $code = $this->errorCode($response->json());
                if (in_array($code, self::DEAD_TOKEN_CODES, true)) $dead[] = $token;
                else Log::warning('Web push failed', ['notification_id' => $message['notification_id'], 'status' => $response->status(), 'code' => $code]);
            }
            if ($dead) UserWebPushToken::whereIn('token', $dead)->delete();
        } catch (\Throwable $e) {
            Log::warning('Web push skipped', ['notification_id' => $message['notification_id'] ?? null, 'error' => $e->getMessage()]);
        }
    }

    private function errorCode(?array $body): ?string
    {
        foreach ((array) data_get($body, 'error.details', []) as $detail) {
            if (!empty($detail['errorCode'])) return (string) $detail['errorCode'];
        }
        return data_get($body, 'error.status');
    }
}
