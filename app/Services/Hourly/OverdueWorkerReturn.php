<?php
namespace App\Services\Hourly;

use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB,Schema};

/** Physical return holds are evaluated live, independently of the reminder cron. */
class OverdueWorkerReturn
{
    /** @return array<int,array{visit_id:int,ended_at:?string}> */
    public static function pending(?int $maidId=null): array
    {
        if(!Schema::hasTable('hourly_visits') || !Schema::hasTable('hourly_visit_workers'))return [];
        $cutoff=now()->subMinutes((int)HourlyRentalSettings::get('pickup_grace_minutes',30));
        $hasMovements=Schema::hasTable('worker_daily_movements');
        $hasArrival=Schema::hasColumn('hourly_visit_workers','arrived_at');
        $visits=DB::table('hourly_visit_workers as w')->join('hourly_visits as v','v.id','=','w.hourly_visit_id')
            ->join('hourly_rental_orders as o','o.id','=','v.hourly_rental_order_id')
            ->whereIn('v.status',ShelterPresence::OUT_STATUSES)
            ->when($maidId!==null,fn($q)=>$q->where('w.maid_id',$maidId))->orderBy('v.scheduled_end_at')
            ->get(['w.maid_id','v.id as visit_id','v.scheduled_end_at','v.scheduled_start_at','v.planned_hours',
                $hasArrival?'w.arrived_at':DB::raw('NULL as arrived_at'),
                Schema::hasColumn('hourly_rental_orders','booking_metadata')?'o.booking_metadata':DB::raw('NULL as booking_metadata')]);
        $returned=[];
        if($hasMovements && $visits->isNotEmpty()){
            $rows=DB::table('worker_daily_movements')->where('source','hourly_visit')->where('reference_type','hourly_visit')
                ->whereIn('reference_id',$visits->pluck('visit_id'))->whereNotNull('in_at')
                ->when($maidId!==null,fn($q)=>$q->where('maid_id',$maidId))->get(['maid_id','reference_id']);
            foreach($rows as $row)$returned[$row->maid_id.':'.$row->reference_id]=true;
        }
        $blocked=[];
        foreach($visits as $visit){
            if(!self::isOverdue($visit,$cutoff))continue;
            $metadata=json_decode($visit->booking_metadata??'{}',true)??[];
            // Emergency return deliberately leaves the execution/financial status open.
            if(!empty($metadata['service_disruptions'][$visit->visit_id]['return_recorded_at'])
                || isset($returned[$visit->maid_id.':'.$visit->visit_id]))continue;
            $blocked[(int)$visit->maid_id]??=['visit_id'=>(int)$visit->visit_id,'ended_at'=>$visit->scheduled_end_at];
        }
        if($hasMovements){
            // An unclosed physical movement still holds the worker even if the order/visit was closed or unassigned.
            $open=DB::table('worker_daily_movements as m')->leftJoin('hourly_visits as v','v.id','=','m.reference_id')
                ->where('m.source','hourly_visit')->where('m.reference_type','hourly_visit')->whereNull('m.in_at')
                ->when($maidId!==null,fn($q)=>$q->where('m.maid_id',$maidId))
                ->orderBy('m.out_at')->get(['m.maid_id','m.reference_id','m.out_at','v.scheduled_end_at','v.scheduled_start_at','v.planned_hours',
                    $hasArrival?DB::raw('(select aw.arrived_at from hourly_visit_workers aw where aw.hourly_visit_id = m.reference_id and aw.maid_id = m.maid_id limit 1) as arrived_at'):DB::raw('NULL as arrived_at')]);
            foreach($open as $movement){
                if(!self::isOverdue($movement,$cutoff))continue;
                $blocked[(int)$movement->maid_id]??=['visit_id'=>(int)$movement->reference_id,'ended_at'=>$movement->scheduled_end_at];
            }
        }
        return $blocked;
    }

    private static function isOverdue(object $row,\Carbon\Carbon $cutoff): bool
    {
        if(!empty($row->arrived_at) && (float)$row->planned_hours>0){
            $visit=(new \App\Models\Hourly\HourlyVisit)->forceFill(['scheduled_start_at'=>$row->scheduled_start_at,'planned_hours'=>$row->planned_hours]);
            return ServiceTime::expectedEnd($visit,$row)?->lte($cutoff)??false;
        }
        $until=$row->scheduled_end_at??$row->out_at??null;
        return $until ? \Carbon\Carbon::parse($until)->lte($cutoff) : false;
    }

    public static function reason(int $maidId): ?string
    {
        $pending=self::pending($maidId)[$maidId]??null;
        return $pending ? 'غير متاحة للحجز — عودة متأخرة تحتاج مراجعة؛ لم تُؤكد العودة الفعلية من الزيارة #'.$pending['visit_id'].'.' : null;
    }

    public static function exclude($query)
    {
        $ids=array_keys(self::pending());
        return $ids ? $query->whereNotIn('maids.id',$ids) : $query;
    }
}
