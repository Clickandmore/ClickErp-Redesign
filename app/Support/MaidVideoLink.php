<?php

namespace App\Support;

final class MaidVideoLink
{
    public static function normalize($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*(["\']?)([^"\'\s>]+)\1/iu', $value, $matches)) {
            $value = $matches[2];
        }

        $value = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');

        if (preg_match('~^https?://(?:drive|docs)\.google\.com/file/d/([^/?#]+)~i', $value, $matches)) {
            return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
        }

        if (preg_match('~^https?://drive\.google\.com/(?:open|uc)~i', $value)) {
            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);
            if (filled($query['id'] ?? null)) {
                return 'https://drive.google.com/file/d/' . $query['id'] . '/preview';
            }
        }

        return $value ?: null;
    }
}
