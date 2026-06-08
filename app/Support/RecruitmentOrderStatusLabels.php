<?php

namespace App\Support;

use App\Enums\ingaz\OrderEnum;
use App\Models\Setting;

class RecruitmentOrderStatusLabels
{
    private const SETTING_KEY = 'recruitment_order_status_labels';

    private static ?array $cachedLabels = null;

    public static function defaults(): array
    {
        return [
            OrderEnum::New->value => [
                'ar' => 'جديد',
                'en' => 'New',
            ],
            OrderEnum::UnderProcess->value => [
                'ar' => 'تحت الإجراء',
                'en' => 'Under Process',
            ],
            OrderEnum::Contracted->value => [
                'ar' => 'تم التعاقد',
                'en' => 'Contracted',
            ],
            OrderEnum::ContractedWithoutContract->value => [
                'ar' => 'تم التعاقد بدون عقد',
                'en' => 'Contracted Without Contract',
            ],
            OrderEnum::Completed->value => [
                'ar' => 'مكتملة',
                'en' => 'Completed',
            ],
            OrderEnum::Done->value => [
                'ar' => 'مكتملة',
                'en' => 'Done',
            ],
            OrderEnum::Refused->value => [
                'ar' => 'مرفوضة',
                'en' => 'Refused',
            ],
            OrderEnum::Return->value => [
                'ar' => 'استرجاع',
                'en' => 'Return',
            ],
            OrderEnum::CanceledBySystem->value => [
                'ar' => 'ملغاة آلياً',
                'en' => 'Canceled by System',
            ],
        ];
    }

    public static function labels(): array
    {
        if (self::$cachedLabels !== null) {
            return self::$cachedLabels;
        }

        $labels = self::defaults();
        $stored = json_decode((string) Setting::getValue(self::SETTING_KEY, ''), true);

        if (is_array($stored)) {
            foreach ($labels as $status => $locales) {
                foreach (['ar', 'en'] as $locale) {
                    $value = trim((string) data_get($stored, "{$status}.{$locale}", ''));
                    if ($value !== '') {
                        $labels[$status][$locale] = $value;
                    }
                }
            }
        }

        return self::$cachedLabels = $labels;
    }

    public static function label(?string $status, ?string $locale = null): ?string
    {
        if (!$status) {
            return null;
        }

        $locale = $locale === 'en' ? 'en' : 'ar';
        $labels = self::labels();

        return $labels[$status][$locale] ?? $labels[$status]['ar'] ?? null;
    }

    public static function save(array $labels): void
    {
        $cleanLabels = [];

        foreach (self::defaults() as $status => $defaultLocales) {
            foreach (['ar', 'en'] as $locale) {
                $value = trim((string) data_get($labels, "{$status}.{$locale}", ''));
                $cleanLabels[$status][$locale] = $value !== '' ? $value : $defaultLocales[$locale];
            }
        }

        Setting::setValue(self::SETTING_KEY, json_encode($cleanLabels, JSON_UNESCAPED_UNICODE), 'recruitment');
        self::$cachedLabels = $cleanLabels;
    }

    public static function reset(): void
    {
        Setting::where('key', self::SETTING_KEY)->delete();
        self::$cachedLabels = self::defaults();
    }
}
