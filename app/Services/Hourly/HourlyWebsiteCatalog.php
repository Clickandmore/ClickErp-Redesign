<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, ServiceDistrict};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\Schema;

class HourlyWebsiteCatalog
{
    /** Explains catalog visibility, not date-specific booking capacity. */
    public function publicationStatus(HourlyPackage $item): array
    {
        $reasons=[];
        $rules=app(HourlyBookingRules::class);
        $category=$rules->category($item);
        if (!HourlyRentalSettings::isEnabled()) $reasons[]='نظام الساعات غير مفعّل.';
        if (!$item->is_active) $reasons[]='الباقة متوقفة.';
        if (!$item->show_on_app) $reasons[]='خيار عرض الباقة للعملاء غير مفعّل.';
        if ($item->is_private) $reasons[]='الباقة خاصة وليست معروضة للعملاء.';
        if (!$category || !$category->is_active || $category->trashed()) $reasons[]='فئة الخدمة غير موجودة أو متوقفة أو مؤرشفة.';
        if (!app(ProductAttributeService::class)->available($item)) $reasons[]='سمة الباقة أو سمتها الرئيسية متوقفة أو غير صالحة لهذه الخدمة.';
        $tier=Schema::hasTable('hourly_package_tiers')
            ? ($item->package_tier_id ? $item->packageTier : \App\Models\Hourly\HourlyPackageTier::where('code','one_off')->first()) : null;
        if (Schema::hasTable('hourly_package_tiers') && (!$tier || !$tier->is_active)) $reasons[]='نمط الباقة متوقف أو غير موجود.';
        $once=$tier ? $tier->frequency==='one_off' : $item->visits_count<=1;
        if (!$rules->settings()->value($item,$once?'show_one_off':'show_packages')) $reasons[]=$once
            ? 'عرض الزيارة الواحدة للعملاء معطّل في إعدادات الخدمة أو تخصيصات الباقة.'
            : 'عرض الباقات للعملاء معطّل في إعدادات الخدمة أو تخصيصات الباقة.';
        $cities=$item->effectiveCities()->get();
        if ($cities->isEmpty()) $reasons[]='لا توجد مدينة نشطة ضمن نطاق الباقة أو تغطية الخدمة.';
        elseif (($allowed=HourlyRentalSettings::get('city_ids',[])) && $cities->whereIn('id',$allowed)->isEmpty()) $reasons[]='مدن الباقة خارج نطاق تشغيل نظام الساعات.';
        if ($reasons) return ['visible'=>false,'reasons'=>$reasons];
        if (!Schema::hasTable('hourly_category_district')) return ['visible'=>false,'reasons'=>['لم تُهيأ تغطية أحياء الخدمة.']];
        $districts=$this->districts($item);
        if ($districts->isEmpty()) return ['visible'=>false,'reasons'=>['لا يوجد حي تغطية مفعّل ضمن مدن الباقة ونطاق التشغيل.']];
        if (!Schema::hasTable('maids')) return ['visible'=>false,'reasons'=>['لم تُهيأ بيانات العاملات.']];
        $hasTeam=false;$hasEligible=false;$hiddenWorkers=false;$hiddenNationalities=false;$seenTeams=[];
        $required=$this->requiredWorkers($item);$best=null;$withoutSchedule=false;
        foreach ($districts as $district) {
            [, $coverage]=$rules->assertDistrictCovered($category,(int)$district->city_id,null,null,null,$district->id);
            if (!$coverage?->team_id) continue;
            $hasTeam=true;
            if (isset($seenTeams[$coverage->team_id])) continue;
            $seenTeams[$coverage->team_id]=true;
            $visible=$this->candidates($item,$district);
            $ready=$visible->filter(fn($worker)=>$this->hasWorkDay($worker));
            if ($ready->count()>=$required) return ['visible'=>true,'reasons'=>[
                'الباقة وفئتها مفعّلتان، ولها في الموقع عاملات ظاهرات بالعدد الذي تحتاجه كل زيارة ('.$required.').',
                'تظهر بعد تسجيل الدخول واختيار المدينة المناسبة، ولا تُعرض إلا الأوقات التي يتوفر فيها هذا العدد فعلًا.',
            ]];
            if ($visible->isNotEmpty() && (!$best || $ready->count()>$best[1])) $best=[$district->name,$ready->count()];
            $withoutSchedule=$withoutSchedule || $ready->count()<$visible->count();
            $eligible=app(HourlyWorkerAssignment::class)->candidates($item,$coverage->team_id,
                (bool)$rules->settings()->value($item,'assign_by_product_attribute'))->get();
            $hasEligible=$hasEligible || $eligible->isNotEmpty();
            if (Schema::hasColumn('maids','is_show')) $hiddenWorkers=$hiddenWorkers || $eligible->contains(fn($worker)=>!$worker->is_show);
            if (Schema::hasColumn('nationalities','is_show')) $hiddenNationalities=$hiddenNationalities || $eligible->contains(fn($worker)=>$worker->nationality && !$worker->nationality->is_show);
        }
        if ($best) {
            $reasons[]='عدد العاملات المطلوب لكل زيارة: '.$required.'، وأعلى عدد جاهز في أحياء التغطية: '.$best[1].' ('.$best[0].').';
            if ($withoutSchedule) $reasons[]='بعض العاملات المؤهلات جداول عملهن كلها إجازة، فلا يُحتسبن.';
        }
        if ($hasEligible) {
            if ($hiddenWorkers) $reasons[]='توجد عاملات مؤهلات في فرق التغطية لكنهن مخفيات عن الموقع. فعّل خيار إظهار العاملة للعاملات المطلوبات.';
            if ($hiddenNationalities) $reasons[]='جنسيات عاملات مؤهلات غير معروضة. فعّل ظهور الجنسيات المطلوبة في الموقع.';
        }
        if (!$reasons) $reasons[]=$hasTeam
            ? 'لا توجد عاملات مؤهلات لهذه الباقة في فرق التغطية. راجع نمط الساعات والإيقاف وأهلية التأجير وربط الخدمة ومواصفات الباقة.'
            : 'لا يوجد فريق نشط مرتبط بأحياء تغطية الخدمة.';
        return ['visible'=>false,'reasons'=>$reasons];
    }

