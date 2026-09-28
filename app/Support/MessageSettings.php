<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Cache;

class MessageSettings
{
    public const MESSAGE_URL = 'MESSAGE_URL';
    public const MESSAGE_TOKEN = 'MESSAGE_TOKEN';
    public const MESSAGE_SENDER = 'MESSAGE_SENDER';
    public const MESSAGE_LIVE = 'MESSAGE_Live';
    public const REGISTRATION_OTP_DISABLED = 'registration_otp_disabled';

    public const CACHE_KEY = 'message_settings.values';

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return NewSettings::query()
                ->whereIn('key', self::keys())
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function get(string $key, $default = null)
    {
        $settings = self::all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function messageUrl(): string
    {
        return (string) self::get(
            self::MESSAGE_URL,
            env('TEQNYAT_BASE_URL', env('MESSAGE_URL', 'https://api.taqnyat.sa/v1/messages'))
        );
    }

    public static function token(): ?string
    {
        return self::nullableString(self::get(
            self::MESSAGE_TOKEN,
            env('TEQNYAT_BEARER_TOKEN', env('MESSAGE_TOKEN'))
        ));
    }

    public static function sender(): ?string
    {
        return self::nullableString(self::get(
            self::MESSAGE_SENDER,
            env('TEQNYAT_SENDER', env('MESSAGE_SENDER'))
        ));
    }

    public static function smsEnabled(): bool
    {
        return self::boolValue(self::get(
            self::MESSAGE_LIVE,
            env('TEQNYAT_ENABLED', env('MESSAGE_Live', false))
        ));
    }

    public static function registrationOtpDisabled(): bool
    {
        return self::boolValue(self::get(self::REGISTRATION_OTP_DISABLED, false));
    }

    public static function formValues(): array
    {
        return [
            self::MESSAGE_URL => self::messageUrl(),
            self::MESSAGE_TOKEN => self::token(),
            self::MESSAGE_SENDER => self::sender(),
            self::MESSAGE_LIVE => self::smsEnabled(),
            self::REGISTRATION_OTP_DISABLED => self::registrationOtpDisabled(),
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function keys(): array
    {
        return [
            self::MESSAGE_URL,
            self::MESSAGE_TOKEN,
            self::MESSAGE_SENDER,
            self::MESSAGE_LIVE,
            self::REGISTRATION_OTP_DISABLED,
        ];
    }

    protected static function boolValue($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected static function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
