<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder,HourlyVisit,HourlyPackage,ServiceDistrict};
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;

class ServiceIncident
{
    public const WAITING=['awaiting_admin','awaiting_customer','manual_review'];

    public static function state(HourlyRentalOrder $order,int $visit): ?array
    {
        return $order->booking_metadata['service_disruptions'][$visit]??null;
    }
    public static function waiting(HourlyRentalOrder $order,int $visit): bool
    {
        return in_array(self::state($order,$visit)['status']??null,self::WAITING,true);
    }
    public static function recoverable(HourlyRentalOrder $order,HourlyVisit $visit): bool
    {
        return in_array(self::state($order,$visit->id)['status']??null,['awaiting_admin','awaiting_customer'],true)
            && in_array($order->status,['active','pending_confirmation','expired'],true)
            && in_array($visit->status,['scheduled','unscheduled','expired'],true);
    }
    public static function hasOpenMovement(int $visit): bool
    {
        return Schema::hasTable('worker_daily_movements') && DB::table('worker_daily_movements')
            ->where('source','hourly_visit')->where('reference_type','hourly_visit')->where('reference_id',$visit)->whereNull('in_at')->exists();
    }
    public function publicData(HourlyRentalOrder $order,HourlyVisit $visit): ?array
    {
        $state=self::state($order,$visit->id);if(!$state)return null;
        $choice=!app(WorkerReplacement::class)->allowed($order);
        return ['id'=>$state['id'],'status'=>$state['status'],'customer_worker_selection'=>$choice,
            'replacement_options'=>$choice?$this->alternatives($order,$visit):[]];
    }

    /** A worker can have only one pending report or unresolved approved report per visit. */
    public static function openReport(int $visitId,int $maidId): ?object
    {
        if(!Schema::hasTable('hourly_service_incidents'))return null;
        $pending=DB::table('hourly_service_incidents')->where('visit_id',$visitId)->where('maid_id',$maidId)
            ->where('status','pending')->orderByDesc('id')->first();
        if($pending)return $pending;
        $visit=HourlyVisit::with('order')->find($visitId);
        $state=$visit?->order ? self::state($visit->order,$visitId) : null;
        if(!in_array($state['status']??null,self::WAITING,true)
            || !in_array($maidId,array_map('intval',$state['failed_worker_ids']??[]),true))return null;
        return DB::table('hourly_service_incidents')->where('maid_id',$maidId)->where('status','approved')
            ->where(fn($q)=>$q->where('visit_id',$visitId)->orWhere('id',$state['incident_id']??0))->orderByDesc('id')->first();
    }

