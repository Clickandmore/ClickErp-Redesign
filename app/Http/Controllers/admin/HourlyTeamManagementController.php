<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyTeam, HourlyDriver, ServiceDistrict};
use App\Models\City;
use App\Models\ShelterPlaces;
use App\Services\Hourly\DriverTeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HourlyTeamManagementController extends Controller
{
    public function show(HourlyTeam $team)
    {
        return view('admin.hourly_teams.manage', [
            'team' => $team->load(['driverMembers.driver', 'maidMembers.maid', 'leaders', 'districts.city', 'baseShelterPlace']),
            'maids' => \App\Models\ingaz\Maid::where('rental_mode','hourly')
                ->whereHas('jobTypes',fn($q)=>$q->where('job_types.id',\App\Services\Rental\RentalMaidQuery::rentalJobTypeId()))
                ->whereDoesntHave('hourlyTeamMemberships',fn($q)=>$q->where('hourly_team_id',$team->id))
                ->orderBy('name')->get(),
            'drivers' => HourlyDriver::where('is_active', true)->orderBy('name')->get(),
            'leaderDrivers' => HourlyDriver::orderBy('name')->get(['id', 'name', 'employee_number', 'is_active']),
            'cities' => City::saudi()->active()->orderBy('name_ar')->get(),
            'districts' => ServiceDistrict::with('city')->whereHas('city', fn ($q) => $q->saudi()->active())->orderBy('name')->get(),
            'shelters' => ShelterPlaces::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function location(Request $request, HourlyTeam $team)
    {
        $request->merge([
            'base_location_type' => $request->input('base_location_type', 'custom'),
        ]);

        $data = $request->validate([
            'base_location_type' => ['required', Rule::in(['custom', 'shelter'])],
            'base_shelter_place_id' => ['nullable', 'required_if:base_location_type,shelter', 'integer', 'exists:shelter_places,id'],
            'base_lat' => ['nullable', 'required_if:base_location_type,custom', 'numeric', 'between:-90,90'],
            'base_lng' => ['nullable', 'required_if:base_location_type,custom', 'numeric', 'between:-180,180'],
        ]);

        if ($data['base_location_type'] === 'shelter') {
            $shelter = ShelterPlaces::findOrFail($data['base_shelter_place_id']);
            if ($shelter->latitude === null || $shelter->longitude === null) {
                throw ValidationException::withMessages([
                    'base_shelter_place_id' => 'موقع مقر الإيواء غير محدد. أضف إحداثيات المقر من شاشة أماكن الإيواء أولًا.',
                ]);
            }

            $team->update([
                'base_location_type' => 'shelter',
                'base_shelter_place_id' => $shelter->id,
                'base_lat' => $shelter->latitude,
                'base_lng' => $shelter->longitude,
            ]);
        } else {
            $team->update([
                'base_location_type' => 'custom',
                'base_shelter_place_id' => null,
                'base_lat' => $data['base_lat'],
                'base_lng' => $data['base_lng'],
            ]);
        }

        return $this->back($team, 'location');
    }

    public function locate(Request $request, HourlyTeam $team, \App\Services\Hourly\CityLookupService $lookup)
    {
        $data = $request->validate(['name'=>['required','string','min:2','max:200']]);
        return response()->json($lookup->locate($data['name'], null, true));
    }

    public function drivers(Request $request, HourlyTeam $team, DriverTeamService $service)
    {
        $data = $request->validate(['driver_ids'=>['required','array','min:1'], 'driver_ids.*'=>['integer','distinct',Rule::exists('hourly_drivers','id')->whereNull('deleted_at')->where('is_active',true)], 'role'=>['required','in:primary,secondary']]);
        abort_unless($team->is_active, 422, 'الفريق متوقف. فعّله أولًا.');
        DB::transaction(function () use ($data, $team, $service) {
            foreach (HourlyDriver::whereIn('id', $data['driver_ids'])->orderBy('id')->get() as $driver) $service->assign($driver, $team, $data['role']);
        });
        return $this->back($team, 'drivers');
    }

    public function member(Request $request, HourlyTeam $team, $member)
    {
        $data = $request->validate(['action'=>['required','in:activate,deactivate,remove']]);
        $member = $team->driverMembers()->findOrFail($member);
        $member->update($data['action']==='remove' ? ['left_at'=>now()->toDateString()] : ['is_active'=>$data['action']==='activate']);
        if ($request->expectsJson() && $data['action']!=='remove') {
            return response()->json(['message'=>$member->is_active ? 'تم تفعيل عضوية السائق.' : 'تم إيقاف عضوية السائق.', 'is_active'=>(bool) $member->is_active]);
        }
        return $this->back($team, 'drivers');
    }

    public function leaders(Request $request, HourlyTeam $team)
    {
        $data = $request->validate(['leader_ids'=>['nullable','array'], 'leader_ids.*'=>['integer','distinct',Rule::exists('hourly_drivers','id')->whereNull('deleted_at')]]);
        $team->leaders()->sync($data['leader_ids'] ?? []);
        return $this->back($team, 'leaders');
    }

    public function maids(Request $request, HourlyTeam $team, \App\Services\Hourly\MaidTeamService $service)
    {
        $data=$request->validate(['maid_ids'=>['required','array','min:1'],'maid_ids.*'=>['integer','distinct',Rule::exists('maids','id')->whereNull('deleted_at')->where('rental_mode','hourly')],'role'=>['required','in:primary,secondary']]);
        abort_unless($team->is_active,422,'الفريق متوقف. فعّله أولًا.');
        DB::transaction(function () use ($data,$team,$service) {
            $maids=\App\Models\ingaz\Maid::whereIn('id',$data['maid_ids'])->whereHas('jobTypes',fn($q)=>$q->where('job_types.id',\App\Services\Rental\RentalMaidQuery::rentalJobTypeId()))->orderBy('id')->lockForUpdate()->get();
            abort_unless($maids->count()===count($data['maid_ids']),422,'اختر عاملات إيجار من نوع الساعات.');
            foreach ($maids as $maid) $service->assign($maid,$team,$data['role']);
        });
        return $this->back($team,'maids');
    }

    public function maidMember(Request $request, HourlyTeam $team, $member)
    {
        $data=$request->validate(['action'=>['required','in:activate,deactivate,remove']]);
        $member=$team->maidMembers()->findOrFail($member);
        $member->update($data['action']==='remove' ? ['left_at'=>now()->toDateString()] : ['is_active'=>$data['action']==='activate']);
        return $this->back($team,'maids');
    }

    public function districts(Request $request, HourlyTeam $team)
    {
        $data = $request->validate([
            'city_id'=>['required','integer',Rule::exists('cities','id')->where(fn ($q) => $q->where('is_active',true)->whereIn('country_id', DB::table('countries')->select('id')->where('code','SA')))],
            'district_ids'=>['required','array','min:1'],
            'district_ids.*'=>['integer','distinct',Rule::exists('hourly_service_districts','id')->where('city_id',$request->input('city_id'))],
        ]);
        $team->districts()->syncWithoutDetaching($data['district_ids']);
        return $this->back($team, 'areas');
    }

    public function createDistrict(Request $request, HourlyTeam $team)
    {
        $data = $request->validate(['city_id'=>['required','integer','exists:cities,id'], 'name'=>['required','string','max:150',Rule::unique('hourly_service_districts')->where('city_id',$request->input('city_id'))]]);
        ServiceDistrict::create($data);
        return $this->back($team, 'areas');
    }

    public function districtAction(Request $request, HourlyTeam $team)
    {
        $data = $request->validate(['action'=>['required','in:activate,deactivate,remove'], 'district_id'=>['nullable','integer']]);
        $query = DB::table('hourly_team_district')->where('hourly_team_id', $team->id);
        if (!empty($data['district_id'])) {
            $query->where('district_id', $data['district_id']);
            abort_unless((clone $query)->exists(), 404);
        }
        if ($data['action']==='remove') $query->delete();
        else $query->update(['is_active'=>$data['action']==='activate', 'updated_at'=>now()]);
        if ($request->expectsJson() && !empty($data['district_id']) && $data['action']!=='remove') {
            return response()->json(['message'=>$data['action']==='activate' ? 'تم تفعيل المنطقة للفريق.' : 'تم إيقاف المنطقة للفريق.', 'is_active'=>$data['action']==='activate']);
        }
        return $this->back($team, 'areas');
    }

    private function back(HourlyTeam $team, string $tab)
    {
        if (request()->expectsJson()) {
            return response()->json(['message'=>'تم حفظ التغييرات.', 'tab'=>$tab]);
        }
        return redirect()->to(route('admin.hourly-teams.manage', $team).'#'.$tab)->with('success','تم حفظ التغييرات.');
    }
}
