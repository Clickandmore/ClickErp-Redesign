<?php

namespace App\Console\Commands;

use App\Models\Hourly\{HourlyRentalOrder, HourlyServiceCategory, HourlyVisit};
use App\Services\Hourly\HourlyCategorySettings;
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * يغلق زيارات اليوم تلقائيًا لكل فئة ضُبط لها auto_complete_visits_at (أو ورثته) بعد مرور الوقت.
 * يُشغَّل كل 5 دقائق من المجدول ولا يلمس إلا زيارات اليوم المجدولة/الجارية في طلبات نشطة.
 */
class HourlyAutoCompleteVisits extends Command
{
    protected $signature = 'hourly:auto-complete-visits';
    protected $description = 'Complete today\'s hourly visits once each category\'s auto-complete time has passed';

    public const OPEN_STATUSES = ['scheduled', 'en_route', 'delivered', 'in_progress', 'worker_finished'];

    public function handle(HourlyCategorySettings $settings): int
    {
        if (!HourlyRentalSettings::isEnabled() || !Schema::hasTable('hourly_category_settings') || !Schema::hasColumn('hourly_rental_orders', 'service_category_id')) return self::SUCCESS;
        $now = now()->format('H:i');
        $total = 0;
        foreach (HourlyServiceCategory::pluck('id') as $categoryId) {
            $visits = HourlyVisit::query()
                ->whereDate('scheduled_date', today())->whereIn('status', self::OPEN_STATUSES)
                ->whereIn('hourly_rental_order_id', HourlyRentalOrder::where('service_category_id', $categoryId)->where('status', 'active')->select('id'))
                ->get();
            foreach ($visits as $visit) {
                DB::transaction(function () use ($visit,$settings,$now,&$total) {
                    $order=HourlyRentalOrder::whereKey($visit->hourly_rental_order_id)->lockForUpdate()->first();
                    $visit=HourlyVisit::whereKey($visit->id)->lockForUpdate()->first();
                    if(!$order || $order->status!=='active' || !in_array($visit->status,self::OPEN_STATUSES,true))return;
                    $time=$settings->autoCompleteAt((array)$order->package_snapshot+['service_category_id'=>$order->service_category_id]);
                    if(!$time || $now<$time)return;
                    $visit->update(['status' => 'completed']);
                    $visit->order->statusLogs()->create(['from_status' => $visit->order->status, 'to_status' => $visit->order->status, 'actor_type' => 'system', 'actor_id' => null, 'reason' => 'إغلاق تلقائي للزيارة '.$visit->visit_number]);
                    $total++;
                });
            }
        }
        $this->info("Completed {$total} visit(s).");
        return self::SUCCESS;
    }
}
