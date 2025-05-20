<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use Carbon\Carbon;

class UpdateOrdersExpiration extends Command
{
    protected $signature = 'orders:update-expiration';
    protected $description = 'Update expiration time for existing orders';

    public function handle()
    {
        $this->info('Updating expiration time for existing orders...');

        // Get all new orders without expiration time
        $newOrders = Order::where('status', OrderEnum::New->value)
            ->whereNull('expiration_time')
            ->get();

        $this->info("Found {$newOrders->count()} orders without expiration time");

        // Get expiration hours from settings
        $expirationHours = \App\Models\ingaz\Setting::getValue('order_expiration_hours', 24);

        // Update each order
        foreach ($newOrders as $order) {
            $this->info("\nUpdating order #{$order->order_code}:");
            $this->info("Created at: {$order->created_at}");

            // Set expiration time based on creation time
            $order->expiration_time = $order->created_at->addHours($expirationHours);
            $order->save();

            $this->info("Expiration time set to: {$order->expiration_time}");
        }

        $this->info("\nUpdate completed successfully!");
    }
}
