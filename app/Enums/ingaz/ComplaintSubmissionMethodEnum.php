<?php

namespace App\Enums\ingaz;

/**
 * قناة تقديم الشكوى — تُضبط تلقائياً حسب المصدر الذي أُنشئت منه الشكوى.
 *
 * العمود submission_method نصي حر لأن شكاوى الاستيراد تحمل قيمًا وصفية
 * (مثل "زيارة مقر")، لذلك يُستخدم label() الذي يعيد النص كما هو عند عدم التطابق.
 */
enum ComplaintSubmissionMethodEnum: string
{
    case Dashboard = 'dashboard'; // إدخال يدوي من لوحة التحكم بواسطة موظف
    case Website = 'website';     // نموذج الشكاوى في الموقع الإلكتروني
    case MobileApp = 'mobile_app'; // تطبيق الجوال
    case Import = 'import';       // استيراد بيانات
    case AgentPortal = 'agent_portal'; // بوابة المكتب الخارجي (الوكيل)

    public function lang(): string
    {
        return match ($this) {
            self::Dashboard => 'لوحة التحكم',
            self::Website => 'الموقع الإلكتروني',
            self::MobileApp => 'تطبيق الجوال',
            self::Import => 'استيراد بيانات',
            self::AgentPortal => 'بوابة المكتب الخارجي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Dashboard => 'primary',
            self::Website => 'info',
            self::MobileApp => 'success',
            self::Import => 'secondary',
            self::AgentPortal => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Dashboard => 'fa-desktop',
            self::Website => 'fa-globe',
            self::MobileApp => 'fa-mobile-screen-button',
            self::Import => 'fa-file-import',
            self::AgentPortal => 'fa-building-user',
        };
    }

    /**
     * الاسم المعروض لأي قيمة مخزّنة، بما فيها النصوص الحرة القادمة من الاستيراد.
     */
    public static function label(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return self::tryFrom($value)?->lang() ?: $value;
    }

    /**
     * @return array<string, string> القيمة => الاسم المعروض
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->lang()])
            ->all();
    }
}
