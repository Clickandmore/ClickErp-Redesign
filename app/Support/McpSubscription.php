<?php

namespace App\Support;

/**
 * اشتراك خدمة المساعد الذكي (MCP)، مستقل عن اشتراك النظام.
 *
 * مجاني: بلا تواريخ ولا قيود. مدفوع: يعمل بين تاريخي البداية والنهاية، ثم فترة سماح للقراءة فقط،
 * ثم يتوقف. مفتاح تشغيل الخادم في McpSettings يبقى مستقلاً: الخدمة تعمل بالاثنين معاً.
 * التواريخ بتوقيت التطبيق (Asia/Riyadh)، ويوم النهاية يوم عمل كامل.
 */
class McpSubscription
{
    public const MODE_FREE = 'free';

    public const MODE_PAID = 'paid';

    public const STATE_FREE = 'free';

    public const STATE_SCHEDULED = 'scheduled';

    public const STATE_ACTIVE = 'active';

    public const STATE_GRACE = 'grace';

    public const STATE_EXPIRED = 'expired';

    public const LABELS = [
        self::STATE_FREE => 'مجاني',
        self::STATE_SCHEDULED => 'لم يبدأ بعد',
        self::STATE_ACTIVE => 'ساري',
        self::STATE_GRACE => 'فترة السماح',
        self::STATE_EXPIRED => 'منتهٍ',
    ];

    /** ينبّه الموظفين داخل الخدمة قبل النهاية بهذا العدد من الأيام. */
    public const WARN_DAYS = 14;

    public static function state(): string
    {
        $settings = McpSettings::all();

        if ($settings['subscription_mode'] !== self::MODE_PAID) {
            return self::STATE_FREE;
        }

        $today = today()->toDateString();
        $starts = $settings['subscription_starts_at'];
        $ends = $settings['subscription_ends_at'];

        // مدفوع بلا تاريخ نهاية لا يأتي من الشاشة؛ لو ظهر فالخدمة تُغلق ولا تُفتح مجاناً.
        if (!$ends) {
            return self::STATE_EXPIRED;
        }

        if ($starts && $today < $starts) {
            return self::STATE_SCHEDULED;
        }

        if ($today <= $ends) {
            return self::STATE_ACTIVE;
        }

        return $today <= self::graceEndsAt($settings) ? self::STATE_GRACE : self::STATE_EXPIRED;
    }

    public static function allowsRead(): bool
    {
        return in_array(self::state(), [self::STATE_FREE, self::STATE_ACTIVE, self::STATE_GRACE], true);
    }

    public static function allowsWrite(): bool
    {
        return in_array(self::state(), [self::STATE_FREE, self::STATE_ACTIVE], true);
    }

    /** الأيام حتى تاريخ النهاية؛ سالبة بعده، وnull للمجاني. */
    public static function daysLeft(): ?int
    {
        $settings = McpSettings::all();

        if ($settings['subscription_mode'] !== self::MODE_PAID || !$settings['subscription_ends_at']) {
            return null;
        }

        return (int) today()->diffInDays(\Illuminate\Support\Carbon::parse($settings['subscription_ends_at'])->startOfDay(), false);
    }

    public static function expiringSoon(): bool
    {
        $days = self::daysLeft();

        return self::state() === self::STATE_ACTIVE && $days !== null && $days <= self::WARN_DAYS;
    }

    /** الرسالة التي يراها الموظف أو البرنامج المتصل؛ null إذا لا يوجد ما يُقال. */
    public static function message(): ?string
    {
        $settings = McpSettings::all();

        return match (self::state()) {
            self::STATE_SCHEDULED => "يبدأ اشتراك المساعد الذكي بتاريخ {$settings['subscription_starts_at']}. للاستفسار تواصل مع فريق ClickERP.",
            self::STATE_EXPIRED => 'انتهى اشتراك المساعد الذكي. لتجديده تواصل مع فريق ClickERP.',
            self::STATE_GRACE => 'انتهى اشتراك المساعد الذكي، ويعمل للقراءة فقط حتى ' . self::graceEndsAt($settings) . '. الإضافة والإرسال متوقفان. لتجديده تواصل مع فريق ClickERP.',
            default => self::expiringSoon() ? "ينتهي اشتراك المساعد الذكي بتاريخ {$settings['subscription_ends_at']}. لتجديده تواصل مع فريق ClickERP." : null,
        };
    }

    /** الإضافة متوقفة بسبب الاشتراك: رسالة تناسب الأداة، حتى لا يظن الموظف أنها مشكلة صلاحيات. */
    public static function writeBlockedMessage(): string
    {
        return self::message() ?? 'الإضافة والإرسال متوقفان لأن اشتراك المساعد الذكي غير ساري.';
    }

    public static function summary(): array
    {
        $settings = McpSettings::all();
        $state = self::state();

        return [
            'state' => $state,
            'label' => self::LABELS[$state],
            'mode' => $settings['subscription_mode'],
            'starts_at' => $settings['subscription_starts_at'],
            'ends_at' => $settings['subscription_ends_at'],
            'grace_ends_at' => $settings['subscription_mode'] === self::MODE_PAID && $settings['subscription_ends_at'] ? self::graceEndsAt($settings) : null,
            'days_left' => self::daysLeft(),
            'allows_read' => self::allowsRead(),
            'allows_write' => self::allowsWrite(),
            'expiring_soon' => self::expiringSoon(),
            'message' => self::message(),
        ];
    }

    private static function graceEndsAt(array $settings): string
    {
        return \Illuminate\Support\Carbon::parse($settings['subscription_ends_at'])
            ->addDays($settings['subscription_grace_days'])
            ->toDateString();
    }
}
