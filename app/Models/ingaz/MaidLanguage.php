<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaidLanguage extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['title'];

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'en'
            ? ($this->title_en ?: $this->title_ar)
            : ($this->title_ar ?: $this->title_en);
    }
}
