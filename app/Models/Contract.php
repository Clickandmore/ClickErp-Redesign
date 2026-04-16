<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use App\Models\ContractReturnRequest;use App\Models\ingaz\User;
use App\Models\ContractInfo;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\ingaz\ContractNote;
use App\Models\ingaz\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $with = ['info'];

    // إضافة الحقول الجديدة للضمان
    protected $casts = [
        'warranty_start_date' => 'datetime',
        'warranty_end_date' => 'datetime',
    ];

    public function getDescAttribute()
    {
        return helperTrans('ingaz.contract number') . $this->id . helperTrans('ingaz.contract of') . ($this->user->name ?? __('ingaz.unknown_user'));
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\ingaz\Order::class, 'order_id');
    }

    public function marketer()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'marketer_id');
    }

    public function requiredMaidNationality()
    {
        return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'required_maid_nationality_id');
    }

    public function requiredMaidReligion()
    {
        return $this->belongsTo(\App\Models\ingaz\Religion::class, 'required_maid_religion_id');
    }

    public function requiredMaidJobTitle()
    {
        return $this->belongsTo(\App\Models\ingaz\JobTitle::class, 'required_maid_job_title_id');
    }

    public function info()
    {
        return $this->hasOne(\App\Models\ContractInfo::class, 'contract_id');
    }

    public function get_info()
    {
        return $this->belongsTo(ContractInfo::class, 'contract_id');
    }

    public function contract_status_logs()
    {
        return $this->hasMany(ContractStatusLog::class, "contract_id");
    }

    public function invoice(){
        return $this->hasOne(Invoice::class, 'contract_id');
    }

    public function musanedTransferItem()
    {
        return $this->hasOne(MusanedTransferItem::class, 'contract_id');
    }

    public function notes(){
        return $this->hasMany(ContractNote::class, 'contract_id');
    }
    

    public function latestShelterLog()
    {
        return $this->hasOne(ContractStatusLog::class, 'contract_id')
            ->where('status', ContractStatusEnum::Shelter->value)
            ->latestOfMany();
    }

    public function deportationProcedure()
    {
        return $this->hasOne(\App\Models\DeportationProcedure::class, "contract_id");
    }

    public function travelHistory()
    {
        return $this->hasOne(\App\Models\TravelHistory::class, 'order_id', 'id');
    }


    public function hasPendingReturnRequest()
    {
        return $this->returnRequests()->where('status', 'pending')->exists();
    }

    // get last contract status log
    public function getLastContractStatusLog()
    {
        return $this->contract_status_logs()->latest()->first();
    }

    public function scopeRecruitmentContract($query)
    {
        return $query->whereNotIn('status', [ContractStatusEnum::Sari->value, ContractStatusEnum::Closed->value]);
    }

    public function scopeUnderWarranty($query)
    {
        return $query->where('status', ContractStatusEnum::Arrival->value)
            ->where('warranty_status', 'active');
    }

    public function scopeRentContract($query)
    {
        return $query->whereIn('status', [ContractStatusEnum::Sari->value, ContractStatusEnum::Closed->value]);
    }

    public function scopeInsideShelter($query)
    {
        return $query->where('status', ContractStatusEnum::Shelter->value)
            ->where(function ($innerQuery) {
                $innerQuery->whereDoesntHave('info')
                    ->orWhereHas('info', function ($infoQuery) {
                        $infoQuery->where(function ($condition) {
                            $condition->whereNull('is_outside_shelter')
                                      ->orWhere('is_outside_shelter', false);
                        });
                    });
            });
    }

    public function scopeOutsideShelter($query)
    {
        return $query->where('status', ContractStatusEnum::Shelter->value)
            ->whereHas('info', function ($infoQuery) {
                $infoQuery->where('is_outside_shelter', true);
            });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    /**
     * حساب حالة الضمان تلقائياً
     */
    public function calculateWarrantyStatus()
    {
        if (!in_array($this->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true)) {
            $this->update(['warranty_status' => 'not_applicable']);
            return;
        }

        // البحث عن رحلة الوصول
        $travelHistory = \App\Models\TravelHistory::where('order_id', $this->id)
            ->latest('id')
            ->first();

        if (!$travelHistory) {
            $this->update(['warranty_status' => 'not_applicable']);
            return;
        }

        $arrivalDateTime = null;

        if ($travelHistory->arrival_date && $travelHistory->arrival_time) {
            $arrivalDateTime = \Carbon\Carbon::parse($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);
        } elseif ($travelHistory->arrival_date) {
            $arrivalDateTime = \Carbon\Carbon::parse($travelHistory->arrival_date);
        } elseif (!empty($travelHistory->arrived_at)) {
            $arrivalDateTime = \Carbon\Carbon::parse($travelHistory->arrived_at);
        }

        if (!$arrivalDateTime) {
            $this->update(['warranty_status' => 'not_applicable']);
            return;
        }

        $warrantyEndDate = $arrivalDateTime->copy()->addDays(90);
        $updates = [
            'warranty_start_date' => $arrivalDateTime,
            'warranty_end_date' => $warrantyEndDate,
        ];

        if ($warrantyEndDate->isPast()) {
            $updates['status'] = ContractStatusEnum::Completed->value;
            $updates['warranty_status'] = 'expired';
        } else {
            $updates['status'] = ContractStatusEnum::Arrival->value;
            $updates['warranty_status'] = 'active';
        }

        $this->update($updates);
    }

    /**
     * الحصول على حالة الضمان كنص
     */
    public function getWarrantyStatusTextAttribute()
    {
        switch ($this->warranty_status) {
            case 'active':
                return 'نشط';
            case 'expired':
                return 'منتهي';
            case 'not_applicable':
                return 'غير قابل للتطبيق';
            default:
                return 'غير محدد';
        }
    }

    /**
     * الحصول على الأيام المتبقية في الضمان
     */
    public function getWarrantyRemainingDaysAttribute()
    {
        if ($this->warranty_status !== 'active' || !$this->warranty_end_date) {
            return 0;
        }

        $remaining = \Carbon\Carbon::now()->diffInDays($this->warranty_end_date, false);
        return max(0, $remaining);
    }

    /**
     * العلاقات مع طلبات الاسترجاع
     */
    public function returnRequests()
    {
        return $this->hasMany(ContractReturnRequest::class);
    }

    /**
     * Calculate contract total using stored info.
     */
    public function calculateContractValue(): float
    {
        if ($this->info) {
            if ($this->info->contract_value !== null) {
                return (float) $this->info->contract_value;
            }

            $monthlyCost = $this->info->monthly_cost;
            $duration = $this->info->contract_duration;

            if ($monthlyCost && $duration) {
                return (float) $monthlyCost * (float) $duration;
            }
        }

        return 0;
    }

}
