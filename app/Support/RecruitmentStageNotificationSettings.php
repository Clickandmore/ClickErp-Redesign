<?php

namespace App\Support;

use App\Models\Setting;

class RecruitmentStageNotificationSettings
{
    public const KEY = 'recruitment_stage_notifications';
    public const TEMPLATE = 'عزيزنا {client_name}، تحديث العقد رقم {contract_number}: {stage_name}{step_name}.';
    public const VARIABLES = ['client_name', 'contract_number', 'stage_name', 'step_name'];

    public static function all(): array
    {
        return json_decode((string) Setting::getValue(self::KEY, '{}'), true) ?: [];
    }

    public static function smsManaged(string $stage): bool
    {
        $settings = self::all();
        return !empty($settings['enabled']) && !empty($settings['stages'][$stage]['sms']);
    }

    public static function save(array $settings): void
    {
        Setting::setValue(self::KEY, json_encode($settings, JSON_UNESCAPED_UNICODE), 'recruitment');
    }
}
