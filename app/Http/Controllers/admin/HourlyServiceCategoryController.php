<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hourly\ServiceCategoryRequest;
use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyServiceCategory};
use App\Services\Hourly\ServiceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

/** فئات خدمة الساعات: القائمة والإضافة والتعديل والترتيب والحذف وصفحة إدارة الفئة. */
class HourlyServiceCategoryController extends Controller
{
    public function __construct(private ServiceCategoryService $service) {}

    public function index()
    {
        $categories = HourlyServiceCategory::ordered()->withCount('packages')->get();
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $publication=$categories->mapWithKeys(fn($category)=>[$category->id=>$catalog->categoryPublicationStatus($category)]);
        return view('admin.hourly_service_categories.index', compact('categories','publication'));
    }

    public function store(ServiceCategoryRequest $request)
    {
        $category = DB::transaction(function () use ($request) {
            $category = new HourlyServiceCategory(['sort_order' => (int) HourlyServiceCategory::withTrashed()->max('sort_order') + 1]);
            $this->fill($category, $request);
            $category->save();
            $this->service->audit($category, 'category', 'create', null, $this->service->snapshot($category));
            return $category;
        });
        return redirect()->route('admin.hourly-service-categories.show', $category)->with('success', __('hourly.category_created', ['name' => $category->name_ar]));
    }

    public function update(ServiceCategoryRequest $request, HourlyServiceCategory $category)
    {
        DB::transaction(function () use ($request, $category) {
            $before = $this->service->snapshot($category);
            $this->fill($category, $request);
            $category->save();
            // الاسم النصي القديم في عناصر الخدمة يبقى متزامنًا مع اسم الفئة.
            HourlyPackage::withTrashed()->where('service_category_id', $category->id)->update(['category' => $category->name_ar]);
            $this->service->audit($category, 'category', 'edit', $before, $this->service->snapshot($category));
        });
        if ($request->boolean('return_to_settings')) {
            return redirect()->to(route('admin.hourly-service-categories.show', $category).'#settings')->with('success', __('hourly.category_saved'));
        }
        return back()->with('success', __('hourly.category_saved'));
    }

