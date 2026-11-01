<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (Contact $contact): void {
            try {
                if (\App\Support\ContactNotificationSettings::all()['enabled']) {
                    $contactId = (int) $contact->id;
                    $dispatch = static function () use ($contactId): void {
                        try {
                            \App\Jobs\SendContactNotification::dispatch($contactId);
                        } catch (\Throwable $exception) {
                            \Illuminate\Support\Facades\Log::warning('Unable to queue contact notification', [
                                'contact_id' => $contactId, 'exception' => get_class($exception),
                            ]);
                        }
                    };
                    // The sync driver in this Laravel version does not defer jobs itself.
                    if ($contact->getConnection()->transactionLevel() > 0) {
                        $contact->getConnection()->afterCommit($dispatch);
                    } else {
                        $dispatch();
                    }
                }
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('Unable to queue contact notification', [
                    'contact_id' => $contact->id, 'exception' => get_class($exception),
                ]);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
