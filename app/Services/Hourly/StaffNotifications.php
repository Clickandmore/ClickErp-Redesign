<?php

namespace App\Services\Hourly;

use App\Support\HourlyStaffOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * صندوق إشعارات تطبيق الفريق الميداني (سائق/عاملة).
 *
 * الإشعار المدفوع (FCM) قد لا يصل: التطبيق مغلق، أو الجهاز بلا شبكة، أو التوكن انتهى.
 * StaffPushService يسجّل كل إرسال في communication_logs (provider = staff_app) أصلًا،
 * فهذا الصندوق يقرأ من ذلك السجل نفسه — لا جدول موازٍ ولا مصدر ثانٍ للحقيقة —
 * ويضيف فقط علامة «قرأتُ حتى» في hourly_staff_notification_reads لحساب غير المقروء.
 */
class StaffNotifications
{
    public const PROVIDER = 'staff_app';
    private const READS = 'hourly_staff_notification_reads';
    private const LOGS = 'communication_logs';

    /** @return array{items:array,unread_count:int,last_read_at:?string} */
    public function feed(string $guard, int $staffId, int $limit = 50): array
    {
        $guard = $this->guard($guard);
        if (!Schema::hasTable(self::LOGS)) return ['items' => [], 'unread_count' => 0, 'last_read_at' => null];
        $lastRead = $this->lastReadAt($guard, $staffId);
        $rows = $this->query($guard, $staffId)->orderByDesc('id')->limit($limit)->get();

        return [
            'items' => $rows->map(fn ($row) => $this->item($row, $lastRead))->all(),
            'unread_count' => $this->unreadCount($guard, $staffId, $lastRead),
            'last_read_at' => $lastRead?->toIso8601String(),
        ];
    }

    /** يضع علامة القراءة عند وقت محدد (الافتراضي الآن) فيصفّر شارة غير المقروء. */
    public function markRead(string $guard, int $staffId, ?string $until = null): array
    {
        $guard = $this->guard($guard);
        abort_unless(Schema::hasTable(self::READS), 503, 'صندوق الإشعارات غير مهيأ على الخادم.');
        $at = $until ? Carbon::parse($until) : now();
        $current = $this->lastReadAt($guard, $staffId);
        // العلامة لا ترجع للخلف: إعادة إرسال الطلب لا تُعيد إظهار ما قُرئ.
        if ($current && $current->gte($at)) $at = $current;
        DB::table(self::READS)->updateOrInsert(
            ['guard' => $guard, 'staff_id' => $staffId],
            ['last_read_at' => $at, 'updated_at' => now(), 'created_at' => $current ? DB::raw('created_at') : now()]
        );

        return ['last_read_at' => $at->toIso8601String(), 'unread_count' => $this->unreadCount($guard, $staffId, $at)];
    }

    public function unreadCount(string $guard, int $staffId, ?Carbon $lastRead = null): int
    {
        $guard = $this->guard($guard);
        if (!Schema::hasTable(self::LOGS)) return 0;
        $lastRead ??= $this->lastReadAt($guard, $staffId);
        return (int) $this->query($guard, $staffId)->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))->count();
    }

    private function query(string $guard, int $staffId)
    {
        return DB::table(self::LOGS)->where('provider', self::PROVIDER)->where('recipient_type', 'staff')
            ->where('meta->guard', $guard)->where('meta->staff_id', $staffId);
    }

    private function lastReadAt(string $guard, int $staffId): ?Carbon
    {
        if (!Schema::hasTable(self::READS)) return null;
        $value = DB::table(self::READS)->where('guard', $guard)->where('staff_id', $staffId)->value('last_read_at');
        return $value ? Carbon::parse($value) : null;
    }

    /** صف السجل بالشكل الذي يعرضه التطبيق، مع بيانات الانتقال (type/visit/order) إن سُجّلت. */
    private function item(object $row, ?Carbon $lastRead): array
    {
        $meta = json_decode((string) $row->meta, true) ?: [];
        $payload = (array) ($meta['payload'] ?? []);
        $created = $row->created_at ? Carbon::parse($row->created_at) : null;

        return [
            'id' => (int) $row->id,
            'title' => (string) $row->title,
            'body' => (string) $row->message,
            'event' => $payload['type'] ?? null,
            'visit_id' => isset($payload['hourly_visit_id']) ? (int) $payload['hourly_visit_id'] : null,
            'order_id' => isset($payload['hourly_order_id']) ? (int) $payload['hourly_order_id'] : null,
            'role' => $payload['role'] ?? null,
            'status' => (string) $row->status,
            'delivered' => $row->status === 'sent',
            'created_at' => $created?->toIso8601String(),
            'read' => (bool) ($lastRead && $created && $created->lte($lastRead)),
        ];
    }

    private function guard(string $guard): string
    {
        return $guard === HourlyStaffOtp::DRIVER ? HourlyStaffOtp::DRIVER : HourlyStaffOtp::WORKER;
    }
}
