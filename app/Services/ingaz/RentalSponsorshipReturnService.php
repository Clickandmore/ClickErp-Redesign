<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\CompanyProfile;
use App\Models\Contract;
use App\Models\RentalSponsorshipReturn;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\Maid;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Operational ownership changes only. Never calls finance or reverses an earlier transfer. */
class RentalSponsorshipReturnService
{
    public function create(SponsorshipTransfer $source, array $data, int $adminId): RentalSponsorshipReturn
    {
        return DB::transaction(function () use ($source, $data, $adminId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($source->contract_id);
            $maid = Maid::withoutBranchScope()->without('translations')->lockForUpdate()->findOrFail($contract->maid_id);
            $source = SponsorshipTransfer::query()->lockForUpdate()->findOrFail($source->id);
            $this->check($source->status === 'transferred' && $source->new_sponsor_id, 'يجب اختيار نقل كفالة مكتمل إلى عميل.');
            $this->check($source->isRentalWorkflow() || $maid->jobTypes()->where('job_type_id', 2)->exists(), 'هذا المسار مخصص لعاملات الإيجار.');
            $this->check(!$contract->company_profile_id && (int) $contract->user_id === (int) $source->new_sponsor_id, 'الكفيل الحالي تغير بعد عملية النقل المحددة.');
            $this->check(in_array($contract->status, ['completed', 'shelter'], true), 'حالة العقد لا تسمح بطلب إعادة الكفالة.');
            $this->check(!RentalSponsorshipReturn::withoutBranchScope()->where('maid_id', $maid->id)
                ->whereNotIn('status', ['cancelled', 'transferred'])->exists(), 'يوجد طلب رجوع قائم لهذه العاملة.');
            $this->assertNoOtherAssignment($maid, $source);
            $company = CompanyProfile::current();
            $this->check((bool) $company, 'أكمل بيانات الشركة أولاً.');
            $request = RentalSponsorshipReturn::create([
                'source_transfer_id' => $source->id, 'contract_id' => $contract->id,
                'maid_id' => $maid->id, 'branch_id' => $contract->branch_id,
                'current_sponsor_id' => $source->new_sponsor_id, 'company_profile_id' => $company->id,
                'shelter_id' => $data['shelter_id'], 'request_date' => $data['request_date'],
                'notes' => $data['notes'] ?? null, 'status' => 'pending',
                'history' => [['status' => 'pending', 'at' => now()->toIso8601String(), 'admin_id' => $adminId]],
            ]);
            $maid->update(['status' => 'reserved', 'available' => false, 'is_show' => false]);
            $contract->info()->update(['wants_to_work' => 'no', 'is_available_for_instant' => false]);
            return $request;
        });
    }

