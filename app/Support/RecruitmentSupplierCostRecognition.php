<?php

namespace App\Support;

use Nami\Finance\Models\ConfirmSetting;

class RecruitmentSupplierCostRecognition
{
    public const SETTING_KEY = 'recruitment_supplier_cost_recognition';
    public const MODE_ON_ARRIVAL = 'on_arrival';
    public const MODE_ON_DISPATCH = 'on_dispatch';

    public static function normalize($value): string
    {
        return $value === self::MODE_ON_DISPATCH ? self::MODE_ON_DISPATCH : self::MODE_ON_ARRIVAL;
    }

    public static function mode(): string
    {
        return self::normalize(ConfirmSetting::query()
            ->where('title', self::SETTING_KEY)->latest('updated_at')->value('value'));
    }
}
