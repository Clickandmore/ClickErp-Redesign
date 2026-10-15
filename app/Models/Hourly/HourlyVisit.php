<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyVisit extends Model
{
    protected $guarded = [];

    /** Display-only state: a missed slot is neither performed nor cancelled. */
    public function getIsMissedAttribute(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_end_at && $this->scheduled_end_at->lte(now());
    }
    public function getDisplayStatusAttribute(): string
    {
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
    }
    protected $casts = ['scheduled_date'=>'date','scheduled_start_at'=>'datetime','scheduled_end_at'=>'datetime','delivery_at'=>'datetime','pickup_at'=>'datetime','is_free'=>'boolean'];
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }
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
