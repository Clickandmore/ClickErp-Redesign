<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الغلاف الموحد لمسارات تطبيق الفريق الميداني: { code, status, message, data }.
 *
 * تطبيق السائق والعاملة يقرأ `data` ويعرض `message` كما هي، ويعتبر `status: false`
 * أو `code` خارج 2xx فشلًا حتى مع HTTP 200. المتحكمات تعيد شكلها الطبيعي
 * (`['data'=>…]` أو كائنًا مباشرًا) وهذا الوسيط يلفّه، فلا يتكرر الغلاف في كل دالة.
 */
class HourlyStaffApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$response instanceof JsonResponse) return $response;

        $body = $response->getData(true);
        $status = $response->getStatusCode();
        // مغلَّف مسبقًا (أو رد من معالج أخطاء يلتزم نفس الشكل): يُترك كما هو.
        if (is_array($body) && array_key_exists('code', $body) && array_key_exists('data', $body)) return $response;

        $success = $status < 400;
        $payload = [
            'code' => $status,
            'status' => $success,
            'message' => is_array($body) ? (string) ($body['message'] ?? '') : '',
            'data' => null,
        ];
        if ($success) {
            if (is_array($body) && array_key_exists('data', $body)) {
                // data صريحة: تُنقل كما هي. قد تحمل حقل message مشروعًا (نص بلاغ نداء النجدة مثلًا).
                $payload['data'] = $body['data'];
            } else {
                // لا data: الجسم كله هو البيانات، ورسالته تُرفع للغلاف فلا تتكرر داخلها.
                $payload['data'] = $body;
                if (is_array($payload['data'])) unset($payload['data']['message']);
            }
        } elseif (is_array($body)) {
            if (isset($body['errors'])) $payload['errors'] = $body['errors'];
            // رد الخطأ قد يحمل بيانات يحتاجها التطبيق (إعدادات التتبع مثلًا)، فتُحفظ في data.
            $rest = array_diff_key($body, array_flip(['message', 'errors', 'error', 'code', 'status', 'data']));
            if ($rest) $payload['data'] = $rest;
            elseif (array_key_exists('data', $body)) $payload['data'] = $body['data'];
        }

        return $response->setData($payload);
    }
}
