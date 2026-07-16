<?php

namespace App\Services\Firebase;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * يولّد access token لواجهة FCM HTTP v1 عبر تدفّق OAuth2 JWT Bearer.
 *
 * الـ Legacy API كان يستخدم مفتاح سيرفر ثابت في الهيدر. أما HTTP v1 فيتطلب
 * توكن OAuth2 قصير العمر: نوقّع JWT بالمفتاح الخاص لحساب الخدمة، ثم نبادله
 * بـ access token صالح لساعة. نخزّنه في الكاش لتجنّب طلب توقيع مع كل إشعار.
 */
class FirebaseAccessToken
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    private FirebaseCredentials $credentials;

    public function __construct(FirebaseCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    public function get(): string
    {
        $ttl = max(60, (int) config('firebase.token_cache_ttl', 3300));
        $key = 'firebase:access_token:' . $this->credentials->fingerprint();

        return Cache::remember($key, $ttl, fn () => $this->request());
    }

    /**
     * يُبطل التوكن المخزّن — يُستدعى عند رد 401 حتى لا نظل نعيد استخدام توكن
     * مرفوض حتى انتهاء مدة الكاش.
     */
    public function forget(): void
    {
        Cache::forget('firebase:access_token:' . $this->credentials->fingerprint());
    }

    private function request(): string
    {
        $response = Http::asForm()
            ->timeout((int) config('firebase.timeout', 15))
            ->post(self::TOKEN_ENDPOINT, [
                'grant_type' => self::GRANT_TYPE,
                'assertion' => $this->buildAssertion(),
            ]);

        if (!$response->successful()) {
            throw new FirebaseException(
                'تعذر الحصول على access token من جوجل: ' . $response->status() . ' ' . $response->body()
            );
        }

        $token = (string) ($response->json('access_token') ?? '');

        if ($token === '') {
            throw new FirebaseException('رد جوجل لا يحتوي على access_token.');
        }

        return $token;
    }

    private function buildAssertion(): string
    {
        $issuedAt = time();

        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64Url(json_encode([
            'iss' => $this->credentials->clientEmail(),
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ]));

        $signingInput = $header . '.' . $claims;
        $privateKey = openssl_pkey_get_private($this->credentials->privateKey());

        if ($privateKey === false) {
            throw new FirebaseException('المفتاح الخاص في ملف حساب الخدمة غير صالح.');
        }

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new FirebaseException('فشل توقيع JWT الخاص بـ Firebase.');
        }

        return $signingInput . '.' . $this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
