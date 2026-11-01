<?php

namespace Nami\Finance\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * البرنامج يبحث عن حسابات معينة بكود ثابت (144، 221، 4216…). بعد إعادة ترقيم دليل شركة يُحفظ هنا
 * الكود القديم ← رقم الحساب، فيعطي current() كود الحساب الحالي. الجدول فارغ = يعود الكود نفسه كما كان.
 */
class AccountCodeAliases
{
    private static ?array $map = null;

    public static function current(string|int $code): string
    {
        return self::map()[(string) $code] ?? (string) $code;
    }

    public static function many(array $codes): array
    {
        return array_map(fn ($code) => self::current($code), $codes);
    }

    public static function reset(): void
    {
        self::$map = null;
    }

    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }
        if (!Schema::hasTable('fn_account_code_aliases')) {
            return self::$map = [];
        }

        return self::$map = DB::table('fn_account_code_aliases as alias')
            ->join('fn_accounts as account', 'account.id', '=', 'alias.account_id')
            ->pluck('account.code', 'alias.legacy_code')
            ->map(fn ($code) => (string) $code)
            ->all();
    }
}
