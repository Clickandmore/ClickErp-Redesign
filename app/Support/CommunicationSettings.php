<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunicationSettings
{
    private const SETTINGS_KEY = 'communication_channels';
    private const PHONE_VERIFICATION_BYPASS_CODE = '12345';

    public static function all(): array
    {
        return self::normalize(self::storedPayload(), false);
    }

    public static function email(): array
    {
        return self::all()['email'] ?? self::defaults()['email'];
    }

    public static function sms(): array
    {
        return self::all()['sms'] ?? self::defaults()['sms'];
    }

    public static function phoneVerificationIsDisabled(): bool
    {
        return (bool) (self::sms()['disable_phone_verification'] ?? false);
    }

    public static function smsGatewayStatus(): string
    {
        return self::smsGatewayIsEnabled() ? 'on' : 'off';
    }

    public static function smsGatewayIsEnabled(): bool
    {
        return (string) (self::sms()['gateway_status'] ?? 'on') !== 'off';
    }

    public static function phoneVerificationBypassCode(): string
    {
        return self::PHONE_VERIFICATION_BYPASS_CODE;
    }

    public static function whatsapp(): array
    {
        return self::all()['whatsapp'] ?? self::defaults()['whatsapp'];
    }

    public const VERIFICATION_TEMPLATE = 'phone_verification_code';

    /** @return array{fallback_to_sms: bool} */
    public static function verification(): array
    {
        return self::all()['verification'] ?? self::defaults()['verification'];
    }

    /** قناة رمز التحقق = قناة قالب phone_verification_code في مكتبة القوالب (sms إن لم يوجد). */
    public static function verificationChannel(): string
    {
        if (!Schema::hasTable('sms_templates')) {
            return 'sms';
        }
        $template = \App\Models\SmsTemplate::getByName(self::VERIFICATION_TEMPLATE);

        return $template && $template->channel === \App\Models\SmsTemplate::CHANNEL_WHATSAPP ? 'whatsapp' : 'sms';
    }

    /** هل توجد قناة جاهزة لإرسال رمز التحقق فعلياً؟ */
    public static function verificationChannelIsReady(): bool
    {
        $smsReady = self::smsIsConfigured();

        if (self::verificationChannel() === 'whatsapp') {
            return app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->isConfigured()
                || (self::verification()['fallback_to_sms'] && $smsReady);
        }

        return $smsReady;
    }

    public static function smsIsConfigured(?array $settings = null): bool
    {
        $settings ??= self::sms();

        if ((string) ($settings['gateway_status'] ?? 'on') === 'off') {
            return false;
        }

        $provider = strtolower((string) ($settings['provider'] ?? 'msegat'));

        if ($provider === 'taqnyat') {
            return self::trimString($settings['sender'] ?? '') !== ''
                && self::trimString(data_get($settings, 'taqnyat.base_url', '')) !== ''
                && self::trimString(data_get($settings, 'taqnyat.bearer_token', '')) !== '';
        }

        return self::trimString($settings['url'] ?? '') !== ''
            && self::trimString($settings['username'] ?? '') !== ''
            && self::trimString($settings['api_key'] ?? '') !== ''
            && self::trimString($settings['sender'] ?? '') !== '';
    }

    public static function persist(array $payload, ?array $validatedSections = null): array
    {
        $normalized = self::normalize($payload, true);
        self::validatePayload($normalized, $validatedSections);

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode(
                self::encryptSecretsForStorage($normalized),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )]
        );

        self::applyRuntimeConfig($normalized);

        return $normalized;
    }

    public static function persistSection(string $section, array $payload): array
    {
        return self::persist([$section => $payload], [$section]);
    }

    public static function applyRuntimeConfig(?array $settings = null): void
    {
        try {
            $settings ??= self::all();

            config([
                'mail.default' => $settings['email']['mailer'],
                'mail.mailers.smtp.host' => $settings['email']['host'],
                'mail.mailers.smtp.port' => (int) $settings['email']['port'],
                'mail.mailers.smtp.encryption' => $settings['email']['encryption'] !== '' ? $settings['email']['encryption'] : null,
                'mail.mailers.smtp.username' => $settings['email']['username'],
                'mail.mailers.smtp.password' => $settings['email']['password'],
                'mail.from.address' => $settings['email']['from_address'] !== '' ? $settings['email']['from_address'] : config('mail.from.address'),
                'mail.from.name' => $settings['email']['from_name'] !== '' ? $settings['email']['from_name'] : config('mail.from.name'),

                'msegat.provider' => $settings['sms']['provider'],
                'msegat.url' => $settings['sms']['url'],
                'msegat.username' => $settings['sms']['username'],
                'msegat.api_key' => $settings['sms']['api_key'],
                'msegat.sender' => $settings['sms']['sender'],
                'msegat.advertising_sender' => $settings['sms']['advertising_sender'],
                'msegat.verification_sender' => $settings['sms']['verification_sender'],
                'msegat.app_domain_name' => $settings['sms']['app_domain_for_msegat'],
                'msegat.app_domain_for_msegat' => $settings['sms']['app_domain_for_msegat'],
                'msegat.default_sender_type' => $settings['sms']['default_sender_type'],
                'msegat.encoding' => $settings['sms']['encoding'],
                'msegat.bulk_send_limit' => (int) $settings['sms']['bulk_send_limit'],
                'msegat.bulk_confirmation_keyword' => $settings['sms']['bulk_confirmation_keyword'],
                'msegat.disable_phone_verification' => (bool) $settings['sms']['disable_phone_verification'],
                'msegat.gateway_status' => $settings['sms']['gateway_status'],
                'services.taqnyat.sms.base_url' => $settings['sms']['taqnyat']['base_url'],
                'services.taqnyat.sms.bearer_token' => $settings['sms']['taqnyat']['bearer_token'],

                'services.whatsapp.driver' => $settings['whatsapp']['driver'],
                'services.whatsapp.chatwoot' => $settings['whatsapp']['chatwoot'],
                'services.whatsapp.clickchat' => $settings['whatsapp']['clickchat'],
                'services.whatsapp.opt_out' => $settings['whatsapp']['opt_out'],
                'services.whatsapp.graph_version' => $settings['whatsapp']['meta']['graph_version'],
                'services.whatsapp.phone_number_id' => $settings['whatsapp']['meta']['phone_number_id'],
                'services.whatsapp.business_account_id' => $settings['whatsapp']['meta']['business_account_id'],
                'services.whatsapp.app_id' => $settings['whatsapp']['meta']['app_id'],
                'services.whatsapp.configuration_id' => $settings['whatsapp']['meta']['configuration_id'],
                'services.whatsapp.access_token' => $settings['whatsapp']['meta']['access_token'],
                'services.whatsapp.webhook_verify_token' => $settings['whatsapp']['meta']['webhook_verify_token'],
                'services.whatsapp.app_secret' => $settings['whatsapp']['meta']['app_secret'],
                'services.whatsapp.default_country_code' => $settings['whatsapp']['default_country_code'],
                'services.whatsapp.timeout' => (int) $settings['whatsapp']['timeout'],
                'services.whatsapp.bulk_send_limit' => (int) $settings['whatsapp']['bulk_send_limit'],
                'services.whatsapp.bulk_confirmation_keyword' => $settings['whatsapp']['bulk_confirmation_keyword'],
                'services.whatsapp.default_purpose' => $settings['whatsapp']['default_purpose'],
                'services.whatsapp.allow_advertising' => (bool) $settings['whatsapp']['allow_advertising'],
                'services.whatsapp.ultramsg.api_url' => $settings['whatsapp']['ultramsg']['api_url'],
                'services.whatsapp.ultramsg.instances' => $settings['whatsapp']['ultramsg']['instances'],
                'services.whatsapp.ultramsg.default_instance_key' => $settings['whatsapp']['ultramsg']['default_instance_key'],
            ]);
        } catch (\Throwable $exception) {
            // Keep boot resilient when the database is unavailable or not migrated yet.
        }
    }

    public static function activeUltraMsgInstances(?array $settings = null): array
    {
        $settings ??= self::all();

        return array_values(array_filter(
            (array) data_get($settings, 'whatsapp.ultramsg.instances', []),
            static fn (array $instance): bool => (bool) ($instance['enabled'] ?? false)
        ));
    }

    public static function findUltraMsgInstance(?string $key = null, ?array $settings = null): ?array
    {
        $instances = self::activeUltraMsgInstances($settings);
        if ($instances === []) {
            return null;
        }

        $defaultKey = (string) data_get($settings ?? self::all(), 'whatsapp.ultramsg.default_instance_key', '');
        $resolvedKey = trim((string) ($key ?: $defaultKey));

        foreach ($instances as $instance) {
            if ((string) ($instance['key'] ?? '') === $resolvedKey) {
                return $instance;
            }
        }

        return $instances[0] ?? null;
    }

    private static function storedPayload(): array
    {
        $defaults = self::defaults();

        if (!Schema::hasTable('new_settings')) {
            return $defaults;
        }

        $raw = NewSettings::query()
            ->where('key', self::SETTINGS_KEY)
            ->value('value');

        if (!is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return $defaults;
        }

        $decoded = self::decryptSecretsFromStorage($decoded);

        return self::mergeStoredSettings($defaults, $decoded);
    }

    private static function normalize(array $payload, bool $preserveSecrets): array
    {
        $defaults = self::defaults();
        $current = $preserveSecrets ? self::all() : $defaults;
        $data = array_replace_recursive($current, $payload);
        $data = array_replace_recursive($defaults, $data);

        $mailers = ['smtp', 'sendmail', 'log'];
        $data['email']['mailer'] = in_array((string) ($data['email']['mailer'] ?? ''), $mailers, true)
            ? (string) $data['email']['mailer']
            : $defaults['email']['mailer'];
        $data['email']['host'] = self::trimString($data['email']['host'] ?? '');
        $data['email']['port'] = max(1, (int) ($data['email']['port'] ?? $defaults['email']['port']));
        $data['email']['encryption'] = in_array((string) ($data['email']['encryption'] ?? ''), ['', 'tls', 'ssl'], true)
            ? (string) $data['email']['encryption']
            : $defaults['email']['encryption'];
        $data['email']['username'] = self::trimString($data['email']['username'] ?? '');
        $data['email']['password'] = self::preserveSecret(
            $data['email']['password'] ?? '',
            $current['email']['password'] ?? '',
            $preserveSecrets
        );
        $data['email']['from_address'] = self::trimString($data['email']['from_address'] ?? '');
        $data['email']['from_name'] = self::trimString($data['email']['from_name'] ?? '');

        $data['sms']['provider'] = in_array((string) ($data['sms']['provider'] ?? ''), ['msegat', 'taqnyat'], true)
            ? (string) $data['sms']['provider']
            : $defaults['sms']['provider'];
        $data['sms']['gateway_status'] = (string) ($data['sms']['gateway_status'] ?? $defaults['sms']['gateway_status']) === 'off'
            ? 'off'
            : 'on';
        $data['sms']['url'] = self::trimString($data['sms']['url'] ?? '');
        $data['sms']['username'] = self::trimString($data['sms']['username'] ?? '');
        $data['sms']['api_key'] = self::preserveSecret(
            $data['sms']['api_key'] ?? '',
            $current['sms']['api_key'] ?? '',
            $preserveSecrets
        );
        $data['sms']['sender'] = self::trimString($data['sms']['sender'] ?? '');
        $data['sms']['advertising_sender'] = self::trimString($data['sms']['advertising_sender'] ?? '');
        $data['sms']['verification_sender'] = self::trimString($data['sms']['verification_sender'] ?? '');
        $legacySmsAppDomain = self::trimString($data['sms']['app_domain_name'] ?? $current['sms']['app_domain_name'] ?? '');
        $data['sms']['app_domain_for_msegat'] = self::trimString($data['sms']['app_domain_for_msegat'] ?? '')
            ?: ($legacySmsAppDomain !== '' ? $legacySmsAppDomain : $defaults['sms']['app_domain_for_msegat']);
        unset($data['sms']['app_domain_name']);
        $data['sms']['default_sender_type'] = in_array((string) ($data['sms']['default_sender_type'] ?? ''), ['advertising', 'verification'], true)
            ? (string) $data['sms']['default_sender_type']
            : $defaults['sms']['default_sender_type'];
        $data['sms']['encoding'] = self::trimString($data['sms']['encoding'] ?? '') ?: $defaults['sms']['encoding'];
        $data['sms']['bulk_send_limit'] = max(1, (int) ($data['sms']['bulk_send_limit'] ?? $defaults['sms']['bulk_send_limit']));
        $data['sms']['bulk_confirmation_keyword'] = self::trimString($data['sms']['bulk_confirmation_keyword'] ?? '')
            ?: $defaults['sms']['bulk_confirmation_keyword'];
        $data['sms']['disable_phone_verification'] = filter_var(
            $data['sms']['disable_phone_verification'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $data['sms']['external_order_admin_notification']['enabled'] = filter_var(
            data_get($data, 'sms.external_order_admin_notification.enabled', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $recipientMode = (string) data_get($data, 'sms.external_order_admin_notification.recipient_mode', 'selected_marketer');
        $data['sms']['external_order_admin_notification']['recipient_mode'] = in_array($recipientMode, ['selected_marketer', 'unified'], true)
            ? $recipientMode
            : 'selected_marketer';
        $data['sms']['external_order_admin_notification']['unified_phone'] = self::digitsOnly(
            data_get($data, 'sms.external_order_admin_notification.unified_phone', '')
        );
        $data['sms']['taqnyat']['base_url'] = rtrim(
            self::trimString($data['sms']['taqnyat']['base_url'] ?? $defaults['sms']['taqnyat']['base_url'])
                ?: $defaults['sms']['taqnyat']['base_url'],
            '/'
        );
        $data['sms']['taqnyat']['bearer_token'] = self::preserveSecret(
            $data['sms']['taqnyat']['bearer_token'] ?? '',
            $current['sms']['taqnyat']['bearer_token'] ?? '',
            $preserveSecrets
        );

        $data['whatsapp']['driver'] = in_array((string) ($data['whatsapp']['driver'] ?? ''), ['meta', 'ultramsg', 'chatwoot', 'clickchat'], true)
            ? (string) $data['whatsapp']['driver']
            : $defaults['whatsapp']['driver'];
        $data['whatsapp']['default_country_code'] = self::digitsOnly($data['whatsapp']['default_country_code'] ?? $defaults['whatsapp']['default_country_code']);
        $data['whatsapp']['timeout'] = max(5, (int) ($data['whatsapp']['timeout'] ?? $defaults['whatsapp']['timeout']));
        $data['whatsapp']['bulk_send_limit'] = max(1, (int) ($data['whatsapp']['bulk_send_limit'] ?? $defaults['whatsapp']['bulk_send_limit']));
        $data['whatsapp']['bulk_confirmation_keyword'] = self::trimString($data['whatsapp']['bulk_confirmation_keyword'] ?? '')
            ?: $defaults['whatsapp']['bulk_confirmation_keyword'];
        $data['whatsapp']['allow_advertising'] = filter_var(
            $data['whatsapp']['allow_advertising'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $data['whatsapp']['default_purpose'] = in_array((string) ($data['whatsapp']['default_purpose'] ?? ''), ['verification', 'follow_up', 'advertising'], true)
            ? (string) $data['whatsapp']['default_purpose']
            : $defaults['whatsapp']['default_purpose'];
        if (!$data['whatsapp']['allow_advertising'] && $data['whatsapp']['default_purpose'] === 'advertising') {
            $data['whatsapp']['default_purpose'] = 'verification';
        }

        foreach (['base_url', 'account_id', 'inbox_id'] as $key) {
            $data['whatsapp']['chatwoot'][$key] = self::trimString($data['whatsapp']['chatwoot'][$key] ?? '');
        }
        $data['whatsapp']['chatwoot']['base_url'] = rtrim($data['whatsapp']['chatwoot']['base_url'], '/');
        $data['whatsapp']['chatwoot']['access_token'] = self::preserveSecret(
            $data['whatsapp']['chatwoot']['access_token'] ?? '',
            $current['whatsapp']['chatwoot']['access_token'] ?? '', $preserveSecrets
        );
        $data['whatsapp']['clickchat']['base_url'] = rtrim(self::trimString($data['whatsapp']['clickchat']['base_url'] ?? ''), '/');
        $data['whatsapp']['clickchat']['default_channel_account_id'] = self::trimString(
            $data['whatsapp']['clickchat']['default_channel_account_id'] ?? ''
        );
        foreach (['api_token', 'webhook_secret'] as $secretKey) {
            $data['whatsapp']['clickchat'][$secretKey] = self::preserveSecret(
                $data['whatsapp']['clickchat'][$secretKey] ?? '',
                $current['whatsapp']['clickchat'][$secretKey] ?? '', $preserveSecrets
            );
        }
        $data['whatsapp']['clickchat']['fallback'] = in_array((string) ($data['whatsapp']['clickchat']['fallback'] ?? ''), ['none', 'ultramsg', 'sms'], true)
            ? (string) $data['whatsapp']['clickchat']['fallback'] : 'none';
        $data['whatsapp']['clickchat']['auto_retry'] = filter_var($data['whatsapp']['clickchat']['auto_retry'] ?? true, FILTER_VALIDATE_BOOLEAN);
        // Lists are taken as sent: a recursive merge would keep removed entries.
        $sentClickChat = (array) data_get($payload, 'whatsapp.clickchat', []);
        $adminIds = array_key_exists('alert_admin_ids', $sentClickChat)
            ? (array) $sentClickChat['alert_admin_ids'] : (array) ($current['whatsapp']['clickchat']['alert_admin_ids'] ?? []);
        $data['whatsapp']['clickchat']['alert_admin_ids'] = array_values(array_unique(array_filter(array_map('intval', $adminIds))));
        $branchSenders = array_key_exists('branch_senders', $sentClickChat)
            ? (array) $sentClickChat['branch_senders'] : (array) ($current['whatsapp']['clickchat']['branch_senders'] ?? []);
        $data['whatsapp']['clickchat']['branch_senders'] = collect($branchSenders)
            ->mapWithKeys(fn ($channel, $branch) => [(string) (int) $branch => (string) (int) $channel])
            ->filter(fn ($channel, $branch) => (int) $branch > 0 && (int) $channel > 0)
            ->all();
        $data['whatsapp']['opt_out']['enabled'] = filter_var($data['whatsapp']['opt_out']['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        foreach (['stop_keywords', 'start_keywords'] as $keywordsKey) {
            $data['whatsapp']['opt_out'][$keywordsKey] = self::trimString($data['whatsapp']['opt_out'][$keywordsKey] ?? '')
                ?: $defaults['whatsapp']['opt_out'][$keywordsKey];
        }
        $data['whatsapp']['meta']['graph_version'] = self::trimString($data['whatsapp']['meta']['graph_version'] ?? '')
            ?: $defaults['whatsapp']['meta']['graph_version'];
        $data['whatsapp']['meta']['phone_number_id'] = self::trimString($data['whatsapp']['meta']['phone_number_id'] ?? '');
        $data['whatsapp']['meta']['business_account_id'] = self::trimString($data['whatsapp']['meta']['business_account_id'] ?? '');
        $data['whatsapp']['meta']['app_id'] = self::trimString($data['whatsapp']['meta']['app_id'] ?? '');
        $data['whatsapp']['meta']['configuration_id'] = self::trimString($data['whatsapp']['meta']['configuration_id'] ?? '');
        $data['whatsapp']['meta']['access_token'] = self::preserveSecret(
            $data['whatsapp']['meta']['access_token'] ?? '',
            $current['whatsapp']['meta']['access_token'] ?? '',
            $preserveSecrets
        );
        $data['whatsapp']['meta']['webhook_verify_token'] = self::preserveSecret(
            $data['whatsapp']['meta']['webhook_verify_token'] ?? '',
            $current['whatsapp']['meta']['webhook_verify_token'] ?? '',
            $preserveSecrets
        );
        $data['whatsapp']['meta']['app_secret'] = self::preserveSecret(
            $data['whatsapp']['meta']['app_secret'] ?? '',
            $current['whatsapp']['meta']['app_secret'] ?? '',
            $preserveSecrets
        );

        $data['whatsapp']['ultramsg']['api_url'] = rtrim(
            self::trimString($data['whatsapp']['ultramsg']['api_url'] ?? $defaults['whatsapp']['ultramsg']['api_url'])
                ?: $defaults['whatsapp']['ultramsg']['api_url'],
            '/'
        );

        $currentInstances = collect($current['whatsapp']['ultramsg']['instances'] ?? [])
            ->keyBy('key');
        $instances = [];
        foreach ((array) ($data['whatsapp']['ultramsg']['instances'] ?? []) as $instance) {
            $key = self::trimString($instance['key'] ?? '');
            $instanceId = self::trimString($instance['instance_id'] ?? '');
            $name = self::trimString($instance['name'] ?? '');
            $phoneNumber = self::trimString($instance['phone_number'] ?? '');
            $enabled = filter_var($instance['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($key === '') {
                $seed = $instanceId !== '' ? Str::slug($instanceId, '_') : Str::random(8);
                $key = 'ultramsg_' . $seed;
            }

            $currentInstance = $currentInstances->get($key, []);
            $token = self::preserveSecret(
                $instance['token'] ?? '',
                $currentInstance['token'] ?? '',
                $preserveSecrets
            );

            if ($name === '' && $phoneNumber === '' && $instanceId === '' && $token === '') {
                continue;
            }

            $uniqueKey = self::uniqueInstanceKey($key, $instances);

            $instances[] = [
                'key' => $uniqueKey,
                'name' => $name !== '' ? $name : ($phoneNumber !== '' ? $phoneNumber : ($instanceId !== '' ? $instanceId : 'UltraMsg')),
                'phone_number' => $phoneNumber,
                'instance_id' => $instanceId,
                'token' => $token,
                'enabled' => $enabled,
            ];
        }

        $defaultKey = self::trimString($data['whatsapp']['ultramsg']['default_instance_key'] ?? '');
        $activeKeys = collect($instances)
            ->filter(static fn (array $instance): bool => (bool) $instance['enabled'])
            ->pluck('key')
            ->values()
            ->all();

        if ($defaultKey === '' || !in_array($defaultKey, $activeKeys, true)) {
            $defaultKey = $activeKeys[0] ?? '';
        }

        $data['whatsapp']['ultramsg']['instances'] = $instances;
        $data['whatsapp']['ultramsg']['default_instance_key'] = $defaultKey;

        unset($data['verification']['channel']);
        $data['verification']['fallback_to_sms'] = filter_var(
            data_get($data, 'verification.fallback_to_sms', true),
            FILTER_VALIDATE_BOOLEAN
        );

        return $data;
    }

    private static function validatePayload(array $payload, ?array $validatedSections = null): void
    {
        $errors = [];
        $validatedSections ??= ['email', 'sms', 'whatsapp'];

        if (in_array('email', $validatedSections, true) && $payload['email']['mailer'] === 'smtp') {
            if ($payload['email']['host'] === '') {
                $errors['email.host'] = 'عنوان خادم البريد مطلوب عند استخدام SMTP.';
            }
            if ($payload['email']['from_address'] === '') {
                $errors['email.from_address'] = 'بريد المرسل مطلوب عند استخدام SMTP.';
            }
        }

        if (in_array('sms', $validatedSections, true) && $payload['sms']['provider'] === 'taqnyat') {
            if ($payload['sms']['sender'] === '') {
                $errors['sms.sender'] = 'اسم المرسل مطلوب عند استخدام بوابة تقنيات.';
            }
            if ($payload['sms']['taqnyat']['base_url'] === '') {
                $errors['sms.taqnyat.base_url'] = 'رابط API لبوابة تقنيات مطلوب.';
            }
            if ($payload['sms']['taqnyat']['bearer_token'] === '') {
                $errors['sms.taqnyat.bearer_token'] = 'Bearer Token لبوابة تقنيات مطلوب.';
            }
        }

        if (in_array('whatsapp', $validatedSections, true) && $payload['whatsapp']['driver'] === 'chatwoot') {
            $cw = $payload['whatsapp']['chatwoot'];
            $url = parse_url($cw['base_url']);
            if (($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
                $errors['whatsapp.chatwoot.base_url'] = 'أدخل عنوان HTTPS صالحًا للمنصة.';
            }
            if (!ctype_digit($cw['account_id']) || (int) $cw['account_id'] <= 0) {
                $errors['whatsapp.chatwoot.account_id'] = 'معرف الحساب مطلوب ويجب أن يكون رقمًا موجبًا.';
            }
            if ($cw['inbox_id'] !== '' && (!ctype_digit($cw['inbox_id']) || (int) $cw['inbox_id'] <= 0)) {
                $errors['whatsapp.chatwoot.inbox_id'] = 'معرف صندوق الوارد يجب أن يكون رقمًا موجبًا.';
            }
            if ($cw['access_token'] === '') {
                $errors['whatsapp.chatwoot.access_token'] = 'رمز الوصول مطلوب.';
            }
        }
        if (in_array('whatsapp', $validatedSections, true) && $payload['whatsapp']['driver'] === 'clickchat') {
            $cc = $payload['whatsapp']['clickchat'];
            $url = parse_url($cc['base_url']);
            // Plain HTTP is accepted only for a local development platform.
            $local = in_array($url['host'] ?? '', ['localhost', '127.0.0.1'], true);
            if (!in_array($url['scheme'] ?? '', $local ? ['http', 'https'] : ['https'], true) || empty($url['host'])
                || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
                $errors['whatsapp.clickchat.base_url'] = 'أدخل عنوان HTTPS صالحًا لمنصة Click Live Chat.';
            }
            if ($cc['api_token'] === '') {
                $errors['whatsapp.clickchat.api_token'] = 'مفتاح API مطلوب.';
            }
            if ($cc['webhook_secret'] !== '' && !str_starts_with($cc['webhook_secret'], 'whsec_')) {
                $errors['whatsapp.clickchat.webhook_secret'] = 'المفتاح السري للـ Webhook يبدأ بـ whsec_.';
            }
            if ($cc['default_channel_account_id'] !== ''
                && (!ctype_digit($cc['default_channel_account_id']) || (int) $cc['default_channel_account_id'] <= 0)) {
                $errors['whatsapp.clickchat.default_channel_account_id'] = 'الرقم المرسِل يجب أن يكون رقمًا موجبًا.';
            }
        }
        if (in_array('whatsapp', $validatedSections, true) && $payload['whatsapp']['driver'] === 'meta') {
            $embeddedSignupReady = $payload['whatsapp']['meta']['app_id'] !== ''
                && $payload['whatsapp']['meta']['configuration_id'] !== ''
                && $payload['whatsapp']['meta']['app_secret'] !== '';

            if ($payload['whatsapp']['meta']['phone_number_id'] === '' && !$embeddedSignupReady) {
                $errors['whatsapp.meta.phone_number_id'] = 'رقم تعريف الهاتف في Meta مطلوب.';
            }
            if ($payload['whatsapp']['meta']['access_token'] === '' && !$embeddedSignupReady) {
                $errors['whatsapp.meta.access_token'] = 'رمز الوصول لـ Meta مطلوب.';
            }
            if ($payload['whatsapp']['meta']['webhook_verify_token'] === '') {
                $errors['whatsapp.meta.webhook_verify_token'] = 'Webhook Verify Token مطلوب لاستقبال الرسائل.';
            }
            if ($payload['whatsapp']['meta']['app_secret'] === '') {
                $errors['whatsapp.meta.app_secret'] = 'App Secret مطلوب للتحقق من أمان الرسائل الواردة.';
            }
        }

        if (in_array('whatsapp', $validatedSections, true) && $payload['whatsapp']['driver'] === 'ultramsg') {
            $enabledInstances = collect($payload['whatsapp']['ultramsg']['instances'])
                ->filter(static fn (array $instance): bool => (bool) $instance['enabled'])
                ->values();

            if ($enabledInstances->isEmpty()) {
                $errors['whatsapp.ultramsg.instances'] = 'أضف رقم واتساب واحدًا مفعّلًا على الأقل في UltraMsg.';
            }

            foreach ($enabledInstances as $index => $instance) {
                if ((string) ($instance['instance_id'] ?? '') === '') {
                    $errors["whatsapp.ultramsg.instances.{$index}.instance_id"] = 'رقم الـ Instance مطلوب للحسابات المفعلة.';
                }
                if ((string) ($instance['token'] ?? '') === '') {
                    $errors["whatsapp.ultramsg.instances.{$index}.token"] = 'رمز الـ Token مطلوب للحسابات المفعلة.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private static function defaults(): array
    {
        return [
            'email' => [
                'mailer' => (string) config('mail.default', 'smtp'),
                'host' => (string) config('mail.mailers.smtp.host', ''),
                'port' => (int) config('mail.mailers.smtp.port', 587),
                'encryption' => (string) (config('mail.mailers.smtp.encryption') ?? 'tls'),
                'username' => (string) config('mail.mailers.smtp.username', ''),
                'password' => (string) config('mail.mailers.smtp.password', ''),
                'from_address' => (string) config('mail.from.address', ''),
                'from_name' => (string) config('mail.from.name', ''),
            ],
            'sms' => [
                'provider' => 'msegat',
                'url' => 'https://www.msegat.com/gw/sendsms.php',
                'username' => '',
                'api_key' => '',
                'sender' => '',
                'advertising_sender' => '',
                'verification_sender' => '',
                'app_domain_for_msegat' => 'aqro.sa',
                'default_sender_type' => 'advertising',
                'encoding' => 'UTF8',
                'bulk_send_limit' => 200,
                'bulk_confirmation_keyword' => 'SEND_ALL_SMS',
                'disable_phone_verification' => false,
                'gateway_status' => 'on',
                'external_order_admin_notification' => [
                    'enabled' => false,
                    'recipient_mode' => 'selected_marketer',
                    'unified_phone' => '',
                ],
                'taqnyat' => [
                    'base_url' => 'https://api.taqnyat.sa',
                    'bearer_token' => '',
                ],
            ],
            'whatsapp' => [
                'driver' => (string) config('services.whatsapp.driver', 'meta'),
                'default_country_code' => (string) config('services.whatsapp.default_country_code', '966'),
                'timeout' => (int) config('services.whatsapp.timeout', 15),
                'bulk_send_limit' => (int) config('services.whatsapp.bulk_send_limit', 200),
                'bulk_confirmation_keyword' => (string) config('services.whatsapp.bulk_confirmation_keyword', 'SEND_ALL_WHATSAPP'),
                'default_purpose' => (string) config('services.whatsapp.default_purpose', 'verification'),
                'allow_advertising' => (bool) config('services.whatsapp.allow_advertising', false),
                'chatwoot' => [
                    'base_url' => (string) config('services.whatsapp.chatwoot.base_url', 'https://chat.widers.net'),
                    'account_id' => (string) config('services.whatsapp.chatwoot.account_id', ''),
                    'inbox_id' => (string) config('services.whatsapp.chatwoot.inbox_id', ''),
                    'access_token' => (string) config('services.whatsapp.chatwoot.access_token', ''),
                ],
                'clickchat' => [
                    'base_url' => (string) config('services.whatsapp.clickchat.base_url', ''),
                    'api_token' => (string) config('services.whatsapp.clickchat.api_token', ''),
                    'webhook_secret' => (string) config('services.whatsapp.clickchat.webhook_secret', ''),
                    'default_channel_account_id' => (string) config('services.whatsapp.clickchat.default_channel_account_id', ''),
                    'branch_senders' => [],
                    'fallback' => 'none',
                    'auto_retry' => true,
                    'alert_admin_ids' => [],
                ],
                'opt_out' => [
                    'enabled' => false,
                    // Whole-message matches only, so "إلغاء الطلب" is never read as a stop request.
                    'stop_keywords' => 'إيقاف, ايقاف, الغاء الاشتراك, إلغاء الاشتراك, stop, unsubscribe',
                    'start_keywords' => 'اشتراك, تفعيل, start',
                ],
                'meta' => [
                    'graph_version' => (string) config('services.whatsapp.graph_version', 'v23.0'),
                    'phone_number_id' => (string) config('services.whatsapp.phone_number_id', ''),
                    'business_account_id' => (string) config('services.whatsapp.business_account_id', ''),
                    'app_id' => (string) config('services.whatsapp.app_id', ''),
                    'configuration_id' => (string) config('services.whatsapp.configuration_id', ''),
                    'access_token' => (string) config('services.whatsapp.access_token', ''),
                    'webhook_verify_token' => (string) config('services.whatsapp.webhook_verify_token', ''),
                    'app_secret' => (string) config('services.whatsapp.app_secret', ''),
                ],
                'ultramsg' => [
                    'api_url' => (string) config('services.whatsapp.ultramsg.api_url', 'https://api.ultramsg.com'),
                    'default_instance_key' => (string) config('services.whatsapp.ultramsg.default_instance_key', ''),
                    'instances' => array_values((array) config('services.whatsapp.ultramsg.instances', [])),
                ],
            ],
            // القناة نفسها هي قناة قالب phone_verification_code؛ هنا فقط سلوك الاحتياط.
            'verification' => [
                'fallback_to_sms' => true,
            ],
        ];
    }

    private static function preserveSecret($newValue, string $currentValue, bool $preserveSecrets): string
    {
        $trimmed = self::trimString($newValue);

        if ($trimmed !== '') {
            return $trimmed;
        }

        return $preserveSecrets ? $currentValue : $trimmed;
    }

    private static function trimString($value): string
    {
        return trim((string) $value);
    }

    private static function digitsOnly($value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : '966';
    }

    private static function uniqueInstanceKey(string $key, array $instances): string
    {
        $existingKeys = array_map(static fn (array $instance): string => (string) ($instance['key'] ?? ''), $instances);
        if (!in_array($key, $existingKeys, true)) {
            return $key;
        }

        $counter = 2;
        $candidate = $key . '_' . $counter;
        while (in_array($candidate, $existingKeys, true)) {
            $counter++;
            $candidate = $key . '_' . $counter;
        }

        return $candidate;
    }

    private static function mergeStoredSettings(array $defaults, array $stored): array
    {
        $merged = $defaults;

        foreach ($stored as $key => $value) {
            if (is_array($value)) {
                $defaultValue = $defaults[$key] ?? [];
                $merged[$key] = self::mergeStoredSettings(is_array($defaultValue) ? $defaultValue : [], $value);
                continue;
            }

            if (self::isBlankStoredValue($value) && array_key_exists($key, $defaults)) {
                $merged[$key] = $defaults[$key];
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private static function isBlankStoredValue($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private static function encryptSecretsForStorage(array $settings): array
    {
        foreach ([
            'email.password',
            'sms.api_key',
            'sms.taqnyat.bearer_token',
            'whatsapp.chatwoot.access_token',
            'whatsapp.clickchat.api_token',
            'whatsapp.clickchat.webhook_secret',
            'whatsapp.meta.access_token',
            'whatsapp.meta.webhook_verify_token',
            'whatsapp.meta.app_secret',
        ] as $path) {
            $value = trim((string) data_get($settings, $path, ''));
            if ($value !== '' && !str_starts_with($value, 'encrypted:')) {
                data_set($settings, $path, 'encrypted:' . Crypt::encryptString($value));
            }
        }

        foreach ((array) data_get($settings, 'whatsapp.ultramsg.instances', []) as $index => $instance) {
            $value = trim((string) ($instance['token'] ?? ''));
            if ($value !== '' && !str_starts_with($value, 'encrypted:')) {
                data_set(
                    $settings,
                    "whatsapp.ultramsg.instances.{$index}.token",
                    'encrypted:' . Crypt::encryptString($value)
                );
            }
        }

        return $settings;
    }

    private static function decryptSecretsFromStorage(array $settings): array
    {
        foreach ([
            'email.password',
            'sms.api_key',
            'sms.taqnyat.bearer_token',
            'whatsapp.chatwoot.access_token',
            'whatsapp.clickchat.api_token',
            'whatsapp.clickchat.webhook_secret',
            'whatsapp.meta.access_token',
            'whatsapp.meta.webhook_verify_token',
            'whatsapp.meta.app_secret',
        ] as $path) {
            self::decryptSecretAtPath($settings, $path);
        }

        foreach ((array) data_get($settings, 'whatsapp.ultramsg.instances', []) as $index => $instance) {
            self::decryptSecretAtPath($settings, "whatsapp.ultramsg.instances.{$index}.token");
        }

        return $settings;
    }

    private static function decryptSecretAtPath(array &$settings, string $path): void
    {
        $value = (string) data_get($settings, $path, '');
        if (!str_starts_with($value, 'encrypted:')) {
            return;
        }

        try {
            data_set($settings, $path, Crypt::decryptString(substr($value, 10)));
        } catch (\Throwable $exception) {
            data_set($settings, $path, '');
        }
    }
}
