<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * A visit "waits on a driver" when it has not started, a worker on it needs transport, and a driver leg is empty.
 * One rule for the dashboard alert, the order-page alert, the driver suggestion and the auto-assign sweep.
 */
class DriverGaps
{
    /** Same default as the worker app: a worker needs a driver unless her profile says otherwise. */
    public static function workerNeedsDriver($worker): bool
    {
        return (bool) (((array) ($worker->hourly_settings ?? []))['requires_driver'] ?? true);
    }

    /** @return string[] driver legs the visit still needs; empty when no assigned worker needs transport. */
    public static function missingRoles(HourlyVisit $visit): array
    {
        if ($visit->status !== 'scheduled' || !$visit->scheduled_start_at) return [];
        $workers = $visit->relationLoaded('workers') ? $visit->workers : $visit->workers()->get(['maids.id', 'maids.hourly_settings']);
        // Without assigned workers nobody can be judged yet; the empty-seat alert covers that case.
        if ($workers->isEmpty() || !$workers->contains(fn ($worker) => self::workerNeedsDriver($worker))) return [];
        return array_values(array_filter(['delivery', 'pickup'], fn ($role) => !$visit->{$role.'_driver_id'}));
    }

    /**
     * Today's visits that wait on a driver, including those whose start already passed, most urgent first.
     *
     * @return Collection<int, array{visit: HourlyVisit, missing: string[], late_minutes: int}>
     */
    public function today(): Collection
    {
        return HourlyVisit::where('status', 'scheduled')
            ->whereBetween('scheduled_start_at', [today(), today()->endOfDay()])
            ->where(fn ($q) => $q->whereNull('delivery_driver_id')->orWhereNull('pickup_driver_id'))
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['active', 'pending_confirmation']))
            ->with(['order:id,order_number,user_id,status', 'order.user:id,name,phone', 'workers:maids.id,maids.name,maids.hourly_settings'])
            ->orderBy('scheduled_start_at')->get()
            ->map(fn ($visit) => ['visit' => $visit, 'missing' => self::missingRoles($visit),
                'late_minutes' => $visit->scheduled_start_at->isPast() ? (int) $visit->scheduled_start_at->diffInMinutes(now()) : 0])
            ->filter(fn ($row) => $row['missing'])->values();
    }

    public static function roleLabel(string $role): string
    {
        return $role === 'delivery' ? 'التوصيل' : 'الاستلام';
    }

    public static function startsIn(Carbon $start): string
    {
        $minutes = (int) now()->diffInMinutes($start);
        return $minutes < 60 ? $minutes.' دقيقة' : intdiv($minutes, 60).' س'.($minutes % 60 ? ' و'.($minutes % 60).' د' : '');
    }
}
