<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Models\FiscalYearClosure;

class FiscalYearClosingService
{
    public function __construct(private ConstraintNumberService $constraintNumberService)
    {
    }

    public function pageData(?int $yearId = null, ?string $closingDate = null): array
    {
        $years = FnYear::query()->orderByDesc('from_date')->get();
        $year = $yearId
            ? $years->firstWhere('id', $yearId)
            : ($years->firstWhere('status', 1) ?? $years->first());

        if (!$year) {
            return [
                'years' => $years,
                'year' => null,
                'closingDate' => null,
                'summary' => null,
                'distributionAccounts' => collect(),
                'closingConstraint' => null,
                'operations' => $this->operations(),
            ];
        }

        $closingDate = $this->normalizeClosingDate($year, $closingDate);
        $accountGroups = $this->accountGroups();

        $activeClosure = FiscalYearClosure::query()
            ->with('constraint')
            ->whereNull('reversed_at')
            ->whereBetween('closing_date', [$year->from_date, $year->to_date])
            ->latest('operation_number')
            ->first();

        return [
            'years' => $years,
            'year' => $year,
            'closingDate' => $closingDate,
            'summary' => $this->summary($year, $closingDate, $accountGroups),
            'distributionAccounts' => $accountGroups['balance_sheet_accounts'],
            'closingConstraint' => $activeClosure?->constraint,
            'closingOperation' => $activeClosure,
            'operations' => $this->operations(),
        ];
    }

    private function operations()
    {
        return FiscalYearClosure::query()
            ->with(['constraint', 'createdBy', 'reversedBy'])
            ->orderByDesc('operation_number')
            ->paginate(12, ['*'], 'operations_page')
            ->withPath(route('finance.fiscal-year-closing.index'))
            ->appends(request()->only(['year_id', 'closing_date']))
            ->fragment('closingOperations');
    }

    public function close(int $yearId, string $closingDate, array $allocations, ?string $description): Constraint
    {
        return DB::transaction(function () use ($yearId, $closingDate, $allocations, $description) {
            $year = FnYear::query()->lockForUpdate()->findOrFail($yearId);
            $closingDate = $this->normalizeClosingDate($year, $closingDate, true);

            $existingOperation = FiscalYearClosure::query()
                ->whereNull('reversed_at')
                ->whereBetween('closing_date', [$year->from_date, $year->to_date])
                ->lockForUpdate()
                ->first();

            if ($existingOperation) {
                throw ValidationException::withMessages([
                    'year_id' => 'تم إقفال هذه السنة المالية مسبقاً في العملية رقم '
                        . ($existingOperation->operation_number ?? $existingOperation->id) . '.',
                ]);
            }

            $accountGroups = $this->accountGroups();
            $summary = $this->summary($year, $closingDate, $accountGroups);
            $incomeNet = round((float) $summary['income_net'], 2);

            if ($summary['pending_constraints_count'] > 0) {
                throw ValidationException::withMessages([
                    'closing_date' => 'يوجد ' . $summary['pending_constraints_count']
                        . ' قيد بانتظار الاعتماد ضمن الفترة. يجب مراجعتها واعتمادها قبل الإقفال.',
                ]);
            }

            if (abs($incomeNet) < 0.01) {
                throw ValidationException::withMessages([
                    'closing_date' => 'رصيد قائمة الدخل متزن ولا توجد حركة تحتاج إلى إقفال.',
                ]);
            }

            $allowedAccounts = $accountGroups['balance_sheet_accounts']->keyBy('id');
            $allocationLines = collect($allocations)->map(function (array $allocation, int $index) use ($allowedAccounts) {
                $accountId = (int) ($allocation['account_id'] ?? 0);
                $account = $allowedAccounts->get($accountId);
                $amount = round((float) ($allocation['value'] ?? 0), 2);
                $movement = $allocation['movement'] ?? null;

                if (!$account) {
                    throw ValidationException::withMessages([
                        "allocations.$index.account_id" => 'الحساب المحدد غير صالح للتوزيع أو يتبع قائمة الدخل.',
                    ]);
                }

                if ($amount <= 0 || !in_array($movement, ['debtor', 'creditor'], true)) {
                    throw ValidationException::withMessages([
                        "allocations.$index.value" => 'أدخل قيمة موجبة وحركة رصيد صحيحة.',
                    ]);
                }

                return [
                    'account_id' => $accountId,
                    'movement' => $movement,
                    'value' => $amount,
                    'statement' => trim((string) ($allocation['statement'] ?? '')) ?: 'توزيع صافي قائمة الدخل',
                ];
            });

            $allocationNet = round($allocationLines->sum(
                fn (array $line) => $line['movement'] === 'debtor' ? $line['value'] : -$line['value']
            ), 2);

            if (abs($allocationNet - $incomeNet) >= 0.01) {
                throw ValidationException::withMessages([
                    'allocations' => 'يجب أن يساوي صافي التوزيع ' . number_format(abs($incomeNet), 2)
                        . ' ' . ($incomeNet > 0 ? 'مدين' : 'دائن') . '.',
                ]);
            }

            $statement = trim((string) $description) ?: 'قيد إقفال السنة المالية ' . $year->title;
            $constraint = Constraint::query()->create([
                'type' => ConstraintTypiesEnum::FiscalYearClosing->value,
                'value' => abs($incomeNet),
                'date' => $closingDate,
                'statement' => $statement,
                'year_id' => $year->id,
                'code' => $this->constraintNumberService->nextCode(),
                'created_by_id' => auth('admin')->id(),
                'approval_status' => Constraint::APPROVAL_APPROVED,
                'approved_at' => now(),
                'approved_by_id' => auth('admin')->id(),
            ]);

            foreach ($summary['income_accounts'] as $incomeAccount) {
                $net = round((float) $incomeAccount->net, 2);
                if (abs($net) < 0.01) {
                    continue;
                }

                $this->createDetail(
                    $constraint,
                    (int) $incomeAccount->id,
                    $net > 0 ? 'creditor' : 'debtor',
                    abs($net),
                    'إقفال رصيد ' . $incomeAccount->name
                );
            }

            foreach ($allocationLines as $line) {
                $this->createDetail(
                    $constraint,
                    $line['account_id'],
                    $line['movement'],
                    $line['value'],
                    $line['statement']
                );
            }

            FiscalYearClosure::query()->create([
                'operation_number' => ((int) FiscalYearClosure::query()->lockForUpdate()->max('operation_number')) + 1,
                'title' => $statement,
                'income_balance' => abs($incomeNet),
                'income_side' => $summary['income_side'],
                'closing_date' => $closingDate,
                'constraint_id' => $constraint->id,
                'legacy_constraint_code' => null,
                'created_by_id' => auth('admin')->id(),
                'created_by_name' => auth('admin')->user()?->name,
                'source' => 'system',
            ]);

            return $constraint->fresh('constraintDetails.account');
        });
    }

