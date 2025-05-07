<?php

namespace App\Models\ingaz;

use App\Models\ingaz\User;
use App\Notifications\NewComplaintNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Complaint extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected static function booted()
    {
        static::created(function ($complaint) {
            // Notify all admin users
            $adminUsers = User::role('admin')->get();
            foreach ($adminUsers as $admin) {
                $admin->notify(new NewComplaintNotification($complaint));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complaintType()
    {
        return $this->belongsTo(ComplaintType::class, 'complaint_type_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
