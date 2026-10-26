<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * One driver choice for booking, the periodic sweep, the admin "auto driver" action and the dashboard suggestion.
 * Availability (team, work schedule, residency, 30-minute gap, vehicle seats) decides who may take the task;
 * live GPS only ranks drivers for imminent same-day visits, otherwise the driver with the lightest day goes first.
 */
class NearestDriverAssignment
{
    /** Live locations say nothing about where a driver will be later, so they rank only visits starting this soon. */
    public const LIVE_WINDOW_MINUTES = 180;

    /** Channel whose auto_assign_driver setting governs an existing order. */
    public static function channel(HourlyRentalOrder $order): string
    {
        return HourlyBookingRules::channel($order->source === 'admin');
    }

    /**
     * Fills the roles that still have no driver. A visit without an available driver stays unassigned
     * (the dashboard flags it) instead of rejecting the customer's booking.
     *
     * @return string[] roles left without a driver
     */
    public function assign(HourlyVisit $visit, bool $notify = true): array
    {
        return DB::transaction(function () use ($visit, $notify) {
            if (!$visit->scheduled_start_at || !$visit->scheduled_end_at || !$visit->hourly_team_id) return [];
            if ($visit->delivery_driver_id && $visit->pickup_driver_id) return [];
            // Workers who travel on their own need no driver; with no workers yet, cover the visit anyway.
            $workers = $visit->workers()->get(['maids.id', 'maids.hourly_settings']);
            if ($workers->isNotEmpty() && !$workers->contains(fn ($worker) => DriverGaps::workerNeedsDriver($worker))) return [];
            $before = $visit->only(['delivery_driver_id', 'pickup_driver_id']);
            // Lock in a stable order before availability checks to serialize competing bookings.
            $this->teamDrivers($visit)->lockForUpdate()->get();
            $missing = [];
            foreach ($this->suggest($visit) as $role => $pick) {
                if (!$pick) { $missing[] = $role; continue; }
                $visit->{$role.'_driver_id'} = $pick['driver']->id;
                $visit->{$role.'_at'} = $pick['time'];
            }
            if ($before === $visit->only(['delivery_driver_id', 'pickup_driver_id'])) return $missing;
            $visit->save();
            if ($notify) {
                $id = $visit->id;
                DB::afterCommit(fn () => app(StaffPushService::class)->assignmentChanged($id, $before + ['worker_ids' => []]));
            }
            return $missing;
        });
    }

    /**
     * Best available driver for each role that has none yet; nothing is saved.
     *
     * @param string[]|null $roles
     * @return array<string, array{driver: HourlyDriver, time: Carbon}|null>
     */
    public function suggest(HourlyVisit $visit, int $deliveryLeadMinutes = 0, ?array $roles = null): array
    {
        if (!$visit->scheduled_start_at || !$visit->scheduled_end_at || !$visit->hourly_team_id) return [];
        $draft = clone $visit;
        $picks = [];
        foreach ($roles ?? ['delivery', 'pickup'] as $role) {
            if ($draft->{$role.'_driver_id'}) continue;
            $time = $role === 'delivery' ? $visit->scheduled_start_at->copy()->subMinutes($deliveryLeadMinutes) : $visit->scheduled_end_at->copy();
            $available = app(OrderManagementService::class)->availableDrivers(collect([$draft]), $role, $time->format('H:i'), $time)
                ->filter(fn ($driver) => !$driver->vehicle_capacity || $driver->vehicle_capacity >= $visit->workers_count);
            $driver = $this->rank($visit, $available, $draft)->first();
            $picks[$role] = $driver ? ['driver' => $driver, 'time' => $time] : null;
            // The next role sees this choice, so one driver can take both legs when free.
            if ($driver) { $draft->{$role.'_driver_id'} = $driver->id; $draft->{$role.'_at'} = $time; }
        }
        return $picks;
    }

    private function teamDrivers(HourlyVisit $visit)
    {
        return HourlyDriver::where('is_active', true)->whereHas('teamMemberships', fn ($q) => $q
            ->where('hourly_team_id', $visit->hourly_team_id)->where('is_active', true))->orderBy('id');
    }

    private function rank(HourlyVisit $visit, Collection $drivers, HourlyVisit $draft): Collection
    {
        if ($drivers->isEmpty()) return $drivers;
        $distance = $this->liveDistances($visit, $drivers);
        $load = $this->dailyLoad($visit, $drivers);
        $taken = array_filter([$draft->delivery_driver_id, $draft->pickup_driver_id]);
        return $drivers->sortBy(fn ($driver) => [
            isset($distance[$driver->id]) ? 0 : 1, $distance[$driver->id] ?? 0,
            in_array($driver->id, $taken) ? 0 : 1, $load[$driver->id] ?? 0, $driver->id,
        ])->values();
    }

    /** @return array<int, float> haversine term per driver with a fresh location; empty unless the visit is imminent */
    private function liveDistances(HourlyVisit $visit, Collection $drivers): array
    {
        $start = $visit->scheduled_start_at;
        if ($visit->latitude === null || $visit->longitude === null || !Schema::hasTable('location_pings')
            || !$start->isSameDay(now()) || $start->gt(now()->addMinutes(self::LIVE_WINDOW_MINUTES))) return [];
        $lat = deg2rad((float) $visit->latitude);
        return DB::table('location_pings')->where('user_type', 'driver')->whereIn('user_id', $drivers->pluck('id'))
            ->where('recorded_at', '>=', now()->subMinutes((int) HourlyRentalSettings::get('tracking_active_minutes', 60)))
            ->where('recorded_at', '<=', now())->orderByDesc('recorded_at')->orderByDesc('id')->get()->unique('user_id')
            ->mapWithKeys(function ($point) use ($lat, $visit) {
                $other = deg2rad((float) $point->lat);
                return [(int) $point->user_id => pow(sin(($other - $lat) / 2), 2)
                    + cos($lat) * cos($other) * pow(sin(deg2rad((float) $point->lng - (float) $visit->longitude) / 2), 2)];
            })->all();
    }

    /** @return array<int, int> delivery + pickup tasks each driver already has on the visit's day */
    private function dailyLoad(HourlyVisit $visit, Collection $drivers): array
    {
        $load = [];
        foreach (['delivery' => 'scheduled_start_at', 'pickup' => 'scheduled_end_at'] as $role => $anchor) {
            $counts = HourlyVisit::where('id', '!=', $visit->id)->whereNotIn('status', ['cancelled', 'cancelled_by_client', 'expired'])
                ->whereIn($role.'_driver_id', $drivers->pluck('id'))
                ->whereRaw('DATE(COALESCE('.$role.'_at, '.$anchor.')) = ?', [$visit->scheduled_start_at->toDateString()])
                ->groupBy($role.'_driver_id')->selectRaw($role.'_driver_id as driver_id, COUNT(*) as tasks')->pluck('tasks', 'driver_id');
            foreach ($counts as $driver => $tasks) $load[$driver] = ($load[$driver] ?? 0) + (int) $tasks;
        }
        return $load;
    }
}
