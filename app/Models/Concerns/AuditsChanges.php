<?php

namespace App\Models\Concerns;

use App\Support\Audit\AuditProperties;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait AuditsChanges
{
    use LogsActivity, SerializesActivityEnums;

    public function getActivitylogOptions(): LogOptions
    {
        $excluded = array_filter(array_keys($this->getAttributes()), [AuditProperties::class, 'sensitive']);

        return LogOptions::defaults()
            ->useLogName(str_starts_with(static::class, 'Nami\\Crm\\') ? 'crm' : 'default')
            ->logAll()
            ->logExcept(array_values(array_unique(array_merge($excluded, $this->getHidden(), [
                'password', 'remember_token', 'token', 'verification_code',
                'last_activity_at', 'last_seen_at', 'last_login_at',
            ]))))
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }
}
