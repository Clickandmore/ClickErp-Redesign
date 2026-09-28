<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات «جدول الموظفين» على مستوى الحساب: نطاق الساعات الظاهر، ارتفاع الصف،
 * وحقول «نسخ التفاصيل» بترتيبها. مفتاح مستقل عن HourlyRentalSettings لأن الأخير يطبّع مفاتيحه حصرًا.
 */
class HourlyScheduleSettings
{
    public const SETTINGS_KEY = 'hourly_staff_schedule';

    /** حقول نسخ تفاصيل الزيارة بالترتيب الافتراضي. */
    public const COPY_FIELDS = [
        'customer' => 'العميل', 'visit_date' => 'تاريخ الزيارة', 'order_number' => 'رقم الطلب', 'visit' => 'زيارة',
        'address' => 'العنوان', 'location' => 'الموقع المثبّت', 'service' => 'خدمة', 'package' => 'عنصر الخدمة',
        'workers' => 'عامل', 'payment_method' => 'طريقة الدفع', 'price' => 'السعر', 'payment_status' => 'حالة السداد',
    ];

    public static function defaults(): array
    {
        return [
            'start_time' => '07:00',
            'end_time' => '23:00',
            'row_height' => 15,
            'copy_fields' => array_map(fn ($key) => ['key' => $key, 'enabled' => true, 'label' => true, 'blank' => false], array_keys(self::COPY_FIELDS)),
        ];
    }

    public static function all(): array
    {
        $stored = null;
        if (Schema::hasTable('new_settings')) {
            $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
            $stored = is_string($raw) ? json_decode($raw, true) : null;
        }

        return self::normalize(is_array($stored) ? array_replace(self::defaults(), $stored) : self::defaults());
    }

    public static function persist(array $payload): array
    {
        $settings = self::normalize(array_replace(self::all(), $payload));
        NewSettings::query()->updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE)]);

        return $settings;
    }

    public static function normalize(array $settings): array
    {
        $defaults = self::defaults();
        $hour = fn ($value, $fallback) => preg_match('/^([01]\d|2[0-3]):00$/', (string) $value) ? $value : $fallback;
        $start = $hour($settings['start_time'] ?? null, $defaults['start_time']);
        $end = ($settings['end_time'] ?? null) === '24:00' ? '24:00' : $hour($settings['end_time'] ?? null, $defaults['end_time']);
        // النهاية بعد البداية بساعة على الأقل، وإلا نعود للافتراضي.
        if ((int) substr($end, 0, 2) <= (int) substr($start, 0, 2)) {
            [$start, $end] = [$defaults['start_time'], $defaults['end_time']];
        }

        // الحقول المعروفة فقط، بلا تكرار، وأي حقل ناقص يُلحق بآخر القائمة معطّلًا حتى تظهر الحقول الجديدة في الإعدادات.
        $fields = [];
        foreach ((array) ($settings['copy_fields'] ?? []) as $field) {
            $key = is_array($field) ? ($field['key'] ?? null) : null;
            if (!isset(self::COPY_FIELDS[$key]) || isset($fields[$key])) continue;
            $fields[$key] = ['key' => $key, 'enabled' => filter_var($field['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN), 'label' => filter_var($field['label'] ?? false, FILTER_VALIDATE_BOOLEAN), 'blank' => filter_var($field['blank'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        }
        foreach (array_keys(self::COPY_FIELDS) as $key) {
            $fields[$key] ??= ['key' => $key, 'enabled' => false, 'label' => true, 'blank' => false];
        }

        return [
            'start_time' => $start,
            'end_time' => $end,
            'row_height' => max(10, min(30, (int) ($settings['row_height'] ?? $defaults['row_height']))),
            'copy_fields' => array_values($fields),
        ];
    }
}
