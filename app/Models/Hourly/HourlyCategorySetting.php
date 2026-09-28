<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

class HourlyCategorySetting extends Model
{
    protected $table = 'hourly_category_settings';
    protected $guarded = ['id'];
    protected $casts = ['settings' => 'array'];

    public function category() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id'); }
}
