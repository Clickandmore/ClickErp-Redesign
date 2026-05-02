<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Contact;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\UserFirebaseToken;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppOrderController extends Controller
{
    public function orders(Request $request)
    {
        $status = (string) $request->query('status', 'current');

        $query = Order::with([
            'maid.jobTypes',
            'maid.jobTitles',
            'maid.nationality',
            'maid.religion',
            'maid.recruitmentOffice',
            'maid.attachments',
            'user',
            'customer',
            'details.admin',
        ])->where('user_id', auth('api')->id());

        if ($status === 'current') {
            $query->whereIn('status', [
                OrderEnum::New->value,
                OrderEnum::Completed->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value,
            ]);
        } elseif ($status === 'previous') {
            $query->whereIn('status', [
                OrderEnum::Refused->value,
                OrderEnum::Done->value,
                OrderEnum::Return->value,
            ]);
        }

        $orders = $query->latest()->get()
            ->map(fn (Order $order) => $this->transformLegacyOrder($order))
            ->values();

        return jsonSuccess($orders, __('api.All items'));
    }

    public function orderDetails(int $id)
    {
        $order = Order::with([
            'maid.jobTypes',
            'maid.jobTitles',
            'maid.nationality',
            'maid.religion',
            'maid.recruitmentOffice',
            'maid.attachments',
            'user',
            'customer',
            'details.admin',
        ])
            ->where('user_id', auth('api')->id())
            ->findOrFail($id);

        return jsonSuccess($this->transformLegacyOrder($order), __('auth.done successfully'));
    }

    public function createSpecialRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'details' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return jsonValid($validator->errors(), $validator->errors()->first());
        }

        $contact = Contact::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'subject' => 'special-request',
            'message' => $request->details,
        ]);

        return jsonSuccess([
            'id' => (int) $contact->id,
            'name' => (string) $contact->name,
            'phone' => (string) $contact->phone,
            'email' => (string) $contact->email,
            'details' => (string) $contact->message,
            'created_at' => optional($contact->created_at)->format('Y-m-d H:i:s'),
        ], 'تم إرسال طلبك الخاص بنجاح');
    }

    public function createOrder(int $id)
    {
        $maid = Maid::with('jobTypes')->findOrFail($id);
        $jobType = $maid->jobTypes->first();

        if (!$jobType) {
            return response()->json([
                'success' => false,
                'msg' => 'لم يتم العثور على نوع الخدمة لهذه العاملة',
            ], 422);
        }

        $existingOrder = RecruitmentRequest::where('maid_id', $maid->id)
            ->where('user_id', auth('api')->id())
            ->whereIn('status', [
                OrderEnum::New->value,
                OrderEnum::Completed->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value,
            ])
            ->exists();

        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'msg' => 'يوجد طلب قائم لهذه العاملة بالفعل',
            ], 422);
        }

        RecruitmentRequest::create([
            'user_id' => auth('api')->id(),
            'maid_id' => $maid->id,
            'job_type_id' => $jobType->id,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'تم تنفيذ طلبك بنجاح',
        ], 200);
    }

    private function transformLegacyOrder(Order $order): array
    {
        $admin = $order->details?->admin ?? $order->customer;
        $travel = TravelHistory::where('order_id', $order->id)->latest('id')->first();
        $user = $order->user;
        $firebaseToken = $user
            ? UserFirebaseToken::where('user_id', $user->id)->latest('id')->value('phone_token')
            : null;

        return [
            'id' => (int) $order->id,
            'images' => asset('public'),
            'status' => $this->orderStatusLabel($order->status),
            'stage' => $this->orderStatusLabel($order->status),
            'maid_id' => (int) ($order->maid_id ?? 0),
            'maid' => $order->maid ? app(AppHomeController::class)->formatLegacyMaid($order->maid) : null,
            'passport' => (string) ($order->maid?->passport_num ?? ''),
            'nationality' => (string) ($order->maid?->nationality?->title ?? ''),
            'occupation' => (string) ($order->maid?->jobTitles->first()?->title ?? ''),
            'arrived_at' => $this->resolveArrivalDate($travel),
            'adminstaff' => (string) ($admin?->name ?? ''),
            'adminstaff_user' => (string) ($admin?->name ?? ''),
            'admin_whats_link' => $this->resolveWhatsappLink($admin?->whatsapp_phone ?? $admin?->phone),
            'phone' => (string) ($admin?->phone ?? ''),
            'user_id' => (int) $order->user_id,
            'user' => [
                'id' => (int) ($user?->id ?? 0),
                'name' => (string) ($user?->name ?? ''),
                'verification_code' => (string) ($user?->verification_code ?? ''),
                'phone' => (string) ($user?->phone ?? ''),
                'is_blocked' => $user?->is_blocked === null ? '' : (string) $user->is_blocked,
                'token' => '',
                'firebase_token' => $firebaseToken,
            ],
            'created_at' => optional($order->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function orderStatusLabel(?string $status): string
    {
        if ($status && function_exists('getEnumValue')) {
            $label = getEnumValue('orders_statuses', $status);
            if ($label && $label !== 'undefined') {
                return (string) $label;
            }
        }

        return (string) ($status ?? '');
    }

    private function resolveArrivalDate($travel): string
    {
        if (!$travel) {
            return 'غير محدد';
        }

        if (!empty($travel->arrived_at)) {
            return (string) $travel->arrived_at;
        }

        if (!empty($travel->arrival_date)) {
            return (string) $travel->arrival_date;
        }

        return 'غير محدد';
    }

    private function resolveWhatsappLink(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? 'https://wa.me/' . $digits : '';
    }
}
