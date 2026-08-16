<?php

namespace App\Console\Commands;

use App\Models\ingaz\Order;
use App\Models\ingaz\Notification;
use App\Enums\ingaz\OrderEnum;
use App\Support\AutomationSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired';
    protected $description = 'إلغاء الطلبات التي انتهت مهلة متابعتها التلقائية';

    public function handle()
    {
        try {
            $deadlineHours = AutomationSettings::orderNewDeadlineHours();

            // البحث عن الطلبات الجديدة التي انتهت مهلة متابعتها
            $query = Order::where('status', OrderEnum::New->value)
                ->where('deadline_at', '<=', now())
                ->whereNotNull('deadline_at');

            $canceledCount = 0;

            $query->chunkById(100, function ($orders) use (&$canceledCount, $deadlineHours): void {
                foreach ($orders as $order) {
                    Log::info('Canceling expired order', [
                        'order_id' => $order->id,
                        'deadline_at' => $order->deadline_at,
                    ]);

                // تحديث حالة الطلب إلى ملغي
                    $order->status = OrderEnum::Return->value; // أو استخدم القيمة المناسبة
                    $order->refuse_reason = "تم إلغاء الطلب تلقائياً بسبب انتهاء المهلة ({$deadlineHours} ساعة) دون تأكيد من المندوب";
                    $order->status_changed_at = now();
                    $order->save();

                // تحرير العاملة وإعادتها لحالة متاحة
                    $this->releaseMaid($order);

                // إرسال إشعار للعميل
                    Notification::create([
                        'title' => 'إلغاء الطلب',
                        'description' => "تم إلغاء طلبك رقم {$order->id} تلقائياً بسبب عدم تأكيده خلال {$deadlineHours} ساعة",
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'type' => 'order_canceled',
                    ]);

                    $canceledCount++;
                }
            });

            $this->info("تم إلغاء {$canceledCount} طلب منتهي الصلاحية");
            Log::info("Auto-canceled {$canceledCount} expired orders");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error in CancelExpiredOrders command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error('حدث خطأ: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * تحرير العاملة وإعادتها لحالة متاحة
     */
    private function releaseMaid(Order $order)
    {
        if ($order->maid) {
            $order->maid->status = 'available'; // أو القيمة المناسبة
            $order->maid->save();

            Log::info('Maid released', [
                'maid_id' => $order->maid->id,
                'order_id' => $order->id,
            ]);
        }
    }
}
