<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\OrderAction;
use App\Http\Actions\ingaz\OrderAction as ObjAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\CustomerServiceRequest;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\Order;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\ReturnRequest;
use App\Models\TravelHistory;
use App\Services\ExternalOrderAdminSmsNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiOrderController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;

    public $postData = ['maid_id', 'job_type_id', 'payment_method'];

    public function index(OrderAction $orderAction)
    {
        $current = $orderAction->getIndex('current');
        $previous = $orderAction->getIndex('previous');
        $order_progressbar = [
            'refused' => 0,
            'new' => 20,
            'paid' => 40,
            'under_process' => 60,
            'started' => 80,
            'contracted' => 100,
            'done' => 100,
            'completed' => 100,
        ];

        return apiResponse(true, __('api.All items'), [
            'current' => $current,
            'previous' => $previous,
            'order_progressbar' => $order_progressbar,
        ], 200);
    }

    public function tracking(Request $request)
    {
        $orderCode = trim((string) $request->query('order_code', ''));
        $normalizedOrderCode = preg_replace('/\D+/', '', $orderCode);

        $orders = Order::with([
            'jobType',
            'maid.nationality',
            'details.admin',
            'customer',
            'user',
            'transfers',
        ])
            ->where('user_id', Auth::id())
            ->when($orderCode !== '', function ($query) use ($orderCode, $normalizedOrderCode) {
                $query->where(function ($orderQuery) use ($orderCode, $normalizedOrderCode) {
                    $orderQuery->where('order_code', $orderCode);

                    if ($normalizedOrderCode !== '' && $normalizedOrderCode !== $orderCode) {
                        $orderQuery->orWhere('order_code', $normalizedOrderCode);
                    }
                });
            })
            ->latest()
            ->get();

        $items = $orders->map(fn(Order $order) => $this->transformTrackingOrder($order))->values();

        return apiResponse(true, __('api.All items'), [
            'orders' => $items,
            'summary' => [
                'total' => $items->count(),
                'active' => $items->whereIn('status', ['new', 'in_progress', 'ready'])->count(),
                'completed' => $items->where('status', 'completed')->count(),
                'cancelled' => $items->where('status', 'cancelled')->count(),
            ],
        ], 200);
    }

    public function create(Request $request)
    {
        //
    }

    public function store(Request $request, MaidAction $maidAction, AdminAction $adminAction, ExternalOrderAdminSmsNotifier $adminSmsNotifier)
    {
        // Website clients identify their channel; existing app requests keep their behavior.
        if ($request->input('booking_channel') === 'external_website'
            && !\App\Support\ExternalOrderSettings::employeeSelectionEnabled()) {
            $request->merge(['customer' => null]);
        }
        $request->validate([
            'maid_id' => ['required', 'exists:maids,id'],
            'service_type' => ['nullable', 'string', 'max:30'],
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

        $maid = $maidAction->find($request->maid_id);

        // الموقع الخارجي يرسل الخدمة التي حجز منها العميل (استقدام/إيجار/نقل خدمات)
        // ليصل الطلب إلى شاشته الصحيحة في لوحة التحكم. غيابها يبقي السلوك القديم.
        $jobTypeId = $maid->resolveJobTypeIdForService($request->input('service_type'))
            ?? $maid->jobTypes->first()?->id;

        if (!$jobTypeId) {
            return apiResponse(false, __("ingaz.job_type_not_found"), null, 404);
        }

        return DB::transaction(function () use ($request, $maid, $jobTypeId, $adminAction, $adminSmsNotifier) {
            if (app(\App\Services\ExternalBookingAvailabilityService::class)->isReserved((int) $maid->id)) {
                return apiResponse(false, __('web.there_are_order_in_prograss'), null, 403);
            }

            $order = RecruitmentRequest::create([
                'user_id' => auth()->id(),
                'maid_id' => $maid->id,
                'job_type_id' => $jobTypeId,
            ]);

            $user = auth()->user();
            $userPhone = formatSaudiPhone($user->phone);

            if ($userPhone) {
                $msg = "عزيزي العميل، تم حجز سيرة ذاتية بنجاح. رقم الطلب {$order->order_code}.";
                $smsResult = $this->sendSMS($userPhone, $msg);

                if (isset($smsResult['code']) && $smsResult['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $user->id);
                }
            }

            $customerService = null;

            if ($request->filled('customer')) {
                $customerService = $adminAction->find($request->customer);

                $order->update(['customer_id' => $customerService->id]);

                OrderDetail::updateOrCreate(
                    ['order_id' => $order->id],
                    ['admin_id' => $customerService->id]
                );

            }

            DB::afterCommit(fn () => $adminSmsNotifier->send($order, $user, $maid, $customerService));

            return apiResponse(true, __('api.Order created successfully'), [
                'order' => $order,
                'customer' => $customerService,
            ], 201);
        });
    }

    public function show(OrderAction $orderAction, $id)
    {
        $data['order'] = Order::with([
            'jobType',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'maid.expertises.country',
            'maid.expertises.jobTitle',
        ])->find($id);

        if ($data['order']->user_id != Auth::user()->id) {
            abort(404);
        }

        $data['order_progressbar'] = [
            'refused' => 0,
            'new' => 20,
            'paid' => 40,
            'completed' => 60,
            'started' => 80,
            'done' => 100,
        ];
        $data['returnRequest'] = ReturnRequest::where('order_id', $id)->first();
        $data['travelRequest'] = TravelHistory::where('order_id', $id)->first();

        return apiResponse(true, "تم جلب الطلب بنجاح", $data, 200);
    }

    public function edit($id, Request $request, ObjAction $action)
    {
        //
    }

    public function update($id, CustomerServiceRequest $request, AdminAction $adminAction)
    {
        $dataInserted = $request->validated();
        $order = RecruitmentRequest::find($id);

        if (isset($order)) {
            $order->update([
                'customer_id' => $dataInserted['customer'],
            ]);

            OrderDetail::updateOrCreate(
                ['order_id' => $order->id],
                ['admin_id' => $dataInserted['customer']]
            );
        }

        $userPhone = formatSaudiPhone(auth()->user()->phone);
        $userName = auth()->user()->name;
        $customerPhone = formatSaudiPhone($adminAction->find($dataInserted['customer'])->phone);

        if (!empty($customerPhone)) {
            $body = "يوجد لديكم حجز باسم " . $userName . " برقم جوال " . $userPhone;
            $this->sendSMS($customerPhone, $body);
        }

        $customer = $adminAction->find($dataInserted['customer']);

        return apiResponse(true, 'success', [
            'order' => $order,
            'customer' => $customer,
        ], 200);
    }

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

        return apiResponse(true, "تم بنجاح", [
            'maid_id' => $maid_id,
        ], 200);
    }

    private function transformTrackingOrder(Order $order): array
    {
        $statusMeta = $this->resolveFrontendStatusMeta($order->status);
        $activities = $this->collectOrderActivities($order);
        $updates = $this->buildTrackingUpdates($activities);
        $steps = $this->buildTrackingSteps($order, $statusMeta['progress']);
        $timeline = collect($steps)
            ->map(fn(array $step) => [
                'label' => $step['label'],
                'done' => $step['done'],
            ])
            ->values()
            ->all();

        $lastUpdateDate = collect($updates)->pluck('date')->filter()->first();
        $consultant = $order->details?->admin?->name ?? $order->customer?->name ?? null;
        $workerName = $order->maid?->name ?: ('#' . $order->maid_id);
        $country = $order->maid?->nationality?->title ?? $order->maid?->nationality?->sub_title ?? null;
        $expectedDate = optional($order->deadline_at)->toDateString()
            ?? optional($order->created_at)->copy()?->addDays(45)?->toDateString();
        $nextStep = collect($steps)->firstWhere('current', true)['label'] ?? null;

        return [
            'id' => $this->formatOrderCode($order->order_code),
            'order_code' => (string) $order->order_code,
            'workerName' => $workerName,
            'country' => $country ?: '-',
            'city' => '-',
            'service' => $order->jobType?->title ?? __('api.Order'),
            'sponsor' => $order->user?->name ?? '-',
            'consultant' => $consultant ?: '-',
            'phoneLast4' => substr((string) preg_replace('/\D+/', '', (string) ($order->user?->phone ?? '')), -4),
            'createdAt' => optional($order->created_at)->toDateString(),
            'expectedDate' => $expectedDate,
            'status' => $statusMeta['key'],
            'statusLabel' => $this->resolveStatusLabel($order->status, $statusMeta['label']),
            'progress' => $statusMeta['progress'],
            'lastUpdateAt' => $lastUpdateDate ?: optional($order->updated_at)->toDateString(),
            'nextStep' => $nextStep ?: $statusMeta['label'],
            'timeline' => $timeline,
            'steps' => $steps,
            'updates' => $updates,
        ];
    }

    private function resolveFrontendStatusMeta(?string $status): array
    {
        return match ($status) {
            'completed', 'done', 'delivered' => [
                'key' => 'completed',
                'label' => 'مكتمل',
                'progress' => 100,
            ],
            'contracted', 'contracted_without_contract', 'arrived', 'msaned', 'training', 'started' => [
                'key' => 'ready',
                'label' => 'جاهز للوصول',
                'progress' => 90,
            ],
            'under_process', 'paid' => [
                'key' => 'in_progress',
                'label' => 'قيد الإجراء',
                'progress' => 60,
            ],
            'refused', 'return', 'canceled_by_system' => [
                'key' => 'cancelled',
                'label' => 'ملغي',
                'progress' => 0,
            ],
            default => [
                'key' => 'new',
                'label' => 'جديد',
                'progress' => 20,
            ],
        };
    }

    private function resolveStatusLabel(?string $status, string $fallback): string
    {
        if ($status && function_exists('getEnumValue')) {
            return (string) (getEnumValue('orders_statuses', $status) ?: $fallback);
        }

        return $fallback;
    }

    private function formatOrderCode(?string $orderCode): string
    {
        $value = trim((string) $orderCode);

        return $value === '' ? '-' : 'AQ-' . $value;
    }

    private function collectOrderActivities(Order $order): Collection
    {
        $activities = collect();

        if (method_exists($order, 'activities')) {
            $activities = $activities->merge($order->activities()->with('causer')->latest()->get());
        }

        if ($order->details && method_exists($order->details, 'activities')) {
            $activities = $activities->merge($order->details->activities()->with('causer')->latest()->get());
        }

        foreach ($order->transfers as $transfer) {
            if (method_exists($transfer, 'activities')) {
                $activities = $activities->merge($transfer->activities()->with('causer')->latest()->get());
            }
        }

        return $activities
            ->filter()
            ->sortByDesc('created_at')
            ->values();
    }

    private function buildTrackingUpdates(Collection $activities): array
    {
        $fieldLabels = $this->trackingFieldLabels();

        $updates = $activities
            ->take(8)
            ->map(function ($activity) use ($fieldLabels) {
                $event = strtolower((string) ($activity->event ?: $activity->description ?: ''));
                $subjectLabel = $this->resolveActivitySubjectLabel($activity, 'الطلب');
                $actionLabel = $this->resolveActivityActionLabel($event);
                $description = $this->resolveActivityDescription($activity, $event);
                $changedFields = $this->extractActivityChangedFields($activity, $fieldLabels);
                $note = $description ?: (empty($changedFields) ? null : implode('، ', $changedFields));

                return [
                    'title' => trim($actionLabel . ' ' . $subjectLabel),
                    'date' => optional($activity->created_at)->toDateString(),
                    'note' => $note ?: 'تم تحديث بيانات الطلب.',
                ];
            })
            ->values()
            ->all();

        if (!empty($updates)) {
            return $updates;
        }

        return [[
            'title' => 'إنشاء الطلب',
            'date' => now()->toDateString(),
            'note' => 'تم استلام الطلب وتسجيله بنجاح.',
        ]];
    }

    private function buildTrackingSteps(Order $order, int $progress): array
    {
        $currentStepIndex = $this->resolveCurrentStepIndex($order->status);
        $isCompleted = $progress >= 100;
        $isCancelled = $progress === 0 && in_array($order->status, ['refused', 'return', 'canceled_by_system'], true);

        $steps = [
            [
                'label' => 'إنشاء الطلب',
                'note' => 'تم استلام الطلب وتسجيله بنجاح.',
            ],
            [
                'label' => 'مراجعة البيانات',
                'note' => 'يتم التحقق من بيانات الطلب والعاملة.',
            ],
            [
                'label' => 'قيد الإجراء',
                'note' => 'الطلب ضمن المعالجة لدى الفريق المختص.',
            ],
            [
                'label' => 'جاهز للوصول',
                'note' => 'تم استكمال المراحل الأساسية والطلب جاهز للخطوة النهائية.',
            ],
            [
                'label' => 'اكتمال الطلب',
                'note' => 'تم إنهاء الطلب بنجاح.',
            ],
        ];

        return collect($steps)
            ->map(function (array $step, int $index) use ($currentStepIndex, $isCompleted, $isCancelled, $order) {
                $done = $isCompleted || (!$isCancelled && $currentStepIndex !== null && $index < $currentStepIndex);
                $current = !$isCompleted && !$isCancelled && $currentStepIndex !== null && $index === $currentStepIndex;

                if ($index === 0) {
                    $done = true;
                }

                return [
                    'label' => $step['label'],
                    'note' => $step['note'],
                    'date' => $done ? optional($order->created_at)->toDateString() : null,
                    'done' => $done,
                    'current' => $current,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveCurrentStepIndex(?string $status): ?int
    {
        return match ($status) {
            'new', 'paid' => 1,
            'under_process' => 2,
            'contracted', 'contracted_without_contract', 'arrived', 'msaned', 'training', 'started' => 3,
            'completed', 'done', 'delivered' => 4,
            'refused', 'return', 'canceled_by_system' => null,
            default => 1,
        };
    }

    private function trackingFieldLabels(): array
    {
        return [
            'status' => 'حالة الطلب',
            'maid_id' => 'العاملة',
            'customer_id' => 'الموظف المسؤول',
            'user_id' => 'العميل',
            'job_type_id' => 'نوع الخدمة',
            'payment_method' => 'طريقة الدفع',
            'refuse_reason' => 'سبب الإلغاء',
            'status_changed_at' => 'وقت تغيير الحالة',
            'deadline_at' => 'الموعد النهائي',
            'admin_id' => 'الموظف المسؤول',
            'package_id' => 'الباقة',
            'address_id' => 'العنوان',
            'deliver_maid' => 'تسليم العاملة',
            'name' => 'الاسم',
            'phone' => 'رقم الهاتف',
            'address' => 'العنوان',
            'notes' => 'الملاحظات',
            'file' => 'الملف',
        ];
    }
}
