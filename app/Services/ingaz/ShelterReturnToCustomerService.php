<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ShelterCostEntry;
use App\Models\ingaz\Maid;
use App\Services\commissions\RecruitmentCommissionService;
use App\Support\AutomaticJournalEntries;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShelterReturnToCustomerService
{
    public function returnToCustomer(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $maid = Maid::query()->whereKey($locked->maid_id)->lockForUpdate()->first();
            $locked->load(['info', 'latestShelterLog', 'returnRequests']);
            $reject = function (string $message): void {
                throw ValidationException::withMessages(['contract' => $message]);
            };
            if ($locked->status !== ContractStatusEnum::Shelter->value || !$locked->info || $locked->info->is_outside_shelter) {
                $reject('الإجراء متاح للعاملة الموجودة داخل الإيواء فقط.');
            }
            if (!$maid || !$locked->user_id) {
                $reject('لا يوجد عميل أصلي أو عاملة مرتبطة بالعقد.');
            }
            if (app(RecruitmentCommissionService::class)->isReturned($locked)) {
                $reject('العقد مرتجع أو مسترد؛ لا يمكن إعادة العاملة للعميل بهذا الإجراء.');
            }
            if ($locked->sponsorshipTransfers()->whereNotIn('status', ['cancelled', 'rejected', 'failed'])->exists()
                || $maid->rentalContracts()->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_RENTAL_STATUSES)->holdingMaid()->exists()
                || $locked->deportationProcedure()->whereNotIn('status', ['cancelled'])->exists()) {
                $reject('يوجد مسار نقل خدمات أو تأجير أو ترحيل للعاملة؛ يجب تسويته قبل إعادتها للعميل.');
            }
            $ledger = app(ShelterCostLedgerService::class);
            if ($blocker = $ledger->returnToCustomerBlocker($locked)) {
                $reject($blocker);
            }
            if (!AutomaticJournalEntries::enabled() && ShelterCostEntry::query()->where('maid_id', $maid->id)->whereNotNull('constraint_id')->exists()
                && $ledger->balanceForContract($locked) > 0) {
                $reject('يوجد قيد دخول إيواء؛ يلزم تفعيل القيود الآلية لإنشاء القيد العكسي.');
            }
            $ledger->returnToCustomer($locked, now()->toDateString());
            // Mark the physical exit while the contract is still in shelter so its stay closes with this reason.
            $locked->info->update(['is_outside_shelter' => true, 'outside_shelter_at' => now(), 'outside_shelter_reason' => 'return_to_customer']);
            $locked->status = ContractStatusEnum::Completed->value;
            $locked->save();
            $locked->info->update([
                'shelter_id' => null, 'maid_date_back' => null, 'receipt_source' => null, 'wants_to_work' => null,
                'is_available_for_instant' => false, 'is_outside_shelter' => false, 'outside_shelter_at' => null,
                'outside_shelter_reason' => null, 'outside_shelter_reference_type' => null,
                'outside_shelter_reference_id' => null, 'outside_shelter_by' => null,
            ]);
            $maid->update(['status' => 'contracted', 'available' => false, 'is_show' => false]);
            $locked->contract_status_logs()->create([
                'status' => ContractStatusEnum::Completed->value, 'previous_status' => ContractStatusEnum::Shelter->value,
                'date' => now(), 'notes' => 'إعادة العاملة إلى العميل الأصلي وإخراجها من الإيواء مع تسوية رصيد الإيواء إن وجد.',
            ]);
            $activity = activity()->performedOn($locked);
            if ($admin = auth('admin')->user()) {
                $activity->causedBy($admin);
            }
            $activity->log('إعادة العاملة إلى العميل الأصلي من الإيواء');
            return $locked->fresh();
        });
    }
}
