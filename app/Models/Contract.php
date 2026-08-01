<?php

namespace App\Models;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Maid;
use App\Models\ContractReturnRequest;
use App\Models\ingaz\User;
use App\Models\ContractInfo;
use App\Models\ingaz\ContractNote;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\RecruitmentContractAttachment;
use App\Support\AutomationSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Nami\Finance\Models\Bond;

class Contract extends Model
{
    use AssignsBranch, HasFactory, LogsActivity;

    protected $guarded = [];
    protected $with = ['info'];

    // إضافة الحقول الجديدة للضمان
    protected $casts = [
        'warranty_start_date' => 'datetime',
        'warranty_end_date' => 'datetime',
        'branch_id' => 'integer',
    ];

    protected function resolveBranchIdForCreate(): ?int
    {
        $marketerId = (int) ($this->marketer_id ?? 0);
        if ($marketerId > 0) {
            return $this->resolveBranchIdFromAdmin($marketerId)
                ?: $this->resolveMainBranchId();
        }

        $orderId = (int) ($this->order_id ?? 0);
        if ($orderId > 0) {
            return $this->resolveBranchIdFromOrder($orderId)
                ?: $this->resolveBranchIdFromOrderResponsibleAdmin($orderId)
                ?: $this->resolveMainBranchId();
        }

        return $this->resolveBranchIdFromCurrentAdmin()
            ?: $this->resolveMainBranchId();
    }

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

