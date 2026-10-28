<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails;

/**
 * رصيد حساب من شجرة الحسابات (يشمل حساباته الفرعية) في فترة.
 *
 * نفس قاعدة AccountService: الحساب المدين = مدين − دائن، والدائن = دائن − مدين،
 * من بنود القيود غير المحذوفة لقيود غير محذوفة. ConstraintDetails عليه BranchScoped.
 */
class FinanceAccountBalanceTool extends McpTool
{
    public function name(): string
    {
        return 'finance_account_balance';
    }

    public function description(): string
    {
        return 'رصيد حساب أو أكثر من شجرة الحسابات (بالاسم أو الرقم) مع حساباته الفرعية، في فترة: مجموع المدين والدائن والرصيد. '
            . 'Chart-of-accounts balance by name or code, including sub-accounts, for a period.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'account' => ['type' => 'string', 'description' => 'اسم الحساب أو رقمه، مثل: الصندوق، البنك، 1101'],
                'from' => ['type' => 'string', 'format' => 'date'],
                'to' => ['type' => 'string', 'format' => 'date'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 5],
            ],
            'required' => ['account'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'finance.accounts.index';
    }

    protected function gatePackage(): ?string
    {
        return 'finance';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $term = trim((string) ($arguments['account'] ?? ''));
        if ($term === '') {
            throw new McpToolException('أرسل اسم الحساب أو رقمه في account.');
        }

        $accounts = Account::query()
            ->where(fn ($q) => $q->where('code', $term)->orWhere('name', 'like', "%{$term}%")->orWhere('code', 'like', "{$term}%"))
            ->orderByRaw('code = ? desc', [$term])
            ->orderBy('level')
            ->orderBy('code')
            ->limit(min(20, $this->limit($arguments, 5)))
            ->get();

        if ($accounts->isEmpty()) {
            throw new McpToolException("لا يوجد حساب يطابق «{$term}».");
        }

        $tree = Account::query()->get(['id', 'parent_id'])->groupBy('parent_id');

        return [
            'currency' => 'SAR',
            'period' => ['from' => $arguments['from'] ?? null, 'to' => $arguments['to'] ?? null],
            'accounts' => $accounts->map(function (Account $account) use ($arguments, $tree) {
                $ids = $this->descendantIds($account->id, $tree);
                $movement = ConstraintDetails::query()
                    ->whereIn('fn_constraint_details.account_id', $ids)
                    ->whereHas('constraint')
                    ->when(!empty($arguments['from']), fn ($q) => $q->whereDate('fn_constraint_details.date', '>=', $arguments['from']))
                    ->when(!empty($arguments['to']), fn ($q) => $q->whereDate('fn_constraint_details.date', '<=', $arguments['to']))
                    ->selectRaw('coalesce(sum(debtor_value), 0) as debit, coalesce(sum(creditor_value), 0) as credit, count(*) as line_count')
                    ->first();

                $debit = round((float) $movement->debit, 2);
                $credit = round((float) $movement->credit, 2);
                // status مُحوَّل إلى AccountStatusEnum في الموديل.
                $status = $account->status instanceof \BackedEnum ? $account->status->value : $account->status;
                $isDebtor = $status === 'debtor';

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'nature' => $isDebtor ? 'مدين' : 'دائن',
                    'sub_accounts' => count($ids) - 1,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => round($isDebtor ? $debit - $credit : $credit - $debit, 2),
                    'entry_lines' => (int) $movement->line_count,
                ];
            })->all(),
        ];
    }

    private function descendantIds(int $id, $tree): array
    {
        $ids = [$id];
        foreach ($tree->get($id, collect()) as $child) {
            $ids = array_merge($ids, $this->descendantIds($child->id, $tree));
        }

        return $ids;
    }
}
