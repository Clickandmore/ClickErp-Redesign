<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

class AppAuthSettings
{
    public const MODE_PASSWORD = 'password';
    public const MODE_OTP = 'otp';

    public static function mode(): string
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'app_auth_mode')) {
                return self::MODE_PASSWORD;
            }

            $mode = Setting::query()->value('app_auth_mode');
        } catch (\Throwable) {
            return self::MODE_PASSWORD;
        }

        return $mode === self::MODE_OTP ? self::MODE_OTP : self::MODE_PASSWORD;
    }

    public static function usesOtpOnly(): bool
    {
        return self::mode() === self::MODE_OTP;
    }
}
