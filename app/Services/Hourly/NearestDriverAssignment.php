<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB, Schema};

class NearestDriverAssignment
{
    /** Uses fresh actual locations, not a prediction of the driver's future position. */
    public function assign(HourlyVisit $visit, bool $notify = true): void
    {
        DB::transaction(function () use ($visit, $notify) {
            if (!$visit->scheduled_start_at || !$visit->scheduled_end_at) return;
            if ($visit->delivery_driver_id && $visit->pickup_driver_id) return;
            if ($visit->latitude === null || $visit->longitude === null || !Schema::hasTable('location_pings')) {
                HourlyOrderService::reject('الإسناد التلقائي للسائق يتطلب إحداثيات الزيارة ومواقع حديثة للسائقين.');
            }
            $before=$visit->only(['delivery_driver_id','pickup_driver_id']);
            // Lock in a stable order before availability checks to serialize competing bookings.
            $drivers=HourlyDriver::where('is_active',true)->whereHas('teamMemberships',fn($q)=>$q
                ->where('hourly_team_id',$visit->hourly_team_id)->where('is_active',true))->orderBy('id')->lockForUpdate()->get();
            $locations=DB::table('location_pings')->where('user_type','driver')->whereIn('user_id',$drivers->pluck('id'))
                ->where('recorded_at','>=',now()->subMinutes((int)HourlyRentalSettings::get('tracking_active_minutes',60)))
                ->where('recorded_at','<=',now())->orderByDesc('recorded_at')->orderByDesc('id')->get()->unique('user_id')->keyBy('user_id');
            $ranked=$drivers->filter(fn($driver)=>isset($locations[$driver->id]) &&
                (!$driver->vehicle_capacity || $driver->vehicle_capacity >= $visit->workers_count))->sortBy(function($driver)use($locations,$visit){
                    $point=$locations[$driver->id];
                    $lat=deg2rad((float)$visit->latitude);$other=deg2rad((float)$point->lat);
                    return pow(sin(($other-$lat)/2),2)+cos($lat)*cos($other)*pow(sin(deg2rad((float)$point->lng-(float)$visit->longitude)/2),2);
                });
            foreach (['delivery','pickup'] as $role) {
                // Keep explicit panel selections; existing validation still checks them.
                if ($visit->{$role.'_driver_id'}) continue;
                $time=$role==='delivery'?$visit->scheduled_start_at:$visit->scheduled_end_at;
                $ids=app(OrderManagementService::class)->availableDrivers(collect([$visit]),$role,$time->format('H:i'),$time)->pluck('id');
                $driver=$ranked->first(fn($driver)=>$ids->contains($driver->id));
                if (!$driver) {
                    $reasons=$ranked->map(fn($candidate)=>$candidate->residencyAssignmentReason($time))->filter()->implode(' ');
                    HourlyOrderService::reject('لا يوجد سائق متاح ذو موقع حديث من فريق الزيارة لمهمة '.($role==='delivery'?'التوصيل':'الاستلام').'.'.($reasons ? ' '.$reasons : ''));
                }
                $visit->{$role.'_driver_id'}=$driver->id;
                $visit->{$role.'_at'}=$time;
            }
            $visit->save();
            if ($notify && $before!==$visit->only(['delivery_driver_id','pickup_driver_id'])) {
                $id=$visit->id;
                DB::afterCommit(fn()=>app(StaffPushService::class)->assignmentChanged($id,$before+['worker_ids'=>[]]));
            }
        });
    }
}
