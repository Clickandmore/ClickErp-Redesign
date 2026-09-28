<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hourly\{HourlyServiceCategory, HourlyTeam, ServiceDistrict};
use App\Services\Hourly\ServiceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** تبويب «مناطق الخدمة» في إدارة الفئة: ربط الأحياء بالفئة مع الفريق والحالة، فرديًا وجماعيًا. */
class HourlyCategoryDistrictController extends Controller
{
    public function __construct(private ServiceCategoryService $service) {}

    public function store(Request $request, HourlyServiceCategory $category)
    {
        $data = $request->validate([
            'city_id' => ['required', 'integer'],
            'district_ids' => ['required', 'array', 'min:1'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('hourly_service_districts', 'id')->where('city_id', $request->input('city_id'))],
            'team_id' => ['nullable', 'integer', Rule::exists('hourly_teams', 'id')->whereNull('deleted_at')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        City::saudi()->findOrFail($data['city_id']);
        DB::transaction(function () use ($category, $data, $request) {
            $existing = $category->districts()->pluck('hourly_service_districts.id')->all();
            if ($dup = array_intersect($data['district_ids'], $existing)) {
                throw ValidationException::withMessages(['district_ids' => __('hourly_category_ui.duplicates')]);
            }
            $attach = array_fill_keys($data['district_ids'], ['team_id' => $data['team_id'] ?? null, 'is_active' => $request->boolean('is_active', true)]);
            $category->districts()->attach($attach);
            $this->service->audit($category, 'districts', 'add', null, ['city_id' => (int) $data['city_id'], 'districts' => $this->names($data['district_ids']), 'team_id' => $data['team_id'] ?? null]);
        });
        return $this->back($category, __('hourly_category_ui.areas_added', ['count'=>count($data['district_ids'])]));
    }

    public function update(Request $request, HourlyServiceCategory $category, ServiceDistrict $district)
    {
        $data = $request->validate([
            'team_id' => ['sometimes', 'nullable', 'integer', Rule::exists('hourly_teams', 'id')->whereNull('deleted_at')],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);
        $link = $this->link($category, $district);
        $changes = array_intersect_key($data, array_flip(['team_id', 'is_active']));
        if ($changes) {
            DB::table('hourly_category_district')->where('id', $link->id)->update($changes + ['updated_at' => now()]);
            $this->service->audit($category, 'districts', 'edit', ['district' => $district->name] + array_intersect_key((array) $link, $changes), ['district' => $district->name] + $changes);
        }
        $fresh = $this->link($category, $district);
        if ($request->expectsJson()) {
            return response()->json(['message' => __('hourly_category_ui.area_saved'), 'is_active' => (bool) $fresh->is_active, 'team_id' => $fresh->team_id, 'team' => $fresh->team_id ? HourlyTeam::find($fresh->team_id)?->name : null]);
        }
        return $this->back($category, __('hourly_category_ui.area_saved'));
    }

    public function destroy(HourlyServiceCategory $category, ServiceDistrict $district)
    {
        $link = $this->link($category, $district);
        DB::table('hourly_category_district')->where('id', $link->id)->delete();
        $this->service->audit($category, 'districts', 'remove', ['district' => $district->name, 'team_id' => $link->team_id, 'is_active' => (bool) $link->is_active], null);
        return $this->back($category, __('hourly_category_ui.area_deleted'));
    }

    /** «تعديل الكل» لمدينة: تفعيل أو تعطيل أو تغيير الفريق لكل أحياء المدينة في الفئة. */
    public function bulk(Request $request, HourlyServiceCategory $category)
    {
        $data = $request->validate([
            'city_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'team'])],
            'team_id' => ['nullable', 'required_if:action,team', 'integer', Rule::exists('hourly_teams', 'id')->whereNull('deleted_at')],
        ]);
        $query = $this->cityLinks($category, (int) $data['city_id']);
        $changes = match ($data['action']) {
            'activate' => ['is_active' => true],
            'deactivate' => ['is_active' => false],
            'team' => ['team_id' => $data['team_id']],
        };
        $count = (clone $query)->count();
        $query->update($changes + ['updated_at' => now()]);
        $this->service->audit($category, 'districts', 'bulk_'.$data['action'], ['city_id' => (int) $data['city_id'], 'count' => $count], ['city_id' => (int) $data['city_id']] + $changes);
        return $this->back($category, __('hourly_category_ui.areas_updated', ['count'=>$count]));
    }

    public function destroyCity(HourlyServiceCategory $category, City $city)
    {
        $query = $this->cityLinks($category, $city->id);
        $names = $this->names((clone $query)->pluck('district_id')->all());
        $query->delete();
        $this->service->audit($category, 'districts', 'remove_all', ['city_id' => $city->id, 'districts' => $names], null);
        return $this->back($category, __('hourly_category_ui.areas_deleted', ['city'=>app()->getLocale()==='en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar]));
    }

    private function link(HourlyServiceCategory $category, ServiceDistrict $district): object
    {
        $link = DB::table('hourly_category_district')->where('service_category_id', $category->id)->where('district_id', $district->id)->first();
        abort_unless($link, 404);
        return $link;
    }

    private function cityLinks(HourlyServiceCategory $category, int $cityId)
    {
        return DB::table('hourly_category_district')->where('service_category_id', $category->id)
            ->whereIn('district_id', ServiceDistrict::where('city_id', $cityId)->select('id'));
    }

    private function names(array $ids): array
    {
        return ServiceDistrict::whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
    }

    private function back(HourlyServiceCategory $category, string $message)
    {
        return redirect()->to(route('admin.hourly-service-categories.show', $category).'#districts')->with('success', $message);
    }
}
