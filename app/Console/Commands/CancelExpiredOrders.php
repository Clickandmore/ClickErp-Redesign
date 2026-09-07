<?php

namespace App\Console\Commands;

use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired {--dry-run : List eligible orders without changing data}';
    protected $description = 'Cancel new orders at their alert deadline and release their maids';

    public function handle(): int
    {
        $count = 0;
        Order::withoutBranchScope()->where('status', OrderEnum::New->value)
            ->where(function ($query) {
                $query->where('deadline_at', '<=', now())->orWhereNull('deadline_at');
            })->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate, &$count) {
                        $order = Order::withoutBranchScope()->lockForUpdate()->find($candidate->id);
                        if (!$order || $order->status !== OrderEnum::New->value
                            || !$order->getEffectiveDeadline()?->lessThanOrEqualTo(now())) {
                            return;
                        }
                        if ($order->contracts()->withoutBranchScope()->exists()
                            || $order->rentalContracts()->withoutBranchScope()->exists()) {
                            return;
                        }
                        if ($this->option('dry-run')) {
                            $this->line("Eligible order: {$order->id}");
                            $count++;
                            return;
                        }
                        $maid = Maid::withoutBranchScope()->lockForUpdate()->find($order->maid_id);
                        $order->update([
                            'status' => OrderEnum::CanceledBySystem->value,
                            'status_changed_at' => now(),
                            'expiration_time' => null,
                            'refuse_reason' => 'تم إلغاء الطلب تلقائياً لانتهاء مهلة الطلب الجديد دون تأكيد.',
                        ]);
                        if ($order->user_id) {
                            \App\Models\ingaz\Notification::create([
                                'title' => ['ar' => 'إلغاء الطلب تلقائياً', 'en' => 'Order automatically canceled'],
                                'description' => [
                                    'ar' => "تم إلغاء الطلب رقم {$order->order_code} لانتهاء مهلة التأكيد.",
                                    'en' => "Order {$order->order_code} was canceled because its confirmation deadline expired.",
                                ],
                                'order_id' => $order->id,
                                'user_id' => $order->user_id,
                                'type' => 'order_canceled',
                            ]);
                        }
                        if ($maid && !Order::withoutBranchScope()->where('maid_id', $maid->id)
                            ->whereNotIn('status', Order::nonBlockingMaidAssignmentStatuses())->exists()
                            && !\App\Models\Contract::withoutBranchScope()->where('maid_id', $maid->id)
                                ->whereNotIn('status', ['closed', 'back', 'cancelled', 'canceled'])->exists()
                            && !\App\Models\RentalContract::withoutBranchScope()->where('maid_id', $maid->id)
                                ->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
                            $maid->update(['status' => 'available', 'available' => true, 'available_at' => now()]);
                        }
                        $count++;
                    }, 3);
                }
            });
        $this->info(($this->option('dry-run') ? 'Eligible orders: ' : 'Canceled orders: ') . $count);
        return self::SUCCESS;
    }
}
