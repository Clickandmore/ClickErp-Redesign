<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyCategoryClosure, HourlyRentalOrder, HourlyVisit};
use Illuminate\Support\Facades\Schema;

class ClosureImpact
{
    public function visits(HourlyCategoryClosure $closure,?int $orderId=null)
    {
        return HourlyVisit::with('order')->where('scheduled_start_at','<',$closure->ends_at)
            ->when($orderId,fn($q)=>$q->where('hourly_rental_order_id',$orderId))
            ->where('scheduled_end_at','>',$closure->starts_at)
            ->whereNotIn('status',CustomerVisitActions::CLOSED)
            ->whereHas('order',fn($q)=>$q->where('service_category_id',$closure->service_category_id)
                ->whereIn('status',['active','pending_confirmation']))->get()
            ->filter(fn($visit)=>$this->applies($closure,$visit,$visit->order))->values();
    }

    public function applies(HourlyCategoryClosure $closure,HourlyVisit $visit,HourlyRentalOrder $order): bool
    {
        return (int)$order->service_category_id===(int)$closure->service_category_id
            && empty($order->package_snapshot['ignore_closed_days'])
            && $closure->coversDistrict($order->service_district_id?(int)$order->service_district_id:null)
            && $visit->scheduled_start_at && $visit->scheduled_end_at
            && $closure->starts_at->lt($visit->scheduled_end_at) && $closure->ends_at->gt($visit->scheduled_start_at);
    }

    public function conflict(HourlyVisit $visit,?HourlyRentalOrder $order=null): ?array
    {
        $order??=$visit->order;
        if(!$order || !in_array($order->status,['active','pending_confirmation','expired'],true)
            || in_array($visit->status,CustomerVisitActions::CLOSED,true) || !$visit->scheduled_start_at || !$visit->scheduled_end_at
            || !Schema::hasTable('hourly_category_closures'))return null;
        $closure=HourlyCategoryClosure::where('service_category_id',$order->service_category_id)
            ->where('starts_at','<',$visit->scheduled_end_at)->where('ends_at','>',$visit->scheduled_start_at)
            ->orderBy('id')->get()->first(fn($closure)=>$this->applies($closure,$visit,$order));
        if(!$closure)return null;
        return ['closure_id'=>$closure->id,'starts_at'=>$closure->starts_at->toIso8601String(),
            'ends_at'=>$closure->ends_at->toIso8601String(),'reason'=>$closure->reason,
            'message'=>'موعد هذه الزيارة يتعارض مع إغلاق الخدمة / إجازة. يرجى إعادة الجدولة إلى موعد متاح أو طلب الإلغاء دون رسوم بعد مراجعة الإدارة.',
            'can_request_resolution'=>!$visit->has_started_execution && $visit->status==='scheduled'];
    }
}
