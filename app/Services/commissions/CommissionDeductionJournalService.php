<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class CommissionDeductionJournalService
{
    public function __construct(
        private readonly ConstraintService $constraints,
        private readonly YearService $years,
        private readonly CommissionAccrualService $accruals
    ) {}

    public function post(CommissionBalanceTransaction $transaction, ?int $actorId = null, ?string $date = null): Constraint
    {
        return DB::transaction(function () use ($transaction, $actorId, $date) {
            Admin::withTrashed()->whereKey($transaction->marketer_id)->lockForUpdate()->firstOrFail();
            $locked = CommissionBalanceTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($locked->constraint_id) {
                $existing = Constraint::query()->find($locked->constraint_id);
                if (!$existing) {
                    throw ValidationException::withMessages(['deduction' => 'قيد الخصم المرتبط مفقود أو محذوف؛ يلزم مراجعته قبل إنشاء أي قيد إضافي.']);
                }
                return $existing;
            }
            $item = CommissionBatchItem::query()->with('batch')->whereKey($locked->reference_id)->lockForUpdate()->firstOrFail();
            if ($locked->reference_type !== 'commission_reversal' || $locked->type !== CommissionBalanceTransaction::TYPE_DEBIT
                || (float) $locked->amount <= 0 || (int) $item->commission_reversal_transaction_id !== (int) $locked->id
                || (int) $item->batch?->marketer_id !== (int) $locked->marketer_id
                || $item->batch?->status !== CommissionBatch::STATUS_APPROVED
                || (float) $locked->amount > (float) $item->commission_amount) {
                throw ValidationException::withMessages(['deduction' => 'حركة الخصم لا تطابق العمولة المعتمدة؛ لم يتم إنشاء قيد.']);
            }
            if ($item->commission_reversal_constraint_id) {
                $existing = Constraint::query()->with('constraintDetails')->find($item->commission_reversal_constraint_id);
                if (! $existing
                    || round((float) $existing->constraintDetails->sum('debtor_value'), 2) !== round((float) $locked->amount, 2)
                    || round((float) $existing->constraintDetails->sum('creditor_value'), 2) !== round((float) $locked->amount, 2)) {
                    throw ValidationException::withMessages(['deduction' => 'قيد عكس العمولة السابق مفقود أو لا يطابق مبلغ الخصم. اطلب من المحاسب مراجعته؛ لم يتم إنشاء قيد إضافي.']);
                }
                $locked->update(['constraint_id' => $existing->id]);
                $transaction->refresh();
                return $existing;
            }
            $actorId ??= $locked->created_by ?? $item->batch?->approved_by ?? $item->batch?->created_by ?? auth('admin')->id();
            $batch = $item->batch;
            $expense = $batch->expense_account_id
                ? Account::query()->findOrFail($batch->expense_account_id) : $this->accruals->expenseAccount();
            $payable = $batch->payable_account_id
                ? Account::query()->findOrFail($batch->payable_account_id)
                : $this->accruals->payableAccountForMarketer((int) $locked->marketer_id, $actorId);
            if ($expense->status !== AccountStatusEnum::Debtor || $payable->status !== AccountStatusEnum::Creditor
                || $expense->children()->exists() || $payable->children()->exists() || $expense->id === $payable->id) {
                throw ValidationException::withMessages(['deduction' => 'حساب مصروف العمولة أو حساب المسوق غير صالح لإثبات قيد الخصم.']);
            }
            $date = Carbon::parse($date ?? now())->toDateString();
            $year = $this->years->checkDateRange($date);
            if (!$year) {
                throw ValidationException::withMessages(['deduction' => 'تاريخ قيد الخصم لا يقع داخل سنة مالية نشطة.']);
            }
            $amount = round((float) $locked->amount, 2);
            $statement = mb_substr('خصم عمولة المسوق ' . $locked->marketer?->name . ' / حركة #' . $locked->id
                . ' / ' . $locked->notes, 0, 500);
            $data = [
                'branch_id' => $batch->branch_id ?? $locked->branch_id,
                'date' => $date, 'statement' => $statement, 'value' => $amount, 'created_by_id' => $actorId,
                'account_id' => [$payable->id, $expense->id], 'account_type' => ['debtor', 'creditor'],
                'debtor_value' => [$amount, 0], 'creditor_value' => [0, $amount],
                'statement_account' => [$statement, $statement], 'cost_center_id' => [null, $batch->cost_center_id],
            ];
            $constraint = $this->constraints->storeConstraint($data, $year, ConstraintTypiesEnum::MarketerEntry);
            $this->constraints->storeConstraintDetails($data['account_id'], $constraint->id, $data);
            $locked->update(['constraint_id' => $constraint->id]);
            $item->update(['commission_reversal_constraint_id' => $constraint->id]);
            $transaction->refresh();
            return $constraint;
        });
    }

    public function repairForMarketer(int $marketerId, ?int $actorId = null, ?string $date = null): int
    {
        return DB::transaction(function () use ($marketerId, $actorId, $date) {
            Admin::withTrashed()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $transactions = CommissionBalanceTransaction::query()->where('marketer_id', $marketerId)
                ->where('reference_type', 'commission_reversal')->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
                ->where('amount', '>', 0)->whereNull('constraint_id')->orderBy('id')->lockForUpdate()->get();
            foreach ($transactions as $transaction) {
                $this->post($transaction, $actorId, $date);
            }
            return $transactions->count();
        });
    }
}
