<?php

namespace Nami\Finance\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * إعدادات الدليل المحاسبي لكل شركة (fn_confirm_settings). الافتراضي فارغ: الترقيم والعرض كما كانا.
 * levelDigits: طول الكود لكل مستوى، مثل [1 => 1, 2 => 2, 3 => 4, 4 => 8].
 */
class ChartSettings
{
    public const LEVEL_DIGITS = 'account_code_level_digits';
    public const TYPE_DISPLAY = 'account_type_display';
    public const TYPE_DISPLAY_HIERARCHY = 'hierarchy';

    private static array $values = [];

    public static function levelDigits(): array
    {
        $digits = json_decode((string) self::value(self::LEVEL_DIGITS), true);
        if (!is_array($digits)) {
            return [];
        }
        $normalized = [];
        foreach ($digits as $level => $length) {
            $normalized[(int) $level] = (int) $length;
        }
        ksort($normalized);

        return $normalized;
    }

    public static function typeDisplayByHierarchy(): bool
    {
        return self::value(self::TYPE_DISPLAY) === self::TYPE_DISPLAY_HIERARCHY;
    }

    public static function put(string $title, ?string $value): void
    {
        if ($value === null || $value === '') {
            DB::table('fn_confirm_settings')->where('title', $title)->delete();
        } else {
            DB::table('fn_confirm_settings')->updateOrInsert(['title' => $title], [
                'value' => $value, 'created_by_id' => auth('admin')->id() ?? 1, 'updated_at' => now(),
            ]);
        }
        self::reset();
    }

    public static function reset(): void
    {
        self::$values = [];
    }

    /**
     * أول كود لابن هذا الأب حسب طول المستوى، أو null إذا لم يُضبط الإعداد أو لم يكن كود الأب بطوله الصحيح
     * (فيعمل الترقيم القديم: كود الأب × 100).
     */
    public static function childCodeStart(int|string $parentCode, int $parentLevel): ?int
    {
        $digits = self::levelDigits();
        $parentLength = $digits[$parentLevel] ?? null;
        $childLength = $digits[$parentLevel + 1] ?? null;
        if (!$parentLength || !$childLength || strlen((string) $parentCode) !== $parentLength) {
            return null;
        }

        return ((int) $parentCode) * (10 ** ($childLength - $parentLength)) + 1;
    }

    /** يمنع تجاوز سعة المستوى: كود أطول من المحدد يكسر طول المستوى. */
    public static function assertFits(int $code, int $level): void
    {
        $length = self::levelDigits()[$level] ?? null;
        if ($length && strlen((string) $code) > $length) {
            throw new RuntimeException("امتلأت أكواد المستوى {$level} ({$length} خانة)؛ زد عدد الخانات من إعدادات الدليل.");
        }
    }

    private static function value(string $title): ?string
    {
        if (array_key_exists($title, self::$values)) {
            return self::$values[$title];
        }
        $value = Schema::hasTable('fn_confirm_settings') && Schema::hasColumn('fn_confirm_settings', 'title')
            ? DB::table('fn_confirm_settings')->where('title', $title)->value('value')
            : null;

        return self::$values[$title] = $value === null ? null : (string) $value;
    }
}
