<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Nami\Finance\Models\Bond;

use App\Models\Concerns\BranchScoped;

class RentalContract extends Model
{
    use AssignsBranch, HasFactory, LogsActivity, BranchScoped;

    protected $fillable = [
        'user_id',
        'maid_id',
        'marketer_id',
        'order_id',
        'branch_id',
        'status',
        'closure_type',
        'closed_at',
        'transferred_to_contract_id',
        'status_before_freeze',
        'freeze_reason',
        'frozen_at',
        'resumed_at',
        'frozen_by',
        'resumed_by',
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
        'legacy_order_id',
        'legacy_created_by',
        'legacy_created_at',
        'legacy_created_at_hijri',
        'legacy_source',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'contract_sent_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'maid_delivered_at' => 'datetime',
        'trial_started_at' => 'datetime',
        'closed_at' => 'datetime',
        'frozen_at' => 'datetime',
        'resumed_at' => 'datetime',
        'branch_id' => 'integer',
        'legacy_order_id' => 'integer',
        'legacy_created_at' => 'datetime',
    ];

    /** Match every search word across the contract, client and worker identifiers. */
    public function scopeQuickSearch($query, string $search)
    {
        $search = strtr(trim($search), array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
        $terms = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            // Treat SQL wildcard characters as literal user input.
            $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $query->where(function ($match) use ($pattern) {
                foreach (['id', 'legacy_order_id', 'contract_number', 'order_id', 'contract_date'] as $column) {
                    $match->orWhereRaw("rental_contracts.{$column} LIKE ? ESCAPE '!'", [$pattern]);
                }
                $match->orWhereHas('user', function ($client) use ($pattern) {
                    $client->where(function ($fields) use ($pattern) {
                        foreach (['id', 'arabia_client_number', 'name', 'national_num', 'tax_number',
                            'license_number', 'phone', 'phone2', 'source_phone', 'source_phone2', 'email'] as $column) {
                            $fields->orWhereRaw("users.{$column} LIKE ? ESCAPE '!'", [$pattern]);
                        }
                        $fields->orWhereRaw("CONCAT(phone_code, phone) LIKE ? ESCAPE '!'", [$pattern]);
                    });
                })->orWhereHas('maid', function ($maid) use ($pattern) {
                    $maid->where(function ($fields) use ($pattern) {
                        foreach (['id', 'arabia_worker_number', 'name', 'passport_num', 'id_number',
                            'border_number', 'phone'] as $column) {
                            $fields->orWhereRaw("maids.{$column} LIKE ? ESCAPE '!'", [$pattern]);
                        }
                    });
                });
            });
        }

        return $query;
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromOrder($this->order_id ?? null)
            ?: $this->resolveBranchIdFromMaid($this->maid_id ?? null)
            ?: $this->resolveBranchIdFromAdmin($this->marketer_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    protected $with = ['info'];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function financeBonds()
    {
        return $this->hasMany(Bond::class, 'rental_contract_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function info()
    {
        return $this->hasOne(RentalContractInfo::class, 'rental_contract_id');
    }

    public function financialAdjustments()
    {
        return $this->hasMany(RentalFinancialAdjustment::class, 'rental_contract_id');
    }

    public function financeInvoice()
    {
        return $this->hasOne(RentalContractInvoice::class, 'rental_contract_id');
    }

    public function extensions()
    {
        return $this->hasMany(RentalContractExtension::class, 'rental_contract_id');
    }

    public function expiryReminders()
    {
        return $this->hasMany(RentalContractExpiryReminder::class, 'rental_contract_id');
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

    public function frozenBy()
    {
        return $this->belongsTo(Admin::class, 'frozen_by');
    }

    public function resumedBy()
    {
        return $this->belongsTo(Admin::class, 'resumed_by');
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
        return $query->whereIn('status', ['waiting_contract_send', 'contract_sent']);
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
            'waiting_contract_send' => 'بانتظار التوقيع',
            'contract_sent' => 'بانتظار التوقيع',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'frozen' => 'مجمد',
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
            // The recorded end date is authoritative; historical extensions may overlap.
            $at = in_array($this->status, ['completed', 'cancelled'], true) && $this->closed_at
                ? \Carbon\Carbon::parse($this->closed_at)
                : now();

            return (int) $at->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->info->ingaz_to)->startOfDay(), false);
        }
        return null;
    }

    public function calculateDayUsage(): array
    {
        $start = $this->info?->ingaz_from ?: $this->contract_date;
        $start = $start ? \Carbon\Carbon::parse($start)->startOfDay() : null;
        $end = $this->info?->ingaz_to ? \Carbon\Carbon::parse($this->info->ingaz_to)->startOfDay() : null;
        $duration = (int) ($this->info?->contract_duration_days ?: (($this->info?->contract_duration_months ?? 0) * 30));
        $extensionDays = (int) $this->extensions->sum('extension_days');
        $dateDuration = $start && $end ? max(1, (int) $start->diffInDays($end, false)) : 0;
        $duration = $duration > 0 ? $duration + $extensionDays : $dateDuration;
        if (!$this->info?->package_id) {
            $duration = max($duration, $dateDuration);
        }
        $at = in_array($this->status, ['completed', 'cancelled'], true) && $this->closed_at
            ? \Carbon\Carbon::parse($this->closed_at)
            : now();

        return \App\Support\RentalContractDays::calculate($duration, $start, $end, $at);
    }

    // طرق للتحكم في workflow
    public function canPrintContract()
    {
        return in_array($this->status, ['waiting_contract_send', 'contract_sent'], true);
    }

    public function canSendContract()
    {
        return false;
    }

    public function canSignContract()
    {
        return in_array($this->status, ['waiting_contract_send', 'contract_sent'], true);
    }

    public function canDeliverMaid()
    {
        return $this->status === 'contract_signed';
    }

    public function canActivateContract()
    {
        return $this->status === 'maid_delivered';
    }

    public function canCloseOrExtend()
    {
        return $this->status === 'active';
    }

    public function canFreeze()
    {
        return in_array($this->status, ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'suspended'], true);
    }

    public function canResume()
    {
        return $this->status === 'frozen';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
