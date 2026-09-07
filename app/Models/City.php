<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * العلاقة مع الدولة
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * الحصول على الاسم باللغة المطلوبة
     */
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * نطاق للمدن النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق للعواصم فقط
     */
    public function scopeCapitals($query)
    {
        return $query->where('is_capital', true);
    }

    /**
     * نطاق لترتيب المدن
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }
}
