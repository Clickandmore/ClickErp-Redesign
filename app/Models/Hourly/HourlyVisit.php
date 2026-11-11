<?php
namespace App\Models\Hourly;
use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;
class HourlyVisit extends Model
{
    use AssignsBranch, BranchScoped;

    protected $guarded = [];

    /** Actual execution includes an individual worker arriving before the rest of the team. */
    public function getHasStartedExecutionAttribute(): bool
    {
        if (in_array($this->status, ['en_route','delivered','in_progress','worker_finished','picked_up','completed'], true)) return true;
        if (!$this->id) return false;
        $arrival = \Illuminate\Support\Facades\Schema::hasColumn('hourly_visit_workers','arrived_at');
        $status = \Illuminate\Support\Facades\Schema::hasColumn('hourly_visit_workers','status');
        if (!$arrival && !$status) return false;
        return \Illuminate\Support\Facades\DB::table('hourly_visit_workers')->where('hourly_visit_id',$this->id)
            ->where(function ($q) use ($arrival,$status) {
                if ($arrival) $q->whereNotNull('arrived_at');
                if ($status) $q->orWhereIn('status',['delivered','finished','picked_up','returned','completed']);
            })->exists();
    }

    public function getIsMissedAttribute(): bool
    {
        return $this->status === 'scheduled' && !$this->has_started_execution && $this->scheduled_end_at && $this->scheduled_end_at->lte(now());
    }
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'scheduled' && $this->has_started_execution) return 'in_progress';
        return $this->is_missed ? 'missed' : $this->status;
    }

    /**
     * رمز التسليم يُولَّد لكل زيارة عند إنشائها: يراه العميل في تطبيقه ويطلبه السائق عند التسليم.
     * الفحص لا يُخزَّن في ذاكرة ثابتة لأن المخطط قد يختلف بين الاتصالات والاختبارات.
     */
    protected static function booted()
    {
        static::creating(function (self $visit) {
            if (empty($visit->delivery_code) && \Illuminate\Support\Facades\Schema::hasColumn('hourly_visits', 'delivery_code')) {
                $visit->delivery_code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
        // بداية الخدمة الفعلية لكل عاملة = لحظة وصول الزيارة إلى «تم التوصيل» من أي مسار
        // (بوابة السائق، تطبيقه، الإدارة). وصول العاملة بنفسها يملأ arrived_at قبل ذلك فلا يُستبدل.
        static::updated(function (self $visit) {
            if ($visit->wasChanged('status') && $visit->status === 'delivered'
                && \Illuminate\Support\Facades\Schema::hasColumn('hourly_visit_workers', 'arrived_at')) {
                $rows = \Illuminate\Support\Facades\DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id);
                // وصول ذاتي (لصف العاملة حالة): لا يُختم وقت الوصول على زميلة لم تحضر بعد، وإلا بدت حاضرة للتحصيل ومتابعة العودة.
                if (\Illuminate\Support\Facades\Schema::hasColumn('hourly_visit_workers', 'status')
                    && (clone $rows)->whereIn('status', ['delivered', 'finished', 'picked_up', 'returned', 'completed'])->exists()) return;
                $rows->whereNull('arrived_at')->update(['arrived_at' => now()]);
            }
        });
    }
    protected $casts = ['scheduled_date'=>'date','scheduled_start_at'=>'datetime','scheduled_end_at'=>'datetime','delivery_at'=>'datetime','pickup_at'=>'datetime','is_free'=>'boolean'];
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromTable('hourly_rental_orders', $this->hourly_rental_order_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin()
            ?: $this->resolveMainBranchId();
    }
    public function workers() { return $this->belongsToMany(\App\Models\ingaz\Maid::class,'hourly_visit_workers','hourly_visit_id','maid_id')->withPivot('id')->withTimestamps(); }
    public function deliveryDriver() { return $this->belongsTo(HourlyDriver::class,'delivery_driver_id'); }
    public function pickupDriver() { return $this->belongsTo(HourlyDriver::class,'pickup_driver_id'); }
    public function teamLeader() { return $this->belongsTo(HourlyDriver::class,'team_leader_id'); }
    public function getMapUrlAttribute(): string
    {
        $query=$this->latitude!==null && $this->longitude!==null?$this->latitude.','.$this->longitude:$this->address_text;
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string)$query);
    }
}
