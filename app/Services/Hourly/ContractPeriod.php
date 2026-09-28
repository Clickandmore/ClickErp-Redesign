<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;

/**
 * المصدر الوحيد لمدة عقد طلب الساعات: بدايته أول زيارة ونهايته آخر زيارة من «زيارات العقد».
 * تستخدمه صفحة التجديدات والتذكير اليومي وعداد القائمة ومخطِّط التجديد، فلا يختلف الحساب بينها.
 *
 * زيارات العقد: كل زيارة لها موعد، عدا الملغاة والمنتهية الصلاحية دائمًا، وعدا المجانية/التعويضية
 * (is_free) ما لم يفعّل إعداد renewal_count_free_visits.
 */
class ContractPeriod
{
    public const VOID = ['cancelled', 'cancelled_by_client', 'expired'];
    /** الزيارات المغلقة بالتنفيذ؛ ما عداها من زيارات العقد «مفتوحة». */
    public const DONE = ['completed', 'no_show'];

    public function countsFreeVisits(): bool
    {
        return (bool) HourlyRentalSettings::get('renewal_count_free_visits', false);
    }

    /** قيد «زيارات العقد» على أي استعلام زيارات؛ $table يُمرَّر عند استعمال اسم مستعار. */
    public function scope($query, string $table = 'hourly_visits')
    {
        $query->whereNotIn($table.'.status', self::VOID)->whereNotNull($table.'.scheduled_start_at');
        if (!$this->countsFreeVisits()) $query->where(fn ($q) => $q->where($table.'.is_free', false)->orWhereNull($table.'.is_free'));

        return $query;
    }

    /** مجمّع لكل طلب: أول زيارة وآخر زيارة وعدد الزيارات التي لم تُغلق، لاستخدامه كـ joinSub. */
    public function aggregate()
    {
        $done = implode(', ', array_map(fn ($s) => "'".$s."'", self::DONE));

        return $this->scope(HourlyVisit::query())->groupBy('hourly_rental_order_id')
            ->selectRaw('hourly_rental_order_id, MIN(scheduled_start_at) as first_visit_at, MAX(scheduled_start_at) as last_visit_at, SUM(CASE WHEN status IN ('.$done.') THEN 0 ELSE 1 END) as open_visits');
    }

    /** زيارات عقد طلب معيّن بترتيبها الزمني. */
    public function visitsOf(HourlyRentalOrder $order)
    {
        return $this->scope($order->visits()->getQuery())->orderBy('scheduled_start_at')->orderBy('id')->get();
    }

    /** تاريخ انتهاء العقد = موعد آخر زيارة من زيارات العقد (null إن لم توجد). */
    public function endOf(HourlyRentalOrder $order): ?Carbon
    {
        $last = $this->scope($order->visits()->getQuery())->max('scheduled_start_at');

        return $last ? Carbon::parse($last) : null;
    }
}
