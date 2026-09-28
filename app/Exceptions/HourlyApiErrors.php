<?php

namespace App\Exceptions;

use App\Http\Middleware\HourlyApiLang;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * أخطاء واجهات تطبيق الساعات (api/app/hourly/*) بشكل ثابت يفهمه المطور والعميل:
 * {message, error, errors?} مع رسالة مترجمة حسب لغة الطلب، وبدون تتبع الأخطاء (trace) حتى مع APP_DEBUG.
 * أخطاء التحقق تحافظ على شكل Laravel {message, errors} الذي يعتمد عليه التطبيق.
 */
class HourlyApiErrors
{
    private const CODES = [400 => 'bad_request', 401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed',
        409 => 'conflict', 422 => 'validation_error', 429 => 'too_many_requests', 503 => 'service_unavailable'];
    private const FRAMEWORK_MESSAGES = ['Too Many Attempts.', 'Not found.', 'Bad hostname provided.', 'This action is unauthorized.',
        'Unauthenticated.', 'Unauthorized.', 'Forbidden.', 'CSRF token mismatch.', 'Service Unavailable', 'Server Error'];

    public static function matches(Request $request): bool
    {
        // مسارات تطبيق العميل والفريق الميداني، بالبادئة الأصلية وبالبادئة التي يستدعيها التطبيق.
        return $request->is('api/app/hourly', 'api/app/hourly/*', 'api/driver/*', 'api/worker/*');
    }

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if ($e instanceof HttpResponseException) return null;
        // الخطأ قد يقع قبل وسيط اللغة (مثل ربط {package} بالنموذج)، فنحدد اللغة هنا أيضاً.
        app()->setLocale(HourlyApiLang::localeFor($request));

        if ($e instanceof ValidationException) {
            $errors = $e->validator->errors();
            return $this->json($e->status, $errors->first() ?: __('hourly_api.validation'), ['errors' => $errors->messages()]);
        }
        if ($e instanceof AuthenticationException) return $this->json(401, __('hourly_api.unauthenticated'));
        if (!$e instanceof HttpExceptionInterface) return $this->json(500, __('hourly_api.server_error'), $this->debug($e), 'server_error');

        $status = $e->getStatusCode();
        $headers = $e->getHeaders();
        return $this->json($status, $this->message($e, $request, $status, $headers), [], null, $headers);
    }

    private function message(HttpExceptionInterface $e, Request $request, int $status, array $headers): string
    {
        $previous = $e->getPrevious();
        if ($previous instanceof ModelNotFoundException) {
            $key = 'hourly_api.models.'.class_basename($previous->getModel());
            return __($key) !== $key ? __($key) : __('hourly_api.not_found');
        }
        if ($e instanceof MethodNotAllowedHttpException) {
            return __('hourly_api.method_not_allowed', ['method' => $request->method(), 'allowed' => $headers['Allow'] ?? '']);
        }
        if ($status === 404 && !$request->route()) return __('hourly_api.route_not_found');
        // رسائل abort() في كودنا مقصودة للعميل؛ رسائل الإطار الإنجليزية الافتراضية تُستبدل بالمترجمة.
        $message = (string) $e->getMessage();
        if ($message !== '' && !in_array($message, self::FRAMEWORK_MESSAGES, true)) return $message;

        return match ($status) {
            429 => __('hourly_api.too_many_requests', ['seconds' => $headers['Retry-After'] ?? 60]),
            default => __('hourly_api.'.(self::CODES[$status] ?? ($status >= 500 ? 'server_error' : 'bad_request'))),
        };
    }

    private function debug(Throwable $e): array
    {
        return config('app.debug') ? ['debug' => ['exception' => get_class($e), 'message' => $e->getMessage(), 'at' => basename($e->getFile()).':'.$e->getLine()]] : [];
    }

    private function json(int $status, string $message, array $extra = [], ?string $code = null, array $headers = []): JsonResponse
    {
        $code ??= self::CODES[$status] ?? ($status >= 500 ? 'server_error' : 'bad_request');
        return response()->json(['message' => $message, 'error' => $code] + $extra, $status, $headers);
    }
}
