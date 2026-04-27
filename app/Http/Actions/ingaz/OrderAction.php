<?php

namespace App\Http\Actions\ingaz;

use Carbon\Carbon;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Http\Traits\Firebase;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\MainAction;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\Notification;
use App\Support\AutomationSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Traits\StoreMessageTrait;

class OrderAction extends MainAction
{
    use Firebase, SendSMSTrait, StoreMessageTrait;
    public function __construct(Order $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Order/';
    }

    public function getIndex($status)
    {
        $acceptedStatuses = [];
        if ($status == 'current') {
            $acceptedStatuses = [
                OrderEnum::New->value,
                OrderEnum::Completed->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value
            ];
        } elseif ($status == 'previous') {
            $acceptedStatuses = [OrderEnum::Refused->value, OrderEnum::Done->value, OrderEnum::Return->value];
        }

        $userId = Auth::user() ? Auth::user()->id : (getGuardedUser() ? getGuardedUser()->id : null);

        if (!$userId) {
            return collect([]);
        }

        $data = $this->model->where('user_id', $userId)
            ->whereIn('status', $acceptedStatuses)
            ->with(['user', 'maid.jobTitles','maid.nationality',  'jobType', 'details.admin', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();
        return $data;
    }

    public function storeOrder($data, $request)
    {
        $data['user_id'] = Auth::user()->id;
        $data['order_code'] = $this->model::generateUniqueCode();

        $order = $this->store($data);

        // استخدام مدة الحجز من الإعدادات
        $expirationHours = AutomationSettings::orderExpirationHours();
        $order->setExpirationTime($expirationHours);

        if ($request->details_list) {
            $this->StoreOrderDetails($request, $order);
        }
        $this->StoreNotification($order);
        $order = $this->model->find($order->id);

        try{
            $adminPhone = $order?->admin?->formatted_verification_phone;

            if($order && $adminPhone){
                $orderId = $order->order_code;

                if($order->job_type_id == 1){
                    $msg = "يوجد طلب إستقدام جديد رقم $orderId";
                } elseif($order->job_type_id == 2){
                    $msg = "يوجد طلب إيجار عمالة جديد رقم $orderId";
                } elseif($order->job_type_id == 3){
                    $msg = "يوجد طلب نقل كفالة جديد رقم $orderId";
                }

                $result = $this->sendSMS($adminPhone, $msg);

                if (isset($result['code']) && $result['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $data['user_id']);
                }
            }
        }catch(\Exception $ex){
            //msg not sent
        }

        return $order;
    }

    public function StoreOrderDetails($request, $order)
    {
//        foreach ($request->details_list as $key => $details_list) {

        // معالجة address_id للقيم الخاصة
        $addressId = $request->details_list['address_id'] ?? null;
        if ($addressId === 'skip' || $addressId === 'manual' || $addressId === '') {
            $addressId = null; // تعيين null للقيم الخاصة
        }

        $dataProduct = [
            'admin_id' => $request->details_list['admin_id'] ?? null,
            'package_id' => $request->details_list['package_id'] ?? null,
            'address_id' => $addressId,
            'deliver_maid' => $request->details_list['deliver_maid'] ?? '0',
            'order_id' => $order->id,
        ];
        OrderDetail::updateOrCreate(
            ['order_id' => $order->id],
            $dataProduct
        );

        if (!empty($dataProduct['admin_id']) && (int) $order->customer_id !== (int) $dataProduct['admin_id']) {
            $order->forceFill([
                'customer_id' => $dataProduct['admin_id'],
            ])->save();
        }
//        }
    }

    public function StoreNotification($order, $type = null)
    {
        $notification = Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            "type" => ($type != null) ? $type : NotificationActionEnum::NewOrder->value,
            'title' => [
                'en' => documentSetting('website_name', config('app.name'), 'en'),
                'ar' => documentSetting('website_name', config('app.name'), 'ar'),
            ],
            'description' => [
                'en' => __('notifications.There is a new order', [], 'en') . $order->id,
                'ar' => __('notifications.There is a new order', [], 'ar') . $order->id,
            ],
        ]);
        $data = [
            "type" => $notification->type,
            "order_id" => $notification->order_id,
        ];
        $fire = $this->sendFCMNotification($notification->title, $notification->description, $notification->user_id, $data);
    }

    public function updateOrderStatus($id, $data)
    {
        $order = $this->find($id);
        $maid = Maid::find($order->maid_id);

        // تحديث التايمر عند تغيير الحالة
        $order->updateStatusChangedAt();

        if ($data['status'] == 'completed') {

            if ($order->package) {
//            if(now() < $maid->available_at){
//                return jsonValid('','asd',422);
//            }
                $maid->available = 0;
                $maid->available_at = Carbon::now()->addDays($order->package->days_count);
            }

            //** NEW ADDED D18M7Y24 => IS */
            // توجيه كل نوع طلب إلى الصفحة المناسبة
            if ($order->job_type_id == 1) {
                // إنشاء عقد استقدام
                $checkContract = Contract::where('order_id', $order->id)->exists();
                if(!$checkContract){
                    $contractData['order_id'] = $order->id;
                    $contractData['user_id'] = $order->user_id;
                    $contractData['maid_id'] = $order->maid_id;
                    $contractData['status'] = 'sari';
                    $contract = Contract::create($contractData);
                }
            } elseif ($order->job_type_id == 2) {
                // توجيه إلى صفحة إنشاء عقد إيجار
                // لا يتم إنشاء عقد هنا - العميل يذهب إلى RentContract/create
                \Log::info('Rental order completed - redirect to RentContract/create', [
                    'order_id' => $order->id,
                    'maid_id' => $order->maid_id,
                    'user_id' => $order->user_id
                ]);
            } elseif ($order->job_type_id == 3) {
                // توجيه إلى صفحة إنشاء طلب نقل كفالة
                // لا يتم إنشاء عقد هنا - العميل يذهب إلى SponsorshipTransfer/create
                \Log::info('Instant receipt order completed - redirect to SponsorshipTransfer/create', [
                    'order_id' => $order->id,
                    'maid_id' => $order->maid_id,
                    'user_id' => $order->user_id
                ]);
            }
            //** END */
        }

        if ($data['status'] == 'done') {
            $maid->available = 1;
            $maid->available_at = Carbon::now();
        }
        $notification = Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            "type" => NotificationActionEnum::OrderStatus->value,
            'title' => [
                'en' => documentSetting('website_name', config('app.name'), 'en'),
                'ar' => documentSetting('website_name', config('app.name'), 'ar'),
            ],
            'description' => [
                'en' => __('notifications.update_order_status', [], 'en') . $order->id,
                'ar' => __('notifications.update_order_status', [], 'ar') . $order->id,
            ],
        ]);
        $send = [
            "type" => $notification->type,
            "order_id" => $notification->order_id,
        ];
        $this->sendFCMNotification($notification->title, $notification->description, $notification->user_id, $send);
        $maid->save();

        return $this->find($id)->update(['status' => $data['status']]);
    }

    /**
     * حذف الطلب مع إرجاع العاملة متاحة
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();

            // البحث عن الطلب
            $order = $this->model->findOrFail($id);

            // حفظ معلومات العاملة قبل الحذف
            $maidId = $order->maid_id;
            $maid = Maid::find($maidId);

            // حذف الطلب
            $deleted = parent::delete($id);

            if ($deleted && $maid) {
                // إرجاع العاملة متاحة مرة أخرى
                $maid->update([
                    'status' => 'available',
                    'available' => 1,
                    'available_at' => now()->addDays(30)->toDateString()
                ]);

                \Log::info('Order deleted and maid restored to available', [
                    'order_id' => $id,
                    'maid_id' => $maidId,
                    'maid_status' => 'available'
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error deleting order and restoring maid', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
