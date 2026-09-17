<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Support\HomeSectionSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeSectionSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.ingaz.home_sections.edit', [
            'allDefinitions' => HomeSectionSettings::definitions(),
            'definitions' => HomeSectionSettings::availableDefinitions(),
            'availableKeys' => HomeSectionSettings::availableKeys(),
            'visibility' => HomeSectionSettings::all(),
            'presets' => HomeSectionSettings::presets(),
            // اختيار الأقسام الموجودة في تصميم الموقع للمطور فقط.
            'isDevSuperAdmin' => (bool) $request->session()->get('dev_superadmin_authenticated'),
        ]);
    }

    public function update(Request $request)
    {
        // لا نقبل إلا الأقسام المعروضة لهذا الموقع؛ الأقسام غير الموجودة في
        // تصميمه تبقى كما هي.
        $keys = array_keys(HomeSectionSettings::availableDefinitions());

        $validated = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['nullable', 'boolean'],
        ]);

        $submitted = $validated['sections'] ?? [];

        // مفتاح القسم يُرسل دائماً بقيمة 0 من الحقل المخفي و1 عند التفعيل، لذلك
        // نعتبر أي قسم غائب عن الطلب «بدون تغيير» بدلاً من إخفائه بالخطأ.
        $visibility = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $submitted)) {
                $visibility[$key] = (bool) $submitted[$key];
            }
        }

        HomeSectionSettings::update($visibility);

        return redirect()
            ->route('home-sections.edit')
            ->with('success', __('home_sections.saved'));
    }

    /**
     * المطور يحدد الأقسام الموجودة فعلاً في تصميم موقع هذا العميل.
     * المسار داخل مجموعة dev.superadmin.
     */
    public function updateAvailable(Request $request)
    {
        $validated = $request->validate([
            'reset' => ['nullable', 'boolean'],
            'available' => ['nullable', 'array'],
            'available.*' => ['string', Rule::in(array_keys(HomeSectionSettings::definitions()))],
        ]);

        if ($request->boolean('reset')) {
            HomeSectionSettings::updateAvailableKeys(null);
        } else {
            HomeSectionSettings::updateAvailableKeys($validated['available'] ?? []);
        }

        return redirect()
            ->route('home-sections.edit')
            ->with('success', __('home_sections.available_saved'));
    }
}
