<?php

namespace App\Support;

/**
 * مسميات حالة الشكوى وأولويتها في بوابة المكتب الخارجي.
 *
 * لوحة التحكم تبني الشارات داخل DataTables بـ HTML مباشر، وبوابة الوكيل
 * ثنائية اللغة بأنماط CSS خاصة بها، لذلك تُجمع المسميات هنا بدل تكرارها في كل view.
 */
class AgentComplaintLabels
{
    /** @return array<string, array{ar: string, en: string, class: string}> */
    public static function statuses(): array
    {
        return [
            'pending' => ['ar' => 'قيد الانتظار', 'en' => 'Pending', 'class' => 'warning'],
            'in_progress' => ['ar' => 'قيد المعالجة', 'en' => 'In Progress', 'class' => ''],
            'resolved' => ['ar' => 'تم الحل', 'en' => 'Resolved', 'class' => 'success'],
            'closed' => ['ar' => 'مغلقة', 'en' => 'Closed', 'class' => 'muted'],
            'rejected' => ['ar' => 'مرفوضة', 'en' => 'Rejected', 'class' => 'danger'],
        ];
    }

    /** @return array<string, array{ar: string, en: string, class: string}> */
    public static function priorities(): array
    {
        return [
            'low' => ['ar' => 'منخفضة', 'en' => 'Low', 'class' => 'muted'],
            'medium' => ['ar' => 'متوسطة', 'en' => 'Medium', 'class' => ''],
            'high' => ['ar' => 'عالية', 'en' => 'High', 'class' => 'warning'],
            'urgent' => ['ar' => 'عاجلة', 'en' => 'Urgent', 'class' => 'danger'],
        ];
    }

    /** @return array{ar: string, en: string, class: string} */
    public static function status(?string $status): array
    {
        return self::statuses()[$status] ?? ['ar' => 'غير محدد', 'en' => 'Unknown', 'class' => 'muted'];
    }

    /** @return array{ar: string, en: string, class: string} */
    public static function priority(?string $priority): array
    {
        return self::priorities()[$priority] ?? ['ar' => 'غير محدد', 'en' => 'Unknown', 'class' => 'muted'];
    }
}
