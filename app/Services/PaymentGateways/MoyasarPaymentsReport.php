<?php

namespace App\Services\PaymentGateways;

use App\Services\BranchAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * مدفوعات ميسر من كل مصادرها في كليك بصيغة موحدة، ومنها المؤشرات والرسوم. للعرض فقط.
 * المصادر: فواتير عقود التأجير وتجديدها، نقل الخدمات، طلبات الساعات، وطلبات التطبيق.
 * الحالات تُوحَّد إلى: paid, partially_refunded, refunded, pending, cancelled.
 */
class MoyasarPaymentsReport
{
    public const SERVICES = ['rental', 'renewal', 'transfer', 'hourly', 'app'];
    public const STATUSES = ['paid', 'partially_refunded', 'refunded', 'pending', 'cancelled'];
    /** حالات دُفع فيها المبلغ (حتى لو استُرد لاحقاً). */
    private const COLLECTED = ['paid', 'partially_refunded', 'refunded'];

    /** @return Collection<int, array> عمليات ميسر من بداية نافذة الاتجاه حتى نهاية الفترة */
    public function rows(ReportPeriod $period): Collection
    {
        $from = $period->trendStart()->toDateTimeString();
        $to = $period->to->toDateTimeString();

        return $this->rental($from, $to)
            ->concat($this->legacyRental($from, $to))
            ->concat($this->transfers($from, $to))
            ->concat($this->hourly($from, $to))
            ->concat($this->app($from, $to))
            ->sortByDesc('created_at')
            ->values();
    }

    public function summary(ReportPeriod $period, Collection $rows): array
    {
        $inPeriod = $rows->filter(fn ($row) => $period->contains($row['created_at']));
        $billable = $inPeriod->where('status', '!=', 'cancelled');
        $collected = $rows->filter(fn ($row) => in_array($row['status'], self::COLLECTED, true) && $period->contains($row['paid_at']));
        $billed = (float) $billable->sum('amount');
        $collectedAmount = (float) $collected->sum('amount');
        $refunded = (float) $rows->filter(fn ($row) => $row['refunded'] > 0 && $period->contains($row['paid_at']))->sum('refunded');

        return [
            'count' => $inPeriod->count(),
            'paid_count' => $inPeriod->whereIn('status', self::COLLECTED)->count(),
            'billed' => $billed,
            'collected' => $collectedAmount,
            'refunded' => $refunded,
            'net' => $collectedAmount - $refunded,
            'pending' => (float) $inPeriod->where('status', 'pending')->sum('amount'),
            'pending_count' => $inPeriod->where('status', 'pending')->count(),
            'cancelled_count' => $inPeriod->where('status', 'cancelled')->count(),
            // نسبة ما سُدّد من روابط الفترة (غير الملغاة).
            'conversion' => $billable->count() ? round($billable->whereIn('status', self::COLLECTED)->count() / $billable->count() * 100, 1) : 0,
            'average' => $collected->count() ? $collectedAmount / $collected->count() : 0,
        ];
    }

