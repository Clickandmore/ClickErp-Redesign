<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

class HourlyCategoryClosure extends Model
{
    protected $table = 'hourly_category_closures';
    protected $guarded = ['id'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'district_ids' => 'array'];

    public function category() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id'); }

    /** هل يشمل الإغلاق هذا الحي؟ (null = كل الأحياء، والحي غير المعروف يُعد مشمولًا بإغلاق الكل فقط) */
    public function coversDistrict(?int $districtId): bool
    {
        return $this->district_ids === null || ($districtId !== null && in_array($districtId, array_map('intval', $this->district_ids), true));
    }
}
