<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory, ServiceDistrict};
use App\Models\ingaz\{User, UserAddress};
use App\Services\Hourly\{HourlyBookingRules, HourlySlotService, HourlyWorkerAssignment, PanelBookingService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rule;

class PanelBookingController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }

    public function create(Request $request)
    {
        $renewal=null;$renewalBlocked=null;
        if($request->filled('renew')){
            $request->validate(['renew'=>'required|integer|min:1']);
            $source=\App\Models\Hourly\HourlyRentalOrder::with('user')->findOrFail((int)$request->input('renew'));
            abort_unless($source->user && !$source->user->is_blocked,422);
            $reason=app(\App\Services\Hourly\RenewalEligibility::class)->reason($source);
            if($reason)return redirect()->route('admin.hourly-orders.show',$source)->withErrors(['hourly'=>$reason]);
            $renewal=app(\App\Services\Hourly\RenewalPlanner::class)->for($source);
            // طلب مجدَّد سابقًا: يُعرض رابط التجديد القائم، ولا يُعبّأ المعالج لتجديد ثانٍ إلا بصلاحية.
            if($renewal['renewed_by'] && !AdminGuardHasPerm('hourly_orders_renew_again')){$renewalBlocked=$renewal;$renewal=null;}
        }
        abort_unless(\Illuminate\Support\Facades\Schema::hasColumn('hourly_rental_orders','booking_token'),503,'يرجى تشغيل ترحيل معالج حجز الساعات أولًا.');
        return view('admin.hourly.booking-wizard',[
            'renewal'=>$renewal,'renewalBlocked'=>$renewalBlocked,
            'cities'=>\App\Models\City::saudi()->active()->get(['id','name_ar']),
            'districts'=>ServiceDistrict::where('is_active',true)->whereHas('city', fn ($city) => $city->saudi()->active())->get(['id','city_id','name']),
            'drivers'=>\App\Models\Hourly\HourlyDriver::where('is_active',true)->get(['id','name']),
            'canPrice'=>AdminGuardHasPerm('hourly_orders_price_override'),
            'canCollect'=>AdminGuardHasPerm('hourly_orders_collect'),
            // مزود الخرائط يُضبط من إعدادات الموديول؛ المجاني لا يحتاج مفتاحًا.
            'mapProvider'=>\App\Support\HourlyRentalSettings::mapProvider(),
            'mapsKey'=>\App\Support\HourlyRentalSettings::googleMapsKey(),
        ]);
    }

    /**
     * ترميز جغرافي مجاني عبر OpenStreetMap — يقابل Geocoding API حين يكون المزود المدفوع مطفأً.
     * يمر عبر الخادم لأن سياسة Nominatim تُلزم بتعريف التطبيق وبحد للمعدل لا يمكن ضبطهما في المتصفح.
     */
    public function geocode(Request $request, \App\Services\Hourly\CityLookupService $lookup)
    {
        $data=$request->validate([
            'latitude'=>'required_without:query|nullable|numeric|between:-90,90',
            'longitude'=>'required_with:latitude|nullable|numeric|between:-180,180',
            'query'=>'required_without:latitude|nullable|string|max:200',
        ]);
        return response()->json($lookup->address(
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
            $data['query'] ?? null,
        ));
    }

    public function customers(Request $request)
    {
        $data=$request->validate(['search'=>'nullable|string|max:100']);
        return User::query()->where('is_blocked',0)
            // المحذوف من قسم الساعات (ومنه الحسابات المدموجة) لا يُحجز له.
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('hourly_customer_profiles','deleted_at'),fn($q)=>$q->whereNotExists(fn($s)=>$s->from('hourly_customer_profiles as hp')->whereColumn('hp.user_id','users.id')->whereNotNull('hp.deleted_at')->select(\Illuminate\Support\Facades\DB::raw(1))))
            ->when(!empty($data['search']),fn ($q)=>$q->where(function ($q) use ($data) {
            $term='%'.$data['search'].'%'; $q->where('name','like',$term)->orWhere('email','like',$term)->orWhere('phone','like',$term);
        }))->select(['id','name','email','phone','hourly_wallet_balance'])->orderByDesc('id')->paginate(15);
    }

    public function addresses(Request $request)
    {
        $data=$request->validate(['user_id'=>'required|integer|exists:users,id']);
        return UserAddress::withoutDuplicates(UserAddress::query()->leftJoin('cities','cities.id','=','user_addresses.city_id')
            ->where('user_addresses.user_id',$data['user_id'])->select('user_addresses.*','cities.name_ar as city_name')
            ->orderByDesc('user_addresses.id')->get());
    }

    public function mapLink(Request $request)
    {
        $url=$request->validate(['url'=>'required|url|max:2000'])['url'];
        for ($i=0;$i<5;$i++) {
            $host=strtolower((string)parse_url($url,PHP_URL_HOST));
            abort_unless(parse_url($url,PHP_URL_SCHEME)==='https' && !parse_url($url,PHP_URL_USER) && !parse_url($url,PHP_URL_PASS)
                && (!parse_url($url,PHP_URL_PORT) || parse_url($url,PHP_URL_PORT)===443)
                && in_array($host,['maps.app.goo.gl','goo.gl','www.google.com','google.com','maps.google.com'],true),422,'استخدم رابط Google Maps صالحًا.');
            $decoded=urldecode($url);
            // /maps/search/24.71,+46.67 and /maps/dir/<from>/24.71,46.67: the point is in the path (the last one is
            // the destination); their @ is only the viewport centre.
            $pathPoint=preg_match('#/maps/(?:search|dir)/#',$decoded)
                && preg_match_all('#/(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#',(string)parse_url($decoded,PHP_URL_PATH),$points,PREG_SET_ORDER);
            if ($pathPoint) $match=end($points);
            if ($pathPoint
                || preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',$decoded,$match)
                || preg_match('/(?:@|[?&](?:q|query|ll|destination)=)(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',$decoded,$match)) {
                abort_unless(abs((float)$match[1])<=90 && abs((float)$match[2])<=180,422);
                return response()->json(['latitude'=>(float)$match[1],'longitude'=>(float)$match[2]]);
            }
            if (!in_array($host,['maps.app.goo.gl','goo.gl'],true)) break;
            $response=\Illuminate\Support\Facades\Http::timeout(8)->withOptions(['allow_redirects'=>false])->get($url);
            if (!$response->redirect() || !$response->header('Location')) break;
            $url=$response->header('Location');
        }
        throw \Illuminate\Validation\ValidationException::withMessages(['url'=>'تعذر استخراج الموقع من الرابط. افتح الرابط وانسخ رابط الخريطة الكامل أو ابحث باسم العنوان.']);
    }

    private function address(Request $request): array
    {
        if ($request->input('address_mode') === 'existing') {
            $data=$request->validate(['user_id'=>'required|integer|exists:users,id','address_id'=>'required|integer']);
            return UserAddress::where('user_id',$data['user_id'])->findOrFail($data['address_id'])->toArray();
        }
        return $request->validate(['address.city_id'=>'required|integer|exists:cities,id','address.hourly_district_id'=>'required|integer|exists:hourly_service_districts,id',
            'address.latitude'=>'required|numeric|between:-90,90','address.longitude'=>'required|numeric|between:-180,180'])['address'];
    }

    public function catalog(Request $request, PanelBookingService $service)
    {
        $address=$this->address($request);
        return HourlyServiceCategory::ordered()->get()->map(function ($category) use ($address,$service) {
            $activeItems=HourlyPackage::where('service_category_id',$category->id)->where('is_active',true)->get();
            $items=$activeItems->filter(function ($item) use ($address,$service) {
                try { $service->coverage($item,$address); return true; } catch (\Illuminate\Validation\ValidationException $e) { return false; }
            })->map(function ($item) use ($category) {
                $rules = app(HourlyBookingRules::class);
                $counts = $rules->defaultCounts($item, 'panel');
                $quote = $rules->quote($item, $counts['workers'], $counts['quantity'], true);
                return ['id'=>$item->id,'title'=>$item->title,'hours'=>$item->hours_per_visit,'visits'=>$item->visits_count,
                    'workers'=>$counts['workers'], 'quantity'=>$counts['quantity'], 'special'=>(bool)$item->is_private,
                    'price'=>($quote['price_display']==='per_visit' ? $quote['visit_prices'][0] : $quote['total'])/100,
                    'price_display'=>$quote['price_display'],
                    'booking_config'=>app(\App\Services\Hourly\HourlyCategoryConfig::class)->forCategory($category, 'panel', null, $item)];
            })->values();
            return ['id'=>$category->id,'name'=>$category->name,'available'=>$items->isNotEmpty(),'items'=>$items,
                'unavailable_message'=>$activeItems->isEmpty()
                    ? __('لا توجد عناصر خدمة مفعّلة لهذه الفئة - أضف عنصر خدمة وفعّله من الخدمات والباقات')
                    : __('لا توجد منطقة خدمة مفعّلة لهذه الخدمة - راجع مناطق الخدمة')];
        });
    }

    public function quote(Request $request, PanelBookingService $service)
    {
        $data=$request->validate(\App\Services\Hourly\HourlyBookingOptions::rules() + ['quantity'=>'nullable|integer|between:1,50','hourly_package_id'=>'required|integer|exists:hourly_packages,id','workers_per_visit'=>'required|integer|between:1,20','manual_total'=>'nullable|numeric|min:0.01|max:99999999','coupon_code'=>'nullable|string|max:100']);
        if (isset($data['manual_total'])) abort_unless(AdminGuardHasPerm('hourly_orders_price_override'),403);
        $package=HourlyPackage::findOrFail($data['hourly_package_id']);
        $service->coverage($package,$this->address($request));
        return response()->json($service->quote($package,$data)+['payment_methods'=>array_values(array_intersect(HourlyBookingRules::PANEL_PAYMENT_METHODS,app(HourlyBookingRules::class)->paymentMethods($package,'employee')))]);
    }

    public function availability(Request $request, PanelBookingService $service)
    {
        $data=$request->validate(['hourly_package_id'=>'required|integer|exists:hourly_packages,id','date'=>'required|date_format:Y-m-d','workers_per_visit'=>'required|integer|between:1,20','all_workers'=>'nullable|boolean']);
        $item=HourlyPackage::findOrFail($data['hourly_package_id']);
        $address=$this->address($request);
        [$district,$link]=$service->coverage($item,$address);
        $candidates=$service->candidates($item,$address,$request->boolean('all_workers'));
        // مفضلات العميل تظهر أولًا في قائمة العاملات لكل فترة.
        $candidates=app(\App\Services\Hourly\CustomerDirectory::class)->favoritesFirst($candidates,(int)$request->input('user_id') ?: null);
        $snapshot=$item->toArray()+['service_district_id'=>$district->id,'assign_by_product_attribute'=>(bool)app(HourlyBookingRules::class)->settings()->value($item,'assign_by_product_attribute')];
        $slots=app(HourlySlotService::class)->forDate($data['date'],(float)$item->hours_per_visit,$item->min_notice_hours,$snapshot,true);
        return response()->json(array_map(function ($slot) use ($candidates,$snapshot,$service,$link,$data,$item) {
            $workers=$candidates->filter(fn ($maid)=>app(HourlyWorkerAssignment::class)->available($maid,$slot['starts_at'],$slot['ends_at'],$snapshot));
            $free=$workers->count()-$service->unassignedCapacity($link->team_id,$slot['starts_at'],$slot['ends_at']);
            $withinValidity=\Carbon\Carbon::parse($slot['ends_at'])->lte(now()->addDays($item->validity_days));
            $available=$free >= $data['workers_per_visit'] && $withinValidity;
            $reason=null;
            if (!$available) {
                if (!$link->team_id && empty($data['all_workers'])) $reason=__('الحي غير مربوط بفريق خدمة. اربط فريقًا مفعّلًا بالحي من مناطق الخدمة.');
                elseif ($candidates->isEmpty()) $reason=__('لا توجد عاملات مؤهلات للخدمة في الفريق. راجع تفعيل الفريق وعضوية العاملات وجاهزيتهن.');
                elseif (!$withinValidity) $reason=__('الموعد خارج صلاحية باقة الخدمة.');
                else $reason=__('المتاح :available من أصل :required عاملات مطلوبات. راجع جدول العمل والإجازات والحجوزات أو اختر موعدًا آخر.', ['available'=>max(0,$free),'required'=>$data['workers_per_visit']]);
            }
            return $slot+['available'=>$available,'unavailable_reason'=>$reason,
                'workers'=>$workers->map(fn ($maid)=>['id'=>$maid->id,'name'=>$maid->name])->values()];
        },$slots));
    }

    public function store(Request $request, PanelBookingService $service)
    {
        $request->validate(['booking_token'=>'required|uuid']);
        $existing=\App\Models\Hourly\HourlyRentalOrder::where('booking_token',$request->input('booking_token'))->first();
        if ($existing) {
            abort_unless((int)$existing->created_by_admin_id === (int)auth('admin')->id(),403);
            return response()->json(['id'=>$existing->id,'redirect'=>route('admin.hourly-orders.summary',$existing)]);
        }
        $data=$request->validate(\App\Services\Hourly\HourlyBookingOptions::rules() + [
            'booking_token'=>'required|uuid','customer_mode'=>'required|in:existing,new','address_mode'=>'required|in:existing,new',
            'quantity'=>'nullable|integer|between:1,50',
            'renewed_from_order_id'=>'nullable|integer|exists:hourly_rental_orders,id',
            'user_id'=>'required_if:customer_mode,existing|nullable|integer|exists:users,id','address_id'=>'required_if:address_mode,existing|nullable|integer',
            'customer'=>'required_if:customer_mode,new|array','address'=>'required_if:address_mode,new|array',
            'hourly_package_id'=>'required|integer|exists:hourly_packages,id','workers_per_visit'=>'required|integer|between:1,20',
            'manual_total'=>'nullable|numeric|min:0.01|max:99999999','coupon_code'=>'nullable|string|max:100',
            'all_workers'=>'required|boolean','payment_method'=>['required',Rule::in(HourlyBookingRules::PANEL_PAYMENT_METHODS)],
            'collect_now'=>'required|boolean','notify_email'=>'required|boolean','notify_app'=>'required|boolean','notes'=>'nullable|string|max:5000',
            'cash_amount'=>'nullable|numeric|min:0|max:99999999',
            'visits'=>'required|array|min:1|max:365','visits.*.starts_at'=>'required|date_format:Y-m-d H:i|distinct',
            'visits.*.worker_ids'=>'required|array|max:20','visits.*.worker_ids.*'=>'nullable|integer|exists:maids,id',
            'visits.*.delivery_driver_id'=>['nullable','integer',Rule::exists('hourly_drivers','id')->where('is_active',true)],
            'visits.*.pickup_driver_id'=>['nullable','integer',Rule::exists('hourly_drivers','id')->where('is_active',true)],
        ]);
        if ($data['customer_mode']==='new') $data['customer']=Validator::make($request->input('customer'),[
            'type'=>'required|in:individual,company',
            'first_name'=>'exclude_unless:type,individual|required|string|max:100','last_name'=>'exclude_unless:type,individual|required|string|max:100',
            'company_name'=>'exclude_unless:type,company|required|string|max:200','company_id'=>'exclude_unless:type,company|nullable|string|max:100',
            'email'=>'required|email|max:255|unique:users,email','phone'=>['bail','required','string','regex:/^5[0-9]{8}$/',function ($attribute,$value,$fail) {
                if (User::whereIn('phone',[$value,'0'.$value,'966'.$value,'+966'.$value])->exists()) $fail('رقم الجوال مسجل لعميل حالي.');
            }], 'phone_code'=>'nullable|in:966',
            'password'=>'required|string|min:8|max:100','birthdate'=>'exclude_unless:type,individual|nullable|date|before_or_equal:today',
            'identity'=>'exclude_unless:type,individual|nullable|string|max:100','language'=>'nullable|in:ar,en','note'=>'exclude_unless:type,company|nullable|string|max:2000',
        ])->validate();
        if ($data['address_mode']==='new') $data['address']=Validator::make($request->input('address'),[
            'city_id'=>'required|integer|exists:cities,id','hourly_district_id'=>'required|integer|exists:hourly_service_districts,id',
            'building'=>'required|string|max:255','unit'=>'required|string|max:255','address'=>'required|string|max:500',
            'contact_first_name'=>'required|string|max:100','contact_last_name'=>'required|string|max:100',
            'contact_phone'=>'required|string|max:30','contact_phone2'=>'nullable|string|max:30',
            'latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180',
        ])->validate();
        if (isset($data['manual_total'])) abort_unless(AdminGuardHasPerm('hourly_orders_price_override'),403);
        if ($data['collect_now'] || $data['payment_method']==='customer_balance') abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        $data['allow_renew_again']=AdminGuardHasPerm('hourly_orders_renew_again');
        $order=$service->create($data,(int)auth('admin')->id());
        try { \App\Jobs\SendHourlyBookingConfirmation::dispatch($order->id)->afterCommit(); }
        catch (\Throwable $e) { report($e); } // Delivery failure must never invite a duplicate booking.
        return response()->json(['id'=>$order->id,'redirect'=>route('admin.hourly-orders.summary',$order)]);
    }
}
