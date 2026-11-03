<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyVisit,HourlyRentalOrder};
use App\Models\ingaz\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VisitBatchService
{
    public const ACTIONS=['assign_workers'=>'تعيين عاملة','unassign_worker'=>'إلغاء تعيين عاملة','unassign_workers'=>'إلغاء تعيين العاملات','unassign_driver'=>'إزالة سائق المهمة','assign_all'=>'تعيين العاملات تلقائيًا','auto_driver'=>'تعيين السائق تلقائيًا','confirm_return'=>'تأكيد العودة للإيواء يدويًا','complete'=>'إكمال الزيارات','cancel_visit'=>'إلغاء الزيارات','collect'=>'إضافة دفعة جماعية','unassign_drivers'=>'إزالة السائقين','clear_driver_routes'=>'إزالة مسارات السائقين','unassign_all'=>'إزالة العاملات والسائقين','reschedule'=>'تغيير التاريخ','start_time'=>'تغيير وقت البدء','duration'=>'تغيير المدة','price'=>'تغيير السعر','assign_driver'=>'تعيين سائق','team_leader'=>'تعيين قائد الفريق','note'=>'إضافة مذكرة','offer'=>'عرض للعاملات','confirmers'=>'من يؤكد الوصول والعودة'];
    public static function rules(): array
    {
        return ['batch_id'=>'required|uuid','action'=>['required',Rule::in(array_keys(self::ACTIONS))],
            'visit_ids'=>'required|array|min:1|max:100','visit_ids.*'=>'required|integer|distinct','reason'=>'required|string|max:2000',
            'worker_ids'=>'required_if:action,assign_workers|array|min:1|max:20','worker_ids.*'=>'integer|distinct',
            'expected_worker_ids'=>'sometimes|array|max:20','expected_worker_ids.*'=>'integer|distinct',
            'worker_id'=>'required_if:action,unassign_worker|integer',
            'shift_days'=>'exclude_unless:action,reschedule|required_without_all:new_date,starts_at|nullable|integer|between:-365,365',
            'new_date'=>'exclude_unless:action,reschedule|required_without_all:shift_days,starts_at|nullable|date_format:Y-m-d',
            'starts_at'=>['exclude_unless:action,reschedule','required_without_all:new_date,shift_days','nullable','date_format:Y-m-d\\TH:i'],
            'start_time'=>'required_if:action,start_time|nullable|date_format:H:i',
            'hours'=>'required_if:action,duration|nullable|numeric|between:0.5,24','ignore_break'=>'sometimes|boolean',
            'price'=>'required_if:action,price|nullable|numeric|between:0,9999999',
            'driver_direction'=>'required_if:action,auto_driver|nullable|in:delivery,pickup,together',
            'arrival_confirmer'=>['required_if:action,confirmers','nullable',Rule::in(array_keys(VisitConfirmation::MODES))],
            'return_confirmer'=>['required_if:action,confirmers','nullable',Rule::in(array_keys(VisitConfirmation::MODES))],
            'driver_id'=>'required_if:action,assign_driver|nullable|integer','driver_role'=>'required_if:action,assign_driver,unassign_driver|nullable|in:delivery,pickup','driver_time'=>'required_if:action,assign_driver|nullable|date_format:H:i',
            'leader_id'=>'required_if:action,team_leader|nullable|integer','offered'=>'required_if:action,offer|boolean',
            'refund_method'=>'required_if:action,cancel_visit|nullable|in:wallet,cash,bank_transfer,none',
            'external_refund_confirmed'=>'sometimes|boolean',
            'notify_app'=>'sometimes|boolean','notify_email'=>'sometimes|boolean','send_receipt'=>'sometimes|boolean',
            'method'=>'required_if:action,collect|nullable|in:cash,card_on_delivery,bank_transfer,cheque',
            'amount'=>'required_if:action,collect|nullable|numeric|min:0.01|max:9999999',
            'paid_at'=>'required_if:action,collect|nullable|date_format:Y-m-d\TH:i|before_or_equal:now',
            'attachment'=>'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    /** زيارة بلا موعد، أو زيارة قادمة لم يبدأ تنفيذها؛ تُجدول من نافذة المواعيد المتاحة. الفائتة لها مسار مستقل يتطلب سببًا. */
    public static function slotPickable(HourlyVisit $visit,?HourlyRentalOrder $order=null): bool
    {
        $order??=$visit->order;
        if(!$order || !in_array($order->status,['active','pending_confirmation'],true))return false;
        if(in_array($visit->status,['completed','cancelled','expired','no_show','cancelled_by_client'],true) || $visit->is_missed || $visit->has_started_execution)return false;
        return $visit->scheduled_start_at ? $visit->scheduled_start_at->isFuture() : $visit->status==='unscheduled';
    }

    /** Availability for picking a slot for a missed, unscheduled or upcoming visit. */
    public function rescheduleSlots(HourlyVisit $visit,string $date): array
    {
        $order=$visit->order()->with(['visits','serviceCategory'])->firstOrFail();
        if(!$visit->is_missed && !self::slotPickable($visit,$order))return [];
        if(!in_array($order->status,['active','pending_confirmation'],true))return [];
        $snapshot=(array)$order->package_snapshot;
        $package=\App\Models\Hourly\HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $snapshot['duration_minutes']=(int)round((float)$visit->planned_hours*60);
        $snapshot['start_times']=$package->start_times;
        $teamId=$visit->hourly_team_id;
        if(!$teamId){
            $category=app(HourlyBookingRules::class)->category($package);
            if(!$category)return [];
            [, $coverage]=app(HourlyBookingRules::class)->assertDistrictCovered($category,(int)$order->city_id,
                $order->latitude!==null?(float)$order->latitude:null,$order->longitude!==null?(float)$order->longitude:null,
                $order->district,$order->service_district_id?(int)$order->service_district_id:null);
            $teamId=$coverage?->team_id;
        }
        $slots=app(HourlySlotService::class)->forDate($date,(float)$visit->planned_hours,(int)($snapshot['min_notice_hours']??0),$snapshot,true);
        $assigned=$visit->workers()->pluck('maids.id')->map(fn($id)=>(int)$id)->all();
        if(\Illuminate\Support\Facades\Schema::hasTable('hourly_visit_reservations'))$assigned=array_values(array_unique(array_merge($assigned,
            DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->pluck('maid_id')->map(fn($id)=>(int)$id)->all())));
        $other=$order->visits->where('id','!=',$visit->id)->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->whereNotNull('scheduled_start_at');
        $rest=(int)($snapshot['break_minutes']??0)+(int)($snapshot['buffer_minutes']??0);$result=[];
        // Same check as OrderManagementService::availableWorkers, but the pool is loaded once and each slot stops
        // as soon as the assigned workers plus enough others are free, instead of re-scanning every worker per slot.
        $assignment=app(HourlyWorkerAssignment::class);
        $pool=$assignment->candidates($package,$teamId,(bool)($order->package_snapshot['assign_by_product_attribute']??false),(bool)($order->booking_metadata['all_workers']??false))
            ->orderBy('maids.id')->get()->keyBy(fn($maid)=>(int)$maid->id);
        $slotHasWorkers=function(string $from,string $to)use($pool,$assigned,$assignment,$order,$visit): bool{
            $free=fn($maid)=>$assignment->available($maid,$from,$to,$order->package_snapshot,$visit->id);
            foreach($assigned as $id)if(!isset($pool[$id]) || !$free($pool[$id]))return false;
            $count=count($assigned);
            foreach($pool as $id=>$maid){
                if($count>=$visit->workers_count)return true;
                if(!in_array($id,$assigned,true) && $free($maid))$count++;
            }
            return $count>=$visit->workers_count;
        };
        return OverdueWorkerReturn::remember(function()use($slots,$snapshot,$other,$rest,$order,$package,$visit,$slotHasWorkers,$result){
        foreach($slots as $slot){
            $start=\Carbon\Carbon::parse($slot['starts_at']);$end=\Carbon\Carbon::parse($slot['ends_at']);
            if($order->expires_at && $end->gt($order->expires_at))continue;
            try{
                app(HourlyBookingRules::class)->assertSessionDates($snapshot,array_merge($other->map(fn($v)=>$v->scheduled_start_at->format('Y-m-d H:i'))->all(),[$start->format('Y-m-d H:i')]),true);
                foreach($other as $existing)if($start->lt($existing->scheduled_end_at->copy()->addMinutes($rest))&&$end->gt($existing->scheduled_start_at->copy()->subMinutes($rest)))throw \Illuminate\Validation\ValidationException::withMessages(['starts_at'=>'overlap']);
                app(DuplicateBookingService::class)->assertAvailable((int)$order->user_id,$order->only(['city_id','address_text','latitude','longitude','building','unit']),$package,[$slot],$visit->id,true);
                if(!$slotHasWorkers($start->toDateTimeString(),$end->toDateTimeString()))continue;
            }catch(\Illuminate\Validation\ValidationException $e){continue;}catch(\Illuminate\Http\Exceptions\HttpResponseException $e){continue;}
            $result[]=['starts_at'=>$start->format('Y-m\\-d\\TH:i'),'label'=>$start->format('H:i')];
        }
        return $result;
        });
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
            if(!empty($data['starts_at']) && ($visits->count()!==1 || !$visits->first()->is_missed))HourlyOrderService::reject('Only one missed visit can use an available slot.');
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
            $financial=app(PaymentScheduleService::class)->forOrder($o);
            foreach($o->visits()->orderBy('scheduled_start_at')->orderBy('id')->get() as $v){
                $cancelled=in_array($v->status,['cancelled','cancelled_by_client']);
                $price=(int)round(($cancelled?$v->cancellation_fee:$v->visit_price)*100);$used=(int)round(($allocated[$v->id]['paid']??0)*100);
                if(!$cancelled && $v->status!=='expired' && $visits->contains('id',$v->id) && ($financial['policy']!=='per_visit' || $financial['visits'][$v->id]['is_due'])){$dues[$o->id][$v->id]=max(0,$price-$used);$selectedDue+=max(0,$price-$used);}
            }
            $limits[$o->id]=max(0,min($selectedDue,(int)round(($o->total_amount-$o->paid_amount)*100)));
        }
        if($remaining>array_sum($limits)){
            $available=array_sum($limits);
            if($visits->count()===1){
                $visit=$visits->first();$order=$orders->firstWhere('id',$visit->hourly_rental_order_id);
                $financial=app(PaymentScheduleService::class)->forOrder($order);$row=$financial['visits'][$visit->id];
                if($row['remaining']<=0 || $financial['remaining']<=0)
                    HourlyOrderService::reject('الزيارة #'.$visit->id.' مسددة بالكامل أو لا يوجد رصيد متبقٍ على الطلب. لا يمكن إضافة دفعة أخرى. راجع سجل الدفعات.');
                if(in_array($visit->status,['cancelled','cancelled_by_client','expired'],true))
                    HourlyOrderService::reject('لا يمكن تحصيل دفعة لهذه الزيارة بسبب حالتها الحالية.');
                if($financial['policy']==='per_visit' && !$row['is_due'])
                    HourlyOrderService::reject('لم يحن موعد استحقاق سداد الزيارة #'.$visit->id.' بعد.');
            }
            if($available<=0)HourlyOrderService::reject('لا يوجد مبلغ مستحق للتحصيل للزيارات المحددة حاليًا. راجع حالات الزيارات ومواعيد الاستحقاق وسجل الدفعات.');
            HourlyOrderService::reject('المبلغ المدخل '.number_format($remaining/100,2).' ر.س يتجاوز المتاح للتحصيل للزيارات المحددة: '.number_format($available/100,2).' ر.س بعد خصم السداد السابق.');
        }
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

}