    public function categoryPublicationStatus(\App\Models\Hourly\HourlyServiceCategory $category, ?array $reports=null): array
    {
        $reports ??= $category->packages()->with(['cities','serviceCategory'])->get()->map(fn($item)=>$this->publicationStatus($item))->all();
        $shown=collect($reports)->where('visible',true)->count();
        return ['visible'=>$shown>0,'reasons'=>$shown>0
            ? [($shown===1 ? 'تظهر الخدمة لأن لديها باقة واحدة تستوفي شروط الظهور في الموقع.' : 'تظهر الخدمة لأن عدد باقاتها التي تستوفي شروط الظهور في الموقع: '.$shown.'.'),'الحجز يتطلب تسجيل الدخول واختيار مدينة مغطاة.']
            : (count($reports) ? array_values(array_unique(array_merge(...array_column($reports,'reasons')))) : ['لا توجد باقات مرتبطة بهذه الخدمة.'])];
    }

    /** Prefer geographic boundaries; use an unambiguous district name when boundaries are unavailable. */
    public function districtForAddress(int $cityId, ?float $latitude, ?float $longitude, string $address): ?ServiceDistrict
    {
        $districts=ServiceDistrict::where('city_id',$cityId)->where('is_active',true)->get();
        if ($latitude !== null && $longitude !== null) {
            $inside=$districts->filter(fn($district)=>\App\Models\Hourly\HourlyTeamArea::pointInPolygon($latitude,$longitude,$district->polygon??[]));
            if ($inside->count()===1) return $inside->first();
            if ($inside->count()>1) return null;
        }
        $normalize=fn($value)=>trim(preg_replace('/[^\p{L}\p{N}]+/u',' ',mb_strtolower(str_replace(['أ','إ','آ','ـ'],['ا','ا','ا',''],(string)$value))));
        $text=' '.$normalize($address).' ';
        $matches=$districts->filter(function($district)use($normalize,$text){
            foreach ([$district->name,$district->name_en] as $name) {
                $name=preg_replace('/^(حي|district)\s+/u','',$normalize($name));
                if ($name!=='' && str_contains($text,' '.$name.' ')) return true;
            }
            return false;
        });
        return $matches->count()===1 ? $matches->first() : null;
    }

