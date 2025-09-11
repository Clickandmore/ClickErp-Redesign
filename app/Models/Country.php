<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'phone_code',
        'currency',
        'currency_symbol',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function getTitleAttribute()
    {
        return app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_' . app()->getLocale());
    }

    /**
     * العلاقة مع المدن
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    /**
     * الحصول على العاصمة
     */
    public function capital()
    {
        return $this->cities()->where('is_capital', true)->first();
    }
}
