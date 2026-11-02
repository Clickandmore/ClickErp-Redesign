<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyVisit};
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

class MissedVisitRecovery
{
    /**
     * Whether the time a customer asked for in a pending reschedule request can still be approved,
     * using the same check as review(). state: available|unavailable|past|coverage|ineligible.
     */
    public function requestedSlotStatus(HourlyRentalOrder $order,int $visitId): ?array
    {
        $request=$order->booking_metadata['missed_visit_requests'][$visitId]??null;
        if(!$request || ($request['status']??null)!=='pending' || ($request['type']??null)!=='reschedule' || empty($request['requested_start']))return null;
        $start=Carbon::parse($request['requested_start']);
        if(!$start->isFuture())return ['state'=>'past','alternatives'=>[]];
        if(($request['reason_code']??null)==='coverage_unavailable' && self::coverageUnavailable($order))return ['state'=>'coverage','alternatives'=>[]];
        try{$slots=$this->availableSlots($order,$visitId,(string)$request['id'],$start->toDateString());}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException|\Illuminate\Database\Eloquent\ModelNotFoundException $e){return ['state'=>'ineligible','alternatives'=>[]];}
        $available=collect($slots)->contains(fn($slot)=>$slot['starts_at']===$start->format('Y-m-d\\TH:i'));
        return ['state'=>$available?'available':'unavailable','alternatives'=>$available?[]:array_column($slots,'label')];
    }

    /** Slot lookup for a customer preparing a recovery request, before the request exists. */
    public function customerAvailableSlots(HourlyRentalOrder $order,int $visitId,string $date): array
    {
        $visit=$order->visits()->findOrFail($visitId);
        if(!self::eligible($visit,$order))return [];
        $meta=$order->booking_metadata??[];
        $existing=$meta['missed_visit_requests'][$visitId]??null;
        if(($existing['status']??null)==='pending')return [];
        $reason=ServiceIncident::recoverable($order,$visit)?'service_incident':(self::closureRecovery($visit,$order)?'category_closure':(self::coverageRecovery($visit,$order)?'coverage_unavailable':'missed_visit'));
        $requestId=(string)\Illuminate\Support\Str::uuid();
        $meta['missed_visit_requests'][$visitId]=['id'=>$requestId,'status'=>'pending','type'=>'reschedule','reason_code'=>$reason];
        $copy=clone $order;$copy->booking_metadata=$meta;
        return $this->availableSlots($copy,$visitId,$requestId,$date,true);
    }

    /**
     * Return only dispatcher slots that can be used to recover this visit.
     * $customer narrows them to what booking shows customers (customer start times, team capacity left for
     * unassigned visits), so a time the customer picks still passes the dispatcher check on approval.
     */
    public function availableSlots(HourlyRentalOrder $order,int $visitId,string $requestId,string $date,bool $customer=false): array
    {
        $visit=$order->visits()->findOrFail($visitId);
        $request=$order->booking_metadata['missed_visit_requests'][$visitId]??null;
        abort_unless($request && ($request['id']??null)===$requestId && ($request['status']??null)==='pending'
            && ($request['type']??null)==='reschedule' && self::eligible($visit,$order),404);
        if(($request['reason_code']??null)==='coverage_unavailable' && self::coverageUnavailable($order))return [];
        $snapshot=(array)$order->package_snapshot;
        $snapshot['duration_minutes']=(int)round((float)$visit->planned_hours*60);
        $snapshot['start_times']=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id)->start_times;
        $teamId=$visit->hourly_team_id;
        if(!$teamId){
            try{$package=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);$category=app(HourlyBookingRules::class)->category($package);
                if(!$category)return [];
                [, $coverage]=app(HourlyBookingRules::class)->assertDistrictCovered($category,(int)$order->city_id,
                    $order->latitude!==null?(float)$order->latitude:null,$order->longitude!==null?(float)$order->longitude:null,
                    $order->district,$order->service_district_id?(int)$order->service_district_id:null);
                $teamId=$coverage?->team_id;
            }catch(\Illuminate\Validation\ValidationException $e){return [];}
        }
        $slots=app(HourlySlotService::class)->forDate($date,(float)$visit->planned_hours,(int)($snapshot['min_notice_hours']??0),$snapshot,!$customer);
        $incident=($request['reason_code']??null)==='service_incident';
        $failed=$incident?(ServiceIncident::state($order,$visitId)['failed_worker_ids']??[]):[];
        $assigned=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visitId)->pluck('maid_id')->map(fn($id)=>(int)$id)->all();
        if(Schema::hasTable('hourly_visit_reservations'))$assigned=array_values(array_unique(array_merge($assigned,
            DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visitId)->pluck('maid_id')->map(fn($id)=>(int)$id)->all())));
        $assigned=array_values(array_diff($assigned,array_map('intval',$failed)));
        $rest=(int)($snapshot['break_minutes']??0)+(int)($snapshot['buffer_minutes']??0);
        $other=$order->visits()->where('id','!=',$visitId)->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->whereNotNull('scheduled_start_at')->get();
        $manager=app(OrderManagementService::class);$result=[];
        foreach($slots as $slot){
            $start=Carbon::parse($slot['starts_at']);$end=Carbon::parse($slot['ends_at']);
            $dateTimes=array_merge($other->map(fn($v)=>$v->scheduled_start_at->format('Y-m-d H:i'))->all(),[$start->format('Y-m-d H:i')]);
            try{
                app(HourlyBookingRules::class)->assertSessionDates($snapshot,$dateTimes,true);
                foreach($other as $existing)if($start->lt($existing->scheduled_end_at->copy()->addMinutes($rest)) && $end->gt($existing->scheduled_start_at->copy()->subMinutes($rest)))throw \Illuminate\Validation\ValidationException::withMessages(['new_start'=>'overlap']);
                app(DuplicateBookingService::class)->assertAvailable((int)$order->user_id,$order->only(['city_id','address_text','latitude','longitude','building','unit']),HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id),[$slot],$visitId,true);
                $candidate=clone $visit;$candidate->scheduled_start_at=$start;$candidate->scheduled_end_at=$end;$candidate->scheduled_date=$start->toDateString();$candidate->hourly_team_id=$teamId;
                $workers=$manager->availableWorkers($order,$candidate);
                if($workers->count()<$visit->workers_count)continue;
                if($customer){
                    // As in booking: the workers who could not serve this visit do not count, and unassigned visits keep their share of the team.
                    $free=$workers->reject(fn($worker)=>in_array((int)$worker->id,array_map('intval',$failed),true))->count()
                        -($teamId?app(PanelBookingService::class)->unassignedCapacity((int)$teamId,$start->toDateTimeString(),$end->toDateTimeString(),(int)$order->id):0);
                    if($free<$visit->workers_count)continue;
                }
                if(!$incident && count(array_diff($assigned,$workers->pluck('id')->map(fn($id)=>(int)$id)->all())))continue;
            }catch(\Illuminate\Validation\ValidationException $e){continue;}
            catch(\Illuminate\Http\Exceptions\HttpResponseException $e){continue;}
            $result[]=['starts_at'=>$start->format('Y-m-d\\TH:i'),'ends_at'=>$end->format('Y-m-d\\TH:i'),'label'=>$start->format('H:i')];
        }
        return $result;
    }

    /** A substitute must still be able to reach the customer; closer to the start the customer reschedules instead. */
    public const REPLACEMENT_LEAD_MINUTES = 60;

    /** The customer may ask the admin for a substitute at the same time only for an upcoming disrupted visit. */
    public static function replacementEligible(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        return ServiceIncident::recoverable($order,$visit) && $visit->status==='scheduled'
            && in_array($order->status,['active','pending_confirmation'],true)
            && (bool)$visit->scheduled_start_at?->gt(now()->addMinutes(self::REPLACEMENT_LEAD_MINUTES));
    }

    /** Workers the admin can put on the visit's slot in place of the ones who could not attend. */
    public function replacementCandidates(HourlyRentalOrder $order, HourlyVisit $visit)
    {
        if(!$visit->scheduled_start_at || !$visit->scheduled_end_at)return collect();
        $failed=array_map('intval',ServiceIncident::state($order,$visit->id)['failed_worker_ids']??[]);
        $kept=array_diff($visit->workers()->pluck('maids.id')->map(fn($id)=>(int)$id)->all(),$failed);
        try{$workers=app(OrderManagementService::class)->availableWorkers($order,$visit);}
        catch(\Illuminate\Validation\ValidationException $e){return collect();}
        return $workers->reject(fn($worker)=>in_array((int)$worker->id,$kept,true) || in_array((int)$worker->id,$failed,true))->values();
    }

    public static function eligible(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        return ((($visit->is_missed || self::coverageRecovery($visit,$order)) && (float)$order->paid_amount>0)
            || self::closureRecovery($visit,$order)
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

    public static function closureRecovery(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        return (bool)(app(ClosureImpact::class)->conflict($visit,$order)['can_request_resolution']??false);
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
            if($data['type']==='replacement' && !self::replacementEligible($visit,$order))
                HourlyOrderService::reject('طلب عاملة بديلة متاح لزيارة قادمة تعذر تنفيذها، قبل موعدها بساعة على الأقل. يمكنك طلب إعادة الجدولة أو الإلغاء.');
            if($data['type']==='reschedule'){
                $start=Carbon::parse($data['requested_start']);
                $slots=$this->customerAvailableSlots($order,$visitId,$start->toDateString());
                if(!collect($slots)->contains(fn($slot)=>$slot['starts_at']===$start->format('Y-m-d\\TH:i')))
                    HourlyOrderService::reject('الموعد المختار غير متاح. اختر موعدًا من المواعيد المتاحة.');
            }
            $request=['id'=>$data['request_token'],'visit_id'=>$visitId,'type'=>$data['type'],'status'=>'pending',
                'reason_code'=>ServiceIncident::recoverable($order,$visit)?'service_incident':(self::closureRecovery($visit,$order)?'category_closure':(self::coverageRecovery($visit,$order)?'coverage_unavailable':'missed_visit')),
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
                    $start=Carbon::parse($request['requested_start']);
                    $requestedStart=$start->format('Y-m-d\\TH:i');
                    if(!empty($data['new_start']) && Carbon::parse($data['new_start'])->format('Y-m-d\\TH:i')!==$requestedStart)
                        HourlyOrderService::reject('لا يمكن اعتماد موعد مختلف عن الموعد الذي اختاره العميل. ارفض الطلب ليختار العميل موعدًا آخر.');
                    if(!$start->isFuture()) HourlyOrderService::reject('اختر موعدًا مستقبليًا.');
                    $available=$this->availableSlots($order,$visitId,$request['id'],$start->toDateString());
                    if(!collect($available)->contains(fn($slot)=>$slot['starts_at']===$requestedStart))
                        HourlyOrderService::reject('الموعد الذي اختاره العميل لم يعد متاحًا. ارفض الطلب مع توضيح السبب ليختار العميل موعدًا آخر.');
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
                }elseif($request['type']==='replacement'){
                    if(!$incident || !self::replacementEligible($visit,$order))
                        HourlyOrderService::reject('فات وقت توفير بديلة لهذه الزيارة؛ ارفض الطلب ليختار العميل إعادة الجدولة أو الإلغاء.');
                    $failed=array_map('intval',ServiceIncident::state($order,$visitId)['failed_worker_ids']??[]);
                    $choices=array_map('intval',$data['replacement_worker_ids']??[]);
                    foreach($failed as $old)if(empty($choices[$old]))HourlyOrderService::reject('اختر عاملة بديلة لكل عاملة تعذر حضورها.');
                    if(count(array_unique(array_intersect_key($choices,array_flip($failed))))!==count($failed))HourlyOrderService::reject('اختر عاملة مختلفة لكل مقعد.');
                    // replace() refuses while a recovery request is pending; this is the request being approved.
                    $meta['missed_visit_requests'][$visitId]['status']='approved';$order->update(['booking_metadata'=>$meta]);
                    foreach($failed as $old)app(WorkerReplacement::class)->replace($order,$visitId,['worker_id'=>$old,
                        'replacement_worker_id'=>$choices[$old],'reason'=>'عاملة بديلة بطلب العميل: '.$data['reason']],$admin,false,true);
                    $request['replacement_worker_ids']=array_intersect_key($choices,array_flip($failed));
                }else{
                    $allocation=app(VisitPaymentAllocation::class)->forOrder($order);
                    $amount=round(min((float)$visit->visit_price,(float)($allocation[$visitId]['paid']??0)),2);
                    if($amount<=0 && !$incident && ($request['reason_code']??null)!=='category_closure') HourlyOrderService::reject('لا يوجد مبلغ مسدد مخصص لهذه الزيارة؛ راجع توزيع الدفعات.');
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
