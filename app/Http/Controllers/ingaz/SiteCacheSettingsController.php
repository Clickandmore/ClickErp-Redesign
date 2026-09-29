<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Support\ExternalSiteCacheSettings;
use Illuminate\Http\Request;

/**
 * صفحة "كاش المواقع الخارجية" داخل الإعدادات. الصلاحيات تُفرض من
 * AdminRoutePermissionMatrix (settings.site-cache.*).
 */
class SiteCacheSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.site-cache', [
            'cacheSettings' => ExternalSiteCacheSettings::all(),
            'ranges' => ExternalSiteCacheSettings::RANGES,
            'refreshSeconds' => ExternalSiteCacheSettings::SITE_REFRESH_SECONDS,
            'activeSection' => 'site-cache',
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'enabled' => ['nullable', 'boolean'],
            'stale_fallback_enabled' => ['nullable', 'boolean'],
        ];

        foreach (ExternalSiteCacheSettings::RANGES as $key => [$min, $max]) {
            $rules[$key] = ['required', 'integer', 'min:' . $min, 'max:' . $max];
        }

        $validated = $request->validate($rules);

        if ($validated['connect_timeout_seconds'] > $validated['request_timeout_seconds']) {
            return back()
                ->withInput()
                ->withErrors(['connect_timeout_seconds' => 'مهلة الاتصال يجب ألا تتجاوز مهلة الطلب.']);
        }

        ExternalSiteCacheSettings::save($validated);

        return redirect()
            ->route('settings.site-cache.edit')
            ->with('success', 'تم حفظ إعدادات كاش المواقع. تُطبَّق على المواقع خلال دقيقة تقريبًا.');
    }

    public function flush()
    {
        ExternalSiteCacheSettings::flushSites();

        return redirect()
            ->route('settings.site-cache.edit')
            ->with('success', 'تم طلب مسح كاش المواقع. ستعرض المواقع البيانات الجديدة خلال دقيقة تقريبًا.');
    }
}
