<?php

namespace App\Support;

/** Page order, Arabic names and action labels shared by the permissions screen. */
final class HourlyPermissionCatalog
{
    public const PAGES = [
        'hourly_orders' => 'لوحة الطلبات',
        'hourly_renewals' => 'التجديدات',
        'hourly_visits' => 'قائمة الزيارات وتفاصيل الزيارة',
        'hourly_unassigned_visits' => 'الزيارات غير المخصصة',
        'hourly_past_visits' => 'الزيارات الماضية غير المنتهية',
        'hourly_latest_visits' => 'آخر الزيارات للطلب',
        'hourly_schedule' => 'جدول الموظفين',
        'hourly_sos' => 'نداءات النجدة',
        'hourly_routing' => 'المسارات وتوزيع الزيارات',
        'hourly_gps' => 'التتبع المباشر',
        'hourly_cities' => 'المدن',
        'hourly_districts' => 'المناطق',
        'hourly_teams' => 'الفرق',
        'hourly_team_map' => 'خريطة الفرق',
        'hourly_drivers' => 'السائقون',
        'hourly_maids' => 'عاملات الساعات',
        'hourly_service_categories' => 'تصنيفات الخدمات',
        'hourly_category_districts' => 'مناطق تقديم الخدمة',
        'hourly_category_closures' => 'إغلاقات الخدمة',
        'hourly_category_settings' => 'إعدادات الخدمة',
        'hourly_category_payments' => 'طرق دفع الخدمة',
        'hourly_category_notes' => 'ملاحظات الخدمة',
        'hourly_attributes' => 'خصائص الخدمة',
        'hourly_service_defaults' => 'الإعدادات الافتراضية للخدمات',
        'hourly_service_items' => 'باقات الخدمات وأسعارها',
        'hourly_packages' => 'إدارة الباقات',
        'hourly_package_tiers' => 'أنماط تكرار الباقات',
        'hourly_customers' => 'عملاء الساعات',
        'hourly_customer_groups' => 'مجموعات العملاء',
        'hourly_customer_duplicates' => 'العملاء المكررون ودمجهم',
    ];

    public const ACTIONS = [
        'view' => 'عرض', 'create' => 'إضافة', 'edit' => 'تعديل', 'delete' => 'حذف',
        'export' => 'تصدير', 'status' => 'تغيير الحالة', 'restore' => 'استعادة المحذوف',
        'force_delete' => 'حذف نهائي', 'manage' => 'إدارة', 'collect' => 'تحصيل المدفوعات',
        'confirm' => 'تأكيد الطلب', 'print' => 'طباعة المستندات', 'note' => 'إضافة الملاحظات',
        'reschedule' => 'إعادة الجدولة', 'price_override' => 'تجاوز السعر وتعديل القيمة',
        'notice_override' => 'تجاوز المدة المسبقة للحجز', 'renew_again' => 'تكرار التجديد', 'close_unpaid' => 'إغلاق طلب غير مسدد',
        'balance' => 'إيداع رصيد', 'balance_adjust' => 'تسوية الرصيد', 'notify' => 'إرسال إشعارات',
        'merge' => 'دمج العملاء', 'impersonate' => 'الدخول نيابة عن السائق',
        'capacity' => 'تعديل السعة', 'reorder' => 'إعادة الترتيب', 'transfer' => 'نقل بين الفرق',
        'windows' => 'تعديل أوقات التوزيع', 'settings' => 'تعديل الإعدادات',
        'price' => 'تعديل الأسعار', 'logs' => 'عرض سجل التعديلات',
    ];

    public static function page(string $name): ?string
    {
        $pages = array_keys(self::PAGES);
        usort($pages, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($pages as $page) {
            if ($name === $page || str_starts_with($name, $page.'_')) return $page;
        }
        return null;
    }

    public static function action(string $name): string
    {
        $page = self::page($name);
        return $page ? substr($name, strlen($page) + 1) : '';
    }

    public static function label(string $name): ?string
    {
        $page = self::page($name);
        $action = self::ACTIONS[self::action($name)] ?? null;
        return $page && $action ? $action.' — '.self::PAGES[$page] : null;
    }

    public static function canRoute(string $route): bool
    {
        $permissions = AdminRoutePermissionMatrix::resolve($route);
        return $permissions !== [] && AdminGuardHasAnyPerm($permissions);
    }
}
