<?php

namespace App\Services\Hourly;

use App\Models\NewSettings;
use App\Support\HourlyRentalSettings;

/**
 * عداد «التجديدات» في القائمة الجانبية: العقود غير المجدَّدة التي تنتهي خلال مدة التذكير.
 * يُخزَّن في new_settings بتاريخ حسابه (مشغّل الكاش قد يكون array فلا يدوم بين الطلبات)،
 * تحدّثه المهمة اليومية، ويُمسح عند إنشاء تجديد حتى يختفي العقد المجدَّد من العداد فورًا.
 */
class RenewalCounter
{
    public const KEY = 'hourly_renewals_due_count';

    /** نسخة الطلب الحالي فقط، حتى لا يُقرأ الإعداد أكثر من مرة في نفس الصفحة. */
    private ?int $memo = null;

    public function get(bool $refresh = false): int
    {
        if (!$refresh && $this->memo !== null) return $this->memo;
        $stored = json_decode((string) NewSettings::query()->where('key', self::KEY)->value('value'), true);
        if (!$refresh && is_array($stored) && ($stored['date'] ?? null) === today()->toDateString()) return $this->memo = (int) $stored['count'];

        $count = app(RenewalSearch::class)->dueCount($this->days());
        NewSettings::query()->updateOrCreate(['key' => self::KEY], ['value' => json_encode(['count' => $count, 'date' => today()->toDateString()])]);

        return $this->memo = $count;
    }

    /** يُستدعى عند تغيّر حالة التجديد؛ يُعاد الحساب عند أول عرض للقائمة. */
    public function forget(): void
    {
        $this->memo = null;
        NewSettings::query()->where('key', self::KEY)->delete();
    }

    /** مدة التذكير، وعند تعطيل التذكير (0) يبقى العداد على 7 أيام. */
    public function days(): int
    {
        $days = (int) HourlyRentalSettings::get('renewal_reminder_days', 7);

        return $days > 0 ? $days : 7;
    }
}
