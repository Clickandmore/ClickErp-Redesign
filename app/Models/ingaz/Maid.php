<?php

namespace App\Models\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\RecruitmentOffice;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\ingaz\User;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maid extends Model
{
    use HasFactory, Translatable, LogsActivity, SoftDeletes;

    public const REQUEST_TYPE_KNOWN = 'known';
    public const REQUEST_TYPE_SPECIFIC = 'specific';
    public const REQUEST_TYPE_CUSTOM = 'custom_spec';

    // Status constants with translations
    const STATUS_AVAILABLE = [
        'ar' => 'متاحة',
        'en' => 'Available'
    ];
    const STATUS_RESERVED = [
        'ar' => 'محجوزة',
        'en' => 'Reserved'
    ];
    const STATUS_CONTRACTED = [
        'ar' => 'تم التعاقد',
        'en' => 'Contracted'
    ];
    const STATUS_IN_ACCOMMODATION = [
        'ar' => 'داخل الايواء',
        'en' => 'In Accommodation'
    ];

    protected $fillable = [
        'name',
        'nationality_id',
        'religion_id',
        'passport_num',
        'id_number',
        'id_expiry_date',
        'price',
        'salary',
        'age',
        'height',
        'weight',
        'birth_date',
        'experience_years',
        'experience_status',
        'skills',
        'cv_file',
        'image',
        'video',
        'video_link',
        'cv_preview',
        'office_id',
        'status',
        'back_out_reason',
        'maid_accommodation_type',
        'added_by',
        'request_type',
        'available',
        'available_at',
        'is_show',
        'is_pinned',
        'percentage',
        'childrens_count',
        'additional_notes',
        'duration',
        'languages',
        'offer_price',
        'offer_duration',
        'offer_description',
        'offer_starts_at',
        'offer_expires_at',
        'deleted_by',
        'arrival_date',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'percentage' => 'array',
        'price' => 'decimal:2',
        'salary' => 'decimal:2',
        'age' => 'float',
        'available' => 'boolean',
        'is_show' => 'boolean',
        'is_pinned' => 'boolean',
        'available_at' => 'date',
        'birth_date' => 'date',
        'id_expiry_date' => 'date',
        'offer_price' => 'decimal:2',
        'offer_starts_at' => 'date',
        'offer_expires_at' => 'date',
        'arrival_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public $translatedAttributes = ['marital_status'];
    protected $translationForeignKey = 'maid_id';
    public $translationModel = MaidTranslation::class;
    protected $with = ['translations'];
    protected $primaryJobTypeInstance;
    protected static array $officeSerialIndex = [];

    protected $appends = [
        'resolved_price',
        'resolved_salary',
        'image_url',
        'cv_preview_url',
        'cv_file_url',
    ];

    public function getImageURLAttribute()
    {
        return asset('storage/' . $this->image) ?? null;
    }

    public function getCvPreviewURLAttribute()
    {
        return asset('storage/' . $this->cv_preview);
    }

    public function getCvFileURLAttribute()
    {
        return asset('storage/' . $this->cv_file);
    }

    public function getLanguagesAttribute($value): array
    {
        $languages = $this->normalizeLanguageList($value);
        if ($languages !== []) {
            return $languages;
        }

        foreach ($this->languageFallbackLocales() as $locale) {
            $translation = $this->relationLoaded('translations')
                ? $this->translations->firstWhere($this->getLocaleKey(), $locale)
                : $this->translations()->where($this->getLocaleKey(), $locale)->first();

            $languages = $this->normalizeLanguageList($translation?->languages);
            if ($languages !== []) {
                return $languages;
            }
        }

        return [];
    }

    public function setLanguagesAttribute($value): void
    {
        $this->attributes['languages'] = json_encode(
            $this->normalizeLanguageList($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function getPercentageAttribute($value): array
    {
        return $this->normalizeLanguagePercentages($value);
    }

    public function setPercentageAttribute($value): void
    {
        $this->attributes['percentage'] = json_encode(
            $this->normalizeLanguagePercentages($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    protected function languageFallbackLocales(): array
    {
        return collect([
            app()->getLocale(),
            config('app.fallback_locale'),
            'ar',
            'en',
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeLanguageList($value): array
    {
        $value = $this->decodeJsonLikeValue($value);

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            $value = preg_split('/\s*,\s*/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $languages = [];
        foreach ($value as $key => $language) {
            if (is_array($language)) {
                $language = $language['title'] ?? $language['value'] ?? null;
            }

            if ($this->isCheckedLanguageValue($language) && !is_int($key)) {
                $language = $key;
            }

            if ($language === null || $language === false) {
                continue;
            }

            $language = $this->normalizeLanguageKey((string) $language);
            if ($language !== '') {
                $languages[] = $language;
            }
        }

        return array_values(array_unique($languages));
    }

    protected function normalizeLanguagePercentages($value): array
    {
        $value = $this->decodeJsonLikeValue($value);

        if (!is_array($value)) {
            return [];
        }

        $percentages = [];
        foreach ($value as $language => $percentage) {
            if ($percentage === null || $percentage === '') {
                continue;
            }

            $language = $this->normalizeLanguageKey((string) $language);
            if ($language !== '') {
                $percentages[$language] = $percentage;
            }
        }

        return $percentages;
    }

    protected function decodeJsonLikeValue($value)
    {
        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $value;
            }

            $value = $decoded;
        }

        return $value;
    }

    protected function isCheckedLanguageValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    protected function normalizeLanguageKey(string $language): string
    {
        $language = strtolower(trim($language));

        return match ($language) {
            'ar' => 'arabic',
            'en' => 'english',
            'amh' => 'amharic',
            'amharic_language' => 'amharic',
            'mother_language' => 'mother',
            default => $language,
        };
    }

    ####### relations
    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }

    public function job_types()
    {
        return $this->hasMany(MaidJob::class, 'maid_id');
    }

    public function job_titles()
    {
        return $this->hasMany(MaidJobTitles::class, 'maid_id');
    }

    public function jobTypes()
    {
        return $this->belongsToMany(JobType::class, 'maid_jobs', 'maid_id', 'job_type_id');
    }

    public function jobTitles()
    {
        return $this->belongsToMany(JobTitle::class, 'maid_job_titles', 'maid_id', 'job_title_id');
    }

    public function rentalFinancialAdjustments()
    {
        return $this->hasMany(\App\Models\RentalFinancialAdjustment::class, 'maid_id');
    }

    public function expertises()
    {
        return $this->hasMany(\App\Models\MaidExpertise::class, 'maid_id');
    }
    ####### scopes
    public function scopeShown($query)
    {
        return $query->where('is_show', true);
    }

    public function scopeAvailableFromToday($query)
    {
        return $query->where('available_at', '>=', now()->toDateString());
    }
    public function scopeNotAvailable($query)
    {
        return $query->where('available_at', '<', now()->toDateString());
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'maid_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'maid_id');
    }

    public function rentalContracts()
    {
        return $this->hasMany(\App\Models\RentalContract::class, 'maid_id');
    }

    public function attachments()
    {
        return $this->hasMany(MaidAttachment::class, 'maid_id')->latest();
    }

    public function office()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'office_id');
    }
    public function recruitmentOffice()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'office_id');
    }

    public function creatorActivity()
    {
        return $this->morphOne(Activity::class, 'subject')
            ->where('event', 'created')
            ->latestOfMany();
    }

    public function getAddedByNameAttribute(): ?string
    {
        return $this->creatorActivity?->causer?->name;
    }

    public function addedBy()
    {
        return $this->belongsTo(Admin::class, 'added_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(Admin::class, 'deleted_by');
    }

    public function unavailableRecords()
    {
        return $this->hasMany(\App\Models\MaidUnavailableRecord::class, 'maid_id')->orderBy('created_at', 'desc');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->image) {
            return showFile($this->image);
        }
        return asset('images/no-image.png');
    }

    public static function getRequestTypeOptions(): array
    {
        return [
            self::REQUEST_TYPE_KNOWN => helperTrans('ingaz.request_type_known'),
            self::REQUEST_TYPE_SPECIFIC => helperTrans('ingaz.request_type_specific'),
            self::REQUEST_TYPE_CUSTOM => helperTrans('ingaz.request_type_custom'),
        ];
    }

    public static function normalizeRequestType(?string $requestType): ?string
    {
        $requestType = is_string($requestType) ? trim($requestType) : $requestType;

        return array_key_exists($requestType, self::getRequestTypeOptions())
            ? $requestType
            : null;
    }

    public static function resolveRequestType(?string $requestType): string
    {
        return self::normalizeRequestType($requestType) ?? self::REQUEST_TYPE_CUSTOM;
    }

    public function scopeOfRequestType(Builder $query, ?string $requestType): Builder
    {
        $requestType = self::normalizeRequestType($requestType);

        if (!$requestType) {
            return $query;
        }

        if ($requestType === self::REQUEST_TYPE_CUSTOM) {
            return $query->where(function (Builder $innerQuery) {
                $innerQuery->where('request_type', self::REQUEST_TYPE_CUSTOM)
                    ->orWhereNull('request_type')
                    ->orWhere('request_type', '');
            });
        }

        return $query->where('request_type', $requestType);
    }

    public function getRequestTypeLabelAttribute(): string
    {
        $requestType = self::resolveRequestType($this->request_type);

        return self::getRequestTypeOptions()[$requestType] ?? helperTrans('ingaz.request_type_custom');
    }

    // public function getCvCodeAttribute(): ?string
    // {
    //     if (!$this->office_id || !$this->office?->title) {
    //         return null;
    //     }

    //     // أول 3 أحرف من اسم المكتب
    //     $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->office->title), 0, 3));
    //     if ($prefix === '') $prefix = 'OFF';

    //     // رقم تسلسلي حسب عدد العاملات في نفس المكتب
    //     $count = static::where('office_id', $this->office_id)
    //         ->where('id', '<=', $this->id)
    //         ->count();

    //     return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    // }

    protected function getLoadedJobTypes()
    {
        if (!$this->relationLoaded('jobTypes')) {
            $this->load('jobTypes');
        }

        return $this->jobTypes;
    }

    protected function normalizeBooleanLikeValue($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes'], true);
    }

    protected function normalizeWantsToWorkValue($value): bool
    {
        if (is_string($value)) {
            $value = mb_strtolower(trim($value));
        }

        return in_array($value, ['yes', true, 1, '1'], true);
    }

    protected function isInstantJobType(?JobType $jobType): bool
    {
        if (!$jobType) {
            return false;
        }

        if ((int) $jobType->id === 3) {
            return true;
        }

        $title = $this->normalizeJobTypeTitle($jobType->title);

        return str_contains($title, 'استلام')
            || str_contains($title, 'فوري')
            || str_contains($title, 'instant')
            || str_contains($title, 'immediate')
            || str_contains($title, 'transfer')
            || str_contains($title, 'نقل');
    }

    protected function makeFallbackInstantJobType(): JobType
    {
        return new JobType([
            'id' => 3,
            'title' => 'استلام فوري',
        ]);
    }

    public function isInstantShelterListing(): bool
    {
        if ($this->relationLoaded('contracts')) {
            return $this->contracts->contains(function (Contract $contract) {
                $info = $contract->relationLoaded('info') ? $contract->info : $contract->info()->first();

                if (!$info || $contract->status !== ContractStatusEnum::Shelter->value) {
                    return false;
                }

                if ($this->normalizeBooleanLikeValue($info->is_outside_shelter ?? false)) {
                    return false;
                }

                return $this->normalizeBooleanLikeValue($info->is_available_for_instant ?? false)
                    && $this->normalizeWantsToWorkValue($info->wants_to_work ?? null);
            });
        }

        return $this->contracts()
            ->where('status', ContractStatusEnum::Shelter->value)
            ->whereHas('info', function ($infoQuery) {
                $infoQuery
                    ->where(function ($outsideShelterQuery) {
                        $outsideShelterQuery->whereNull('is_outside_shelter')
                            ->orWhere('is_outside_shelter', false);
                    })
                    ->whereIn('is_available_for_instant', [true, 1, '1'])
                    ->where(function ($wantsToWorkQuery) {
                        $wantsToWorkQuery->whereRaw('LOWER(TRIM(COALESCE(wants_to_work, ""))) = ?', ['yes'])
                            ->orWhereIn('wants_to_work', [1, '1', true]);
                    });
            })
            ->exists();
    }

    public function getIsInstantShelterListingAttribute(): bool
    {
        return $this->isInstantShelterListing();
    }

    public function getSiteListingJobTypesAttribute()
    {
        $jobTypes = $this->getLoadedJobTypes();

        if (!$this->isInstantShelterListing()) {
            return $jobTypes;
        }

        $instantJobTypes = $jobTypes
            ->filter(fn($jobType) => $this->isInstantJobType($jobType))
            ->values();

        if ($instantJobTypes->isNotEmpty()) {
            return $instantJobTypes;
        }

        return new EloquentCollection([$this->makeFallbackInstantJobType()]);
    }

    public function getEffectiveJobTypeAttribute(): ?JobType
    {
        return $this->site_listing_job_types->first() ?? $this->getLoadedJobTypes()->first();
    }

    protected function getPrimaryJobType()
    {
        $jobTypes = $this->getLoadedJobTypes();

        if ($this->isInstantShelterListing()) {
            $instantJobType = $jobTypes->first(fn($jobType) => $this->isInstantJobType($jobType));

            if ($instantJobType) {
                return $instantJobType;
            }

            return $this->makeFallbackInstantJobType();
        }

        if ($this->primaryJobTypeInstance === null) {
            $this->primaryJobTypeInstance = $jobTypes->first();
        }

        return $this->primaryJobTypeInstance;
    }

    protected function normalizeJobTypeTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        $normalized = mb_strtolower(trim($title));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);

        return preg_replace('/\s+/', ' ', $normalized);
    }

    protected function detectJobCategory(): string
    {
        if ($this->isInstantShelterListing()) {
            return 'transfer';
        }

        $jobType = $this->getPrimaryJobType();
        $title = $this->normalizeJobTypeTitle($jobType?->title);

        if ($title === '') {
            return 'recruitment';
        }

        if (str_contains($title, 'ايجار') || str_contains($title, 'تاجير') || str_contains($title, 'rent')) {
            return 'rental';
        }

        if (str_contains($title, 'استلام') || str_contains($title, 'instant')) {
            return 'transfer';
        }

        return 'recruitment';
    }

    protected function normalizeServiceType(?string $serviceType): ?string
    {
        if (!is_string($serviceType) || trim($serviceType) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($serviceType));

        return match ($normalized) {
            'egar', 'rental', 'rent' => 'rental',
            'transfer', 'instant', 'immediate' => 'transfer',
            'estqdam', 'recruitment', 'recruit' => 'recruitment',
            default => null,
        };
    }

    protected function firstPositiveNumericValue(...$values): ?float
    {
        foreach ($values as $value) {
            if ($value !== null && (float) $value > 0) {
                return (float) $value;
            }
        }

        return null;
    }

    public function resolvePriceForService(?string $serviceType = null): ?float
    {
        $serviceType = $this->normalizeServiceType($serviceType);
        $nationality = $this->nationality;

        return match ($serviceType) {
            'rental' => $this->firstPositiveNumericValue(
                $nationality?->rental_cost,
                $this->price,
                $nationality?->price
            ),
            'transfer' => $this->firstPositiveNumericValue(
                $nationality?->service_transportation_cost,
                $this->price,
                $nationality?->price
            ),
            'recruitment' => $this->firstPositiveNumericValue(
                $this->price,
                $nationality?->price
            ),
            default => $this->resolved_price,
        };
    }

    public function getResolvedPriceAttribute(): ?float
    {
        if ($this->price !== null && (float) $this->price > 0) {
            return (float) $this->price;
        }

        $nationality = $this->nationality;
        if (!$nationality) {
            return null;
        }

        $category = $this->detectJobCategory();

        $candidate = match ($category) {
            'rental' => $nationality->rental_cost,
            'transfer' => $nationality->service_transportation_cost,
            default => $nationality->price,
        };

        return ($candidate !== null && (float) $candidate > 0) ? (float) $candidate : null;
    }

    public function getResolvedSalaryAttribute(): ?float
    {
        if ($this->salary !== null && (float) $this->salary > 0) {
            return (float) $this->salary;
        }

        $nationality = $this->nationality;
        if (!$nationality) {
            return null;
        }

        if ($this->experience_status === 'has_experience' && $nationality->salary_with_experience) {
            return (float) $nationality->salary_with_experience;
        }

        if ($this->experience_status !== 'has_experience' && $nationality->salary_with_no_experience) {
            return (float) $nationality->salary_with_no_experience;
        }

        return $nationality->monthly_salary ? (float) $nationality->monthly_salary : null;
    }
}
