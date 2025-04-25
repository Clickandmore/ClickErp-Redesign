<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ContractInfo;
use App\Enums\ingaz\ContractStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $with = ['info'];

    public function getDescAttribute()
    {
        return helperTrans('ingaz.contract number') . $this->id . helperTrans('ingaz.contract of') . $this->user->name;
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function info()
    {
        return $this->belongsTo(\App\Models\ContractInfo::class, 'contract_id');
    }

    public function get_info()
    {
        return $this->belongsTo(ContractInfo::class, 'contract_id');
    }

    public function contract_status_logs()
    {
        return $this->hasMany(ContractStatusLog::class, 'contract_id');
    }

    // get last contract status log
    public function getLastContractStatusLog()
    {
        return $this->contract_status_logs()->latest()->first();
    }

    public function scopeRecruitmentContract($query)
    {
        return $query->whereNotIn('status', [ContractStatusEnum::Sari->value, ContractStatusEnum::Closed->value]);
    }

    public function scopeRentContract($query)
    {
        return $query->whereIn('status', [ContractStatusEnum::Sari->value, ContractStatusEnum::Closed->value]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

}
