<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\SerializesActivityEnums;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Finance extends Model
{
    use HasFactory, LogsActivity, SerializesActivityEnums;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('finance')
            ->logOnly(['*'])
            ->logExcept([
                'password',
                'remember_token',
                'token',
                'verification_code',
            ])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }
}
