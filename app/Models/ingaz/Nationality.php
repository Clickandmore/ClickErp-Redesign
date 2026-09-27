<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Nationality extends Model
{
    use Translatable, HasFactory, LogsActivity, \App\Models\Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['logo'];

    protected $guarded = [];
    protected $fillable = [
        'logo',
        'phone_code',
        'sort_order',
        'price',
        'offer_price',
        'offer_duration',
        'offer_description',
        'offer_starts_at',
        'offer_expires_at',
        'direct_recruitment_cost',
        'external_recruitment_cost',
        'government_cost',
        'salary_with_experience',
        'salary_with_no_experience',
        'service_transportation_cost',
        'rental_cost',
        'monthly_salary',
        'expected_duration',
        'is_show',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'offer_starts_at' => 'date',
        'offer_expires_at' => 'date',
        'direct_recruitment_cost' => 'decimal:2',
        'external_recruitment_cost' => 'decimal:2',
        'government_cost' => 'decimal:2',
        'service_transportation_cost' => 'decimal:2',
        'rental_cost' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'expected_duration' => 'integer',
        'is_show' => 'boolean',
    ];

    public $translatedAttributes = ['title', 'sub_title', 'description'];
    protected $translationForeignKey = 'nationality_id';
    public $translationModel = NationalitiesTranslation::class;
    protected $with = ['translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    protected $appends = [
        'logo_url',
    ];

    public function getLogoURLAttribute()
    {
        // ملفات R2 تحتاج رابطًا مطلقًا، والمحلية تبقى مسارًا نسبيًا كما يتوقعها الموقع الخارجي.
        return apiFileUrl($this->logo);
    }

    public function getDisplayNameAttribute(): string
    {
        $locale = strtolower(substr((string) app()->getLocale(), 0, 2));
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
        $translation = $this->translations->firstWhere('locale', $locale);
        $name = trim((string) ($translation?->sub_title ?: $translation?->title));

        if ($name !== '') {
            return $name;
        }

        foreach ($this->translations as $translation) {
            $name = trim((string) ($translation->sub_title ?: $translation->title));

            if ($name !== '') {
                return $name;
            }
        }

        return (string) $this->id;
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function maids()
    {
        return $this->hasMany(Maid::class, 'nationality_id');
    }

    public function getOfferStateAttribute(): string
    {
        if ($this->offer_price === null) {
            return 'none';
        }

        $today = now()->startOfDay();

        if ($this->offer_starts_at && $this->offer_starts_at->gt($today)) {
            return 'scheduled';
        }

        if ($this->offer_expires_at && $this->offer_expires_at->lt($today)) {
            return 'expired';
        }

        return 'active';
    }
}
