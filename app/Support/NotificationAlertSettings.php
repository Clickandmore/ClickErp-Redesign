<?php

namespace App\Support;

use App\Models\NewSettings;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Schema;

class NotificationAlertSettings
{
    private const SETTINGS_KEY = 'notification_alert_rules';

    private const CHANNELS = ['sms', 'whatsapp', 'notification', 'email'];
    private const RECIPIENTS = ['client', 'admin', 'both', 'assigned_staff'];
    private const UNITS = ['minutes', 'hours', 'days'];

    public static function all(): array
    {
        return self::normalize(self::storedRulesPayload());
    }

    public static function custom(): array
    {
        return self::normalizeCustom(self::storedCustomPayload());
    }

    public static function rule(string $key): array
    {
        return self::all()[$key] ?? [];
    }

    public static function isEnabled(string $key): bool
    {
        return (bool) data_get(self::rule($key), 'enabled', false);
    }

    public static function template(string $key): ?SmsTemplate
    {
        $templateId = (int) data_get(self::rule($key), 'template_id', 0);

        if ($templateId < 1 || !Schema::hasTable('sms_templates')) {
            return null;
        }

        return SmsTemplate::query()
            ->active()
            ->find($templateId);
    }

    public static function leadTimeInMinutes(string $key): int
    {
        $rule = self::rule($key);
        $value = max(0, (int) ($rule['lead_time_value'] ?? 0));

        return match ((string) ($rule['lead_time_unit'] ?? 'minutes')) {
            'days' => $value * 1440,
            'hours' => $value * 60,
            default => $value,
        };
    }

    public static function definitions(): array
    {
        return [
            'recruitment_orders' => [
                'label' => 'طلبات الاستقدام',
                'description' => 'تنبيه قبل انتهاء مهلة الطلب أو عند قرب تجاوز مدة المعالجة.',
                'default_lead_time_value' => 2,
                'default_lead_time_unit' => 'hours',
                'route' => 'orders.index',
                'url' => url('/admin/orders'),
            ],
            'recruitment_contracts_completed' => [
                'label' => 'عقود الاستقدام المكتملة',
                'description' => 'متابعة العقود في مرحلة completed_stage وما بعدها.',
                'default_lead_time_value' => 1,
                'default_lead_time_unit' => 'days',
                'route' => 'RecruitmentContract.index',
                'url' => url('/admin/RecruitmentContract?status=completed_stage'),
            ],
            'recruitment_warranty' => [
                'label' => 'مدة ضمان الاستقدام',
                'description' => 'تنبيه قبل نهاية ضمان عقد الاستقدام.',
                'default_lead_time_value' => 7,
                'default_lead_time_unit' => 'days',
                'route' => 'RecruitmentContract.index',
                'url' => url('/admin/RecruitmentContract?status=completed_stage'),
            ],
            'rent_contract_warranty' => [
                'label' => 'مدة ضمان عقود الإيجار',
                'description' => 'تنبيه قبل نهاية ضمان عقد الإيجار.',
                'default_lead_time_value' => 7,
                'default_lead_time_unit' => 'days',
                'route' => 'RentContract.index',
                'url' => url('/admin/RentContract'),
            ],
            'rent_contract_expiry' => [
                'label' => 'انتهاء عقود الإيجار',
                'description' => 'تنبيه قبل انتهاء عقد الإيجار.',
                'default_lead_time_value' => 14,
                'default_lead_time_unit' => 'days',
                'route' => 'RentContract.index',
                'url' => url('/admin/RentContract'),
            ],
            'recruitment_travel_arrival' => [
                'label' => 'وصول رحلات الاستقدام',
                'description' => 'تنبيه قبل موعد وصول رحلة الاستقدام.',
                'default_lead_time_value' => 24,
                'default_lead_time_unit' => 'hours',
                'route' => 'travel',
                'url' => url('/admin/travel'),
            ],
            'deportation_travel' => [
                'label' => 'رحلات الترحيل',
                'description' => 'تنبيه قبل موعد رحلة الترحيل.',
                'default_lead_time_value' => 24,
                'default_lead_time_unit' => 'hours',
                'route' => 'DeportationTravel.index',
                'url' => url('/admin/DeportationTravel'),
            ],
            'rent_trial_period' => [
                'label' => 'انتهاء فترة التجربة',
                'description' => 'تنبيه قبل انتهاء فترة التجربة للعقود.',
                'default_lead_time_value' => 3,
                'default_lead_time_unit' => 'days',
                'route' => 'RentContract.index',
                'url' => url('/admin/RentContract'),
            ],
        ];
    }

    public static function channelOptions(): array
    {
        return [
            'sms' => 'SMS',
            'whatsapp' => 'واتساب',
            'notification' => 'إشعار داخلي',
            'email' => 'بريد إلكتروني',
        ];
    }

    public static function recipientOptions(): array
    {
        return [
            'client' => 'العميل',
            'admin' => 'الإدارة',
            'both' => 'العميل والإدارة',
            'assigned_staff' => 'الموظف المسؤول',
        ];
    }

    public static function unitOptions(): array
    {
        return [
            'minutes' => 'دقائق',
            'hours' => 'ساعات',
            'days' => 'أيام',
        ];
    }

