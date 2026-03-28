<?php

namespace App\Http\Controllers\site;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\PackageAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Actions\ingaz\UserAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\ImmediateReciptsRequest;
use App\Models\auth\Admin;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;

class ImmediateReciptsController extends Controller
{


    public function show($id, MaidAction $action, PackageAction $packageAction, UserAddressAction $userAddressAction, JobTypeAction $jobTypeAction, AdminAction $admin_action)
    {
        $data['maid'] = $action->find($id);
        $data['packages'] = $packageAction->getWhere(['nationality_id' => $data['maid']->nationality_id]);
        $data['addresses'] = $userAddressAction->getwhere(['user_id' => getGuardedUser('id')]);
        $data['type'] = $jobTypeAction->find(3);
        $data['customer_services'] = Admin::query()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('sort')
            ->get();
        return view('site.pages.immediat_recipts', $data);
    }

    public function store(ImmediateReciptsRequest $request, MaidAction $maidAction)
    {
        $data = $request->validated();
        $maid = $maidAction->find($data['maid_id']);
        $jobType = $maid->effective_job_type;
        if (!isset($jobType)) {
            return jsonError(null, __("ingaz.job_type_not_found"));
        }
        $order = RecruitmentRequest::create([
            'user_id' => auth()->user()->id,
            'maid_id' => $maid->id,
            'job_type_id' => $jobType->id,
            'customer_id' => $data['details_list']['admin_id'],
        ]);

        OrderDetail::updateOrCreate(
            ['order_id' => $order->id],
            ['admin_id' => $data['details_list']['admin_id']]
        );

        return jsonSuccess(['redirect' => route('site.last-orders.index')], __("ingaz.The immediate delivery request was successful"), 200);
    }
}
