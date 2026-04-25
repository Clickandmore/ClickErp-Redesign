<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\OrderAction;
use App\Http\Actions\ingaz\OrderAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\CustomerServiceRequest;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\Order;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\ReturnRequest;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiOrderController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;
    public $postData = ['maid_id', 'job_type_id', 'payment_method'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(OrderAction $orderAction)
    {
        $current = $orderAction->getIndex('current');
        $previous = $orderAction->getIndex('previous');
        $order_progressbar = ['refused' => 0, 'new' => 20, 'paid' => 40, 'under_process' => 60, 'started' => 80, 'contracted' => 100, 'done' => 100, 'completed' => 100];

        $data = [
            'current' => $current,
            'previous' => $previous,
            'order_progressbar' => $order_progressbar,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, MaidAction $maidAction, AdminAction $adminAction)
    {
        // 1. Validation
        $request->validate([
            'maid_id' => ['required', 'exists:maids,id'],
            'customer' => [
                'nullable',
                Rule::exists('admins', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('admin_type', AdminTypes::CustomerService->value)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'active');
                        });
                }),
            ],
        ], [
            'maid_id.required' => __('ingaz.maid_id_is_required'),
            'maid_id.exists' => __('ingaz.maid_id_is_not_exists'),
            'customer.exists' => __('ingaz.customer_is_not_exists'),
        ]);

        // 2. Fetch Data
        $maid = $maidAction->find($request->maid_id);
        $jobType = $maid->jobTypes->first();

        if (!$jobType) {
            return apiResponse(false, __("ingaz.job_type_not_found"), null, 404);
        }

        // 3. Check for Duplicate Order
        $existingOrder = RecruitmentRequest::where('maid_id', $request->maid_id)
            ->where('user_id', auth()->id())
            ->whereIn('status', [
                OrderEnum::New->value,
                OrderEnum::Contracted->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::ContractedWithoutContract->value
            ])->exists(); // استخدام exists() أسرع من first()

        if ($existingOrder) {
            return apiResponse(false, __("web.there_are_order_in_prograss"), null, 403);
        }

        // 4. Start Transaction
        return DB::transaction(function () use ($request, $maid, $jobType, $adminAction) {

            $order = RecruitmentRequest::create([
                'user_id' => auth()->id(),
                'maid_id' => $maid->id,
                'job_type_id' => $jobType->id
            ]);

            $user = auth()->user();
            $userPhone = formatSaudiPhone($user->phone);

            // SMS to User
            if ($userPhone) {
                $msg = "عزيزي العميل، تم حجز سيرة ذاتية بنجاح. رقم الطلب {$order->order_code}.";
                $smsResult = $this->sendSMS($userPhone, $msg);
                if (isset($smsResult['code']) && $smsResult['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $user->id);
                }
            }

            $customerService = (object)[];

            // Assign Customer Service if provided
            if ($request->filled('customer')) {
                $customerService = $adminAction->find($request->customer);

                $order->update(['customer_id' => $customerService->id]);

                OrderDetail::updateOrCreate(
                    ['order_id' => $order->id],
                    ['admin_id' => $customerService->id]
                );

                $customerPhone = formatSaudiPhone($customerService->phone);
                if ($customerPhone) {
                    $customerMsg = "يوجد لديكم حجز باسم {$user->name} برقم جوال {$userPhone}";
                    $this->sendSMS($customerPhone, $customerMsg);
                }
            }

            // Return the created order data
            return apiResponse(true, __('api.Order created successfully'), ['order' => $order, 'customer' => $customerService], 201);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(OrderAction $orderAction, $id)
    {
        //  $data['order'] = $orderAction->find($id);
        $data['order'] = Order::with(['jobType', 'maid.nationality', 'maid.religion', 'maid.jobTitles'])->find($id);
        if ($data['order']->user_id != Auth::user()->id) {
            abort(404);
        }
        $data['order_progressbar'] = ['refused' => 0, 'new' => 20, 'paid' => 40, 'completed' => 60, 'started' => 80, 'done' => 100];
        $data['returnRequest'] = ReturnRequest::where('order_id', $id)->first();
        $data['travelRequest'] = TravelHistory::where('order_id', $id)->first();

        return apiResponse(true, "تم جلب الطلب بنجاح", $data, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjAction $action)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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

        $data = [
            'order' => $order,
            'customer' => $customer,
        ];

        return apiResponse(true, 'success', $data, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderAction $orderAction, $id)
    {
        $order = $orderAction->delete($id);

        if (!$order) {
            return apiResponse(false, 'الطلب غير موجود', [], 404);
        }

        return apiResponse(true, "تم حذف الطلب بنجاح", [], 200);
    }


    public function showRecruitmentRequest($id)
    {
        $maid_id = RecruitmentRequest::find($id)->maid_id;

        $data = [
            'maid_id' => $maid_id,
        ];

        return apiResponse(true, "تم بنجاح", $data, 200);
    }
}
