<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Support\CvOptionsSettings;
use Illuminate\Http\Request;

class CvOptionsSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings_cv_options,admin');
    }

    public function edit()
    {
        $settings = Setting::query()->firstOrCreate([]);

        return view('admin.ingaz.cv-options-settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rental_cv_transfer_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = Setting::query()->firstOrCreate([]);

        $settings->forceFill([
            CvOptionsSettings::RENTAL_TRANSFER_COLUMN => (bool) ($validated['rental_cv_transfer_enabled'] ?? false),
        ])->save();

        CvOptionsSettings::clearCache();

        return redirect()
            ->route('settings.cv-options.edit')
            ->with('success', 'تم حفظ إعدادات السير الذاتية بنجاح.');
    }
}
