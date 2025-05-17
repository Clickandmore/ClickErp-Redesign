<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use Carbon\Carbon;

class HandleExpiredOrders extends Command
{
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
        }

        $this->info('Expired orders handled successfully.');
    }
}