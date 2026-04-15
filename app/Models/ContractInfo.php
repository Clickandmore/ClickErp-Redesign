<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractInfo extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        'shelter_attachments' => 'array',
        'shelter_quick_tags' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function prevSponsorship()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'previous_sponsorship');
    }

    public function shelter()
    {
        return $this->belongsTo(ShelterPlaces::class, 'shelter_id');
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
