<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentContractTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Scope للبنود النشطة فقط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope للترتيب
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
