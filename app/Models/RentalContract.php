<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RentalContract extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'maid_id',
        'marketer_id',
        'order_id',
        'status',
        'contract_date',
        'contract_number',
        'warranty_status',
        'warranty_start_date',
        'warranty_end_date',
        'signature',
        'contract_sent_at',
        'contract_signed_at',
        'maid_delivered_at',
        'trial_started_at',
        'contract_sent_by',
        'contract_signed_by',
        'maid_delivered_by',
        'trial_started_by',
        'company_signature',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'contract_sent_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'maid_delivered_at' => 'datetime',
        'trial_started_at' => 'datetime',
    ];

    protected $with = ['info'];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function info()
    {
        return $this->hasOne(RentalContractInfo::class, 'rental_contract_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(RentalContractStatusLog::class, 'rental_contract_id');
    }

    // علاقات workflow الجديدة
    public function contractSentBy()
    {
        return $this->belongsTo(Admin::class, 'contract_sent_by');
    }

    public function contractSignedBy()
    {
        return $this->belongsTo(Admin::class, 'contract_signed_by');
    }

    public function maidDeliveredBy()
    {
        return $this->belongsTo(Admin::class, 'maid_delivered_by');
    }

    public function trialStartedBy()
    {
        return $this->belongsTo(Admin::class, 'trial_started_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithWarranty($query)
    {
        return $query->where('warranty_status', 'active')
                    ->where('warranty_end_date', '>', now());
    }

    // Scopes للمراحل الجديدة
    public function scopeWaitingContractSend($query)
    {
        return $query->where('status', 'waiting_contract_send');
    }

    public function scopeContractSent($query)
    {
        return $query->where('status', 'contract_sent');
    }

    public function scopeContractSigned($query)
    {
        return $query->where('status', 'contract_signed');
    }

    public function scopeMaidDelivered($query)
    {
        return $query->where('status', 'maid_delivered');
    }


    // Accessors & Mutators
    public function getDescAttribute()
    {
        return 'عقد إيجار رقم ' . $this->contract_number . ' - ' . ($this->user ? $this->user->name : 'غير محدد');
    }

    public function getStatusTextAttribute()
    {
        $statusTexts = [
            'new' => 'جديد', 
            'waiting_contract_send' => 'انتظار إرسال العقد',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
        ];

        return $statusTexts[$this->status] ?? 'غير محدد';
    }

    // Helper Methods
    public function calculateContractValue()
    {
        if ($this->info && $this->info->monthly_cost && $this->info->contract_duration_months) {
            return $this->info->monthly_cost * $this->info->contract_duration_months;
        }
        return 0;
    }

    public function calculateRemainingDays()
    {
        if ($this->info && $this->info->ingaz_to) {
            return now()->diffInDays($this->info->ingaz_to, false);
        }
        return null;
    }

    // طرق للتحكم في workflow
    public function canPrintContract()
    {
        return $this->status === 'waiting_contract_send';
    }

    public function canSendContract()
    {
        return $this->status === 'waiting_contract_send';
    }

    public function canSignContract()
    {
        return $this->status === 'contract_sent';
    }

    public function canDeliverMaid()
    {
        return $this->status === 'contract_signed';
    }

    public function canActivateContract()
    {
        return $this->status === 'maid_delivered';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