    public function advance(RentalSponsorshipReturn $request, string $target, int $adminId, ?string $reason = null, ?string $attachment = null, bool $readyForRental = true): RentalSponsorshipReturn
    {
        return DB::transaction(function () use ($request, $target, $adminId, $reason, $attachment, $readyForRental) {
            // Same lock order as creation: contract, worker, then operation.
            $contract = Contract::query()->lockForUpdate()->findOrFail($request->contract_id);
            $maid = Maid::withoutBranchScope()->without('translations')->lockForUpdate()->findOrFail($request->maid_id);
            $request = RentalSponsorshipReturn::query()->lockForUpdate()->findOrFail($request->id);
            $this->check(!in_array($request->status, ['transferred', 'cancelled'], true), 'الطلب مغلق بالفعل.');
            $this->check($target === 'cancelled' || (RentalSponsorshipReturn::NEXT[$request->status] ?? null) === $target, 'يجب إتمام مراحل الطلب بالترتيب.');
            $this->check(!$contract->company_profile_id && (int) $contract->user_id === (int) $request->current_sponsor_id
                && (int) $contract->maid_id === (int) $maid->id, 'تغيرت بيانات العاملة أو الكفيل الحالي؛ راجع الطلب.');
            if ($target !== 'cancelled') {
                $source = $request->sourceTransfer;
                $this->check($source && $source->status === 'transferred', 'طلب النقل الأصلي غير موجود أو تغيرت حالته.');
                $this->assertNoOtherAssignment($maid, $source);
            } else {
                $this->check(filled($reason), 'سبب الإلغاء مطلوب.');
            }
            if ($target === 'received') {
                // Avoid recruitment commission/status financial listeners. Physical
                // admission and the operational audit trail are recorded explicitly.
                $wasInside = $contract->status === 'shelter' && $contract->info && !$contract->info->is_outside_shelter;
                $contract->updateQuietly(['status' => ContractStatusEnum::Shelter->value]);
                $contract->info()->updateOrCreate([], [
                    'shelter_id' => $request->shelter_id, 'maid_date_back' => now()->toDateString(),
                    'is_outside_shelter' => false, 'outside_shelter_at' => null,
                    'outside_shelter_reason' => null, 'outside_shelter_reference_type' => null,
                    'outside_shelter_reference_id' => null, 'outside_shelter_by' => null,
                    'wants_to_work' => 'no', 'is_available_for_instant' => false,
                ]);
                $maid->update(['maid_accommodation_type' => 2]);
                app(\App\Services\ShelterStayService::class)->sync($contract->fresh(), (bool) $wasInside);
                app(\App\Services\ShelterDocumentMovementService::class)->refreshAdmissionData($contract->fresh());
            }
            if ($target === 'transferred') {
                $company = CompanyProfile::findOrFail($request->company_profile_id);
                $this->check($contract->status === ContractStatusEnum::Shelter->value && $contract->info && !$contract->info->is_outside_shelter, 'يجب إثبات استلام العاملة داخل الإيواء أولاً.');
                $contract->updateQuietly(['user_id' => null, 'company_profile_id' => $company->id,
                    'company_finance_account_id' => $company->account_id]);
                $contract->info()->update(['wants_to_work' => $readyForRental ? 'yes' : 'no']);
                $maid->update(['status' => $readyForRental ? 'available' : 'reserved', 'available' => $readyForRental,
                    'available_at' => $readyForRental ? now()->toDateString() : null]);
            }
            // Cancellation keeps the actual location and current sponsor. Release
            // for rental requires a completed ownership transfer, never cancellation.
            $history = $request->history;
            $history[] = ['status' => $target, 'at' => now()->toIso8601String(), 'admin_id' => $adminId,
                'reason' => $reason, 'attachment' => $attachment,
                'ready_for_rental' => $target === 'transferred' ? $readyForRental : null];
            $request->update(['status' => $target, 'history' => $history]);
            if (in_array($target, ['received', 'transferred'], true)) {
                $contract->contract_status_logs()->create([
                    'status' => $contract->status, 'date' => now(),
                    'notes' => 'طلب إعادة الكفالة للشركة #' . $request->id . ': ' . RentalSponsorshipReturn::LABELS[$target],
                ]);
            }
            return $request;
        });
    }

    private function assertNoOtherAssignment(Maid $maid, SponsorshipTransfer $source): void
    {
        $this->check(!in_array($maid->status, ['deported', 'suspended', 'backout'], true), 'حالة العاملة الحالية تمنع إكمال نقل الكفالة.');
        $this->check(!$source->contract->hasEscapeReport(), 'يوجد بلاغ هروب على العاملة.');
        $this->check(!$maid->rentalContracts()->withoutBranchScope()->holdingMaid()
            ->whereIn('status', ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'suspended', 'frozen'])->exists(), 'يوجد عقد إيجار قائم على العاملة.');
        $this->check(!$maid->reservationOrders()->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->exists(), 'يوجد طلب قائم على العاملة.');
        $this->check(!SponsorshipTransfer::withoutBranchScope()->whereHas('contract', fn ($q) => $q->withoutBranchScope()->where('maid_id', $maid->id))
            ->where('id', '>', $source->id)->whereNotIn('status', ['failed', 'rejected', 'cancelled'])->exists(), 'توجد حركة نقل أحدث لهذه العاملة.');
    }

    private function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw ValidationException::withMessages(['return' => $message]);
        }
    }
}