    public function charts(ReportPeriod $period, Collection $rows): array
    {
        $months = $period->trendMonths();
        $byMonth = fn (string $dateKey, callable $filter, string $field = 'amount') => array_map(
            fn ($month) => round((float) $rows->filter($filter)->filter(fn ($row) => $row[$dateKey] && $row[$dateKey]->format('Y-m') === $month)->sum($field), 2),
            $months
        );
        $inPeriod = $rows->filter(fn ($row) => $period->contains($row['created_at']));
        $collectedInPeriod = $rows->filter(fn ($row) => in_array($row['status'], self::COLLECTED, true) && $period->contains($row['paid_at']));
        $days = $period->days();

        return [
            'trend' => [
                'categories' => array_map([ReportPeriod::class, 'monthLabel'], $months),
                'series' => [
                    ['name' => __('payment_gateways.chart.billed'), 'data' => $byMonth('created_at', fn ($row) => $row['status'] !== 'cancelled')],
                    ['name' => __('payment_gateways.chart.collected'), 'data' => $byMonth('paid_at', fn ($row) => in_array($row['status'], self::COLLECTED, true))],
                    ['name' => __('payment_gateways.chart.refunded'), 'data' => $byMonth('paid_at', fn ($row) => $row['refunded'] > 0, 'refunded')],
                ],
            ],
            'daily' => [
                'categories' => array_map(fn ($day) => substr($day, 5), $days),
                'series' => [['name' => __('payment_gateways.chart.collected'), 'data' => array_map(
                    fn ($day) => round((float) $collectedInPeriod->filter(fn ($row) => $row['paid_at']->format('Y-m-d') === $day)->sum('amount'), 2),
                    $days
                )]],
            ],
            'services' => $this->donut(self::SERVICES, 'payment_gateways.service', fn ($service) => round((float) $collectedInPeriod->where('service', $service)->sum('amount'), 2)),
            'statuses' => $this->donut(self::STATUSES, 'payment_gateways.status', fn ($status) => $inPeriod->where('status', $status)->count()),
        ];
    }

