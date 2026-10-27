<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\Concerns\BranchScoped;

class ContractExternalOfficeDispatch extends Model
{
    use AssignsBranch, HasFactory, LogsActivity, BranchScoped;

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'dispatch_date' => 'date',
        'office_contract_cost_usd' => 'decimal:2',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function recruitmentOffice()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'recruitment_office_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by_admin_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'updated_by_admin_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract_external_office_dispatch')
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by_admin_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
