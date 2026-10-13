<?php

namespace App\Support;

use App\Models\NewSettings;
use App\Models\ingaz\User;
use App\Models\Hourly\{HourlyDriver, HourlyMaidAccount};
use Illuminate\Support\Facades\{Crypt, DB, Hash, Schema};

/** Explicit, role-scoped OTP exceptions. Ordinary authentication stays unchanged. */
class HourlyAppReviewAccounts
{
    public const KEY = 'hourly_app_review_accounts';
    public const ROLES = ['customer' => 'العميل', 'worker' => 'العاملة', 'driver' => 'السائق'];

    public static function all(): array
    {
        $defaults = ['enabled' => false, 'accounts' => []];
        if (!Schema::hasTable('new_settings')) return $defaults;
        $value = NewSettings::where('key', self::KEY)->value('value');
        return $value ? array_replace($defaults, json_decode(Crypt::decryptString($value), true, 512, JSON_THROW_ON_ERROR)) : $defaults;
    }

    public static function query(string $role)
    {
        return match ($role) {
            'customer' => User::query(),
            'driver' => HourlyDriver::query(),
            'worker' => HourlyMaidAccount::where('rental_mode', 'hourly'),
        };
    }

    public static function phone(string $role, $account): string
    {
        if ($role === 'customer') return (string) $account->phone;
        if ($role === 'driver') return (string) ($account->login_phone ?: $account->phone);
        foreach (['hourly_login_phone', 'phone', 'phone_1', 'phone_2'] as $column) {
            if (Schema::hasColumn('maids', $column) && filled($account->$column)) return (string) $account->$column;
        }
        return '';
    }

    public static function available(string $role, $account): bool
    {
        if (!$account) return false;
        if ($role === 'customer') {
            return !(int) $account->is_blocked && (string) $account->phone_code === '966'
                && (bool) preg_match('/^5[0-9]{8}$/', (string) $account->phone);
        }
        if (!$account->canLoginVia('mobile')) return false;
        if ($role === 'worker') return !$account->hourly_is_suspended;
        foreach ($account->expiringDocuments(0) as $days) if ($days < 0) return false;
        return true;
    }

    public static function code(string $role, string $phone, ?int $accountId = null, ?string $countryCode = null): ?string
    {
        // A missing/broken review configuration must never interrupt ordinary login.
        try {
            return self::resolveCode($role, $phone, $accountId, $countryCode);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveCode(string $role, string $phone, ?int $accountId, ?string $countryCode): ?string
    {
        $settings = self::all();
        $entry = $settings['accounts'][$role] ?? [];
        if (!$settings['enabled'] || empty($entry['enabled']) || empty($entry['account_id'])) return null;
        if ($role === 'customer' && ltrim((string) $countryCode, '+') !== '966') return null;
        if ($accountId !== null && (int) $entry['account_id'] !== $accountId) return null;
        if (HourlyStaffOtp::normalize($phone) !== ($entry['phone'] ?? '')) return null;
        $account = self::query($role)->find($entry['account_id']);
        if (!self::available($role, $account) || HourlyStaffOtp::normalize(self::phone($role, $account)) !== $entry['phone']) return null;
        return $entry['code'];
    }

    public static function persist(array $settings): void
    {
        // Only revoke the previous review code when its active exception changes.
        DB::transaction(function () use ($settings) {
            $previous = self::all();
            if ($previous['enabled']) {
                foreach ($previous['accounts'] as $role => $entry) {
                    if (empty($entry['enabled']) || empty($entry['phone']) || empty($entry['code'])) continue;
                    $next = $settings['accounts'][$role] ?? [];
                    if ($settings['enabled'] && !empty($next['enabled'])
                        && ($next['account_id'] ?? null) === $entry['account_id']
                        && ($next['phone'] ?? null) === $entry['phone']
                        && ($next['code'] ?? null) === $entry['code']) continue;
                    $variants = HourlyStaffOtp::variants($entry['phone']);
                    if ($role === 'customer' && Schema::hasTable('phone_verifications')) {
                        DB::table('phone_verifications')->whereIn('phone', $variants)->where('phone_code', '966')->where('code', $entry['code'])->delete();
                    } elseif (Schema::hasTable('hourly_staff_otps')) {
                        $query = DB::table('hourly_staff_otps')->where('guard', $role)->whereIn('phone', $variants)->where('staff_id', $entry['account_id']);
                        foreach ((clone $query)->get() as $otp) {
                            if (Hash::check($entry['code'], $otp->code_hash)) (clone $query)->where('id', $otp->id)->where('code_hash', $otp->code_hash)->delete();
                        }
                    }
                }
            }
            NewSettings::updateOrCreate(['key' => self::KEY], ['value' => Crypt::encryptString(json_encode($settings, JSON_THROW_ON_ERROR))]);
        });
    }
}
