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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
