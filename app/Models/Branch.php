<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'working_hours' => 'array',
        'zoho_salesiq_settings' => 'array',
    ];

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : ($this->name_en ?: $this->name_ar);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    public function manager()
    {
        return $this->hasOne(\App\Models\auth\Admin::class, 'branch_id')
            ->whereIn('admin_type', ['admin', 'super_admin'])
            ->whereNull('deleted_at')
            ->orderBy('id');
    }

    public function admins()
    {
        return $this->hasMany(\App\Models\auth\Admin::class, 'branch_id')
            ->notExternalOfficeAgents();
    }
}