    /** الجدول: عمليات الفترة بعد فلاتر الخدمة والحالة والبحث. */
    public function table(ReportPeriod $period, Collection $rows, array $filters): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));

        return $rows
            ->filter(fn ($row) => $period->contains($row['created_at']) || $period->contains($row['paid_at']))
            ->when(in_array($filters['service'] ?? null, self::SERVICES, true), fn ($c) => $c->where('service', $filters['service']))
            ->when(in_array($filters['status'] ?? null, self::STATUSES, true), fn ($c) => $c->where('status', $filters['status']))
            ->when($search !== '', fn ($c) => $c->filter(fn ($row) => str_contains(mb_strtolower($row['reference'] . ' ' . $row['customer'] . ' ' . $row['phone'] . ' ' . $row['gateway_id']), $search)))
            ->values();
    }

    private function donut(array $keys, string $langPrefix, callable $value): array
    {
        return [
            'labels' => array_map(fn ($key) => __($langPrefix . '.' . $key), $keys),
            'series' => array_map($value, $keys),
        ];
    }

    /** كل فاتورة مسجلة لعقد (الأساسية أو التجديد)؛ السداد والاسترداد يُقرآن من سجل الفاتورة نفسه. */
    private function rental(string $from, string $to): Collection
    {
        if (!Schema::hasTable('moyasar_contract_payments')) {
            return collect();
        }

        $query = DB::table('moyasar_contract_payments as p')
            ->join('rental_contracts as c', 'c.id', '=', 'p.rental_contract_id')
            ->leftJoin('rental_contract_infos as i', 'i.rental_contract_id', '=', 'c.id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->where(fn ($q) => $q->whereBetween('p.created_at', [$from, $to])->orWhereBetween('p.paid_at', [$from, $to]));
        $this->scopeBranch($query, 'rental_contracts', 'c.branch_id');

        return $query->get(['c.id', 'c.contract_number', 'u.name', 'u.phone', 'p.invoice_id', 'p.amount', 'p.refunded_amount',
                'p.paid_at', 'p.created_at', 'p.reminder_id', 'i.moyasar_payment_id', 'i.moyasar_payment_status'])
            ->map(function ($r) {
                // حالة الفاتورة غير المدفوعة محفوظة على العقد للفاتورة الحالية فقط.
                $status = $r->paid_at ? 'paid' : ($r->moyasar_payment_id === $r->invoice_id ? (string) $r->moyasar_payment_status : 'pending');

                return $this->row($r->reminder_id ? 'renewal' : 'rental', $r->id, $r->contract_number, $r->name, $r->phone, $r->invoice_id,
                    $r->amount, $r->refunded_amount, $status, $r->paid_at, $r->created_at, $this->url('RentContract.show', $r->id));
            });
    }

    /** روابط عقود أُنشئت قبل سجل الفواتير ولم تُسجل فيه بعد. */
    private function legacyRental(string $from, string $to): Collection
    {
        $query = DB::table('rental_contract_infos as i')
            ->join('rental_contracts as c', 'c.id', '=', 'i.rental_contract_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->whereNotNull('i.moyasar_payment_id')
            ->when(Schema::hasTable('moyasar_contract_payments'), fn ($q) => $q->whereNotExists(fn ($sub) => $sub
                ->select(DB::raw(1))->from('moyasar_contract_payments as p')->whereColumn('p.invoice_id', 'i.moyasar_payment_id')))
            ->where(fn ($q) => $q->whereBetween('i.created_at', [$from, $to])->orWhereBetween('i.moyasar_paid_at', [$from, $to]));
        $this->scopeBranch($query, 'rental_contracts', 'c.branch_id');

        return $query->get(['c.id', 'c.contract_number', 'u.name', 'u.phone', 'i.moyasar_payment_id', 'i.contract_value',
                'i.moyasar_refunded_amount', 'i.moyasar_payment_status', 'i.moyasar_paid_at', 'i.created_at'])
            ->map(fn ($r) => $this->row('rental', $r->id, $r->contract_number, $r->name, $r->phone, $r->moyasar_payment_id,
                $r->contract_value, $r->moyasar_refunded_amount, $r->moyasar_paid_at ? 'paid' : (string) $r->moyasar_payment_status,
                $r->moyasar_paid_at, $r->created_at, $this->url('RentContract.show', $r->id)));
    }

    private function transfers(string $from, string $to): Collection
    {
        if (!Schema::hasColumn('sponsorship_transfers', 'moyasar_invoice_id')) {
            return collect();
        }

        $query = DB::table('sponsorship_transfers as t')
            ->leftJoin('users as u', 'u.id', '=', 't.new_sponsor_id')
            ->whereNotNull('t.moyasar_invoice_id')
            ->whereNull('t.deleted_at')
            ->where(fn ($q) => $q->whereBetween('t.created_at', [$from, $to])->orWhereBetween('t.moyasar_paid_at', [$from, $to]));
        $this->scopeBranch($query, 'sponsorship_transfers', 't.branch_id');

        return $query->get(['t.id', 'u.name', 'u.phone', 't.moyasar_invoice_id', 't.moyasar_amount', 't.moyasar_refunded_amount',
                't.moyasar_payment_status', 't.moyasar_paid_at', 't.created_at'])
            ->map(fn ($r) => $this->row('transfer', $r->id, '#' . $r->id, $r->name, $r->phone, $r->moyasar_invoice_id,
                $r->moyasar_amount, $r->moyasar_refunded_amount, $r->moyasar_paid_at ? 'paid' : (string) $r->moyasar_payment_status,
                $r->moyasar_paid_at, $r->created_at, $this->url('SponsorshipTransfer.show', $r->id)));
    }

    /** الاسترداد قد يُسجَّل على الدفعة نفسها أو صفاً بمبلغ سالب للطلب نفسه؛ يُضم إلى دفعته الأصلية. */
    private function hourly(string $from, string $to): Collection
    {
        if (!Schema::hasTable('hourly_order_payments')) {
            return collect();
        }

        $payments = DB::table('hourly_order_payments as p')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'p.hourly_rental_order_id')
            ->leftJoin('users as u', 'u.id', '=', 'o.user_id')
            ->where('p.provider', 'moyasar')
            ->where(fn ($q) => $q->whereBetween('p.created_at', [$from, $to])->orWhereBetween('p.paid_at', [$from, $to]))
            ->get(['p.id', 'p.hourly_rental_order_id', 'o.order_number', 'u.name', 'u.phone', 'p.provider_reference', 'p.amount',
                'p.refunded_amount', 'p.status', 'p.paid_at', 'p.created_at']);

        $refunds = $payments->filter(fn ($p) => (float) $p->amount < 0)->groupBy('hourly_rental_order_id');

        return $payments->filter(fn ($p) => (float) $p->amount >= 0)->map(function ($p) use ($refunds) {
            $negative = abs((float) collect($refunds->get($p->hourly_rental_order_id, []))->sum('amount'));
            $refunded = $p->status === 'paid' ? min(max((float) $p->refunded_amount, $negative), (float) $p->amount) : 0.0;

            return $this->row('hourly', $p->hourly_rental_order_id, $p->order_number, $p->name, $p->phone, $p->provider_reference,
                $p->amount, $refunded, $p->status, $p->paid_at, $p->created_at, $this->url('admin.hourly-orders.show', $p->hourly_rental_order_id));
        })->values();
    }

    /** مدفوعات طلبات التطبيق (الحجز قبل العقد). */
    private function app(string $from, string $to): Collection
    {
        if (!Schema::hasTable('rental_order_payments')) {
            return collect();
        }

        $query = DB::table('rental_order_payments as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.provider', 'moyasar')
            ->whereNotIn('p.status', ['creating'])
            ->where(fn ($q) => $q->whereBetween('p.created_at', [$from, $to])->orWhereBetween('p.paid_at', [$from, $to]));
        $this->scopeBranch($query, 'rental_order_payments', 'p.branch_id');

        return $query->get(['p.id', 'p.order_id', 'u.name', 'u.phone', 'p.provider_invoice_id', 'p.amount', 'p.refunded_amount',
                'p.status', 'p.paid_at', 'p.created_at'])
            ->map(fn ($r) => $this->row('app', (int) ($r->order_id ?: $r->id), '#' . ($r->order_id ?: $r->id), $r->name, $r->phone,
                $r->provider_invoice_id, $r->amount, $r->refunded_amount, $r->paid_at ? 'paid' : (string) $r->status,
                $r->paid_at, $r->created_at, $r->order_id ? $this->url('orders.show', $r->order_id) : null));
    }

    /** نفس عزل الفروع المطبق في قوائم السجلات؛ لا أثر له حين يكون وضع الفروع معطلاً. */
    private function scopeBranch($query, string $table, string $column): void
    {
        $branches = app(BranchAccessService::class);
        if (!$branches->enabled() || !$branches->tableHasBranchColumn($table)) {
            return;
        }
        $branches->applyToQuery($query, request(), $table, $column);
    }

    private function url(string $route, $id): ?string
    {
        return Route::has($route) ? route($route, $id) : null;
    }

    private function row(string $service, int $id, ?string $reference, ?string $customer, ?string $phone, ?string $gatewayId,
        $amount, $refunded, string $status, $paidAt, $createdAt, ?string $url): array
    {
        $amount = round((float) $amount, 2);
        $refunded = round((float) $refunded, 2);
        $status = self::normalise($status);
        if ($status === 'paid' && $refunded > 0) {
            $status = $refunded >= $amount ? 'refunded' : 'partially_refunded';
        }

        return [
            'service' => $service,
            'id' => $id,
            'reference' => (string) ($reference ?: '#' . $id),
            'customer' => (string) ($customer ?? ''),
            'phone' => (string) ($phone ?? ''),
            'gateway_id' => (string) ($gatewayId ?? ''),
            'amount' => $amount,
            'refunded' => $refunded,
            'status' => $status,
            'created_at' => $createdAt ? CarbonImmutable::parse($createdAt) : null,
            'paid_at' => $paidAt ? CarbonImmutable::parse($paidAt) : null,
            'url' => $url,
        ];
    }

    public static function normalise(string $status): string
    {
        return match ($status) {
            'paid', 'captured' => 'paid',
            'partially_refunded' => 'partially_refunded',
            'refunded' => 'refunded',
            'expired', 'canceled', 'cancelled', 'voided', 'failed' => 'cancelled',
            default => 'pending',
        };
    }
}
