<?php

namespace App\Http\Controllers\site;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\SettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\CustomerServiceRequest;
use App\Models\auth\Admin;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use Illuminate\Http\Request;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use Illuminate\Validation\Rule;

class CustomerServiceController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;
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

            OrderDetail::updateOrCreate(
                ['order_id' => $order->id],
                ['admin_id' => $dataInserted['customer']]
            );
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
        $request->validate([
            'maid_id' => ['required', 'exists:maids,id'],
            'customer' => [
                'nullable',
                Rule::exists('admins', 'id')->where(function ($query) {
                    $query
                        ->whereNull('deleted_at')
                        ->where('admin_type', AdminTypes::CustomerService->value)
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('status')->orWhere('status', 'active');
                        });
                }),
            ],
        ], [
            'maid_id.required' => __('ingaz.maid_id_is_required'),
            'maid_id.exists' => __('ingaz.maid_id_is_not_exists'),
            'customer.exists' => __('ingaz.customer_is_not_exists'),
        ]);

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
            $result = $this->sendSMS($userPhone, $body);

            if (isset($result['code']) && $result['code'] == "1") {
                $this->storeMessage('ارسال رسالة نصية الى العميل', $body, NotificationActionEnum::SMS->value, auth()->user()->id);
            }
        }

        if ($request->filled('customer')) {
            $customer = $adminAction->find($request->customer);

            if ($customer) {
                $order->update([
                    'customer_id' => $customer->id,
                ]);

                OrderDetail::updateOrCreate(
                    ['order_id' => $order->id],
                    ['admin_id' => $customer->id]
                );

                $customerPhone = formatSaudiPhone($customer->phone);
                $userName = auth()->user()->name;

                if (!empty($customerPhone)) {
                    $body = "يوجد لديكم حجز باسم " . $userName . " برقم جوال " . $userPhone;
                    $this->sendSMS($customerPhone, $body);
                }
            }

            return view('site.pages.selected_customer', get_defined_vars());
        }

        $customer_services = Admin::query()
            ->whereNull('deleted_at')
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get();
        return view('site.pages.customer_sevices', get_defined_vars());
    }

    public function show($id)
    {
        $maid_id = RecruitmentRequest::find($id)->maid_id;
        return redirect(route('site.all-workers.show', $maid_id));
    }

}
