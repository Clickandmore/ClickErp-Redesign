<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\MoyasarService;
use App\Support\MoyasarSettings;
use App\Support\PaymentMessageTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** إعدادات بوابة ميسر؛ صفحة من لوحة Super Admin ومحمية بجلستها (dev.superadmin). */
class MoyasarSettingsController extends Controller
{
    public function edit()
    {
        $settings = MoyasarSettings::current();
        $hasKeys = $settings['publishable_key'] !== '' && $settings['secret_key'] !== '';
        // المفتاحان يبدآن بـ pk_test/sk_test في بيئة الاختبار وpk_live/sk_live في الإنتاج.
        $environment = match (true) {
            str_starts_with($settings['secret_key'], 'sk_live') => 'live',
            str_starts_with($settings['secret_key'], 'sk_test') => 'test',
            default => null,
        };

        return view('admin.superadmin.moyasar-settings', [
            'moyasarSettings' => $settings,
            'paymentMessages' => PaymentMessageTemplates::formValues(),
            'readiness' => [
                'keys' => $hasKeys,
                'webhook' => $settings['webhook_secret'] !== '',
                'enabled' => MoyasarSettings::isEnabled(),
                'environment' => $environment,
                'mismatched' => $environment !== null && $settings['publishable_key'] !== ''
                    && !str_starts_with($settings['publishable_key'], 'pk_' . $environment),
                'masked' => MoyasarSettings::containsMaskCharacters($settings['publishable_key'])
                    || MoyasarSettings::containsMaskCharacters($settings['secret_key'])
                    || MoyasarSettings::containsMaskCharacters($settings['webhook_secret']),
            ],
            'webhookUrl' => route('moyasar.webhook'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'reports_enabled' => 'nullable|boolean',
            'base_url' => 'required|url|max:255',
            'publishable_key' => 'nullable|string|max:1000',
            'secret_key' => 'nullable|string|max:1000',
            'webhook_secret' => 'nullable|string|max:1000',
            'company_name' => 'nullable|string|max:255',
            'payment_description' => 'nullable|string|max:500',
            'payment_messages' => 'nullable|array',
            'payment_messages.company_name' => 'nullable|string|max:255',
            'payment_messages.rental' => 'nullable|string|max:1000',
            'payment_messages.transfer' => 'nullable|string|max:1000',
        ], [
            'base_url.required' => 'رابط Moyasar API مطلوب.',
            'base_url.url' => 'رابط Moyasar API غير صحيح.',
        ]);

        $current = MoyasarSettings::current();
        $willBeEnabled = (bool) ($validated['is_enabled'] ?? false);
        $publishableKey = trim((string) ($validated['publishable_key'] ?? '')) ?: $current['publishable_key'];
        $secretKey = trim((string) ($validated['secret_key'] ?? '')) ?: $current['secret_key'];
        $webhookSecret = trim((string) ($validated['webhook_secret'] ?? '')) ?: $current['webhook_secret'];

        $maskedValues = [];

        if (MoyasarSettings::containsMaskCharacters($publishableKey)) {
            $maskedValues['publishable_key'] = 'مفتاح النشر يحتوي على نجوم (*)، أدخل المفتاح الحقيقي من لوحة ميسر.';
        }

        if (MoyasarSettings::containsMaskCharacters($secretKey)) {
            $maskedValues['secret_key'] = 'المفتاح السري يحتوي على نجوم (*)، أدخل المفتاح الحقيقي من لوحة ميسر.';
        }

        if (MoyasarSettings::containsMaskCharacters($webhookSecret)) {
            $maskedValues['webhook_secret'] = 'Webhook Secret يحتوي على نجوم (*)، أدخل القيمة الحقيقية من لوحة ميسر.';
        }

        if ($maskedValues) {
            throw ValidationException::withMessages($maskedValues);
        }

        foreach (array_keys(PaymentMessageTemplates::TYPES) as $type) {
            $template = (string) ($validated['payment_messages'][$type] ?? '');
            if (trim($template) !== '' && !str_contains($template, '{payment_link}')) {
                throw ValidationException::withMessages([
                    "payment_messages.$type" => 'يجب أن يحتوي نص الرسالة على المتغير {payment_link} حتى يصل رابط الدفع للعميل.',
                ]);
            }
        }

        if ($willBeEnabled && ($publishableKey === '' || $secretKey === '')) {
            $messages = [];

            if ($publishableKey === '') {
                $messages['publishable_key'] = 'مفتاح النشر مطلوب عند تفعيل ميسر.';
            }

            if ($secretKey === '') {
                $messages['secret_key'] = 'المفتاح السري مطلوب عند تفعيل ميسر.';
            }

            throw ValidationException::withMessages($messages);
        }

        try {
            MoyasarSettings::persist($validated, auth('admin')->id());
            if (array_key_exists('payment_messages', $validated)) {
                PaymentMessageTemplates::save((array) $validated['payment_messages']);
            }

            return redirect()
                ->route('dev-superadmin.moyasar-settings')
                ->with('success', 'تم حفظ إعدادات ميسر بنجاح.');
        } catch (\Throwable $exception) {
            Log::error('Failed to update Moyasar settings', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('dev-superadmin.moyasar-settings')
                ->withInput()
                ->with('error', 'تعذر حفظ إعدادات ميسر حالياً.');
        }
    }

    /** يختبر المفتاح السري المحفوظ، لا القيم غير المحفوظة في النموذج. */
    public function testConnection()
    {
        $result = app(MoyasarService::class)->testConnection();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
