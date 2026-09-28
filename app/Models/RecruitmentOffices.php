<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use App\PermKeys;
use App\Services\BranchDataSharingService;
use App\Models\ingaz\Nationality;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecruitmentOffices extends Model
{
    use AssignsBranch, BranchScoped, Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'rec_office_id';
    public $translationModel = RecruitmentOfficeTranslation::class;
    protected $with = ['translations'];

    public function branchScopeSharedAcrossBranches(): bool
    {
        return false;
    }

    public function branchScopeViewAllPermission(): ?string
    {
        return PermKeys::OFFICES_VIEW_ALL_BRANCHES;
    }

    public function branchScopeSharingEntity(): ?string
    {
        return BranchDataSharingService::EXTERNAL_OFFICES;
    }

    public function branchScopeSharesUnassigned(): bool
    {
        // Legacy offices without a branch belong to the main branch's shared catalogue.
        return app(BranchDataSharingService::class)->mainBranchShares(BranchDataSharingService::EXTERNAL_OFFICES);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function maids()
    {
        return $this->hasMany(\App\Models\ingaz\Maid::class, 'office_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
