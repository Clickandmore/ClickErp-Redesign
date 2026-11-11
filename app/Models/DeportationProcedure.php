<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\auth\Admin;

class DeportationProcedure extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'maid_id',
        'salary_received',
        'salary_received_date',
        'has_passport',
        'passport_date',
        'has_deportation_letter',
        'deportation_letter_date',
        'has_settlement_session',
        'settlement_date',
        'has_final_exit',
        'final_exit_date',
        'attachments',
        'notes',
        'status',
        'started_at',
        'completed_at',
        'created_by',
        'completed_by',
        'trip_scheduled',
        'trip_id',
        'procedures_confirmed',
        'confirmed_at',
        'confirmed_by'
    ];

    protected $casts = [
        'salary_received' => 'boolean',
        'has_passport' => 'boolean',
        'has_deportation_letter' => 'boolean',
        'has_settlement_session' => 'boolean',
        'has_final_exit' => 'boolean',
        'trip_scheduled' => 'boolean',
        'procedures_confirmed' => 'boolean',
        'salary_received_date' => 'date',
        'passport_date' => 'date',
        'deportation_letter_date' => 'date',
        'settlement_date' => 'date',
        'final_exit_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'attachments' => 'array'
    ];

    // العلاقات
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id ?? null)
            ?: $this->resolveBranchIdFromMaid($this->maid_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
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

    public function confirmedBy()
    {
        return $this->belongsTo(Admin::class, 'confirmed_by');
    }

    public function trip()
    {
        return $this->belongsTo(\App\Models\TravelHistory::class, 'trip_id');
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\DeportationProcedureLog::class)->orderBy('created_at', 'desc');
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
        // يعتبر الإجراءات مكتملة إذا تم تأكيدها من قبل المستخدم
        return $this->procedures_confirmed;
    }

    // التحقق من أن جميع الأسئلة تمت الإجابة عليها
    public function areAllQuestionsAnswered()
    {
        // السؤال يعتبر مجاب عليه إذا:
        // 1. الإجابة "لا" (0)
        // 2. الإجابة "نعم" (1) مع وجود تاريخ

        $salaryAnswered = ($this->salary_received === 0 || $this->salary_received === false) ||
                         (($this->salary_received === 1 || $this->salary_received === true) && $this->salary_received_date);

        $passportAnswered = ($this->has_passport === 0 || $this->has_passport === false) ||
                           (($this->has_passport === 1 || $this->has_passport === true) && $this->passport_date);

        $letterAnswered = ($this->has_deportation_letter === 0 || $this->has_deportation_letter === false) ||
                         (($this->has_deportation_letter === 1 || $this->has_deportation_letter === true) && $this->deportation_letter_date);

        $settlementAnswered = ($this->has_settlement_session === 0 || $this->has_settlement_session === false) ||
                             (($this->has_settlement_session === 1 || $this->has_settlement_session === true) && $this->settlement_date);

        $exitAnswered = ($this->has_final_exit === 0 || $this->has_final_exit === false) ||
                       (($this->has_final_exit === 1 || $this->has_final_exit === true) && $this->final_exit_date);

        return $salaryAnswered && $passportAnswered && $letterAnswered && $settlementAnswered && $exitAnswered;
    }

    // حساب نسبة الإكمال
    public function getCompletionPercentageAttribute()
    {
        // إذا اكتملت الإجراءات، النسبة 100
        if ($this->status === 'completed') {
            return 100;
        }

        $total = 5; // عدد الأسئلة
        $answered = 0;

        // نحسب الأسئلة المجاب عليها بشكل صحيح
        // السؤال يعتبر مجاب عليه إذا:
        // 1. الإجابة "لا" (0)
        // 2. الإجابة "نعم" (1) مع وجود تاريخ

        // الراتب
        if ($this->salary_received === false || ($this->salary_received === true && $this->salary_received_date)) {
            $answered++;
        }

        // الجواز
        if ($this->has_passport === false || ($this->has_passport === true && $this->passport_date)) {
            $answered++;
        }

        // خطاب الترحيل
        if ($this->has_deportation_letter === false || ($this->has_deportation_letter === true && $this->deportation_letter_date)) {
            $answered++;
        }

        // جلسة التسوية - التاريخ مطلوب دائماً
        if (($this->has_settlement_session === false && $this->settlement_date) ||
            ($this->has_settlement_session === true && $this->settlement_date)) {
            $answered++;
        }

        // الخروج النهائي
        if ($this->has_final_exit === false || ($this->has_final_exit === true && $this->final_exit_date)) {
            $answered++;
        }

        return round(($answered / $total) * 100);
    }
}
