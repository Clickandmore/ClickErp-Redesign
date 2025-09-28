<?php

namespace App\Http\Controllers\site;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\CustomerServiceRequest;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use Illuminate\Http\Request;

class EagarRequestController extends Controller
{
    public function store(Request $request, MaidAction $maidAction, AdminAction $adminAction, SettingAction $settingAction)
    {
        $data = $request->all();
        $maid = $maidAction->find($request->maid_id);
        $jobType = $maid->jobTypes->first();
        if (!isset($jobType)) {
            return jsonError(null, __("ingaz.job_type_not_found"));
        }
        $order = RecruitmentRequest::create([
            'user_id' => auth()->user()->id,
            'maid_id' => $maid->id,
            'job_type_id' => $jobType->id
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'package_id' => $data['details_list']['package_id'],
            'address_id' => $data['details_list']['address_id'],
            'deliver_maid' => $data['details_list']['deliver_maid'],
        ]);
        return jsonSuccess(['redirect' => route('site.last-orders.index')], __("ingaz.The request for hiring workers was successful"), 200);
    }
}
