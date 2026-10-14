<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyVisit,HourlyRentalOrder};
use App\Models\ingaz\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VisitBatchService
{
    public const ACTIONS=['assign_workers'=>'تعيين عاملة','unassign_worker'=>'إلغاء تعيين عاملة','unassign_workers'=>'إلغاء تعيين العاملات','unassign_driver'=>'إزالة سائق المهمة','assign_all'=>'تعيين العاملات تلقائيًا','auto_driver'=>'تعيين السائق تلقائيًا','confirm_return'=>'تأكيد العودة للإيواء يدويًا','complete'=>'إكمال الزيارات','cancel_visit'=>'إلغاء الزيارات','collect'=>'إضافة دفعة جماعية','unassign_drivers'=>'إزالة السائقين','clear_driver_routes'=>'إزالة مسارات السائقين','unassign_all'=>'إزالة العاملات والسائقين','reschedule'=>'تغيير التاريخ','start_time'=>'تغيير وقت البدء','duration'=>'تغيير المدة','price'=>'تغيير السعر','assign_driver'=>'تعيين سائق','team_leader'=>'تعيين قائد الفريق','note'=>'إضافة مذكرة','offer'=>'عرض للعاملات'];
    public static function rules(): array
    {
        return ['batch_id'=>'required|uuid','action'=>['required',Rule::in(array_keys(self::ACTIONS))],
            'visit_ids'=>'required|array|min:1|max:100','visit_ids.*'=>'required|integer|distinct','reason'=>'required|string|max:2000',
            'worker_ids'=>'required_if:action,assign_workers|array|min:1|max:20','worker_ids.*'=>'integer|distinct',
            'expected_worker_ids'=>'sometimes|array|max:20','expected_worker_ids.*'=>'integer|distinct',
            'worker_id'=>'required_if:action,unassign_worker|integer',
            'shift_days'=>'exclude_unless:action,reschedule|required_without:new_date|nullable|integer|between:-365,365',
            'new_date'=>'exclude_unless:action,reschedule|required_without:shift_days|nullable|date_format:Y-m-d',
            'start_time'=>'required_if:action,start_time|nullable|date_format:H:i',
            'hours'=>'required_if:action,duration|nullable|numeric|between:0.5,24','ignore_break'=>'sometimes|boolean',
            'price'=>'required_if:action,price|nullable|numeric|between:0,9999999',
            'driver_direction'=>'required_if:action,auto_driver|nullable|in:delivery,pickup,together',
            'driver_id'=>'required_if:action,assign_driver|nullable|integer','driver_role'=>'required_if:action,assign_driver,unassign_driver|nullable|in:delivery,pickup','driver_time'=>'required_if:action,assign_driver|nullable|date_format:H:i',
            'leader_id'=>'required_if:action,team_leader|nullable|integer','offered'=>'required_if:action,offer|boolean',
            'refund_method'=>'required_if:action,cancel_visit|nullable|in:wallet,cash,bank_transfer,none',
            'notify_app'=>'sometimes|boolean','notify_email'=>'sometimes|boolean','send_receipt'=>'sometimes|boolean',
            'method'=>'required_if:action,collect|nullable|in:cash,card_on_delivery,bank_transfer,cheque',
            'amount'=>'required_if:action,collect|nullable|numeric|min:0.01|max:9999999',
            'paid_at'=>'required_if:action,collect|nullable|date_format:Y-m-d\TH:i|before_or_equal:now',
            'attachment'=>'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function run(array $data,int $admin): array
    {
        HourlyOrderService::enabled();
        $hash=hash('sha256',json_encode($data));
        return DB::transaction(function()use($data,$admin,$hash){
            DB::table('hourly_visit_batches')->insertOrIgnore(['id'=>$data['batch_id'],'admin_id'=>$admin,'action'=>$data['action'],'payload_hash'=>$hash,'created_at'=>now(),'updated_at'=>now()]);
            $batch=DB::table('hourly_visit_batches')->where('id',$data['batch_id'])->lockForUpdate()->first();
            if((int)$batch->admin_id!==$admin || $batch->payload_hash!==$hash)HourlyOrderService::reject('معرّف العملية مستخدم لطلب مختلف. أعد فتح النافذة.');
            if($batch->result)return json_decode($batch->result,true);
            $visits=HourlyVisit::whereIn('id',$data['visit_ids'])->orderBy('hourly_rental_order_id')->orderBy('id')->get();
            if($visits->count()!==count($data['visit_ids']))HourlyOrderService::reject('بعض الزيارات لم تعد موجودة. حدّث القائمة.');
            $orders=HourlyRentalOrder::whereIn('id',$visits->pluck('hourly_rental_order_id'))->orderBy('id')->lockForUpdate()->get();
            $visits=HourlyVisit::whereIn('id',$data['visit_ids'])->orderBy('id')->lockForUpdate()->get();
            $manager=app(OrderManagementService::class);$receipts=[];
            if($data['action']==='collect')$receipts=$this->collect($orders,$visits,$data,$admin);
            else foreach($orders as $order){
                $ids=$visits->where('hourly_rental_order_id',$order->id)->pluck('id')->all();
                if($data['action']==='note'){
                    $metadata=$order->booking_metadata??[];
                    foreach($ids as $id)$metadata['notes'][]=['id'=>(string)Str::uuid(),'text'=>$data['reason'],'visit_id'=>$id,'audience'=>'admin','admin_id'=>$admin,'author'=>auth('admin')->user()?->name??'الإدارة','created_at'=>now()->toDateTimeString()];
                    $order->update(['booking_metadata'=>$metadata]);
                    foreach($ids as $id)$manager->audit($order,'bulk_note',[],['batch_id'=>$data['batch_id']],$admin,$id,$data['reason']);
                }else{
                    $manager->apply($order,array_replace($data,['visit_ids'=>$ids]),$admin);
                    if($data['action']==='cancel_visit' && $data['refund_method']!=='none'){
                        $updated=$order->fresh();
                        $this->refund($updated,$data,$admin,max(0,(int)round(($order->total_amount-$updated->total_amount)*100)));
                    }
                }
            }
            foreach($orders->groupBy('user_id') as $userId=>$customerOrders){
                $customerVisits=$visits->whereIn('hourly_rental_order_id',$customerOrders->pluck('id'));
                $ids=$customerVisits->pluck('id')->join('، ');
                $body=self::ACTIONS[$data['action']].' — الزيارات: '.$ids.' — '.$data['reason'];
                if(isset($receipts[$userId]))$body='إيصال تحصيل #'.$receipts[$userId]['id'].' — المبلغ: '.$receipts[$userId]['amount'].' ر.س — '.$body;
                // وجهة الرابط العميق: تُملأ فقط حين يخص الإشعار طلبًا واحدًا أو زيارة واحدة، وإلا يفتح التطبيق قائمة الطلبات.
                $target=['order_id'=>$customerOrders->count()===1?(int)$customerOrders->first()->id:null,
                    'visit_id'=>$customerVisits->count()===1?(int)$customerVisits->first()->id:null];
                foreach(['app','email'] as $channel){
                    $enabled=!empty($data['notify_'.$channel]) || ($channel==='email' && $data['action']==='collect' && !empty($data['send_receipt']));
                    if(!$enabled)continue;
                    $notice=DB::table('hourly_visit_notices')->insertGetId($target+['batch_id'=>$data['batch_id'],'user_id'=>$userId,'channel'=>$channel,'title'=>self::ACTIONS[$data['action']],'body'=>$body,'created_at'=>now(),'updated_at'=>now()]);
                }
            }
            $result=['message'=>'تم تنفيذ الإجراء على '.$visits->count().' زيارة.','receipts'=>array_values($receipts),'batch_id'=>$data['batch_id']];
            DB::table('hourly_visit_batches')->where('id',$data['batch_id'])->update(['result'=>json_encode($result),'updated_at'=>now()]);
            return $result;
        });
    }

    private function collect($orders,$visits,array $data,int $admin): array
    {
        $remaining=(int)round($data['amount']*100);$limits=[];$dues=[];
        foreach($orders as $o){
            // Legacy payments belong to the order. Allocate them chronologically to visits for a bounded selection.
            $allocated=app(VisitPaymentAllocation::class)->forOrder($o);$selectedDue=0;
            foreach($o->visits()->orderBy('scheduled_start_at')->orderBy('id')->get() as $v){
                $cancelled=in_array($v->status,['cancelled','cancelled_by_client']);
                $price=(int)round(($cancelled?$v->cancellation_fee:$v->visit_price)*100);$used=(int)round(($allocated[$v->id]['paid']??0)*100);
                if(!$cancelled && $v->status!=='expired' && $visits->contains('id',$v->id)){$dues[$o->id][$v->id]=max(0,$price-$used);$selectedDue+=max(0,$price-$used);}
            }
            $limits[$o->id]=max(0,min($selectedDue,(int)round(($o->total_amount-$o->paid_amount)*100)));
        }
        if($remaining>array_sum($limits))HourlyOrderService::reject('المبلغ يتجاوز المتبقي للزيارات المحددة بعد توزيع السداد السابق حسب ترتيب الزيارات.');
        $receipts=[];
        foreach($orders as $o){
            $amount=min($remaining,$limits[$o->id]);if(!$amount)continue;
            $payment=app(HourlyPaymentService::class)->collect($o,['amount'=>$amount/100,'collection_method'=>$data['method'],'paid_at'=>$data['paid_at'],'attachment_path'=>$data['attachment_path']??null],$admin);
            $left=$amount;$allocations=[];
            foreach($dues[$o->id] as $id=>$due){$part=min($left,$due);if(!$part)continue;$allocations[$id]=$part;$left-=$part;app(OrderManagementService::class)->audit($o,'bulk_collect',[],['payment_id'=>$payment->id,'amount'=>$part/100,'batch_id'=>$data['batch_id']],$admin,$id,$data['reason']);}
            $payment->update(['provider_payload'=>['visit_allocations'=>$allocations,'batch_id'=>$data['batch_id']]]);
            $remaining-=$amount;
            $receipts[$o->user_id]['amount']=($receipts[$o->user_id]['amount']??0)+$amount;
            $receipts[$o->user_id]['details'][]=['order_id'=>$o->id,'order_number'=>$o->order_number,'payment_id'=>$payment->id,'amount'=>$amount/100,'visit_ids'=>$visits->where('hourly_rental_order_id',$o->id)->pluck('id')->all()];
            app(OrderManagementService::class)->audit($o,'bulk_collect',[],['payment_id'=>$payment->id,'batch_id'=>$data['batch_id']],$admin,null,$data['reason']);
        }
        foreach($receipts as $user=>$receipt){
            $receipts[$user]['amount']=$receipt['amount']/100;
            $receipts[$user]['id']=DB::table('hourly_visit_receipts')->insertGetId(['batch_id'=>$data['batch_id'],'user_id'=>$user,'amount'=>$receipt['amount']/100,'details'=>json_encode($receipt['details']),'method'=>$data['method'],'paid_at'=>$data['paid_at'],'description'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
        }
        return $receipts;
    }

    private function refund(HourlyRentalOrder $order,array $data,int $admin,int $cap): void
    {
        if($order->payments()->whereIn('status',['pending','creating'])->exists())HourlyOrderService::reject('يوجد دفع إلكتروني قيد التنفيذ، لا يمكن الاسترداد الآن.');
        $amount=min($cap,max(0,(int)round(($order->paid_amount-$order->total_amount)*100)));if(!$amount)return;
        $user=User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
        if($data['refund_method']==='wallet'){
            $balance=(int)round($user->hourly_wallet_balance*100)+$amount;
            $user->update(['hourly_wallet_balance'=>$balance/100]);
            DB::table('hourly_wallet_transactions')->insert(['user_id'=>$user->id,'hourly_rental_order_id'=>null,'amount'=>$amount/100,'balance_after'=>$balance/100,'admin_id'=>$admin,'created_at'=>now(),'updated_at'=>now()]);
        }
        $payment=$order->payments()->create(['user_id'=>$user->id,'public_id'=>(string)Str::uuid(),'method'=>$data['refund_method'],'amount'=>-$amount/100,'status'=>'paid','paid_at'=>now(),'collected_by_type'=>'admin','collected_by_id'=>$admin,'provider_payload'=>['batch_id'=>$data['batch_id'],'type'=>'refund','reason'=>$data['reason']]]);
        $order->update(['paid_amount'=>($order->paid_amount*100-$amount)/100]);
        app(OrderManagementService::class)->audit($order,'refund',[],['amount'=>$amount/100,'method'=>$data['refund_method'],'payment_id'=>$payment->id,'batch_id'=>$data['batch_id']],$admin,null,$data['reason']);
        foreach($data['visit_ids'] as $id)if($order->visits()->whereKey($id)->exists())app(OrderManagementService::class)->audit($order,'refund',[],['payment_id'=>$payment->id,'order_refund_amount'=>$amount/100],$admin,$id,$data['reason']);
    }
}
