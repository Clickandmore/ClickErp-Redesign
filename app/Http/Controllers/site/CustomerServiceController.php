<?php

namespace App\Http\Controllers\site;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\CustomerServiceRequest;
use App\Models\ingaz\RecruitmentRequest;
use Illuminate\Http\Request;
use App\Http\Traits\SendSMSTrait;

class CustomerServiceController extends Controller
{
    use SendSMSTrait;
    public function index()
    {
        return redirect(route('site.all-workers.index'));
    }

    public function update($id, CustomerServiceRequest $request, AdminAction $adminAction)
    {
        $dataInserted = $request->validated();
        $order = RecruitmentRequest::find($id);
        if (isset($order)) {
            $order->update([
                'customer_id' => $dataInserted['customer']
            ]);
        }

        // Send SMS after assigning customer service
        $userPhone = formatSaudiPhone(auth()->user()->phone);
        $userName = auth()->user()->name;
        $customerPhone = formatSaudiPhone($adminAction->find($dataInserted['customer'])->phone);
        if (!empty($customerPhone)) {
            $body =  "يوجد لديكم حجز باسم " . $userName . "برقم جوال " . $userPhone;
            $this->sendSMS($customerPhone, $body);
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
        $sameOrder = RecruitmentRequest::where('maid_id', $request->maid_id)
            ->where('user_id', auth()->user()->id)
            ->whereIn('status', [OrderEnum::New->value, OrderEnum::Contracted->value, OrderEnum::UnderProcess->value, OrderEnum::ContractedWithoutContract->value])
            ->first();
        if ($sameOrder) {
            return back()->with('error', __("web.there_are_order_in_prograss"));
        }
        $order = RecruitmentRequest::create([
            'user_id' => auth()->user()->id,
            'maid_id' => $maid->id,
            'job_type_id' => $jobType->id
        ]);

        // Send SMS to user after creating order and before assigning customer service
        $userPhone = formatSaudiPhone(auth()->user()->phone);
        if (!empty($userPhone)) {
            $body = "عزيزي العميل، تم حجز سيرة ذاتية جديدة بنجاح. رقم الطلب {$order->order_code}. للمتابعة يرجى التواصل مع المندوب المسؤول عن طلبكم.";
            $this->sendSMS($userPhone, $body);
        }

        $customer_services = $adminAction->getWhere(['admin_type' => AdminTypes::CustomerService->value]);
        return view('site.pages.customer_sevices', get_defined_vars());
    }

    public function show($id)
    {
        $maid_id = RecruitmentRequest::find($id)->maid_id;
        return redirect(route('site.all-workers.show', $maid_id));
    }

}
