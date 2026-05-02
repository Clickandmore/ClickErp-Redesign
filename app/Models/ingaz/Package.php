<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\ingaz\Nationality;

class Package extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        'original_price' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'price_with_tax' => 'decimal:2',
        'days_count' => 'integer',
        'duration_count' => 'integer',
        'working_hours_per_day' => 'integer',
        'is_show' => 'boolean',
    ];

    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'package_id';
    public $translationModel = PackagesTranslation::class;
    protected $with = ['translations'];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function scopeVisible($query)
    {
        return $query->where(function ($query) {
            $query->where('is_show', true)->orWhereNull('is_show');
        });
    }

    public function scopeForNationality($query, $nationalityId)
    {
        return $query->where(function ($query) use ($nationalityId) {
            $query->whereNull('nationality_id');

            if ($nationalityId) {
                $query->orWhere('nationality_id', $nationalityId);
            }
        });
    }

    public function getRentalTypeLabelAttribute(): string
    {
        return [
            'hourly' => 'بالساعات',
            'daily' => 'يومي',
            'weekly' => 'أسبوعي',
            'monthly' => 'شهري',
            'yearly' => 'سنوي',
        ][$this->rental_type ?? 'monthly'] ?? 'شهري';
    }

    public function getDurationUnitLabelAttribute(): string
    {
        return [
            'hour' => 'ساعة',
            'day' => 'يوم',
            'week' => 'أسبوع',
            'month' => 'شهر',
            'year' => 'سنة',
        ][$this->duration_unit ?? 'month'] ?? 'شهر';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