    public function latestContractStatusLog()
    {
        return $this->hasOne(ContractStatusLog::class, 'contract_id')->latestOfMany('id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'contract_id');
    }

    public function financeBonds()
    {
        return $this->hasMany(Bond::class, 'recruitment_contract_id');
    }

    public function musanedTransferItem()
    {
        return $this->hasOne(MusanedTransferItem::class, 'contract_id');
    }

    public function recruitmentRefund()
    {
        return $this->hasOne(RecruitmentContractRefund::class, 'contract_id');
    }

    public function notes()
    {
        return $this->hasMany(ContractNote::class, 'contract_id');
    }

    public function complaints()
    {
        return $this->hasMany(\App\Models\ingaz\Complaint::class, 'contract_id');
    }

    public function attachments()
    {
        return $this->hasMany(RecruitmentContractAttachment::class, 'contract_id');
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

    public function externalOfficeDispatch()
    {
        return $this->hasOne(ContractExternalOfficeDispatch::class, 'contract_id');
    }

    public function sponsorshipTransfers()
    {
        return $this->hasMany(SponsorshipTransfer::class, 'contract_id');
    }

    public function isCurrentRecruitmentContract(): bool
    {
        return in_array($this->status, [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New ->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ], true);
    }

    public function canManageExternalOfficeDispatch(): bool
    {
        return $this->isCurrentRecruitmentContract();
    }

    public function resolveExternalOfficeDispatchOffice(): ?RecruitmentOffices
    {
        if ($this->maid) {
            return $this->maid->office ?? $this->maid->recruitmentOffice;
        }

        return $this->info?->manualMaidOffice;
    }

    public function buildExternalOfficeDispatchSourceData($officeContractCostUsd = null): array
    {
        $this->loadMissing([
            'user',
            'maid.religion',
            'maid.office',
            'maid.recruitmentOffice',
            'info.manualMaidOffice',
        ]);

        $office = $this->resolveExternalOfficeDispatchOffice();
        $officeCost = null;

        if ($officeContractCostUsd !== null && $officeContractCostUsd !== '') {
            $officeCost = number_format((float) str_replace(',', '', (string) $officeContractCostUsd), 2, '.', '');
        } elseif (filled($office?->cost_of_dollar)) {
            $officeCost = number_format((float) str_replace(',', '', (string) $office->cost_of_dollar), 2, '.', '');
        }

        return [
            'contract_number' => (string) $this->id,
            'visa_number' => trim((string) ($this->info?->visa_number ?? '')),
            'sponsor_name' => trim((string) ($this->user?->name ?? '')),
            'sponsor_identity_number' => trim((string) ($this->user?->documentNumber() ?? '')),
            'worker_name' => trim((string) ($this->maid?->name ?? $this->info?->manual_maid_name ?? '')),
            'passport_number' => trim((string) ($this->maid?->passport_num ?? $this->info?->manual_maid_passport_num ?? '')),
            'worker_age' => $this->formatExternalOfficeDispatchAge($this->maid?->age),
            'worker_experience' => $this->formatExternalOfficeDispatchExperience(),
            'worker_religion' => trim((string) ($this->maid?->religion?->title ?? '')),
            'office_name' => trim((string) ($office?->title ?? '')),
            'recruitment_office_id' => $office?->id,
            'office_contract_cost_usd' => $officeCost,
        ];
    }

    public function buildExternalOfficeDispatchSourceHash($officeContractCostUsd = null): string
    {
        return sha1(json_encode(
            $this->buildExternalOfficeDispatchSourceData($officeContractCostUsd),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function externalOfficeDispatchSourceValidationErrors($officeContractCostUsd = null): array
    {
        $data = $this->buildExternalOfficeDispatchSourceData($officeContractCostUsd);
        $errors = [];

        if (!$this->maid) {
            $errors[] = 'يجب ربط عاملة فعلية بالعقد قبل إنشاء إرسالية المكتب الخارجي.';
        }

        if ($data['sponsor_name'] === '') {
            $errors[] = 'اسم الكفيل غير مكتمل على بيانات العميل.';
        }

        if ($data['sponsor_identity_number'] === '') {
            $errors[] = 'رقم هوية العميل غير مكتمل.';
        }

        if ($data['worker_name'] === '') {
            $errors[] = 'اسم العاملة غير مكتمل.';
        }

        if ($data['passport_number'] === '') {
            $errors[] = 'رقم جواز العاملة غير مكتمل.';
        }

        if ($data['worker_age'] === '') {
            $errors[] = 'عمر العاملة غير مكتمل.';
        }

        if ($data['worker_experience'] === '') {
            $errors[] = 'خبرة العاملة غير مكتملة.';
        }

        if ($data['worker_religion'] === '') {
            $errors[] = 'ديانة العاملة غير مكتملة.';
        }

        if ($data['office_name'] === '') {
            $errors[] = 'المكتب الخارجي غير محدد على العاملة.';
        }

        return $errors;
    }

    public function resolveExternalOfficeDispatchArrivalStation(): ?string
    {
        if ($this->relationLoaded('externalOfficeDispatch') && filled($this->externalOfficeDispatch?->arrival_station)) {
            return trim((string) $this->externalOfficeDispatch->arrival_station);
        }

        $this->loadMissing('user');

        if (filled($this->user?->city)) {
            return trim((string) $this->user->city);
        }

        $travelHistory = $this->relationLoaded('travelHistory')
            ? $this->travelHistory
            : $this->travelHistory()->latest('id')->first();

        if (filled($travelHistory?->arrival_airport)) {
            return trim((string) $travelHistory->arrival_airport);
        }

        return null;
    }

    public function getExternalOfficeDispatchState(): array
    {
        $dispatch = $this->relationLoaded('externalOfficeDispatch')
            ? $this->externalOfficeDispatch
            : $this->externalOfficeDispatch()->first();

        if (!$dispatch) {
            return [
                'code' => 'missing',
                'label' => 'غير مكتملة',
                'message' => 'لم يتم إنشاء إرسالية المكتب الخارجي لهذا العقد بعد.',
                'is_completed' => false,
            ];
        }

        if (!$dispatch->dispatch_date || !filled($dispatch->arrival_station)) {
            return [
                'code' => 'incomplete',
                'label' => 'غير مكتملة',
                'message' => 'تاريخ الإرسالية أو محطة الوصول غير مكتملين.',
                'is_completed' => false,
            ];
        }

        // Arabia did not allow closing an order before its external-office dispatch.
        // Historical imports do not carry the office USD cost, so the persisted
        // dispatch date and arrival station are the authoritative legacy proof.
        if (filled($this->arabia_order_number)
            && $this->status === ContractStatusEnum::Completed->value) {
            return [
                'code' => 'completed',
                'label' => 'مكتملة',
                'message' => 'تمت إرسالية المكتب الخارجي في نظام أربيا بتاريخ '
                    . optional($dispatch->dispatch_date)->format('Y-m-d') . '.',
                'is_completed' => true,
            ];
        }

        if ((float) ($dispatch->office_contract_cost_usd ?? 0) <= 0) {
            return [
                'code' => 'incomplete',
                'label' => 'غير مكتملة',
                'message' => 'تكلفة العقد بالدولار غير مكتملة على إرسالية المكتب الخارجي.',
                'is_completed' => false,
            ];
        }

        $sourceErrors = $this->externalOfficeDispatchSourceValidationErrors($dispatch->office_contract_cost_usd);
        if (!empty($sourceErrors)) {
            return [
                'code' => 'outdated',
                'label' => 'تحتاج تحديث',
                'message' => implode(' ', $sourceErrors),
                'is_completed' => false,
            ];
        }

        if ($dispatch->source_hash !== $this->buildExternalOfficeDispatchSourceHash($dispatch->office_contract_cost_usd)) {
            return [
                'code' => 'outdated',
                'label' => 'تحتاج تحديث',
                'message' => 'تم تعديل بيانات العقد أو العاملة أو المكتب بعد إنشاء الإرسالية الحالية.',
                'is_completed' => false,
            ];
        }

        return [
            'code' => 'completed',
            'label' => 'مكتملة',
            'message' => 'تمت إرسالية المكتب الخارجي بتاريخ ' . optional($dispatch->dispatch_date)->format('Y-m-d') . '.',
            'is_completed' => true,
        ];
    }

    public function hasCompletedExternalOfficeDispatch(): bool
    {
        return (bool) ($this->getExternalOfficeDispatchState()['is_completed'] ?? false);
    }

    private function formatExternalOfficeDispatchExperience(): string
    {
        if (!$this->maid) {
            return '';
        }

        $experienceYears = $this->maid->experience_years;

        if ($this->maid->experience_status === 'has_experience') {
            if (filled($experienceYears)) {
                return trim((string) $experienceYears) . ' سنوات';
            }

            return __('ingaz.has_experience_label');
        }

        if ($this->maid->experience_status === 'no_experience') {
            return __('ingaz.no_experience_label');
        }

        if (filled($experienceYears)) {
            return trim((string) $experienceYears) . ' سنوات';
        }

        return '';
    }

    private function formatExternalOfficeDispatchAge($age): string
    {
        if ($age === null || $age === '') {
            return '';
        }

        $normalizedAge = (float) $age;

        if ((int) $normalizedAge == $normalizedAge) {
            return (string) (int) $normalizedAge;
        }

        return rtrim(rtrim(number_format($normalizedAge, 2, '.', ''), '0'), '.');
    }


    public function hasPendingReturnRequest()
    {
        return $this->returnRequests()->where('status', 'pending')->exists();
    }

    // get last contract status log
    public function getLastContractStatusLog()
    {
        if ($this->relationLoaded('latestContractStatusLog')) {
            return $this->latestContractStatusLog;
        }

        if ($this->relationLoaded('contract_status_logs')) {
            return $this->contract_status_logs->sortByDesc('id')->first();
        }

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
            ->whereDoesntHave('maid.rentalContracts', function ($rentalQuery) {
                $rentalQuery->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_RENTAL_STATUSES);
            })
            ->whereDoesntHave('sponsorshipTransfers', function ($transferQuery) {
                $transferQuery->whereIn('status', ['maid_delivered', 'trial', 'trial_started']);
            })
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
        return $query->where(function ($outsideQuery) {
            $outsideQuery
                ->where(function ($flaggedQuery) {
                    $flaggedQuery->where('status', ContractStatusEnum::Shelter->value)
                        ->whereHas('info', function ($infoQuery) {
                            $infoQuery->where('is_outside_shelter', true);
                        });
                })
                ->orWhere(function ($rentalQuery) {
                    $rentalQuery->where('status', ContractStatusEnum::Shelter->value)
                        ->whereHas('maid.rentalContracts', function ($contractQuery) {
                            $contractQuery->whereIn('status', ['maid_delivered', 'active']);
                        });
                })
                ->orWhere(function ($transferQuery) {
                    $transferQuery->whereIn('status', [
                        ContractStatusEnum::Shelter->value,
                        ContractStatusEnum::Trial->value,
                    ])
                        ->whereHas('sponsorshipTransfers', function ($sponsorshipQuery) {
                            $sponsorshipQuery->whereIn('status', ['maid_delivered', 'trial', 'trial_started']);
                        });
                });
        });
    }

    public function hasEscapeReport(): bool
    {
        return $this->info?->outside_shelter_reason === 'escape_report';
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
    public function resolveRecruitmentArrivalDateTime(): ?\Carbon\Carbon
    {
        if ($this->warranty_start_date) {
            return \Carbon\Carbon::parse($this->warranty_start_date);
        }

        $travelHistory = $this->exists
            ? \App\Models\TravelHistory::where('order_id', $this->id)->latest('id')->first()
            : ($this->relationLoaded('travelHistory') ? $this->travelHistory : null);

        $arrivalDateTime = null;

        if ($travelHistory?->status === 'completed') {
            $arrivalDateTime = $travelHistory->resolveArrivalDateTime();

            if (!$arrivalDateTime && !empty($travelHistory->arrived_at)) {
                $arrivalDateTime = \Carbon\Carbon::parse($travelHistory->arrived_at);
            }
        }

        $hasReachedArrivalStage = in_array($this->status, [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
        ], true);

        if (!$arrivalDateTime && $hasReachedArrivalStage && $this->info?->arrival_date) {
            $arrivalDateTime = \Carbon\Carbon::parse($this->info->arrival_date);
        }

        if (!$arrivalDateTime && $hasReachedArrivalStage && $this->maid?->arrival_date) {
            $arrivalDateTime = \Carbon\Carbon::parse($this->maid->arrival_date);
        }

        return $arrivalDateTime;
    }

    public function recruitmentContractExpiryDate(?\Carbon\Carbon $arrivalDateTime = null): ?\Carbon\Carbon
    {
        $arrivalDateTime ??= $this->resolveRecruitmentArrivalDateTime();

        if (!$arrivalDateTime) {
            return null;
        }

        $durationInYears = (int) ($this->info?->contract_duration ?: 2);

        return $arrivalDateTime->copy()->addYears($durationInYears);
    }

    public function calculateWarrantyStatus()
    {
        if (!in_array($this->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true)) {
            return;
        }

        // يبدأ ضمان الاستقدام من تاريخ الوصول الفعلي فقط.
        $arrivalDateTime = $this->resolveRecruitmentArrivalDateTime();

        if (!$arrivalDateTime) {
            $updates = [
                'warranty_status' => 'not_applicable',
                'warranty_start_date' => null,
                'warranty_end_date' => null,
            ];
            $this->fill($updates);

            if ($this->isDirty(array_keys($updates))) {
                $this->save();
            }

            return;
        }

        $warrantyEndDate = $arrivalDateTime->copy()->addDays(AutomationSettings::warrantyPeriodDays());
        $updates = [
            'warranty_start_date' => $arrivalDateTime,
            'warranty_end_date' => $warrantyEndDate,
        ];

        // يظل الضمان نشطاً حتى نهاية تاريخ الانتهاء الظاهر للمستخدم.
        if ($warrantyEndDate->copy()->endOfDay()->isPast()) {
            $updates['status'] = ContractStatusEnum::Completed->value;
            $updates['warranty_status'] = 'expired';
        } else {
            $updates['status'] = ContractStatusEnum::Arrival->value;
            $updates['warranty_status'] = 'active';
        }

        $this->fill($updates);

        if ($this->isDirty(array_keys($updates))) {
            $this->save();
        }
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
