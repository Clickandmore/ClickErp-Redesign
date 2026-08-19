<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\OrderAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EagarRequestController extends Controller
{
    public function store(Request $request, MaidAction $maidAction, AdminAction $adminAction, SettingAction $settingAction, OrderAction $orderAction)
    {
        $request->merge([
            'details_list' => array_merge($request->input('details_list', []), [
                'address_id' => in_array($request->input('details_list.address_id'), ['skip', 'manual', ''], true)
                    ? null
                    : $request->input('details_list.address_id'),
                'deliver_maid' => $request->boolean('details_list.deliver_maid') ? 1 : 0,
            ]),
        ]);

        $data = $request->validate([
            'maid_id' => ['required', 'integer', 'exists:maids,id'],
            'payment_method' => ['nullable', 'string'],
            'details_list' => ['required', 'array'],
            'details_list.package_id' => ['required', 'integer', 'exists:packages,id'],
            'details_list.address_id' => [
                'nullable',
                'integer',
                Rule::exists('user_addresses', 'id')->where(fn($query) => $query->where('user_id', auth()->id())),
            ],
            'details_list.deliver_maid' => ['nullable', 'boolean'],
        ]);

        $maid = $maidAction->find($data['maid_id']);
        if (!$maid || !$maid->jobTypes()->where('job_type_id', 2)->exists()) {
            return apiResponse(false, __("ingaz.job_type_not_found"), null, 422);
        }

        $package = Package::query()
            ->visible()
            ->forNationality($maid->nationality_id)
            ->whereKey($data['details_list']['package_id'])
            ->first();

        if (!$package) {
            return apiResponse(false, __('ingaz.No_packages_to_show'), null, 422);
        }

        $order = DB::transaction(function () use ($request, $orderAction, $maid) {
            return $orderAction->storeOrder([
                'maid_id' => $maid->id,
                'job_type_id' => 2,
                // 'none' كانت تُقرأ في شاشة طلبات الإيجار على أنها "تم الدفع"،
                // والطلب القادم من الموقع الخارجي غير مدفوع.
                'payment_method' => 'cash',
            ], $request);
        });

        return apiResponse(true, __("ingaz.The request for hiring workers was successful"), [
            'order' => $order->loadMissing(['details.package', 'details.address', 'maid.nationality', 'jobType']),
        ], 201);
    }
}
