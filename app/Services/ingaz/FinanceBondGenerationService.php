<?php

namespace App\Services\ingaz;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Services\BondService;

class FinanceBondGenerationService
{
    public function __construct(protected BondService $bondService)
    {
    }

    public function statistics(): array
    {
        if (! $this->hasRequiredTables()) {
            return [
                'candidate_constraints' => 0,
                'existing_bonds' => 0,
                'ready' => false,
            ];
        }

        return [
            'candidate_constraints' => Constraint::query()
                ->where('type', ConstraintTypiesEnum::DailyEntry->value)
                ->whereDoesntHave('bond')
                ->count(),
            'existing_bonds' => Bond::query()
                ->whereNotNull('constraint_id')
                ->count(),
            'ready' => true,
        ];
    }

    public function generateFromDailyConstraints(?int $adminId = null): array
    {
        $result = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (! $this->hasRequiredTables()) {
            $result['errors'][] = 'جداول المالية المطلوبة غير موجودة.';
            return $result;
        }

        Constraint::query()
            ->with(['constraintDetails.account', 'bond'])
            ->where('type', ConstraintTypiesEnum::DailyEntry->value)
            ->whereDoesntHave('bond')
            ->orderBy('id')
            ->chunkById(100, function ($constraints) use (&$result, $adminId) {
                foreach ($constraints as $constraint) {
                    $result['processed']++;

                    try {
                        DB::transaction(function () use ($constraint, $adminId, &$result) {
                            $freshConstraint = Constraint::query()
                                ->with(['constraintDetails.account', 'bond'])
                                ->lockForUpdate()
                                ->find($constraint->id);

                            if (! $freshConstraint || $freshConstraint->bond) {
                                $result['skipped']++;
                                return;
                            }

                            $bondPayload = $this->bondPayloadForConstraint($freshConstraint);

                            if (! $bondPayload) {
                                $result['skipped']++;
                                return;
                            }

                            $type = $bondPayload['type'];

                            $bondData = [
                                'code' => $this->bondService->lastCode($type),
                                'type' => $type,
                                'payment_type' => $bondPayload['payment_type'],
                                'bond_book_num' => 'قيد #' . ($freshConstraint->code ?: $freshConstraint->id),
                                'debit_account_id' => $bondPayload['debit_account_id'],
                                'credit_account_id' => $bondPayload['credit_account_id'],
                                'value' => $bondPayload['value'],
                                'date' => $freshConstraint->date,
                                'statement' => $freshConstraint->statement ?: 'سند مولد من قيد محاسبي',
                                'constraint_id' => $freshConstraint->id,
                                'is_confirm' => 1,
                                'created_by_id' => $adminId ?: (auth('admin')->id() ?: 1),
                                'year_id' => $freshConstraint->year_id,
                                'cost_center_id' => $bondPayload['cost_center_id'],
                            ];

                            foreach ([
                                'is_tax' => 0,
                                'net' => $bondPayload['value'],
                                'account_text' => $bondPayload['account_text'],
                                'general_type' => 0,
                            ] as $column => $value) {
                                if (Schema::hasColumn('fn_bonds', $column)) {
                                    $bondData[$column] = $value;
                                }
                            }

                            $bond = Bond::query()->create($bondData);

                            $freshConstraint->update([
                                'type' => $type === BondTypes::Sarf->value
                                    ? ConstraintTypiesEnum::SarfBond->value
                                    : ConstraintTypiesEnum::QapdBond->value,
                            ]);

                            if ($bond->exists) {
                                $result['created']++;
                            }
                        });
                    } catch (\Throwable $exception) {
                        $result['skipped']++;
                        if (count($result['errors']) < 10) {
                            $result['errors'][] = 'القيد #' . ($constraint->code ?: $constraint->id) . ': ' . $exception->getMessage();
                        }
                    }
                }
            });

        return $result;
    }

    private function bondPayloadForConstraint(Constraint $constraint): ?array
    {
        $details = $constraint->constraintDetails
            ->filter(fn (ConstraintDetails $detail) => $detail->account)
            ->values();

        $debtors = $details
            ->filter(fn (ConstraintDetails $detail) => (float) $detail->debtor_value > 0)
            ->values();

        $creditors = $details
            ->filter(fn (ConstraintDetails $detail) => (float) $detail->creditor_value > 0)
            ->values();

        if ($debtors->count() !== 1 || $creditors->count() !== 1) {
            return null;
        }

        $debtor = $debtors->first();
        $creditor = $creditors->first();
        $debtorValue = round((float) $debtor->debtor_value, 2);
        $creditorValue = round((float) $creditor->creditor_value, 2);

        if ($debtorValue <= 0 || abs($debtorValue - $creditorValue) > 0.01) {
            return null;
        }

        $debtorIsCash = $this->isCashOrBankAccount($debtor);
        $creditorIsCash = $this->isCashOrBankAccount($creditor);

        if ($debtorIsCash === $creditorIsCash) {
            return null;
        }

        $cashDetail = $debtorIsCash ? $debtor : $creditor;
        $counterDetail = $debtorIsCash ? $creditor : $debtor;

        return [
            'type' => $debtorIsCash ? BondTypes::Qabd->value : BondTypes::Sarf->value,
            'payment_type' => $this->paymentTypeForAccount($cashDetail),
            'debit_account_id' => (int) $debtor->account_id,
            'credit_account_id' => (int) $creditor->account_id,
            'value' => $debtorValue,
            'cost_center_id' => $counterDetail->cost_center_id ?: $cashDetail->cost_center_id,
            'account_text' => $counterDetail->account?->name ?: '-',
        ];
    }

    private function isCashOrBankAccount(ConstraintDetails $detail): bool
    {
        $type = $this->accountTypeValue($detail);

        return in_array($type, [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Box->value,
            AccountTypesEnum::ElectronicWallet->value,
        ], true);
    }

    private function paymentTypeForAccount(ConstraintDetails $detail): int
    {
        return match ($this->accountTypeValue($detail)) {
            AccountTypesEnum::Box->value => BondPaymentTypes::Cash->value,
            AccountTypesEnum::ElectronicWallet->value => BondPaymentTypes::ElectronicWallet->value,
            default => BondPaymentTypes::BankTransfer->value,
        };
    }

    private function accountTypeValue(ConstraintDetails $detail): int
    {
        $raw = $detail->account?->getRawOriginal('type');

        return is_numeric($raw) ? (int) $raw : 0;
    }

    private function hasRequiredTables(): bool
    {
        return Schema::hasTable('fn_constraints')
            && Schema::hasTable('fn_constraint_details')
            && Schema::hasTable('fn_accounts')
            && Schema::hasTable('fn_bonds');
    }
}
