<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;

class UpdateOrdersTimer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-timer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update timer for existing orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating orders timer...');

        // Get orders that need timer update
        $orders = Order::whereIn('status', [
            OrderEnum::New->value,
            OrderEnum::UnderProcess->value
        ])->get();

        $updatedCount = 0;

        foreach ($orders as $order) {
            // Set status_changed_at if not set
            if (!$order->status_changed_at) {
                $order->status_changed_at = $order->created_at;
            }

            // Update deadline
            $order->updateDeadline();
            $order->save();
            $updatedCount++;
        }

        $this->info("Updated {$updatedCount} orders timer successfully!");
    }
}
