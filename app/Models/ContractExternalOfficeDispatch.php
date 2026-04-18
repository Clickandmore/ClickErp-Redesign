<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractExternalOfficeDispatch extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'dispatch_date' => 'date',
        'office_contract_cost_usd' => 'decimal:2',
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
}
