<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

class ExternalOrderSettings
{
    public const MODE_AUTHENTICATED = 'authenticated';
    public const MODE_SIMPLIFIED = 'simplified';

    public static function mode(): string
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'external_order_mode')) {
                return self::MODE_AUTHENTICATED;
            }

            $mode = Setting::query()->value('external_order_mode');
        } catch (\Throwable) {
            return self::MODE_AUTHENTICATED;
        }

        return $mode === self::MODE_SIMPLIFIED
            ? self::MODE_SIMPLIFIED
            : self::MODE_AUTHENTICATED;
    }

    public static function usesSimplifiedOrdering(): bool
    {
        return self::mode() === self::MODE_SIMPLIFIED;
    }
}
