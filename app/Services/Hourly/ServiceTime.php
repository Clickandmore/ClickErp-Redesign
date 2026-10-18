<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyVisit,HourlyRentalOrder};
use App\Models\ingaz\Maid;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Schema};

/** Records time exceptions and consent reminders. This service never changes a price or wallet. */
class ServiceTime
{
    public const REMIND_MINUTES=30;
    public const REASONS=[
        'waiting_driver'=>'انتهى العمل وبانتظار السائق','forgot_finish'=>'انتهيت ونسيت تسجيل الانتهاء',
        'customer_extension'=>'استمر العمل بطلب العميل دون تمديد معتمد','company_issue'=>'تأخير أو عائق من جهة الشركة',
        'emergency'=>'ظرف طارئ','other'=>'سبب آخر',
    ];
    public const DECISIONS=['no_charge'=>'إغلاق دون مبلغ إضافي','company_issue'=>'تأخير من الشركة — متابعة التشغيل',
        'worker_issue'=>'متابعة إجراء مع العاملة','settled_elsewhere'=>'تمت التسوية بإجراء مستقل موثّق','other'=>'قرار آخر موثّق'];

    public static function ready(): bool { return Schema::hasTable('hourly_service_time_checks'); }

    public static function recordDelivery(HourlyVisit $visit): void
    {
        if(!Schema::hasColumn('hourly_visit_workers','arrived_at'))return;
        DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->whereNull('arrived_at')
            ->where(fn($q)=>$q->whereNull('status')->orWhereIn('status',['assigned','en_route']))
            ->update(['status'=>'delivered','arrived_at'=>now(),'updated_at'=>now()]);
    }

    public static function expectedEnd(HourlyVisit $visit,object $worker): ?Carbon
    {
        return app(EarlyFinish::class)->plannedEnd($worker->arrived_at??null,$visit);
    }

    public static function deadline(HourlyVisit $visit): ?Carbon
    {
        $fallback=$visit->scheduled_end_at?->copy();
        if(!$visit->id || !Schema::hasColumn('hourly_visit_workers','arrived_at'))return $fallback;
        $end=null;
        foreach(DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->get(['arrived_at']) as $worker){
            $actual=!empty($worker->arrived_at)?self::expectedEnd($visit,$worker):$fallback;
            if($actual && (!$end || $actual->gt($end)))$end=$actual;
        }
        return $end??$fallback;
    }

    public function workerData(HourlyVisit $visit,object $worker): array
    {
        $end=self::expectedEnd($visit,$worker);
        $started=!empty($worker->arrived_at) || in_array($visit->status,['delivered','in_progress','worker_finished','picked_up','completed'],true);
        $finished=!empty($worker->finished_at) || in_array($worker->status??null,['finished','picked_up','returned','completed'],true)
            || in_array($visit->status,['worker_finished','picked_up','completed'],true);
        $finish=!empty($worker->finished_at)?Carbon::parse($worker->finished_at):null;
        $over=$started && $end && ($finish??now())->gt($end);
        $phase=!$started?'not_started':($finished?'finished':($over?'overrun':($end && now()->gte($end->copy()->subMinutes(self::REMIND_MINUTES))?'ending_soon':'in_service')));
        $check=self::ready() && $end ? DB::table('hourly_service_time_checks')->where('worker_visit_id',$worker->id)->where('expected_end_at',$end)->first() : null;
        $requiresReason=$over && (!$finished || $finish) && (!$check || in_array($check->status,['reminded','awaiting_reason'],true));
        return ['phase'=>$phase,'expected_end_at'=>$end?->toIso8601String(),'started_from_schedule'=>empty($worker->arrived_at),
            'remaining_minutes'=>$end && !$finished?max(0,(int)ceil(now()->diffInSeconds($end,false)/60)):0,
            'overrun_minutes'=>$over?max(1,(int)floor($end->diffInSeconds($finish??now())/60)):0,
            'requires_reason'=>$requiresReason,'reason_options'=>self::REASONS,'review_status'=>$check->status??null,
            'can_extend'=>!$finished && $started && $end?->isFuture() && !ServiceIncident::waiting($visit->order,$visit->id)];
    }

