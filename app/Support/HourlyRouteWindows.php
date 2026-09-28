<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;

/**
 * نوافذ «إدارة المسار» المخصصة (اسم + من/إلى) على مستوى الحساب، بجانب قبل الظهر وبعد الظهر.
 * مفتاح مستقل لأن HourlyRentalSettings يطبّع مفاتيحه حصرًا.
 */
class HourlyRouteWindows
{
    public const SETTINGS_KEY = 'hourly_route_windows';
    public const MAX = 10;

    /** @return array<int, array{name:string,from:string,to:string}> */
    public static function all(): array
    {
        if (!Schema::hasTable('new_settings')) return [];
        $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
        $stored = is_string($raw) ? json_decode($raw, true) : null;

        return self::normalize(is_array($stored) ? $stored : []);
    }

    public static function persist(array $windows): array
    {
        $windows = self::normalize($windows);
        NewSettings::query()->updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => json_encode($windows, JSON_UNESCAPED_UNICODE)]);

        return $windows;
    }

    /** نوافذ صالحة فقط: اسم غير فارغ، ووقتان بصيغة ساعة:دقيقة والنهاية بعد البداية، بلا أسماء مكررة. */
    public static function normalize(array $windows): array
    {
        $clock = fn ($v) => is_string($v) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v);
        $result = [];
        foreach ($windows as $window) {
            $name = mb_substr(trim((string) ($window['name'] ?? '')), 0, 40);
            if ($name === '' || !$clock($window['from'] ?? null) || !$clock($window['to'] ?? null) || $window['to'] <= $window['from'] || isset($result[$name])) continue;
            $result[$name] = ['name' => $name, 'from' => $window['from'], 'to' => $window['to']];
        }

        return array_slice(array_values($result), 0, self::MAX);
    }
}
