<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionMarketerOverride;
use App\Models\commissions\CommissionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;

class CommissionSettingsController extends Controller
{
    public function index()
    {
        $settings = CommissionSetting::all()->keyBy('service_type');
        $serviceTypeOptions = CommissionSetting::serviceTypeOptions();

        $marketers = Admin::query()
            ->where('admin_type', 'customer_service')
            ->activeForOperations()
            ->with(['commissionOverrides', 'commissionPayableAccount'])
            ->orderBy('name')
            ->get();

        $marketerPayableAccounts = Account::query()
            ->where('status', 'creditor')
            ->whereDoesntHave('children')
            ->whereHas('parent', function ($query) {
                $query->where('code', 144)->orWhere('name', 'المسوقين');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('commissions.settings.index', compact(
            'settings',
            'serviceTypeOptions',
            'marketers',
            'marketerPayableAccounts'
        ));
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
            'payable_accounts'        => ['nullable', 'array'],
            'payable_accounts.*'      => ['nullable', 'integer', 'distinct', 'exists:fn_accounts,id'],
        ]);

        $adminId = auth('admin')->id();

        if ($request->has('payable_accounts')) {
            $payableAccounts = collect($request->input('payable_accounts', []))
                ->map(fn ($accountId) => $accountId ? (int) $accountId : null);
            $selectedAccountIds = $payableAccounts->filter()->unique()->values();
            $validAccountIds = Account::query()
                ->whereIn('id', $selectedAccountIds)
                ->where('status', 'creditor')
                ->whereDoesntHave('children')
                ->whereHas('parent', function ($query) {
                    $query->where('code', 144)->orWhere('name', 'المسوقين');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($selectedAccountIds->diff($validAccountIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'payable_accounts' => 'يجب اختيار حساب دائن نهائي تابع لحساب المسوقين رقم 144.',
                ]);
            }

            $editableMarketers = Admin::query()
                ->where('admin_type', 'customer_service')
                ->activeForOperations()
                ->whereIn('id', $payableAccounts->keys()->map(fn ($id) => (int) $id))
                ->get(['id']);

            DB::transaction(function () use ($editableMarketers, $payableAccounts) {
                $editableMarketerIds = $editableMarketers->pluck('id');
                Admin::query()
                    ->whereIn('id', $editableMarketerIds)
                    ->update(['commission_payable_account_id' => null]);

                foreach ($editableMarketers as $marketer) {
                    $marketer->update([
                        'commission_payable_account_id' => $payableAccounts->get((string) $marketer->id)
                            ?? $payableAccounts->get($marketer->id),
                    ]);
                }
            });
        }

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
