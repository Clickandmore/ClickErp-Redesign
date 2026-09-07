<?php

namespace App\Models;

use App\PermKeys;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\MaidJobTitles;
use App\Support\BranchSharingSettings;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class Maid extends Model
{
    use BranchScoped;


    /** السيرة الذاتية بلا فرع تبقى في المخزون المشترك المرئي لكل الفروع. */
    public function branchScopeSharesUnassigned(): bool
    {
        return BranchSharingSettings::shareMaids();
    }

    public function branchScopeViewAllPermission(): ?string
    {
        return PermKeys::MAIDS_VIEW_ALL_BRANCHES;
    }
    protected $fillable = [
        'name',
        'gender',
        'passport_num',
        'id_number',
        'id_expiry_date',
        'skills',
        'languages',
        'experience_years',
        'nationality_id',
        'cv_file',
        'price',
        'salary',
        'status',
        'available',
        'is_pinned',
        'available_at',
        'is_show',
        'cv_preview',
        'office_id',
        'image',
        'height',
        'weight',
        'birth_date',
        'age',
        'religion_id',
        'st_code',
        'int_code',
        'duration',
        'video_preview',
        'childrens_count',
        'education',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
    ];

    public function nationality()
    {
        return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'nationality_id');
    }

    public function contracts()
    {
        return $this->hasMany(\App\Models\Contract::class, 'maid_id');
    }

    public function recruitmentProcedure()
    {
        return $this->hasOne(\App\Models\MaidRecruitmentProcedure::class, 'maid_id');
    }


    public function jobTitles()
    {
        return $this->belongsToMany(JobTitle::class, 'maid_job_titles', 'maid_id', 'job_title_id');
    }
}
