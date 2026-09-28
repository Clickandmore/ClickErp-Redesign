<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

class HourlyServiceCategoryLog extends Model
{
    protected $table = 'hourly_service_category_logs';
    protected $guarded = ['id'];
    protected $casts = ['before_data' => 'array', 'after_data' => 'array'];

    public function category() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id')->withTrashed(); }
}
