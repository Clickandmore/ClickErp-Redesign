<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\User;
use App\Support\ExternalOrderSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApiSimplifiedOrderController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;

    public function store(Request $request)
    {
        if (!ExternalOrderSettings::usesSimplifiedOrdering()) {
            return apiResponse(false, __('api.not found'), null, 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'regex:/^5\d{8}$/'],
            'maid_id' => ['required', 'integer', 'exists:maids,id'],
            'service_type' => ['nullable', 'string', 'max:30'],
            'customer' => [
                'required',
                'integer',
                Rule::exists('admins', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('admin_type', AdminTypes::CustomerService->value)
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('status')->orWhere('status', 'active');
                        });
                }),
            ],
        ], [
            'maid_id.required' => __('ingaz.maid_id_is_required'),
            'maid_id.exists' => __('ingaz.maid_id_is_not_exists'),
            'customer.required' => __('ingaz.customer_is_required'),
            'customer.exists' => __('ingaz.customer_is_not_exists'),
        ]);

        $maid = Maid::with(['jobTypes', 'nationality'])->findOrFail($data['maid_id']);

        // الخدمة التي حجز منها العميل على الموقع الخارجي تحدد شاشة الطلب في لوحة التحكم.
        $jobTypeId = $maid->resolveJobTypeIdForService($data['service_type'] ?? null)
            ?? $maid->jobTypes->first()?->id;

        if (!$jobTypeId) {
            return apiResponse(false, __('ingaz.job_type_not_found'), null, 404);
        }

        return DB::transaction(function () use ($data, $maid, $jobTypeId) {
            $user = User::query()->where('phone', $data['phone'])->lockForUpdate()->first();

            if ($user && (int) $user->is_blocked === 1) {
                return apiResponse(false, __('web.account_is_blocked'), null, 422);
            }

            if (!$user) {
                $user = User::create([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'phone_code' => '+966',
                    'password' => Hash::make(Str::random(40)),
                    'register_from' => 'web',
                ]);
            }

            $existingOrder = RecruitmentRequest::query()
                ->where('maid_id', $maid->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    OrderEnum::New->value,
                    OrderEnum::Contracted->value,
                    OrderEnum::UnderProcess->value,
                    OrderEnum::ContractedWithoutContract->value,
                ])
                ->exists();

            if ($existingOrder) {
                return apiResponse(false, __('web.there_are_order_in_prograss'), null, 403);
            }

            $order = RecruitmentRequest::create([
                'user_id' => $user->id,
                'maid_id' => $maid->id,
                'job_type_id' => $jobTypeId,
                'customer_id' => $data['customer'],
            ]);

            OrderDetail::updateOrCreate(
                ['order_id' => $order->id],
                ['admin_id' => $data['customer']]
            );

            $customerService = Admin::findOrFail($data['customer']);
            $userPhone = formatSaudiPhone($user->phone);

            if ($userPhone) {
                $message = "عزيزي العميل، تم حجز سيرة ذاتية بنجاح. رقم الطلب {$order->order_code}.";
                $smsResult = $this->sendSMS($userPhone, $message);

                if (isset($smsResult['code']) && (string) $smsResult['code'] === '1') {
                    $this->storeMessage(
                        'ارسال رسالة نصية الى العميل',
                        $message,
                        NotificationActionEnum::SMS->value,
                        $user->id
                    );
                }
            }

            $customerPhone = formatSaudiPhone($customerService->phone);
            if ($customerPhone) {
                $this->sendSMS(
                    $customerPhone,
                    "يوجد لديكم حجز باسم {$user->name} برقم جوال {$userPhone}"
                );
            }

            return apiResponse(true, __('api.Order created successfully'), [
                'order' => $order,
                'customer' => [
                    'id' => $customerService->id,
                    'name' => $customerService->name,
                ],
                'user_created' => $user->wasRecentlyCreated,
            ], 201);
        });
    }
}
