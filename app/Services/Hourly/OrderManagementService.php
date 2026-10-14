<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder,HourlyVisit,HourlyPackage,HourlyDriver};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderManagementService
{
    public const ACTIONS=['assign_workers','unassign_driver','reschedule','start_time','duration','price','assign_all','add_worker','remove_worker','unassign_worker','assign_driver','auto_driver','team_leader','free_visit','unassign_drivers','unassign_workers','unassign_all','clear_driver_routes','offer','cancel_visit','complete','confirm_return','cancel_order','payment_method'];
    public function audit(HourlyRentalOrder $order,string $action,array $before,array $after,?int $admin,?int $visit=null,?string $reason=null): void
    {
        DB::table('hourly_order_audits')->insert(['hourly_rental_order_id'=>$order->id,'visit_id'=>$visit,'admin_id'=>$admin,'action'=>$action,'reason'=>$reason,'before'=>json_encode($before),'after'=>json_encode($after),'created_at'=>now(),'updated_at'=>now()]);
    }
    private function snapshot(HourlyVisit $visit): array { return $visit->getAttributes()+['worker_ids'=>$visit->workers()->pluck('maids.id')->all()]; }
    public function apply(HourlyRentalOrder $order,array $data,?int $admin): array
    {
        HourlyOrderService::enabled();
        return DB::transaction(function()use($order,$data,$admin){
            $result=[];
            $order=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $action=$data['action'];
            if(!in_array($order->status,['pending_confirmation','active'])) HourlyOrderService::reject('الطلب مغلق ولا يقبل تعديلات تشغيلية.');
            $beforeOrder=$order->getAttributes();
            if($order->initial_amount===null)$order->initial_amount=$order->total_amount;
            if($action==='payment_method'){
                app(HourlyBookingRules::class)->assertPaymentMethod($order->package_snapshot,$data['payment_method'],true);
                if($data['payment_method']!==$order->payment_method){
                    if($order->payments()->where('status','pending_review')->exists())HourlyOrderService::reject('يوجد تحويل بنكي قيد المراجعة؛ اقبله أو ارفضه قبل تغيير طريقة الدفع.');
                    app(HourlyPaymentService::class)->cancelPendingMoyasar($order,(int)$admin);
                }
                $order->payment_method=$data['payment_method'];$order->save();
            } else {
                $query=$order->visits()->orderBy('id')->lockForUpdate();
                if($action!=='cancel_order' && $action!=='free_visit')$query->whereIn('id',$data['visit_ids'] ?? []);
                $visits=$query->get();
                if(!in_array($action,['cancel_order','free_visit']) && ($visits->isEmpty() || $visits->count()!==count(array_unique($data['visit_ids'] ?? []))))HourlyOrderService::reject('اختر زيارات تابعة لهذا الطلب.');
                if($action==='free_visit'){
                    $result=$this->freeVisit($order,$data,$admin);
                } else {
                $pending=[];
                foreach($visits as $visit){
                    if($action==='cancel_order' && in_array($visit->status,['completed','cancelled','expired','no_show','cancelled_by_client']))continue;
                    if(in_array($visit->status,['completed','cancelled','expired','no_show','cancelled_by_client']))HourlyOrderService::reject('لا يمكن تعديل زيارة منتهية أو ملغاة.');
                    if(in_array($action,['assign_workers','assign_all'],true) && (!$visit->scheduled_start_at || !$visit->scheduled_start_at->isFuture())){
                        HourlyOrderService::reject('تعيين العاملات متاح قبل بدء موعد الزيارة فقط. موعد الزيارة #'.$visit->id.': '.($visit->scheduled_start_at?->format('d/m/Y H:i') ?? 'غير محدد').'. اختر زيارة قادمة؛ ولتصحيح زيارة سابقة راجع مسؤول التشغيل.');
                    }
                    $before=$this->snapshot($visit);
                    if(isset($data['expected_start_at']) && $visit->scheduled_start_at?->format('Y-m-d\TH:i')!==$data['expected_start_at'])HourlyOrderService::reject('تغير موعد الزيارة. حدّث الصفحة وأعد المحاولة.');
                    if((isset($data['expected_price']) && abs($visit->visit_price-$data['expected_price'])>0.001) || (isset($data['expected_hours']) && abs($visit->planned_hours-$data['expected_hours'])>0.001))HourlyOrderService::reject('تم تعديل الزيارة من مستخدم آخر. حدّث الصفحة قبل إعادة المحاولة.');
                    if(in_array($action,['cancel_order','cancel_visit'])){
                        $visit->status='cancelled';$visit->cancellation_fee=($data['refund_method']??null)==='none'?$visit->visit_price:0;
                        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
                    } elseif($action==='confirm_return'){
                        if (!$admin || !trim((string)($data['reason'] ?? ''))) HourlyOrderService::reject('تأكيد العودة يتطلب مشرفًا وسببًا مسجلًا.');
                        if (!in_array($visit->status, ShelterPresence::OUT_STATUSES, true)) HourlyOrderService::reject('تأكيد العودة متاح للعاملة خارج الإيواء فقط.');
                        app(ShelterPresence::class)->close($visit, 'admin', $admin);
                        app(ShelterPresence::class)->flush();
                        $visit->status='completed';
                    } elseif($action==='complete'){
                        if (in_array($visit->status, ShelterPresence::OUT_STATUSES, true)
                            || (\Illuminate\Support\Facades\Schema::hasTable('worker_daily_movements') && DB::table('worker_daily_movements')
                                ->where('source','hourly_visit')->where('reference_type','hourly_visit')->where('reference_id',$visit->id)->whereNull('in_at')->exists()))
                            HourlyOrderService::reject('العاملة ما زالت خارج الإيواء؛ استخدم تأكيد العودة يدويًا بعد التحقق من وصولها.');
                        if(!$visit->scheduled_end_at || $visit->scheduled_end_at->isFuture() || $visit->workers()->count()<$visit->workers_count)HourlyOrderService::reject('الإكمال يتطلب انتهاء الموعد وتعيين العدد المطلوب من العاملات.');
                        $visit->status='completed';
                    } elseif($action==='price'){$visit->visit_price=$data['price'];$visit->is_free=(float)$data['price']===0;}
                    elseif(in_array($action,['reschedule','start_time','duration'])){
                        if(!$visit->scheduled_start_at || $visit->scheduled_start_at->isPast())HourlyOrderService::reject('تعديل الموعد والمدة متاح للزيارات القادمة فقط.');
                        $start=$visit->scheduled_start_at->format('Y-m-d H:i');$hours=(float)$visit->planned_hours;
                        $settings=app(HourlyCategorySettings::class);
                        if($action!=='duration' && (!$settings->value($order->package_snapshot,'allow_reschedule') || $visit->reschedule_count >= $settings->value($order->package_snapshot,'max_reschedules_per_visit')))HourlyOrderService::reject('إعادة الجدولة غير مسموحة أو تم بلوغ الحد الأقصى لهذه الزيارة.');
                        if($action==='reschedule')$start=!empty($data['new_date']) ? $data['new_date'].' '.$visit->scheduled_start_at->format('H:i') : (isset($data['starts_at']) ? Carbon::parse($data['starts_at'])->format('Y-m-d H:i') : Carbon::parse($start)->addDays($data['shift_days'])->format('Y-m-d H:i'));
                        if($action==='start_time')$start=$visit->scheduled_start_at->format('Y-m-d').' '.$data['start_time'];
                        if($action==='duration'){
                            $visit->visit_price=array_key_exists('duration_price_adjustment',$data) && $data['duration_price_adjustment']!==null
                                ? round($visit->visit_price+$data['duration_price_adjustment'],2)
                                : round($visit->visit_price*$data['hours']/$hours,2);
                            if($visit->visit_price<0 || $visit->visit_price>9999999)HourlyOrderService::reject('السعر الناتج للزيارة خارج الحدود المسموحة.');
                            $visit->is_free=(float)$visit->visit_price===0.0;$hours=(float)$data['hours'];
                        }
                        $this->setTime($visit,$start,$hours);
                        if($action!=='duration'){$visit->reschedule_count++;$visit->delivery_at=null;}
                        $visit->pickup_at=null;
                    } elseif($action==='unassign_driver'){$role=$data['driver_role'];$visit->{$role.'_driver_id'}=null;$visit->{$role.'_at'}=null;}
                    elseif(in_array($action,['unassign_drivers','unassign_all','clear_driver_routes'])){$visit->delivery_driver_id=null;$visit->pickup_driver_id=null;$visit->delivery_at=null;$visit->pickup_at=null;}
                    elseif($action==='offer'){
                        if(!$visit->scheduled_start_at || $visit->scheduled_start_at->isPast() || $visit->workers()->exists())HourlyOrderService::reject('العرض للعاملات متاح لزيارة قادمة بلا عاملات فقط.');
                        $visit->offered_to_workers=(bool)$data['offered'];
                    }
                    elseif($action==='auto_driver')$this->autoDriver($visit,$data['driver_direction']??'together');
                    elseif($action==='assign_driver'){
                        if(!$visit->scheduled_start_at || !$visit->scheduled_end_at)HourlyOrderService::reject('حدد موعد الزيارة قبل تعيين السائق.');
                        $driver=HourlyDriver::where('is_active',true)->whereKey($data['driver_id'])->lockForUpdate()->first();
                        if(!$driver || !$driver->teamMemberships()->where('hourly_team_id',$visit->hourly_team_id)->where('is_active',true)->exists())HourlyOrderService::reject('السائق ليس عضوًا نشطًا في فريق الزيارة.');
                        $kind=$data['driver_role'];$visit->{$kind.'_driver_id'}=$driver->id;
                        $visit->{$kind.'_at'}=Carbon::parse($visit->scheduled_start_at->format('Y-m-d').' '.$data['driver_time']);
                    } elseif($action==='team_leader'){
                        $valid=app(TeamLeaderChoices::class)->query($visit->hourly_team_id,(bool)($data['all_teams']??false))->whereKey($data['leader_id'])->lockForUpdate()->first();
                        if(!$valid)HourlyOrderService::reject('اختر قائدًا مفعّلًا من الفرق المحددة.');
                        $visit->team_leader_id=$data['leader_id'];
                    }
                    $ids=$visit->workers()->pluck('maids.id')->all();
                    if($action==='assign_workers'){
                        if(isset($data['expected_worker_ids']) && collect($ids)->sort()->values()->all()!==collect($data['expected_worker_ids'])->map(fn($id)=>(int)$id)->sort()->values()->all())HourlyOrderService::reject('تغير تعيين العاملات. افتح النافذة مجددًا قبل التأكيد.');
                        $ids=array_map('intval',$data['worker_ids']);
                        if(count($ids)>$visit->workers_count)HourlyOrderService::reject('عدد العاملات المختارات يتجاوز العدد المطلوب للزيارة.');
                    }
                    if(in_array($action,['unassign_workers','unassign_all']))$ids=[];
                    if(in_array($action,['remove_worker','unassign_worker'])){
                        if(!in_array($data['worker_id'],$ids))HourlyOrderService::reject('العاملة غير معيّنة لهذه الزيارة.');
                        $ids=array_values(array_diff($ids,[$data['worker_id']]));
                        if($action==='remove_worker'){
                            if($visit->workers_count<=1)HourlyOrderService::reject('يجب بقاء عاملة واحدة على الأقل؛ استخدم إلغاء التعيين بدلًا من تقليل العدد.');
                            $visit->visit_price=round($visit->visit_price*($visit->workers_count-1)/$visit->workers_count,2);$visit->workers_count--;
                        }
                    }
                    if($action==='add_worker'){
                        if(isset($data['expected_workers']) && (int)$data['expected_workers']!==(int)$visit->workers_count)HourlyOrderService::reject('تغير عدد العاملات، حدّث الصفحة وأعد المحاولة.');
                        if(in_array($data['worker_id'],$ids))HourlyOrderService::reject('العاملة معيّنة بالفعل.');
                        $ids[]=(int)$data['worker_id'];
                        $fee=$data['additional_fee']??(count($ids)>$visit->workers_count?$visit->visit_price/$visit->workers_count:0);
                        $visit->visit_price=round($visit->visit_price+$fee,2);
                        if($visit->visit_price>9999999)HourlyOrderService::reject('السعر الناتج للزيارة خارج الحدود المسموحة.');
                        $visit->workers_count=max($visit->workers_count,count($ids));$visit->is_free=(float)$visit->visit_price===0.0;
                    }
                    $visit->save();
                    $pending[]=[$visit,$ids,$before];
                }
                foreach($pending as [$visit,$ids,$before]){
                    if(in_array($action,['unassign_worker','unassign_workers','unassign_all'])){
                        if($visit->scheduled_start_at?->isFuture())$this->reserve($order,$visit,$ids,false,false,false,true);
                        else{$visit->workers()->sync($ids);DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();}
                    }
                    if(in_array($action,['reschedule','start_time','duration','assign_all','assign_workers','add_worker','remove_worker'])){
                        $ignoreCaps=$action==='duration' && !empty($data['ignore_hour_caps']);
                        $ignoreBreak=$action==='duration' && !empty($data['ignore_break']);
                        // Staffing an existing visit does not create or change its booked time.
                        $ignoreNotice=in_array($action,['assign_workers','assign_all'],true);
                        $this->checkSlot($order,$visit,$ignoreCaps,$ignoreBreak,$ignoreNotice);$this->reserve($order,$visit,$ids,$action==='assign_all',$ignoreCaps,$ignoreBreak);
                    }
                    if(in_array($action,['assign_driver','reschedule','start_time','duration']))$this->checkDrivers($visit);
                    $after=$this->snapshot($visit);
                    if($action==='team_leader')$after['all_teams']=!empty($data['all_teams']);
                    if($action==='add_worker')$after['additional_fee']=round($visit->visit_price-$before['visit_price'],2);
                    if($action==='duration')$after['duration_options']=['ignore_hour_caps'=>!empty($data['ignore_hour_caps']),'ignore_break'=>!empty($data['ignore_break']),'price_adjustment'=>$data['duration_price_adjustment']??null];
                    $this->audit($order,$action,$before,$after,$admin,$visit->id,$data['reason']);
                    $visitId=(int)$visit->id;
                    if(in_array($action,['assign_workers','assign_all','add_worker','assign_driver','auto_driver'],true)){
                        DB::afterCommit(fn()=>app(StaffPushService::class)->assignmentChanged($visitId,$before));
                    }
                    // الطاقم المسند يجب أن يعرف بإلغاء الزيارة أو تغيّر موعدها أو سحبها منه،
                    // وإلا انتقل إلى زيارة ملغاة أو حضر في الموعد القديم.
                    elseif(in_array($action,['cancel_visit','cancel_order'],true)){
                        $reason=$data['reason']??null;
                        DB::afterCommit(fn()=>app(StaffPushService::class)->visitCancelled($visitId,$before,$reason));
                    }
                    elseif(in_array($action,['reschedule','start_time','duration'],true)){
                        DB::afterCommit(fn()=>app(StaffPushService::class)->scheduleChanged($visitId,$before));
                    }
                    elseif(in_array($action,['unassign_worker','unassign_workers','unassign_all','remove_worker','unassign_driver','unassign_drivers','clear_driver_routes'],true)){
                        DB::afterCommit(fn()=>app(StaffPushService::class)->unassigned($visitId,$before));
                    }
                    // العرض بلا إشعار ميزة معطّلة: لا تعلم العاملة بوجود زيارة تقبلها.
                    elseif($action==='offer'){
                        DB::afterCommit(fn()=>app(StaffPushService::class)->visitOffered($visitId));
                    }
                    elseif($action==='team_leader'){
                        DB::afterCommit(fn()=>app(StaffPushService::class)->teamLeaderAssigned($visitId,$before));
                    }
                }
                }
                if($action==='free_visit')$order->update(['visits_total'=>$order->visits()->count()]);
                else $this->totals($order);
                if($action==='cancel_order')app(HourlyOrderService::class)->transition($order,'cancelled','admin',$admin,$data['reason']);
                elseif(!$order->visits()->whereNotIn('status',['completed','cancelled','expired','no_show','cancelled_by_client'])->exists())app(HourlyOrderService::class)->transition($order,$order->visits()->where('status','completed')->exists()?'completed':'cancelled','admin',$admin,$data['reason']);
            }
            $this->audit($order,$action,$beforeOrder,$order->fresh()->getAttributes(),$admin,null,$data['reason']);
            return $result;
        });
    }
    private function freeVisit(HourlyRentalOrder $order,array $data,?int $admin): array
    {
        $ignoreNotice=!empty($data['ignore_notice']);
        if($ignoreNotice)abort_unless($admin && AdminGuardHasPerm('hourly_orders_notice_override'),403);
        $start=Carbon::parse($data['starts_at']);
        if($start->isPast())HourlyOrderService::reject('موعد الزيارة المجانية يجب أن يكون في المستقبل.');
        if($start->minute%15!==0 || $start->second!==0)HourlyOrderService::reject('دقائق الموعد يجب أن تكون 00 أو 15 أو 30 أو 45.');
        if($order->visits()->count()>=365)HourlyOrderService::reject('تم بلوغ الحد الأقصى لزيارات الطلب.');
        $base=$order->visits()->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->orderByDesc('scheduled_start_at')->orderByDesc('visit_number')->first();
        if(!$base)HourlyOrderService::reject('لا توجد زيارة غير ملغاة لنسخ التعيينات منها.');
        $ids=$base->workers()->pluck('maids.id')->all();
        $visit=$base->replicate();$visit->unsetRelations();
        $visit->visit_number=(int)$order->visits()->max('visit_number')+1;
        $visit->status='scheduled';$visit->visit_price=0;$visit->is_free=true;$visit->cancellation_fee=0;$visit->reschedule_count=0;$visit->workers_count=$order->workers_per_visit;
        if(array_key_exists('offered_to_workers',$base->getAttributes()))$visit->offered_to_workers=false;
        $this->setTime($visit,$start->format('Y-m-d H:i'),(float)$order->hours_per_visit);
        foreach(['delivery','pickup'] as $role){
            $anchor=$role==='delivery'?'scheduled_start_at':'scheduled_end_at';
            $visit->{$role.'_at'}=$visit->{$role.'_driver_id'}?($base->{$role.'_at'} && $base->$anchor
                ? $visit->$anchor->copy()->addSeconds($base->$anchor->diffInSeconds(Carbon::parse($base->{$role.'_at'}),false))
                : $this->defaultDriverTime($visit,$role)):null;
        }
        $snapshot=$order->package_snapshot;
        // New visits follow the item's current start times, including null for the general schedule.
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $snapshot['start_times']=$item->start_times;
        $snapshot['duration_minutes']=(int)round($visit->planned_hours*60);
        app(HourlySlotService::class)->validate([$start->format('Y-m-d H:i')],(float)$visit->planned_hours,(int)($snapshot['min_notice_hours']??0),$order->expires_at,$snapshot,true,false,$ignoreNotice);
        $visit->save();$warning=null;$assignmentFailed=false;
        if($ids){
            try{$this->reserve($order,$visit,$ids);}
            catch(\Illuminate\Validation\ValidationException $e){
                if(empty($data['allow_unassigned']))throw $e;
                $assignmentFailed=true;
                $ids=[];$warning='أُضيفت الزيارة المجانية بدون تعيين عاملات أو سائقين أو قائد فريق لعدم توفر العاملات. '.collect($e->errors())->flatten()->first();
                $visit->workers()->detach();DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
                foreach(['delivery_driver_id','pickup_driver_id','team_leader_id','delivery_at','pickup_at'] as $field)$visit->$field=null;
            }
        }else{$warning='أُضيفت الزيارة المجانية بدون عاملات؛ الزيارة المصدر لا تحتوي تعيين عاملات.';}
        $this->checkDrivers($visit);
        // The explicit fallback stores an unassigned placeholder even if the source visit overlaps.
        // Working slots, notice and package expiry were already validated above; no capacity is reserved.
        if(!$assignmentFailed)$this->checkSlot($order,$visit,false,false,$ignoreNotice,$snapshot);
        $visit->save();
        $this->audit($order,'free_visit',[],$this->snapshot($visit)+['source_visit_id'=>$base->id,'ignore_notice'=>$ignoreNotice,'unassigned_warning'=>$warning],$admin,$visit->id,$data['reason']);
        return ['visit_id'=>$visit->id,'warning'=>$warning];
    }
    private function defaultDriverTime(HourlyVisit $visit,string $role): Carbon
    {
        return $role==='delivery'?$visit->scheduled_start_at->copy()->subMinutes(15):$visit->scheduled_end_at->copy();
    }
    private function setTime(HourlyVisit $visit,string $start,float $hours): void {
        $visit->scheduled_start_at=Carbon::parse($start);$visit->scheduled_end_at=Carbon::parse($start)->addMinutes((int)round($hours*60));$visit->scheduled_date=Carbon::parse($start)->toDateString();$visit->planned_hours=$hours;
    }
    private function autoDriver(HourlyVisit $visit,string $direction): void
    {
        if(!$visit->scheduled_start_at || !$visit->scheduled_end_at || $visit->scheduled_start_at->isPast())HourlyOrderService::reject('تعيين السائق تلقائيًا يتطلب زيارة قادمة بموعد محدد.');
        $roles=$direction==='together'?['delivery','pickup']:[$direction];
        $drivers=HourlyDriver::where('is_active',true)->whereHas('teamMemberships',fn($q)=>$q->where('hourly_team_id',$visit->hourly_team_id)->where('is_active',true))->orderBy('id')->lockForUpdate()->get();
        foreach($drivers as $driver){
            $candidate=clone $visit;
            foreach($roles as $role){$candidate->{$role.'_driver_id'}=$driver->id;$candidate->{$role.'_at'}=$this->defaultDriverTime($visit,$role);}
            try{$this->checkDrivers($candidate);}catch(\Illuminate\Validation\ValidationException $e){continue;}
            foreach($roles as $role){$visit->{$role.'_driver_id'}=$candidate->{$role.'_driver_id'};$visit->{$role.'_at'}=$candidate->{$role.'_at'};}
            return;
        }
        HourlyOrderService::reject('لا يوجد سائق متاح من فريق الزيارة للاتجاه المحدد.');
    }
    private function checkSlot(HourlyRentalOrder $order,HourlyVisit $visit,bool $ignoreCaps=false,bool $ignoreBreak=false,bool $ignoreNotice=false,?array $slotItem=null): void {
        $snapshot=$slotItem??$order->package_snapshot;$snapshot['duration_minutes']=(int)round($visit->planned_hours*60);
        if($ignoreBreak){$snapshot['break_minutes']=0;$snapshot['buffer_minutes']=0;}
        app(HourlySlotService::class)->validate([$visit->scheduled_start_at->format('Y-m-d H:i')],(float)$visit->planned_hours,(int)($snapshot['min_notice_hours']??0),$order->expires_at,$snapshot,true,$ignoreCaps,$ignoreNotice);
        $dates=$order->visits()->whereNotIn('status',['cancelled','expired'])->whereNotNull('scheduled_start_at')->pluck('scheduled_start_at')->map(fn($d)=>Carbon::parse($d)->format('Y-m-d H:i'))->all();
        app(HourlyBookingRules::class)->assertSessionDates($snapshot,$dates,true);
        $rest=(int)($snapshot['break_minutes']??0)+(int)($snapshot['buffer_minutes']??0);
        if($order->visits()->where('id','!=',$visit->id)->whereNotIn('status',['cancelled','expired'])->where('scheduled_start_at','<',$visit->scheduled_end_at->copy()->addMinutes($rest))->where('scheduled_end_at','>',$visit->scheduled_start_at->copy()->subMinutes($rest))->exists())HourlyOrderService::reject('الموعد يتداخل مع زيارة أخرى في الطلب.');
    }
    private function reserve(HourlyRentalOrder $order,HourlyVisit $visit,array $ids,bool $assignAll=false,bool $ignoreCaps=false,bool $ignoreBreak=false,bool $releaseOnly=false): void {
        if($visit->workers_count>20)HourlyOrderService::reject('الحد الأقصى 20 عاملة للزيارة.');
        if(!$releaseOnly)$this->ensureVisitTeam($order,$visit);
        DB::table('hourly_teams')->where('id',$visit->hourly_team_id)->lockForUpdate()->first();
        $workers=$this->availableWorkers($order,$visit,true,$ignoreCaps,$ignoreBreak);
        if(!$releaseOnly)foreach(array_diff($ids,$workers->pluck('id')->all()) as $id){$maid=\App\Models\ingaz\Maid::find($id);if($maid)HourlyOrderService::reject(app(HourlyWorkerAssignment::class)->unavailableMessage($maid,$visit,$order->package_snapshot));}
        if(!$releaseOnly && (array_diff($ids,$workers->pluck('id')->all()) || $workers->count()<$visit->workers_count))HourlyOrderService::reject('تعارض في تعيين العاملات: العاملة مرتبطة بزيارة أو حجز آخر، أو خارج جدول العمل أو فريق الخدمة، أو العدد المتاح غير كافٍ.');
        if($assignAll)$ids=array_merge($ids,$workers->whereNotIn('id',$ids)->take(max(0,$visit->workers_count-count($ids)))->pluck('id')->all());
        $visit->workers()->sync($ids);
        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
        foreach($workers->whereNotIn('id',$ids)->take(max(0,$visit->workers_count-count($ids))) as $maid)DB::table('hourly_visit_reservations')->insert(['hourly_visit_id'=>$visit->id,'maid_id'=>$maid->id,'created_at'=>now(),'updated_at'=>now()]);
    }
    /** Repair legacy visits created without a team before attempting an operational assignment. */
    private function ensureVisitTeam(HourlyRentalOrder $order,HourlyVisit $visit): void
    {
        if($visit->hourly_team_id)return;
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $category=app(HourlyBookingRules::class)->category($item);
        if(!$category)HourlyOrderService::reject('لا يوجد فريق خدمة مرتبط بالزيارة.');
        [, $coverage]=app(HourlyBookingRules::class)->assertDistrictCovered($category,(int)$order->city_id,$order->latitude!==null?(float)$order->latitude:null,$order->longitude!==null?(float)$order->longitude:null,$order->district,$order->service_district_id ? (int)$order->service_district_id : null);
        $teamId=(int)($coverage?->team_id ?? 0);
        if(!$teamId)HourlyOrderService::reject('لا يوجد فريق نشط يغطي عنوان الزيارة.');
        $visit->hourly_team_id=$teamId;
        $visit->save();
    }
    public function availableWorkers(HourlyRentalOrder $order,HourlyVisit $visit,bool $lock=false,bool $ignoreCaps=false,bool $ignoreBreak=false)
    {
        if(!$visit->scheduled_start_at || !$visit->scheduled_end_at)HourlyOrderService::reject('حدد موعد الزيارة قبل تعيين العاملة.');
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $service=app(HourlyWorkerAssignment::class);
        return $service->candidates($item,$visit->hourly_team_id,(bool)($order->package_snapshot['assign_by_product_attribute']??false),(bool)($order->booking_metadata['all_workers']??false))
            ->orderBy('maids.id')->when($lock,fn($q)=>$q->lockForUpdate())->get()
            ->filter(fn($m)=>$service->available($m,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id,$ignoreCaps,$ignoreBreak))
            // مفضلات العميل (عاملات ثم عاملات فرقه المفضلة) أولًا، فيأخذهن التعيين التلقائي قبل غيرهن.
            ->pipe(fn($workers)=>app(CustomerDirectory::class)->favoritesFirst($workers,(int)$order->user_id))->values();
    }
    public function availableDrivers($visits,string $role,string $clock,?Carbon $exactTime=null) {
        return HourlyDriver::where('is_active',true)->orderBy('name')->get()->filter(function($driver)use($visits,$role,$clock,$exactTime){
            $times=[];
            foreach($visits as $visit){
                if(!$visit->scheduled_start_at || !$visit->scheduled_end_at || !$driver->teamMemberships()->where('hourly_team_id',$visit->hourly_team_id)->where('is_active',true)->exists())return false;
                $copy=clone $visit;
                $copy->{$role.'_driver_id'}=$driver->id;
                $copy->{$role.'_at'}=$exactTime?->copy() ?? Carbon::parse($visit->scheduled_start_at->format('Y-m-d').' '.$clock);
                foreach($times as $time)if(abs($time->diffInMinutes($copy->{$role.'_at'},false))<30)return false;
                $times[]=$copy->{$role.'_at'};
                try{$this->checkDrivers($copy,false);}catch(\Illuminate\Validation\ValidationException $e){return false;}
            }
            return true;
        })->values();
    }
    private function checkDrivers(HourlyVisit $visit,bool $lock=true): void {
        foreach(['delivery','pickup'] as $role){
            if(!$id=$visit->{$role.'_driver_id'})continue;
            $driver=HourlyDriver::whereKey($id)->when($lock,fn($query)=>$query->lockForUpdate())->first();
            if(!$driver || !$driver->is_active)HourlyOrderService::reject('السائق '.($driver?->name??'#'.$id).' غير نشط أو غير موجود.');
            $time=$visit->{$role.'_at'} ? Carbon::parse($visit->{$role.'_at'}) : ($role==='delivery'?$visit->scheduled_start_at:$visit->scheduled_end_at);
            if(($role==='delivery' && $time->gt($visit->scheduled_start_at)) || ($role==='pickup' && $time->lt($visit->scheduled_end_at)))HourlyOrderService::reject('وقت التوصيل يجب أن يسبق بداية الزيارة، والاستلام بعد نهايتها.');
            $schedule=$driver->work_schedule[strtolower($time->format('l'))]??['mode'=>'off'];
            if(($schedule['mode']??'off')==='off' || (($schedule['mode']??'')==='hours' && ($time->format('H:i')<($schedule['start']??'24:00') || $time->format('H:i')>($schedule['end']??'00:00'))))HourlyOrderService::reject('السائق '.$driver->name.' خارج جدول العمل.');
            foreach(['delivery','pickup'] as $otherRole){
                $column=$otherRole==='delivery'?'scheduled_start_at':'scheduled_end_at';
                $conflict=HourlyVisit::where('id','!=',$visit->id)->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->where($otherRole.'_driver_id',$id)->whereRaw('COALESCE('.$otherRole.'_at,'.$column.') > ?',[$time->copy()->subMinutes(30)])->whereRaw('COALESCE('.$otherRole.'_at,'.$column.') < ?',[$time->copy()->addMinutes(30)])->first();
                if($conflict)HourlyOrderService::reject('السائق '.$driver->name.' مرتبط بالزيارة #'.$conflict->visit_number.' (مرجع #'.$conflict->id.') يوم '.$conflict->scheduled_start_at?->format('d/m/Y').' من '.$conflict->scheduled_start_at?->format('H:i').' إلى '.$conflict->scheduled_end_at?->format('H:i').'؛ يجب ترك 30 دقيقة بين مهام السائق.');
            }
        }
    }
    /** إجماليات الطلب من زياراته: مصدر الحقيقة الوحيد للمبالغ، تستدعيه لوحة التحكم وإجراءات العميل. */
    public function totals(HourlyRentalOrder $order): void {
        $visits=$order->visits()->get();$total=$visits->sum(fn($v)=>(int)round((in_array($v->status,['cancelled','cancelled_by_client'])?$v->cancellation_fee:$v->visit_price)*100));
        $subtotal=(int)round($total/(1+$order->tax_rate/100));
        $start=$visits->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->min('scheduled_start_at');
        if($start)$order->starts_at=$start;
        $order->fill(['total_amount'=>$total/100,'subtotal'=>$subtotal/100,'tax_amount'=>($total-$subtotal)/100,'visits_total'=>$visits->count(),'payment_status'=>$order->paid_amount*100>=$total?'paid':($order->paid_amount>0?'partial':'unpaid')]);$order->save();
    }
}
