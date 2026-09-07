<?php

namespace App\Support\Audit;

class AuditProperties
{
    public static function sensitive(string $key): bool
    {
        return (bool) preg_match('/password|passwd|token|secret|credential|private[_-]?key|api[_-]?key|access[_-]?key|authorization|cookie|verification[_-]?code|session[_-]?id|session[_-]?fingerprint/i', $key);
    }

    public static function sanitize(array $properties): array
    {
        // Settings may store a whole credential document in a generic value column.
        $settingKey = $properties['key'] ?? null;
        foreach ($properties as $key => $value) {
            if (self::sensitive((string) $key) || ($key === 'value' && is_string($settingKey) && self::sensitive($settingKey))) {
                $properties[$key] = '[محجوب]';
            } elseif (is_array($value)) {
                $properties[$key] = self::sanitize($value);
            } elseif (is_string($value) && in_array(substr(ltrim($value), 0, 1), ['{', '['], true)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $properties[$key] = json_encode(self::sanitize($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        return $properties;
    }
}
