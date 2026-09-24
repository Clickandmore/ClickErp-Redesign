<?php

namespace App\Support;

use Nami\Finance\Models\ConfirmSetting;

class PostArrivalRefundSettings
{
    public const POSTING_MODE = 'return_settlement_posting';

    public static function postingMode(): string
    {
        $value = trim((string) ConfirmSetting::query()
            ->where('title', self::POSTING_MODE)
            ->latest('updated_at')
            ->value('value'));

        return in_array($value, ['manual_screen', 'auto'], true) ? $value : 'manual_screen';
    }

    public static function manualScreen(): bool
    {
        return self::postingMode() === 'manual_screen';
    }
}