    public function reverse(int $operationId): FiscalYearClosure
    {
        return DB::transaction(function () use ($operationId) {
            $operation = FiscalYearClosure::query()->lockForUpdate()->findOrFail($operationId);

            if ($operation->reversed_at) {
                throw ValidationException::withMessages([
                    'operation' => 'تم التراجع عن عملية الإقفال مسبقاً.',
                ]);
            }

            $constraint = $operation->constraint;
            if (!$constraint || $constraint->trashed()) {
                throw ValidationException::withMessages([
                    'operation' => 'لا يمكن التراجع لأن القيد المحاسبي المرتبط غير موجود.',
                ]);
            }

            $operation->update([
                'reversed_at' => now(),
                'reversed_by_id' => auth('admin')->id(),
                'reversed_by_name' => auth('admin')->user()?->name,
            ]);

            $constraint->constraintDetails()->delete();
            $constraint->delete();

            return $operation->fresh(['constraint', 'createdBy', 'reversedBy']);
        });
    }

    private function summary(FnYear $year, string $closingDate, array $accountGroups): array
    {
        $balances = $this->balances($year, $closingDate);
        $incomeIds = $accountGroups['income_ids'];
        $balanceSheetIds = $accountGroups['balance_sheet_accounts']->pluck('id')->map(fn ($id) => (int) $id);

        $incomeAccounts = $accountGroups['accounts']
            ->whereIn('id', $incomeIds)
            ->map(function ($account) use ($balances) {
                $balance = $balances->get((int) $account->id);
                $account->debtor_total = (float) ($balance->debtor_total ?? 0);
                $account->creditor_total = (float) ($balance->creditor_total ?? 0);
                $account->net = round($account->debtor_total - $account->creditor_total, 2);
                return $account;
            })
            ->filter(fn ($account) => abs($account->net) >= 0.01)
            ->values();

        $incomeNet = round($incomeAccounts->sum('net'), 2);
        $budgetNet = round($balanceSheetIds->sum(function (int $id) use ($balances) {
            $balance = $balances->get($id);
            return (float) ($balance->debtor_total ?? 0) - (float) ($balance->creditor_total ?? 0);
        }), 2);

        return [
            'income_net' => $incomeNet,
            'income_side' => $this->side($incomeNet),
            'budget_net' => $budgetNet,
            'budget_side' => $this->side($budgetNet),
            'income_accounts' => $incomeAccounts,
            'expected_distribution_side' => $incomeNet > 0 ? 'debtor' : 'creditor',
            'pending_constraints_count' => $this->pendingConstraintsCount($year, $closingDate),
        ];
    }

