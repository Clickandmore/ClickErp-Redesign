<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyRentalOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * التجديدات: عقود الطلبات حسب مدة العقد (ContractPeriod) — بدايته أول زيارة ونهايته آخر زيارة.
 * تُستبعد الطلبات الملغاة دائمًا. «مجدَّد» = له طلب تجديد غير ملغى عبر renewed_from_order_id.
 */
class RenewalSearch
{
    public const PRESETS = ['today' => 'تنتهي اليوم', 'd5' => 'خلال 5 أيام', 'd10' => 'خلال 10 أيام', 'd15' => 'خلال 15 يومًا', 'd30' => 'خلال 30 يومًا',
        'active_all' => 'كل العقود النشطة', 'active_on' => 'نشط في تاريخ محدد', 'custom' => 'نطاق مخصص'];
    public const REPORTS = ['all' => 'الكل', 'done' => 'تمت جميع الزيارات', 'open' => 'آخر زيارة غير مكتملة'];
    public const RENEWAL = ['all' => 'الكل', 'renewed' => 'تم التجديد', 'not_renewed' => 'لم يُجدَّد'];
    public const INSTALLMENTS = ['has' => 'لديه أقساط', 'pending' => 'أقساط معلقة', 'overdue' => 'أقساط متأخرة', 'none' => 'بدون أقساط'];
    /** قيمة «بدون فريق» في فلتر الفرق المتعدد. */
    public const NO_TEAM = 'none';

    public function __construct(private ContractPeriod $period) {}

