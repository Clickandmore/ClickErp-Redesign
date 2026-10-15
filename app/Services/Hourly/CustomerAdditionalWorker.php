<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder,HourlyVisit};
use Illuminate\Support\Facades\DB;

class CustomerAdditionalWorker
{
    private function valid(HourlyRentalOrder $order,HourlyVisit $visit): void
    {
        if(!in_array($order->status,['active','pending_confirmation'],true) || ($order->status==='pending_confirmation' && $order->expires_at?->isPast())
            || $visit->status!=='scheduled' || !$visit->scheduled_start_at?->isFuture())
            HourlyOrderService::reject('إضافة عاملة متاحة فقط لزيارة قادمة لم يبدأ تنفيذها.');
        if($visit->is_free)HourlyOrderService::reject('إضافة عاملة للزيارة المجانية تحتاج مراجعة الإدارة.');
        if($visit->workers_count>=20)HourlyOrderService::reject('الحد الأقصى 20 عاملة للزيارة.');
        foreach(['deliveryDriver','pickupDriver'] as $role){
            $driver=$visit->$role;
            if($driver && $driver->vehicle_capacity && $driver->vehicle_capacity<$visit->workers_count+1)
                HourlyOrderService::reject('سعة مركبة السائق لا تكفي بعد الزيادة؛ راجع الإدارة لترتيب التوصيل.');
        }
    }
    public function preview(HourlyRentalOrder $order,HourlyVisit $visit,bool $lock=false): array
    {
        $this->valid($order,$visit);
        $assigned=$visit->workers()->pluck('maids.id')->all();
        $choices=app(OrderManagementService::class)->availableWorkers($order,$visit,$lock)->whereNotIn('id',$assigned)->values();
        if($choices->isEmpty())HourlyOrderService::reject('لا توجد عاملة إضافية متاحة لهذا الموعد.');
        $canChoose=(bool)app(HourlyCategorySettings::class)->value($order->package_snapshot,'customer_worker_selection');
        $fee=round((float)$visit->visit_price/max(1,(int)$visit->workers_count),2);
        return ['visit_id'=>$visit->id,'order_id'=>$order->id,'current_workers'=>(int)$visit->workers_count,
            'new_workers'=>(int)$visit->workers_count+1,'current_price'=>(float)$visit->visit_price,'additional_fee'=>$fee,
            'new_price'=>round((float)$visit->visit_price+$fee,2),'order_total'=>(float)$order->total_amount,
            'new_order_total'=>round((float)$order->total_amount+$fee,2),'can_choose_worker'=>$canChoose,
            'workers'=>$canChoose?$choices->map(fn($m)=>['id'=>$m->id,'name'=>$m->name])->all():[],
            'automatic_worker_id'=>(int)$choices->first()->id];
    }
    public function add(HourlyRentalOrder $candidate,int $visitId,array $data,int $user): array
    {
        return DB::transaction(function()use($candidate,$visitId,$data,$user){
            \App\Models\ingaz\User::whereKey($user)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($candidate->id)->where('user_id',$user)->lockForUpdate()->firstOrFail();
            $visit=$order->visits()->whereKey($visitId)->lockForUpdate()->firstOrFail();
            $key=hash('sha256',$user.':'.$data['request_token']);
            $fingerprint=hash('sha256',json_encode([$visitId,$data['worker_id']??null,(int)$data['expected_workers'],round($data['expected_price'],2),round($data['expected_total'],2)]));
            $meta=$order->booking_metadata??[];$old=$meta['worker_additions'][$key]??null;
            if($old){if($old['fingerprint']!==$fingerprint)HourlyOrderService::reject('معرّف التأكيد مستخدم لعملية أخرى.');return $old['result']+['replayed'=>true];}
            DB::table('hourly_teams')->where('id',$visit->hourly_team_id)->lockForUpdate()->first();
            $quote=$this->preview($order,$visit,true);
            if($quote['current_workers']!==(int)$data['expected_workers'] || abs($quote['current_price']-$data['expected_price'])>0.001 || abs($quote['order_total']-$data['expected_total'])>0.001)
                HourlyOrderService::reject('تغيرت بيانات الزيارة أو السعر؛ اعرض السعر الجديد ثم أكد مرة أخرى.');
            $worker=$quote['can_choose_worker']?($data['worker_id']??null):$quote['automatic_worker_id'];
            if(!$worker || ($quote['can_choose_worker'] && !collect($quote['workers'])->contains('id',(int)$worker)))HourlyOrderService::reject('اختر عاملة متاحة من القائمة.');
            $lastInstallment=null;
            if(app(PaymentScheduleService::class)->policy($order)==='installments'){
                $lastInstallment=DB::table('hourly_order_installments')->where('hourly_rental_order_id',$order->id)->orderByDesc('due_date')->orderByDesc('id')->lockForUpdate()->first();
                if(!$lastInstallment)HourlyOrderService::reject('راجع الإدارة لاستكمال جدول الأقساط قبل تعديل الزيارة.');
            }
            app(OrderManagementService::class)->apply($order,['action'=>'add_worker','visit_ids'=>[$visit->id],
                'worker_id'=>(int)$worker,'additional_fee'=>$quote['additional_fee'],'increase_required_count'=>true,
                'expected_workers'=>$quote['current_workers'],'reason'=>'إضافة عاملة بطلب العميل #'.$user.' بعد مراجعة السعر'],null);
            $order->refresh();$visit->refresh();
            if($lastInstallment){
                DB::table('hourly_order_installments')->where('id',$lastInstallment->id)->update(['amount'=>round($lastInstallment->amount+$quote['additional_fee'],2),'updated_at'=>now()]);
                app(PaymentScheduleService::class)->syncInstallments($order);
            }
            $result=['order_id'=>$order->id,'visit_id'=>$visit->id,'workers_count'=>(int)$visit->workers_count,
                'visit_price'=>(float)$visit->visit_price,'additional_fee'=>$quote['additional_fee'],'total_amount'=>(float)$order->total_amount];
            $meta=$order->booking_metadata??[];$meta['worker_additions'][$key]=['fingerprint'=>$fingerprint,'result'=>$result];
            $order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'customer_add_worker',[],['user_id'=>$user,'request_key'=>$key]+$result,null,$visit->id,'موافقة العميل على إضافة عاملة وتكلفتها');
            return $result+['replayed'=>false];
        });
    }
}