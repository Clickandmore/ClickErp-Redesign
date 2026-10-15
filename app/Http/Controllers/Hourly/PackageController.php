<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyPackage;
use App\Models\City;
use App\Models\ingaz\Nationality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }
    public function index(Request $request)
    {
        $packages = HourlyPackage::with('cities')->when($request->filled('search'),fn ($q) => $q->where('code','like','%'.$request->input('search').'%'))->orderBy('sort_order')->paginate(20)->withQueryString();
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $publication=$packages->getCollection()->mapWithKeys(fn($item)=>[$item->id=>$catalog->publicationStatus($item)]);
        return view('admin.hourly.packages', compact('packages','publication'));
    }
    public function create() { return $this->form(new HourlyPackage(['workers_per_visit'=>1,'visits_count'=>1,'hours_per_visit'=>4,'validity_days'=>30,'transport_included'=>true])); }
    public function edit(HourlyPackage $package) { return $this->form($package); }
    private function form(HourlyPackage $package)
    {
        if (!$package->exists && request()->filled('service_category_id')) $package->service_category_id = (int) request('service_category_id');
        return view(request()->ajax() ? 'admin.hourly.service-item-fields' : 'admin.hourly.service-item-form', ['package'=>$package,'cities'=>City::saudi()->where('is_active',true)->orderBy('name_ar')->get(),'nationalities'=>Nationality::all(),
            'productAttributes'=>\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes') ? \App\Models\Hourly\HourlyProductAttribute::whereNull('parent_id')->with('children')->orderBy('sort_order')->get() : collect(),
            'packageTiers'=>\Illuminate\Support\Facades\Schema::hasTable('hourly_package_tiers') ? \App\Models\Hourly\HourlyPackageTier::where(fn($q)=>$q->where('is_active',true)->orWhere('id',$package->package_tier_id))->orderBy('sort_order')->get() : collect(),
            'serviceCategories'=>\App\Models\Hourly\HourlyServiceCategory::ordered()->where(fn($q)=>$q->where('is_active',true)->orWhere('id',$package->service_category_id))->get()]);
    }
    public function store(Request $request) { return $this->save($request,new HourlyPackage()); }
    public function update(Request $request, HourlyPackage $package) { return $this->save($request,$package); }
    private function save(Request $request, HourlyPackage $package)
    {
        if (!$request->filled('code')) {
            $request->merge(['code' => $package->code ?: 'SI-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12))]);
        }
        $tierFields=[];
        if(\Illuminate\Support\Facades\Schema::hasTable('hourly_package_tiers')){
            $choice=$request->validate(['package_tier_id'=>['nullable','integer',Rule::exists('hourly_package_tiers','id')]]);
            $tier=isset($choice['package_tier_id']) ? \App\Models\Hourly\HourlyPackageTier::findOrFail($choice['package_tier_id']) : \App\Models\Hourly\HourlyPackageTier::where('code','one_off')->firstOrFail();
            if(!$tier->is_active && $package->package_tier_id!=$tier->id)throw \Illuminate\Validation\ValidationException::withMessages(['package_tier_id'=>__('hourly.tier_inactive')]);
            if($tier->frequency==='one_off')$request->merge(['recurring_months'=>0,'visits_count'=>1]);
            else {
                $request->validate(['recurring_months'=>['required','integer','between:1,24']]);
                if(!$request->filled('visits_count'))$request->merge(['visits_count'=>(int)round($tier->visits_per_week*4*$request->recurring_months)]);
            }
            $tierFields=['package_tier_id'=>$tier->id,'package_type'=>$tier->code];
        }
        $data = $request->validate([
            'payment_policy'=>['nullable',Rule::in(['prepaid','per_visit','installments'])],
            'total_price'=>['required_if:service_item_form,1','nullable','numeric','min:0.01','max:100000'],
            'package_type'=>['nullable','string','max:100'],'brand'=>['nullable','string','max:150'],
            'duration_minutes'=>['nullable','integer','between:30,720'],'break_minutes'=>['nullable','integer','between:0,240'],'buffer_minutes'=>['nullable','integer','between:0,240'],
            'recurring_months'=>['nullable','integer','between:0,24'],'deposit_amount'=>['nullable','numeric','min:0','max:100000'],'monthly_amount'=>['nullable','numeric','min:0','max:100000'],
            'start_times'=>['nullable','array','max:288'],'start_times.*'=>['in:off,all,dispatch'],
            'is_private'=>['nullable','boolean'],'materials_included'=>['nullable','boolean'],'for_home'=>['nullable','boolean'],'for_company'=>['nullable','boolean'],'show_on_app'=>['nullable','boolean'],'ignore_closed_days'=>['nullable','boolean'],
            'code'=>['required','string','max:40',Rule::unique('hourly_packages')->ignore($package->id)],
            // الفئة الآن كيان مستقل؛ الاسم النصي مقبول فقط من النماذج القديمة ويُربط بفئة بنفس الاسم.
            'service_category_id'=>['required_without:category','nullable','integer',Rule::exists('hourly_service_categories','id')->whereNull('deleted_at')],'category'=>['required_without:service_category_id','nullable','string','max:100'],
            'title_ar'=>['required','string','max:255'],'title_en'=>['nullable','string','max:255'], 'description_ar'=>['nullable','string','max:10000'],'description_en'=>['nullable','string','max:10000'],
            'hours_per_visit'=>['required','numeric','min:0.5','max:12'],'workers_per_visit'=>['required','integer','min:1','max:20'],
            'visits_count'=>['required','integer','min:1','max:730'],'validity_days'=>['required','integer','min:1','max:730'],
            'price_per_visit'=>['required_without:total_price','nullable','numeric','min:0','max:100000'], 'extra_worker_price'=>['required','numeric','min:0','max:100000'],
            'extra_hour_price'=>['required','numeric','min:0','max:100000'],'transport_fee'=>['required','numeric','min:0','max:100000'],
            // فارغ = يرث ضريبة الفئة ثم العامة (HourlyCategorySettings).
            'tax_rate'=>['nullable','numeric','min:0','max:100'],'transport_included'=>['nullable','boolean'],'is_active'=>['nullable','boolean'],
            'min_notice_hours'=>['nullable','integer','min:0','max:168'],'cancellation_window_hours'=>['nullable','integer','min:0','max:168'],
            'cancellation_fee_percent'=>['nullable','integer','min:0','max:100'],'sort_order'=>['required','integer','min:0','max:10000'],
            'city_ids'=>['nullable','array'],'city_ids.*'=>['integer','distinct','exists:cities,id'],
            'nationality_ids'=>['nullable','array'],'nationality_ids.*'=>['integer','distinct','exists:nationalities,id'],
        ]);
        if (!empty($data['city_ids']) && City::saudi()->where('is_active',true)->whereIn('id',$data['city_ids'])->count() !== count($data['city_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['city_ids'=>'اختر مدنًا نشطة داخل السعودية فقط.']);
        }
        $data=array_replace($data,$tierFields);
        if(\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes')){
            $choice=$request->validate(['attribute_id'=>['nullable','integer',Rule::exists('hourly_product_attributes','id')]]);
            $attribute=empty($choice['attribute_id'])?null:\App\Models\Hourly\HourlyProductAttribute::with('parent')->findOrFail($choice['attribute_id']);
            if($attribute && (!$attribute->parent_id || $attribute->service_category_id!=(int)($data['service_category_id']??0) || ((!$attribute->is_active || !$attribute->parent?->is_active) && $package->attribute_id!=$attribute->id)))throw \Illuminate\Validation\ValidationException::withMessages(['attribute_id'=>'اختر خيارًا نشطًا من سمات فئة العنصر.']);
            $data['attribute_id']=$attribute?->id;$data['brand']=$attribute?->name_ar;
        }
        if (isset($data['deposit_amount']) && $data['deposit_amount'] > ($data['total_price'] ?? $data['price_per_visit']*$data['visits_count'])) throw \Illuminate\Validation\ValidationException::withMessages(['deposit_amount'=>'الدفعة المقدمة لا تتجاوز سعر الباقة.']);

        $selectedPolicy=array_key_exists('payment_policy',$data)?$data['payment_policy']:$package->payment_policy;
        if($selectedPolicy==='installments'){
            $total=(float)($data['total_price']??$data['price_per_visit']*$data['visits_count']);
            $deposit=(float)($data['deposit_amount']??$package->deposit_amount??0);$monthly=(float)($data['monthly_amount']??$package->monthly_amount??0);
            if($total>$deposit && ($monthly<=0 || ceil(($total-$deposit)/$monthly)>120))
                throw \Illuminate\Validation\ValidationException::withMessages(['monthly_amount'=>'حدد قسطًا شهريًا موجبًا، بحد أقصى 120 قسطًا.']);
        }
        if(array_key_exists('payment_policy',$data) && !\Illuminate\Support\Facades\Schema::hasColumn('hourly_packages','payment_policy')){
            if($data['payment_policy'])throw \Illuminate\Validation\ValidationException::withMessages(['payment_policy'=>'شغّل ترحيل سياسة الدفع أولًا.']);
            unset($data['payment_policy']);
        }
        DB::transaction(function () use ($package,$data,$request) {
            if($package->exists)$package=HourlyPackage::lockForUpdate()->findOrFail($package->id);
            $service=app(\App\Services\Hourly\ServiceItemService::class);
            $before=$package->exists ? $service->snapshot($package) : null;
            $fields = collect($data)->except(['title_ar','title_en','description_ar','description_en','city_ids','nationality_ids'])->all();
            $category = app(\App\Services\Hourly\ServiceCategoryService::class)->resolveForItem($data['service_category_id'] ?? null, $data['category'] ?? null);
            $fields['service_category_id'] = $category->id;
            $fields['category'] = $category->name_ar;
            if(isset($fields['duration_minutes']))$fields['hours_per_visit']=round($fields['duration_minutes']/60,2);
            if(isset($fields['total_price']))$fields['price_per_visit']=round($fields['total_price']/$fields['visits_count'],2);
            if($request->boolean('service_item_form')) {
                foreach(['is_private','materials_included','for_home','for_company','show_on_app','ignore_closed_days'] as $flag)$fields[$flag]=$request->boolean($flag);
                $times=$fields['start_times'] ?? [];
                foreach(array_keys($times) as $time)if(!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/',$time))throw \Illuminate\Validation\ValidationException::withMessages(['start_times'=>'وقت بداية غير صالح.']);
                $fields['start_times']=array_filter($times,fn($access)=>$access!=='off');
                if($request->boolean('use_general_schedule'))$fields['start_times']=null;
            }
            $fields['revision']=($package->revision ?? 0)+1;
            $fields['is_active']=$request->boolean('is_active'); $fields['transport_included']=$request->boolean('transport_included');
            if (!$package->exists) $fields['created_by']=auth('admin')->id();
            $package->fill($fields)->save();
            foreach (['ar','en'] as $locale) $package->translations()->updateOrCreate(['locale'=>$locale],['title'=>($data['title_'.$locale] ?? null) ?: $data['title_ar'],'description'=>$data['description_'.$locale] ?? null]);
            $package->cities()->sync($data['city_ids'] ?? []); $package->nationalities()->sync($data['nationality_ids'] ?? []);
            $service->audit($package,$before ? 'edit' : 'create',$before);
        });
        if($request->expectsJson())return response()->json(['message'=>'تم حفظ عنصر الخدمة.']);
        if($request->boolean('service_item_form'))return redirect()->route('admin.hourly-service-items.index')->with('success','تم حفظ عنصر الخدمة.');
        return redirect()->route('admin.hourly-packages.index')->with('success','تم حفظ الباقة.');
    }
    public function destroy(HourlyPackage $package)
    {
        DB::transaction(function()use($package){$service=app(\App\Services\Hourly\ServiceItemService::class);$before=$service->snapshot($package);$package->delete();$service->audit($package,'archive',$before);});
        return back()->with('success','تم أرشفة الباقة مع الاحتفاظ بالطلبات السابقة.');
    }
}
