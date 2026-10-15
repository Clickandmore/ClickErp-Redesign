<?php

namespace App\Http\Controllers\Api\Hourly;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hourly\ClientController;
use App\Models\City;
use App\Models\Country;
use App\Models\Hourly\{HourlyOrderPayment, HourlyRentalOrder, ServiceDistrict};
use App\Models\ingaz\{Maid, UserAddress};
use App\Services\Hourly\{CityLookupService, CustomerHourlyData, HourlyBookingRules, HourlyOrderService, HourlyPaymentService, HourlyWebsiteBooking, HourlyWebsiteCatalog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * حجز الساعات للموقع الخارجي (api/client/hourly/*): نفس تسلسل موقع اللوحة المدمج — تحديد الموقع، الباقات،
 * إتمام الحجز، الطلبات — بصيغة JSON وبتوكن العميل (auth:api). لا يمس واجهات التطبيق api/app/hourly/*.
 * الموقع الخارجي يحفظ موقع الزائر في جلسته ويرسله هنا (located_city)، فلا جلسة في هذه الواجهات.
 */
class WebsiteHourlyController extends Controller
{
    private const PAYMENT_METHODS = ['moyasar', 'bank_transfer', 'cash'];

    public function __construct(private HourlyWebsiteCatalog $catalog)
    {
        $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class);
    }

    /** يحدد حي التغطية من نقطة على الخريطة أو من اختيار المدينة والحي؛ available=false يعني خارج التغطية. */
    public function location(Request $request, CityLookupService $lookup)
    {
        $data=$request->validate(['district_id'=>'required_without_all:latitude,city_id|nullable|integer','city_id'=>'nullable|integer',
            'latitude'=>'required_without_all:district_id,city_id|nullable|numeric|between:-90,90','longitude'=>'required_with:latitude|nullable|numeric|between:-180,180']);
        $covered=$this->catalog->coveredDistricts();
        if (!empty($data['district_id'])) {
            $district=ServiceDistrict::where('is_active',true)->findOrFail((int)$data['district_id']);
            [$latitude,$longitude]=$this->districtCenter($district);
            return response()->json(['data'=>$this->locationPayload($district,$latitude,$longitude,'',$covered->contains('id',$district->id))]);
        }
        if (empty($data['latitude'])) {
            $city=City::where('is_active',true)->findOrFail((int)$data['city_id']);
            return response()->json(['data'=>$this->locationPayload(null,(float)($city->latitude??24.7136),(float)($city->longitude??46.6753),'',false,$city)]);
        }
        [$latitude,$longitude]=[(float)$data['latitude'],(float)$data['longitude']];
        try { $address=(string)($lookup->address($latitude,$longitude)['address']??''); } catch (\Throwable $e) { $address=''; }
        if ($district=$this->catalog->locate($latitude,$longitude,null,$address,$covered)) {
            return response()->json(['data'=>$this->locationPayload($district,$latitude,$longitude,$address)]);
        }
        $city=$this->nearestCity($latitude,$longitude);
        $district=$city ? $this->catalog->districtForAddress((int)$city->id,$latitude,$longitude,$address) : null;
        $label=collect(preg_split('/[,،]/u',$address))->map(fn($part)=>trim($part))->filter(fn($part)=>$part!=='' && !ctype_digit($part))
            ->unique(fn($part)=>str_replace(['ة','أ','إ','آ'],['ه','ا','ا','ا'],$part))->take(3)->implode('، ');
        return response()->json(['data'=>$this->locationPayload($district,$latitude,$longitude,$address,false,$city,$label)]);
    }

    /** مدن السعودية المفعّلة وأحياؤها، مع تعليم الأحياء المتاحة للحجز. */
    public function locationOptions()
    {
        $covered=$this->catalog->coveredDistricts()->pluck('id')->map(fn($id)=>(int)$id)->all();
        $saudi=Country::where('code','SA')->value('id');
        $cities=City::where('is_active',true)->when($saudi,fn($q)=>$q->where('country_id',$saudi))->orderBy('sort_order')->orderBy('name_ar')->get();
        $districts=ServiceDistrict::where('is_active',true)->whereIn('city_id',$cities->pluck('id'))->orderBy('name')->get()->groupBy('city_id');
        return response()->json(['data'=>$cities->map(fn($city)=>['id'=>$city->id,'name'=>$this->name($city->name_ar,$city->name_en),
            'districts'=>($districts[$city->id]??collect())->map(fn($district)=>['id'=>$district->id,'name'=>$this->name($district->name,$district->name_en),
                'available'=>in_array((int)$district->id,$covered,true)])->values()])->values()]);
    }

    /** بحث نصي على الخريطة أو قراءة عنوان نقطة. */
    public function geocode(Request $request, CityLookupService $lookup)
    {
        $data=$request->validate(['query'=>'required_without:latitude|nullable|string|max:200','latitude'=>'required_without:query|nullable|numeric|between:-90,90',
            'longitude'=>'required_with:latitude|nullable|numeric|between:-180,180']);
        return response()->json(['data'=>$lookup->address(isset($data['latitude'])?(float)$data['latitude']:null,
            isset($data['longitude'])?(float)$data['longitude']:null,$data['query']??null)]);
    }

    /** المدن والخدمات والباقات المتاحة؛ located_city مدينة الموقع المحدد في الموقع الخارجي. */
    public function catalog(Request $request, CustomerHourlyData $presenter)
    {
        $selection=$this->selection($request);
        $maid=$selection['maid']??null;
        $contexts=collect($this->catalog->contexts($maid));
        $cities=City::whereIn('id',$contexts->pluck('district.city_id')->unique())->where('is_active',true)->orderBy('sort_order')->get();
        $located=$selection['located_city']??null;
        $city=(int)($selection['city']??($cities->contains('id',$located)?$located:$cities->first()?->id));
        $contexts=$contexts->filter(fn($row)=>(int)$row['district']->city_id===$city);
        $categories=$contexts->pluck('item.serviceCategory')->unique('id')->values();
        $category=(int)($selection['category']??$categories->first()?->id);
        $packages=$contexts->pluck('item')->where('service_category_id',$category)->unique('id')->values();
        return response()->json(['data'=>[
            'city'=>$city ?: null, 'category'=>$category ?: null, 'maid'=>$maid,
            'cities'=>$cities->map(fn($row)=>['id'=>$row->id,'name'=>$this->name($row->name_ar,$row->name_en)])->values(),
            'categories'=>$categories->map(fn($row)=>['id'=>$row->id,'name'=>$this->name($row->name_ar,$row->name_en),'icon'=>$row->icon,'color'=>$row->color])->values(),
            'packages'=>$packages->map(fn($item)=>$presenter->package($item))->values(),
        ]]);
    }

    public function package(Request $request, int $package)
    {
        return response()->json(['data'=>$this->publicContext($this->context($request,$package))]);
    }

    /** صفحة إتمام الحجز: الباقة وأحياء التغطية وعناوين العميل في المدينة. */
    public function checkout(Request $request, int $package)
    {
        $context=$this->context($request,$package);
        $addresses=UserAddress::where('user_id',auth('api')->id())->where('city_id',$context['city'])->latest('id')->get();
        foreach ($addresses as $address) {
            if (!$address->hourly_district_id) {
                $district=$this->catalog->districtForAddress((int)$address->city_id,$address->latitude,$address->longitude,$address->district.' '.$address->address);
                if ($district && $context['districts']->contains('id',$district->id)) $address->hourly_district_id=$district->id;
            }
        }
        $city=City::findOrFail($context['city']);
        $rules=app(HourlyBookingRules::class);
        return response()->json(['data'=>$this->publicContext($context)+[
            'city_name'=>$this->name($city->name_ar,$city->name_en),
            'map_center'=>[$city->latitude!==null?(float)$city->latitude:24.7136,$city->longitude!==null?(float)$city->longitude:46.6753],
            'can_choose_worker'=>(bool)$rules->settings()->value($context['item'],'customer_worker_selection'),
            'payment_methods'=>array_values(array_intersect((array)($context['data']['booking_config']['payment_methods']??[]),self::PAYMENT_METHODS)),
            'max_date'=>min((string)($context['data']['booking_config']['booking']['max_date']??now()->addDays($context['item']->validity_days)->toDateString()),
                now()->addDays($context['item']->validity_days)->toDateString()),
            'addresses'=>$addresses->map(fn($address)=>$address->only(['id','title','address','district','hourly_district_id','latitude','longitude','building','unit','contact_phone']))->values(),
            'coverage'=>$context['districts']->map(fn($district)=>['id'=>$district->id,'name'=>$this->name($district->name,$district->name_en),
                'center'=>$district->latitude!==null?[(float)$district->latitude,(float)$district->longitude]:null,
                'polygon'=>is_array($district->polygon)?$district->polygon:null])->values(),
        ]]);
    }

    /** يحفظ عنوانًا جديدًا ويحدد حيّه من الموقع، ويرفضه إن كان خارج تغطية الباقة. */
    public function address(Request $request)
    {
        $data=$request->validate(['city_id'=>'required|integer|exists:cities,id','district_id'=>'nullable|integer|exists:hourly_service_districts,id',
            'hourly_package_id'=>'nullable|integer',
            'title'=>'required|string|max:100','address'=>'required|string|max:500','building'=>'nullable|string|max:255','unit'=>'nullable|string|max:255',
            'latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180','contact_phone'=>'required|string|max:30']);
        $district=$this->catalog->districtForAddress((int)$data['city_id'],(float)$data['latitude'],(float)$data['longitude'],$data['address']);
        if (!empty($data['district_id'])) {
            $selected=ServiceDistrict::where('city_id',$data['city_id'])->where('is_active',true)->findOrFail($data['district_id']);
            $district ??= $selected;
        }
        if (!$district) throw ValidationException::withMessages(['district_id'=>__('hourly_website.district_unresolved')]);
        if (!empty($data['hourly_package_id'])) {
            $item=$this->catalog->item((int)$data['hourly_package_id']);
            if (!$this->catalog->districts($item)->contains('id',$district->id)) {
                throw ValidationException::withMessages(['district_id'=>__('hourly_website.district_outside_package')]);
            }
        }
        unset($data['district_id'],$data['hourly_package_id']);
        $address=UserAddress::create($data+['user_id'=>auth('api')->id(),'name'=>auth('api')->user()->name,
            'phone'=>$data['contact_phone'],'notes'=>'','district'=>$district->name,'hourly_district_id'=>$district->id]);
        return response()->json(['data'=>$address->only(['id','title','address','district','hourly_district_id','latitude','longitude','building','unit','contact_phone'])],201);
    }

    public function slots(Request $request, HourlyWebsiteBooking $booking)
    {
        return response()->json($booking->slots($request->validate(HourlyWebsiteBooking::slotRules()),(int)auth('api')->id()));
    }

    /** ينشئ الطلب؛ مع ميسر يعيد رابط الدفع، وإن تعذر يبقى الطلب قائمًا ويمكن الدفع لاحقًا من صفحته. */
    public function store(Request $request, ClientController $client, HourlyOrderService $orders, HourlyPaymentService $payments, HourlyWebsiteBooking $booking)
    {
        $request->validate(HourlyWebsiteBooking::storeRules());
        $returnUrl=$this->returnUrl($request);
        $order=$booking->place($request,$client,$orders,(int)auth('api')->id());
        $paymentUrl=null;$paymentError=null;
        if ($order->payment_method==='moyasar' && $order->payment_status!=='paid') {
            try { $paymentUrl=$payments->initiate($order,true,$returnUrl)->payment_url; }
            catch (\Throwable $exception) {
                report($exception);
                $paymentError=__('hourly_website.payment_open_failed');
            }
        }
        return response()->json(['data'=>['id'=>$order->id,'order_number'=>$order->order_number,
            'payment_url'=>$paymentUrl,'payment_error'=>$paymentError]],201);
    }

    /** تفاصيل الطلب مع ما تحتاجه صفحة الموقع: إمكانية الدفع والمستحق والحسابات البنكية واستعلام المواعيد. */
    public function show(int $id, CustomerHourlyData $presenter, HourlyPaymentService $payments)
    {
        $order=$this->owned($id)->load('visits','payments');
        $details=$presenter->order($order);
        $schedule=$details['payment_schedule'];
        $canPay=(($schedule['policy']??null)!=='legacy' && in_array($order->status,['active','completed'],true))
            || (!in_array($order->status,['cancelled','expired','completed'],true) && $order->expires_at->isFuture());
        $canPay=$canPay && (float)($schedule['due_now']??0)>0;
        $banks=Schema::hasTable('bank_accounts')
            ? \Nami\Finance\Models\BankAccount::query()->whereNotNull('iban')->get(['name','iban','bank_number'])
                ->map(fn($bank)=>['name'=>$bank->name,'iban'=>$bank->iban,'account_number'=>$bank->bank_number])->values()
            : collect();
        return response()->json(['data'=>$details+['website'=>[
            'created_at'=>$order->created_at?->toIso8601String(),
            'expires_on'=>$order->expires_at?->toDateString(),
            'can_pay'=>$canPay,
            'due_now'=>$canPay ? $payments->dueNow($order) : 0,
            'pending_review'=>$order->payments->contains('status','pending_review'),
            'banks'=>$banks,
            'slot_query'=>$order->service_district_id ? ['hourly_package_id'=>$order->hourly_package_id,
                'district_id'=>$order->service_district_id,'workers_per_visit'=>$order->workers_per_visit] : null,
        ]]]);
    }

    public function pay(Request $request, int $id, HourlyPaymentService $payments)
    {
        $payment=$payments->initiate($this->owned($id),true,$this->returnUrl($request));
        return response()->json(['data'=>$payment->only(['public_id','status','amount','payment_url'])]);
    }

    /** بعد العودة من ميسر: يتحقق من الفاتورة لدى البوابة نفسها، ويعيد رقم الطلب لصفحة العودة. */
    public function paymentReturn(Request $request, HourlyPaymentService $payments)
    {
        $data=$request->validate(['payment'=>'required|uuid']);
        $payment=HourlyOrderPayment::where('public_id',$data['payment'])->where('provider','moyasar')->firstOrFail();
        $payment=$payments->synchronize($payment);
        return response()->json(['data'=>['order_id'=>(int)$payment->hourly_rental_order_id,'status'=>$payment->settlement_status]]);
    }

    private function owned(int $id): HourlyRentalOrder
    {
        return HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id);
    }

    private function selection(Request $request): array
    {
        $data=$request->validate(['maid'=>['nullable','integer','min:1'],'city'=>['nullable','integer','min:1'],
            'category'=>['nullable','integer','min:1'],'located_city'=>['nullable','integer','min:1']]);
        return array_map(fn($value)=>$value===null?null:(int)$value,$data);
    }

    private function context(Request $request, int $id): array
    {
        $selection=$this->selection($request);
        $maid=$selection['maid']??null;
        $item=$this->catalog->item($id);
        $districts=$this->catalog->readyDistricts($item);
        $located=$selection['located_city']??null;
        $city=(int)($selection['city']??($districts->contains('city_id',$located)?$located:$districts->first()?->city_id));
        $districts=$districts->where('city_id',$city)->values();
        if ($maid) {
            abort_unless(app(HourlyBookingRules::class)->settings()->value($item,'customer_worker_selection'),404);
            $districts=$districts->filter(fn($district)=>$this->catalog->candidates($item,$district)->contains('id',$maid))->values();
        }
        abort_if($districts->isEmpty(),404);
        $data=app(CustomerHourlyData::class)->package($item);
        $worker=$maid?Maid::findOrFail($maid):null;
        return compact('item','data','city','districts','maid','worker');
    }

    private function publicContext(array $context): array
    {
        $worker=$context['worker'];
        return ['package'=>$context['data'],'city'=>$context['city'],'maid'=>$context['maid'],
            'districts'=>$context['districts']->map(fn($district)=>['id'=>$district->id,'name'=>$this->name($district->name,$district->name_en),'name_en'=>$district->name_en])->values(),
            'worker'=>$worker ? ['id'=>$worker->id,'name'=>$worker->name,'photo'=>$worker->photo_url,
                'nationality'=>$worker->nationality?->sub_title ?? $worker->nationality?->title ?? ''] : null];
    }

    /** صفحة العودة بعد الدفع في الموقع الخارجي؛ تُقيَّد بالنطاقات المسموحة إن ضُبطت. */
    private function returnUrl(Request $request): ?string
    {
        $data=$request->validate(['return_url'=>['nullable','string','max:500','url','regex:/^https?:\/\//i']]);
        $url=$data['return_url']??null;
        if (!$url) return null;
        $hosts=(array)config('services.hourly_website.return_hosts',[]);
        if ($hosts && !in_array(strtolower((string)parse_url($url,PHP_URL_HOST)),array_map('strtolower',$hosts),true)) {
            throw ValidationException::withMessages(['return_url'=>__('hourly_website.return_url_not_allowed')]);
        }
        return $url;
    }

    private function locationPayload(?ServiceDistrict $district, float $latitude, float $longitude, string $address='', bool $available=true, ?City $city=null, string $label=''): array
    {
        $city ??= $district?->city;
        $cityName=$city ? $this->name($city->name_ar,$city->name_en) : '';
        $districtName=$district ? $this->name($district->name,$district->name_en) : '';
        return ['latitude'=>round($latitude,7),'longitude'=>round($longitude,7),'address'=>mb_substr($address,0,500),
            'city_id'=>$city ? (int)$city->id : null,'city'=>$cityName,
            'district_id'=>$district ? (int)$district->id : null,'district'=>$districtName,
            'available'=>$available && $district!==null,
            'label'=>$district ? trim($districtName.($cityName ? '، '.$cityName : '')) : ($label ?: ($cityName ?: __('hourly_website.map_point')))];
    }

    /** أقرب مدينة سعودية مفعّلة لمركزها خلال 80 كم، وإلا null. */
    private function nearestCity(float $latitude, float $longitude): ?City
    {
        $saudi=Country::where('code','SA')->value('id');
        return City::where('is_active',true)->when($saudi,fn($q)=>$q->where('country_id',$saudi))->whereNotNull('latitude')->whereNotNull('longitude')->get()
            ->map(fn($city)=>[$city,6371*2*asin(sqrt(sin(deg2rad($city->latitude-$latitude)/2)**2+cos(deg2rad($latitude))*cos(deg2rad($city->latitude))*sin(deg2rad($city->longitude-$longitude)/2)**2))])
            ->filter(fn($row)=>$row[1]<=80)->sortBy(1)->first()[0] ?? null;
    }

    /** مركز الحي: إحداثياته المسجلة، ثم متوسط نقاط حدوده، ثم مركز المدينة. */
    private function districtCenter(ServiceDistrict $district): array
    {
        if ($district->latitude!==null && $district->longitude!==null) return [(float)$district->latitude,(float)$district->longitude];
        $points=collect(is_array($district->polygon)?$district->polygon:[])->filter(fn($point)=>is_array($point) && count($point)>=2);
        if ($points->isNotEmpty()) return [(float)$points->avg(0),(float)$points->avg(1)];
        return [(float)($district->city?->latitude ?? 24.7136),(float)($district->city?->longitude ?? 46.6753)];
    }

    private function name(?string $arabic, ?string $english): string
    {
        return (string)(app()->getLocale()==='en' ? ($english ?: $arabic) : ($arabic ?: $english));
    }
}
