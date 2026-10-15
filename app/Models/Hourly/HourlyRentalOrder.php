<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyRentalOrder extends Model
{
    protected $guarded = [];
    protected $hidden = ['booking_metadata'];
    protected $casts = ['booking_metadata'=>'array','package_snapshot'=>'array','schedule_pattern'=>'array','starts_at'=>'datetime','expires_at'=>'datetime'];
    protected $appends = ['balance'];
    public function user() { return $this->belongsTo(\App\Models\ingaz\User::class); }
    public function creator() { return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by_admin_id'); }
    public function city() { return $this->belongsTo(\App\Models\City::class); }
    public function visits() { return $this->hasMany(HourlyVisit::class); }
    public function payments() { return $this->hasMany(HourlyOrderPayment::class); }
    public function statusLogs() { return $this->hasMany(HourlyOrderStatusLog::class); }
    public function serviceCategory() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id')->withTrashed(); }
    /** الطلب الأصلي الذي جُدِّد هذا الطلب منه. */
    public function renewedFrom() { return $this->belongsTo(self::class, 'renewed_from_order_id'); }
    /** كل الطلبات المنشأة كتجديد لهذا الطلب (أكثر من واحد فقط بصلاحية hourly_orders_renew_again). */
    public function renewals() { return $this->hasMany(self::class, 'renewed_from_order_id'); }
    /** التجديد الساري (آخر تجديد غير ملغى)؛ الطلب «مجدَّد» عند وجوده. */
    public function renewedBy() { return $this->hasOne(self::class, 'renewed_from_order_id')->where('status', '!=', 'cancelled')->orderByDesc('id'); }
    /** أقرب زيارة قادمة أو جارية (لم تنتهِ ولم تُلغَ)؛ null إن لم يبقَ موعد محدد. */
    public function nextVisit(): ?HourlyVisit
    {
        return $this->visits
            ->filter(fn ($visit) => $visit->scheduled_start_at && $visit->scheduled_end_at?->isFuture()
                && !in_array($visit->status, ['completed', 'no_show', 'cancelled', 'cancelled_by_client', 'expired', 'unscheduled'], true))
            ->sortBy('scheduled_start_at')->first();
    }

    /** آخر زيارة سبق موعدها، لعرضها حين لا توجد زيارة قادمة. */
    public function lastVisit(): ?HourlyVisit
    {
        return $this->visits->filter(fn ($visit) => $visit->scheduled_start_at?->isPast())->sortByDesc('scheduled_start_at')->first();
    }

    public function getBalanceAttribute(): array
    {
        $counts = $this->visits()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $available = (int) ($counts['unscheduled'] ?? 0);
        $consumed = (int) ($counts['completed'] ?? 0) + (int) ($counts['no_show'] ?? 0) + (int) ($counts['cancelled_by_client'] ?? 0);
        $expired = (int) ($counts['expired'] ?? 0);
        return ['available'=>$available, 'reserved'=>(int) $counts->sum()-$available-$consumed-$expired-(int)($counts['cancelled']??0), 'consumed'=>$consumed,'expired'=>$expired];
    }
}
