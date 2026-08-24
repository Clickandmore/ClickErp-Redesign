<?php

namespace Nami\Hr\Models;

use App\Models\Concerns\SerializesActivityEnums;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

abstract class HrModel extends Model
{
    use LogsActivity, SerializesActivityEnums;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('hr')
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
