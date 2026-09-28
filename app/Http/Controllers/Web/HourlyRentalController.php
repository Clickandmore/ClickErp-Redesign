<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hourly\ClientController;
use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyOrderPayment, ServiceDistrict};
use App\Models\ingaz\{Maid, User, UserAddress};
use App\Services\Hourly\{HourlyWebsiteCatalog, HourlyOrderService, HourlyPaymentService, HourlyBookingRules, HourlyWorkerAssignment, CustomerHourlyData, CityLookupService};
use App\Support\HourlyRentalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class HourlyRentalController extends Controller
{
    public function __construct(private HourlyWebsiteCatalog $catalog) {}

    public function index(Request $request)
    {
        $workers=collect($this->catalog->availableWorkers())->values();
        $page=max(1,(int)$request->input('page',1));
        $maids=new \Illuminate\Pagination\LengthAwarePaginator($workers->forPage($page,12)->values(),$workers->count(),12,$page,
            ['path'=>route('web.hourly.index')]);
        return view('web.hourly.index',compact('maids'));
    }

    private function selection(Request $request): array
    {
        return $request->validate(['maid'=>['nullable','integer','min:1'],'city'=>['nullable','integer','min:1'],'category'=>['nullable','integer','min:1']]);
    }

    public function catalog(Request $request)
    {
        $selection=$this->selection($request);
        $maid=isset($selection['maid'])?(int)$selection['maid']:null;
        $contexts=collect($this->catalog->contexts($maid));
        $cityIds=$contexts->pluck('district.city_id')->unique();
        $cities=\App\Models\City::whereIn('id',$cityIds)->where('is_active',true)->orderBy('sort_order')->get();
        $city=(int)($selection['city']??$cities->first()?->id);
        $contexts=$contexts->filter(fn($row)=>(int)$row['district']->city_id===$city);
        $categories=$contexts->pluck('item.serviceCategory')->unique('id')->values();
        $category=(int)($selection['category']??$categories->first()?->id);
        $packages=$contexts->pluck('item')->where('service_category_id',$category)->unique('id')->values();
        return view('web.hourly.catalog',compact('cities','city','categories','category','packages','maid'));
    }

    private function context(Request $request, int $id): array
    {
        $selection=$this->selection($request);
        $maid=isset($selection['maid'])?(int)$selection['maid']:null;
        $item=$this->catalog->item($id);
        $districts=$this->catalog->readyDistricts($item);
        $city=(int)($selection['city']??$districts->first()?->city_id);
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

    public function package(Request $request, int $package)
    {
        return view('web.hourly.package',$this->context($request,$package));
    }

    public function checkout(Request $request, int $package)
    {
        $context=$this->context($request,$package);
        $addresses=UserAddress::where('user_id',auth('web')->id())->where('city_id',$context['city'])->get();
        foreach ($addresses as $address) {
            if (!$address->hourly_district_id) {
                $district=$this->catalog->districtForAddress((int)$address->city_id,$address->latitude,$address->longitude,$address->district.' '.$address->address);
                if ($district && $context['districts']->contains('id',$district->id)) $address->hourly_district_id=$district->id;
            }
        }
        $context['addresses']=$addresses;
        $city=\App\Models\City::findOrFail($context['city']);
        $context['cityName']=$city->name_ar;
        $context['mapCenter']=[$city->latitude!==null?(float)$city->latitude:24.7136,$city->longitude!==null?(float)$city->longitude:46.6753];
        return view('web.hourly.checkout',$context);
    }

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
        if (!$district) throw \Illuminate\Validation\ValidationException::withMessages(['district_id'=>'تعذر تحديد الحي من الموقع والعنوان. اختاري الحي يدويًا من القائمة.']);
        if (!empty($data['hourly_package_id'])) {
            $item=$this->catalog->item((int)$data['hourly_package_id']);
            if (!$this->catalog->districts($item)->contains('id',$district->id)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['district_id'=>'حي العنوان خارج تغطية هذه الباقة. اختاري عنوانًا داخل منطقة الخدمة.']);
            }
        }
        unset($data['district_id'],$data['hourly_package_id']);
        $address=UserAddress::create($data+['user_id'=>auth('web')->id(),'name'=>auth('web')->user()->name,
            'phone'=>$data['contact_phone'],'notes'=>'','district'=>$district->name,'hourly_district_id'=>$district->id]);
        return response()->json(['data'=>$address],201);
    }

    public function geocode(Request $request, CityLookupService $lookup)
    {
        $data=$request->validate(['query'=>'required_without:latitude|nullable|string|max:200','latitude'=>'required_without:query|nullable|numeric|between:-90,90',
            'longitude'=>'required_with:latitude|nullable|numeric|between:-180,180']);
        return response()->json($lookup->address(isset($data['latitude'])?(float)$data['latitude']:null,
            isset($data['longitude'])?(float)$data['longitude']:null,$data['query']??null));
    }

    public function slots(Request $request)
    {
        $data=$request->validate(['hourly_package_id'=>'required|integer','district_id'=>'required|integer','date'=>'required|date_format:Y-m-d',
            'workers_per_visit'=>'required|integer|between:1,20','preferred_maid_id'=>'nullable|integer']);
        $item=$this->catalog->item($data['hourly_package_id']);
        $district=ServiceDistrict::findOrFail($data['district_id']);
        $slots=$this->catalog->slots($item,$district,$data['date'],(int)$data['workers_per_visit'],isset($data['preferred_maid_id'])?(int)$data['preferred_maid_id']:null);
        $dateOnly=!app(HourlyBookingRules::class)->settings()->value($item,'show_time_options_app');
        $canChoose=(bool)app(HourlyBookingRules::class)->settings()->value($item,'customer_worker_selection');
        $publicWorkers=fn($workers)=>$workers->unique('id')->map(fn($worker)=>[
            'id'=>$worker->id,'name'=>$worker->name,'photo'=>$worker->photo_url,
            'nationality'=>$worker->nationality?->sub_title ?? $worker->nationality?->title ?? '',
        ])->values();
        return response()->json(['data'=>collect($dateOnly?array_slice($slots,0,1):$slots)->map(fn($slot)=>[
            'starts_at'=>$slot['starts_at'],'ends_at'=>$slot['ends_at'],
            'workers'=>$canChoose?$publicWorkers($slot['workers']):[]])->values(),
            'workers'=>$canChoose && $dateOnly?$publicWorkers(collect($slots)->flatMap(fn($slot)=>$slot['workers'])):[],
            'worker_groups'=>$canChoose && $dateOnly?collect($slots)->map(fn($slot)=>$slot['workers']->pluck('id')->map(fn($id)=>(int)$id)->values()->all())->unique()->values():[],
            'time_selection'=>$dateOnly?'date':'time']);
    }

    public function store(Request $request, ClientController $client, HourlyOrderService $orders, HourlyPaymentService $payments)
    {
        $request->validate(['booking_token'=>'required|uuid','hourly_package_id'=>'required|integer','district_id'=>'required|integer',
            'payment_method'=>'required|in:moyasar,bank_transfer,cash','scheduled_starts'=>'required_without:scheduled_dates|array|min:1|max:1',
            'scheduled_dates'=>'required_without:scheduled_starts|array|min:1|max:1']);
        abort_unless(Schema::hasTable('hourly_visit_reservations'),503,'لم تكتمل تهيئة الحجز بعد.');
        $order=DB::transaction(function()use($request,$client,$orders){
            User::whereKey(auth('web')->id())->lockForUpdate()->firstOrFail();
            HourlyPackage::whereKey((int)$request->input('hourly_package_id'))->lockForUpdate()->firstOrFail();
            $item=$this->catalog->item((int)$request->input('hourly_package_id'));
            $district=ServiceDistrict::findOrFail((int)$request->input('district_id'));
            abort_unless($this->catalog->districts($item)->contains('id',$district->id),422,'الحي خارج تغطية الخدمة.');
            $candidates=$this->catalog->candidates($item,$district,true);
            $request->attributes->set('hourly_website_candidates',$candidates);
            $before=HourlyRentalOrder::where('booking_token',$request->input('booking_token'))->first();
            $response=$client->store($request,$orders);
            $order=HourlyRentalOrder::findOrFail($response->getData(true)['data']['id']);
            if ($before) return $order;
            foreach ($order->visits()->whereNotNull('scheduled_start_at')->get() as $visit) {
                $assigned=$visit->workers()->pluck('maids.id');
                // المفتاح slot يخبر صفحة الحجز أن الموعد نفسه لم يعد متاحًا، فتعيد العميل لاختيار موعد آخر
                $taken=fn(string $message)=>throw \Illuminate\Validation\ValidationException::withMessages(['slot'=>$message]);
                foreach ($assigned as $id) if (!$candidates->contains('id',$id)) $taken('العاملة المختارة لم تعد متاحة في هذا الموعد. اختاري موعدًا آخر.');
                $remaining=(int)$visit->workers_count-$assigned->count();
                if ($remaining<=0) continue;
                $available=$candidates->whereNotIn('id',$assigned)->filter(fn($maid)=>app(HourlyWorkerAssignment::class)->available($maid,
                    $visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id))->take($remaining);
                if ($available->count()<$remaining && !$request->has('selected_maid_ids')) $taken('حُجز هذا الموعد للتو ولم يعد العدد المطلوب من العاملات متاحًا فيه. اختاري موعدًا آخر من الأوقات المحدّثة.');
                foreach ($available as $maid) DB::table('hourly_visit_reservations')->insert(['hourly_visit_id'=>$visit->id,'maid_id'=>$maid->id,'created_at'=>now(),'updated_at'=>now()]);
            }
            $metadata=$order->booking_metadata;$metadata['booking_channel']='website';$order->update(['booking_metadata'=>$metadata]);
            return $order;
        });
        $redirect=route('web.hourly.order',$order->id);
        if ($order->payment_method==='moyasar' && $order->payment_status!=='paid') {
            try { $redirect=$payments->initiate($order,true)->payment_url; }
            catch (\Throwable $exception) {
                report($exception);
                // The booking is already committed: expose it so the customer can retry payment without rebooking.
                session()->flash('hourly_payment_error','تم إنشاء الطلب، لكن تعذر فتح الدفع. يمكنك المحاولة مجددًا من صفحة الطلب.');
            }
        }
        return response()->json(['id'=>$order->id,'redirect'=>$redirect],201);
    }

    private function owned(int $id): HourlyRentalOrder
    {
        return HourlyRentalOrder::where('user_id',auth('web')->id())->findOrFail($id);
    }

    public function orders()
    {
        return view('web.hourly.orders',['orders'=>HourlyRentalOrder::where('user_id',auth('web')->id())->latest('id')->paginate(12)]);
    }

    public function order(int $id)
    {
        $order=$this->owned($id)->load('visits','payments');
        $banks=Schema::hasTable('bank_accounts')?\Nami\Finance\Models\BankAccount::query()->whereNotNull('iban')->get(['name','iban','bank_number'])->map(fn($bank)=>(object)['name'=>$bank->name,'iban'=>$bank->iban,'account_number'=>$bank->bank_number]):collect();
        return view('web.hourly.order',compact('order','banks'));
    }

    public function pay(int $id, HourlyPaymentService $payments)
    {
        return redirect()->away($payments->initiate($this->owned($id),true)->payment_url);
    }

    public function paymentReturn(Request $request, HourlyPaymentService $payments)
    {
        $data=$request->validate(['payment'=>'required|uuid']);
        $payment=HourlyOrderPayment::where('public_id',$data['payment'])->where('provider','moyasar')->firstOrFail();
        $payments->synchronize($payment);
        return redirect()->route('web.hourly.order',$payment->hourly_rental_order_id);
    }
}