    public function status(Request $request, HourlyServiceCategory $category)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $before = $this->service->snapshot($category);
        $category->update(['is_active' => $data['is_active']]);
        $this->service->audit($category, 'category', 'status', $before, $this->service->snapshot($category));
        return response()->json(['is_active' => $category->is_active, 'message' => $category->is_active ? __('hourly.category_enabled') : __('hourly.category_disabled'),
            'publication'=>app(\App\Services\Hourly\HourlyWebsiteCatalog::class)->categoryPublicationStatus($category)]);
    }

    public function move(HourlyServiceCategory $category, string $direction)
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);
        DB::transaction(function () use ($category, $direction) {
            $ids = HourlyServiceCategory::ordered()->lockForUpdate()->pluck('id')->all();
            $before=$ids;
            $index = array_search($category->id, $ids, true);
            $target = $direction === 'up' ? $index - 1 : $index + 1;
            if ($index === false || !isset($ids[$target])) return;
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            foreach ($ids as $position => $id) HourlyServiceCategory::whereKey($id)->update(['sort_order' => $position + 1]);
            $this->service->audit($category,'category','reorder',$before,$ids);
        });
        return back();
    }

    public function destroy(HourlyServiceCategory $category)
    {
        if ($category->packages()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['category' => __('hourly.category_has_items')]);
        }
        DB::transaction(function () use ($category) {
            $this->service->audit($category, 'category', 'delete', $this->service->snapshot($category), null);
            $category->delete();
        });
        return redirect()->route('admin.hourly-service-categories.index')->with('success', __('hourly.category_deleted'));
    }

    /** صفحة إدارة الفئة: هيدر ثابت وتبويبات. بيانات كل تبويب تُجمع من ManageData. */
    public function show(HourlyServiceCategory $category)
    {
        $packageIds = HourlyPackage::withTrashed()->where('service_category_id', $category->id)->pluck('id');
        $orders = HourlyRentalOrder::whereIn('hourly_package_id', $packageIds);
        $stats = ['orders' => (clone $orders)->count(), 'amount' => (float) (clone $orders)->sum('total_amount')];
        return view('admin.hourly_service_categories.manage', ['category' => $category, 'stats' => $stats] + $this->manageData($category));
    }

    /** إعدادات نموذج الطلب وملاحظاته لقناة اللوحة (تستخدمها شاشة إنشاء الطلب). */
    public function orderConfig(HourlyServiceCategory $category, \App\Services\Hourly\HourlyCategoryConfig $config)
    {
        return response()->json(['data' => $config->forCategory($category, 'panel')]);
    }

    /** بيانات تبويبات صفحة الإدارة. */
    protected function manageData(HourlyServiceCategory $category): array
    {
        return $this->districtsData($category) + [
            'closures' => $category->closures()->orderByRaw('ends_at < ? asc', [now()])->orderBy('starts_at')->get(),
            // الإعدادات: المخصص للفئة، والقيمة العامة الموروثة لعرضها كتلميح.
            'paymentMatrix' => HourlyCategoryPaymentController::matrix($category),
            'customNotes' => $category->notes()->get(),
            'automaticNotes' => ['ar' => app(\App\Services\Hourly\HourlyCategoryConfig::class)->automaticNotes($category, 'ar'), 'en' => app(\App\Services\Hourly\HourlyCategoryConfig::class)->automaticNotes($category, 'en')],
            'categorySettings' => app(\App\Services\Hourly\HourlyCategorySettings::class)->stored($category->id),
            'generalSettings' => collect(array_keys(\App\Services\Hourly\HourlyCategorySettings::GENERAL))->mapWithKeys(fn ($key) => [$key => app(\App\Services\Hourly\HourlyCategorySettings::class)->general($key)])->all(),
        ];
    }

    /** بطاقة لكل مدينة مغطاة (من الإعداد العام، أو كل المدن التي لها أحياء) مع أحيائها وارتباطها بالفئة. */
    private function districtsData(HourlyServiceCategory $category): array
    {
        $links = DB::table('hourly_category_district')->where('service_category_id', $category->id)->get()->keyBy('district_id');
        $linkedCities = \App\Models\Hourly\ServiceDistrict::whereIn('id', $links->keys())->distinct()->pluck('city_id');
        $cityIds = collect(\App\Support\HourlyRentalSettings::get('city_ids'))
            ->whenEmpty(fn () => \App\Models\Hourly\ServiceDistrict::distinct()->pluck('city_id'))
            ->merge($linkedCities)->unique()->values();
        $cities = \App\Models\City::saudi()->whereIn('id', $cityIds)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $districts = \App\Models\Hourly\ServiceDistrict::whereIn('city_id', $cities->modelKeys())->orderBy('name')->get(['id', 'city_id', 'name', 'name_en', 'is_active'])->groupBy('city_id');
        $teams = \App\Models\Hourly\HourlyTeam::where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $links->pluck('team_id')->filter()))->orderBy('name')->get(['id', 'name', 'is_active']);
        // نفس قاعدة الحجز في assertDistrictCovered: فريق الربط النشط، وإلا أول فريق نشط للحي. حي بلا فريق لا تُقبل فيه الحجوزات.
        $activeTeams = $teams->where('is_active', true)->pluck('id')->all();
        $districtTeams = DB::table('hourly_team_district as td')->join('hourly_teams as t', 't.id', '=', 'td.hourly_team_id')
            ->whereIn('td.district_id', $links->keys())->where('td.is_active', true)->where('t.is_active', true)
            ->orderBy('t.id')->get(['td.district_id', 't.id', 't.name'])->groupBy('district_id')->map->first();
        $effectiveTeams = $links->map(fn ($link) => $link->team_id && in_array($link->team_id, $activeTeams)
            ? null : ($districtTeams[$link->district_id] ?? false));
        return ['coverageCities' => $cities, 'cityDistricts' => $districts, 'districtLinks' => $links, 'teams' => $teams, 'districtTeams' => $effectiveTeams];
    }

    private function fill(HourlyServiceCategory $category, ServiceCategoryRequest $request): void
    {
        $data = $request->validated();
        $category->fill([
            'name_ar' => $data['name_ar'], 'name_en' => $data['name_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null, 'description_en' => $data['description_en'] ?? null,
            'color' => $data['color'] ?? null, 'is_active' => (bool) $data['is_active'], 'icon_type' => $data['icon_type'],
        ]);
        $category->slug = $data['slug'] ?? null ?: ($category->slug ?: $this->service->uniqueSlug($data['name_en'] ?? '' ?: $data['name_ar'], $category->id));
        if ($data['icon_type'] === 'library') {
            if ($category->getOriginal('icon_type') === 'upload' && $category->getOriginal('icon')) Storage::disk('public')->delete($category->getOriginal('icon'));
            $category->icon = $data['icon'];
        } elseif ($request->hasFile('icon_file')) {
            if ($category->getOriginal('icon_type') === 'upload' && $category->getOriginal('icon')) Storage::disk('public')->delete($category->getOriginal('icon'));
            $category->icon = $request->file('icon_file')->store('hourly/categories', 'public');
        }
        if ($request->boolean('remove_image') && $category->image) {
            Storage::disk('public')->delete($category->image);
            $category->image = null;
        }
        if ($request->hasFile('image')) {
            if ($category->image) Storage::disk('public')->delete($category->image);
            $category->image = $request->file('image')->store('hourly/categories', 'public');
        }
    }
}
