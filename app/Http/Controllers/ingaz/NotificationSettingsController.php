<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Support\CommunicationSettings;
use App\Support\AutomationSettings;
use App\Support\NotificationAlertSettings;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationSettingsController extends Controller
{
    private const ALLOWED_SECTIONS = ['alerts', 'email', 'sms', 'whatsapp'];

    public function edit()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        return view('admin.ingaz.notifications.settings', [
            'communicationSettings' => CommunicationSettings::all(),
        ]);
    }

    public function editAlerts()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        return view('admin.ingaz.notifications.alerts', [
            'alertDefinitions' => NotificationAlertSettings::definitions(),
            'alertSettings' => NotificationAlertSettings::all(),
            'customAlertSettings' => NotificationAlertSettings::custom(),
            'alertChannelOptions' => NotificationAlertSettings::channelOptions(),
            'alertRecipientOptions' => NotificationAlertSettings::recipientOptions(),
            'alertUnitOptions' => NotificationAlertSettings::unitOptions(),
            'alertTemplateOptions' => NotificationAlertSettings::templateOptions(),
            'automationSettings' => AutomationSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        $section = (string) $request->input('section');

        if (!in_array($section, self::ALLOWED_SECTIONS, true)) {
            return redirect()
                ->route('notifications.settings')
                ->with('error', 'القسم المطلوب غير صحيح.');
        }

        try {
            $validatedSectionPayload = match ($section) {
                'alerts' => $this->validateAlertsSection($request),
                'email' => $this->validateEmailSection($request),
                'sms' => $this->validateSmsSection($request),
                'whatsapp' => $this->validateWhatsAppSection($request),
            };

            if ($section === 'alerts') {
                $validatedCustomAlerts = $this->validateCustomAlertsSection($request);
                $validatedAutomation = $this->validateAutomationSection($request);

                NotificationAlertSettings::persist($validatedSectionPayload, $validatedCustomAlerts);
                AutomationSettings::persist($validatedAutomation);
            } else {
                CommunicationSettings::persistSection($section, $validatedSectionPayload);
            }

            $route = $section === 'alerts' ? 'notifications.settings.alerts' : 'notifications.settings';

            return redirect()
                ->to(route($route) . '#' . $this->sectionAnchor($section))
                ->with('success', $this->successMessage($section));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Failed to update notification settings', [
                'error' => $exception->getMessage(),
                'section' => $section,
            ]);

            return redirect()
                ->to(route('notifications.settings') . '#' . $this->sectionAnchor($section))
                ->withInput()
                ->with('error', 'تعذر حفظ إعدادات الربط حالياً.');
        }
    }

    public function testWhatsApp(MetaWhatsAppService $metaWhatsAppService)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            abort(403, __('admin.no_permissions'));
        }

        $result = $metaWhatsAppService->connectionStatus();

        if ($result['success'] ?? false) {
            $phone = (string) data_get($result, 'response.display_phone_number', '');
            $name = (string) data_get($result, 'response.verified_name', '');

            return redirect()
                ->to(route('notifications.settings') . '#whatsapp-settings')
                ->with('success', 'تم الاتصال بـ Meta بنجاح'
                    . ($name !== '' ? " — {$name}" : '')
                    . ($phone !== '' ? " ({$phone})" : '')
                    . '.');
        }

        $error = (string) (
            data_get($result, 'response.error.message')
            ?: data_get($result, 'response.message')
            ?: implode(', ', (array) data_get($result, 'response.missing', []))
            ?: 'تعذر التحقق من الاتصال.'
        );

        return redirect()
            ->to(route('notifications.settings') . '#whatsapp-settings')
            ->with('error', 'فشل اختبار اتصال Meta: ' . $error);
    }

    private function validateEmailSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:email',
            'email' => 'required|array',
            'email.mailer' => 'required|in:smtp,sendmail,log',
            'email.host' => 'nullable|string|max:255',
            'email.port' => 'nullable|integer|min:1|max:65535',
            'email.encryption' => 'nullable|in:,tls,ssl',
            'email.username' => 'nullable|string|max:255',
            'email.password' => 'nullable|string|max:1000',
            'email.from_address' => 'nullable|email|max:255',
            'email.from_name' => 'nullable|string|max:255',
        ], [
            'email.from_address.email' => 'صيغة بريد المرسل غير صحيحة.',
        ]);

        return $validated['email'];
    }

    private function validateAlertsSection(Request $request): array
    {
        $definitionKeys = array_keys(NotificationAlertSettings::definitions());
        $channelKeys = array_keys(NotificationAlertSettings::channelOptions());
        $recipientKeys = array_keys(NotificationAlertSettings::recipientOptions());
        $unitKeys = array_keys(NotificationAlertSettings::unitOptions());

        $validated = $request->validate([
            'section' => 'required|in:alerts',
            'alerts' => 'required|array',
            'alerts.*.enabled' => 'nullable|boolean',
            'alerts.*.channels' => 'nullable|array',
            'alerts.*.channels.*' => 'required|in:' . implode(',', $channelKeys),
            'alerts.*.recipient' => 'required|in:' . implode(',', $recipientKeys),
            'alerts.*.lead_time_value' => 'required|integer|min:0|max:3650',
            'alerts.*.lead_time_unit' => 'required|in:' . implode(',', $unitKeys),
            'alerts.*.repeat_every_value' => 'nullable|integer|min:0|max:3650',
            'alerts.*.repeat_every_unit' => 'required|in:' . implode(',', $unitKeys),
            'alerts.*.template_id' => 'nullable|integer|exists:sms_templates,id',
        ], [
            'alerts.*.template_id.exists' => 'قالب الرسالة المحدد غير موجود.',
        ]);

        $alerts = $validated['alerts'];

        return collect($definitionKeys)
            ->mapWithKeys(fn (string $key): array => [$key => (array) ($alerts[$key] ?? [])])
            ->all();
    }

    private function validateCustomAlertsSection(Request $request): array
    {
        $channelKeys = array_keys(NotificationAlertSettings::channelOptions());
        $recipientKeys = array_keys(NotificationAlertSettings::recipientOptions());
        $unitKeys = array_keys(NotificationAlertSettings::unitOptions());

        $validated = $request->validate([
            'custom_alerts' => 'nullable|array',
            'custom_alerts.*.key' => 'nullable|string|max:100',
            'custom_alerts.*.label' => 'nullable|string|max:255',
            'custom_alerts.*.event_key' => 'nullable|string|max:120',
            'custom_alerts.*.description' => 'nullable|string|max:500',
            'custom_alerts.*.source_url' => 'nullable|string|max:500',
            'custom_alerts.*.enabled' => 'nullable|boolean',
            'custom_alerts.*.channels' => 'nullable|array',
            'custom_alerts.*.channels.*' => 'required|in:' . implode(',', $channelKeys),
            'custom_alerts.*.recipient' => 'required_with:custom_alerts.*.label|in:' . implode(',', $recipientKeys),
            'custom_alerts.*.lead_time_value' => 'required_with:custom_alerts.*.label|integer|min:0|max:3650',
            'custom_alerts.*.lead_time_unit' => 'required_with:custom_alerts.*.label|in:' . implode(',', $unitKeys),
            'custom_alerts.*.repeat_every_value' => 'nullable|integer|min:0|max:3650',
            'custom_alerts.*.repeat_every_unit' => 'required_with:custom_alerts.*.label|in:' . implode(',', $unitKeys),
            'custom_alerts.*.template_id' => 'nullable|integer|exists:sms_templates,id',
        ], [
            'custom_alerts.*.template_id.exists' => 'قالب الرسالة المحدد في التنبيهات المخصصة غير موجود.',
        ]);

        return $validated['custom_alerts'] ?? [];
    }

    private function validateAutomationSection(Request $request): array
    {
        return $request->validate([
            'warranty_period_days' => 'required|integer|min:1|max:3650',
            'order_expiration_hours' => 'required|integer|min:1|max:720',
            'order_new_deadline_hours' => 'required|integer|min:1|max:720',
            'order_under_process_deadline_hours' => 'required|integer|min:1|max:720',
            'order_urgent_threshold_hours' => 'required|integer|min:1|max:720',
            'old_pending_orders_days' => 'required|integer|min:1|max:3650',
            'warranty_status_update_time' => 'required|date_format:H:i',
            'warranty_initialize_interval_hours' => 'required|integer|min:1|max:24',
            'orders_timer_interval_minutes' => 'required|integer|min:1|max:60',
            'orders_expired_handler_interval_minutes' => 'required|integer|min:1|max:60',
            'orders_auto_cancel_interval_minutes' => 'required|integer|min:1|max:60',
            'maids_availability_update_time' => 'required|date_format:H:i',
            'log_clear_keep_days' => 'required|integer|min:1|max:365',
            'log_clear_weekday' => 'required|integer|min:0|max:6',
            'log_clear_time' => 'required|date_format:H:i',
            'database_optimize_weekday' => 'required|integer|min:0|max:6',
            'database_optimize_time' => 'required|date_format:H:i',
        ]);
    }

    private function validateSmsSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:sms',
            'sms' => 'required|array',
            'sms.provider' => 'required|in:msegat,taqnyat',
            'sms.gateway_status' => 'required|in:on,off',
            'sms.url' => 'nullable|url|max:255',
            'sms.username' => 'nullable|string|max:255',
            'sms.api_key' => 'nullable|string|max:1000',
            'sms.sender' => 'nullable|string|max:255',
            'sms.advertising_sender' => 'nullable|string|max:255',
            'sms.verification_sender' => 'nullable|string|max:255',
            'sms.app_domain_for_msegat' => 'nullable|string|max:255',
            'sms.default_sender_type' => 'required|in:advertising,verification',
            'sms.encoding' => 'required|string|max:50',
            'sms.bulk_send_limit' => 'required|integer|min:1|max:10000',
            'sms.bulk_confirmation_keyword' => 'required|string|max:50',
            'sms.disable_phone_verification' => 'nullable|boolean',
            'sms.taqnyat' => 'required|array',
            'sms.taqnyat.base_url' => 'nullable|url|max:255',
            'sms.taqnyat.bearer_token' => 'nullable|string|max:5000',
        ], [
            'sms.url.url' => 'رابط مزود الرسائل النصية غير صحيح.',
            'sms.taqnyat.base_url.url' => 'رابط بوابة تقنيات غير صحيح.',
        ]);

        return $validated['sms'];
    }

    private function validateWhatsAppSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:whatsapp',
            'whatsapp' => 'required|array',
            'whatsapp.driver' => 'required|in:meta,ultramsg',
            'whatsapp.default_country_code' => 'required|string|max:10',
            'whatsapp.timeout' => 'required|integer|min:5|max:120',
            'whatsapp.bulk_send_limit' => 'required|integer|min:1|max:10000',
            'whatsapp.bulk_confirmation_keyword' => 'required|string|max:50',
            'whatsapp.default_purpose' => 'required|in:verification,follow_up,advertising',
            'whatsapp.allow_advertising' => 'nullable|boolean',

            'whatsapp.meta' => 'required|array',
            'whatsapp.meta.graph_version' => 'nullable|string|max:20',
            'whatsapp.meta.phone_number_id' => 'nullable|string|max:255',
            'whatsapp.meta.access_token' => 'nullable|string|max:5000',
            'whatsapp.meta.webhook_verify_token' => 'nullable|string|max:255',
            'whatsapp.meta.app_secret' => 'nullable|string|max:1000',

            'whatsapp.ultramsg' => 'required|array',
            'whatsapp.ultramsg.api_url' => 'nullable|url|max:255',
            'whatsapp.ultramsg.default_instance_key' => 'nullable|string|max:100',
            'whatsapp.ultramsg.instances' => 'nullable|array',
            'whatsapp.ultramsg.instances.*.key' => 'nullable|string|max:100',
            'whatsapp.ultramsg.instances.*.name' => 'nullable|string|max:255',
            'whatsapp.ultramsg.instances.*.phone_number' => 'nullable|string|max:30',
            'whatsapp.ultramsg.instances.*.instance_id' => 'nullable|string|max:255',
            'whatsapp.ultramsg.instances.*.token' => 'nullable|string|max:1000',
            'whatsapp.ultramsg.instances.*.enabled' => 'nullable|boolean',
        ], [
            'whatsapp.ultramsg.api_url.url' => 'رابط UltraMsg غير صحيح.',
        ]);

        return $validated['whatsapp'];
    }

    private function successMessage(string $section): string
    {
        return match ($section) {
            'alerts' => 'تم تحديث إعدادات التنبيهات الآلية بنجاح.',
            'email' => 'تم تحديث إعدادات البريد الإلكتروني بنجاح.',
            'sms' => 'تم تحديث إعدادات الرسائل النصية بنجاح.',
            'whatsapp' => 'تم تحديث إعدادات واتساب بنجاح.',
        };
    }

    private function sectionAnchor(string $section): string
    {
        return match ($section) {
            'alerts' => 'alert-rules-settings',
            'email' => 'email-settings',
            'sms' => 'sms-settings',
            'whatsapp' => 'whatsapp-settings',
        };
    }
}
