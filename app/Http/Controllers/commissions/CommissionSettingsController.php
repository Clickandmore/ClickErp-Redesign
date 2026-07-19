<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionMarketerOverride;
use App\Models\commissions\CommissionSetting;
use Illuminate\Http\Request;

class CommissionSettingsController extends Controller
{
    public function index()
    {
        $settings = CommissionSetting::all()->keyBy('service_type');
        $serviceTypeOptions = CommissionSetting::serviceTypeOptions();

        $marketers = Admin::query()
            ->where('admin_type', 'customer_service')
            ->activeForOperations()
            ->with(['commissionOverrides'])
            ->orderBy('name')
            ->get();

        return view('commissions.settings.index', compact('settings', 'serviceTypeOptions', 'marketers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'settings'                => ['nullable', 'array'],
            'settings.*.method'       => ['required_with:settings', 'in:percentage,fixed'],
            'settings.*.value'        => ['required_with:settings', 'numeric', 'min:0'],
            'overrides'               => ['nullable', 'array'],
            'overrides.*.admin_id'    => ['required_with:overrides', 'exists:admins,id'],
            'overrides.*.method'      => ['required_with:overrides', 'in:percentage,fixed'],
            'overrides.*.value'       => ['required_with:overrides', 'numeric', 'min:0'],
            'overrides.*.service_type' => ['nullable', 'string'],
        ]);

        $adminId = auth('admin')->id();

        // Save global settings per service type
        foreach ($request->input('settings', []) as $serviceType => $data) {
            CommissionSetting::updateOrCreate(
                ['service_type' => $serviceType],
                ['method' => $data['method'], 'value' => $data['value'], 'created_by' => $adminId]
            );
        }

        // Delete removed overrides, then re-save
        if ($request->has('overrides')) {
            $keptIds = collect($request->overrides)->pluck('id')->filter()->all();
            CommissionMarketerOverride::whereNotIn('id', $keptIds)->delete();

            foreach ($request->overrides as $override) {
                CommissionMarketerOverride::updateOrCreate(
                    [
                        'admin_id'     => $override['admin_id'],
                        'service_type' => $override['service_type'] ?: null,
                    ],
                    [
                        'method'     => $override['method'],
                        'value'      => $override['value'],
                        'created_by' => $adminId,
                    ]
                );
            }
        }

        return redirect()
            ->route('commissions.settings.index')
            ->with('success', 'تم حفظ إعدادات العمولات بنجاح.');
    }
}
