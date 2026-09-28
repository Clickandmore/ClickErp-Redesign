<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyServiceCategory, HourlyTeam, HourlyVisit};
use App\Models\ingaz\Maid;
use App\Services\Hourly\VisitSearch;
use App\Services\Rental\RentalMaidQuery;
use App\Support\HourlyScheduleSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * جدول الموظفين اليومي: عمود لكل عاملة ساعات، والزيارات موضوعة على خط الوقت.
 * الإجراءات لا تمر من هنا؛ الصفحة تستدعي نقاط الزيارات والطلبات الموجودة بصلاحياتها.
 */
class StaffScheduleController extends Controller
{
    /** الزيارات المنتهية بالإلغاء لا تشغل وقت العاملة فلا تُرسم. */
    private const HIDDEN = ['cancelled', 'cancelled_by_client', 'expired'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'date' => 'nullable|date_format:Y-m-d', 'city_id' => 'nullable|integer', 'team_id' => 'nullable|integer',
            'service_id' => 'nullable|integer', 'hide_empty' => 'nullable|boolean',
        ]);
        $date = Carbon::parse($filters['date'] ?? today()->toDateString())->startOfDay();
        $settings = HourlyScheduleSettings::all();

        $visits = HourlyVisit::query()
            ->whereBetween('scheduled_start_at', [$date, $date->copy()->endOfDay()])
            ->whereNotIn('status', self::HIDDEN)
            ->whereHas('order', fn ($o) => $o->whereNotIn('status', ['cancelled', 'expired'])
                ->when($filters['service_id'] ?? null, fn ($q, $id) => $q->where('service_category_id', $id)))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->with(['workers:id,name', 'order' => fn ($o) => $o->withCount('visits')->with(['user:id,name,phone', 'serviceCategory'])])
            ->orderBy('scheduled_start_at')->get();

        $workers = $this->workers($filters, $visits);
        if (!empty($filters['hide_empty'])) {
            $busy = $visits->flatMap(fn ($v) => $v->workers->pluck('id'))->unique();
            $workers = $workers->filter(fn ($w) => $busy->contains($w->id))->values();
        }

        // توزيع المدفوع على الزيارات يُحسب مرة لكل طلب لا لكل زيارة.
        $allocator = app(\App\Services\Hourly\VisitPaymentAllocation::class);
        $paid = [];
        foreach ($visits->pluck('order')->unique('id') as $order) {
            foreach ($allocator->forOrder($order) as $visitId => $row) $paid[$visitId] = $row['paid'];
        }
        $creators = \App\Models\auth\Admin::whereIn('id', $visits->pluck('order.created_by_admin_id')->filter()->unique())->pluck('name', 'id');
        $blocks = $this->blocks($visits, $workers->pluck('id'), $date, $settings);
        // زيارات اليوم التي تنقصها عاملات (بلا تعيين أو تعيين جزئي) تظهر في لوحة مستقلة أعلى الجدول.
        $needing = $visits->filter(fn ($v) => $v->workers->count() < (int) $v->workers_count && !in_array($v->status, VisitSearch::CLOSED, true))->values();
        $slots = (int) $visits->sum('workers_count');
        $assignedSlots = (int) $visits->sum(fn ($v) => min($v->workers->count(), (int) $v->workers_count));

        return view('admin.hourly_schedule.index', [
            'date' => $date, 'filters' => $filters, 'settings' => $settings, 'workers' => $workers, 'blocks' => $blocks,
            'visits' => $visits, 'needing' => $needing, 'creators' => $creators, 'slots' => $slots, 'assignedSlots' => $assignedSlots,
            'details' => $visits->mapWithKeys(fn ($v) => [$v->id => $this->details($v, $creators, $paid[$v->id] ?? 0)]),
            'teams' => HourlyTeam::orderBy('name')->get(['id', 'name']),
            'services' => HourlyServiceCategory::ordered()->get(),
            'cities' => \App\Models\City::saudi()->active()->get(),
            'canEdit' => AdminGuardHasAllPerms(['hourly_visits_edit', 'hourly_visits_view', 'hourly_orders_edit', 'hourly_orders_reschedule']),
            'canSettings' => AdminGuardHasPerm('hourly_schedule_settings'),
            'canPrice' => AdminGuardHasPerm('hourly_visits_price_override'),
            'canCollect' => AdminGuardHasPerm('hourly_visits_collect'),
        ]);
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'start_time' => ['required', 'regex:/^([01]\d|2[0-3]):00$/'],
            'end_time' => ['required', 'regex:/^(([01]\d|2[0-3]):00|24:00)$/'],
            'row_height' => 'required|integer|between:10,30',
            'copy_fields' => 'required|array|max:'.count(HourlyScheduleSettings::COPY_FIELDS),
            'copy_fields.*.key' => ['required', \Illuminate\Validation\Rule::in(array_keys(HourlyScheduleSettings::COPY_FIELDS))],
            'copy_fields.*.enabled' => 'required|boolean', 'copy_fields.*.label' => 'required|boolean', 'copy_fields.*.blank' => 'required|boolean',
        ]);
        if ((int) substr($data['end_time'], 0, 2) <= (int) substr($data['start_time'], 0, 2)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_time' => 'وقت الانتهاء يجب أن يكون بعد وقت البدء.']);
        }

        return response()->json(['message' => 'تم حفظ إعدادات التقويم.', 'settings' => HourlyScheduleSettings::persist($data)]);
    }

    /** عاملات الساعات كأعمدة، مع إبقاء أي عاملة لديها زيارة اليوم حتى لو خرجت من الفلتر. */
    private function workers(array $filters, $visits)
    {
        $query = Maid::query()->select('maids.id', 'maids.name', 'maids.hourly_is_suspended')
            ->where('rental_mode', 'hourly')
            ->whereHas('jobTypes', fn ($q) => $q->where('job_types.id', RentalMaidQuery::rentalJobTypeId()))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->whereHas('hourlyTeamMemberships', fn ($m) => $m->where('hourly_team_id', $id)->whereNull('left_at')))
            ->when($filters['service_id'] ?? null, fn ($q, $id) => $q->whereJsonContains('hourly_settings->service_category_ids', (int) $id));
        $filtered = !empty($filters['team_id']) || !empty($filters['service_id']);
        if (!$filtered) {
            $query->where(fn ($q) => $q->where('hourly_is_suspended', false)->orWhereIn('maids.id', $visits->flatMap(fn ($v) => $v->workers->pluck('id'))->all() ?: [0]));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * كتل الزيارات لكل عاملة: الموضع بالدقائق من بداية التقويم، مع مسارات جانبية عند تداخل زيارتين لنفس العاملة.
     *
     * @return array<int, array<int, array{visit:int,top:int,height:int,lane:int,lanes:int}>>
     */
    private function blocks($visits, $workerIds, Carbon $date, array $settings): array
    {
        $dayStart = $date->copy()->setTimeFromTimeString($settings['start_time']);
        $dayEnd = $settings['end_time'] === '24:00' ? $date->copy()->endOfDay() : $date->copy()->setTimeFromTimeString($settings['end_time']);
        $byWorker = [];
        foreach ($visits as $visit) {
            if (!$visit->scheduled_start_at) continue;
            $start = $visit->scheduled_start_at->max($dayStart);
            $end = ($visit->scheduled_end_at ?? $visit->scheduled_start_at->copy()->addMinutes((int) round($visit->planned_hours * 60)))->min($dayEnd);
            if ($end <= $start) continue;
            foreach ($visit->workers as $worker) {
                if (!$workerIds->contains($worker->id)) continue;
                $byWorker[$worker->id][] = ['visit' => $visit->id, 'from' => $dayStart->diffInMinutes($start), 'to' => $dayStart->diffInMinutes($end)];
            }
        }

        foreach ($byWorker as $id => $items) {
            usort($items, fn ($a, $b) => $a['from'] <=> $b['from']);
            $laneEnds = [];
            foreach ($items as &$item) {
                $lane = 0;
                while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $item['from']) $lane++;
                $laneEnds[$lane] = $item['to'];
                $item['lane'] = $lane;
            }
            unset($item);
            $lanes = max(1, count($laneEnds));
            $byWorker[$id] = array_map(fn ($i) => ['visit' => $i['visit'], 'top' => $i['from'], 'height' => $i['to'] - $i['from'], 'lane' => $i['lane'], 'lanes' => $lanes], $items);
        }

        return $byWorker;
    }

    /** بيانات الزيارة للمودال ونسخ التفاصيل (تُرسل للمتصفح كـ JSON). */
    private function details(HourlyVisit $visit, $creators, float $paid): array
    {
        $order = $visit->order;
        $snapshot = $order->package_snapshot ?? [];
        $package = collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale())['title'] ?? ($snapshot['title'] ?? $snapshot['code'] ?? '—');
        $hours = rtrim(rtrim(number_format((float) $visit->planned_hours, 2), '0'), '.');
        return [
            'id' => $visit->id, 'number' => $visit->visit_number, 'order_id' => $order->id, 'order_number' => $order->order_number,
            'customer' => $order->user?->name, 'phone' => $visit->contact_phone ?: $order->user?->phone,
            'date' => $visit->scheduled_start_at?->format('Y-m-d'), 'start' => $visit->scheduled_start_at?->format('H:i'), 'end' => $visit->scheduled_end_at?->format('H:i'),
            'starts_at' => $visit->scheduled_start_at?->format('Y-m-d\TH:i'), 'hours' => $hours,
            'address' => $visit->address_text, 'location' => $visit->map_url, 'service' => $order->serviceCategory?->name ?? '—', 'package' => $package,
            'workers' => $visit->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(), 'required' => (int) $visit->workers_count,
            'price' => number_format((float) $visit->visit_price, 2, '.', ''), 'paid' => number_format((float) $paid, 2, '.', ''),
            'payment_method' => __('hourly.'.$order->payment_method), 'payment_status' => __('hourly.'.$order->payment_status),
            'status' => VisitSearch::STATUSES[$visit->status] ?? $visit->status, 'closed' => in_array($visit->status, VisitSearch::CLOSED, true),
            'recurring' => $order->visits_count > 1, 'creator' => $order->source === 'admin' ? ($creators[$order->created_by_admin_id] ?? null) : null,
            'order_url' => route('admin.hourly-orders.show', $order), 'schedule_url' => route('admin.hourly-orders.reschedule', [$order, $visit->id]),
            'operations_url' => route('admin.hourly-orders.operations', $order), 'workers_url' => route('admin.hourly-visits.workers', $visit),
        ];
    }
}
