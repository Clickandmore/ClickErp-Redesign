<?php

namespace App\Models\ingaz;

use App\PermKeys;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\RecruitmentOffice;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\Branch;
use App\Support\CvOptionsSettings;
use App\Support\BranchSharingSettings;
use App\Services\BranchDataSharingService;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

use App\Models\Concerns\BranchScoped;

class Maid extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $maid) {
            if ($maid->gender !== 'male') {
                $maid->has_driving_license = null;
            }

            // مرفق الرخصة لا معنى له بدون رخصة قيادة.
            if (!$maid->has_driving_license) {
                $maid->driving_license_file = null;
            }
        });
    }

    use BranchScoped;

    use HasFactory, Translatable, LogsActivity, SoftDeletes, \App\Models\Concerns\ResolvesMediaUrls;

    /** السيرة الذاتية بلا فرع تبقى في المخزون المشترك المرئي لكل الفروع. */
    public function branchScopeSharesUnassigned(): bool
    {
        return BranchSharingSettings::shareMaids();
    }

    public function branchScopeViewAllPermission(): ?string
    {
        return PermKeys::MAIDS_VIEW_ALL_BRANCHES;
    }

    public function branchScopeSharingEntity(): ?string
    {
        return BranchDataSharingService::MAIDS;
    }

    protected array $mediaUrlColumns = ['image', 'cv_file', 'cv_preview', 'video', 'driving_license_file'];

    public const REQUEST_TYPE_KNOWN = 'known';
    public const REQUEST_TYPE_SPECIFIC = 'specific';
    public const REQUEST_TYPE_CUSTOM = 'custom_spec';
    public const LANGUAGE_LEVEL_PERCENTAGES = [
        'acceptable' => 50,
        'average' => 65,
        'good' => 80,
        'excellent' => 95,
    ];

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
        'id',
        'arabia_worker_number',
        'name',
        'gender',
        'nationality_id',
        'religion_id',
        'passport_num',
        'phone',
        'relative_name',
        'relative_phone',
        'residence_city',
        'id_number',
        'id_expiry_date',
        'border_number',
        'passport_issue_date',
        'passport_issue_place',
        'passport_expiry_date',
        'price',
        'salary',
        'age',
        'height',
        'weight',
        'birth_date',
        'experience_years',
        'experience_status',
        'cooking_experience',
        'skills',
        'cv_file',
        'image',
        'video',
        'video_link',
        'cv_preview',
        'maid_cv_file',
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
        'language_levels',
        'childrens_count',
        'education',
        'education_en',
        'has_driving_license',
        'driving_license_file',
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
        'branch_id',
        'created_at',
        'submission_status',
        'submission_type',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'percentage' => 'array',
        'language_levels' => 'array',
        'price' => 'decimal:2',
        'salary' => 'decimal:2',
        'age' => 'float',
        'available' => 'boolean',
        'is_show' => 'boolean',
        'is_pinned' => 'boolean',
        'has_driving_license' => 'boolean',
        'available_at' => 'date',
        'birth_date' => 'date',
        'id_expiry_date' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'offer_price' => 'decimal:2',
        'offer_starts_at' => 'date',
        'offer_expires_at' => 'date',
        'arrival_date' => 'date',
        'deleted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function externalReferences()
    {
        return $this->hasMany(MaidExternalReference::class);
    }

    // ثوابت حالة تقديم السيرة
    const SUBMISSION_PENDING  = 'pending_review';
    const SUBMISSION_APPROVED = 'approved';
    const SUBMISSION_REJECTED = 'rejected';
    const SUBMISSION_TYPE_CREATE = 'create';
    const SUBMISSION_TYPE_UPDATE = 'update';

    public $translatedAttributes = ['marital_status'];
    protected $translationForeignKey = 'maid_id';
    public $translationModel = MaidTranslation::class;
    protected $with = ['translations'];
    protected $primaryJobTypeInstance;
    protected static array $officeSerialIndex = [];

    protected $appends = [
        'resolved_price',
        'resolved_salary',
        'effective_offer_price',
        'effective_offer_duration',
        'effective_offer_description',
        'effective_offer_starts_at',
        'effective_offer_expires_at',
        'effective_offer_source',
        'image_url',
        'cv_preview_url',
        'cv_file_url',
        'cv_download_url',
    ];

    /**
     * An individual offer always has priority. Otherwise an available worker
     * inherits the offer currently configured for her nationality.
     */
    public function getEffectiveOfferPriceAttribute()
    {
        if ($this->hasIndividualOfferValue()) {
            return $this->offer_price;
        }

        return $this->nationalityOfferApplies() ? $this->nationality?->offer_price : null;
    }

    public function getEffectiveOfferDurationAttribute(): ?string
    {
        if ($this->hasIndividualOfferValue()) {
            return $this->offer_duration;
        }

        return $this->nationalityOfferApplies() ? $this->nationality?->offer_duration : null;
    }

    public function getEffectiveOfferDescriptionAttribute(): ?string
    {
        if ($this->hasIndividualOfferValue()) {
            return $this->offer_description;
        }

        return $this->nationalityOfferApplies() ? $this->nationality?->offer_description : null;
    }

    public function getEffectiveOfferStartsAtAttribute()
    {
        if ($this->hasIndividualOfferValue()) {
            return $this->offer_starts_at;
        }

        return $this->nationalityOfferApplies() ? $this->nationality?->offer_starts_at : null;
    }

    public function getEffectiveOfferExpiresAtAttribute()
    {
        if ($this->hasIndividualOfferValue()) {
            return $this->offer_expires_at;
        }

        return $this->nationalityOfferApplies() ? $this->nationality?->offer_expires_at : null;
    }

    public function getEffectiveOfferSourceAttribute(): ?string
    {
        if ($this->hasIndividualOfferValue()) {
            return 'maid';
        }

        return $this->nationalityOfferApplies() ? 'nationality' : null;
    }

    public function hasActiveEffectiveOffer(): bool
    {
        if ($this->effective_offer_price === null) {
            return false;
        }

        $today = now()->startOfDay();

        return (!$this->effective_offer_starts_at || $this->effective_offer_starts_at->lte($today))
            && (!$this->effective_offer_expires_at || $this->effective_offer_expires_at->gte($today));
    }

    private function nationalityOfferApplies(): bool
    {
        return (bool) $this->available
            && $this->status === 'available'
            && $this->nationality?->offer_price !== null;
    }

    private function hasIndividualOfferValue(): bool
    {
        return array_key_exists('offer_price', $this->getAttributes())
            && $this->getAttributes()['offer_price'] !== null;
    }

    public function getImageURLAttribute()
    {
        return showFile($this->image);
    }

    public function getCvPreviewURLAttribute()
    {
        return $this->cvPreviewUrl();
    }

    /**
     * Generated CV keys change after regeneration. Expose a permanent signed
     * application URL so API consumers never retain a deleted R2 object key.
     */
    public function cvPreviewUrl(): ?string
    {
        $path = $this->getRawOriginal('cv_preview');

        if (!filled($path)) {
            return null;
        }

        if (
            $this->exists
            && Route::has('maids.cv-preview.shared')
            && app(\App\Services\ingaz\MaidCvPdfGenerator::class)->isGeneratedPath($path)
        ) {
            return URL::signedRoute('maids.cv-preview.shared', ['maid' => $this->getKey()]);
        }

        return showFile($path);
    }

    public function getCvFileURLAttribute()
    {
        return showFile($this->cv_file);
    }

    public function getCvDownloadURLAttribute(): ?string
    {
        if (!filled($this->getRawOriginal('cv_preview'))
            || !$this->exists
            || !Route::has('maids.cv-preview.shared')) {
            return null;
        }

        // Download the generated preview in its original format (PNG/SVG/etc.)
        // instead of routing images through the PDF conversion endpoint.
        return URL::signedRoute('maids.cv-preview.shared', [
            'maid' => $this->getKey(),
            'download' => 1,
        ]);
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

    public function getLanguageLevelsAttribute($value): array
    {
        return $this->normalizeLanguageLevels($value);
    }

    public function setLanguageLevelsAttribute($value): void
    {
        $this->attributes['language_levels'] = json_encode(
            $this->normalizeLanguageLevels($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public static function percentageForLanguageLevel(string $level): ?int
    {
        return self::LANGUAGE_LEVEL_PERCENTAGES[$level] ?? null;
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

    protected function normalizeLanguageLevels($value): array
    {
        $value = $this->decodeJsonLikeValue($value);

        if (!is_array($value)) {
            return [];
        }

        $levels = [];
        foreach ($value as $language => $level) {
            $language = $this->normalizeLanguageKey((string) $language);
            $level = strtolower(trim((string) $level));

            if ($language !== '' && array_key_exists($level, self::LANGUAGE_LEVEL_PERCENTAGES)) {
                $levels[$language] = $level;
            }
        }

        return $levels;
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

    public function scopePendingSubmission($query)
    {
        return $query->where('submission_status', self::SUBMISSION_PENDING);
    }

    public function scopeApprovedSubmission($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('submission_status')
                ->orWhere('submission_status', self::SUBMISSION_APPROVED);
        });
    }

    public function scopeFromExternalOffice($query)
    {
        return $query->whereNotNull('office_id');
    }

    /**
     * السير التي لا يوجد عليها طلب قائم من الموقع الخارجي أو التطبيق.
     * طلب الاستقدام أو الإيجار أو نقل الخدمات يحجز السيرة فتختفي من الموقع.
     */
    public function scopeWithoutActiveOrders($query)
    {
        return $query->whereDoesntHave('orders', function ($orderQuery) {
            $orderQuery->withoutGlobalScopes()->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhereNotIn('status', Order::nonBlockingMaidAssignmentStatuses());
            });
        });
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

    public function statusChangeRequests()
    {
        return $this->hasMany(MaidStatusChangeRequest::class);
    }

    public function pendingStatusChangeRequest()
    {
        return $this->hasOne(MaidStatusChangeRequest::class)
            ->where('status', MaidStatusChangeRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    public function recruitmentProcedure()
    {
        return $this->hasOne(\App\Models\MaidRecruitmentProcedure::class, 'maid_id');
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

    public function hasFinancialEditLock(): bool
    {
        if (!$this->exists || !$this->id) {
            return false;
        }

        if ($this->hasActiveOrderForEditLock()) {
            return true;
        }

        if ($this->hasActiveRecruitmentContractForEditLock()) {
            return true;
        }

        if ($this->hasRecruitmentInvoiceForEditLock()) {
            return true;
        }

        if ($this->hasRentalInvoiceForEditLock()) {
            return true;
        }

        if ($this->hasMaidAccountingConstraintForEditLock()) {
            return true;
        }

        return false;
    }

    private function hasActiveOrderForEditLock(): bool
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'maid_id')) {
            return false;
        }

        return $this->orders()
            ->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])
            ->exists();
    }

    private function hasActiveRecruitmentContractForEditLock(): bool
    {
        if (!Schema::hasTable('contracts') || !Schema::hasColumn('contracts', 'maid_id')) {
            return false;
        }

        return $this->contracts()
            ->whereIn('status', [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value,
            ])
            ->exists();
    }

    public function hasExternalOfficeDispatchEditLock(): bool
    {
        if (
            !$this->exists
            || !$this->id
            || !Schema::hasTable('contracts')
            || !Schema::hasTable('contract_external_office_dispatches')
        ) {
            return false;
        }

        return DB::table('contract_external_office_dispatches')
            ->join('contracts', 'contracts.id', '=', 'contract_external_office_dispatches.contract_id')
            ->where('contracts.maid_id', $this->id)
            ->exists();
    }

    private function hasRecruitmentInvoiceForEditLock(): bool
    {
        if (
            !Schema::hasTable('contracts')
            || !Schema::hasTable('invoices')
            || !Schema::hasColumn('contracts', 'maid_id')
            || !Schema::hasColumn('invoices', 'contract_id')
        ) {
            return false;
        }

        return DB::table('invoices')
            ->join('contracts', 'contracts.id', '=', 'invoices.contract_id')
            ->where('contracts.maid_id', $this->id)
            ->exists();
    }

    private function hasRentalInvoiceForEditLock(): bool
    {
        if (
            !Schema::hasTable('rental_contracts')
            || !Schema::hasTable('rental_contract_invoices')
            || !Schema::hasColumn('rental_contracts', 'maid_id')
            || !Schema::hasColumn('rental_contract_invoices', 'rental_contract_id')
        ) {
            return false;
        }

        return DB::table('rental_contract_invoices')
            ->join('rental_contracts', 'rental_contracts.id', '=', 'rental_contract_invoices.rental_contract_id')
            ->where('rental_contracts.maid_id', $this->id)
            ->exists();
    }

    private function hasMaidAccountingConstraintForEditLock(): bool
    {
        if (
            Schema::hasTable('rental_financial_adjustments')
            && Schema::hasColumn('rental_financial_adjustments', 'maid_id')
            && Schema::hasColumn('rental_financial_adjustments', 'constraint_id')
            && DB::table('rental_financial_adjustments')
                ->where('maid_id', $this->id)
                ->whereNotNull('constraint_id')
                ->exists()
        ) {
            return true;
        }

        if (
            Schema::hasTable('shelter_living_expenses')
            && Schema::hasColumn('shelter_living_expenses', 'maid_id')
            && Schema::hasColumn('shelter_living_expenses', 'constraint_id')
            && DB::table('shelter_living_expenses')
                ->where('maid_id', $this->id)
                ->whereNotNull('constraint_id')
                ->exists()
        ) {
            return true;
        }

        return false;
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

    public function scopeWillingToWorkInsideShelter(Builder $query): Builder
    {
        return $query->whereHas('contracts', function ($contractQuery) {
            $contractQuery->insideShelter()
                ->whereHas('info', function ($infoQuery) {
                    $infoQuery->where(function ($willingQuery) {
                        $willingQuery->where('wants_to_work', 'yes')
                            ->orWhere('wants_to_work', 1);
                    });
                });
        });
    }

    public function getRequestTypeLabelAttribute(): string
    {
        $requestType = self::resolveRequestType($this->request_type);

        return self::getRequestTypeOptions()[$requestType] ?? helperTrans('ingaz.request_type_custom');
    }

    /**
     * Imported rows carry the gender in Arabic or as a single letter, so match
     * the same spellings the dashboard statistics already normalise.
     */
    public function getGenderLabelAttribute(): ?string
    {
        $gender = strtolower(trim((string) $this->gender));

        return match (true) {
            in_array($gender, ['female', 'f', '1', 'أنثى', 'انثى'], true) => 'أنثى',
            in_array($gender, ['male', 'm', '0', 'ذكر'], true) => 'ذكر',
            default => null,
        };
    }

    /**
     * جنس العامل كمفتاح موحّد (male / female) لاختيار صيغة النصوص المذكّرة أو المؤنثة.
     * الافتراضي أنثى لأن أغلب السير لعاملات ولأن القيمة قد تكون فارغة في السجلات القديمة.
     */
    public function getGenderKeyAttribute(): string
    {
        return $this->gender_label === 'ذكر' ? 'male' : 'female';
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

    protected function isRentalJobType(?JobType $jobType): bool
    {
        if (!$jobType) {
            return false;
        }

        if ((int) $jobType->id === 2) {
            return true;
        }

        $title = $this->normalizeJobTypeTitle($jobType->title);

        return str_contains($title, 'ايجار')
            || str_contains($title, 'تاجير')
            || str_contains($title, 'rent');
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

        // Rental is an exclusive public service by default: instant availability is
        // only metadata and never moves or duplicates a rental worker to transfer.
        // Enabling "إتاحة سيرة عاملة الإيجار لنقل الخدمات" lists an instant-ready
        // rental worker under both rental and transfer.
        $rentalJobTypes = $jobTypes
            ->filter(fn($jobType) => $this->isRentalJobType($jobType))
            ->values();

        if ($rentalJobTypes->isNotEmpty()) {
            if (!CvOptionsSettings::rentalTransferEnabled() || !$this->isInstantShelterListing()) {
                return $rentalJobTypes;
            }

            $rentalTransferJobTypes = $jobTypes
                ->filter(fn($jobType) => $this->isInstantJobType($jobType))
                ->values();

            if ($rentalTransferJobTypes->isEmpty()) {
                $rentalTransferJobTypes = new EloquentCollection([$this->makeFallbackInstantJobType()]);
            }

            return new EloquentCollection(
                $rentalJobTypes->concat($rentalTransferJobTypes)->unique('id')->values()->all()
            );
        }

        if (!$this->isInstantShelterListing()) {
            return $jobTypes;
        }

        $instantJobTypes = $jobTypes
            ->filter(fn($jobType) => $this->isInstantJobType($jobType))
            ->values();

        if ($instantJobTypes->isEmpty()) {
            $instantJobTypes = new EloquentCollection([$this->makeFallbackInstantJobType()]);
        }

        return new EloquentCollection($instantJobTypes->unique('id')->values()->all());
    }

    /**
     * سيرة عاملة إيجار مُتاحة أيضًا لنقل الخدمات على الموقع الخارجي.
     * تُستخدم لعرض ملاحظة توضيحية للعميل على بطاقة السيرة.
     */
    public function getIsRentalTransferListingAttribute(): bool
    {
        if (!CvOptionsSettings::rentalTransferEnabled()) {
            return false;
        }

        $hasRentalJobType = $this->getLoadedJobTypes()
            ->contains(fn($jobType) => $this->isRentalJobType($jobType));

        return $hasRentalJobType && $this->isInstantShelterListing();
    }

    public function getEffectiveJobTypeAttribute(): ?JobType
    {
        return $this->site_listing_job_types->first() ?? $this->getLoadedJobTypes()->first();
    }

    protected function getPrimaryJobType()
    {
        $jobTypes = $this->getLoadedJobTypes();

        $rentalJobType = $jobTypes->first(fn($jobType) => $this->isRentalJobType($jobType));

        if ($rentalJobType) {
            return $rentalJobType;
        }

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
        $jobType = $this->getPrimaryJobType();
        $title = $this->normalizeJobTypeTitle($jobType?->title);

        if ($title === '') {
            return 'recruitment';
        }

        if (str_contains($title, 'ايجار') || str_contains($title, 'تاجير') || str_contains($title, 'rent')) {
            return 'rental';
        }

        if ($this->isInstantShelterListing()) {
            return 'transfer';
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

    /**
     * نوع الطلب (job_type_id) المناسب للخدمة التي طلبها الموقع الخارجي.
     * يرجع null إذا لم تكن العاملة معروضة تحت هذه الخدمة، فيبقى السلوك القديم كما هو.
     */
    public function resolveJobTypeIdForService(?string $serviceType): ?int
    {
        $jobTypeId = match ($this->normalizeServiceType($serviceType)) {
            'recruitment' => 1,
            'rental' => 2,
            'transfer' => 3,
            default => null,
        };

        if ($jobTypeId === null) {
            return null;
        }

        $attachedIds = $this->jobTypes
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($attachedIds->contains($jobTypeId)) {
            return $jobTypeId;
        }

        // سيرة عاملة الإيجار المتاحة للاستلام الفوري تظهر أيضًا ضمن نقل الخدمات على
        // الموقع الخارجي دون أن يكون النوع 3 مرتبطًا بها في maid_jobs.
        if ($jobTypeId === 3 && $this->isInstantShelterListing()) {
            return 3;
        }

        return null;
    }

    /**
     * التكاليف المعروضة على بطاقة السيرة في الموقع الخارجي، كل خدمة بسعرها.
     * قيمة نقل الخدمات تأتي من الجنسية (service_transportation_cost) لا من سعر الاستقدام.
     */
    public function serviceCostsForSite(): array
    {
        $transferCost = $this->nationality?->service_transportation_cost;

        return [
            'recruitment_cost' => $this->resolvePriceForService('recruitment'),
            'rental_cost' => $this->resolvePriceForService('rental'),
            'transfer_cost' => ($transferCost !== null && (float) $transferCost > 0)
                ? (float) $transferCost
                : null,
        ];
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
