<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Support\CommunicationSettings;
use App\Support\AutomationSettings;
use App\Support\NotificationAlertSettings;
use App\Support\MessagingPermissions;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\MetaWhatsAppOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationSettingsController extends Controller
{
    private const ALLOWED_SECTIONS = ['alerts', 'email', 'sms', 'whatsapp', 'contact', 'external_order_alert', 'verification'];

    /** أقسام تُحفظ من صفحة «إعدادات التنبيهات» وتعود إليها. */
    private const ALERT_PAGE_SECTIONS = ['alerts', 'contact', 'external_order_alert'];

    public function edit()
    {
        if (!MessagingPermissions::allows(MessagingPermissions::INTEGRATIONS)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        return view('admin.ingaz.notifications.settings', [
            'communicationSettings' => CommunicationSettings::all(),
        ]);
    }

    public function editAlerts()
    {
        if (!MessagingPermissions::allows(MessagingPermissions::ALERTS)) {
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
            'alertRuntimeStats' => $this->alertRuntimeStats(),
            'externalOrderAlert' => (array) data_get(CommunicationSettings::sms(), 'external_order_admin_notification', []),
        ]);
    }

    public function update(Request $request)
    {
        $section = (string) $request->input('section');

        if (!in_array($section, self::ALLOWED_SECTIONS, true)) {
            return redirect()
                ->back()
                ->with('error', 'القسم المطلوب غير صحيح.');
        }

        // نفس المسار يحفظ إعدادات الربط وإعدادات التنبيهات؛ لكل منهما صلاحيته.
        if (!@AdminGuardHasAnyPerm(MessagingPermissions::forSettingsSection($section))) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $validatedSectionPayload = match ($section) {
                'alerts' => $this->validateAlertsSection($request),
                'email' => $this->validateEmailSection($request),
                'sms' => $this->validateSmsSection($request),
                'whatsapp' => $this->validateWhatsAppSection($request),
                'contact' => \App\Support\ContactNotificationSettings::validate((array) $request->input('contact', [])),
                'external_order_alert' => $this->validateExternalOrderAlertSection($request),
                'verification' => $this->validateVerificationSection($request),
            };

            if ($section === 'alerts') {
                $validatedCustomAlerts = $this->validateCustomAlertsSection($request);
                $validatedAutomation = $this->validateAutomationSection($request);
                $validatedContact = $request->has('contact')
                    ? \App\Support\ContactNotificationSettings::validate((array) $request->input('contact'))
                    : null;
                $validatedExternalOrderAlert = $request->has('external_order_alert')
                    ? $this->validateExternalOrderAlertSection($request)
                    : null;

                DB::transaction(function () use (
                    $validatedSectionPayload,
                    $validatedCustomAlerts,
                    $validatedAutomation,
                    $validatedContact,
                    $validatedExternalOrderAlert
                ): void {
                    NotificationAlertSettings::persist($validatedSectionPayload, $validatedCustomAlerts);
                    AutomationSettings::persist($validatedAutomation);
                    if ($validatedContact !== null) {
                        \App\Support\ContactNotificationSettings::persist($validatedContact);
                    }
                    if ($validatedExternalOrderAlert !== null) {
                        $this->persistExternalOrderAlert($validatedExternalOrderAlert);
                    }
                });
            } elseif ($section === 'contact') {
                \App\Support\ContactNotificationSettings::persist($validatedSectionPayload);
            } elseif ($section === 'external_order_alert') {
                $this->persistExternalOrderAlert($validatedSectionPayload);
            } elseif ($section === 'verification') {
                $this->persistVerification($validatedSectionPayload);
            } else {
                CommunicationSettings::persistSection($section, $validatedSectionPayload);
            }

            $route = in_array($section, self::ALERT_PAGE_SECTIONS, true) ? 'notifications.settings.alerts' : 'notifications.settings';

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

            $fallbackRoute = in_array($section, self::ALERT_PAGE_SECTIONS, true)
                ? 'notifications.settings.alerts'
                : 'notifications.settings';

            return redirect()
                ->to(route($fallbackRoute) . '#' . $this->sectionAnchor($section))
                ->withInput($request->except('whatsapp.chatwoot.access_token'))
                ->with('error', in_array($section, self::ALERT_PAGE_SECTIONS, true)
                    ? 'تعذر حفظ إعدادات التنبيهات. لم يتم حفظ أي جزء من التغييرات.'
                    : 'تعذر حفظ إعدادات الربط حالياً.');
        }
    }

    public function testWhatsApp(MetaWhatsAppService $metaWhatsAppService)
    {
        if (!MessagingPermissions::allows(MessagingPermissions::INTEGRATIONS)) {
            abort(403, __('admin.no_permissions'));
        }

        if (config('services.whatsapp.driver') === 'clickchat') {
            $result = app(\App\Services\WhatsApp\ClickChatWhatsAppService::class)->connectionStatus();
            $channels = $result['channels'] ?? [];

            return redirect()->to(route('notifications.settings').'#whatsapp-settings')
                ->with(($result['success'] ?? false) ? 'success' : 'error', ($result['success'] ?? false)
                    ? 'تم الاتصال بمنصة Click Live Chat. عدد الأرقام المربوطة: '.count($channels).'. اختر الرقم المرسِل الافتراضي ثم احفظ.'
                    : 'فشل اختبار الاتصال: '.data_get($result, 'response.message', 'تعذر التحقق من الاتصال.'))
                ->with('clickchat_channels', $channels);
        }
        if (config('services.whatsapp.driver') === 'chatwoot') {
            $result = app(\App\Services\WhatsApp\ChatwootWhatsAppService::class)->connectionStatus();
            return redirect()->to(route('notifications.settings').'#whatsapp-settings')
                ->with(($result['success'] ?? false) ? 'success' : 'error',
                    ($result['success'] ?? false) ? 'تم الاتصال بـ Widers. اختر معرف صندوق واتساب من القائمة ثم احفظ.' : data_get($result, 'response.message'))
                ->with('chatwoot_inboxes', $result['inboxes'] ?? []);
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

    public function completeWhatsAppEmbeddedSignup(
        Request $request,
        MetaWhatsAppOnboardingService $onboardingService
    ) {
        if (!MessagingPermissions::allows(MessagingPermissions::INTEGRATIONS)) {
            return response()->json(['message' => __('admin.no_permissions')], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:5000'],
            'waba_id' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'pin' => ['nullable', 'digits:6'],
        ], [
            'pin.digits' => 'رمز تسجيل الرقم يجب أن يتكون من 6 أرقام.',
        ]);

        $result = $onboardingService->complete(
            $validated['code'],
            $validated['waba_id'],
            $validated['phone_number_id'] ?? null,
            $validated['pin'] ?? null
        );

        if (!($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['error'] ?? 'تعذر إكمال الربط مع Meta.',
                'result' => $result,
            ], 422);
        }

        return response()->json([
            'message' => 'اكتمل ربط حساب واتساب مع Meta وحفظ بياناته بنجاح.',
            'result' => $result,
        ]);
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

    private function alertRuntimeStats(): array
    {
        $leadUntil = now()->addMinutes(
            NotificationAlertSettings::leadTimeInMinutes('recruitment_orders')
        );
        $activeOrders = Order::query()
            ->whereIn('status', [OrderEnum::New->value, OrderEnum::UnderProcess->value])
            ->whereNotNull('deadline_at');

        return [
            'recruitment_orders' => [
                'matched' => (clone $activeOrders)
                    ->where('deadline_at', '<=', $leadUntil)
                    ->count(),
                'overdue' => (clone $activeOrders)
                    ->where('deadline_at', '<', now())
                    ->count(),
            ],
        ];
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
            'alerts.rent_contract_expiry.include_renewal_link' => 'nullable|boolean',
            'alerts.rent_contract_expiry.renewal_payment_methods' => 'required_if:alerts.rent_contract_expiry.include_renewal_link,1|array',
            'alerts.rent_contract_expiry.renewal_payment_methods.*' => 'required|distinct|in:' . implode(',', array_keys(NotificationAlertSettings::renewalPaymentMethodOptions())),
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
            'alerts.rent_contract_expiry.renewal_payment_methods.required_if' => 'اختر طريقة دفع واحدة على الأقل قبل تفعيل رابط التمديد.',
        ]);

        $alerts = $validated['alerts'];
        $missingTemplates = collect($alerts)
            ->filter(fn (array $rule): bool => filter_var($rule['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))
            // Rental reminders already have a built-in customer message and response link.
            ->reject(fn (array $rule, string $key): bool => $key === 'rent_contract_expiry')
            ->filter(fn (array $rule): bool => empty($rule['template_id']))
            ->keys();

        if ($missingTemplates->isNotEmpty()) {
            throw ValidationException::withMessages(
                $missingTemplates
                    ->mapWithKeys(fn (string $key): array => [
                        "alerts.{$key}.template_id" => 'يجب اختيار نموذج رسالة لكل تنبيه مفعل.',
                    ])
                    ->all()
            );
        }

        foreach ($alerts as $key => $rule) {
            if (filter_var($rule['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) && empty($rule['channels'])) {
                throw ValidationException::withMessages(["alerts.{$key}.channels" => 'اختر قناة إرسال واحدة على الأقل للتنبيه المفعّل.']);
            }
        }

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

        $customAlerts = $validated['custom_alerts'] ?? [];

        foreach ($customAlerts as $index => $rule) {
            if (
                filter_var($rule['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && !empty($rule['label'])
                && empty($rule['template_id'])
            ) {
                throw ValidationException::withMessages([
                    "custom_alerts.{$index}.template_id" => 'يجب اختيار نموذج رسالة لكل تنبيه مخصص مفعل.',
                ]);
            }
        }

        return $customAlerts;
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

        // تنبيه حجوزات الموقع الخارجي انتقل إلى صفحة التنبيهات؛ لا يُعاد كتابته من هنا.
        unset($validated['sms']['external_order_admin_notification']);

        return $validated['sms'];
    }

    private function validateVerificationSection(Request $request): array
    {
        $validated = $request->validate([
            'verification' => 'required|array',
            'verification.channel' => 'required|in:sms,whatsapp',
            'verification.fallback_to_sms' => 'nullable|boolean',
        ]);

        if (!\App\Models\SmsTemplate::getByName(CommunicationSettings::VERIFICATION_TEMPLATE)) {
            throw ValidationException::withMessages([
                'verification.channel' => 'قالب «phone_verification_code» غير موجود أو غير نشط في «قوالب الرسائل»؛ فعّله أولاً.',
            ]);
        }
        if ($validated['verification']['channel'] === 'whatsapp'
            && !app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->isConfigured()) {
            throw ValidationException::withMessages([
                'verification.channel' => 'مزود واتساب غير مربوط بعد؛ أكمل بيانات الربط في قسم واتساب ثم أعد المحاولة.',
            ]);
        }

        return [
            'channel' => $validated['verification']['channel'],
            'fallback_to_sms' => $request->boolean('verification.fallback_to_sms'),
        ];
    }

    /** القناة تُكتب على قالب رمز التحقق نفسه (مصدر واحد)، والاحتياط في إعدادات الربط. */
    private function persistVerification(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            \App\Models\SmsTemplate::query()
                ->where('name', CommunicationSettings::VERIFICATION_TEMPLATE)
                ->update(['channel' => $payload['channel'] === 'whatsapp'
                    ? \App\Models\SmsTemplate::CHANNEL_WHATSAPP
                    : \App\Models\SmsTemplate::CHANNEL_SMS]);
            CommunicationSettings::persist(['verification' => ['fallback_to_sms' => $payload['fallback_to_sms']]], []);
        });
    }

    /** يبقى التخزين في نفس مفتاح إعدادات SMS الذي يقرؤه ExternalOrderAdminSmsNotifier. */
    private function persistExternalOrderAlert(array $payload): void
    {
        CommunicationSettings::persist(['sms' => ['external_order_admin_notification' => $payload]], []);
    }

    private function validateExternalOrderAlertSection(Request $request): array
    {
        $validateUnifiedPhone = $request->boolean('external_order_alert.enabled')
            && $request->input('external_order_alert.recipient_mode') === 'unified';

        $validated = $request->validate([
            'external_order_alert' => 'required|array',
            'external_order_alert.enabled' => 'nullable|boolean',
            'external_order_alert.recipient_mode' => 'required|in:selected_marketer,unified',
            'external_order_alert.unified_phone' => $validateUnifiedPhone
                ? ['required', 'regex:/^(?:\+?9665|05|5)\d{8}$/']
                : ['nullable', 'string', 'max:20'],
        ], [
            'external_order_alert.unified_phone.required' => 'أدخل الرقم الموحد الذي سيستقبل تنبيهات حجوزات الموقع الخارجي.',
            'external_order_alert.unified_phone.regex' => 'صيغة الرقم الموحد غير صحيحة. استخدم 05XXXXXXXX أو 5XXXXXXXX أو 9665XXXXXXXX أو +9665XXXXXXXX.',
        ]);

        return [
            'enabled' => $request->boolean('external_order_alert.enabled'),
            'recipient_mode' => $validated['external_order_alert']['recipient_mode'],
            'unified_phone' => (string) ($validated['external_order_alert']['unified_phone'] ?? ''),
        ];
    }

    private function validateWhatsAppSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:whatsapp',
            'whatsapp' => 'required|array',
            'whatsapp.driver' => 'required|in:meta,ultramsg,chatwoot,clickchat',
            'whatsapp.default_country_code' => 'required|string|max:10',
            'whatsapp.timeout' => 'required|integer|min:5|max:120',
            'whatsapp.bulk_send_limit' => 'required|integer|min:1|max:10000',
            'whatsapp.bulk_confirmation_keyword' => 'required|string|max:50',
            'whatsapp.default_purpose' => 'required|in:verification,follow_up,advertising',
            'whatsapp.allow_advertising' => 'nullable|boolean',

            'whatsapp.chatwoot' => 'nullable|array',
            'whatsapp.chatwoot.base_url' => 'nullable|url:https|max:500',
            'whatsapp.chatwoot.account_id' => 'nullable|integer|min:1',
            'whatsapp.chatwoot.inbox_id' => 'nullable|integer|min:1',
            'whatsapp.chatwoot.access_token' => 'nullable|string|max:5000',
            'whatsapp.clickchat' => 'nullable|array',
            'whatsapp.clickchat.base_url' => 'nullable|url|max:500',
            'whatsapp.clickchat.api_token' => 'nullable|string|max:1000',
            'whatsapp.clickchat.webhook_secret' => 'nullable|string|max:255',
            'whatsapp.clickchat.default_channel_account_id' => 'nullable|integer|min:1',
            'whatsapp.clickchat.fallback' => 'nullable|in:none,ultramsg,sms',
            'whatsapp.clickchat.auto_retry' => 'nullable|boolean',
            'whatsapp.clickchat.alert_admin_ids' => 'nullable|array|max:50',
            'whatsapp.clickchat.alert_admin_ids.*' => 'nullable|integer|exists:admins,id',
            'whatsapp.clickchat.branch_senders' => 'nullable|array',
            'whatsapp.clickchat.branch_senders.*' => 'nullable|integer|min:1',
            'whatsapp.opt_out' => 'nullable|array',
            'whatsapp.opt_out.enabled' => 'nullable|boolean',
            'whatsapp.opt_out.stop_keywords' => 'nullable|string|max:500',
            'whatsapp.opt_out.start_keywords' => 'nullable|string|max:500',
            'whatsapp.meta' => 'required|array',
            'whatsapp.meta.graph_version' => 'nullable|string|max:20',
            'whatsapp.meta.phone_number_id' => 'nullable|string|max:255',
            'whatsapp.meta.business_account_id' => 'nullable|string|max:255',
            'whatsapp.meta.app_id' => 'nullable|string|max:255',
            'whatsapp.meta.configuration_id' => 'nullable|string|max:255',
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
        if ($section === 'contact') return 'تم تحديث إعدادات إشعارات تواصل معنا بنجاح.';
        if ($section === 'external_order_alert') return 'تم تحديث تنبيه حجوزات الموقع الخارجي بنجاح.';
        if ($section === 'verification') return 'تم تحديث قناة رمز التحقق بنجاح.';
        return match ($section) {
            'alerts' => 'تم تحديث إعدادات التنبيهات الآلية بنجاح.',
            'email' => 'تم تحديث إعدادات البريد الإلكتروني بنجاح.',
            'sms' => 'تم تحديث إعدادات الرسائل النصية بنجاح.',
            'whatsapp' => 'تم تحديث إعدادات واتساب بنجاح.',
        };
    }

    private function sectionAnchor(string $section): string
    {
        if ($section === 'contact') return 'contact-notification-settings';
        if ($section === 'external_order_alert') return 'external-order-alert-settings';
        if ($section === 'verification') return 'verification-settings';
        return match ($section) {
            'alerts' => 'alert-rules-settings',
            'email' => 'email-settings',
            'sms' => 'sms-settings',
            'whatsapp' => 'whatsapp-settings',
        };
    }
}
