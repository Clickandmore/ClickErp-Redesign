<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyRentalOrder;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\DB;

class WorkerReplacement
{
    public function allowed(HourlyRentalOrder $order): bool
    {
        return !app(HourlyCategorySettings::class)->value((array)$order->package_snapshot,'customer_worker_selection');
    }

    public function replace(HourlyRentalOrder $candidate,int $visitId,array $data,int $admin,bool $customerChoice=false): void
    {
        DB::transaction(function()use($candidate,$visitId,$data,$admin,$customerChoice){
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $visit=$order->visits()->lockForUpdate()->findOrFail($visitId);
            $state=ServiceIncident::state($order,$visitId);
            if($customerChoice && (!ServiceIncident::recoverable($order,$visit) || $this->allowed($order)))
                HourlyOrderService::reject('اختيار العميل للبديلة غير متاح.');
            if(!$customerChoice && !$this->allowed($order)) HourlyOrderService::reject('اختيار العاملة مفعّل للعميل؛ يلزم التنسيق معه قبل تغيير اختياره.');
            if(($order->booking_metadata['missed_visit_requests'][$visitId]['status']??null)==='pending')
                HourlyOrderService::reject('يوجد طلب معالجة للزيارة قيد المراجعة؛ راجعه قبل الاستبدال.');
            $orderReady=$order->status==='active' || ($order->status==='pending_confirmation' && ServiceIncident::recoverable($order,$visit));
            if(!$orderReady || $visit->status!=='scheduled' || !$visit->scheduled_start_at?->isFuture())
                HourlyOrderService::reject('الاستبدال المباشر متاح للزيارة القادمة قبل بدء التوصيل فقط؛ راجع الإدارة لمعالجة الزيارات الجارية أو الفائتة.');
            if(DB::getSchemaBuilder()->hasTable('worker_daily_movements') && DB::table('worker_daily_movements')
                ->where('source','hourly_visit')->where('reference_type','hourly_visit')->where('reference_id',$visitId)->whereNull('in_at')->exists())
                HourlyOrderService::reject('لا يمكن الاستبدال أثناء وجود حركة خروج مفتوحة للزيارة.');
            $old=(int)$data['worker_id'];$new=(int)$data['replacement_worker_id'];
            $ids=$visit->workers()->pluck('maids.id')->map(fn($id)=>(int)$id)->all();
            // A repeated submit is harmless only if it repeats this recorded replacement.
            $last=$order->booking_metadata['worker_replacements'][$visitId]??[];
            if(!in_array($old,$ids,true) && in_array($new,$ids,true)
                && ($last['old_worker_id']??null)===$old && ($last['new_worker_id']??null)===$new) return;
            if(!in_array($old,$ids,true) || in_array($new,$ids,true) || $old===$new)
                HourlyOrderService::reject('تغيّر تعيين العاملات؛ حدّث الصفحة واختر العاملة المعيّنة وبديلتها.');
            $next=array_map(fn($id)=>$id===$old?$new:$id,$ids);
            $before=['worker_ids'=>$ids,'visit_price'=>$visit->visit_price,'workers_count'=>$visit->workers_count];
            // Existing assignment validates service attributes, availability, team, capacity and routes.
            app(OrderManagementService::class)->apply($order,['action'=>'assign_workers','visit_ids'=>[$visitId],
                'worker_ids'=>$next,'expected_worker_ids'=>$ids,'reason'=>$data['reason']],$admin,false,true);
            $order->refresh();$meta=$order->booking_metadata??[];
            if(ServiceIncident::recoverable($order,$visit)){
                $state=$meta['service_disruptions'][$visitId];
                $state['failed_worker_ids']=array_values(array_diff($state['failed_worker_ids'],[$old]));
                $state['status']=$state['failed_worker_ids']?($customerChoice?'awaiting_customer':'awaiting_admin'):'resolved';
                $state['chosen_worker_id']=$new;$state['resolved_at']=now()->toIso8601String();
                $meta['service_disruptions'][$visitId]=$state;
            }
            $meta['worker_replacements'][$visitId]=['old_worker_id'=>$old,'new_worker_id'=>$new,'replaced_at'=>now()->toIso8601String(),'admin_id'=>$admin];
            $order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'worker_replaced',$before,
                ['worker_ids'=>$next,'visit_price'=>$visit->fresh()->visit_price,'workers_count'=>$visit->workers_count],$admin,$visitId,$data['reason']);
            DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->notify($visitId,'worker_replaced'));
        });
    }
}
