<?php

namespace App\Services\Hourly;

use App\Http\Traits\Firebase;
use App\Models\Hourly\HourlyRentalOrder;
use App\Models\Hourly\HourlyVisit;
use App\Models\ingaz\Notification;
use Illuminate\Support\Facades\Log;

/** إشعار عميل الزيارة بمراحل الوصول وبتغيّر حالة طلبه، مع رابط عميق للزيارة أو الطلب. */
class CustomerTrackingPushService
{
    use Firebase;

    /** مراحل الزيارة التي يهم العميل معرفتها. */
    private const STAGES = [
        'service_ending' => ['اقترب انتهاء مدة الخدمة', 'إذا رغبت في استمرار الخدمة، افتح الزيارة لمعرفة سعر الساعات الإضافية وتأكيدها بعد التحقق من التوفر. لا تُضاف تكلفة تلقائيًا.'],
        'service_followup' => ['متابعة زيارتك', 'تتابع الإدارة ظرفًا طارئًا أثناء تنفيذ زيارتك والعودة والتسوية عند الحاجة. تواصل مع خدمة العملاء لأي مساعدة.'],
        'service_disrupted' => ['تعذر تنفيذ الزيارة', 'نعتذر، تعذر تنفيذ زيارتك لظرف طارئ. افتح تفاصيل الزيارة لاختيار المعالجة المتاحة أو متابعة توفير البديلة بواسطة الإدارة. لن يُستهلك رصيد الزيارة أثناء انتظار المعالجة.'],
        'service_resolved' => ['تمت معالجة زيارتك', 'تم اعتماد طلب معالجة زيارتك. راجع تفاصيل الطلب لمعرفة الموعد الجديد أو قيمة الاسترداد.'],
        'worker_replaced' => ['تم تحديث عاملة الزيارة', 'تم توفير عاملة بديلة لزيارتك بنفس مواصفات الخدمة ودون تكلفة إضافية. يمكنك مراجعة بيانات الزيارة من تفاصيل الطلب.'],
        'on_the_way' => ['العاملة في الطريق', 'بدأ فريق الزيارة طريقه إلى عنوانك. يمكنك متابعة الوصول مباشرة من التطبيق.'],
        'arrived' => ['وصل فريق الزيارة', 'وصل فريق الزيارة إلى عنوانك.'],
        'finished' => ['انتهت الزيارة', 'أنهى فريق الزيارة العمل. نسعد بتقييمك للخدمة من التطبيق.'],
        'early_finish' => ['انتهت الزيارة قبل وقتها', 'أنهت العاملة زيارتك قبل اكتمال الوقت المحجوز. افتح تفاصيل الزيارة وأخبرنا هل كان الإنهاء بطلبك.'],
    ];

    public function notify(int $visitId, string $status): void
    {
        try {
            if (!isset(self::STAGES[$status])) {
                return;
            }

            $visit = HourlyVisit::with('order:id,user_id')->find($visitId);
            if (!$visit?->order?->user_id) return;

            [$title, $body] = self::STAGES[$status];

            $this->push((int) $visit->order->user_id, $title, $body, [
                'type' => 'hourly_visit',
                'hourly_order_id' => (string) $visit->hourly_rental_order_id,
                'visit_id' => (string) $visit->id,
                // توافق مع الإشعارات التشغيلية القديمة.
                'hourly_visit_id' => (string) $visit->id,
                'stage' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to send hourly customer tracking notification', [
                'visit_id' => $visitId, 'stage' => $status, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * نصوص حالات الطلب.
     *
     * الحالة الوسيطة pending_confirmation لا تُشعر: العميل هو من أنشأ الطلب
     * للحظته، وإشعاره بها تكرار بلا فائدة.
     *
     * ورقم الطلب لا يُذكر في النص: صيغته 39 حرفاً (HR- ثم UUID)، فكان يدفع
     * الجملة المفيدة خارج شاشة الإشعار. يبقى في data ليفتح الضغط الطلب نفسه.
     */
    private const ORDER_STATUSES = [
        'active' => ['تم تأكيد طلبك', 'تم تأكيد طلب الخدمة وتفعيله. يمكنك متابعة مواعيد الزيارات من التطبيق.'],
        'completed' => ['اكتمل طلبك', 'اكتملت جميع زيارات طلبك. شكراً لثقتك بنا، ونسعد بتقييمك للخدمة.'],
        'cancelled' => ['تم إلغاء طلبك', 'تم إلغاء طلب الخدمة. لأي استفسار تواصل مع خدمة العملاء.'],
        'expired' => ['انتهت صلاحية باقتك', 'انتهت صلاحية باقتك دون استهلاك كل الزيارات. يمكنك التجديد من التطبيق.'],
    ];

    /**
     * إشعار العميل بتغيّر حالة طلبه.
     *
     * يُستدعى من HourlyOrderService::transition وهي المعبر الوحيد لكل تغييرات
     * حالة الطلب، فتُغطّى بذلك مسارات التفعيل الستة والإلغاء والانتهاء معاً.
     */
    public function orderStatus(int $orderId, string $status): void
    {
        try {
            if (!isset(self::ORDER_STATUSES[$status])) {
                return;
            }

            $order = HourlyRentalOrder::query()->find($orderId, ['id', 'user_id', 'order_number']);
            if (!$order?->user_id) return;

            [$title, $body] = self::ORDER_STATUSES[$status];

            $this->push((int) $order->user_id, $title, $body, [
                'type' => 'hourly_order_status',
                'hourly_order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'order_status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to send hourly order status notification', [
                'order_id' => $orderId, 'status' => $status, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ينشئ الإشعار في قائمة إشعارات التطبيق ثم يرسله.
     *
     * المرور عبر Notification مقصود لا زائد: إنشاء الصف يضيف سطراً في سجل
     * الرسائل، وتمرير notification_id يجعل trait Firebase يكتب حصيلة التسليم
     * على ذلك السطر. الإرسال المباشر عبر FirebasePushService كان يفعل شيئاً
     * واحداً — يرسل — فلا يظهر الإشعار في قائمة التطبيق ولا في سجل اللوحة،
     * ولا يُعرف أوصل أم لا.
     */
    private function push(int $userId, string $title, string $body, array $data): void
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'hourly_visit',
            'title' => ['ar' => $title, 'en' => $title],
            'description' => ['ar' => $body, 'en' => $body],
        ] + Notification::hourlyReference(isset($data['hourly_order_id']) ? (int) $data['hourly_order_id'] : null,
            isset($data['visit_id']) ? (int) $data['visit_id'] : null));

        $this->sendFCMNotification($title, $body, $userId, $data + [
            'notification_id' => (string) $notification->id,
        ]);
    }
}
