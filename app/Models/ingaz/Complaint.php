<?php

namespace App\Models\ingaz;

use App\PermKeys;
use App\Models\Concerns\AssignsBranch;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\User;
use App\Models\auth\Admin;
use App\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\Concerns\BranchScoped;

class Complaint extends Model
{
    use AssignsBranch, HasFactory, LogsActivity, BranchScoped;

    protected $guarded = [];

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'rejected', 'closed'], true);
    }
    
    protected $casts = [
        'attachments' => 'array',
        'import_payload' => 'array',
        'complaint_date' => 'date',
        'source_created_at' => 'datetime',
        'closed_at' => 'datetime',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function getFileUrlAttribute()
    {
        return $this->file ? showFile($this->file) : null;
    }

    // تأكد من إضافة الحقل لخاصية الـ appends ليظهر في الـ JSON
    protected $appends = ['file_url'];

    protected static function booted()
    {
        static::created(function ($complaint) {
            $recipientIds = Admin::query()
                ->activeForOperations()
                ->get()
                ->filter(fn (Admin $admin) => $admin->hasAnyPermission([
                    PermKeys::COMPLAINT_VIEW,
                    PermKeys::COMPLAINT_EDIT,
                    PermKeys::COMPLAINT_CONTROL,
                    PermKeys::COMPLAINT_RESPONSE,
                ]))
                ->pluck('id');

            app(AdminNotificationService::class)->sendToAdmins(
                $recipientIds,
                'شكوى جديدة',
                'تم استلام شكوى جديدة من ' . ($complaint->user?->name ?? 'مستخدم'),
                'new_complaint',
                route('complaints.show', $complaint->id),
                [
                    'complaint_id' => $complaint->id,
                    'user_id' => $complaint->user_id,
                    'complaint_type_id' => $complaint->complaint_type_id,
                ]
            );
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function complaintType()
    {
        return $this->belongsTo(ComplaintType::class, 'complaint_type_id');
    }
    
    public function maidNationality()
    {
        return $this->belongsTo(Nationality::class, 'maid_nationality_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }
    
    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function scopeForRecruitmentContract($query, Contract $contract)
    {
        return $query->where(function ($related) use ($contract) {
            $related->where('contract_id', $contract->id);
            if ($contract->order_id) {
                // Include order-level complaints, but never a complaint
                // explicitly assigned to a different contract on that order.
                $related->orWhere(function ($orderComplaint) use ($contract) {
                    $orderComplaint->whereNull('contract_id')->where('order_id', $contract->order_id);
                });
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id')->withTrashed();
    }

    public function recruitmentOffice()
    {
        return $this->belongsTo(RecruitmentOffices::class, 'recruitment_office_id');
    }
    
    public function getExternalOfficeAttribute(): ?RecruitmentOffices
    {
        return $this->recruitmentOffice
            ?: $this->contract?->resolveExternalOfficeDispatchOffice()
            ?: $this->order?->maid?->office
            ?: $this->order?->contracts->map(fn ($contract) => $contract->resolveExternalOfficeDispatchOffice())->filter()->first();
    }

    public function resolvedBy()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function comments(){
        return $this->hasMany(ComplaintComment::class);
    }

    public function attachmentFiles()
    {
        return $this->hasMany(ComplaintAttachment::class)->latest('id');
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