    public function items()
    {
        return HourlyPackage::where('is_active',true)->where('show_on_app',true)->where('is_private',false)
            ->whereHas('serviceCategory',fn($q)=>$q->where('is_active',true)->whereNull('deleted_at'))
            ->with(['translations','cities','serviceCategory'])->orderBy('sort_order')->get()
            ->filter(fn($item)=>app(HourlyBookingRules::class)->visibleToCustomers($item));
    }

    public function item(int $id): HourlyPackage
    {
        $item=$this->items()->firstWhere('id',$id);
        abort_unless($item,404);
        return $item;
    }

    public function districts(HourlyPackage $item)
    {
        $cities=$item->effectiveCities()->get()->pluck('id');
        $allowed=HourlyRentalSettings::get('city_ids',[]);
        return $item->serviceCategory->districts()->where('hourly_service_districts.is_active',true)
            ->wherePivot('is_active',true)->whereIn('city_id',$cities)
            ->when($allowed,fn($q)=>$q->whereIn('city_id',$allowed))->orderBy('name')->get();
    }

    public function candidates(HourlyPackage $item, ServiceDistrict $district, bool $lock=false)
    {
        [, $coverage]=app(HourlyBookingRules::class)->assertDistrictCovered($item->serviceCategory,(int)$district->city_id,null,null,null,$district->id);
        $query=app(HourlyWorkerAssignment::class)->candidates($item,$coverage->team_id,
            (bool)app(HourlyBookingRules::class)->settings()->value($item,'assign_by_product_attribute'));
        if (Schema::hasColumn('maids','is_show')) $query->where('maids.is_show',true);
        if (Schema::hasColumn('nationalities','is_show')) $query->whereDoesntHave('nationality',fn($q)=>$q->where('is_show',false));
        return ($lock ? $query->orderBy('maids.id')->lockForUpdate() : $query)->get();
    }

    /**
     * مصدر الجاهزية الوحيد: أقل عدد عاملات تطلبه الزيارة من العميل. تستخدمه الفهرسة والأحياء والشارة.
     */
    public function requiredWorkers(HourlyPackage $item): int
    {
        return app(HourlyBookingRules::class)->defaultCounts($item,'web_app')['workers'];
    }

    /** العاملات الظاهرات في الموقع لهذا الحي ولهن يوم عمل واحد على الأقل. */
    public function readyWorkers(HourlyPackage $item, ServiceDistrict $district)
    {
        return $this->candidates($item,$district)->filter(fn($worker)=>$this->hasWorkDay($worker))->values();
    }

    /** أحياء التغطية التي فيها العدد المطلوب من العاملات الجاهزات؛ غيرها لا يُعرض للعميل. */
    public function readyDistricts(HourlyPackage $item)
    {
        $required=$this->requiredWorkers($item);
        return $this->districts($item)->filter(fn($district)=>$this->readyWorkers($item,$district)->count()>=$required)->values();
    }

    private function hasWorkDay($worker): bool
    {
        return collect($worker->hourly_settings['work_schedule'] ?? [])->contains(fn($day)=>($day['mode'] ?? 'off')!=='off');
    }

    public function contexts(?int $maid=null): array
    {
        $result=[];
        foreach ($this->items() as $item) {
            if ($maid && !app(HourlyBookingRules::class)->settings()->value($item,'customer_worker_selection')) continue;
            $required=$this->requiredWorkers($item);
            foreach ($this->districts($item) as $district) {
                $workers=$this->readyWorkers($item,$district);
                if ($workers->count()<$required || ($maid && !$workers->contains('id',$maid))) continue;
                $result[]=['item'=>$item,'district'=>$district,'workers'=>$workers];
            }
        }
        return $result;
    }

    /** أحياء التغطية الجاهزة: فيها باقة معروضة للعملاء وعاملات كافيات. */
    public function coveredDistricts()
    {
        return collect($this->contexts())->pluck('district')->unique('id')->values();
    }

    /**
     * حي التغطية الجاهز الذي يقع فيه الموقع: المعرّف المعروف إن كان ضمن التغطية، ثم حدود الحي على الخريطة،
     * ثم نص العنوان (من القراءة العكسية للخريطة). null يعني أن الموقع خارج التغطية.
     */
    public function locate(float $latitude, float $longitude, ?int $districtId=null, string $text='', $covered=null): ?ServiceDistrict
    {
        $covered ??= $this->coveredDistricts();
        if ($districtId && ($known=$covered->firstWhere('id',$districtId))) return $known;
        $inside=$covered->filter(fn($district)=>is_array($district->polygon) && \App\Models\Hourly\HourlyTeamArea::pointInPolygon($latitude,$longitude,$district->polygon));
        if ($inside->count()===1) return $inside->first();
        if ($inside->count()>1 || trim($text)==='') return null;
        foreach ($covered->groupBy('city_id') as $cityId=>$districts) {
            $match=$this->districtForAddress((int)$cityId,$latitude,$longitude,$text);
            if ($match && $districts->contains('id',$match->id)) return $districts->firstWhere('id',$match->id);
        }
        return null;
    }

