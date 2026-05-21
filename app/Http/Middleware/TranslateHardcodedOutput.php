<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslateHardcodedOutput
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $locale = $this->currentLocale($request);

        if ($locale !== 'en') {
            return $response;
        }

        $dictionary = trans('admin_hardcoded', [], $locale);

        if (!is_array($dictionary) || $dictionary === []) {
            return $response;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $dictionary = $this->prepareDictionary($dictionary);

        if ($response instanceof JsonResponse) {
            $data = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $response;
            }

            $response->setData($this->translateValue($data, $dictionary));

            return $response;
        }

        if (!$response instanceof Response && !$response instanceof IlluminateResponse) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        if ($contentType !== '' && !str_contains($contentType, 'text/html') && !str_contains($contentType, 'application/xhtml+xml')) {
            return $response;
        }

        $content = $response->getContent();

        if (!is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent($this->translateString($content, $dictionary));

        return $response;
    }

    private function currentLocale(Request $request): string
    {
        $segmentLocale = strtolower((string) $request->segment(1));

        if (in_array($segmentLocale, ['ar', 'en'], true)) {
            return $segmentLocale;
        }

        $appLocale = strtolower((string) app()->getLocale());

        return in_array($appLocale, ['ar', 'en'], true) ? $appLocale : 'ar';
    }

    private function prepareDictionary(array $dictionary): array
    {
        $dictionary = array_filter(
            $dictionary,
            static fn ($target, $source) => is_string($source) && $source !== '' && is_string($target) && $target !== '',
            ARRAY_FILTER_USE_BOTH
        );

        uksort($dictionary, static fn ($first, $second) => mb_strlen($second) <=> mb_strlen($first));

        return $dictionary;
    }

    private function translateValue(mixed $value, array $dictionary): mixed
    {
        if (is_string($value)) {
            return $this->translateString($value, $dictionary);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->translateValue($item, $dictionary);
        }

        return $value;
    }

    private function translateString(string $value, array $dictionary): string
    {
        return str_replace(array_keys($dictionary), array_values($dictionary), $value);
    }
}
