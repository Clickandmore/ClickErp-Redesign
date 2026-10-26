<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyCategoryClosure, HourlyServiceCategory};
use App\Services\Hourly\ServiceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** تبويب «تشغيل/إيقاف»: الإغلاقات المؤقتة للفئة. */
class HourlyCategoryClosureController extends Controller
{
    public function __construct(private ServiceCategoryService $service) {}

    public function store(Request $request, HourlyServiceCategory $category)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('hourly_category_district', 'district_id')->where('service_category_id', $category->id)],
            'reason' => ['nullable', 'string', 'max:500'],
        ], ['starts_at.after_or_equal' => __('hourly_category_ui.closure_past')], ['starts_at' => __('hourly_category_ui.closure_start'), 'ends_at' => __('hourly_category_ui.closure_end'), 'district_ids' => __('hourly_category_ui.districts')]);
        $districtIds = !empty($data['district_ids']) ? array_values(array_unique(array_map('intval', $data['district_ids']))) : null;
        if ($districtIds) sort($districtIds);
        $data['starts_at'] = \Carbon\Carbon::parse($data['starts_at'])->startOfMinute();
        $data['ends_at'] = \Carbon\Carbon::parse($data['ends_at'])->startOfMinute();
        // قفل الفئة يسلسل الطلبات المتزامنة (ضغطتان على الحفظ) فلا يُنشأ نفس الإغلاق مرتين.
        DB::transaction(function () use ($category, $data, $districtIds) {
            HourlyServiceCategory::whereKey($category->id)->lockForUpdate()->first();
            $duplicate = $category->closures()->where('starts_at', $data['starts_at'])->where('ends_at', $data['ends_at'])->get()
                ->contains(fn ($c) => ($c->district_ids ? collect($c->district_ids)->map('intval')->sort()->values()->all() : null) === $districtIds);
            if ($duplicate) return;
            $closure = $category->closures()->create([
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'district_ids' => $districtIds,
                'reason' => $data['reason'] ?? null, 'created_by' => auth('admin')->id(),
            ]);
            $this->service->audit($category, 'closures', 'add', null, $this->describe($closure));
        });
        return $this->back($category, __('hourly_category_ui.closure_added'));
    }

    public function destroy(HourlyServiceCategory $category, HourlyCategoryClosure $closure)
    {
        abort_unless((int) $closure->service_category_id === $category->id, 404);
        $this->service->audit($category, 'closures', 'remove', $this->describe($closure), null);
        $closure->delete();
        return $this->back($category, __('hourly_category_ui.closure_deleted'));
    }

    public function clear(HourlyServiceCategory $category)
    {
        DB::transaction(function () use ($category) {
            $count = $category->closures()->count();
            if (!$count) return;
            $this->service->audit($category, 'closures', 'clear', ['count' => $count], null);
            $category->closures()->delete();
        });
        return $this->back($category, __('hourly_category_ui.closures_cleared'));
    }

    private function describe(HourlyCategoryClosure $closure): array
    {
        return ['starts_at' => $closure->starts_at->format('Y-m-d H:i'), 'ends_at' => $closure->ends_at->format('Y-m-d H:i'), 'district_ids' => $closure->district_ids, 'reason' => $closure->reason];
    }

    private function back(HourlyServiceCategory $category, string $message)
    {
        return redirect()->to(route('admin.hourly-service-categories.show', $category).'#operation')->with('success', $message);
    }
}