    private function pendingConstraintsCount(FnYear $year, string $closingDate): int
    {
        if (!Schema::hasColumn('fn_constraints', 'approval_status')) {
            return 0;
        }

        return Constraint::query()
            ->where('year_id', $year->id)
            ->whereDate('date', '>=', $year->from_date)
            ->whereDate('date', '<=', $closingDate)
            ->where('approval_status', Constraint::APPROVAL_PENDING)
            ->count();
    }

    private function balances(FnYear $year, string $closingDate): Collection
    {
        $query = BranchReportFilter::apply(DB::table('fn_constraint_details as details'), 'details')
            ->join('fn_constraints as constraints', 'constraints.id', '=', 'details.constraint_id')
            ->whereNull('details.deleted_at')
            ->whereNull('constraints.deleted_at')
            ->where('constraints.year_id', $year->id)
            ->whereDate('details.date', '>=', $year->from_date)
            ->whereDate('details.date', '<=', $closingDate)
            ->where('constraints.type', '!=', ConstraintTypiesEnum::FiscalYearClosing->value);

        if (Schema::hasColumn('fn_constraints', 'approval_status')) {
            $query->where('constraints.approval_status', Constraint::APPROVAL_APPROVED);
        }

        return $query
            ->groupBy('details.account_id')
            ->selectRaw('details.account_id, SUM(details.debtor_value) as debtor_total, SUM(details.creditor_value) as creditor_total')
            ->get()
            ->keyBy(fn ($row) => (int) $row->account_id);
    }

    private function accountGroups(): array
    {
        $accounts = Account::query()
            ->select('id', 'parent_id', 'code', 'name', 'status', 'type')
            ->orderBy('code')
            ->get();
        $children = $accounts->groupBy(fn ($account) => $account->parent_id ? (int) $account->parent_id : 0);
        $roots = $accounts->filter(function ($account) {
            return !$account->parent_id && (
                in_array((int) $account->getRawOriginal('type'), [
                    AccountTypesEnum::GeneralIncome->value,
                    AccountTypesEnum::GeneralOutcome->value,
                ], true) || in_array((string) $account->code, ['4', '5'], true)
            );
        });

        $incomeIds = collect();
        $walk = function (int $parentId) use (&$walk, $children, $incomeIds): void {
            foreach ($children->get($parentId, collect()) as $child) {
                if (!$children->has((int) $child->id)) {
                    $incomeIds->push((int) $child->id);
                }
                $walk((int) $child->id);
            }
        };

        foreach ($roots as $root) {
            if (!$children->has((int) $root->id)) {
                $incomeIds->push((int) $root->id);
            }
            $walk((int) $root->id);
        }

        $leafIds = $accounts
            ->reject(fn ($account) => $children->has((int) $account->id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        return [
            'accounts' => $accounts,
            'income_ids' => $incomeIds->unique()->values(),
            'balance_sheet_accounts' => $accounts
                ->whereIn('id', $leafIds->diff($incomeIds))
                ->sortBy('code')
                ->values(),
        ];
    }

    private function createDetail(Constraint $constraint, int $accountId, string $movement, float $value, string $statement): void
    {
        ConstraintDetails::query()->create([
            'constraint_id' => $constraint->id,
            'date' => $constraint->date,
            'type' => $movement,
            'account_id' => $accountId,
            'cost_center_id' => null,
            'debtor_value' => $movement === 'debtor' ? $value : 0,
            'creditor_value' => $movement === 'creditor' ? $value : 0,
            'statement' => $statement,
            'year_id' => $constraint->year_id,
        ]);
    }

    private function normalizeClosingDate(FnYear $year, ?string $closingDate, bool $validate = false): string
    {
        $date = $closingDate
            ? Carbon::parse($closingDate)->startOfDay()
            : now()->startOfDay();
        $from = Carbon::parse($year->from_date)->startOfDay();
        $to = Carbon::parse($year->to_date)->startOfDay();

        if (!$closingDate) {
            $date = $date->max($from)->min($to);
        }

        if ($date->lt($from) || $date->gt($to)) {
            if ($validate) {
                throw ValidationException::withMessages([
                    'closing_date' => 'يجب أن يكون تاريخ الإقفال داخل الفترة المالية المحددة.',
                ]);
            }
            $date = $to;
        }

        return $date->toDateString();
    }

    private function side(float $net): string
    {
        if (abs($net) < 0.01) {
            return 'balanced';
        }

        return $net > 0 ? 'debtor' : 'creditor';
    }
}
