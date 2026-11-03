<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\WebPush\WebPushSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Notification extends Model
{
    use HasTranslations, LogsActivity;
    public $translatable = ['title', 'description'];
    protected $guarded = [];

    protected static function booted(): void
    {
        // Website push only; the mobile app keeps receiving through its own path as before.
        static::created(fn (Notification $notification) => WebPushSender::afterCreated($notification));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function hourlyOrder()
    {
        return $this->belongsTo(\App\Models\Hourly\HourlyRentalOrder::class, 'hourly_order_id');
    }

    public function hourlyVisit()
    {
        return $this->belongsTo(\App\Models\Hourly\HourlyVisit::class, 'hourly_visit_id');
    }

    /** مرجع طلب الساعات للإشعار، إن وُجدت أعمدته؛ تستخدمه قائمة الإشعارات والضغط على الإشعار. */
    public static function hourlyReference(?int $orderId, ?int $visitId = null): array
    {
        if (!$orderId || !Schema::hasColumn('notifications', 'hourly_order_id')) return [];
        return ['hourly_order_id' => $orderId, 'hourly_visit_id' => $visitId];
    }

    /** Notifications created after the customer last opened the list are unread. */
    public static function unreadCountFor(User $user): int
    {
        if (!Schema::hasColumn('users', 'notifications_read_at')) return 0;
        return static::where('user_id', $user->id)
            ->when($user->notifications_read_at, fn ($query, $readAt) => $query->where('created_at', '>', $readAt))
            ->count();
    }

    public static function markReadFor(User $user): void
    {
        // A plain update: opening the list is not a profile change worth an activity log entry.
        if (Schema::hasColumn('users', 'notifications_read_at')) User::whereKey($user->id)->update(['notifications_read_at' => now()]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
