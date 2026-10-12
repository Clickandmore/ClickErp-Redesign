<?php

namespace App\Services\Firebase;

use App\Models\ingaz\UserFirebaseToken;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * إرسال إشعارات Firebase عبر FCM HTTP v1.
 *
 * ملاحظة معمارية: واجهة v1 ترسل رسالة واحدة لتوكن واحد في كل طلب — لا يوجد
 * مقابل لـ registration_ids التي كانت في الـ Legacy API. لذلك نرسل الطلبات
 * على دفعات متوازية عبر Http::pool بدل حلقة تسلسلية.
 */
class FirebasePushService
{
    /** أكواد تعني أن التوكن نفسه لم يعد صالحاً ويجب حذفه من قاعدة البيانات. */
    private const DEAD_TOKEN_CODES = ['UNREGISTERED', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'];

    private ?FirebaseCredentials $credentials = null;
    private ?FirebaseAccessToken $accessToken = null;

    /**
     * إرسال إشعار إلى كل أجهزة مستخدم واحد.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): FirebasePushResult
    {
        return $this->sendToUsers([$userId], $title, $body, $data);
    }

    /**
     * إرسال إشعار إلى كل أجهزة مجموعة مستخدمين.
     *
     * @param array<int, int> $userIds
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): FirebasePushResult
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));

        if ($userIds === []) {
            return FirebasePushResult::skipped('لم يتم تمرير أي مستخدم.');
        }

        $tokenUsers = UserFirebaseToken::query()
            ->whereIn('user_id', $userIds)
            ->pluck('user_id', 'phone_token')
            ->all();

        return $this->sendToTokens(array_keys($tokenUsers), $title, $body, $data, ['token_users' => $tokenUsers, 'user_ids' => $userIds]);
    }

    /**
     * إرسال إشعار إلى قائمة توكنات مباشرة.
     *
     * @param array<int, string> $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], array $auditContext = []): FirebasePushResult
    {
        $logs = [];
        foreach (array_unique(array_filter($tokens)) as $token) {
            $userId = $auditContext['token_users'][$token] ?? null;
            $recipient = $userId ? 'client:' . $userId : (($data['guard'] ?? 'device') . ':' . ($data['staff_id'] ?? substr(hash('sha256', $token), 0, 16)));
            $logs[$token] = \App\Support\OutboundMessageRecorder::begin('push', $recipient, $title . "\n" . $body, 'firebase', $data + ['user_id' => $userId]);
        }
        if (!$logs) {
            foreach (($auditContext['user_ids'] ?? ['unknown']) as $userId) {
                $logs['no-token:' . $userId] = \App\Support\OutboundMessageRecorder::begin('push', 'client:' . $userId, $title . "\n" . $body, 'firebase', $data);
            }
        }
        try {
            $result = $this->sendToTokensRaw($tokens, $title, $body, $data);
            $failures = collect($result->failures())->pluck('reason', 'token')->all();
            foreach ($logs as $token => $log) {
                $error = $failures[$token] ?? ($result->wasSkipped() ? ($failures[''] ?? 'لم يُرسل الإشعار.') : null);
                \App\Support\OutboundMessageRecorder::finish($log, $result->wasSkipped() ? 'skipped' : ($error ? 'failed' : 'accepted'), ['error_message' => $error]);
            }
            return $result;
        } catch (\Throwable $e) {
            foreach ($logs as $log) \App\Support\OutboundMessageRecorder::finish($log, 'failed', ['error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function sendToTokensRaw(array $tokens, string $title, string $body, array $data = []): FirebasePushResult
    {
        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens), fn ($t) => trim($t) !== '')));

        if (!config('firebase.enabled', true)) {
            return FirebasePushResult::skipped('إشعارات Firebase معطّلة عبر الإعدادات.');
        }

        if ($tokens === []) {
            return FirebasePushResult::skipped('لا توجد توكنات مسجّلة للمستلمين.');
        }

        try {
            $accessToken = $this->accessToken()->get();
        } catch (Throwable $e) {
            Log::error('Firebase access token failed', ['message' => $e->getMessage()]);

            return FirebasePushResult::skipped('تعذر المصادقة مع Firebase: ' . $e->getMessage());
        }

        $result = new FirebasePushResult();
        $payload = $this->buildMessage($title, $body, $data);
        $url = $this->endpoint();

        $retryTokens = $this->dispatch($tokens, $url, $accessToken, $payload, $result);

        // رد 401 يعني أن التوكن المخزّن في الكاش انتهى أو أُبطل — نجدّده ونعيد
        // المحاولة مرة واحدة فقط للتوكنات المتأثرة.
        if ($retryTokens !== []) {
            $this->accessToken()->forget();

            try {
                $freshToken = $this->accessToken()->get();
                $this->dispatch($retryTokens, $url, $freshToken, $payload, $result);
            } catch (Throwable $e) {
                foreach ($retryTokens as $token) {
                    $result->addFailure($token, 'فشل تجديد المصادقة: ' . $e->getMessage());
                }
            }
        }

        $this->pruneInvalidTokens($result);

        return $result;
    }

    /**
     * يرسل دفعة توكنات ويُرجع التوكنات التي تستحق إعادة محاولة بعد تجديد المصادقة.
     *
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function dispatch(array $tokens, string $url, string $accessToken, array $payload, FirebasePushResult $result): array
    {
        $concurrency = max(1, (int) config('firebase.concurrency', 20));
        $timeout = (int) config('firebase.timeout', 15);
        $retryTokens = [];

        foreach (array_chunk($tokens, $concurrency) as $chunk) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunk, $url, $accessToken, $payload, $timeout) {
                    return array_map(
                        fn (string $token) => $pool->withToken($accessToken)
                            ->timeout($timeout)
                            ->post($url, ['message' => ['token' => $token] + $payload]),
                        $chunk
                    );
                });
            } catch (Throwable $e) {
                // لا نُسقط بقية الدفعات بسبب فشل دفعة واحدة.
                Log::error('Firebase pool dispatch failed', ['message' => $e->getMessage()]);

                foreach ($chunk as $token) {
                    $result->addFailure($token, $e->getMessage());
                }

                continue;
            }

            foreach ($chunk as $index => $token) {
                $response = $responses[$index] ?? null;

                if ($response instanceof Response && $response->successful()) {
                    $result->addSuccess();
                    continue;
                }

                if ($response instanceof Response && $response->status() === 401) {
                    $retryTokens[] = $token;
                    continue;
                }

                if ($response instanceof Response) {
                    $errorCode = $this->extractErrorCode($response);
                    $result->addFailure(
                        $token,
                        $errorCode ?: ('HTTP ' . $response->status()),
                        in_array($errorCode, self::DEAD_TOKEN_CODES, true)
                    );
                    continue;
                }

                $reason = $response instanceof Throwable ? $response->getMessage() : 'رد غير معروف من FCM';
                $result->addFailure($token, $reason);
            }
        }

        return $retryTokens;
    }

    /**
     * FCM v1 يشترط أن تكون كل قيم data نصوصاً — الكود القديم كان يمرّر أرقاماً
     * (مثل order_id) وكان الـ Legacy API يتساهل معها. هنا نحوّلها صراحة، وإلا
     * يُرفض الطلب بـ INVALID_ARGUMENT.
     */
    private function buildMessage(string $title, string $body, array $data): array
    {
        $stringData = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $stringData[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $stringData,
            'android' => [
                'priority' => (string) config('firebase.android.priority', 'high'),
                'notification' => [
                    'channel_id' => (string) config('firebase.android.channel_id', 'default_channel'),
                    'sound' => (string) config('firebase.android.sound', 'default'),
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => (string) config('firebase.apns.sound', 'default'),
                        'content-available' => 1,
                    ],
                ],
            ],
        ];
    }

    private function extractErrorCode(Response $response): ?string
    {
        $details = $response->json('error.details') ?? [];

        foreach ((array) $details as $detail) {
            if (!empty($detail['errorCode'])) {
                return (string) $detail['errorCode'];
            }
        }

        $status = $response->json('error.status');

        return $status ? (string) $status : null;
    }

    private function pruneInvalidTokens(FirebasePushResult $result): void
    {
        $invalid = $result->invalidTokens();

        if ($invalid === [] || !config('firebase.prune_invalid_tokens', true)) {
            return;
        }

        $deleted = UserFirebaseToken::query()->whereIn('phone_token', $invalid)->delete();

        Log::info('Firebase pruned invalid device tokens', ['deleted' => $deleted]);
    }

    private function endpoint(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/' . $this->credentials()->projectId() . '/messages:send';
    }

    private function credentials(): FirebaseCredentials
    {
        return $this->credentials ??= FirebaseCredentials::fromConfig();
    }

    private function accessToken(): FirebaseAccessToken
    {
        return $this->accessToken ??= new FirebaseAccessToken($this->credentials());
    }
}
