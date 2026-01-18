<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use Carbon\Carbon;
use App\Http\Traits\SendSMSTrait;

class HandleExpiredOrders extends Command
{
    use SendSMSTrait;
    protected $signature = 'orders:handle-expired';
    protected $description = 'Handle expired orders by updating their status';

    public function handle()
    {
        $expiredOrders = Order::where('status', OrderEnum::New->value)
            ->where('expiration_time', '<', Carbon::now())
            ->get();

        foreach ($expiredOrders as $order) {
            $order->status = OrderEnum::Refused->value;
            $order->save();

            // Create notification for the user
            $order->user->notifications()->create([
                'title' => [
                    'en' => 'Order Expired',
                    'ar' => 'انتهت صلاحية الطلب'
                ],
                'description' => [
                    'en' => "Your order #{$order->order_code} has expired",
                    'ar' => "انتهت صلاحية طلبك رقم {$order->order_code}"
                ],
                'type' => 'order_expired',
                'order_id' => $order->id
            ]);

            // Send SMS
            $userPhone = $order->user?->phone;
            if ($userPhone) {
                $body = " عزيزي العميل : تخطي الطلب الخاص بكم المدة المسموحة له في النظام والغاءه برقم جواز : " . $order->maid->passport_num . " ورقم الطلب : " . $order->order_code;
                $this->sendSMS(formatSaudiPhone($userPhone), $body);
            } else {
                \Log::warning('Expired order SMS skipped: missing user phone', [
                    'order_id' => $order->id
                ]);
            }
        }

        $this->info('Expired orders handled successfully.');
    }
}