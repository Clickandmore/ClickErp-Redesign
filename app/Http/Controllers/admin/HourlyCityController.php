<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\{City, Country};
use App\Services\Hourly\CityLookupService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HourlyCityController extends Controller
{
    public function index(Request $request)
    {
        $filters=$request->validate([
            'search'=>['nullable','string','max:150'], 'per_page'=>['nullable',Rule::in([10,25,50,100])],
            'sort'=>['nullable',Rule::in(['id','name_ar','name_en','sort_order','is_active','service_districts_count','mapped_count'])],
            'direction'=>['nullable','in:asc,desc'],
            'status'=>['nullable',Rule::in(['active','inactive'])],
            'districts'=>['nullable',Rule::in(['with','without'])],
            'coverage'=>['nullable',Rule::in(['complete','partial','none'])],
            'location'=>['nullable',Rule::in(['available','missing'])],
        ]);
        $query=City::saudi()->withCount(['serviceDistricts','serviceDistricts as active_count'=>fn($q)=>$q->where('is_active',true),'serviceDistricts as mapped_count'=>fn($q)=>$q->whereNotNull('polygon')]);
        if($search=$filters['search'] ?? null) $query->where(fn($q)=>$q->where('name_ar','like',"%$search%")->orWhere('name_en','like',"%$search%"));
        if(isset($filters['status'])) $query->where('is_active',$filters['status']==='active');
        if(($filters['districts'] ?? null)==='with') $query->has('serviceDistricts');
        if(($filters['districts'] ?? null)==='without') $query->doesntHave('serviceDistricts');
        if(($filters['coverage'] ?? null)==='complete') $query->has('serviceDistricts')->whereDoesntHave('serviceDistricts',fn($q)=>$q->whereNull('polygon'));
        if(($filters['coverage'] ?? null)==='partial') $query->whereHas('serviceDistricts',fn($q)=>$q->whereNotNull('polygon'))->whereHas('serviceDistricts',fn($q)=>$q->whereNull('polygon'));
        if(($filters['coverage'] ?? null)==='none') $query->whereDoesntHave('serviceDistricts',fn($q)=>$q->whereNotNull('polygon'));
        if(($filters['location'] ?? null)==='available') $query->whereNotNull('latitude')->whereNotNull('longitude');
        if(($filters['location'] ?? null)==='missing') $query->where(fn($q)=>$q->whereNull('latitude')->orWhereNull('longitude'));
        return view('admin.hourly_districts.cities-grid', ['cities'=>$query->orderBy($filters['sort'] ?? 'sort_order',$filters['direction'] ?? 'asc')->orderBy('id')->paginate($filters['per_page'] ?? 25)->withQueryString(), 'filters'=>$filters]);
    }

    public function create() { return view('admin.hourly_districts.city-form',['city'=>new City(['sort_order'=>1,'is_active'=>true])]); }
    public function edit(City $city) { $this->saudi($city); return view('admin.hourly_districts.city-form',compact('city')); }
    public function store(Request $request)
    {
        $country=Country::where('code','SA')->firstOrFail();
        $city=City::create($this->validated($request,$country->id)+['country_id'=>$country->id]);
        return redirect()->route('admin.hourly-cities.index')->with('success','تمت إضافة المدينة '.$city->name_ar.'.');
    }
    public function update(Request $request, City $city)
    {
        $this->saudi($city); $city->update($this->validated($request,$city->country_id,$city));
        return redirect()->route('admin.hourly-cities.index')->with('success','تم تعديل المدينة.');
    }
    public function status(Request $request, City $city)
    {
        $this->saudi($city); $city->update($request->validate(['is_active'=>['required','boolean']]));
        return response()->json(['is_active'=>$city->is_active,'message'=>'تم تحديث حالة المدينة.']);
    }
    public function bulkStatus(Request $request)
    {
        $data=$request->validate([
            'city_ids'=>['required','array','min:1','max:100'],
            'city_ids.*'=>['required','integer','distinct','exists:cities,id'],
            'is_active'=>['required','boolean'],
        ]);
        $ids=array_map('intval',$data['city_ids']);
        if(City::saudi()->whereKey($ids)->count() !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['city_ids'=>'تتضمن القائمة مدينة غير متاحة في نطاق التأجير بالساعة.']);
        }
        City::whereKey($ids)->update(['is_active'=>(bool)$data['is_active']]);
        $action=$data['is_active'] ? 'تفعيل' : 'إيقاف';
        return redirect()->back()->with('success',"تم {$action} ".count($ids).' مدينة بنجاح.');
    }
    public function polygons(City $city)
    {
        $this->saudi($city); return view('admin.hourly_districts.polygons',['city'=>$city,'districts'=>$city->serviceDistricts()->orderBy('name')->get()]);
    }
    public function boundary(City $city, CityLookupService $service)
    {
        $this->saudi($city);
        return response()->json($service->boundary($city->name_en, $city->name_ar) ?? ['boundary'=>null,'center'=>null,'bbox'=>null,'label'=>null]);
    }
    public function translate(Request $request, CityLookupService $service)
    {
        $data=$request->validate(['text'=>['required','string','max:150'],'source'=>['required','in:ar,en'],'target'=>['required','in:ar,en','different:source']]);
        return response()->json(['text'=>$service->translate($data['text'],$data['source'],$data['target'])]);
    }
    public function locate(Request $request, CityLookupService $service)
    {
        $data=$request->validate(['name'=>['required','string','max:150']]);
        return response()->json($service->locate($data['name']));
    }
    private function saudi(City $city): void { abort_unless(City::saudi()->whereKey($city->id)->exists(),404); }
    private function validated(Request $request, int $countryId, ?City $city=null): array
    {
        return $request->validate([
            'name_ar'=>['required','string','max:150',Rule::unique('cities')->where('country_id',$countryId)->ignore($city?->id)],
            'name_en'=>['required','string','max:150',Rule::unique('cities')->where('country_id',$countryId)->ignore($city?->id)],
            'latitude'=>['nullable','required_with:longitude','numeric','between:-90,90'],
            'longitude'=>['nullable','required_with:latitude','numeric','between:-180,180'],
            'sort_order'=>['required','integer','min:0','max:99999'], 'is_active'=>['required','boolean'],
        ]);
    }
}
