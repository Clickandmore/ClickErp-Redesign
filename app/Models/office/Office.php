<?php

namespace App\Models\office;

use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\OfficeAttachment;
use App\Models\RecruitmentOfficeTranslation;
use Astrotomic\Translatable\Translatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\ingaz\JobTitlesTranslation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;

class Office extends Model
{
    use HasFactory, Translatable, LogsActivity;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'rec_office_id';
    public $translationModel = RecruitmentOfficeTranslation::class;
    protected $with = ['translations'];
    protected $table = 'recruitment_offices';

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function jobTypes()
    {
        return $this->belongsToMany(JobType::class, 'jop_type_offices', 'office_id', 'jop_type_id');
    }

    public function officeManager()
    {
        return $this->hasOne(AdminOffice::class, 'office_id');
    }

    public function file()
    {
        return $this->hasOne(OfficeFile::class, 'recruitment_office_id');
    }

    public function maids()
    {
        return $this->hasMany(Maid::class, 'office_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('office')
            ->logOnly([
                'title',
                'nationality_id',
                'phone_office',
                'license_number',
                'street',
                'manager_name',
                'manager_phone',
                'manager_email',
                'bank_address',
                'bank_name',
                'bank_account_name',
                'bank_account',
                'bank_swift_code',
                'bank_iban',
                'cost_of_dollar',
                'cost_of_other',
                'status',
                'is_show',
                'account_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function attachment()
    {
        return $this->hasOne(OfficeAttachment::class, 'office_id');
    }
}
