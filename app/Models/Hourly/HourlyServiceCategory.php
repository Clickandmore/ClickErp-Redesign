<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * فئة خدمة الساعات. إعداداتها تقع بين الإعدادات العامة وإعداد العنصر:
 * العنصر > الفئة > العام (القيمة الفارغة ترث من الأعلى). انظر HourlyBookingRules.
 */
class HourlyServiceCategory extends Model
{
    use SoftDeletes;

    protected $table = 'hourly_service_categories';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
    protected $appends = ['name', 'icon_url'];

    public function packages() { return $this->hasMany(HourlyPackage::class, 'service_category_id'); }

    public function closures() { return $this->hasMany(HourlyCategoryClosure::class, 'service_category_id'); }
    public function notes() { return $this->hasMany(HourlyCategoryNote::class, 'service_category_id')->orderBy('sort_order')->orderBy('id'); }

    /** الأحياء المخدومة في الفئة مع الفريق والحالة. */
    public function districts()
    {
        return $this->belongsToMany(ServiceDistrict::class, 'hourly_category_district', 'service_category_id', 'district_id')
            ->withPivot(['id', 'team_id', 'is_active'])->withTimestamps();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->name_en ? $this->name_en : (string) $this->name_ar;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_type === 'upload' && $this->icon ? Storage::disk('public')->url($this->icon) : null;
    }

    public function scopeOrdered($query) { return $query->orderBy('sort_order')->orderBy('id'); }
}
