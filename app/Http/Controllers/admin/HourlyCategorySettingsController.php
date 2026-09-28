<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hourly\CategorySettingsRequest;
use App\Models\Hourly\{HourlyCategorySetting, HourlyServiceCategory};
use App\Services\Hourly\ServiceCategoryService;

/** تبويب «الإعدادات»: يحفظ القيم المخصصة للفئة فقط، والباقي يرث الإعداد العام. */
class HourlyCategorySettingsController extends Controller
{
    public function update(CategorySettingsRequest $request, HourlyServiceCategory $category, ServiceCategoryService $service)
    {
        $row = HourlyCategorySetting::firstOrNew(['service_category_id' => $category->id]);
        $before = $row->settings ?? [];
        $after = $request->settings();
        $row->fill(['settings' => $after, 'updated_by' => auth('admin')->id()])->save();
        $service->audit($category, 'settings', 'edit', $before, $after);
        return redirect()->to(route('admin.hourly-service-categories.show', $category).'#settings')->with('success', 'تم حفظ إعدادات الفئة.');
    }
}
