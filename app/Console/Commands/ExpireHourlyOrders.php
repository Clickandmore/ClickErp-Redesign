<?php
namespace App\Console\Commands;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\HourlyOrderService;
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ExpireHourlyOrders extends Command
{
    protected $signature='hourly:expire-orders';
    protected $description='Expire unused hourly rental visits';
    public function handle(HourlyOrderService $service)
    {
        HourlyRentalSettings::flush();
        if (!HourlyRentalSettings::isEnabled()) return 0;
        HourlyRentalOrder::whereIn('status',['pending_confirmation','active'])->where('expires_at','<',now())->chunkById(100,function ($orders) use ($service) {
            foreach ($orders as $candidate) DB::transaction(function () use ($candidate,$service) {
                $order=HourlyRentalOrder::query()->lockForUpdate()->find($candidate->id);
                if (!$order || !in_array($order->status,['pending_confirmation','active']) || !$order->expires_at->isPast()) return;
                if(collect($order->booking_metadata['service_disruptions']??[])->contains(fn($state)=>in_array($state['status']??null,\App\Services\Hourly\ServiceIncident::WAITING,true)))return;
                // Company coverage failure must not consume the customer's unused paid visits.
                if ((float)$order->paid_amount>0 && $order->visits()->where('status','unscheduled')->get()
                    ->contains(fn($visit)=>\App\Services\Hourly\MissedVisitRecovery::coverageRecovery($visit,$order))) return;
                $order->visits()->where('status','unscheduled')->update(['status'=>'expired','updated_at'=>now()]);
                $service->transition($order,'expired','system',null,'انتهاء صلاحية الباقة');
            });
        });
        return 0;
    }
}
