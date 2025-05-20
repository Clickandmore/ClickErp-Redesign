<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use Carbon\Carbon;

class TestExpiredOrders extends Command
{
    protected $signature = 'orders:test-expired';
    protected $description = 'Test expired orders functionality';

    public function handle()
    {
        $this->info('Testing expired orders functionality...');

        // Get all new orders
        $newOrders = Order::where('status', OrderEnum::New->value)->get();

        $this->info("Found {$newOrders->count()} new orders");

        // Check each order's expiration
        foreach ($newOrders as $order) {
            $this->info("\nChecking order #{$order->order_code}:");
            $this->info("Created at: {$order->created_at}");
            $this->info("Expiration time: {$order->expiration_time}");

            if ($order->isExpired()) {
                $this->info("Order is expired!");
                $this->info("Current status: {$order->status}");

                // Update status to refused
                $order->status = OrderEnum::Refused->value;
                $order->save();

                // Create notification
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

                $this->info("Order status updated to refused");
                $this->info("Notification created for user");
            } else {
                $this->info("Order is still valid");
            }
        }

        $this->info("\nTest completed successfully!");
    }
}
