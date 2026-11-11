<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Support\CvOptionsSettings;
use App\Support\MaidFormFieldSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        $maidFormFields = MaidFormFieldSettings::states();

        return view('admin.ingaz.cv-options-settings.edit', compact('settings', 'maidFormFields'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'cv_site_sort_order' => ['required', Rule::in(CvOptionsSettings::SITE_SORT_ORDERS)],
            'rental_cv_transfer_enabled' => ['nullable', 'boolean'],
            'maid_transaction_edit_override_enabled' => ['nullable', 'boolean'],
            'cv_display_source' => ['nullable', Rule::in(CvOptionsSettings::DISPLAY_SOURCES)],
            'maid_form_fields' => ['nullable', 'array'],
            'maid_form_fields.*' => ['nullable', 'string', Rule::in([
                MaidFormFieldSettings::DEFAULT,
                MaidFormFieldSettings::OPTIONAL,
                MaidFormFieldSettings::HIDDEN,
                MaidFormFieldSettings::REQUIRED,
            ])],
        ]);

        $settings = Setting::query()->firstOrCreate([]);

        $values = [
            CvOptionsSettings::SITE_SORT_ORDER_COLUMN => $validated['cv_site_sort_order'],
            CvOptionsSettings::RENTAL_TRANSFER_COLUMN => (bool) ($validated['rental_cv_transfer_enabled'] ?? false),
            CvOptionsSettings::MAID_TRANSACTION_EDIT_OVERRIDE_COLUMN => (bool) ($validated['maid_transaction_edit_override_enabled'] ?? false),
        ];

        if (Schema::hasColumn('settings', CvOptionsSettings::DISPLAY_SOURCE_COLUMN)) {
            $values[CvOptionsSettings::DISPLAY_SOURCE_COLUMN] = $validated['cv_display_source'] ?? CvOptionsSettings::DISPLAY_GENERATED;
        }

        if (Schema::hasColumn('settings', MaidFormFieldSettings::COLUMN)) {
            $values[MaidFormFieldSettings::COLUMN] = MaidFormFieldSettings::encode($validated['maid_form_fields'] ?? []);
        }

        $settings->forceFill($values)->save();

        CvOptionsSettings::clearCache();
        MaidFormFieldSettings::clearCache();

        return redirect()
            ->route('settings.cv-options.edit')
            ->with('success', 'تم حفظ إعدادات السير الذاتية بنجاح.');
    }
}
