<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

class HourlyCategoryNote extends Model
{
    protected $table = 'hourly_category_notes';
    protected $guarded = ['id'];
    protected $casts = ['sort_order' => 'integer'];

    public function category() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id'); }

    public function text(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' && $this->text_en ? $this->text_en : $this->text_ar;
    }
}
