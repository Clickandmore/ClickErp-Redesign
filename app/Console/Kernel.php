<?php

namespace App\Console;

use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Support\AutomationSettings;
use App\Support\DatabaseBackupSettings;
use App\Services\SchedulerMonitorService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call([SchedulerMonitorService::class, 'writeHeartbeat'])
            ->everyMinute()
            ->description('scheduler-heartbeat');

        // $schedule->command('inspire')->hourly();
        $warrantyUpdateTime = AutomationSettings::warrantyStatusUpdateTime();
        $warrantyInitializeCron = AutomationSettings::hoursCronExpression(
            AutomationSettings::warrantyInitializeIntervalHours()
        );
        $ordersTimerCron = AutomationSettings::minutesCronExpression(
            AutomationSettings::ordersTimerIntervalMinutes()
        );
        $ordersExpiredHandlerCron = AutomationSettings::minutesCronExpression(
            AutomationSettings::ordersExpiredHandlerIntervalMinutes()
        );
        $ordersAutoCancelCron = AutomationSettings::minutesCronExpression(
            AutomationSettings::ordersAutoCancelIntervalMinutes()
        );
        $orderUrgentThresholdHours = AutomationSettings::orderUrgentThresholdHours();
        $oldPendingOrdersDays = AutomationSettings::oldPendingOrdersDays();
        $maidsAvailabilityUpdateTime = AutomationSettings::maidsAvailabilityUpdateTime();
        $logClearKeepDays = AutomationSettings::logClearKeepDays();
        $logClearWeekday = AutomationSettings::logClearWeekday();
        $logClearTime = AutomationSettings::logClearTime();
        $databaseOptimizeWeekday = AutomationSettings::databaseOptimizeWeekday();
        $databaseOptimizeTime = AutomationSettings::databaseOptimizeTime();
        $databaseBackupSettings = DatabaseBackupSettings::all();

        // ============ تحديثات الضمان ============

        // 1. تحديث حالة الضمان للعقود المكتملة يومياً في الساعة 1 صباحاً
        $schedule->command('contracts:update-warranty-status')
            ->dailyAt($warrantyUpdateTime)
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/warranty-update.log'));

        // 2. تهيئة الضمان للعقود الجديدة كل 6 ساعات
        $schedule->command('contracts:initialize-warranty')
            ->cron($warrantyInitializeCron)
            ->withoutOverlapping();

        // ============ تحديثات الطلبات ============

        // 3. تحديث تايمر الطلبات كل 30 دقيقة
        $schedule->call(function () use ($orderUrgentThresholdHours) {
            // تحديث حالة الطلبات المتأخرة
            Order::where('status', OrderEnum::New->value)
                ->where('deadline_at', '<', now())
                ->lazyById(100)
                ->each(function (Order $order): void {
                    \Log::warning('Order overdue', [
                        'order_id' => $order->id,
                        'deadline' => $order->deadline_at,
                        'hours_overdue' => $order->deadline_at->diffInHours(now())
                    ]);
                });

            // إرسال تنبيهات للطلبات العاجلة (أقل من ساعتين)
            Order::where('status', OrderEnum::New->value)
                ->where('deadline_at', '>', now())
                ->where('deadline_at', '<', now()->addHours($orderUrgentThresholdHours))
                ->lazyById(100)
                ->each(function (Order $order): void {
                    \Log::info('Order urgent', [
                        'order_id' => $order->id,
                        'deadline' => $order->deadline_at,
                        'minutes_remaining' => $order->deadline_at->diffInMinutes(now())
                    ]);
                });
        })->cron($ordersTimerCron)
          ->name('update-orders-timer')
          ->withoutOverlapping();

        // 4. تنظيف الطلبات القديمة المعلقة شهرياً
        $schedule->call(function () use ($oldPendingOrdersDays) {
            // الطلبات المعلقة لأكثر من المدة المحددة
            $oldPendingOrders = Order::whereIn('status', [OrderEnum::New->value, OrderEnum::UnderProcess->value])
                ->where('created_at', '<', now()->subDays($oldPendingOrdersDays))
                ->count();

            if ($oldPendingOrders > 0) {
                \Log::warning("Found {$oldPendingOrders} old pending orders (>{$oldPendingOrdersDays} days)");
            }
        })->monthly()
          ->name('check-old-orders')
          ->withoutOverlapping();

        // ============ تحديثات العاملات ============

        // 5. تحديث حالة توفر العاملات يومياً
        $schedule->call(function () {
            // تحديث العاملات المحجوزة التي انتهت عقودهن
            $maidsToRelease = Maid::where('status', 'unavailable')
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereNotIn('status', ['completed', 'closed', 'back']);
                })
                ->update(['status' => 'available']);

            if ($maidsToRelease > 0) {
                \Log::info("Released {$maidsToRelease} maids back to available status");
            }
        })->daily()
          ->at($maidsAvailabilityUpdateTime)
          ->name('update-maids-availability')
          ->withoutOverlapping();

        // ============ تنظيف عام ============

        // 6. تنظيف ملفات السجلات القديمة أسبوعياً
        $schedule->command('log:clear --keep=' . $logClearKeepDays)
            ->weeklyOn($logClearWeekday, $logClearTime);

        // 7. تحسين قاعدة البيانات أسبوعياً
        $schedule->call(function () {
            \DB::statement('OPTIMIZE TABLE contracts');
            \DB::statement('OPTIMIZE TABLE orders');
            \DB::statement('OPTIMIZE TABLE maids');
            \DB::statement('OPTIMIZE TABLE users');
            \Log::info('Database tables optimized');
        })->weeklyOn($databaseOptimizeWeekday, $databaseOptimizeTime)
          ->name('optimize-database')
          ->withoutOverlapping();

        
        $schedule->command('orders:cancel-expired')
             ->cron($ordersAutoCancelCron)
             ->withoutOverlapping();

        // Auto-refuse expired orders
        $schedule->command('orders:handle-expired')
            ->cron($ordersExpiredHandlerCron)
            ->withoutOverlapping();

        $schedule->command('rental-contracts:send-expiry-reminders')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/rental-contract-expiry-reminders.log'));

        $schedule->command('notifications:run-configured-alerts')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/configured-notification-alerts.log'));

        if ($databaseBackupSettings['enabled']) {
            $schedule->command('database:backup-r2')
                ->dailyAt($databaseBackupSettings['daily_time'])
                ->name('database-backup-r2')
                ->withoutOverlapping(120)
                ->appendOutputTo(storage_path('logs/database-backup.log'));
        }
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