    public function report(int $visitId,int $maidId,string $actor,int $actorId,array $data): array
    {
        abort_unless(Schema::hasTable('hourly_service_incidents'),503,'يجب تهيئة بلاغات تعذر الخدمة أولًا.');
        return DB::transaction(function()use($visitId,$maidId,$actor,$actorId,$data){
            $visit=HourlyVisit::findOrFail($visitId);
            if($actor==='worker') abort_unless($maidId===$actorId,403);
            if($actor==='driver') abort_unless(in_array($actorId,[(int)$visit->delivery_driver_id,(int)$visit->pickup_driver_id],true),404);
            abort_unless($visit->workers()->where('maids.id',$maidId)->exists(),404);
            \App\Models\ingaz\Maid::whereKey($maidId)->lockForUpdate()->firstOrFail();
            $old=DB::table('hourly_service_incidents')->where('request_token',$data['request_token'])->lockForUpdate()->first();
            if($old){abort_unless($old->reporter_type===$actor && (int)$old->reporter_id===$actorId
                && (int)$old->visit_id===$visitId && (int)$old->maid_id===$maidId,409);return ['id'=>$old->id,'status'=>$old->status];}
            if(in_array($visit->status,['completed','cancelled','cancelled_by_client','expired','no_show'],true))
                HourlyOrderService::reject('لا يمكن تسجيل بلاغ تنفيذ لزيارة مغلقة.');
            if(self::openReport($visitId,$maidId))
                HourlyOrderService::reject('يوجد بلاغ قائم لهذه العاملة والزيارة؛ تابع البلاغ الحالي بدل إنشاء بلاغ آخر.');
            $id=DB::table('hourly_service_incidents')->insertGetId(['request_token'=>$data['request_token'],
                'visit_id'=>$visitId,'maid_id'=>$maidId,'reporter_type'=>$actor,'reporter_id'=>$actorId,
                'category'=>$data['category'],'note'=>$data['note'],'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
            return ['id'=>$id,'status'=>'pending'];
        });
    }

    public function review(int $id,array $data,int $admin): void
    {
        DB::transaction(function()use($id,$data,$admin){
            $incident=DB::table('hourly_service_incidents')->where('id',$id)->lockForUpdate()->first();abort_unless($incident,404);
            if($incident->status!=='pending') return;
            if($data['decision']==='reject'){
                DB::table('hourly_service_incidents')->where('id',$id)->update(['status'=>'rejected','reviewed_by'=>$admin,
                    'review_reason'=>$data['reason'],'reviewed_at'=>now(),'updated_at'=>now()]);return;
            }
            if(empty($data['verified'])) HourlyOrderService::reject('أكد التحقق من البلاغ قبل اعتماده.');
            $until=Carbon::parse($data['unavailable_until']??null);
            if(!$until->isFuture()) HourlyOrderService::reject('حدد نهاية مستقبلية لفترة عدم الجاهزية.');
            $targets=HourlyVisit::whereHas('workers',fn($q)=>$q->where('maids.id',$incident->maid_id))
                ->whereIn('status',array_merge(['scheduled','unscheduled'],DriverAppService::ONGOING))
                ->where(fn($q)=>$q->where('id',$incident->visit_id)->orWhere(fn($v)=>$v->where('scheduled_start_at','<',$until)->where('scheduled_end_at','>',now())))
                ->orderBy('id')->get();
            if($targets->isEmpty()) HourlyOrderService::reject('لا توجد زيارات مفتوحة متأثرة؛ راجع حالة البلاغ.');
            $orderIds=$targets->pluck('hourly_rental_order_id')->unique()->sort()->values();
            $userIds=HourlyRentalOrder::whereIn('id',$orderIds)->pluck('user_id')->unique()->sort()->values();
            User::whereIn('id',$userIds)->orderBy('id')->lockForUpdate()->get();
            $orders=HourlyRentalOrder::whereIn('id',$orderIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $targets=HourlyVisit::whereIn('id',$targets->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $leave=['maid_id'=>$incident->maid_id,'starts_at'=>now(),'ends_at'=>$until,'reason'=>'بلاغ تشغيلي معتمد #'.$id,
                'is_active'=>true,'created_at'=>now(),'updated_at'=>now()];
            if(Schema::hasColumn('hourly_worker_leaves','status'))$leave['status']='approved';
            if(Schema::hasColumn('hourly_worker_leaves','source'))$leave['source']='admin';
            if(Schema::hasColumn('hourly_worker_leaves','type'))$leave['type']='sick';
            $leaveId=DB::table('hourly_worker_leaves')->insertGetId($leave);
            foreach($targets as $visit){
                $order=$orders[$visit->hourly_rental_order_id];
                if(!$visit->workers()->where('maids.id',$incident->maid_id)->lockForUpdate()->first()
                    || !in_array($visit->status,array_merge(['scheduled','unscheduled'],DriverAppService::ONGOING),true))continue;
                $manual=in_array($visit->status,DriverAppService::ONGOING,true) || self::hasOpenMovement($visit->id)
                    || !in_array($order->status,['active','pending_confirmation','expired'],true);
                $choice=!app(WorkerReplacement::class)->allowed($order);
                $meta=$order->booking_metadata??[];$previous=$meta['service_disruptions'][$visit->id]??[];
                $failed=array_values(array_unique(array_merge($previous['failed_worker_ids']??[],[(int)$incident->maid_id])));
                $state=['id'=>(string)Str::uuid(),'incident_id'=>$id,'failed_worker_ids'=>$failed,
                    'status'=>$manual?'manual_review':($choice?'awaiting_customer':'awaiting_admin'),
                    'approved_by'=>$admin,'approved_at'=>now()->toIso8601String()];
                $meta['service_disruptions'][$visit->id]=$state;$order->update(['booking_metadata'=>$meta]);
                app(OrderManagementService::class)->audit($order,'incident_approved',[],$state,$admin,$visit->id,$data['reason']);
                $visitId=$visit->id;$stage=$manual?'service_followup':'service_disrupted';
                DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->notify($visitId,$stage));
            }
            DB::table('hourly_service_incidents')->where('id',$id)->update(['status'=>'approved','reviewed_by'=>$admin,
                'review_reason'=>$data['reason'],'unavailable_until'=>$until,'leave_id'=>$leaveId,'reviewed_at'=>now(),'updated_at'=>now()]);
        });
    }

    public function alternatives(HourlyRentalOrder $order,HourlyVisit $visit): array
    {
        if(!self::recoverable($order,$visit) || !$visit->scheduled_start_at?->isFuture())return [];
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $district=ServiceDistrict::find($order->service_district_id);if(!$district)return [];
        try{$workers=app(HourlyWebsiteCatalog::class)->candidates($item,$district);}
        catch(\Illuminate\Validation\ValidationException $e){return [];}
        $assigned=$visit->workers()->pluck('maids.id')->all();
        return $workers->whereNotIn('id',$assigned)->filter(fn($worker)=>app(HourlyWorkerAssignment::class)->available($worker,
            $visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id))
            ->map(fn($worker)=>['id'=>(int)$worker->id,'name'=>$worker->name])->values()->all();
    }

    public function choose(HourlyRentalOrder $candidate,int $visitId,array $data): void
    {
        DB::transaction(function()use($candidate,$visitId,$data){
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $visit=$order->visits()->lockForUpdate()->findOrFail($visitId);$state=self::state($order,$visitId);
            if(!$state || $state['id']!==$data['disruption_id']) HourlyOrderService::reject('تغيرت حالة الزيارة؛ حدّث الصفحة.');
            if(($state['status']??null)==='resolved' && ($state['chosen_worker_id']??null)===(int)$data['replacement_worker_id'])return;
            if(!self::recoverable($order,$visit) || app(WorkerReplacement::class)->allowed($order))
                HourlyOrderService::reject('اختيار البديلة غير متاح لهذه الزيارة.');
            if(($order->booking_metadata['missed_visit_requests'][$visitId]['status']??null)==='pending')
                HourlyOrderService::reject('لديك طلب إعادة جدولة أو إلغاء قيد المراجعة.');
            if(!in_array((int)$data['replacement_worker_id'],array_column($this->alternatives($order,$visit),'id'),true))
                HourlyOrderService::reject('البديلة لم تعد متاحة؛ اختر بديلة أخرى أو أعد الجدولة.');
            app(WorkerReplacement::class)->replace($order,$visitId,['worker_id'=>$state['failed_worker_ids'][0],
                'replacement_worker_id'=>$data['replacement_worker_id'],'reason'=>'اختار العميل البديلة بعد اعتماد البلاغ #'.$state['incident_id']],
                (int)$state['approved_by'],true);
        });
    }
}
