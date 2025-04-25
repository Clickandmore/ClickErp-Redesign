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
use Illuminate\Support\Facades\Auth;
use App\Enums\ingaz\NotificationActionEnum;

class OrderAction extends MainAction
{
    use Firebase, SendSMSTrait;
    public function __construct(Order $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Order/';
    }

    public function getIndex($status)
    {
        $acceptedStatuses = [];
        if ($status == 'current') {
            $acceptedStatuses = [OrderEnum::New , OrderEnum::Completed];
        } elseif ($status == 'previous') {
            $acceptedStatuses = [OrderEnum::Refused, OrderEnum::Done];
        }
        $data = $this->model->where('User_id', getGuardedUser()->id)
            ->whereIn('status', $acceptedStatuses)
            ->get();
        return $data;
    }

    public function storeOrder($data, $request)
    {
        $data['user_id'] = Auth::user()->id;
        $data['order_code'] = $this->model::generateUniqueCode();

        // dd($data['user_id']);
        $order = $this->store($data);
        if ($request->details_list) {
            $this->StoreOrderDetails($request, $order);
        }
        $this->StoreNotification($order);
        $order = $this->model->find($order->id);

        try{
            if($order && $order->job_type_id == 1 && $order->admin->phone){
                $orderId = $order->order_code;
                $msg = "يوجد طلب إستقدام جديد رقم $orderId";
                $result = $this->sendSMS($order->admin->phone, $msg);
            }
        }catch(\Exception $ex){
            //msg not sent
        }

        return $order;
    }

    public function StoreOrderDetails($request, $order)
    {
//        foreach ($request->details_list as $key => $details_list) {
        $dataProduct = [
            'admin_id' => $request->details_list['admin_id'] ?? null,
            'package_id' => $request->details_list['package_id'] ?? null,
            'address_id' => $request->details_list['address_id'] ?? null,
            'deliver_maid' => $request->details_list['deliver_maid'] ?? '0',
            'order_id' => $order->id,
        ];
        $results = OrderDetail::create($dataProduct);
//        }
    }

    public function StoreNotification($order, $type = null)
    {
        $notification = Notification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            "type" => ($type != null) ? $type : NotificationActionEnum::NewOrder->value,
            'title' => [
                'en' => __('notifications.Asus Al Injaz', [], 'en'),
                'ar' => __('notifications.Asus Al Injaz', [], 'ar'),
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
        if ($data['status'] == 'completed') {

            if ($order->package) {
//            if(now() < $maid->available_at){
//                return jsonValid('','asd',422);
//            }
                $maid->available = 0;
                $maid->available_at = Carbon::now()->addDays($order->package->days_count);
            }

            //** NEW ADDED D18M7Y24 => IS */
            // make a contract after order start
            if ($order->job_type_id == 1) {

                $checkContract = Contract::where('order_id', $order->id)->exists();
                if(!$checkContract){
                    // Scenario 1: User selects an existing order to create a contract
                    $contractData['order_id'] = $order->id;
                    $contractData['user_id'] = $order->user_id;
                    $contractData['maid_id'] = $order->maid_id;
                    $contractData['status'] = 'sari';
                    // Create contract
                    $contract = Contract::create($contractData);
                }
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
                'en' => __('notifications.Asus Al Injaz', [], 'en'),
                'ar' => __('notifications.Asus Al Injaz', [], 'ar'),
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

}
