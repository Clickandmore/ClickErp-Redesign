<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractInfo extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        'shelter_attachments' => 'array',
        'shelter_quick_tags' => 'array',
        'escape_report_attachment' => 'array',
        'is_outside_shelter' => 'boolean',
        'outside_shelter_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    public function prevSponsorship()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'previous_sponsorship');
    }

    public function shelter()
    {
        return $this->belongsTo(ShelterPlaces::class, 'shelter_id');
    }

    public function outsideShelterBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'outside_shelter_by');
    }

    public function outsideReference()
    {
        return $this->morphTo(__FUNCTION__, 'outside_shelter_reference_type', 'outside_shelter_reference_id');
    }

    public function manualMaidNationality()
    {
        return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'manual_maid_nationality_id');
    }

    public function manualMaidOffice()
    {
        return $this->belongsTo(\App\Models\RecruitmentOffices::class, 'manual_maid_office_id');
    }

    public function hasManualMaidSummary(): bool
    {
        return filled($this->manual_maid_name)
            || filled($this->manual_maid_passport_num)
            || filled($this->manual_maid_nationality_id)
            || filled($this->manual_maid_office_id)
            || filled($this->manual_maid_attachment);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
