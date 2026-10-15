<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hourly\ServiceDistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HourlyDistrictController extends Controller
{
    public function create(Request $request)
    {
        $request->validate(['city_id'=>['nullable','integer']]);
        if($request->filled('city_id')) City::saudi()->findOrFail($request->city_id);
        return $this->form(new ServiceDistrict(['city_id'=>$request->input('city_id'),'is_active'=>true]));
    }

    public function edit(ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($district->city_id)->exists(),404);
        return $this->form($district);
    }

    private function form(ServiceDistrict $district)
    {
        $ids=$district->exists ? $district->teams()->pluck('hourly_teams.id')->all() : [];
        return view('admin.hourly_districts.form',[
            'district'=>$district,'cities'=>City::saudi()->orderBy('name_ar')->get(), 'teamIds'=>$ids,
            'teams'=>\App\Models\Hourly\HourlyTeam::where(fn($q)=>$q->where('is_active',true)->orWhereIn('id',$ids))->orderBy('name')->get(),
        ]);
    }

    public function locate(Request $request, \App\Services\Hourly\CityLookupService $lookup)
    {
        $data=$request->validate(['city_id'=>['required','integer'],'name'=>['required','string','max:150']]);
        $city=City::saudi()->findOrFail($data['city_id']);
        return response()->json($lookup->locate($data['name'],$city->name_en ?: $city->name_ar));
    }

    /** تحويل موقع ملصوق من Google Maps (Plus Code أو رابط أو إحداثيات) إلى خط العرض والطول. */
    public function resolveLocation(Request $request, \App\Services\Hourly\LocationTextResolver $resolver)
    {
        $data=$request->validate(['location_text'=>['required','string','max:2000'],'city_id'=>['nullable','integer']]);
        $city=!empty($data['city_id']) ? City::saudi()->find($data['city_id']) : null;
        return response()->json($resolver->resolve($data['location_text'],$city));
    }

    /** ربط الفرق بالمنطقة من مودال «الفرق»: يحافظ على حالة التغطية للارتباطات القائمة ويرفض إضافة فريق متوقف. */
    public function teams(Request $request, ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($district->city_id)->exists(), 404);
        $data=$request->validate([
            'team_ids'=>['present','array'],
            'team_ids.*'=>['integer','distinct',Rule::exists('hourly_teams','id')->whereNull('deleted_at')],
        ]);
        $existing=$district->teams()->pluck('hourly_teams.id')->all();
        if(\App\Models\Hourly\HourlyTeam::whereIn('id',array_diff($data['team_ids'],$existing))->where('is_active',false)->exists()) {
            throw ValidationException::withMessages(['team_ids'=>'لا يمكن ربط فريق متوقف بالمنطقة.']);
        }
        $district->teams()->sync($data['team_ids']);
        $teams=$district->teams()->orderBy('name')->get()->map(fn($team)=>[
            'id'=>$team->id,'name'=>$team->name,'active'=>(bool)$team->is_active,'coverage'=>(bool)$team->pivot->is_active,
            'manage_url'=>route('admin.hourly-teams.manage',$team).'#areas',
        ])->values();
        return response()->json(['message'=>'تم حفظ فرق المنطقة.','teams'=>$teams]);
    }

    /** حدود المنطقة من OpenStreetMap للمراجعة في الخريطة قبل الحفظ؛ لا تُحفظ هنا. */
    public function osmPolygon(ServiceDistrict $district, \App\Services\Hourly\CityLookupService $lookup)
    {
        $city=City::saudi()->findOrFail($district->city_id);
        return response()->json($lookup->districtPolygon([$district->name, $district->name_en], $city->name_ar, $city->name_en ?? ''));
    }

    public function status(Request $request, ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($district->city_id)->exists(),404);
        $district->update($request->validate(['is_active'=>['required','boolean']]));
        if($request->expectsJson())return response()->json(['is_active'=>$district->is_active,'message'=>'تم تحديث حالة المنطقة.']);
        return back()->with('success','تم تحديث حالة المنطقة.');
    }

    public function polygon(Request $request, City $city, ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($city->id)->exists() && (int)$district->city_id===(int)$city->id,404);
        $request->validate(['polygon'=>['present','nullable','array','max:1000']]);
        $district->update(['polygon'=>\App\Services\Hourly\DistrictPolygon::validate($request->input('polygon'))]);
        return response()->json(['message'=>'تم حفظ حدود المنطقة.','polygon'=>$district->polygon]);
    }
    public function cities(Request $request)
    {
        $data = $request->validate(['search'=>['nullable','string','max:150']]);
        $query = City::saudi()->withCount('serviceDistricts');
        if ($search = $data['search'] ?? null) $query->where(fn ($q) => $q->where('name_ar','like','%'.$search.'%')->orWhere('name_en','like','%'.$search.'%'));
        return view('admin.hourly_districts.cities', ['cities'=>$query->orderBy('name_ar')->paginate(25)->withQueryString(), 'search'=>$data['search'] ?? '']);
    }

    public function cityDistricts(Request $request, City $city)
    {
        abort_unless(City::saudi()->whereKey($city->id)->exists(), 404);
        $request->merge(['city_id'=>$city->id]);
        return $this->index($request);
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['city_id'=>['nullable','integer','exists:cities,id'], 'search'=>['nullable','string','max:150'], 'per_page'=>['nullable',Rule::in([10,25,50,100])], 'sort'=>['nullable',Rule::in(['id','name','name_en','slug','latitude','longitude','is_active'])], 'direction'=>['nullable','in:asc,desc']]);
        $selectedCity = !empty($filters['city_id']) ? City::saudi()->findOrFail($filters['city_id']) : null;
        $query = ServiceDistrict::whereHas('city', fn ($q) => $q->saudi())->with(['city','teams'])->withCount('teams');
        if ($city = $filters['city_id'] ?? null) $query->where('city_id', $city);
        if ($search = $filters['search'] ?? null) $query->where(fn($q)=>$q->where('name','like',"%$search%")->orWhere('name_en','like',"%$search%")->orWhere('slug','like',"%$search%"));
        $total=(clone $query)->count();$mapped=(clone $query)->whereNotNull('polygon')->count();
        return view('admin.hourly_districts.list', ['districts'=>$query->orderBy($filters['sort'] ?? 'id',$filters['direction'] ?? 'desc')->paginate($filters['per_page'] ?? 25)->withQueryString(), 'cities'=>City::saudi()->orderBy('name_ar')->get(), 'filters'=>$filters, 'selectedCity'=>$selectedCity,'total'=>$total,'mapped'=>$mapped,
            // مودال الإضافة والتعديل: الفرق المتوقفة المرتبطة بمناطق تبقى في القائمة حتى لا يُزال ربطها عند الحفظ.
            'newDistrict'=>new ServiceDistrict(['city_id'=>$selectedCity?->id,'is_active'=>true]),
            'teams'=>\App\Models\Hourly\HourlyTeam::where(fn($q)=>$q->where('is_active',true)->orWhereHas('districts'))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data=$this->validated($request);
        $district=DB::transaction(function()use($data,$request){$district=new ServiceDistrict();$this->saveDistrict($district,$data,$request);return $district;});
        return redirect()->route('admin.hourly-cities.districts',$district->city_id)->with('success','تمت إضافة المنطقة.');
    }

    public function update(Request $request, ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($district->city_id)->exists(), 404);
        $data = $this->validated($request, $district);
        if ((int)$data['city_id'] !== (int)$district->city_id && $this->isAssigned($district)) {
            throw ValidationException::withMessages(['city_id'=>'لا يمكن نقل منطقة مرتبطة بفرق إلى مدينة أخرى.']);
        }
        DB::transaction(fn()=>$this->saveDistrict($district,$data,$request));
        return redirect()->route('admin.hourly-cities.districts',$district->city_id)->with('success','تم تعديل المنطقة.');
    }

    public function destroy(ServiceDistrict $district)
    {
        abort_unless(City::saudi()->whereKey($district->city_id)->exists(), 404);
        if ($this->isAssigned($district)) {
            return back()->withErrors(['district'=>'المنطقة مرتبطة بفرق. أزل ارتباطاتها قبل حذفها من الدليل.']);
        }
        $district->delete();
        return back()->with('success', 'تم حذف المنطقة من الدليل.');
    }

    private function isAssigned(ServiceDistrict $district): bool
    {
        return DB::table('hourly_team_district')->where('district_id', $district->id)->exists();
    }

    private function validated(Request $request, ?ServiceDistrict $district = null): array
    {
        if (!City::saudi()->whereKey($request->input('city_id'))->exists()) {
            throw ValidationException::withMessages(['city_id'=>'اختر مدينة من مدن المملكة.']);
        }
        return $request->validate([
            'city_id'=>['required','integer','exists:cities,id'],
            'name'=>['required','string','max:150',Rule::unique('hourly_service_districts')->where('city_id',$request->input('city_id'))->ignore($district?->id)],
            'name_en'=>['nullable','string','max:150',Rule::unique('hourly_service_districts')->where('city_id',$request->input('city_id'))->ignore($district?->id)],
            'latitude'=>['nullable','required_with:longitude','numeric','between:-90,90'],
            'longitude'=>['nullable','required_with:latitude','numeric','between:-180,180'],
            'is_active'=>['sometimes','boolean'],
            'teams_submitted'=>['sometimes','boolean'], 'team_ids'=>['nullable','array'],
            'team_ids.*'=>['integer','distinct',Rule::exists('hourly_teams','id')->whereNull('deleted_at')],
        ]);
    }

    private function saveDistrict(ServiceDistrict $district,array $data,Request $request): void
    {
        $ids=$data['team_ids'] ?? [];unset($data['team_ids'],$data['teams_submitted']);
        if($request->boolean('teams_submitted')) {
            $existing=$district->exists ? $district->teams()->pluck('hourly_teams.id')->all() : [];
            if(\App\Models\Hourly\HourlyTeam::whereIn('id',array_diff($ids,$existing))->where('is_active',false)->exists())throw ValidationException::withMessages(['team_ids'=>'اختر فرقًا نشطة.']);
        }
        $district->fill($data);$district->save();
        $district->update(['slug'=>(\Illuminate\Support\Str::slug($district->name_en ?: $district->name) ?: 'district').'-'.$district->id]);
        if($request->boolean('teams_submitted'))$district->teams()->sync($ids);
    }
}
