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
}
