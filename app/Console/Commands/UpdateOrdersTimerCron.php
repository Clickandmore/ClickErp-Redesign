<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Enums\ingaz\OrderEnum;
use Carbon\Carbon;

class UpdateOrdersTimerCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:timer-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update orders timer via cron job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running orders timer cron job...');

        // Get active orders that need timer update
        $orders = Order::whereIn('status', [
            OrderEnum::New->value,
            OrderEnum::UnderProcess->value
        ])->get();

        $updatedCount = 0;

        foreach ($orders as $order) {
            // Check if order is overdue and needs status update
            if ($order->isOverdue()) {
                // You can add logic here to automatically change status or send notifications
                $this->warn("Order #{$order->id} is overdue!");
            }

            $updatedCount++;
        }

        $this->info("Processed {$updatedCount} orders timer successfully!");
    }
}
