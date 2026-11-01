<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactNotificationSettings
{
    private const KEY = 'contact_notification_settings';

    public static function all(): array
    {
        $stored = Schema::hasTable('new_settings')
            ? json_decode((string) NewSettings::where('key', self::KEY)->value('value'), true) : [];
        $settings = array_replace(['enabled' => false, 'channel' => 'whatsapp', 'phone' => ''], is_array($stored) ? $stored : []);
        if (trim((string) $settings['phone']) === '' && Schema::hasColumn('settings', 'phone')) {
            $settings['phone'] = trim((string) DB::table('settings')->orderBy('id')->value('phone'));
        }

        return $settings;
    }

    public static function validate(array $payload): array
    {
        return Validator::make($payload, [
            'enabled' => ['required', 'boolean'],
            'channel' => ['required', Rule::in(['whatsapp', 'sms'])],
            'phone' => ['nullable', 'required_if:enabled,1', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
        ], [], ['phone' => 'الرقم الموحد', 'channel' => 'قناة الإرسال'])->validate();
    }

    public static function persist(array $payload): void
    {
        $data = self::validate($payload);
        $data['enabled'] = (bool) $data['enabled'];
        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        NewSettings::updateOrCreate(['key' => self::KEY], ['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }
}
