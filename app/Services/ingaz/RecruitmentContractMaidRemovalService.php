<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\MaidUnavailableRecord;
use App\Models\RecruitmentContractStepCompletion;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentContractMaidRemovalService
{
    public const STATUSES = ['new', 'accreditation_contracts', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'];

    public function remove(Contract $contract, int $expectedMaidId, string $status, ?string $reason, ?int $adminId): void
    {
        if (!in_array($status, ['available', 'backout'], true) || ($status === 'backout' && !filled($reason))) {
            throw ValidationException::withMessages(['reason' => 'اختر حالة العاملة وأدخل سبب الباك أوت.']);
        }
        DB::transaction(function () use ($contract, $expectedMaidId, $status, $reason, $adminId) {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            if (!in_array($locked->type, ['recruitment', 'maid'], true)
                || !in_array($locked->status, self::STATUSES, true)) {
                throw ValidationException::withMessages(['contract' => 'لا يمكن حذف العاملة في حالة العقد الحالية.']);
            }
            if (!$locked->maid_id || (int) $locked->maid_id !== $expectedMaidId) {
                throw ValidationException::withMessages(['contract' => 'تغيرت العاملة المرتبطة بالعقد؛ حدّث الصفحة.']);
            }
            $blockers = app(RecruitmentContractStageRollbackService::class)->blockingReasons($locked, 'pending_maid_selection');
            if ($locked->travelHistory()->exists()) {
                $blockers[] = 'وجود رحلة مرتبطة بالعاملة؛ ألغِ الرحلة أولًا';
            }
            if ($blockers) {
                throw ValidationException::withMessages(['contract' => implode('، ', $blockers)]);
            }
            $maid = Maid::query()->lockForUpdate()->findOrFail($expectedMaidId);
            $order = $locked->order()->lockForUpdate()->first();
            if ($order && $order->maid_id && (int) $order->maid_id !== $expectedMaidId) {
                throw ValidationException::withMessages(['contract' => 'العاملة في الطلب المرتبط لا تطابق العاملة في العقد.']);
            }
            // Other live assignments must keep their worker reserved.
            $otherContract = Contract::withoutGlobalScopes()->where('maid_id', $maid->id)->where('id', '!=', $locked->id)
                ->whereIn('status', array_merge(self::STATUSES, ['pending_maid_selection', 'shelter', 'sari', 'trial', 'in_progress', 'under_warranty']))->exists();
            $otherOrder = Order::withoutGlobalScopes()->where('maid_id', $maid->id)
                ->whereNull('deleted_at')
                ->when($order, fn ($q) => $q->where('id', '!=', $order->id))
                ->whereNotIn('status', Order::nonBlockingMaidAssignmentStatuses())->exists();
            if ($otherContract || $otherOrder) {
                throw ValidationException::withMessages(['contract' => 'العاملة مرتبطة بعقد أو طلب آخر؛ لا يمكن تغيير إتاحتها.']);
            }
            $previousStatus = $locked->status;
            $locked->update([
                'maid_id' => null, 'status' => ContractStatusEnum::PendingMaidSelection->value,
                'warranty_status' => 'not_applicable', 'warranty_start_date' => null, 'warranty_end_date' => null,
            ]);
            if ($order) $order->update(['maid_id' => null]);
            RecruitmentContractStepCompletion::where('contract_id', $locked->id)->delete();
            $maid->update([
                'status' => $status, 'available' => $status === 'available',
                'back_out_reason' => $status === 'backout' ? $reason : null,
            ]);
            if ($status === 'backout') {
                MaidUnavailableRecord::create([
                    'maid_id' => $maid->id, 'contract_id' => $locked->id, 'reason' => $reason, 'admin_id' => $adminId,
                ]);
            }
            $locked->contract_status_logs()->create([
                'status' => $locked->status, 'previous_status' => $previousStatus,
                'performed_by_id' => $adminId, 'date' => now()->toDateString(),
                'notes' => 'حذف العاملة من العقد بواسطة المستخدم #' . $adminId
                    . '؛ العاملة: ' . $maid->name . ' (#' . $maid->id . ')؛ الحالة السابقة للعقد: ' . $previousStatus
                    . '؛ حالة العاملة: ' . ($status === 'available' ? 'متاحة' : 'باك أوت')
                    . ($reason ? '؛ السبب: ' . $reason : ''),
            ]);
        });
    }
}
