<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyPackage,HourlyRentalOrder,HourlyOrderPayment};
use App\Models\ingaz\UserAddress;
use App\Services\Hourly\{CustomerVisitActions,HourlyOrderService,HourlyPaymentService};
use App\Support\HourlyRentalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }
    public function packages(Request $request)
    {
        $data=$request->validate(['ready_only'=>['sometimes','boolean'],'city_id'=>['required','integer','exists:cities,id'],'service_category_id'=>['nullable','integer'],'attribute_id'=>['nullable','integer'],'package_tier_id'=>['nullable','integer']]);
        $cities=HourlyRentalSettings::get('city_ids');
        if ($cities && !in_array((int)$data['city_id'],$cities,true)) return response()->json(['data'=>[]]);
        if (!\App\Models\City::active()->whereKey($data['city_id'])->exists()) return response()->json(['data'=>[]]);
        $rules=app(\App\Services\Hourly\HourlyBookingRules::class);
        $items=HourlyPackage::where('is_active',true)->where('show_on_app',true)->where('is_private',false)->forCity($data['city_id'])->when($data['service_category_id']??null,fn($q,$id)=>$q->where('service_category_id',$id))->when($data['package_tier_id']??null,fn($q,$id)=>$q->where('package_tier_id',$id))->when($data['attribute_id']??null,fn($q,$id)=>$q->where('attribute_id',$id))->with('nationalities')->orderBy('sort_order')->get()
            // طبقة الفئات: فئة متوقفة تُخفي عناصرها، وshow_packages/show_one_off تحدد ما يُعرض للعملاء.
            ->filter(fn($item)=>!($category=$rules->category($item)) || ($category->is_active && !$category->trashed() && $rules->visibleToCustomers($item)))->values();
        $items->load('cities');
        if ($request->boolean('ready_only')) {
            $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
            $items=$items->filter(fn($item)=>$rules->category($item) && $catalog->readyDistricts($item)->contains('city_id',(int)$data['city_id']))->values();
        }
        return response()->json(['data'=>$items->map(fn($item)=>app(\App\Services\Hourly\CustomerHourlyData::class)->package($item))]);
    }
    public function package(Request $request, int $package)
    {
        $data=$request->validate(['city_id'=>['required','integer','exists:cities,id'],'workers'=>['nullable','integer','between:1,20'],'quantity'=>['nullable','integer','between:1,50']]);
        $cities=HourlyRentalSettings::get('city_ids');
        abort_if($cities && !in_array((int)$data['city_id'],$cities,true),404,__('hourly_api.city_unavailable'));
        $item=HourlyPackage::where('is_active',true)->where('show_on_app',true)->where('is_private',false)->forCity($data['city_id'])->findOrFail($package);
        abort_unless(\App\Models\City::active()->whereKey($data['city_id'])->exists(),404,__('hourly_api.city_unavailable'));
        $rules=app(\App\Services\Hourly\HourlyBookingRules::class);
        $category=$rules->category($item);
        abort_unless((!$category || ($category->is_active && !$category->trashed())) && $rules->visibleToCustomers($item),404,__('hourly_api.package_unavailable'));
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->package($item,$data['workers']??null,$data['quantity']??null)]);
    }
    public function cities()
    {
        $allowed=HourlyRentalSettings::get('city_ids');
        $cities=\App\Models\City::saudi()->active()->when($allowed,fn($q)=>$q->whereIn('id',$allowed))->orderBy('sort_order')->orderBy('id')->get();
        return response()->json(['data'=>$cities->map(fn($city)=>['id'=>$city->id,'name'=>$city->name ?: $city->name_ar,'name_ar'=>$city->name_ar,'name_en'=>$city->name_en])]);
    }
    public function addresses()
    {
        return response()->json(['data'=>UserAddress::where('user_id',auth('api')->id())->orderByDesc('id')->get()->map->only(['id','title','name','city_id','address','latitude','longitude','district','building','unit','contact_phone'])]);
    }
    public function address(Request $request)
    {
        $data=$request->validate(array_replace($this->addressRules(),['title'=>['required','string','max:255'],'name'=>['required','string','max:255'],'notes'=>['nullable','string','max:500'],'contact_phone'=>['required','string','max:30']]));
        $data['notes']=$data['notes']??'';
        // Shared address book has non-null legacy contact fields.
        $address=UserAddress::create($data+['user_id'=>auth('api')->id(),'phone'=>$data['contact_phone']??'','notes'=>'']);
        return response()->json(['data'=>$address],201);
    }
    private function addressRules(): array
    {
        return ['latitude'=>['required','numeric','between:-90,90'],'longitude'=>['required','numeric','between:-180,180'],
            'city_id'=>['required','integer','exists:cities,id'],'address'=>['required','string','max:500'],
            'district'=>['nullable','string','max:150'],'building'=>['nullable','string','max:255'],'unit'=>['nullable','string','max:255'],'contact_phone'=>['nullable','string','max:30']];
    }
    public function tiers(Request $request){
        $request->validate(['city_id'=>['required','integer','exists:cities,id'],'service_category_id'=>['required','integer','exists:hourly_service_categories,id']]);
        $items=$this->packages($request)->getData(true)['data'];
        $ids=collect($items)->pluck('package_tier_id')->map(fn($id)=>$id ?: \App\Models\Hourly\HourlyPackageTier::where('code','one_off')->value('id'))->unique();
        return response()->json(['data'=>\App\Models\Hourly\HourlyPackageTier::whereIn('id',$ids)->where('is_active',true)->orderBy('sort_order')->get()]);
    }
    public function slots(Request $request)
    {
        $data=$request->validate(['preferred_maid_id'=>['nullable','integer','exists:maids,id'], 'date'=>['required','date_format:Y-m-d'], 'city_id'=>['required','integer','exists:cities,id'], 'hours'=>['required_without:hourly_package_id','numeric','min:0.5','max:12'], 'hourly_package_id'=>['nullable','integer'], 'district_id'=>['nullable','integer'], 'workers_per_visit'=>['nullable','integer','between:1,20']]);
        $cities=HourlyRentalSettings::get('city_ids');
        if ($cities && !in_array((int)$data['city_id'],$cities,true)) return response()->json(['data'=>[]]);
        if (!\App\Models\City::active()->whereKey($data['city_id'])->exists()) return response()->json(['data'=>[]]);
        $package=isset($data['hourly_package_id']) ? HourlyPackage::where('is_active',true)->where('show_on_app',true)->where('is_private',false)->forCity($data['city_id'])->findOrFail($data['hourly_package_id']) : null;
        if($package && ($category=app(\App\Services\Hourly\HourlyBookingRules::class)->category($package))) {
            abort_unless($category->is_active && !$category->trashed() && app(\App\Services\Hourly\HourlyBookingRules::class)->visibleToCustomers($package),404,__('hourly_api.package_unavailable'));
            $district=$data['district_id']??null;
            if(!$district || !$category->districts()->where('hourly_service_districts.id',$district)->where('city_id',$data['city_id'])->where('hourly_service_districts.is_active',true)->wherePivot('is_active',true)->exists())return response()->json(['data'=>[]]);
        }
        $defaults=$package ? app(\App\Services\Hourly\HourlyBookingRules::class)->defaultCounts($package,'web_app') : ['workers'=>1,'quantity'=>1];
        $workers=(int)($data['workers_per_visit']??$defaults['workers']);
        if($package && $workers<$package->workers_per_visit)HourlyOrderService::reject('عدد العاملات أقل من عدد الباقة.');
        if($package)app(\App\Services\Hourly\HourlyBookingRules::class)->quote($package,$workers,$defaults['quantity'],false);
        $slots=app(\App\Services\Hourly\HourlySlotService::class)->forDate($data['date'],(float)($package->hours_per_visit ?? $data['hours']),$package->min_notice_hours ?? null,$package ? $package->toArray()+['service_district_id'=>$data['district_id'] ?? null] : []);
        if($package)$slots=array_values(array_filter($slots,fn($slot)=>\Carbon\Carbon::parse($slot['ends_at'])->lte(now()->addDays($package->validity_days))));
        if($package && isset($category) && $category){
            $settings=app(\App\Services\Hourly\HourlyCategorySettings::class);
            if(!empty($data['preferred_maid_id']) && !$settings->value($package,'customer_worker_selection'))HourlyOrderService::reject('اختيار العاملة غير متاح للعملاء في هذه الفئة.');
            if($settings->value($package,'auto_assign','web_app') || !empty($data['preferred_maid_id'])){
                [, $coverage]=app(\App\Services\Hourly\HourlyBookingRules::class)->assertDistrictCovered($category,(int)$data['city_id'],null,null,null,(int)$data['district_id']);
                $teamId=$coverage?->team_id;
                $assignment=app(\App\Services\Hourly\HourlyWorkerAssignment::class);
                $candidates=$assignment->candidates($package,$teamId,(bool)$settings->value($package,'assign_by_product_attribute'))->get();
                $slots=array_values(array_filter($slots,function($slot)use($candidates,$assignment,$package,$workers,$data){
                    $available=$candidates->filter(fn($maid)=>$assignment->available($maid,$slot['starts_at'],$slot['ends_at'],$package->toArray()));
                    return $available->count()>=$workers && (empty($data['preferred_maid_id']) || $available->contains('id',(int)$data['preferred_maid_id']));
                }));
            }
        }
        $dateOnly=$package && !app(\App\Services\Hourly\HourlyCategorySettings::class)->value($package,'show_time_options_app');
        return response()->json(['data'=>$dateOnly ? array_slice($slots,0,1) : $slots,
            'time_selection'=>$dateOnly?'date':'time']);
    }
    /** Additive endpoint: existing package, slot and order response contracts stay unchanged. */
    public function availableWorkers(Request $request,int $package)
    {
        $data=$request->validate(['city_id'=>'required|integer|exists:cities,id',
            'district_id'=>'required|integer|exists:hourly_service_districts,id',
            'date'=>'required|date_format:Y-m-d','starts_at'=>'nullable|date_format:Y-m-d H:i:s',
            'workers_per_visit'=>'nullable|integer|between:1,20']);
        // Reuse the existing customer package access checks (city, publication and category).
        $this->package($request,$package);
        $item=HourlyPackage::findOrFail($package);
        $rules=app(\App\Services\Hourly\HourlyBookingRules::class);
        abort_unless($rules->category($item),404,__('hourly_api.package_unavailable'));
        $dateOnly=!$rules->settings()->value($item,'show_time_options_app');
        $enabled=(bool)$rules->settings()->value($item,'customer_worker_selection');
        $district=\App\Models\Hourly\ServiceDistrict::findOrFail($data['district_id']);
        abort_unless((int)$district->city_id===(int)$data['city_id'],422,'الحي خارج المدينة المختارة.');
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        abort_unless($catalog->districts($item)->contains('id',$district->id),422,'الحي خارج تغطية الباقة.');
        if(!$enabled)return response()->json(['data'=>[],'selection_enabled'=>false,'time_selection'=>$dateOnly?'date':'time']);
        if(!$dateOnly && empty($data['starts_at']))throw \Illuminate\Validation\ValidationException::withMessages(['starts_at'=>'اختر موعد الزيارة لعرض العاملات المتاحات.']);
        if(!empty($data['starts_at']) && substr($data['starts_at'],0,10)!==$data['date'])throw \Illuminate\Validation\ValidationException::withMessages(['starts_at'=>'الموعد لا يطابق تاريخ الزيارة.']);
        $count=(int)($data['workers_per_visit']??$rules->defaultCounts($item,'web_app')['workers']);
        if($count<$item->workers_per_visit)HourlyOrderService::reject('عدد العاملات أقل من عدد الباقة.');
        $slots=collect($catalog->slots($item,$district,$data['date'],$count));
        if(!$dateOnly)$slots=$slots->where('starts_at',$data['starts_at']);
        $workers=$slots->flatMap(fn($slot)=>$slot['workers'])->unique('id')->map(fn($worker)=>[
            'id'=>$worker->id,'name'=>$worker->name,'photo'=>$worker->photo_url,
            'nationality'=>$worker->nationality?->sub_title ?? $worker->nationality?->title ?? '',
        ])->values();
        return response()->json(['data'=>$workers,'selection_enabled'=>true,'time_selection'=>$dateOnly?'date':'time',
            'selection_mode'=>'optional_partial','required_count'=>$count,'min_selected'=>0,'max_selected'=>$count,
            'worker_groups'=>$slots->map(fn($slot)=>$slot['workers']->pluck('id')->map(fn($id)=>(int)$id)->values()->all())->unique()->values()]);
    }

    public function location(Request $request, int $id)
    {
        $address=UserAddress::where('user_id',auth('api')->id())->findOrFail($id);
        $data=$request->validate($this->addressRules());
        $address->update($data);
        return response()->json(['data'=>$address]);
    }
    public function store(Request $request,HourlyOrderService $service)
    {
        $rules=OrderController::rules()+['booking_token'=>['nullable','uuid']];
        unset($rules['payment_channel']);
        // package_id اسم بديل لـ hourly_package_id، مطابق لمسار packages/{package_id}.
        if($request->filled('package_id') && !$request->has('hourly_package_id'))$request->merge(['hourly_package_id'=>$request->input('package_id')]);
        // التطبيق يرسل 2026-09-27T09:00 و/slots ترجع الثواني؛ كلاهما يعني نفس الموعد.
        if($request->has('scheduled_starts'))$request->merge(['scheduled_starts'=>\App\Support\HourlyTimeInput::normalizeAll($request->input('scheduled_starts'))]);
        $data=$request->validate($rules);
        // A stable UUID for a checkout prevents duplicate orders on a network retry.
        $order=\Illuminate\Support\Facades\DB::transaction(function()use($service,$data,$request){
            if(!empty($data['booking_token'])){
                \App\Models\ingaz\User::whereKey(auth('api')->id())->lockForUpdate()->firstOrFail();
                $existing=HourlyRentalOrder::where('booking_token',$data['booking_token'])->first();
                if($existing){
                    abort_unless((int)$existing->user_id===(int)auth('api')->id() && $existing->source==='app',409,__('hourly_api.booking_token_taken'));
                    if(($existing->booking_metadata['customer_request']??null)!=$data)abort(409,__('hourly_api.booking_token_mismatch'));
                    return $existing;
                }
            }
            $order=$service->create(auth('api')->id(),$data,null,$request->attributes->get('hourly_website_candidates'));
            if(!empty($data['booking_token']))$order->update(['booking_token'=>$data['booking_token'],'booking_metadata'=>['customer_request'=>$data]]);
            return $order;
        });
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->order($order->fresh())],201);
    }
    public function index()
    {
        $orders=HourlyRentalOrder::where('user_id',auth('api')->id())->with('visits')->latest('id')->paginate(20);
        $orders->setCollection($orders->getCollection()->map(fn($order)=>app(\App\Services\Hourly\CustomerHourlyData::class)->order($order,false)));
        return response()->json($orders);
    }
    private function owned(int $id): HourlyRentalOrder { return HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id); }
    public function show(int $id) { return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->order($this->owned($id))]); }
    public function visit(int $id, int $visit)
    {
        $order=$this->owned($id);
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($order->visits()->findOrFail($visit),$order)]);
    }
    /** موقع القادم إلى العميل لهذه الزيارة؛ نظير POST location الذي يكتب النقاط. */
    public function tracking(int $id, int $visit, \App\Services\Hourly\VisitTracking $tracking)
    {
        $order=$this->owned($id);
        return response()->json(['data'=>$tracking->forVisit($order->visits()->findOrFail($visit),$order)])
            ->header('Cache-Control','no-store');
    }
    /** موعد الزيارة بصيغة Y-m-d H:i، بعد قبول الفاصل T والثواني والإزاحة الصريحة. */
    private function validateStart(Request $request): array
    {
        $request->merge(['scheduled_start'=>\App\Support\HourlyTimeInput::normalize($request->input('scheduled_start'))]);
        $data = $request->validate([
            'scheduled_start'=>['required_without:scheduled_date','nullable','date_format:Y-m-d H:i'],
            'scheduled_date'=>['required_without:scheduled_start','nullable','date_format:Y-m-d'],
        ]);
        if (!empty($data['scheduled_start']) && !empty($data['scheduled_date'])) HourlyOrderService::reject('أرسل تاريخ الزيارة أو موعدها، وليس كليهما.');
        return ['scheduled_start'=>$data['scheduled_start'] ?? $data['scheduled_date']];
    }
    public function schedule(Request $request, int $id, int $visit, HourlyOrderService $service)
    {
        $data=$this->validateStart($request);
        $order=$this->owned($id);
        $model=$service->schedule($order->visits()->findOrFail($visit),$data['scheduled_start']);
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($model,$order)]);
    }
    public function reschedule(Request $request, int $id, int $visit, HourlyOrderService $service)
    {
        $data=$this->validateStart($request);
        $model=$this->owned($id)->visits()->findOrFail($visit);
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($service->reschedule($model,$data['scheduled_start']),$this->owned($id))]);
    }
    public function cancel(int $id, int $visit, CustomerVisitActions $actions)
    {
        $order=$this->owned($id);
        $model=$actions->cancel($order->visits()->findOrFail($visit));
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($model,$order->fresh())]);
    }
    public function extraHour(Request $request, int $id, int $visit, CustomerVisitActions $actions)
    {
        // hours افتراضيها 1 كما يرسلها التطبيق، وrequest_token اختياري يجعل إعادة الإرسال آمنة صراحةً.
        $data=$request->validate(['hours'=>['nullable','integer','between:1,6'],'request_token'=>['nullable','uuid']]);
        $order=$this->owned($id);
        $result=$actions->extraHour($order->visits()->findOrFail($visit),(int)($data['hours']??1),$data['request_token']??null);
        $order=$order->fresh();
        $due=round(max(0,(float)$order->total_amount-(float)$order->paid_amount),2);
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($result['visit'],$order)
            +['extra_hour'=>['hours'=>$result['hours'],'amount'=>$result['amount'],'currency'=>'SAR','amount_due'=>$due,'payment_status'=>$order->payment_status]]]);
    }
    public function rate(Request $request, int $id, int $visit, CustomerVisitActions $actions)
    {
        // note هو الحقل الذي يرسله التطبيق، وcomment اسم بديل مقبول.
        $data=$request->validate(['rating'=>['required','integer','between:1,5'],'note'=>['nullable','string','max:1000'],'comment'=>['nullable','string','max:1000']]);
        $order=$this->owned($id);
        $result=$actions->rate($order->visits()->findOrFail($visit),(int)$data['rating'],$data['note']??$data['comment']??null);
        return response()->json(['data'=>app(\App\Services\Hourly\CustomerHourlyData::class)->visit($result['visit']->fresh(),$order->fresh())]);
    }
    public function invoice(int $id) { return app(OrderController::class)->invoice($this->owned($id)); }
    public function pay(int $id,HourlyPaymentService $service) { return response()->json(['data'=>$service->initiate($this->owned($id))->only(['public_id','status','amount','payment_url'])]); }
    public function payment(int $id,HourlyPaymentService $service)
    {
        $payment=$this->owned($id)->payments()->where('provider','moyasar')->latest('id')->firstOrFail();
        return response()->json(['data'=>$service->synchronize($payment)->only(['public_id','status','amount','payment_url'])]);
    }
    public function bankTransfer(Request $request, int $id, HourlyPaymentService $service)
    {
        $data=$request->validate([
            'receipt'=>['required','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'reference_number'=>['nullable','string','max:255'],
        ]);
        $path=$request->file('receipt')->store('hourly/payments/bank-transfers','local');
        try {
            $service->submitBankTransfer($this->owned($id),$path,$data['reference_number']??null);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        return response()->json(['status'=>true,'data'=>['payment_status'=>'pending_review']]);
    }
    public function callback(Request $request,HourlyPaymentService $service)
    {
        $data=$request->validate(['payment'=>['required','uuid']]);
        $payment=HourlyOrderPayment::where('public_id',$data['payment'])->where('provider','moyasar')->firstOrFail();
        return response()->json(['status'=>$service->synchronize($payment)->status]);
    }
    public function webhook(Request $request,HourlyPaymentService $service)
    {
        // Treat the notification only as a hint. Re-fetch the stored invoice server-side.
        $reference=$request->input('data.invoice_id') ?? $request->input('data.id') ?? $request->input('invoice_id') ?? $request->input('id');
        if (!is_string($reference) || strlen($reference)>255) return response()->json(['received'=>true]);
        $payment=HourlyOrderPayment::where('provider','moyasar')->where('provider_reference',$reference)->first();
        if ($payment) $service->synchronize($payment);
        return response()->json(['received'=>true]);
    }
}
