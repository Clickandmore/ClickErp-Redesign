<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyVisit,HourlyRentalOrder};
use App\Models\ingaz\Maid;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;

class CashCollectionService
{
    public static function ready(): bool { return Schema::hasTable('hourly_cash_collections') && Schema::hasColumn('hourly_visits','cash_collector_type'); }
    private static function cents($amount): int { return (int)round((float)$amount*100); }
    public function collector(HourlyVisit $visit): array {
        $type=$visit->cash_collector_type ?: 'auto';$id=null;
        if($type==='auto') {
            if($visit->delivery_driver_id){$type='driver';}
            else {
                $workers=$visit->workers;
                $type=$workers->count()===1 && !(bool)($workers->first()->hourly_settings['requires_driver'] ?? true)?'worker':'admin';
            }
        }
        if($type==='driver')$id=$visit->delivery_driver_id;
        if($type==='worker')$id=$visit->cash_collector_worker_id ?: ($visit->workers->count()===1?$visit->workers->first()->id:null);
        return ['type'=>$type,'id'=>$id?(int)$id:null,'label'=>['driver'=>'السائق','worker'=>'العاملة','admin'=>'موظف التحصيل'][$type] ?? 'غير محدد'];
    }
    public function info(HourlyVisit $visit,string $guard='',?int $id=null): array {
        if(!self::ready())return ['enabled'=>false,'can_collect'=>false];
        $collector=$this->collector($visit);$order=$visit->order;
        $financial=app(PaymentScheduleService::class)->forOrder($order);
        $due=app(PaymentScheduleService::class)->collectible($order,$visit);
        $allowed=in_array($order->payment_method,['cash','cash_and_card'],true) && in_array($order->status,['active','completed'],true)
            && !in_array($visit->status,['unscheduled','scheduled','cancelled','cancelled_by_client','expired','no_show'],true);
        return ['enabled'=>true,'collector'=>$collector,'cash_due'=>$due,'financial'=>$financial['visits'][$visit->id]??null,'can_collect'=>$allowed && !($guard==='worker' && $visit->status==='en_route') && $due>0 && $collector['type']===$guard && $collector['id']===$id];
    }
    public function workerCollect(Maid $maid,int $workerVisitId,float $amount,string $token): array {
        $row=DB::table('hourly_visit_workers')->where('id',$workerVisitId)->where('maid_id',$maid->id)->first();
        abort_unless($row,404);
        return $this->collect(HourlyVisit::findOrFail($row->hourly_visit_id),'worker',(int)$maid->id,$amount,$token);
    }
    public function collect(HourlyVisit $candidate,string $holder,int $holderId,float $amount,string $token): array {
        abort_unless(self::ready(),503,'يجب تفعيل ترحيل العهد النقدية أولًا.');
        return DB::transaction(function()use($candidate,$holder,$holderId,$amount,$token){
            $order=HourlyRentalOrder::whereKey($candidate->hourly_rental_order_id)->lockForUpdate()->firstOrFail();
            $visit=HourlyVisit::whereKey($candidate->id)->lockForUpdate()->firstOrFail();$visit->setRelation('order',$order);
            $key=hash('sha256',$holder.':'.$holderId.':'.$token);
            $old=DB::table('hourly_cash_collections')->where('request_key',$key)->first();
            if($old){
                if((int)$old->visit_id!==(int)$visit->id || self::cents($old->amount)!==self::cents($amount))HourlyOrderService::reject('معرّف التحصيل مستخدم لعملية مختلفة.');
                return $this->result($old,true);
            }
            $collector=$this->collector($visit);
            if($collector['type']!==$holder || $collector['id']!==$holderId)HourlyOrderService::reject('لست مسؤول التحصيل لهذه الزيارة.');
            if($holder==='worker' && !DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->where('maid_id',$holderId)->exists())abort(403);
            if(!in_array($order->payment_method,['cash','cash_and_card'],true))HourlyOrderService::reject('هذا الطلب لا يقبل تحصيلًا نقديًا.');
            if(!in_array($order->status,['active','completed'],true) || in_array($visit->status,['unscheduled','scheduled','cancelled','cancelled_by_client','expired','no_show'],true))HourlyOrderService::reject('الزيارة لا تقبل التحصيل قبل الوصول أو بعد الإلغاء.');
            if($holder==='worker' && $visit->status==='en_route')HourlyOrderService::reject('يجب تسجيل وصول العاملة قبل التحصيل.');
            $cents=self::cents($amount);$due=self::cents(app(PaymentScheduleService::class)->collectible($order,$visit));
            if($cents<=0 || $cents>$due)HourlyOrderService::reject('المبلغ يجب أن يكون أكبر من صفر ولا يتجاوز المتبقي على الطلب.');
            $payment=$order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),'method'=>'cash',
                'amount'=>$cents/100,'status'=>'paid','paid_at'=>now(),'collected_by_type'=>$holder==='worker'?'maid':'driver','collected_by_id'=>$holderId,
                'provider_payload'=>['type'=>'staff_cash','visit_id'=>$visit->id,'request_key'=>$key,'visit_allocations'=>app(PaymentScheduleService::class)->allocations($order,$cents,(int)$visit->id)]]);
            $id=DB::table('hourly_cash_collections')->insertGetId(['payment_id'=>$payment->id,'order_id'=>$order->id,'visit_id'=>$visit->id,
                'holder_type'=>$holder,'holder_id'=>$holderId,'amount'=>$cents/100,'remitted_amount'=>0,'request_key'=>$key,'created_at'=>now(),'updated_at'=>now()]);
            app(HourlyPaymentService::class)->recalculate($order,$holder,$holderId);
            app(OrderManagementService::class)->audit($order,'cash_collect',[],['amount'=>$cents/100,'collection_id'=>$id,'payment_id'=>$payment->id,'holder_type'=>$holder,'holder_id'=>$holderId],null,$visit->id,'تحصيل نقدي وإثبات عهدة');
            return $this->result(DB::table('hourly_cash_collections')->find($id),false);
        });
    }
    private function result(object $row,bool $replayed): array {
        return ['collection_id'=>(int)$row->id,'payment_id'=>(int)$row->payment_id,'receipt_number'=>'HC-'.str_pad($row->payment_id,8,'0',STR_PAD_LEFT),
            'amount'=>(float)$row->amount,'custody_due'=>max(0,self::cents($row->amount)-self::cents($row->remitted_amount))/100,'replayed'=>$replayed];
    }
    public function custody(string $holder,int $id): array {
        if(!self::ready())return ['enabled'=>false,'outstanding'=>0,'collections'=>[]];
        $query=DB::table('hourly_cash_collections')->where('holder_type',$holder)->where('holder_id',$id);
        $due=(clone $query)->selectRaw('COALESCE(SUM(amount-remitted_amount),0) as due')->value('due');
        return ['enabled'=>true,'outstanding'=>(float)$due,'collections'=>(clone $query)->orderByDesc('id')->limit(100)->get()->map(fn($r)=>$this->result($r,false)+['order_id'=>(int)$r->order_id,'visit_id'=>(int)$r->visit_id,'remitted_amount'=>(float)$r->remitted_amount])->all()];
    }
    public function remit(int $id,float $amount,int $admin,string $reference,string $token): array {
        abort_unless(self::ready(),503);
        return DB::transaction(function()use($id,$amount,$admin,$reference,$token){
            $row=DB::table('hourly_cash_collections')->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);
            $key=hash('sha256','remit:'.$admin.':'.$token);
            $old=DB::table('hourly_cash_remittances')->where('request_key',$key)->first();
            if($old){if((int)$old->collection_id!==$id || self::cents($old->amount)!==self::cents($amount) || $old->reference!==$reference)HourlyOrderService::reject('معرّف التوريد مستخدم لعملية مختلفة.');return ['remittance_id'=>(int)$old->id,'replayed'=>true];}
            $cents=self::cents($amount);$due=self::cents($row->amount)-self::cents($row->remitted_amount);
            if($cents<=0 || $cents>$due)HourlyOrderService::reject('قيمة التوريد تتجاوز العهدة المتبقية أو غير صالحة.');
            $remit=DB::table('hourly_cash_remittances')->insertGetId(['collection_id'=>$id,'admin_id'=>$admin,'amount'=>$cents/100,'reference'=>$reference,'request_key'=>$key,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('hourly_cash_collections')->where('id',$id)->update(['remitted_amount'=>(self::cents($row->remitted_amount)+$cents)/100,'updated_at'=>now()]);
            // A remittance settles custody; it must never create a second customer payment.
            app(OrderManagementService::class)->audit(HourlyRentalOrder::findOrFail($row->order_id),'cash_remit',[],['collection_id'=>$id,'remittance_id'=>$remit,'amount'=>$cents/100,'reference'=>$reference],$admin,$row->visit_id,'استلام عهدة نقدية من الطاقم');
            return ['remittance_id'=>$remit,'replayed'=>false,'remaining'=>($due-$cents)/100];
        });
    }
}
