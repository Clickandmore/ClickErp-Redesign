<?php

namespace App\Http\Controllers\site;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\CustomerServiceRequest;
use App\Models\ingaz\RecruitmentRequest;
use Illuminate\Http\Request;

class CustomerServiceController extends Controller
{
    public function update($id, CustomerServiceRequest $request, AdminAction $adminAction)
    {
        $dataInserted = $request->validated();
        $order = RecruitmentRequest::find($id);
        if (isset($order)) {
            $order->update([
                'customer_id' => $dataInserted['customer']
            ]);
        }
        $customer = $adminAction->find($dataInserted['customer']);
        return view('site.pages.selected_customer', get_defined_vars());
    }

    public function store(Request $request, MaidAction $maidAction, AdminAction $adminAction, SettingAction $settingAction)
    {
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
        $customer_services = $adminAction->getWhere(['admin_type' => AdminTypes::CustomerService->value]);
        return view('site.pages.customer_sevices', get_defined_vars());
    }
}
