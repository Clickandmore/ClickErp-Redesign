<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder};
use App\Models\ingaz\UserAddress;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;

/**
 * يجهّز بيانات «تجديد» طلب لمعالج الحجز: كل ما يُنقل من الطلب القديم، ومواعيد مقترحة
 * تبدأ بعد انتهاء العقد القديم (ContractPeriod) بنفس نمط الأيام والوقت. كل القيم مقترحة وقابلة للتعديل،
 * والسعر يُحسب في المعالج من التسعير الحالي؛ السعر القديم يُرسل للمقارنة فقط.
 */
class RenewalPlanner
{
    public function __construct(private ContractPeriod $period) {}

    public function for(HourlyRentalOrder $source): array
    {
        $source->loadMissing(['user', 'renewedBy', 'visits.workers:id,name']);
        $package = HourlyPackage::find($source->hourly_package_id);
        $address = UserAddress::where('user_id', $source->user_id)->where('city_id', $source->city_id)->where('address', $source->address_text)->orderByDesc('id')->first();
        $contract = $this->period->visitsOf($source);
        $end = $this->period->endOf($source);
        $count = (int) ($package?->visits_count ?: max(1, $contract->count()));
        $workers = max((int) $source->workers_per_visit, (int) ($package?->workers_per_visit ?? 1));
        $visitCount = max(1, $contract->count());

        return [
            'id' => $source->id, 'order_number' => $source->order_number, 'order_url' => route('admin.hourly-orders.show', $source),
            'customer' => $source->user->only(['id', 'name', 'email', 'phone', 'hourly_wallet_balance']),
            'address_id' => $address?->id, 'item_id' => $source->hourly_package_id, 'category_id' => $source->service_category_id,
            'workers' => $workers, 'hours' => (float) ($package?->hours_per_visit ?? $source->hours_per_visit),
            'payment_method' => $source->payment_method, 'all_workers' => (bool) ($source->booking_metadata['all_workers'] ?? false),
            'team_id' => $contract->pluck('hourly_team_id')->filter()->countBy()->sortDesc()->keys()->first(),
            'preferred_workers' => $this->preferredWorkers($source, $workers),
            // سعر الزيارة القديم للمقارنة فقط: إجمالي الطلب على عدد زياراته المدفوعة.
            'previous_visit_price' => round((float) $source->total_amount / $visitCount, 2),
            'contract_end' => $end?->format('Y-m-d'),
            'pattern' => $this->pattern($contract),
            'visits' => $this->proposedStarts($contract, $end, $count, $package),
            'renewed_by' => $source->renewedBy ? ['id' => $source->renewedBy->id, 'order_number' => $source->renewedBy->order_number, 'url' => route('admin.hourly-orders.show', $source->renewedBy)] : null,
        ];
    }

    /**
     * المواعيد المقترحة: تُزاح زيارات العقد القديم بأسابيع كاملة حتى تبدأ بعد يوم انتهائه، فيبقى نمط الأيام والوقت.
     * إن وقعت البداية قبل أقرب موعد مسموح (مهلة الحجز) تُزاح أسبوعًا آخر حتى تصبح في المستقبل.
     * إن كانت الباقة الجديدة أطول يتكرر النمط دورةً بعد دورة.
     *
     * @return string[] بصيغة Y-m-d H:i
     */
    public function proposedStarts($contract, ?Carbon $end, int $count, ?HourlyPackage $package): array
    {
        // المجانية لا تصنع نمطًا حتى لو احتُسبت في نهاية العقد.
        $base = $contract->filter(fn ($v) => !$v->is_free)->values();
        if ($base->isEmpty()) $base = $contract->values();
        if ($base->isEmpty() || !$end) return [];

        $first = $base->first()->scheduled_start_at->copy();
        $span = $first->copy()->startOfDay()->diffInDays($base->last()->scheduled_start_at->copy()->startOfDay()) + 1;
        $cycle = 7 * (int) ceil($span / 7);
        $offset = 7 * (int) ceil(($first->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1) / 7);
        $notice = (int) ($package?->min_notice_hours ?? HourlyRentalSettings::get('min_notice_hours', 24));
        $earliest = now()->addHours($notice);
        while ($first->copy()->addDays($offset)->lt($earliest)) $offset += 7;

        $starts = [];
        for ($k = 0; $k < $count; $k++) {
            $starts[] = $base[$k % $base->count()]->scheduled_start_at->copy()->addDays($offset + $cycle * intdiv($k, $base->count()))->format('Y-m-d H:i');
        }
        sort($starts);

        return $starts;
    }

    /** آخر من خدم العميل في هذا الطلب: عاملات آخر زيارة منفّذة، وإلا آخر زيارة لها عاملات. */
    private function preferredWorkers(HourlyRentalOrder $source, int $limit): array
    {
        $visits = $source->visits->filter(fn ($v) => $v->workers->isNotEmpty() && !in_array($v->status, ContractPeriod::VOID, true))->sortByDesc('scheduled_start_at');
        $last = $visits->firstWhere('status', 'completed') ?? $visits->first();

        return $last ? $last->workers->take($limit)->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values()->all() : [];
    }

    /** وصف النمط المعتاد للعرض: أيام الأسبوع والوقت الأكثر تكرارًا. */
    private function pattern($contract): ?string
    {
        if ($contract->isEmpty()) return null;
        $days = $contract->map(fn ($v) => $v->scheduled_start_at->locale('ar')->dayName)->unique()->implode('، ');
        $time = $contract->map(fn ($v) => $v->scheduled_start_at->format('H:i'))->countBy()->sortDesc()->keys()->first();

        return $days.' · '.$time;
    }
}
