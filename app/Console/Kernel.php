<?php

namespace App\Console;

use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\Admin\AdminContractController;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            // تغيير حالات الخادمات من غير متوفر الي متوفر اذا كان تاريخ اليوم اكبر من او يساوي يوم توافر العاملة
            $notAvailableMaids = Maid::notAvailable()->get();

            foreach ($notAvailableMaids as $maid) {
                if (now() >= $maid->available_at) {
                    $maid->update(['available' => 1]);
                }
            }
            // مسح الطلبات الجديدة التي مر عليها 24 ساعة دون تغيير الحالة من جديد
            $newOrders = Order::where('status', OrderEnum::New ->value)
                ->whereDate('created_at', '<=', now()->subHours(24))
                ->get();

            foreach ($newOrders as $order) {
                $order->delete();
            }

            // تغيير حالات الطلبات من جاري العمل الي منتهي اذا انتهي الوقت
            $startingOrders = Order::where('status', OrderEnum::Done->value)
                ->whereHas('maid', function ($query) {
                    $query->where('available_at', '<=', now());
                })
                ->get();

            foreach ($startingOrders as $order) {
                if ($order->package) {
                    $order->update(['status' => 'done']);
                }
            }
        })->dailyAt('00:00');

        $schedule->call(function () {
            $controller = new AdminContractController();
            $controller->checkWarrantyContracts();
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
