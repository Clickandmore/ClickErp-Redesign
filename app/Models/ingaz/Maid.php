<?php

namespace App\Models\ingaz;

use App\Http\Controllers\ingaz\RecruitmentOffice;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Maid extends Model
{
    use HasFactory, Translatable, LogsActivity;

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
        'offer_expires_at'
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
        'offer_expires_at' => 'date'
    ];

    public $translatedAttributes = ['marital_status', 'languages'];
    protected $translationForeignKey = 'maid_id';
    public $translationModel = MaidTranslation::class;
    protected $with = ['translations'];
    protected $primaryJobTypeInstance;
    protected static array $officeSerialIndex = [];


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

    public function office()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'office_id');
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

    public function getCvCodeAttribute(): ?string
    {
        $officeName = trim($this->office?->title ?? '');
        if (!$this->office_id || $officeName === '') {
            return null;
        }

        $prefix = $this->buildOfficeCodePrefix($officeName);
        $serial = $this->resolveOfficeSerialNumber();

        if ($serial === null) {
            return $prefix;
        }

        return $prefix . str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
    }

    protected function buildOfficeCodePrefix(string $officeName): string
    {
        $lettersOnly = preg_replace('/[^\p{L}]/u', '', $officeName);
        $compact = $lettersOnly !== '' ? $lettersOnly : preg_replace('/\s+/u', '', $officeName);
        $chunk = mb_substr($compact !== '' ? $compact : $officeName, 0, 3, 'UTF-8');

        $prefix = mb_strtoupper($chunk ?: 'OFF', 'UTF-8');

        return $prefix !== '' ? $prefix : 'OFF';
    }

    protected function resolveOfficeSerialNumber(): ?int
    {
        if (!$this->office_id) {
            return null;
        }

        $officeId = (int) $this->office_id;

        if (!array_key_exists($officeId, static::$officeSerialIndex)) {
            $orderedIds = static::query()
                ->where('office_id', $officeId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            static::$officeSerialIndex[$officeId] = array_flip($orderedIds);
        }

        $map = static::$officeSerialIndex[$officeId] ?? [];

        if (!array_key_exists($this->id, $map)) {
            $position = static::query()
                ->where('office_id', $officeId)
                ->where('id', '<=', $this->id)
                ->count();

            if ($position === 0) {
                return null;
            }

            static::$officeSerialIndex[$officeId][$this->id] = $position - 1;

            return $position;
        }

        return $map[$this->id] + 1;
    }

    protected function getPrimaryJobType()
    {
        if ($this->relationLoaded('jobTypes')) {
            return $this->jobTypes->first();
        }

        if ($this->primaryJobTypeInstance === null) {
            $this->primaryJobTypeInstance = $this->jobTypes()->first();
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

        if (str_contains($title, 'استلام') || str_contains($title, 'instant')) {
            return 'transfer';
        }

        return 'recruitment';
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
