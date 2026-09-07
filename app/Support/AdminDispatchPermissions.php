<?php

namespace App\Support;

class AdminDispatchPermissions
{
    public const VIEW = 'admin_notifications_view';
    public const SEND = 'admin_notifications_send';
    public const AUDIENCES = [
        'users' => 'admin_notifications_target_users',
        'roles' => 'admin_notifications_target_roles',
        'all' => 'admin_notifications_target_all',
    ];
    public const CHANNELS = [
        'email' => 'admin_notifications_email',
        'sms' => 'admin_notifications_sms',
        'whatsapp' => 'admin_notifications_whatsapp',
    ];

    public static function definitions(): array
    {
        return [
            self::VIEW => 'عرض صفحة إشعارات الأدمنز وسجل الإرسال الشخصي',
            self::SEND => 'إرسال إشعارات للأدمنز',
            self::AUDIENCES['users'] => 'إشعارات الأدمنز: اختيار مستخدمين',
            self::AUDIENCES['roles'] => 'إشعارات الأدمنز: اختيار أدوار',
            self::AUDIENCES['all'] => 'إشعارات الأدمنز: الإرسال للجميع',
            self::CHANNELS['email'] => 'إشعارات الأدمنز: البريد الإلكتروني',
            self::CHANNELS['sms'] => 'إشعارات الأدمنز: الرسائل النصية',
            self::CHANNELS['whatsapp'] => 'إشعارات الأدمنز: واتساب',
        ];
    }

    public static function allows($admin, string $permission): bool
    {
        return AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [$permission]);
    }
}
