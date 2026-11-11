<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompletedRecruitmentMaidReleaseService
{
    /** @return array{eligible: bool, reason: ?string, expiry_date: ?string} */
    public function eligibility(Contract $contract): array
    {
        $contract->loadMissing(['info', 'maid']);
        $expiry = $contract->recruitmentContractExpiryDate();

        $reason = match (true) {
            $contract->status !== ContractStatusEnum::Completed->value => 'الإجراء متاح للعقود المكتملة فقط.',
            !$contract->maid_id || !$contract->maid => 'لا توجد عاملة مرتبطة بالعقد.',
            !$expiry => 'لا يمكن تحديد تاريخ انتهاء العقد لعدم وجود تاريخ وصول فعلي.',
            $expiry->copy()->endOfDay()->isFuture() => 'لم تمر مدة العقد كاملة بعد.',
            $this->isInsideShelter((int) $contract->maid_id) => 'العاملة موجودة حاليًا داخل الإيواء.',
            $this->hasOtherActiveContract($contract) => 'يوجد عقد استقدام نشط آخر للعاملة.',
            $this->hasBlockingOrder((int) $contract->maid_id) => 'يوجد طلب استقدام نشط مرتبط بالعاملة.',
            default => null,
        };

        return [
            'eligible' => $reason === null,
            'reason' => $reason,
            'expiry_date' => $expiry?->toDateString(),
        ];
    }

    public function release(Contract $contract, ?int $adminId): Maid
    {
        return DB::transaction(function () use ($contract, $adminId) {
            $locked = Contract::query()->with(['info', 'maid'])->lockForUpdate()->findOrFail($contract->id);
            $eligibility = $this->eligibility($locked);
            if (!$eligibility['eligible']) {
                throw new RuntimeException($eligibility['reason'] ?? 'لا يمكن إتاحة العاملة.');
            }

            $maid = Maid::query()->lockForUpdate()->findOrFail($locked->maid_id);
            $maid->forceFill([
                'status' => 'available',
                'available' => true,
                'available_at' => now()->toDateString(),
                'is_show' => true,
                'maid_accommodation_type' => 1,
            ])->save();

            $locked->contract_status_logs()->create([
                'status' => ContractStatusEnum::Completed->value,
                'date' => now(),
                'notes' => 'تم اعتماد اكتمال مدة العقد وتسفير العاملة وإتاحتها لعقد استقدام جديد بواسطة المستخدم #' . ($adminId ?: 'غير محدد'),
            ]);

            $activity = activity('recruitment_contract')
                ->performedOn($locked)
                ->withProperties(['maid_id' => $maid->id, 'expiry_date' => $eligibility['expiry_date']]);
            if ($admin = auth('admin')->user()) {
                $activity->causedBy($admin);
            }
            $activity->log('تم إتاحة العاملة لعقد استقدام جديد بعد اكتمال مدة العقد وتسفيرها');

            return $maid;
        });
    }

    private function isInsideShelter(int $maidId): bool
    {
        if (Maid::query()->find($maidId)?->rentalContracts()
            ->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_RENTAL_STATUSES)->holdingMaid()->exists()) {
            return false;
        }

        return Contract::query()
            ->where('maid_id', $maidId)
            ->where('status', ContractStatusEnum::Shelter->value)
            ->whereDoesntHave('sponsorshipTransfers', function ($transfer) {
                $transfer->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_TRANSFER_STATUSES);
            })
            ->where(function ($query) {
                $query->whereDoesntHave('info')->orWhereHas('info', function ($info) {
                    $info->whereNull('is_outside_shelter')->orWhere('is_outside_shelter', false);
                });
            })->exists();
    }

    private function hasOtherActiveContract(Contract $contract): bool
    {
        return Contract::query()->acrossBranches()->where('maid_id', $contract->maid_id)->whereKeyNot($contract->id)
            ->whereIn('status', ['pending_maid_selection', 'new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])
            ->exists();
    }

    private function hasBlockingOrder(int $maidId): bool
    {
        return Order::query()->acrossBranches()->where('maid_id', $maidId)
            ->whereNotIn('status', Order::nonBlockingMaidAssignmentStatuses())->exists();
    }
}
