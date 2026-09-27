<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Support\CvOptionsSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'cv_site_sort_order' => ['required', Rule::in(CvOptionsSettings::SITE_SORT_ORDERS)],
            'rental_cv_transfer_enabled' => ['nullable', 'boolean'],
            'maid_transaction_edit_override_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = Setting::query()->firstOrCreate([]);

        $settings->forceFill([
            CvOptionsSettings::SITE_SORT_ORDER_COLUMN => $validated['cv_site_sort_order'],
            CvOptionsSettings::RENTAL_TRANSFER_COLUMN => (bool) ($validated['rental_cv_transfer_enabled'] ?? false),
            CvOptionsSettings::MAID_TRANSACTION_EDIT_OVERRIDE_COLUMN => (bool) ($validated['maid_transaction_edit_override_enabled'] ?? false),
        ])->save();

        CvOptionsSettings::clearCache();

        return redirect()
            ->route('settings.cv-options.edit')
            ->with('success', 'تم حفظ إعدادات السير الذاتية بنجاح.');
    }
}
