<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\TravelHistory;
use App\Support\AutomationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * إرجاع عقد الاستقدام خطوة واحدة إلى الوراء ضمن تسلسل المراحل فقط.
 *
 * العقود مرتبطة بفواتير وقيود محاسبية تُنشأ في مراحل محددة (إيراد الوصول، تكلفة
 * المكتب الخارجي، تحويل مساند، الاسترداد، نقل الخدمات...). لذلك لا يُسمح بالقفز
 * الحر بين المراحل: كل إرجاع ينقل العقد إلى المرحلة السابقة مباشرة، ويُرفض إذا
 * كان سيتخطى مرحلة أُثبتت فيها قيود مالية لم تُعكس، أو إذا كان العقد قد خرج من
 * سياق الاستقدام أصلاً (استرداد، نقل خدمات، ترحيل...).
 */
class RecruitmentContractStageRollbackService
{
    /** المرحلة السابقة مباشرة لكل مرحلة في تسلسل الاستقدام. */
    private const PREVIOUS_STAGE = [
        'completed' => 'arrival',
        'arrival' => 'tazker',
        'tazker' => 'taffez',
        'taffez' => 'tafwed',
        'tafwed' => 'msaned',
        'msaned' => 'new',
        'accreditation_contracts' => 'new',
    ];

    /** ترتيب المراحل، يُستخدم لمعرفة المراحل التي سيتخطاها الإرجاع. */
    private const STAGE_RANK = [
        'pending_maid_selection' => 0,
        'new' => 1,
        'accreditation_contracts' => 1,
        'msaned' => 2,
        'tafwed' => 3,
        'taffez' => 4,
        'tazker' => 5,
        'arrival' => 6,
        'completed' => 7,
    ];

    public function previousStatus(Contract $contract): ?string
    {
        return self::PREVIOUS_STAGE[(string) $contract->status] ?? null;
    }

    public function previousStatusFor(?string $status): ?string
    {
        return self::PREVIOUS_STAGE[(string) $status] ?? null;
    }

    /**
     * سبب عدم إمكانية الإرجاع من هذه المرحلة أصلاً (خارج تسلسل الاستقدام).
     */
    public function notRollbackableReason(?string $status): string
    {
        return match ($status) {
            ContractStatusEnum::Refunded->value => 'العقد مسترد ومكتمل مالياً ولا يمكن تغيير حالته',
            ContractStatusEnum::Back->value => 'العقد مرتجع؛ استخدم «التراجع عن الإرجاع» لإعادته إلى حالته السابقة',
            ContractStatusEnum::Shelter->value => 'العقد في الإيواء ولا يقبل الإرجاع من هذه الشاشة',
            ContractStatusEnum::PendingMaidSelection->value => 'العقد في المرحلة الأولى (بانتظار اختيار العاملة) ولا توجد مرحلة أسبق',
            ContractStatusEnum::New->value => 'العقد في المرحلة الأولى (عقود جديدة) ولا توجد مرحلة أسبق',
            default => 'لا يمكن إرجاع العقد من مرحلة ' . getContractStatus($status),
        };
    }

