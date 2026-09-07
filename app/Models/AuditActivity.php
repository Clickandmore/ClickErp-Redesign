<?php

namespace App\Models;

use App\Support\Audit\AuditProperties;
use Spatie\Activitylog\Models\Activity;

class AuditActivity extends Activity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            $properties = $activity->properties?->toArray() ?? [];
            if ($activity->causer) {
                $properties['actor_name'] = $activity->causer->name ?? $activity->causer->email;
            }
            if (!$activity->causer_id && !isset($properties['actor_name'])) {
                $command = app(\App\Support\Audit\ExecutionContext::class)->command();
                if ($command) {
                    $properties['execution_source'] = 'console';
                    $properties['execution_command'] = $command;
                    $properties['actor_name'] = 'النظام';
                }
            }
            if (app()->bound('request') && request()->hasSession()) {
                $properties['impersonator_admin_id'] = session('impersonator_admin_id');
                if (session('dev_superadmin_impersonating')) {
                    $properties['dev_superadmin_impersonating'] = true;
                }
            }
            $subject = $activity->subject;
            // Keep the key on dirty-only settings updates so generic values can be redacted.
            if ($subject && in_array('key', array_keys($subject->getAttributes()), true)) {
                foreach (['attributes', 'old'] as $group) {
                    if (isset($properties[$group]['value'])) {
                        $properties[$group]['key'] = $properties[$group]['key'] ?? ($group === 'old'
                            ? ($subject->getRawOriginal('key') ?: $subject->getAttribute('key'))
                            : $subject->getAttribute('key'));
                    }
                }
            }
            if ($activity->event === 'deleted' && $subject && method_exists($subject, 'isForceDeleting') && $subject->isForceDeleting()) {
                $activity->event = 'force_deleted';
                if ($activity->description === 'deleted') {
                    $activity->description = 'force_deleted';
                }
            }
            $activity->properties = AuditProperties::sanitize($properties);
        });
    }
}
