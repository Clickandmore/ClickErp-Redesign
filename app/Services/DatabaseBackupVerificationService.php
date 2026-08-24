<?php

namespace App\Services;

use App\Support\SmsGateway;
use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DatabaseBackupVerificationService
{
    public const SESSION_KEY = 'database_backup_verification';

    private const ACTIONS = ['restore', 'delete'];

    public function availableChannels(): array
    {
        $channels = [];
        $settings = DevSuperAdminSecuritySettings::all();
        $email = trim((string) $settings['email']);
        $phone = trim((string) $settings['phone']);

        if ($email !== '') {
            $channels['email'] = [
                'label' => 'البريد الإلكتروني',
                'destination' => $this->maskEmail($email),
            ];
        }

        if ($phone !== '') {
            $channels['phone'] = [
                'label' => 'رقم الجوال',
                'destination' => $this->maskPhone($phone),
            ];
        }

        return $channels;
    }

    public function issue(Request $request, string $action, string $key, string $channel): array
    {
        $this->assertAction($action);
        $channels = $this->availableChannels();
        if (!isset($channels[$channel])) {
            throw ValidationException::withMessages([
                'verification_channel' => ['وسيلة التحقق المختارة غير مهيأة أو غير صالحة.'],
            ]);
        }

        $rateKey = $this->rateKey($request);
        if (RateLimiter::tooManyAttempts($rateKey . ':minute', 1)) {
            throw ValidationException::withMessages([
                'verification_channel' => ['انتظر دقيقة قبل طلب رمز تحقق جديد.'],
            ]);
        }
        if (RateLimiter::tooManyAttempts($rateKey . ':window', 5)) {
            throw ValidationException::withMessages([
                'verification_channel' => ['تم تجاوز عدد طلبات رموز التحقق. حاول مرة أخرى بعد عشر دقائق.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $expiresMinutes = max(1, (int) config('dev_superadmin.verification.expires_minutes', 10));
        $actionLabel = $action === 'restore' ? 'استعادة' : 'حذف';
        $filename = basename($key);
        $message = "رمز التحقق لتأكيد {$actionLabel} نسخة قاعدة البيانات {$filename}: {$code}. صالح لمدة {$expiresMinutes} دقائق. لا تشارك الرمز مع أي شخص.";

        if ($channel === 'email') {
            $destination = trim((string) DevSuperAdminSecuritySettings::all()['email']);
            Mail::raw($message, function ($mail) use ($destination, $actionLabel): void {
                $mail->to($destination)->subject("رمز تأكيد {$actionLabel} قاعدة بيانات ClickERP");
            });
        } else {
            $destination = trim((string) DevSuperAdminSecuritySettings::all()['phone']);
            if (SmsGateway::send(formatSaudiPhone($destination), $message, $code, 'verification') === null) {
                throw new RuntimeException('تعذر إرسال رمز التحقق إلى الجوال. تحقق من إعدادات بوابة الرسائل.');
            }
        }

        RateLimiter::hit($rateKey . ':minute', 60);
        RateLimiter::hit($rateKey . ':window', 600);

        $challenge = [
            'action' => $action,
            'key' => $key,
            'code_hash' => Hash::make($code),
            'channel' => $channel,
            'destination' => $channels[$channel]['destination'],
            'expires_at' => now()->addMinutes($expiresMinutes)->getTimestamp(),
            'attempts' => 0,
        ];
        $request->session()->put(self::SESSION_KEY, $challenge);

        logger()->notice('Database backup verification code issued.', [
            'action' => $action,
            'backup' => $filename,
            'channel' => $channel,
            'ip' => $request->ip(),
        ]);

        return $this->publicChallenge($challenge);
    }

    public function current(Request $request): ?array
    {
        $challenge = $request->session()->get(self::SESSION_KEY);
        if (!is_array($challenge)) {
            return null;
        }

        if ((int) ($challenge['expires_at'] ?? 0) <= now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);
            return null;
        }

        return $this->publicChallenge($challenge);
    }

    public function verifyAndConsume(Request $request, string $action, string $key, string $code): void
    {
        $this->assertAction($action);
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (!is_array($challenge)
            || !hash_equals((string) ($challenge['action'] ?? ''), $action)
            || !hash_equals((string) ($challenge['key'] ?? ''), $key)) {
            throw ValidationException::withMessages([
                'verification_code' => ['اطلب رمز تحقق جديدًا لهذه العملية ولهذه النسخة تحديدًا.'],
            ]);
        }

        if ((int) ($challenge['expires_at'] ?? 0) <= now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);
            throw ValidationException::withMessages([
                'verification_code' => ['انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.'],
            ]);
        }

        $maxAttempts = max(1, (int) config('dev_superadmin.verification.max_attempts', 5));
        $attempts = (int) ($challenge['attempts'] ?? 0);
        if ($attempts >= $maxAttempts) {
            $request->session()->forget(self::SESSION_KEY);
            throw ValidationException::withMessages([
                'verification_code' => ['تم تجاوز عدد محاولات التحقق. اطلب رمزًا جديدًا.'],
            ]);
        }

        if (!preg_match('/^\d{6}$/', $code) || !Hash::check($code, (string) ($challenge['code_hash'] ?? ''))) {
            $challenge['attempts'] = $attempts + 1;
            if ($challenge['attempts'] >= $maxAttempts) {
                $request->session()->forget(self::SESSION_KEY);
            } else {
                $request->session()->put(self::SESSION_KEY, $challenge);
            }

            throw ValidationException::withMessages([
                'verification_code' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        // One-time use: consume before the destructive operation starts so a
        // failed operation cannot be replayed with the same code.
        $request->session()->forget(self::SESSION_KEY);
        logger()->notice('Database backup destructive action verified.', [
            'action' => $action,
            'backup' => basename($key),
            'ip' => $request->ip(),
        ]);
    }

    private function publicChallenge(array $challenge): array
    {
        return [
            'action' => (string) $challenge['action'],
            'key' => (string) $challenge['key'],
            'channel' => (string) $challenge['channel'],
            'destination' => (string) $challenge['destination'],
            'expires_at' => (int) $challenge['expires_at'],
        ];
    }

    private function assertAction(string $action): void
    {
        if (!in_array($action, self::ACTIONS, true)) {
            throw ValidationException::withMessages(['action' => ['نوع العملية غير صالح.']]);
        }
    }

    private function rateKey(Request $request): string
    {
        return 'database-backup-verification:' . hash('sha256', $request->session()->getId() . '|' . $request->ip());
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $length = mb_strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . mb_substr($phone, -4);
    }
}
