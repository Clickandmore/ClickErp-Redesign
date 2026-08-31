<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RecruitmentContractReturnRestorationService
{
    /**
     * Restore a cancelled recruitment contract to the status recorded immediately
     * before the latest "back" status, provided no later dependency makes that unsafe.
     */
    public function restore(Contract $contract, ?int $adminId = null, ?string $selectedPreviousStatus = null): Contract
    {
        return DB::transaction(function () use ($contract, $adminId, $selectedPreviousStatus) {
            $lockedContract = Contract::query()
                ->whereKey($contract->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedContract->status !== ContractStatusEnum::Back->value) {
                throw ValidationException::withMessages([
                    'contract' => 'يمكن استعادة العقود المرتجعة فقط.',
                ]);
            }

            $previousStatus = $this->validRestorableStatus($selectedPreviousStatus)
                ?: $this->resolvePreviousStatus($lockedContract);
            if (!$previousStatus) {
                throw ValidationException::withMessages([
                    'contract' => 'تعذر تحديد حالة العقد السابقة من سجل الحالات، لذلك لم يتم إجراء أي تغيير.',
                ]);
            }

            $blockers = $this->blockingReasons($lockedContract);
            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يمكن استعادة العقد بسبب: ' . implode('، ', $blockers) . '.',
                ]);
            }

            $this->removeAutomaticReturnedCommissionMarker($lockedContract);

            $lockedContract->update(['status' => $previousStatus]);

            $statusLogData = [
                'status' => $previousStatus,
                'date' => now(),
                'notes' => 'تم التراجع عن إرجاع العقد وإعادته إلى حالته السابقة بواسطة المستخدم رقم '
                    . ($adminId ?: 'غير محدد'),
            ];

            if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                $statusLogData['previous_status'] = ContractStatusEnum::Back->value;
            }

            $lockedContract->contract_status_logs()->create($statusLogData);

            if ($lockedContract->maid_id) {
                $maid = $lockedContract->maid()->lockForUpdate()->first();
                if ($maid && $this->statusReservesMaid($previousStatus)) {
                    $maid->update([
                        'available' => false,
                        'status' => 'reserved',
                    ]);
                } elseif ($maid && $previousStatus === ContractStatusEnum::Shelter->value) {
                    $maid->update([
                        'available' => true,
                        'status' => 'available',
                    ]);
                }
            }

            logger()->info('Returned recruitment contract restored', [
                'contract_id' => $lockedContract->id,
                'restored_status' => $previousStatus,
                'restored_by' => $adminId,
            ]);

            return $lockedContract->fresh(['maid', 'contract_status_logs']);
        });
    }

    private function resolvePreviousStatus(Contract $contract): ?string
    {
        $latestBackLog = $contract->contract_status_logs()
            ->where('status', ContractStatusEnum::Back->value)
            ->latest('id')
            ->first();

        if ($latestBackLog && Schema::hasColumn('contract_status_logs', 'previous_status')) {
            $storedStatus = $this->validRestorableStatus($latestBackLog->previous_status);
            if ($storedStatus) {
                return $storedStatus;
            }
        }

        if ($latestBackLog) {
            $previousLogStatus = $contract->contract_status_logs()
                ->where('id', '<', $latestBackLog->id)
                ->where('status', '!=', ContractStatusEnum::Back->value)
                ->latest('id')
                ->value('status');

            $previousLogStatus = $this->validRestorableStatus($previousLogStatus);
            if ($previousLogStatus) {
                return $previousLogStatus;
            }
        }

        // Activity log is a safe fallback for older contracts whose status logs
        // were imported incompletely.
        foreach ($contract->activities()->latest('id')->limit(25)->get() as $activity) {
            $properties = $activity->properties;
            $newStatus = data_get($properties, 'attributes.status');
            $oldStatus = data_get($properties, 'old.status');

            if ($newStatus === ContractStatusEnum::Back->value) {
                $oldStatus = $this->validRestorableStatus($oldStatus);
                if ($oldStatus) {
                    return $oldStatus;
                }
            }
        }

        return null;
    }

    private function validRestorableStatus($status): ?string
    {
        $status = trim((string) $status);
        $enum = ContractStatusEnum::tryFrom($status);

        if (!$enum || in_array($enum, [
            ContractStatusEnum::Back,
            ContractStatusEnum::Refunded,
            ContractStatusEnum::Closed,
            ContractStatusEnum::Sari,
        ], true)) {
            return null;
        }

        return $enum->value;
    }

    private function statusReservesMaid(string $status): bool
    {
        return in_array($status, [
            ContractStatusEnum::New->value,
            ContractStatusEnum::AccreditationContracts->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Trial->value,
            ContractStatusEnum::InProgress->value,
            ContractStatusEnum::ClientContracted->value,
            ContractStatusEnum::DelegateSelected->value,
            ContractStatusEnum::UnderWarranty->value,
        ], true);
    }

    private function blockingReasons(Contract $contract): array
    {
        $blockers = [];

        if ($contract->invoice()->exists()) {
            $blockers[] = 'وجود فاتورة مرتبطة بالعقد';
        }
        if ($contract->recruitmentRefund()->exists()) {
            $blockers[] = 'وجود عملية استرداد مالي';
        }
        if ($contract->recruitmentCreditNotes()->exists()) {
            $blockers[] = 'وجود إشعار دائن';
        }
        if ($contract->financeBonds()->exists()) {
            $blockers[] = 'وجود سند مالي';
        }
        if ($contract->musanedTransferItem()->exists()) {
            $blockers[] = 'وجود تحويل مساند';
        }
        if ($contract->sponsorshipTransfers()->withTrashed()->exists()) {
            $blockers[] = 'وجود عملية نقل خدمات';
        }
        if ($contract->returnRequests()->where('status', 'pending')->exists()) {
            $blockers[] = 'وجود طلب إرجاع قيد المعالجة';
        }
        if ($contract->deportationProcedure()->exists()) {
            $blockers[] = 'وجود إجراء ترحيل مرتبط بالعقد';
        }

        if ($contract->maid_id && $contract->maid) {
            $hasOtherActiveContract = $contract->maid->contracts()
                ->whereKeyNot($contract->id)
                ->whereNotIn('status', [
                    ContractStatusEnum::Back->value,
                    ContractStatusEnum::Refunded->value,
                    ContractStatusEnum::Completed->value,
                    ContractStatusEnum::Closed->value,
                ])
                ->exists();

            $hasOtherActiveOrder = $contract->maid->orders()
                ->when($contract->order_id, fn ($query) => $query->whereKeyNot($contract->order_id))
                ->whereIn('status', [
                    'new', 'under_process', 'contracted', 'contracted_without_contract',
                    'pending', 'approved', 'in_progress',
                ])
                ->exists();

            $hasActiveRentalContract = $contract->maid->rentalContracts()
                ->whereNotIn('status', ['cancelled', 'completed', 'closed'])
                ->exists();

            if ($hasOtherActiveContract || $hasOtherActiveOrder || $hasActiveRentalContract) {
                $blockers[] = 'ارتباط العاملة بعقد أو طلب نشط آخر';
            }
        }

        $commissionItem = $this->commissionItem($contract);
        if ($commissionItem && !$this->isAutomaticReturnedCommissionMarker($commissionItem)) {
            $hasExecutedReversal = $commissionItem->commission_reversal_transaction_id
                || app(RecruitmentCommissionService::class)->isZeroed($commissionItem)
                || CommissionBalanceTransaction::query()
                    ->where('reference_id', $commissionItem->id)
                    ->whereIn('reference_type', ['commission_reversal', 'commission_zero_adjustment'])
                    ->exists();

            if ($hasExecutedReversal) {
                $blockers[] = 'تنفيذ تسوية مالية على عمولة العقد';
            }
        }

        return array_values(array_unique($blockers));
    }

    private function commissionItem(Contract $contract): ?CommissionBatchItem
    {
        return CommissionBatchItem::query()
            ->with('batch')
            ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
            ->where('source_id', $contract->id)
            ->lockForUpdate()
            ->first();
    }

    private function isAutomaticReturnedCommissionMarker(CommissionBatchItem $item): bool
    {
        if ($item->legacy_source !== RecruitmentCommissionService::ZERO_SOURCE
            || (float) $item->commission_amount !== 0.0
            || !$item->batch) {
            return false;
        }

        return $item->batch->items()->count() === 1
            && !$item->batch->balanceTransactions()->exists()
            && !CommissionBalanceTransaction::query()
                ->where('reference_id', $item->id)
                ->whereIn('reference_type', ['commission_reversal', 'commission_zero_adjustment'])
                ->exists();
    }

    private function removeAutomaticReturnedCommissionMarker(Contract $contract): void
    {
        $item = $this->commissionItem($contract);
        if (!$item) {
            return;
        }

        if ($this->isAutomaticReturnedCommissionMarker($item)) {
            $batch = $item->batch;
            $item->delete();
            $batch->delete();
            return;
        }

        // No financial reversal happened yet; remove only the pending return note.
        if (!$item->commission_reversal_transaction_id) {
            $item->update([
                'commission_reversed_at' => null,
                'commission_reversal_reason' => null,
            ]);
        }
    }
}
