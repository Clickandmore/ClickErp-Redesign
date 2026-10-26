<?php
namespace App\Console\Commands;

use App\Models\Hourly\HourlyVisit;
use App\Services\Hourly\{HourlyBookingRules, NearestDriverAssignment, OrderManagementService};
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;

class HourlyAssignDrivers extends Command
{
    /** Far enough to cover what operations plans ahead, short enough to keep the sweep cheap. */
    public const HORIZON_DAYS = 14;

    protected $signature = 'hourly:assign-drivers';
    protected $description = 'Retry automatic driver assignment for upcoming hourly visits that still have no driver';

    public function handle(NearestDriverAssignment $assignment, OrderManagementService $orders): int
    {
        HourlyRentalSettings::flush();
        if (!HourlyRentalSettings::isEnabled()) return self::SUCCESS;
        $settings = app(HourlyBookingRules::class)->settings();
        $assigned = 0;
        HourlyVisit::where('status', 'scheduled')->whereNotNull('hourly_team_id')
            ->whereBetween('scheduled_start_at', [now(), now()->addDays(self::HORIZON_DAYS)])
            ->where(fn ($q) => $q->whereNull('delivery_driver_id')->orWhereNull('pickup_driver_id'))
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['active', 'pending_confirmation']))
            ->with('order')->chunkById(100, function ($visits) use ($assignment, $orders, $settings, &$assigned) {
                foreach ($visits as $visit) {
                    $order = $visit->order;
                    if (!$settings->value($order->package_snapshot, 'auto_assign_driver', NearestDriverAssignment::channel($order))) continue;
                    try {
                        $before = $visit->getAttributes();
                        $assignment->assign($visit);
                        if ($visit->wasChanged(['delivery_driver_id', 'pickup_driver_id'])) {
                            $orders->audit($order, 'auto_driver', $before, $visit->getAttributes(), null, $visit->id, 'إسناد تلقائي للسائق لزيارة قادمة بدون سائق');
                            $assigned++;
                        }
                    } catch (\Throwable $e) { report($e); }
                }
            });
        $this->info('Drivers assigned to '.$assigned.' visit(s).');
        return self::SUCCESS;
    }
}
