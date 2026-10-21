<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Services\Rental\RentalMaidQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HourlyMaidController extends Controller
{
    public function loginAs(Request $request, Maid $maid)
    {
        abort_unless(AdminGuardHasPerm('hourly_maids_impersonate'),403);
        $this->assertHourly($maid);
        $account = \App\Models\Hourly\HourlyMaidAccount::findOrFail($maid->id);
        if (!$account->canLoginVia('web') || $maid->hourly_is_suspended) {
            return back()->withErrors(['maid' => 'لا يمكن الدخول كعاملة: الحساب معطل أو معلّق أو لا يسمح بالدخول من الموقع.']);
        }

        $admin = auth('admin')->user();
        activity('impersonation')->causedBy($admin)->performedOn($maid)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'guard' => 'hourly_maid'])
            ->log('فتح بوابة العاملة بحساب '.$maid->name);
        auth('hourly_maid')->login($account);
        $request->session()->put('hourly_maid_version', $account->hourly_auth_version);
        $request->session()->put('hourly_maid_impersonator', ['id' => $admin->id, 'name' => $admin->name]);

        return redirect()->route('hourly-maid.home');
    }

    public function show(Request $request, Maid $maid, string $tab = 'profile')
    {
        $this->assertHourly($maid);
        $tabs=['profile'=>'ملف العاملة','logs'=>'السجل','calendar'=>'التقويم','orders'=>'الطلبات','visits'=>'الزيارات','leaves'=>'الإجازات','reviews'=>'التقييمات','report'=>'التقرير','payments'=>'التحصيل','preferences'=>'التفضيلات'];
        abort_unless(isset($tabs[$tab]),404);
        $maid->load(['nationality','hourlyTeamMemberships.team']);
        $visitsQuery=\App\Models\Hourly\HourlyVisit::whereHas('workers',fn($q)=>$q->where('maids.id',$maid->id));
        $stats=[];
        foreach (['completed'=>['completed'],'pending'=>['unscheduled','scheduled','en_route','delivered','in_progress','worker_finished','picked_up']] as $key=>$statuses) {
            $summary=(clone $visitsQuery)->whereIn('status',$statuses)->selectRaw('COUNT(*) as visits, COUNT(DISTINCT hourly_rental_order_id) as orders, COALESCE(SUM(planned_hours),0) as hours')->first();
            $stats[$key]=['visits'=>(int)$summary->visits,'orders'=>(int)$summary->orders,'hours'=>(float)$summary->hours];
        }
        $visits=in_array($tab,['visits','report']) ? (clone $visitsQuery)->with('order')->orderBy('scheduled_start_at')->orderBy('id')->paginate(20)->withQueryString() : null;
        $orders=$tab==='orders' ? \App\Models\Hourly\HourlyRentalOrder::whereIn('id',(clone $visitsQuery)->select('hourly_rental_order_id'))->latest('id')->paginate(20) : null;
        $logs=$tab==='logs' ? app(\App\Services\Hourly\MaidActivityLog::class)->paginate($maid) : null;
        $leaves=$tab==='leaves' && \Illuminate\Support\Facades\Schema::hasTable('hourly_worker_leaves')
            ? DB::table('hourly_worker_leaves')->where('maid_id',$maid->id)->latest('id')->paginate(20)->withQueryString()
            : null;
        $services=\App\Models\Hourly\HourlyServiceCategory::ordered()->get();
        $canEdit=AdminGuardHasPerm('hourly_maids_edit');
        // بطاقة «غير معروضة للحجز» في رأس الصفحة فقط؛ تبويبات AJAX لا تعرضها.
        $bookability=$request->ajax() ? null : app(\App\Services\Hourly\WorkerBookability::class)->report($maid);
        // التنقّل بين التبويبات يجلب محتوى التبويب وحده دون هيكل الصفحة.
        return view($request->ajax() ? 'admin.hourly_maids._tab' : 'admin.hourly_maids.show',compact('maid','tab','tabs','stats','visits','orders','logs','leaves','services','canEdit','bookability'));
    }

    public function leaveStatus(Request $request, Maid $maid, int $leave)
    {
        $this->assertHourly($maid);
        abort_unless(AdminGuardHasPerm('hourly_maids_view') && AdminGuardHasPerm('hourly_maids_edit'),403);
        $data=$request->validate(['status'=>['required','in:approved,rejected']]);
        $row=DB::table('hourly_worker_leaves')->where('id',$leave)->where('maid_id',$maid->id)->first();
        abort_unless($row,404);
        if($data['status']==='approved'){
            $conflicts=\App\Models\Hourly\HourlyVisit::whereHas('workers',fn($q)=>$q->where('maids.id',$maid->id))
                ->whereNotIn('status',['completed','cancelled','cancelled_by_client','expired','no_show'])
                ->where('scheduled_start_at','<',$row->ends_at)->where('scheduled_end_at','>',$row->starts_at)->count();
            if($conflicts>0)throw ValidationException::withMessages(['status'=>'لا يمكن اعتماد الإجازة قبل إعادة إسناد '.$conflicts.' زيارة متعارضة.']);
        }
        DB::table('hourly_worker_leaves')->where('id',$leave)->update([
            'status'=>$data['status'],'is_active'=>$data['status']==='approved','updated_at'=>now(),
        ]);
        // العاملة طلبت الإجازة من التطبيق، فقرارها يجب أن يعود إليها هناك.
        app(\App\Services\Hourly\StaffPushService::class)
            ->leaveDecision((int)$maid->id,$data['status'],$row->starts_at ?? null,$row->ends_at ?? null);
        return back()->with('success',$data['status']==='approved'?'تم اعتماد الإجازة وتفعيلها في محرك الإتاحة.':'تم رفض طلب الإجازة.');
    }

    public function update(Request $request, Maid $maid)
    {
        $this->assertHourly($maid);
        abort_unless(AdminGuardHasPerm('hourly_maids_view') && AdminGuardHasPerm('hourly_maids_edit'),403);
        app(\App\Services\Hourly\MaidProfileService::class)->save($request,$maid);
        return redirect()->route('admin.hourly-maids.show',$maid)->with('success','تم حفظ ملف عاملة الساعات.');
    }

    /**
     * مصدر تقويم العاملة: زياراتها في المدى المطلوب، والإجازات، وأيام الراحة الأسبوعية،
     * ولكل يوم الحد الأقصى لساعات العمل والساعات المستهلكة.
     */
    public function calendar(Request $request, Maid $maid)
    {
        $this->assertHourly($maid);
        $data=$request->validate(['start'=>['required','date'],'end'=>['required','date','after:start']]);
        $from=\Carbon\Carbon::parse($data['start'])->startOfDay();
        $to=\Carbon\Carbon::parse($data['end'])->startOfDay();
        abort_if($from->diffInDays($to)>62,422,'المدى المطلوب أطول من اللازم.');

        $visits=\App\Models\Hourly\HourlyVisit::whereHas('workers',fn($q)=>$q->where('maids.id',$maid->id))
            ->where('scheduled_start_at','<',$to)->where('scheduled_end_at','>',$from)
            ->with(['order.user'])->withCount('workers as assigned_workers_count')->orderBy('scheduled_start_at')->get();
        $canOpenOrder=AdminGuardHasPerm('hourly_orders_view');
        $restMinutes=(int)round((float)\App\Support\HourlyRentalSettings::get('min_rest_hours_between_visits',0)*60);
        $events=[];
        foreach ($visits as $visit) {
            $cancelled=in_array($visit->status,['cancelled','cancelled_by_client','expired','no_show']);
            $kind=match (true) {
                $cancelled=>'cancelled',
                $visit->status==='completed'=>'completed',
                in_array($visit->status,['en_route','delivered','in_progress','worker_finished','picked_up'])=>'progress',
                $visit->assigned_workers_count<(int)$visit->workers_count=>'partial',
                default=>'assigned',
            };
            $events[]=[
                'id'=>'visit-'.$visit->id,
                'title'=>$visit->order?->user?->name ?: ('طلب #'.($visit->order?->order_number ?? $visit->hourly_rental_order_id)),
                'start'=>$visit->scheduled_start_at->toIso8601String(),
                'end'=>$visit->scheduled_end_at?->toIso8601String(),
                'classNames'=>['mp-ev','mp-ev-'.$kind],
                'url'=>$canOpenOrder ? route('admin.hourly-orders.show',$visit->hourly_rental_order_id) : null,
                'extendedProps'=>['status'=>__('hourly.'.$visit->status),'order'=>$visit->order?->order_number,'visit'=>$visit->visit_number,'hours'=>(float)$visit->planned_hours],
            ];
            // الفاصل الإلزامي بعد الزيارة كما في إعدادات الساعات.
            if (!$cancelled && $restMinutes>0 && $visit->scheduled_end_at) {
                $events[]=['id'=>'rest-'.$visit->id,'start'=>$visit->scheduled_end_at->toIso8601String(),'end'=>$visit->scheduled_end_at->copy()->addMinutes($restMinutes)->toIso8601String(),'display'=>'background','classNames'=>['mp-bg-rest']];
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('hourly_worker_leaves')) {
            foreach (\Illuminate\Support\Facades\DB::table('hourly_worker_leaves')->where('maid_id',$maid->id)->where('is_active',true)->where('starts_at','<',$to)->where('ends_at','>',$from)->get(['id','starts_at','ends_at']) as $leave) {
                $events[]=['id'=>'leave-'.$leave->id,'title'=>'إجازة','start'=>\Carbon\Carbon::parse($leave->starts_at)->toIso8601String(),'end'=>\Carbon\Carbon::parse($leave->ends_at)->toIso8601String(),'display'=>'background','classNames'=>['mp-bg-leave']];
            }
        }

        $schedule=$maid->hourly_settings['work_schedule'] ?? [];
        $dailyCap=(float)(\App\Support\HourlyRentalSettings::effectiveHourCaps()['per_day'] ?? 24);
        $used=$visits->reject(fn($v)=>in_array($v->status,['cancelled','cancelled_by_client','expired','no_show']))
            ->groupBy(fn($v)=>$v->scheduled_start_at->toDateString())->map(fn($day)=>(float)$day->sum('planned_hours'));
        $days=[];
        for ($day=$from->copy(); $day->lt($to); $day->addDay()) {
            $rule=$schedule[strtolower($day->format('l'))] ?? ['mode'=>'off'];
            $mode=$rule['mode'] ?? 'off';
            $max=match ($mode) {
                'off'=>0.0,
                'hours'=>round(max(0,\Carbon\Carbon::parse($rule['start'] ?? '00:00')->diffInMinutes(\Carbon\Carbon::parse($rule['end'] ?? '00:00'),false))/60,2),
                default=>24.0,
            };
            $date=$day->toDateString();
            $days[$date]=['max'=>min($max,$dailyCap),'used'=>$used[$date] ?? 0,'off'=>$mode==='off'];
            if ($mode==='off') $events[]=['id'=>'off-'.$date,'start'=>$date,'allDay'=>true,'display'=>'background','classNames'=>['mp-bg-off']];
        }

        return response()->json(['events'=>$events,'days'=>$days]);
    }

    private function assertHourly(Maid $maid): void
    {
        abort_unless($maid->rental_mode==='hourly' && $maid->jobTypes()->where('job_types.id',RentalMaidQuery::rentalJobTypeId())->exists(),404);
    }

    public function index(Request $request)
    {
        $data=$request->validate([
            'search'=>['nullable','string','max:150'],'state'=>['nullable','in:waiting,arrived,inside,ready,out_now,available_now,return_overdue'],'per_page'=>['nullable','in:10,25,50,100'],
            'attribute_id'=>['nullable','integer'],'team_id'=>['nullable','integer'],'nationality_id'=>['nullable','integer'],'availability'=>['nullable','in:available,suspended'],
        ]);
        // Do not use forJobType(): its arrival gate hides workers still in recruitment.
        $rentalTypeId=RentalMaidQuery::rentalJobTypeId();
        $base=Maid::query()->where('rental_mode','hourly')->whereHas('jobTypes',fn($q)=>$q->where('job_types.id',$rentalTypeId));
        $ready=RentalMaidQuery::applyNoActiveRental(RentalMaidQuery::applyReadyForRental(RentalMaidQuery::applyArrivalGate(clone $base)))->where('hourly_is_suspended',false);
        $overdueReturns=\App\Services\Hourly\OverdueWorkerReturn::pending();
        $ready->whereNotIn('maids.id',array_keys($overdueReturns));
        $overdueCount=(clone $base)->whereIn('maids.id',array_keys($overdueReturns))->count();
        $waiting=fn($q)=>$q->where('rental_source','outside')->whereNull('arrival_date')->whereDoesntHave('insideShelterContract');
        // الخروج اللحظي يُقرأ من حالة الزيارة لا من عقد الإيواء: العقد يبقى ساريًا طوال الزيارة.
        $presence=app(\App\Services\Hourly\ShelterPresence::class);
        // صفحة الساعات تعرض الزيارة الجارية دائمًا؛ مفتاح الربط يخص شاشات الإيواء فقط.
        $outNow=$presence->inVisitNow();
        $stats=['total'=>(clone $base)->count(),'waiting'=>$waiting(clone $base)->count(),'inside'=>RentalMaidQuery::applyInsideShelter(clone $base)->count(),'ready'=>(clone $ready)->count(),'out_now'=>count($outNow),'available_now'=>(clone $ready)->whereNotIn('maids.id',array_keys($outNow) ?: [0])->count()];
        $query=clone $base;
        if(!empty($data['attribute_id']))$query->whereHas('productAttributes',fn($q)=>$q->where('hourly_product_attributes.id',$data['attribute_id']));
        if (!empty($data['search'])) {
            $term='%'.$data['search'].'%';
            $query->where(fn($q)=>$q->where('name','like',$term)->orWhere('passport_num','like',$term)->orWhere('hourly_username','like',$term)->orWhere('phone_1','like',$term)->orWhere('id_number','like',$term));
        }
        if (!empty($data['team_id'])) $query->whereHas('hourlyTeamMemberships',fn($q)=>$q->where('hourly_team_id',$data['team_id']));
        if (!empty($data['nationality_id'])) $query->where('nationality_id',$data['nationality_id']);
        if (!empty($data['availability'])) $query->where('hourly_is_suspended',$data['availability']==='suspended');
        switch ($data['state'] ?? '') {
            case 'return_overdue': $query->whereIn('maids.id',array_keys($overdueReturns)); break;
            case 'waiting': $waiting($query); break;
            case 'arrived': $query->whereNotNull('arrival_date'); break;
            case 'inside': RentalMaidQuery::applyInsideShelter($query); break;
            case 'ready': $query->whereIn('maids.id',(clone $ready)->select('maids.id')); break;
            case 'out_now': $query->whereIn('maids.id',array_keys($outNow) ?: [0]); break;
            case 'available_now': $query->whereIn('maids.id',(clone $ready)->whereNotIn('maids.id',array_keys($outNow) ?: [0])->select('maids.id')); break;
        }
        $maids=$query->with(['nationality','hourlyTeamMemberships.team','autoRecruitmentContract','insideShelterContract.info','insideShelterContract.travelHistory'])
            ->withCount(['contracts as inside_shelter_count'=>fn($q)=>$q->insideShelter()])
            ->orderByDesc('id')->paginate((int)($data['per_page'] ?? 25))->withQueryString();
        $shelterExceptionHints=[];
        foreach ($maids as $maid) {
            if (!$maid->inside_shelter_count) continue;
            $contract=$maid->insideShelterContract;
            $hints=[];
            if (!$contract || !$contract->info) {
                $hints[]='بيانات عقد الإيواء غير مكتملة؛ أضف بيانات العقد ومكان الإيواء.';
            } elseif (!$contract->info->shelter_id) {
                $hints[]='مكان الإيواء غير محدد في بيانات العقد.';
            }
            $arrivalDate=$contract?->info?->arrival_date ?: ($maid->arrival_date ?: $contract?->travelHistory?->arrival_date);
            if ($maid->rental_source==='outside' && !$arrivalDate) {
                $hints[]='تاريخ الوصول غير مسجل في العقد أو ملف العاملة أو سجل السفر.';
            }
            if ($hints) $shelterExceptionHints[$maid->id]=implode(' ', $hints);
        }
        $readyIds=(clone $ready)->whereIn('maids.id',$maids->pluck('id'))->pluck('maids.id')->all();
        $suspendedCount=(clone $base)->where('hourly_is_suspended',true)->count();
        $filters=array_filter($data,fn($v)=>$v!==null && $v!=='');
        // خيارات الفلاتر: كل الفرق، والجنسيات الموجودة فعلًا بين عاملات الساعات فقط.
        $teams=\Illuminate\Support\Facades\Schema::hasTable('hourly_teams') ? \App\Models\Hourly\HourlyTeam::orderBy('name')->get(['id','name','is_active']) : collect();
        $nationalities=\Illuminate\Support\Facades\Schema::hasTable('nationalities') ? \App\Models\ingaz\Nationality::whereIn('id',(clone $base)->whereNotNull('nationality_id')->select('nationality_id'))->get() : collect();
        $attributes=\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes')?\App\Models\Hourly\HourlyProductAttribute::whereNotNull('parent_id')->with(['parent','category'])->orderBy('sort_order')->get():collect();
        $allocatableMaids=$this->allocatableFromShelterQuery()
            ->with('nationality')->orderBy('name')->orderBy('id')->get();
        return view('admin.hourly_maids.index',compact('maids','stats','readyIds','suspendedCount','filters','teams','nationalities','attributes','allocatableMaids','outNow','overdueReturns','overdueCount','shelterExceptionHints')
            +['shelterLinked'=>\App\Services\Hourly\ShelterPresence::linked(),'visitsTracked'=>\App\Services\Hourly\ShelterPresence::tracked(),
                'upcoming'=>$presence->upcomingSummary($maids->pluck('id')->all())]);
    }

    /** تخصيص عاملة تأجير جاهزة وموجودة فعليًا في الإيواء للعمل بنظام الساعات. */
    public function assignFromShelter(Request $request)
    {
        $data=$request->validate([
            'maid_id'=>['required','integer','exists:maids,id'],
        ], [], ['maid_id'=>'العاملة']);

        $maid=Maid::findOrFail((int)$data['maid_id']);
        if (!$this->allocatableFromShelterQuery()->whereKey($maid->id)->exists()) {
            throw ValidationException::withMessages([
                'maid_id'=>'العاملة لم تعد متاحة للتخصيص. حدّث الصفحة واختر عاملة أخرى.',
            ]);
        }
        // الخدمة تعيد الفحص داخل قفل المعاملة، وتشمل العقد النشط في أي فرع.
        $maid=app(\App\Services\Rental\RentalModeSwitcher::class)->toHourly($maid);

        return redirect()->route('admin.hourly-maids.show',$maid)
            ->with('success','تم تخصيص العاملة '.$maid->name.' لخدمة الساعات. أكمل إعداد الفريق والخدمات من ملفها.');
    }

    private function allocatableFromShelterQuery()
    {
        return app(\App\Services\Rental\RentalModeSwitcher::class)->hourlyCandidates();
    }

    public function availability(Request $request, Maid $maid)
    {
        abort_unless($maid->rental_mode==='hourly' && $maid->jobTypes()->where('job_types.id',RentalMaidQuery::rentalJobTypeId())->exists(),404);
        $data=$request->validate(['is_suspended'=>['required','boolean']]);
        $maid->forceFill(['hourly_is_suspended'=>(bool)$data['is_suspended']])->save();
        $ready=RentalMaidQuery::applyNoActiveRental(RentalMaidQuery::applyReadyForRental(RentalMaidQuery::applyArrivalGate(Maid::query())))
            ->where('rental_mode','hourly')->where('hourly_is_suspended',false)
            ->whereHas('jobTypes',fn($q)=>$q->where('job_types.id',RentalMaidQuery::rentalJobTypeId()));
        \App\Services\Hourly\OverdueWorkerReturn::exclude($ready);
        $returnReason=\App\Services\Hourly\OverdueWorkerReturn::reason((int)$maid->id);
        $isReady=(clone $ready)->whereKey($maid->id)->exists();
        return response()->json([
            'return_hold_reason'=>$returnReason,'is_suspended'=>$maid->hourly_is_suspended,'is_ready'=>$isReady,'ready_count'=>$ready->count(),
            'label'=>$maid->hourly_is_suspended ? 'معلّقة' : ($isReady ? 'متاحة للتأجير' : ($returnReason ? 'عودة متأخرة — غير متاحة للحجز' : 'غير متاحة حاليًا')),
            'message'=>$maid->hourly_is_suspended ? 'تم تعليق إتاحة العاملة للساعات.' : ($isReady ? 'تمت إتاحة العاملة للساعات.' : 'تم رفع التعليق؛ الإتاحة تنتظر استيفاء شروط الوصول والإيواء وجاهزية التأجير.'),
        ]);
    }
}
