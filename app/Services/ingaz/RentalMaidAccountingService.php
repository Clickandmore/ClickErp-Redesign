<?php

namespace App\Services\ingaz;

use App\Models\RentalFinancialAdjustment;
use App\Models\ingaz\Maid;
use App\Models\office\Office;
use App\Services\office\OfficeFinanceAccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RentalMaidAccountingService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected OfficeFinanceAccountService $officeFinanceAccountService
    ) {
    }

    public function recordCreationEntries(Maid $maid): ?Constraint
    {
        return DB::transaction(function () use ($maid) {
            $maid = $maid->fresh(['rentalFinancialAdjustments', 'office']);

            if (!$maid) {
                return null;
            }

            $allItems = $maid->rentalFinancialAdjustments()
                ->where('context', 'maid')
                ->orderBy('id')
                ->get();

            if ($allItems->isEmpty()) {
                return null;
            }

            $costCenter = $this->ensureMaidCostCenter($maid);

            RentalFinancialAdjustment::query()
                ->whereIn('id', $allItems->pluck('id'))
                ->update(['cost_center_id' => $costCenter->id]);

            $existingConstraintId = (int) $allItems->pluck('constraint_id')->filter()->first();
            if ($existingConstraintId > 0) {
                return Constraint::query()->find($existingConstraintId);
            }

            $positiveItems = $allItems->filter(function (RentalFinancialAdjustment $item) {
                return $this->roundMoney((float) $item->amount) > 0;
            })->values();

            if ($positiveItems->isEmpty()) {
                return null;
            }

            $itemsMissingAccounts = $positiveItems->filter(fn (RentalFinancialAdjustment $item) => empty($item->account_id));
            if ($itemsMissingAccounts->isNotEmpty()) {
                throw new \RuntimeException('لا يمكن إنشاء قيد عاملة التأجير لوجود بنود غير مرتبطة بحسابات محاسبية.');
            }

            if (!$maid->office_id) {
                throw new \RuntimeException('لا يمكن إنشاء قيد عاملة التأجير بدون تحديد المكتب الخارجي.');
            }

            $office = Office::query()
                ->with(['translations', 'account'])
                ->find($maid->office_id);

            if (!$office) {
                throw new \RuntimeException('المكتب الخارجي المرتبط بالعاملة غير موجود.');
            }

            $officeAccount = $this->officeFinanceAccountService->ensureForOffice($office);
            [$entryDate, $activeYear] = $this->resolveEntryDateAndYear($maid);

            $constraintStatement = $this->buildConstraintStatement($maid, trim((string) $office->title));
            $totalValue = $this->roundMoney((float) $positiveItems->sum('amount'));

            $existingConstraint = $this->findExistingConstraint($constraintStatement, $entryDate, $totalValue);
            if ($existingConstraint) {
                RentalFinancialAdjustment::query()
                    ->whereIn('id', $allItems->pluck('id'))
                    ->update(['constraint_id' => $existingConstraint->id]);

                return $existingConstraint;
            }

            $lines = [];

            foreach ($positiveItems as $item) {
                $this->appendTransferPair(
                    $lines,
                    (int) $item->account_id,
                    (int) $officeAccount->id,
                    (float) $item->amount,
                    $this->buildItemStatement($maid, $item),
                    $costCenter->id,
                    $item->type
                );
            }

            if (empty($lines) || $totalValue <= 0) {
                return null;
            }

            $constraint = $this->storeConstraint($lines, $constraintStatement, $entryDate, $totalValue, $activeYear);

            if ($constraint) {
                RentalFinancialAdjustment::query()
                    ->whereIn('id', $allItems->pluck('id'))
                    ->update(['constraint_id' => $constraint->id]);
            }

            return $constraint;
        });
    }

    private function ensureMaidCostCenter(Maid $maid): CostCenter
    {
        $rootCostCenter = $this->ensureMaidRootCostCenter();

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $rootCostCenter->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $maid->id)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $title = 'عاملة تأجير #' . $maid->id;
        $maidName = trim((string) ($maid->name ?? ''));

        if ($maidName !== '') {
            $title .= ' - ' . $maidName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $rootCostCenter->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $maid->id,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureMaidRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'العاملات')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'العاملات',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    private function resolveEntryDateAndYear(Maid $maid): array
    {
        $candidateDate = $maid->arrival_date?->format('Y-m-d') ?: now()->toDateString();
        $activeYear = $this->yearService->checkDateRange($candidateDate);

        if ($activeYear) {
            return [$candidateDate, $activeYear];
        }

        $activeYear = $this->yearService->getActiveYear();

        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لإنشاء قيد عاملة التأجير.');
        }

        return [now()->toDateString(), $activeYear];
    }

    private function appendTransferPair(
        array &$lines,
        int $itemAccountId,
        int $officeAccountId,
        float $amount,
        string $statement,
        int $costCenterId,
        string $type = 'expense'
    ): void {
        $amount = $this->roundMoney($amount);

        if ($amount <= 0) {
            return;
        }

        if ($type === 'income') {
            $lines[] = [
                'account_id' => $officeAccountId,
                'account_type' => 'debtor',
                'debtor_value' => $amount,
                'creditor_value' => 0,
                'statement' => $statement,
                'cost_center_id' => $costCenterId,
            ];

            $lines[] = [
                'account_id' => $itemAccountId,
                'account_type' => 'creditor',
                'debtor_value' => 0,
                'creditor_value' => $amount,
                'statement' => $statement,
                'cost_center_id' => $costCenterId,
            ];

            return;
        }

        $lines[] = [
            'account_id' => $itemAccountId,
            'account_type' => 'debtor',
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];

        $lines[] = [
            'account_id' => $officeAccountId,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => $amount,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function storeConstraint(array $lines, string $statement, string $date, float $value, $activeYear): ?Constraint
    {
        if (empty($lines) || $this->roundMoney($value) <= 0) {
            return null;
        }

        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $this->roundMoney($value),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function buildConstraintStatement(Maid $maid, string $officeTitle): string
    {
        $statement = 'إثبات مصاريف عاملة تأجير #' . $maid->id;
        $maidName = trim((string) ($maid->name ?? ''));

        if ($maidName !== '') {
            $statement .= ' - ' . $maidName;
        }

        if ($officeTitle !== '') {
            $statement .= ' / المكتب: ' . $officeTitle;
        }

        return $statement;
    }

    private function buildItemStatement(Maid $maid, RentalFinancialAdjustment $item): string
    {
        return trim($item->label) . ' / عاملة تأجير #' . $maid->id;
    }

    private function findExistingConstraint(string $statement, string $date, float $value): ?Constraint
    {
        if ($this->roundMoney($value) <= 0) {
            return null;
        }

        return Constraint::query()
            ->where('statement', $statement)
            ->whereDate('date', $date)
            ->where('value', $this->roundMoney($value))
            ->latest('id')
            ->first();
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
