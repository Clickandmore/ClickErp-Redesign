<?php

namespace App\Console\Commands;

use App\Http\Controllers\Hourly\RenewalController;
use App\Services\Hourly\RenewalSearch;
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};
use Ramsey\Uuid\Uuid;

/**
 * مهمة يومية للتجديدات: تحدّث عداد «التجديدات» في القائمة، وتضع «تذكير بتجديد الباقة» في طابور
 * hourly_visit_notices للطلبات التي تقع آخر زيارة فيها خلال renewal_reminder_days يومًا.
 * معرّف الدفعة ثابت لكل طلب، فالقيد الفريد يمنع تكرار التذكير حتى لو تكرر التشغيل أو فات يوم.
 */
class HourlyRenewalReminders extends Command
{
    protected $signature = 'hourly:renewal-reminders';
    protected $description = 'Refresh the hourly renewals counter and queue package renewal reminders';

    public function handle(RenewalSearch $search): int
    {
        HourlyRentalSettings::flush();
        if (!HourlyRentalSettings::isEnabled()) return 0;
        $days = (int) HourlyRentalSettings::get('renewal_reminder_days', 7);
        RenewalController::dueCount(true);
        if ($days <= 0 || !Schema::hasTable('hourly_visit_notices')) return 0;

        $queued = 0;
        // نفس نطاق العداد: عقود غير مجدَّدة تنتهي خلال المدة، ولها زيارات لم تُنفَّذ بعد.
        $query = $search->dueQuery($days)->where('cp.open_visits', '>', 0)->with(['user:id,name,email', 'serviceCategory']);
        foreach ($query->get() as $order) {
            if (!$order->user) continue;
            $batch = Uuid::uuid5(Uuid::NAMESPACE_URL, 'hourly-renewal-reminder:'.$order->id)->toString();
            $end = \Carbon\Carbon::parse($order->last_visit_at);
            $body = 'عزيزنا '.$order->user->name.'، تنتهي باقتك «'.($order->serviceCategory?->name ?? 'خدمة الساعات').'» بآخر زيارة يوم '.$end->format('Y-m-d')
                .'. جدّد الباقة الآن لتستمر الخدمة دون انقطاع. رقم الطلب: '.$order->order_number;
            $channels = ['app'];
            if (filter_var($order->user->email, FILTER_VALIDATE_EMAIL)) $channels[] = 'email';
            foreach ($channels as $channel) {
                $queued += DB::table('hourly_visit_notices')->insertOrIgnore(['batch_id' => $batch, 'user_id' => $order->user_id, 'channel' => $channel,
                    'title' => 'تذكير بتجديد الباقة', 'body' => $body, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $this->info('Renewal reminders queued: '.$queued);

        return 0;
    }
}
