<?php

namespace App\Models\ingaz;

use App\Models\ingaz\User;
use App\Models\auth\Admin;
use App\Notifications\NewComplaintNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Complaint extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    
    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
    ];

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
    
    public function maidNationality()
    {
        return $this->belongsTo(Nationality::class, 'maid_nationality_id');
    }
    
    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }
    
    public function resolvedBy()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }
    
    // Accessor for priority badge
    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'low' => 'badge bg-info',
            'medium' => 'badge bg-warning',
            'high' => 'badge bg-danger',
            'urgent' => 'badge bg-danger pulse-danger'
        ];
        
        return $badges[$this->priority] ?? 'badge bg-secondary';
    }
    
    // Accessor for priority text
    public function getPriorityTextAttribute()
    {
        $texts = [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'urgent' => 'عاجلة'
        ];
        
        return $texts[$this->priority] ?? 'غير محدد';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
