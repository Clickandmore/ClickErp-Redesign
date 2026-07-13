<?php

namespace App\Support;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Setting;

class RecruitmentContractStageLabels
{
    private const SETTING_KEY = 'recruitment_contract_stage_labels';

    private static ?array $cachedLabels = null;

    public static function defaults(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value => [
                'ar' => 'بانتظار اختيار العاملة',
                'en' => 'Pending Maid Selection',
            ],
            ContractStatusEnum::New->value => [
                'ar' => 'عقود جديدة',
                'en' => 'New Contracts',
            ],
            ContractStatusEnum::Msaned->value => [
                'ar' => 'عقود مساند',
                'en' => 'Msaned Contracts',
            ],
            ContractStatusEnum::Tafwed->value => [
                'ar' => 'تفويض',
                'en' => 'Tafwed',
            ],
            ContractStatusEnum::Taffez->value => [
                'ar' => 'التفييز',
                'en' => 'Taffez',
            ],
            ContractStatusEnum::Tazker->value => [
                'ar' => 'تذكرة',
                'en' => 'Tazker',
            ],
            ContractStatusEnum::Arrival->value => [
                'ar' => 'مرحلة الوصول',
                'en' => 'Arrival Stage',
            ],
            ContractStatusEnum::Completed->value => [
                'ar' => 'عقد مكتمل',
                'en' => 'Completed Contract',
            ],
            ContractStatusEnum::Refunded->value => [
                'ar' => 'مسترد',
                'en' => 'Refunded',
            ],
            ContractStatusEnum::Back->value => [
                'ar' => 'مرتجع',
                'en' => 'Returned',
            ],
            ContractStatusEnum::Sari->value => [
                'ar' => 'عقد ساري',
                'en' => 'Active Contract',
            ],
            ContractStatusEnum::Shelter->value => [
                'ar' => 'عقد إيواء',
                'en' => 'Shelter Contract',
            ],
            ContractStatusEnum::AccreditationContracts->value => [
                'ar' => 'اعتماد العقود',
                'en' => 'Accreditation Contracts',
            ],
            ContractStatusEnum::UnderWarranty->value => [
                'ar' => 'تحت الضمان',
                'en' => 'Under Warranty',
            ],
            ContractStatusEnum::Closed->value => [
                'ar' => 'عقد مغلق',
                'en' => 'Closed Contract',
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
