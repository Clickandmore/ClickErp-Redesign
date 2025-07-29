<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\auth\Admin;

class DeportationProcedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'maid_id',
        'salary_received',
        'salary_received_date',
        'has_passport',
        'has_deportation_letter',
        'has_settlement_session',
        'has_final_exit',
        'attachments',
        'notes',
        'status',
        'started_at',
        'completed_at',
        'created_by',
        'completed_by'
    ];

    protected $casts = [
        'salary_received' => 'boolean',
        'has_passport' => 'boolean',
        'has_deportation_letter' => 'boolean',
        'has_settlement_session' => 'boolean',
        'has_final_exit' => 'boolean',
        'attachments' => 'array',
        'salary_received_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // العلاقات
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(Admin::class, 'completed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'في الانتظار';
            case 'in_progress':
                return 'قيد التنفيذ';
            case 'completed':
                return 'مكتمل';
            case 'cancelled':
                return 'ملغي';
            default:
                return 'غير محدد';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'warning';
            case 'in_progress':
                return 'info';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // التحقق من اكتمال جميع الإجراءات
    public function isAllProceduresCompleted()
    {
        return $this->salary_received &&
               $this->has_passport &&
               $this->has_deportation_letter &&
               $this->has_settlement_session &&
               $this->has_final_exit;
    }

    // حساب نسبة الإكمال
    public function getCompletionPercentageAttribute()
    {
        $total = 5; // عدد الإجراءات المطلوبة
        $completed = 0;

        if ($this->salary_received) $completed++;
        if ($this->has_passport) $completed++;
        if ($this->has_deportation_letter) $completed++;
        if ($this->has_settlement_session) $completed++;
        if ($this->has_final_exit) $completed++;

        return round(($completed / $total) * 100);
    }
}