    /** Show only publicly visible workers with at least one bookable future interval. */
    public function availableWorkers(): array
    {
        $found=[];
        foreach ($this->contexts() as $context) {
            ['item'=>$item,'district'=>$district,'workers'=>$workers]=$context;
            $rules=app(HourlyBookingRules::class);
            if (!$rules->settings()->value($item,'customer_worker_selection')) continue;
            $required=$rules->defaultCounts($item,'web_app')['workers'];
            $last=min((int)$rules->settings()->value($item,'customer_future_days'),(int)$item->validity_days);
            for ($day=0;$day<=$last;$day++) {
                $slots=$this->slots($item,$district,now()->addDays($day)->toDateString(),$required,null,$workers,true);
                foreach ($slots as $slot) foreach ($slot['workers'] as $worker) $found[$worker->id]=$worker;
                if ($workers->every(fn($worker)=>isset($found[$worker->id]))) break;
            }
        }
        return $found;
    }

    /** Operating rules only (hours, notice, calendar, validity); customer times go through slots() for real capacity. */
    public function operatingSlots(HourlyPackage $item, ServiceDistrict $district, string $date, int $count, ?int $preferred=null): array
    {
        abort_unless($this->districts($item)->contains('id',$district->id),422,'الحي خارج تغطية هذه الخدمة.');
        $rules=app(HourlyBookingRules::class);
        if ($preferred && !$rules->settings()->value($item,'customer_worker_selection')) HourlyOrderService::reject('اختيار العاملة غير متاح لهذه الخدمة.');
        $rules->quote($item,$count,$rules->defaultCounts($item,'web_app')['quantity'],false);
        $snapshot=$item->toArray()+['service_district_id'=>$district->id];
        return array_values(array_filter(
            app(HourlySlotService::class)->forDate($date,(float)$item->hours_per_visit,$item->min_notice_hours,$snapshot),
            fn($slot)=>\Carbon\Carbon::parse($slot['ends_at'])->lte(now()->addDays($item->validity_days))
        ));
    }

    /** Times where the requested worker count is actually free for the whole visit — the same check store() repeats under lock. */
    public function slots(HourlyPackage $item, ServiceDistrict $district, string $date, int $count, ?int $preferred=null, $workers=null, bool $listing=false): array
    {
        $slots=$this->operatingSlots($item,$district,$date,$count,$preferred);
        $workers ??= $this->candidates($item,$district);
        $weekday=strtolower(\Carbon\Carbon::parse($date)->format('l'));
        $workers=$workers->filter(fn($worker)=>($worker->hourly_settings['work_schedule'][$weekday]['mode']??'off')!=='off');
        if ($workers->count()<$count || ($preferred && !$workers->contains('id',$preferred))) return [];
        $snapshot=$item->toArray()+['service_district_id'=>$district->id];
        [, $coverage]=app(HourlyBookingRules::class)->assertDistrictCovered($item->serviceCategory,
            (int)$district->city_id,null,null,null,$district->id);
        $capacity=app(PanelBookingService::class);
        $result=[];$seen=[];
        foreach ($slots as $slot) {
            $available=$workers->filter(fn($worker)=>app(HourlyWorkerAssignment::class)->available($worker,$slot['starts_at'],$slot['ends_at'],$snapshot));
            // Named assignments and reservations are already excluded by available(); subtract only unassigned demand.
            $free=$available->count()-$capacity->unassignedCapacity($coverage->team_id,$slot['starts_at'],$slot['ends_at']);
            if ($free<$count || ($preferred && !$available->contains('id',$preferred))) continue;
            $result[]=$slot+['workers'=>$available];
            if ($listing) {
                foreach ($available as $worker) $seen[$worker->id]=true;
                if (count($seen)===$workers->count()) break;
            }
        }
        return $result;
    }
}
