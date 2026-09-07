<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use \App\Models\Concerns\AuditsChanges;

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
    public function cities()
    {
        return $this->hasMany(City::class , 'country_id');
    }

    public function getTitleAttribute()
    {
        return app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * هل الدولة هي المملكة العربية السعودية؟
     *
     * لا نعتمد على رقم ثابت لأنه يختلف بين البيئات، بل على الاسم أو الرمز.
     */
    public function getIsSaudiAttribute(): bool
    {
        $searchText = mb_strtolower(implode(' ', array_filter([
            $this->name_ar,
            $this->name_en,
            $this->code,
        ])));

        return str_contains($searchText, 'السعود')
            || str_contains($searchText, 'saudi')
            || (bool) preg_match('/(^|\s)sa($|\s)/i', $searchText);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_' . app()->getLocale());
    }
}