    public static function persist(array $rules, ?array $customRules = null): array
    {
        $normalized = self::normalize($rules);
        $custom = $customRules === null ? self::custom() : self::normalizeCustom($customRules);

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode([
                'rules' => $normalized,
                'custom' => $custom,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return $normalized;
    }

    public static function templateOptions(): array
    {
        if (!Schema::hasTable('sms_templates')) {
            return [];
        }

        return SmsTemplate::query()
            ->active()
            ->ordered()
            ->get(['id', 'title', 'channel'])
            ->mapWithKeys(fn (SmsTemplate $template): array => [
                $template->id => $template->title . ' - ' . $template->channel_label,
            ])
            ->all();
    }

    private static function storedPayload(): array
    {
        if (!Schema::hasTable('new_settings')) {
            return [];
        }

        $raw = NewSettings::query()
            ->where('key', self::SETTINGS_KEY)
            ->value('value');

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function storedRulesPayload(): array
    {
        $payload = self::storedPayload();

        if (isset($payload['rules']) && is_array($payload['rules'])) {
            return $payload['rules'];
        }

        return $payload;
    }

    private static function storedCustomPayload(): array
    {
        $payload = self::storedPayload();

        return isset($payload['custom']) && is_array($payload['custom'])
            ? $payload['custom']
            : [];
    }

    private static function normalize(array $payload): array
    {
        $normalized = [];

        foreach (self::definitions() as $key => $definition) {
            $rule = (array) ($payload[$key] ?? []);
            $channels = array_values(array_intersect(
                self::CHANNELS,
                array_map('strval', (array) ($rule['channels'] ?? ['notification']))
            ));

            if ($channels === []) {
                $channels = ['notification'];
            }

            $leadTimeUnit = (string) ($rule['lead_time_unit'] ?? $definition['default_lead_time_unit']);
            if (!in_array($leadTimeUnit, self::UNITS, true)) {
                $leadTimeUnit = $definition['default_lead_time_unit'];
            }

            $repeatEveryUnit = (string) ($rule['repeat_every_unit'] ?? 'days');
            if (!in_array($repeatEveryUnit, self::UNITS, true)) {
                $repeatEveryUnit = 'days';
            }

            $recipient = (string) ($rule['recipient'] ?? 'admin');
            if (!in_array($recipient, self::RECIPIENTS, true)) {
                $recipient = 'admin';
            }

            $templateId = (int) ($rule['template_id'] ?? 0);

            $normalized[$key] = [
                'enabled' => filter_var($rule['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'channels' => $channels,
                'recipient' => $recipient,
                'lead_time_value' => max(0, min(3650, (int) ($rule['lead_time_value'] ?? $definition['default_lead_time_value']))),
                'lead_time_unit' => $leadTimeUnit,
                'repeat_every_value' => max(0, min(3650, (int) ($rule['repeat_every_value'] ?? 0))),
                'repeat_every_unit' => $repeatEveryUnit,
                'template_id' => $templateId > 0 ? $templateId : null,
            ];
        }

        return $normalized;
    }

    private static function normalizeCustom(array $payload): array
    {
        $normalized = [];

        foreach (array_values($payload) as $index => $rule) {
            $rule = (array) $rule;
            $label = trim((string) ($rule['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $channels = array_values(array_intersect(
                self::CHANNELS,
                array_map('strval', (array) ($rule['channels'] ?? ['notification']))
            ));

            if ($channels === []) {
                $channels = ['notification'];
            }

            $leadTimeUnit = (string) ($rule['lead_time_unit'] ?? 'days');
            if (!in_array($leadTimeUnit, self::UNITS, true)) {
                $leadTimeUnit = 'days';
            }

            $repeatEveryUnit = (string) ($rule['repeat_every_unit'] ?? 'days');
            if (!in_array($repeatEveryUnit, self::UNITS, true)) {
                $repeatEveryUnit = 'days';
            }

            $recipient = (string) ($rule['recipient'] ?? 'admin');
            if (!in_array($recipient, self::RECIPIENTS, true)) {
                $recipient = 'admin';
            }

            $templateId = (int) ($rule['template_id'] ?? 0);
            $eventKey = trim((string) ($rule['event_key'] ?? ''));
            $sourceUrl = trim((string) ($rule['source_url'] ?? ''));

            $normalized[] = [
                'key' => trim((string) ($rule['key'] ?? 'custom_' . ($index + 1))),
                'label' => $label,
                'event_key' => $eventKey,
                'description' => trim((string) ($rule['description'] ?? '')),
                'source_url' => $sourceUrl,
                'enabled' => filter_var($rule['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'channels' => $channels,
                'recipient' => $recipient,
                'lead_time_value' => max(0, min(3650, (int) ($rule['lead_time_value'] ?? 1))),
                'lead_time_unit' => $leadTimeUnit,
                'repeat_every_value' => max(0, min(3650, (int) ($rule['repeat_every_value'] ?? 0))),
                'repeat_every_unit' => $repeatEveryUnit,
                'template_id' => $templateId > 0 ? $templateId : null,
            ];
        }

        return $normalized;
    }
}
