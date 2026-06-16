<?php

namespace App\Models\ingaz;

use App\Models\AdminNotification;
use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\User;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Complaint extends Model
{
    use AssignsBranch, HasFactory, LogsActivity;

    protected $guarded = [];
    
    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function getFileUrlAttribute()
    {
        if (!$this->file) return null;

        // هذا سيقوم بإنشاء رابط كامل مثل: https://api.yourdomain.com/storage/complaints/name.jpg
        return asset('storage/' . $this->file);
    }

    // تأكد من إضافة الحقل لخاصية الـ appends ليظهر في الـ JSON
    protected $appends = ['file_url'];

    protected static function booted()
    {
        static::created(function ($complaint) {
            $admins = Admin::query()->get();

            foreach ($admins as $admin) {
                AdminNotification::create([
                    'admin_id' => $admin->id,
                    'created_by' => auth('admin')->id(),
                    'title' => 'شكوى جديدة',
                    'message' => 'تم استلام شكوى جديدة من ' . ($complaint->user?->name ?? 'مستخدم'),
                    'type' => 'new_complaint',
                    'link' => route('complaints.show', $complaint->id),
                    'data' => [
                        'complaint_id' => $complaint->id,
                        'user_id' => $complaint->user_id,
                        'complaint_type_id' => $complaint->complaint_type_id,
                    ],
                ]);
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

    public function comments(){
        return $this->hasMany(ComplaintComment::class);
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

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromAdmin($this->assigned_to)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