    /**
     * العوائق المالية والتشغيلية التي تمنع إرجاع العقد إلى المرحلة المستهدفة.
     *
     * @return string[]
     */
    public function blockingReasons(Contract $contract, ?string $targetStatus = null): array
    {
        $targetStatus ??= $this->previousStatus($contract);
        if (!$targetStatus) {
            return [$this->notRollbackableReason($contract->status)];
        }

        $blockers = [];
        $currentRank = self::STAGE_RANK[(string) $contract->status] ?? null;
        $targetRank = self::STAGE_RANK[$targetStatus] ?? null;
        $crosses = fn (string $stage): bool => $currentRank !== null
            && $targetRank !== null
            && $currentRank >= self::STAGE_RANK[$stage]
            && $targetRank < self::STAGE_RANK[$stage];

        // عوائق عامة: العقد خرج من سياق الاستقدام
        if ($contract->recruitmentRefund()->exists()) {
            $blockers[] = 'وجود عملية استرداد مالي للعقد';
        }
        if ($contract->recruitmentCreditNotes()->exists()) {
            $blockers[] = 'وجود إشعار دائن على فاتورة العقد';
        }

        $transfer = $contract->sponsorshipTransfers()
            ->whereNotIn('status', ['failed', 'rejected'])
            ->latest('id')
            ->first();
        if ($transfer) {
            $blockers[] = 'وجود عملية نقل خدمات رقم ' . $transfer->id . ' (' . $transfer->status . ')';
        }

        if ($contract->returnRequests()->where('status', 'pending')->exists()) {
            $blockers[] = 'وجود طلب إرجاع داخل الضمان قيد المعالجة';
        }
        if ($contract->deportationProcedure()->where('status', 'completed')->exists()) {
            $blockers[] = 'وجود إجراء ترحيل مكتمل مرتبط بالعقد';
        }

        // مكتمل → الوصول: لا معنى للإرجاع إذا كان الضمان منتهياً فعلاً، لأن المهمة
        // اليومية contracts:update-warranty-status ستعيد العقد إلى مكتمل.
        if ($contract->status === ContractStatusEnum::Completed->value
            && $targetStatus === ContractStatusEnum::Arrival->value) {
            $arrivalDateTime = $contract->resolveRecruitmentArrivalDateTime();
            if ($arrivalDateTime) {
                $warrantyEnd = $arrivalDateTime->copy()->addDays(AutomationSettings::warrantyPeriodDays());
                if ($warrantyEnd->copy()->endOfDay()->isPast()) {
                    $blockers[] = 'انتهاء فترة الضمان فعلياً بتاريخ ' . $warrantyEnd->toDateString()
                        . ' (سيُعاد العقد إلى مكتمل تلقائياً؛ صحّح تاريخ الوصول في الرحلة أولاً)';
                }
            }
        }

        // تخطي مرحلة الوصول: قيود الوصول والرحلة المكتملة
        if ($crosses('arrival')) {
            $invoice = $contract->invoice;

            if ($invoice && $this->invoiceHas('arrival_revenue_constraint_id') && $invoice->arrival_revenue_constraint_id) {
                $blockers[] = 'إثبات إيراد الوصول بالقيد رقم ' . $invoice->arrival_revenue_constraint_id;
            }

            if ($invoice
                && $this->invoiceHas('external_office_cost_constraint_id')
                && $invoice->external_office_cost_constraint_id
                && !($this->invoiceHas('external_office_cost_reversal_constraint_id')
                    && $invoice->external_office_cost_reversal_constraint_id)) {
                $blockers[] = 'إثبات تكلفة المكتب الخارجي بالقيد رقم ' . $invoice->external_office_cost_constraint_id;
            }

            $travelHistory = $contract->travelHistory()->latest('id')->first();
            if ($travelHistory && $travelHistory->status === 'completed') {
                $blockers[] = 'الرحلة رقم ' . $travelHistory->id . ' مكتملة (عدّل الرحلة أو ألغِها أولاً)';
            }
        }

        // تخطي مرحلة مساند: تحويل مساند مرتبط بالعقد
        if ($crosses('msaned') && $contract->musanedTransferItem()->exists()) {
            $blockers[] = 'وجود تحويل مساند مرتبط بالعقد';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * ينفّذ الإرجاع خطوة واحدة ويسجّله في سجل الحالات.
     *
     * @throws ValidationException
     */
    public function rollback(Contract $contract, ?int $adminId = null, ?string $date = null): Contract
    {
        return DB::transaction(function () use ($contract, $adminId, $date) {
            $locked = Contract::query()
                ->whereKey($contract->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = (string) $locked->status;
            $targetStatus = $this->previousStatus($locked);

            if (!$targetStatus) {
                throw ValidationException::withMessages([
                    'contract' => $this->notRollbackableReason($currentStatus),
                ]);
            }

            $blockers = $this->blockingReasons($locked, $targetStatus);
            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يمكن إرجاع العقد إلى مرحلة ' . getContractStatus($targetStatus)
                        . ' بسبب: ' . implode('، ', $blockers) . '.',
                ]);
            }

            $updates = ['status' => $targetStatus];

            if ((self::STAGE_RANK[$targetStatus] ?? 0) < self::STAGE_RANK['arrival']) {
                // العقد لم يعد في مرحلة وصول، فلا ضمان سارياً عليه.
                $updates['warranty_status'] = 'not_applicable';
                $updates['warranty_start_date'] = null;
                $updates['warranty_end_date'] = null;

                if (!$this->hasConfirmedArrival($locked) && !$this->hasActiveArrivalTrip($locked)) {
                    $locked->info()->update(['arrival_date' => null]);
                }
            } elseif ($this->isZeroDate($locked->warranty_start_date) || $this->isZeroDate($locked->warranty_end_date)) {
                // تواريخ صفرية موروثة من الاستيراد لا تمثل وصولاً حقيقياً.
                $updates['warranty_start_date'] = null;
                $updates['warranty_end_date'] = null;
            }

            $locked->update($updates);

            if ($targetStatus === ContractStatusEnum::Arrival->value) {
                $locked->calculateWarrantyStatus();
                $locked->refresh();
            }

            $logData = [
                'status' => $locked->status,
                'date' => $date ?: now()->toDateString(),
                'notes' => 'تم إرجاع العقد من ' . getContractStatus($currentStatus)
                    . ' إلى ' . getContractStatus($locked->status)
                    . ' (إرجاع خطوة واحدة ضمن تسلسل المراحل)',
            ];
            if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                $logData['previous_status'] = $currentStatus;
            }
            if ($adminId && Schema::hasColumn('contract_status_logs', 'performed_by_id')) {
                $logData['performed_by_id'] = $adminId;
            }
            $locked->contract_status_logs()->create($logData);

            logger()->info('Recruitment contract stage rolled back', [
                'contract_id' => $locked->id,
                'from' => $currentStatus,
                'to' => $locked->status,
                'admin_id' => $adminId,
            ]);

            return $locked->fresh(['contract_status_logs']);
        });
    }

    private function invoiceHas(string $column): bool
    {
        static $cache = [];

        return $cache[$column] ??= Schema::hasColumn('invoices', $column);
    }

    private function hasConfirmedArrival(Contract $contract): bool
    {
        return TravelHistory::withTrashed()
            ->where('order_id', $contract->id)
            ->where('travel_type', '!=', 'deportation')
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orWhereNotNull('arrived_at')
                    ->orWhereNotNull('arrival_confirmed_by');
            })
            ->exists();
    }

    private function hasActiveArrivalTrip(Contract $contract): bool
    {
        return $contract->travelHistory()
            ->whereNotIn('status', ['completed', 'cancelled', 'canceled', 'rejected'])
            ->exists();
    }

    private function isZeroDate($value): bool
    {
        if (!$value) {
            return false;
        }

        try {
            return \Carbon\Carbon::parse($value)->year < 1900;
        } catch (\Throwable) {
            return true;
        }
    }
}
