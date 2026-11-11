<?php

namespace App\Support;

use App\PermKeys;

/**
 * من يصل إلى أي جزء من وحدة «الرسائل والتواصل».
 *
 * الصلاحية القديمة notifications_control ما زالت تفتح كل الأقسام التشغيلية حتى لا يفقد
 * أي دور عمله اليومي، لكنها لا تفتح «إعدادات الربط» (مفاتيح المزودين وكلمات المرور):
 * تلك تحتاج messaging_integrations_manage وحدها.
 */
class MessagingPermissions
{
    public const SEND = 'send';
    public const LOG = 'log';
    public const TEMPLATES = 'templates';
    public const ALERTS = 'alerts';
    public const INTEGRATIONS = 'integrations';

    /** أقسام نموذج الإعدادات التي تحفظ بيانات المزودين. */
    public const INTEGRATION_SECTIONS = ['email', 'sms', 'whatsapp', 'verification'];

    /** @return array<int, string> */
    public static function for(string $area): array
    {
        return match ($area) {
            self::SEND => [PermKeys::MESSAGING_SEND, PermKeys::NOTIFICATIONS_CONTROL],
            self::LOG => [PermKeys::MESSAGING_LOG_VIEW, PermKeys::NOTIFICATIONS_CONTROL],
            self::TEMPLATES => [PermKeys::MESSAGING_TEMPLATES_MANAGE, PermKeys::NOTIFICATIONS_CONTROL, PermKeys::NOTIFICATIONS_VIEW],
            self::ALERTS => [PermKeys::MESSAGING_ALERTS_MANAGE, PermKeys::NOTIFICATIONS_CONTROL],
            self::INTEGRATIONS => [PermKeys::MESSAGING_INTEGRATIONS_MANAGE],
        };
    }

    public static function allows(string $area): bool
    {
        return (bool) @AdminGuardHasAnyPerm(self::for($area));
    }

    /** صلاحية حفظ قسم معين من نموذج notifications.settings.update. */
    public static function forSettingsSection(?string $section): array
    {
        return in_array($section, self::INTEGRATION_SECTIONS, true)
            ? self::for(self::INTEGRATIONS)
            : self::for(self::ALERTS);
    }

    /** هل يرى الموظف أي رابط داخل قائمة الرسائل؟ */
    public static function allowsAny(): bool
    {
        return (bool) @AdminGuardHasAnyPerm(array_values(array_unique(array_merge(
            self::for(self::SEND),
            self::for(self::LOG),
            self::for(self::TEMPLATES),
            self::for(self::ALERTS),
            self::for(self::INTEGRATIONS),
        ))));
    }
}
