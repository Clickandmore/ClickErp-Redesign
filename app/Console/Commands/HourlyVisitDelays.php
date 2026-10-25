<?php
namespace App\Console\Commands;

use App\Models\Hourly\HourlyVisit;
use App\Services\Hourly\{ShelterPresence, StaffPushService, VisitDelay};
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

class HourlyVisitDelays extends Command
{
    protected $signature = 'hourly:visit-delays';
    protected $description = 'Track overdue hourly visits and remind staff without recording a shelter return';

    public function handle(StaffPushService $push): int
    {
        HourlyRentalSettings::flush();
        if (!HourlyRentalSettings::isEnabled() || !Schema::hasTable('hourly_visit_delay_notices')) return self::SUCCESS;
        // No scheduled_end_at prefilter: VisitDelay measures from the actual service end (arrival + hours), which can differ from the booked slot.
        HourlyVisit::whereIn('status', ShelterPresence::OUT_STATUSES)
            ->whereHas('order', fn ($q) => $q->where('status', 'active'))
            ->with('workers:id')->chunkById(100, function ($visits) use ($push) {
                foreach ($visits as $visit) {
                    $state = VisitDelay::state($visit);
                    if (!$state) continue;
                    if ($state['stage'] !== 'ended' && Schema::hasTable('worker_daily_movements')) {
                        DB::table('worker_daily_movements')->where('source', 'hourly_visit')
                            ->where('reference_type', 'hourly_visit')->where('reference_id', $visit->id)
                            ->whereNull('in_at')->update(['status' => 'overdue', 'updated_at' => now()]);
                    }
                    $recipients = [];
                    if (in_array($visit->status, ['en_route', 'delivered', 'in_progress'], true)) {
                        foreach ($visit->workers as $worker) $recipients[] = ['worker', (int) $worker->id];
                    }
                    foreach (array_unique(array_filter([$visit->pickup_driver_id, $visit->team_leader_id])) as $id) {
                        $recipients[] = ['driver', (int) $id];
                    }
                    foreach ($recipients as [$guard, $id]) {
                        $key = ['visit_id' => $visit->id, 'scheduled_end_at' => $state['ends_at'],
                            'stage' => $state['stage'], 'guard' => $guard, 'staff_id' => $id];
                        DB::table('hourly_visit_delay_notices')->insertOrIgnore($key + ['created_at' => now(), 'updated_at' => now()]);
                        if (DB::table('hourly_visit_delay_notices')->where($key)->whereNotNull('sent_at')->exists()) continue;
                        try {
                            $result = $push->sendToStaff($guard, $id, 'متابعة زيارة ساعات',
                                'زيارة #'.$visit->visit_number.': '.$state['label'].' ('.$state['minutes'].' دقيقة بعد الموعد).',
                                ['type' => 'hourly_visit_delay', 'stage' => $state['stage'],
                                    'hourly_visit_id' => (string) $visit->id, 'hourly_order_id' => (string) $visit->hourly_rental_order_id]);
                            if ($result->successCount() > 0) DB::table('hourly_visit_delay_notices')->where($key)->update(['sent_at' => now(), 'updated_at' => now()]);
                        } catch (\Throwable $e) { report($e); }
                    }
                }
            });
        return self::SUCCESS;
    }
}
