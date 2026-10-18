<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

class MissedVisitRecovery
{
    public static function eligible(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        return ((($visit->is_missed || self::coverageRecovery($visit,$order)) && (float)$order->paid_amount>0)
            || ServiceIncident::recoverable($order,$visit))
            && in_array($order->status,['active','pending_confirmation','expired'],true);
    }

    public static function coverageUnavailable(HourlyRentalOrder $order): bool
    {
        $category=$order->serviceCategory;
        if (!$category) return false;
        try {
            app(HourlyBookingRules::class)->assertDistrictCovered($category,(int)$order->city_id,
                $order->latitude!==null?(float)$order->latitude:null,$order->longitude!==null?(float)$order->longitude:null,
                $order->district,$order->service_district_id ? (int)$order->service_district_id : null);
            return false;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return true;
        }
    }

    public static function coverageRecovery(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        if (!in_array($visit->status,['unscheduled','expired'],true) || $visit->scheduled_start_at) return false;
        $pending=$order->booking_metadata['missed_visit_requests'][$visit->id]??[];
        return (($pending['reason_code']??null)==='coverage_unavailable' && ($pending['status']??null)==='pending')
            || self::coverageUnavailable($order);
    }

    public function request(HourlyRentalOrder $candidate, int $visitId, array $data): array
    {
        return DB::transaction(function()use($candidate,$visitId,$data){
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $visit=$order->visits()->lockForUpdate()->findOrFail($visitId);
            $meta=$order->booking_metadata??[]; $old=$meta['missed_visit_requests'][$visitId]??null;
            if($old && $old['id']===$data['request_token']) return $old;
            if(!self::eligible($visit,$order)) HourlyOrderService::reject('طلب المعالجة متاح لزيارة مدفوعة فات موعدها ولم يبدأ تنفيذها.');
            if($old && $old['status']==='pending') HourlyOrderService::reject('يوجد طلب معالجة قيد المراجعة لهذه الزيارة.');
            $request=['id'=>$data['request_token'],'visit_id'=>$visitId,'type'=>$data['type'],'status'=>'pending',
                'reason_code'=>ServiceIncident::recoverable($order,$visit)?'service_incident':(self::coverageRecovery($visit,$order)?'coverage_unavailable':'missed_visit'),
                'disruption_id'=>ServiceIncident::state($order,$visitId)['id']??null,
                'requested_start'=>$data['requested_start']??null,'note'=>$data['note']??null,'requested_at'=>now()->toIso8601String()];
            $meta['missed_visit_requests'][$visitId]=$request; $order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'customer_missed_visit_request',[], $request,null,$visitId,'طلب العميل معالجة زيارة لم تنفذ');
            return $request;
        });
    }

    public function review(HourlyRentalOrder $candidate, int $visitId, array $data, int $admin): array
    {
        return DB::transaction(function()use($candidate,$visitId,$data,$admin){
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $visit=$order->visits()->lockForUpdate()->findOrFail($visitId);
            $meta=$order->booking_metadata??[]; $request=$meta['missed_visit_requests'][$visitId]??null;
            if(!$request || $request['id']!==$data['request_id']) HourlyOrderService::reject('تغير طلب المعالجة؛ أعد تحميل الصفحة.');
            if($request['status']!=='pending') return $request;
            $before=$request;
            if($data['decision']==='approve'){
                if(empty($data['verified_non_execution'])) HourlyOrderService::reject('أكد التحقق من عدم تنفيذ الخدمة ومسؤولية الشركة قبل الاعتماد.');
                if(!self::eligible($visit,$order)) HourlyOrderService::reject('تغيرت حالة الزيارة أو الدفع؛ لا يمكن اعتماد الطلب.');
                $incident=($request['reason_code']??null)==='service_incident';
                if($incident && (!ServiceIncident::recoverable($order,$visit)
                    || $request['disruption_id']!==(ServiceIncident::state($order,$visitId)['id']??null)))
                    HourlyOrderService::reject('تغيرت معالجة البلاغ؛ حدّث الصفحة قبل الاعتماد.');
                if(Schema::hasTable('worker_daily_movements') && DB::table('worker_daily_movements')->where('source','hourly_visit')
                    ->where('reference_type','hourly_visit')->where('reference_id',$visitId)->whereNull('in_at')->exists())
                    HourlyOrderService::reject('توجد حركة خروج غير مغلقة؛ تحقق من التنفيذ والعودة أولًا.');
                $expired=$order->status==='expired';
                if($expired) $order->update(['status'=>'active']);
                // Restore only this unused visit; completed visits and their charges remain intact.
                if($visit->status==='expired' && !$visit->scheduled_start_at && ($request['reason_code']??null)==='coverage_unavailable')
                    $visit->update(['status'=>'unscheduled']);
                $manager=app(OrderManagementService::class);
                if($request['type']==='reschedule'){
                    if (($request['reason_code']??null)==='coverage_unavailable' && self::coverageUnavailable($order))
                        HourlyOrderService::reject('لا يمكن اعتماد موعد قبل إعادة توفير الخدمة في منطقة العميل.');
                    $start=Carbon::parse($data['new_start']??$request['requested_start']);
                    if(!$start->isFuture()) HourlyOrderService::reject('اختر موعدًا مستقبليًا.');
                    $end=$start->copy()->addMinutes((int)round($visit->planned_hours*60));
                    if($end->gt($order->expires_at)) $order->update(['expires_at'=>$end->copy()->endOfDay()]);
                    if($incident){
                        $failed=ServiceIncident::state($order,$visitId)['failed_worker_ids'];
                        $visit->workers()->detach($failed);
                        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visitId)->delete();
                        $visit->update(['status'=>'unscheduled','scheduled_start_at'=>null,'scheduled_end_at'=>null,'scheduled_date'=>null]);
                        app(HourlyOrderService::class)->reschedule($visit,$start->format('Y-m-d H:i'),$admin,true,true);
                        $manager->apply($order,['action'=>'assign_all','visit_ids'=>[$visitId],'reason'=>$data['reason']],$admin,false,true);
                    } elseif (($request['reason_code']??null)==='coverage_unavailable') {
                        app(HourlyOrderService::class)->reschedule($visit,$start->format('Y-m-d H:i'),$admin,true);
                    } else {
                        $manager->apply($order,['action'=>'reschedule','visit_ids'=>[$visitId],
                            'starts_at'=>$start->format('Y-m-d H:i'),'reason'=>$data['reason']],$admin);
                    }
                    $request['new_start']=$start->toIso8601String();
                }else{
                    $allocation=app(VisitPaymentAllocation::class)->forOrder($order);
                    $amount=round(min((float)$visit->visit_price,(float)($allocation[$visitId]['paid']??0)),2);
                    if($amount<=0 && !$incident) HourlyOrderService::reject('لا يوجد مبلغ مسدد مخصص لهذه الزيارة؛ راجع توزيع الدفعات.');
                    $manager->apply($order,['action'=>'cancel_visit','visit_ids'=>[$visitId],'refund_method'=>'wallet','reason'=>$data['reason']],$admin,true,$incident);
                    $request['refunded_amount']=$amount>0?app(OrderRefundService::class)->refund($order,'wallet',$data['reason'],$admin,'admin',$admin,false,$amount):0;
                    if($expired && (in_array($order->fresh()->status,['active','pending_confirmation'],true)
                        || (($request['reason_code']??null)==='coverage_unavailable' && $order->visits()->where('status','expired')->whereNull('scheduled_start_at')->exists())))
                        $order->update(['status'=>'expired']);
                }
                $request['status']='approved';
            }else $request['status']='rejected';
            $request+=['reviewed_by'=>$admin,'reviewed_at'=>now()->toIso8601String(),'review_reason'=>$data['reason']];
            $order->refresh(); $meta=$order->booking_metadata??[]; $meta['missed_visit_requests'][$visitId]=$request;
            if($request['status']==='approved' && ($request['reason_code']??null)==='service_incident'){
                $meta['service_disruptions'][$visitId]['status']='resolved';
                $meta['service_disruptions'][$visitId]['resolved_at']=now()->toIso8601String();
                DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->notify($visitId,'service_resolved'));
            }
            $order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'review_missed_visit_request',$before,$request,$admin,$visitId,$data['reason']);
            return $request;
        });
    }
}
