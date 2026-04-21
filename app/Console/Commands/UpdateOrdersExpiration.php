<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use App\Support\AutomationSettings;

class UpdateOrdersExpiration extends Command
{
    protected $signature = 'orders:update-expiration';
    protected $description = 'Update expiration time for existing orders';

    public function handle()
    {
        $this->info('Updating expiration time for existing orders...');

        // Update all active new orders so settings changes apply immediately
        $newOrders = Order::where('status', OrderEnum::New->value)
            ->get();

        $this->info("Found {$newOrders->count()} new orders to refresh");

        // Get expiration hours from settings
        $expirationHours = AutomationSettings::orderExpirationHours();

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