    public function publicData(HourlyVisit $visit): array
    {
        if(!Schema::hasColumn('hourly_visit_workers','arrived_at'))return [];
        $rows=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->get();
        $data=$rows->map(fn($row)=>$this->workerData($visit,$row));
        $phase=$data->contains(fn($row)=>$row['phase']==='overrun')?'overrun':($data->contains(fn($row)=>$row['phase']==='ending_soon')?'ending_soon':'normal');
        $review=self::ready() && DB::table('hourly_service_time_checks')->where('visit_id',$visit->id)->whereIn('status',['awaiting_reason','awaiting_admin'])->exists();
        return ['phase'=>$phase,'expected_end_at'=>self::deadline($visit)?->toIso8601String(),
            'overrun_minutes'=>(int)$data->max('overrun_minutes'),'under_review'=>$review,'automatic_charge'=>false,
            'message'=>$phase==='ending_soon'?'اقترب انتهاء مدة الخدمة. يمكنك طلب تمديد بالسعر المعروض بعد التحقق من التوفر.':
                (($phase==='overrun'||$review)?'تجاوز الوقت دون تمديد معتمد يحتاج تحققًا؛ لن يُضاف مبلغ تلقائيًا لمجرد تأخر تسجيل الانتهاء.':null)];
    }

    /** Caller holds the visit/order locks. Notifications are deferred until commit. */
    public function capture(HourlyVisit $visit,object $worker,bool $finishing=false): ?object
    {
        if(!self::ready())return null;
        $end=self::expectedEnd($visit,$worker);
        if(!$end || empty($worker->arrived_at) && !in_array($visit->status,['delivered','in_progress'],true))return null;
        if(!empty($worker->finished_at) && !$finishing)return null;
        if(now()->lt($end->copy()->subMinutes(self::REMIND_MINUTES)))return null;
        $late=now()->gt($end);
        $start=!empty($worker->arrived_at)?Carbon::parse($worker->arrived_at):$visit->scheduled_start_at;
        if(!$start || $start->isFuture())return null;
        DB::table('hourly_service_time_checks')->where('worker_visit_id',$worker->id)->where('expected_end_at','!=',$end)
            ->whereIn('status',['reminded','awaiting_reason','awaiting_admin'])->update(['status'=>'superseded','updated_at'=>now()]);
        DB::table('hourly_service_time_checks')->insertOrIgnore(['order_id'=>$visit->hourly_rental_order_id,'visit_id'=>$visit->id,
            'worker_visit_id'=>$worker->id,'maid_id'=>$worker->maid_id,'started_at'=>$start,'expected_end_at'=>$end,
            'started_from_schedule'=>empty($worker->arrived_at),'status'=>$late?'awaiting_reason':'reminded','created_at'=>now(),'updated_at'=>now()]);
        $check=DB::table('hourly_service_time_checks')->where('worker_visit_id',$worker->id)->where('expected_end_at',$end)->lockForUpdate()->first();
        if(in_array($check->status,['resolved','superseded'],true))return $check;
        if($late && $check->status==='reminded')DB::table('hourly_service_time_checks')->where('id',$check->id)->update(['status'=>'awaiting_reason','updated_at'=>now()]);
        if($late && !$check->overrun_notified_at){
            DB::table('hourly_service_time_checks')->where('id',$check->id)->update(['overrun_notified_at'=>now(),'updated_at'=>now()]);
            $visitId=(int)$visit->id;$maidId=(int)$worker->maid_id;
            DB::afterCommit(function()use($visitId,$maidId){
                try{app(StaffPushService::class)->sendToStaff('worker',$maidId,'انتهت مدة الخدمة','سجّلي انتهاء الخدمة أو سبب تجاوز المدة. لا يُحتسب مبلغ إضافي تلقائيًا.',['type'=>'hourly_time_overrun','visit_id'=>(string)$visitId]);}
                catch(\Throwable $e){report($e);}
            });
        }elseif(!$late && !$finishing && !$check->reminded_at && !ServiceIncident::waiting($visit->order,$visit->id)){
            $already=DB::table('hourly_service_time_checks')->where('visit_id',$visit->id)->where('expected_end_at',$end)->whereNotNull('reminded_at')->exists();
            DB::table('hourly_service_time_checks')->where('id',$check->id)->update(['reminded_at'=>now(),'updated_at'=>now()]);
            if(!$already){$visitId=(int)$visit->id;DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->notify($visitId,'service_ending'));}
        }
        if($finishing && $late)DB::table('hourly_service_time_checks')->where('id',$check->id)->update(['finish_reported_at'=>now(),'updated_at'=>now()]);
        return DB::table('hourly_service_time_checks')->where('id',$check->id)->first();
    }

    public function scan(): int
    {
        if(!self::ready())return 0;
        $count=0;
        HourlyVisit::whereIn('status',['delivered','in_progress'])->whereHas('order',fn($q)=>$q->where('status','active'))
            ->orderBy('id')->chunkById(100,function($visits)use(&$count){foreach($visits as $candidate){
                DB::transaction(function()use($candidate,&$count){
                    $order=HourlyRentalOrder::whereKey($candidate->hourly_rental_order_id)->lockForUpdate()->first();
                    $visit=HourlyVisit::whereKey($candidate->id)->lockForUpdate()->first();
                    if(!$visit || $order?->status!=='active' || !in_array($visit->status,['delivered','in_progress'],true))return;
                    $visit->setRelation('order',$order);
                    foreach(DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->whereNull('finished_at')->get() as $worker){
                        if(in_array($worker->status??null,['finished','picked_up','returned','completed'],true))continue;
                        if($this->capture($visit,$worker))$count++;
                    }
                });
            }});
        return $count;
    }

    public function reportReason(Maid $maid,int $workerVisitId,array $data): array
    {
        abort_unless(self::ready(),503,'يجب تهيئة متابعة مدة الخدمة.');
        return DB::transaction(function()use($maid,$workerVisitId,$data){
            $worker=DB::table('hourly_visit_workers')->where('id',$workerVisitId)->where('maid_id',$maid->id)->first();abort_unless($worker,404);
            $order=HourlyRentalOrder::whereKey(HourlyVisit::findOrFail($worker->hourly_visit_id)->hourly_rental_order_id)->lockForUpdate()->firstOrFail();
            $visit=HourlyVisit::whereKey($worker->hourly_visit_id)->lockForUpdate()->firstOrFail();$visit->setRelation('order',$order);
            $worker=DB::table('hourly_visit_workers')->where('id',$workerVisitId)->lockForUpdate()->first();
            $end=self::expectedEnd($visit,$worker);
            if(!$end || !now()->gt($end))HourlyOrderService::reject('لم تنتهِ مدة الخدمة بعد.');
            $check=DB::table('hourly_service_time_checks')->where('worker_visit_id',$workerVisitId)->where('expected_end_at',$end)->lockForUpdate()->first();
            if(!empty($worker->finished_at) && Carbon::parse($worker->finished_at)->lte($end))HourlyOrderService::reject('سُجل انتهاء هذه الخدمة ضمن المدة.');
            if(!$check)$check=$this->capture($visit,$worker,!empty($worker->finished_at));
            if(!$check)HourlyOrderService::reject('لا يوجد تجاوز قابل للمراجعة لهذه الزيارة.');
            if($check->status==='awaiting_admin' || $check->status==='resolved')return ['id'=>(int)$check->id,'status'=>$check->status];
            if(!in_array($check->status,['awaiting_reason','reminded'],true))HourlyOrderService::reject('تغيرت حالة المتابعة؛ حدّث الصفحة.');
            $claimed=!empty($data['actual_finished_at'])?Carbon::parse($data['actual_finished_at']):null;
            if($claimed && ($claimed->isFuture() || $claimed->lt(Carbon::parse($check->started_at))))HourlyOrderService::reject('وقت الانتهاء المبلّغ عنه يجب أن يقع بين الوصول والوقت الحالي.');
            if(($data['reason']??null)==='forgot_finish' && !$claimed)HourlyOrderService::reject('حدد وقت الانتهاء الفعلي الذي لم تسجليه.');
            DB::table('hourly_service_time_checks')->where('id',$check->id)->update(['status'=>'awaiting_admin','reason'=>$data['reason'],
                'worker_note'=>$data['note']??null,'claimed_finished_at'=>$claimed,'reason_reported_at'=>now(),'updated_at'=>now()]);
            app(OrderManagementService::class)->audit($order,'service_time_reason',[],['check_id'=>$check->id,'maid_id'=>$maid->id,'reason'=>$data['reason']],null,$visit->id,'إبلاغ سبب تجاوز مدة الخدمة — دون تعديل مالي');
            return ['id'=>(int)$check->id,'status'=>'awaiting_admin'];
        });
    }

    public function review(int $id,array $data,int $admin): void
    {
        DB::transaction(function()use($id,$data,$admin){
            $reference=DB::table('hourly_service_time_checks')->where('id',$id)->first();abort_unless($reference,404);
            $order=HourlyRentalOrder::whereKey($reference->order_id)->lockForUpdate()->firstOrFail();
            $check=DB::table('hourly_service_time_checks')->where('id',$id)->lockForUpdate()->first();abort_unless($check,404);
            if($check->status==='resolved')return;
            if(!in_array($check->status,['awaiting_reason','awaiting_admin'],true))HourlyOrderService::reject('هذا السجل لا ينتظر المراجعة.');
            if(empty($data['verified']))HourlyOrderService::reject('أكد التحقق من السبب وأي موافقة أو تسوية مستقلة.');
            DB::table('hourly_service_time_checks')->where('id',$id)->update(['status'=>'resolved','decision'=>$data['decision'],
                'decision_note'=>$data['note'],'reviewed_by'=>$admin,'reviewed_at'=>now(),'updated_at'=>now()]);
            app(OrderManagementService::class)->audit($order,'service_time_review',[],
                ['check_id'=>$id,'decision'=>$data['decision']],$admin,$check->visit_id,$data['note']);
        });
    }
}
