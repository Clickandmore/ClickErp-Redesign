<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyServiceCategory;
use App\Services\Hourly\HourlyCategoryConfig;
use Illuminate\Http\Request;

/** واجهات فئات الخدمة للويب والتطبيق: قائمة الفئات النشطة وإعدادات نموذج الطلب وملاحظاته. */
class CategoryApiController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }

    public function index(Request $request)
    {
        $locale = $this->locale($request);
        return response()->json(['data' => HourlyServiceCategory::ordered()->where('is_active', true)->get()->map(fn ($c) => [
            'id' => $c->id, 'slug' => $c->slug, 'name' => $locale === 'en' && $c->name_en ? $c->name_en : $c->name_ar,
            'icon_type' => $c->icon_type, 'icon' => $c->icon_type === 'library' ? $c->icon : $c->icon_url, 'color' => $c->color,
        ])->values()]);
    }

    public function config(Request $request, int $id, HourlyCategoryConfig $config)
    {
        $category = HourlyServiceCategory::where('is_active', true)->findOrFail($id);
        return response()->json(['data' => $config->forCategory($category, 'web_app', $this->locale($request))]);
    }

    private function locale(Request $request): string
    {
        $locale = $request->query('locale', $request->getPreferredLanguage(['ar', 'en']));
        return in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }
}
