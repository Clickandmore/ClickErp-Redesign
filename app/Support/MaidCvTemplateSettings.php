<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

class MaidCvTemplateSettings
{
    private static ?Setting $cachedSetting = null;

    public static function current(): ?Setting
    {
        if (!Schema::hasTable('settings')
            || !Schema::hasColumn('settings', 'cv_template_enabled')
            || !Schema::hasColumn('settings', 'cv_template_blade')) {
            return null;
        }

        return self::$cachedSetting ??= Setting::query()->first();
    }

    public static function enabled(): bool
    {
        $setting = self::current();

        return (bool) ($setting?->cv_template_enabled)
            && filled($setting?->cv_template_blade);
    }

    public static function outputType(): string
    {
        $setting = self::current();
        $type = strtolower(trim((string) ($setting?->cv_template_output_type ?? 'pdf')));

        return in_array($type, ['pdf', 'image'], true) ? $type : 'pdf';
    }

    public static function clearCache(): void
    {
        self::$cachedSetting = null;
    }
}
