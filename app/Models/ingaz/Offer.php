<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Offer extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_GENERAL = 'general';
    public const TYPE_NATIONALITY = 'nationality';
    public const TYPE_MAID = 'maid';
    public const TYPE_NATIONALITY_MAID = 'nationality_maid';

    protected $fillable = [
        'type',
        'title',
        'description',
        'image',
        'price_before_discount',
        'price_after_discount',
        'discount_percentage',
        'url',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_before_discount' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_GENERAL => 'عرض عام',
            self::TYPE_NATIONALITY => 'عرض مرتبط بجنسية',
            self::TYPE_MAID => 'عرض مرتبط بسيرة ذاتية',
            self::TYPE_NATIONALITY_MAID => 'عرض مرتبط بجنسية وسيرة ذاتية معًا',
        ];
    }

    public function nationalities()
    {
        return $this->belongsToMany(Nationality::class, 'offer_nationality')->withTimestamps();
    }

    public function maids()
    {
        return $this->belongsToMany(Maid::class, 'maid_offer')->withTimestamps();
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? showFile($this->image) : null;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
