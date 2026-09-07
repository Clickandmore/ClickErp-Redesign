<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalContractTerm extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'section_title',
        'section_order',
        'term_number',
        'term_content',
        'term_order',
        'page_number',
        'is_active'
    ];

    /**
     * Scope للحصول على البنود النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للحصول على البنود حسب الصفحة
     */
    public function scopeByPage($query, $page)
    {
        return $query->where('page_number', $page);
    }

    /**
     * الحصول على البنود مرتبة
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('section_order')
                     ->orderBy('term_order');
    }
}
