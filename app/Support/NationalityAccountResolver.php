<?php

namespace App\Support;

use App\Models\ingaz\Nationality;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

/**
 * حل حسابات الجنسيات بترتيب ثابت:
 *  1. المفتاح المحفوظ من صفحة إعدادات الحسابات: nationality_account_{id}_{type}.
 *  2. مطابقة الاسم بعد تطبيع الهمزة (إ/أ/آ → ا) على الطرفين، مع صيغتي المفرد والجمع.
 *
 * الأنواع المتاحة هي نفسها المعرّفة في NationalityFinanceAccountService::DEFINITIONS
 * (recruitment_sales, recruitment_cost, contract_liability, shelter_prepaid_expense,
 * recruitment_sales_return, recruitment_cost_return, transfer_advance, transfer_revenue, ...).
 */
class NationalityAccountResolver
{
    /** مرشحات الاسم لكل نوع، تُطبَّق بعد تطبيع الهمزة و{n} هو اسم الجنسية. */
    private const NAME_PATTERNS = [
        'shelter_prepaid_expense' => ['%مدفوعة مقدما%ايواء%{n}%', '%ايواء%{n}%'],
        'recruitment_sales_return' => ['%مردود%مبيعات%استقدام%{n}%', '%مردود%مبيعات%{n}%'],
        'recruitment_cost_return' => ['%مردود%تكلفة%{n}%'],
        'transfer_sales_return' => ['%مردود%نقل خدمات%{n}%'],
        'transfer_advance' => ['%دفعات مقدمة%نقل خدمات%{n}%'],
        'transfer_revenue' => ['%ايراد%نقل خدمات%{n}%', '%ايرادات%نقل خدمات%{n}%'],
        // المبيعات والتكلفة والالتزام تبقى على محلّلات RecruitmentTripAccountingService
        // لأن أنماطها العامة قد تلتقط حسابات المردودات قبل الحساب الأصلي.
    ];

    public static function forNationality(?Nationality $nationality, string $type): ?int
    {
        if (!$nationality) {
            return null;
        }

        $name = trim((string) ($nationality->sub_title ?: $nationality->title));

        return self::resolve((int) $nationality->id, $name, $type);
    }

    public static function resolve(?int $nationalityId, string $nationalityName, string $type): ?int
    {
        $configured = self::configured($nationalityId, $type);
        if ($configured) {
            return $configured;
        }

        return self::byName($nationalityName, $type);
    }

    public static function configured(?int $nationalityId, string $type): ?int
    {
        if (!$nationalityId) {
            return null;
        }

        $value = ConfirmSetting::query()
            ->where('title', 'nationality_account_' . $nationalityId . '_' . $type)
            ->latest('updated_at')
            ->value('value');

        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return Account::query()->whereKey((int) $value)->exists() ? (int) $value : null;
    }

    public static function byName(string $nationalityName, string $type): ?int
    {
        $nationalityName = trim($nationalityName);
        $patterns = self::NAME_PATTERNS[$type] ?? [];
        if ($nationalityName === '' || empty($patterns)) {
            return null;
        }

        $normalizedName = self::normalize($nationalityName);
        $nameColumn = self::normalizedNameSql();

        foreach ($patterns as $pattern) {
            $pattern = self::normalize(str_replace('{n}', $normalizedName, $pattern));
            $exact = !str_contains($pattern, '%');

            $accountId = Account::query()
                ->whereDoesntHave('children')
                ->whereRaw($nameColumn . ($exact ? ' = ?' : ' LIKE ?'), [$pattern])
                ->orderBy('id')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }

    /**
     * تطبيع الأسماء العربية للمقارنة: توحيد الهمزات والتاء المربوطة والمسافات.
     */
    public static function normalize(string $value): string
    {
        $value = str_replace(['إ', 'أ', 'آ', 'ٱ'], 'ا', $value);
        $value = str_replace(['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ'], '', $value);
        $value = str_replace(['(', ')', '-', '–', '—'], ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private static function normalizedNameSql(): string
    {
        $expr = '`name`';
        foreach (['إ', 'أ', 'آ', 'ٱ'] as $hamza) {
            $expr = "REPLACE({$expr}, '{$hamza}', 'ا')";
        }
        foreach (['ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ'] as $tashkeel) {
            $expr = "REPLACE({$expr}, '{$tashkeel}', '')";
        }
        foreach (['(', ')', '-', '–', '—'] as $sep) {
            $expr = "REPLACE({$expr}, '{$sep}', ' ')";
        }

        return $expr;
    }
}