    public function filters(Request $request): array
    {
        $data = $request->validate([
            'order_number' => 'nullable|string|max:40',
            'service_ids' => 'nullable|array|max:50', 'service_ids.*' => 'integer',
            'team_ids' => 'nullable|array|max:50', 'team_ids.*' => ['string', 'regex:/^(\d+|'.self::NO_TEAM.')$/'],
            'installment' => ['nullable', Rule::in(array_keys(self::INSTALLMENTS))],
            'report' => ['nullable', Rule::in(array_keys(self::REPORTS))],
            'renewal' => ['nullable', Rule::in(array_keys(self::RENEWAL))],
            'status' => ['nullable', Rule::in(array_keys(self::PRESETS))],
            'active_on' => 'nullable|required_if:status,active_on|date_format:Y-m-d',
            'active_from' => 'nullable|date_format:Y-m-d', 'active_to' => 'nullable|date_format:Y-m-d|after_or_equal:active_from',
            'expiry_from' => 'nullable|date_format:Y-m-d', 'expiry_to' => 'nullable|date_format:Y-m-d|after_or_equal:expiry_from',
            'per_page' => 'nullable|in:25,50,100',
        ]);
        $data['status'] ??= 'today';
        $data['report'] ??= 'all';
        $data['renewal'] ??= 'all';

        return array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    public function query(array $f)
    {
        $q = HourlyRentalOrder::query()->select('hourly_rental_orders.*', 'cp.first_visit_at', 'cp.last_visit_at', 'cp.open_visits')
            ->joinSub($this->period->aggregate(), 'cp', 'cp.hourly_rental_order_id', '=', 'hourly_rental_orders.id')
            ->where('hourly_rental_orders.status', '!=', 'cancelled');

        $status = $f['status'] ?? 'today';
        if ($status === 'active_all' || $status === 'active_on') {
            // نشط: اليوم (أو التاريخ المحدد) يقع بين أول زيارة وآخر زيارة من زيارات العقد.
            $day = $status === 'active_on' ? \Carbon\Carbon::parse($f['active_on'] ?? today()) : today();
            $q->where('cp.first_visit_at', '<=', $day->copy()->endOfDay())->where('cp.last_visit_at', '>=', $day->copy()->startOfDay());
        } else {
            [$from, $to] = $this->expiryWindow($f);
            if ($from) $q->where('cp.last_visit_at', '>=', $from);
            if ($to) $q->where('cp.last_visit_at', '<=', $to);
            if (!empty($f['active_from'])) $q->where('cp.first_visit_at', '>=', $f['active_from'].' 00:00:00');
            if (!empty($f['active_to'])) $q->where('cp.first_visit_at', '<=', $f['active_to'].' 23:59:59');
        }

        if (($f['report'] ?? 'all') === 'done') $q->where('cp.open_visits', 0);
        if (($f['report'] ?? 'all') === 'open') $q->where('cp.open_visits', '>', 0);
        if (($f['renewal'] ?? 'all') !== 'all') $q->{($f['renewal'] === 'renewed') ? 'whereExists' : 'whereNotExists'}(fn ($r) => $this->renewalExists($r));

        if (!empty($f['order_number'])) {
            $term = ltrim(trim($f['order_number']), '#');
            $q->where(fn ($s) => $s->where('hourly_rental_orders.order_number', 'like', '%'.$term.'%')->orWhere('hourly_rental_orders.id', ctype_digit($term) ? (int) $term : 0));
        }
        if (!empty($f['service_ids'])) $q->whereIn('hourly_rental_orders.service_category_id', array_map('intval', $f['service_ids']));
        if (!empty($f['team_ids'])) {
            $teams = array_map('intval', array_filter($f['team_ids'], fn ($t) => $t !== self::NO_TEAM));
            $noTeam = in_array(self::NO_TEAM, $f['team_ids'], true);
            $teamVisits = fn ($v) => $v->selectRaw('1')->from('hourly_visits as tv')->whereColumn('tv.hourly_rental_order_id', 'hourly_rental_orders.id');
            $q->where(function ($w) use ($teams, $noTeam, $teamVisits) {
                if ($teams) $w->orWhereExists(fn ($v) => $teamVisits($v)->whereIn('tv.hourly_team_id', $teams));
                // «بدون فريق»: لا توجد زيارة مرتبطة بأي فريق.
                if ($noTeam) $w->orWhereNotExists(fn ($v) => $teamVisits($v)->whereNotNull('tv.hourly_team_id'));
            });
        }
        if (!empty($f['installment'])) {
            $method = $f['installment'] === 'none' ? 'whereNotExists' : 'whereExists';
            $q->$method(function ($i) use ($f) {
                $i->selectRaw('1')->from('hourly_order_installments as i')->whereColumn('i.hourly_rental_order_id', 'hourly_rental_orders.id');
                if (in_array($f['installment'], ['pending', 'overdue'], true)) $i->whereColumn('i.paid_amount', '<', 'i.amount');
                if ($f['installment'] === 'overdue') $i->where('i.due_date', '<', now()->toDateString());
            });
        }

        return $q->orderBy('cp.last_visit_at')->orderBy('hourly_rental_orders.id');
    }

    /** وجود تجديد غير ملغى للطلب الحالي في الاستعلام الخارجي. */
    private function renewalExists($query)
    {
        return $query->selectRaw('1')->from('hourly_rental_orders as renewal')
            ->whereColumn('renewal.renewed_from_order_id', 'hourly_rental_orders.id')->where('renewal.status', '!=', 'cancelled');
    }

    /** نافذة تاريخ الانتهاء بحسب حالة العقد: اليوم، خلال N يومًا، أو نطاق مخصص. */
    public function expiryWindow(array $f): array
    {
        $status = $f['status'] ?? 'today';
        if ($status === 'custom') {
            return [!empty($f['expiry_from']) ? $f['expiry_from'].' 00:00:00' : null, !empty($f['expiry_to']) ? $f['expiry_to'].' 23:59:59' : null];
        }
        if (in_array($status, ['active_all', 'active_on'], true)) return [null, null];
        $days = $status === 'today' ? 0 : (int) substr($status, 1);

        return [today()->toDateTimeString(), today()->addDays($days)->endOfDay()->toDateTimeString()];
    }

    /** العقود غير المجدَّدة التي تنتهي من اليوم حتى N يومًا: مصدر عداد القائمة ونطاق التذكير. */
    public function dueQuery(int $days)
    {
        return $this->query(['status' => 'custom', 'renewal' => 'not_renewed', 'expiry_from' => today()->toDateString(), 'expiry_to' => today()->addDays($days)->toDateString()]);
    }

    public function dueCount(int $days): int
    {
        return $this->dueQuery($days)->count();
    }
}
