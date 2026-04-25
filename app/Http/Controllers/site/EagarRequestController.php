<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\OrderAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
            'details_list.package_id' => ['required', 'integer', 'exists:packages,id'],
            'details_list.address_id' => ['nullable', 'integer', 'exists:user_addresses,id'],
            'details_list.deliver_maid' => ['nullable', 'boolean'],
        ]);

        $maid = $maidAction->find($data['maid_id']);
        if (!$maid || !$maid->jobTypes()->where('job_type_id', 2)->exists()) {
            return jsonError(__("ingaz.job_type_not_found"), 422);
        }

        $package = Package::query()
            ->whereKey($data['details_list']['package_id'])
            ->where('nationality_id', $maid->nationality_id)
            ->first();

        if (!$package) {
            return jsonError(__('ingaz.No_packages_to_show'), 422);
        }

        DB::transaction(function () use ($request, $orderAction, $maid) {
            $orderAction->storeOrder([
                'maid_id' => $maid->id,
                'job_type_id' => 2,
                'payment_method' => $request->input('payment_method', 'none'),
            ], $request);
        });

        return jsonSuccess(['redirect' => route('site.last-orders.index')], __("ingaz.The request for hiring workers was successful"), 200);
    }
}
