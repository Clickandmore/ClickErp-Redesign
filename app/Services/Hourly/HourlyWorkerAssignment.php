<?php
namespace App\Services\Hourly;
use App\Models\ingaz\Maid;
use App\Models\Hourly\{HourlyPackage,HourlyVisit,HourlyTeam};
use App\Services\Rental\RentalMaidQuery;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class HourlyWorkerAssignment {
    public function candidates(HourlyPackage $item, ?int $teamId, bool $byAttribute, bool $allTeams = false) {
        // عقد إيجار شهري نشط = العاملة مسلَّمة لعميل آخر، فلا تُرشَّح لزيارات الساعات.
        $query=RentalMaidQuery::applyNoActiveRental(RentalMaidQuery::applyReadyForRental(RentalMaidQuery::applyArrivalGate(Maid::query())))
            ->where('rental_mode','hourly')->where('hourly_is_suspended',false)
            ->whereHas('jobTypes',fn($q)=>$q->where('job_types.id',RentalMaidQuery::rentalJobTypeId()));
        if (!$allTeams) {
            if(!$teamId || !HourlyTeam::whereKey($teamId)->where('is_active',true)->exists())return $query->whereRaw('1=0');
            $query->whereHas('hourlyTeamMemberships',fn($q)=>$q->where('hourly_team_id',$teamId)->where('is_active',true));
        }
        if($item->service_category_id)$query->where(function($q)use($item){
            $q->whereNull('hourly_settings->service_category_ids');
            if($q->getConnection()->getDriverName()==='sqlite')$q->orWhereRaw("exists (select 1 from json_each(maids.hourly_settings, '$.service_category_ids') where value = ?)",[(int)$item->service_category_id]);
            else $q->orWhereJsonContains('hourly_settings->service_category_ids',(int)$item->service_category_id);
        });
        // The product's worker attribute currently available in this module is nationality.
        if($byAttribute && $item->attribute_id)$query->whereHas('productAttributes',fn($q)=>$q->where('hourly_product_attributes.id',$item->attribute_id));
        if($item->nationalities()->exists())$query->whereIn('nationality_id',$item->nationalities()->pluck('nationalities.id'));
        return OverdueWorkerReturn::exclude($query);
    }
    public function available(Maid $maid, string $start, string $end, array $item, ?int $exceptVisit=null,bool $ignoreHourCaps=false,bool $ignoreBreak=false): bool {
        return $this->availabilityReason($maid,$start,$end,$item,$exceptVisit,$ignoreHourCaps,$ignoreBreak) === null;
    }
    public function availabilityReason(Maid $maid, string $start, string $end, array $item, ?int $exceptVisit=null,bool $ignoreHourCaps=false,bool $ignoreBreak=false): ?string {
        // عاملة حُوّلت للإيجار العادي لا تُسنَد لزيارة ساعات حتى لو بقي معرّفها في طلب قديم.
        if(!$maid->isHourly())return 'العاملة ليست ضمن عاملات الساعات.';
        if($returnReason=OverdueWorkerReturn::reason((int)$maid->id))return $returnReason;
        if(!empty($item['assign_by_product_attribute']) && !empty($item['attribute_id']) && !$maid->productAttributes()->where('hourly_product_attributes.id',$item['attribute_id'])->exists())return 'سمة العاملة لا تطابق الخدمة المطلوبة.';
        $from=Carbon::parse($start);$to=Carbon::parse($end);
        if (\Illuminate\Support\Facades\Schema::hasTable('hourly_worker_leaves') && DB::table('hourly_worker_leaves')->where('maid_id',$maid->id)->where('is_active',true)->where('starts_at','<',$to)->where('ends_at','>',$from)->exists()) return 'لدى العاملة إجازة معتمدة في موعد الزيارة.';
        $schedule=$maid->hourly_settings['work_schedule'][strtolower($from->format('l'))]??['mode'=>'off'];
        if(($schedule['mode']??'off')==='off')return 'يوم الزيارة إجازة أو لم يُحدد له جدول عمل.';
        if(($schedule['mode']??'')==='hours' && ($from->format('H:i')<($schedule['start']??'24:00') || !$from->isSameDay($to) || $to->format('H:i')>($schedule['end']??'00:00')))return 'موعد الزيارة خارج ساعات عمل العاملة.';
        $visits=$this->workerVisits($maid,$exceptVisit);
        if($conflict=$this->conflictingVisit($maid,$start,$end,$item,$exceptVisit,$ignoreBreak))return 'تعارض مع الزيارة #'.$conflict->id.' أو مهلة الراحة والتنقل.';
        $caps=HourlyRentalSettings::effectiveHourCaps();$hours=$from->diffInMinutes($to)/60;
        foreach(['per_day'=>[$from->copy()->startOfDay(),$from->copy()->endOfDay()],'per_week'=>[$from->copy()->startOfWeek(),$from->copy()->endOfWeek()],'per_month'=>[$from->copy()->startOfMonth(),$from->copy()->endOfMonth()]] as $key=>[$a,$b]){
            if(!$ignoreHourCaps && $hours+(clone $visits)->whereBetween('v.scheduled_start_at',[$a,$b])->sum('v.planned_hours')>($caps[$key]??INF))return 'تجاوز حد ساعات العمل '.(['per_day'=>'اليومي','per_week'=>'الأسبوعي','per_month'=>'الشهري'][$key]).'.';
        }
        if ((clone $visits)->whereDate('v.scheduled_start_at',$from->toDateString())->count()>=HourlyRentalSettings::get('max_visits_per_day',1)) return 'بلغت العاملة الحد الأقصى للزيارات اليومية.';
        return null;
    }
    public function excludedWorkers(HourlyVisit $visit, \Illuminate\Support\Collection $available, array $assigned = [], bool $futureOnly = false): \Illuminate\Support\Collection {
        $order=$visit->order;
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $workers=Maid::where('rental_mode','hourly')->whereNotIn('id',$available->pluck('id')->merge($assigned))->with(['hourlyTeamMemberships','jobTypes','productAttributes'])->orderBy('name')->get();
        $ready=RentalMaidQuery::applyReadyForRental(Maid::whereIn('id',$workers->pluck('id')))->pluck('id');
        $inside=RentalMaidQuery::applyInsideShelter(Maid::whereIn('id',$workers->pluck('id')))->pluck('id');
        $noRental=RentalMaidQuery::applyNoActiveRental(Maid::whereIn('id',$workers->pluck('id')))->pluck('id');
        $notDeported=RentalMaidQuery::applyNotDeported(Maid::whereIn('id',$workers->pluck('id')))->pluck('id');
        $nationalities=$item->nationalities()->pluck('nationalities.id');
        $allTeams=(bool)($order->booking_metadata['all_workers']??false);
        $teamActive=$allTeams || ($visit->hourly_team_id && HourlyTeam::whereKey($visit->hourly_team_id)->where('is_active',true)->exists());
        return $workers->map(function($maid)use($visit,$order,$item,$ready,$inside,$noRental,$notDeported,$nationalities,$allTeams,$teamActive,$futureOnly){
            $reasons=[];
            if($maid->hourly_is_suspended)$reasons[]='العاملة موقوفة عن العمل بنظام الساعات.';
            if($maid->rental_source==='outside' && !$maid->arrival_date)$reasons[]='لم يُسجل تاريخ وصول العاملة.';
            if(!$inside->contains($maid->id))$reasons[]='لا يوجد عقد إيواء داخل المركز للعاملة.';
            if(!$noRental->contains($maid->id))$reasons[]='لدى العاملة عقد إيجار شهري قائم.';
            if(!$notDeported->contains($maid->id))$reasons[]='العاملة مستبعدة بسبب إجراء ترحيل مكتمل.';
            if(in_array((string)$maid->status,RentalMaidQuery::NOT_READY_MAID_STATUSES,true))$reasons[]='حالة العاملة الحالية لا تسمح بالإيجار: '.RentalMaidQuery::statusLabel($maid->status);
            if(!$ready->contains($maid->id) && $inside->contains($maid->id) && $noRental->contains($maid->id) && $notDeported->contains($maid->id) && !in_array((string)$maid->status,RentalMaidQuery::NOT_READY_MAID_STATUSES,true))$reasons[]='رغبة العمل غير مؤكدة أو يوجد رفض للعمل في بيانات الإيواء.';
            if(!$maid->jobTypes->contains('id',RentalMaidQuery::rentalJobTypeId()))$reasons[]='العاملة غير مرتبطة بنوع الإيجار.';
            if(!$teamActive)$reasons[]='فريق الزيارة غير محدد أو غير نشط.';
            elseif(!$allTeams && !$maid->hourlyTeamMemberships->contains(fn($m)=>(int)$m->hourly_team_id===(int)$visit->hourly_team_id && $m->is_active))$reasons[]='العاملة ليست عضوًا نشطًا في فريق الزيارة.';
            $services=$maid->hourly_settings['service_category_ids']??null;
            if($item->service_category_id && $services!==null && !in_array($item->service_category_id,$services))$reasons[]='الخدمة المطلوبة غير محددة ضمن خدمات العاملة.';
            if($nationalities->isNotEmpty() && !$nationalities->contains($maid->nationality_id))$reasons[]='جنسية العاملة لا تطابق الخدمة.';
            if(!empty($order->package_snapshot['assign_by_product_attribute']) && $item->attribute_id && !$maid->productAttributes->contains('id',$item->attribute_id))$reasons[]='سمة العاملة لا تطابق الخدمة المطلوبة.';
            if(!$visit->scheduled_start_at || !$visit->scheduled_end_at)$reasons[]='حدد بداية الزيارة ونهايتها قبل إسناد العاملة.';
            elseif($futureOnly && !$visit->scheduled_start_at->isFuture())$reasons[]='إضافة عاملة متاحة للزيارات التي لم يبدأ موعدها فقط.';
            elseif(!$reasons && ($reason=$this->availabilityReason($maid,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id)))$reasons[]=$reason;
            return ['id'=>$maid->id,'name'=>$maid->name,'reasons'=>array_values(array_unique($reasons))];
        })->values();
    }
    private function workerVisits(Maid $maid,?int $exceptVisit)
    {
        $workerVisits=DB::table('hourly_visit_workers')->select('hourly_visit_id','maid_id');
        if (\Illuminate\Support\Facades\Schema::hasTable('hourly_visit_reservations')) $workerVisits->union(DB::table('hourly_visit_reservations')->select('hourly_visit_id','maid_id'));
        return DB::query()->fromSub($workerVisits,'w')->join('hourly_visits as v','v.id','=','w.hourly_visit_id')->join('hourly_rental_orders as o','o.id','=','v.hourly_rental_order_id')
            ->where('w.maid_id',$maid->id)->whereNotIn('v.status',['cancelled','cancelled_by_client','expired'])->whereNotIn('o.status',['cancelled','expired'])->when($exceptVisit,fn($q)=>$q->where('v.id','!=',$exceptVisit));
    }
    public function conflictingVisit(Maid $maid,string $start,string $end,array $item,?int $exceptVisit=null,bool $ignoreBreak=false)
    {
        $rest=$ignoreBreak?0:max((int)($item['break_minutes']??0)+(int)($item['buffer_minutes']??0),(int)(HourlyRentalSettings::get('min_rest_hours_between_visits',0)*60));
        return $this->workerVisits($maid,$exceptVisit)->where('v.scheduled_start_at','<',Carbon::parse($end)->addMinutes($rest))->where('v.scheduled_end_at','>',Carbon::parse($start)->subMinutes($rest))->select('v.*')->orderBy('v.scheduled_start_at')->first();
    }
    public function unavailableMessage(Maid $maid,HourlyVisit $visit,array $item): string
    {
        if($returnReason=OverdueWorkerReturn::reason((int)$maid->id))return $returnReason;
        $conflict=$this->conflictingVisit($maid,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$item,$visit->id);
        if($conflict)return 'العاملة '.$maid->name.' لديها زيارة #'.$conflict->visit_number.' (مرجع #'.$conflict->id.') يوم '.Carbon::parse($conflict->scheduled_start_at)->format('d/m/Y').' من '.Carbon::parse($conflict->scheduled_start_at)->format('H:i').' إلى '.Carbon::parse($conflict->scheduled_end_at)->format('H:i').'؛ يوجد تعارض مع الموعد أو مهلة التنقل.';
        return 'العاملة '.$maid->name.' غير متاحة في الموعد المطلوب حسب جدول العمل أو الإجازات أو شروط الفريق والخدمة أو حد الساعات.';
    }
    public function assign(HourlyVisit $visit,HourlyPackage $item,?int $preferred,bool $byAttribute,?\Illuminate\Support\Collection $eligible=null,?array $selectedIds=null,bool $fillRemaining=true): void {
        if(!$visit->scheduled_start_at){if($preferred)HourlyOrderService::reject('اختر موعد الزيارة قبل اختيار العاملة.');return;}
        $candidates=$eligible ?? $this->candidates($item,$visit->hourly_team_id,$byAttribute)->orderBy('maids.id')->lockForUpdate()->get();
        $available=$candidates->filter(fn($maid)=>$this->available($maid,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$item->toArray()));
        if($preferred && !$available->contains('id',$preferred))HourlyOrderService::reject('العاملة المختارة غير متاحة لهذا الفريق أو الموعد أو مواصفات الخدمة.');
        if ($selectedIds !== null) {
            if (count($selectedIds)>(int)$visit->workers_count || array_diff($selectedIds,$available->pluck('id')->all())) HourlyOrderService::reject('إحدى العاملات المختارات غير متاحة للموعد أو الخدمة. راجع اختيار العاملات.');
            $selected=$available->whereIn('id',$selectedIds);
            if ($fillRemaining) $selected=$selected->concat($available->whereNotIn('id',$selectedIds)->take(max(0,$visit->workers_count-$selected->count())));
        } else {
            // Keep the legacy single-preference and operational assignment contract.
            $selected=$available->sortBy(fn($maid)=>$maid->id===$preferred?0:1)->take($visit->workers_count);
            if($selected->count()<$visit->workers_count)HourlyOrderService::reject('لا توجد عاملات متاحات بالعدد المطلوب لهذا الموعد.');
        }
        foreach($selected as $maid)DB::table('hourly_visit_workers')->insert(['hourly_visit_id'=>$visit->id,'maid_id'=>$maid->id,'created_at'=>now(),'updated_at'=>now()]);
    }
}
