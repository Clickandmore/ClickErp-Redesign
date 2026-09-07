<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

class FinancialPositionService
{
    public const SECTIONS = [
        'current_assets' => 'الأصول المتداولة',
        'non_current_assets' => 'الأصول غير المتداولة',
        'other_assets' => 'أصول تحتاج إلى تحديد التصنيف',
        'current_liabilities' => 'الالتزامات المتداولة',
        'non_current_liabilities' => 'الالتزامات غير المتداولة',
        'other_liabilities' => 'التزامات تحتاج إلى تحديد التصنيف',
        'equity' => 'حقوق الملكية',
        'unclassified' => 'حسابات خارج تصنيف القائمة',
    ];

    public function report(string $date): array
    {
        // Include opening and closing entries: the remaining P&L balance is the
        // undistributed result, so a posted closing entry cannot count twice.
        $query = DB::table('fn_constraint_details as d')
            ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->whereNull('d.deleted_at')->whereNull('c.deleted_at')
            ->where('d.date', '<', \Carbon\Carbon::parse($date)->addDay()->toDateString());
        if (Constraint::hasApprovalColumns()) {
            $query->where('c.approval_status', Constraint::APPROVAL_APPROVED);
        }
        $balances = $query->groupBy('d.account_id')
            ->selectRaw('d.account_id, SUM(CAST(d.debtor_value AS DECIMAL(24, 2))) as debit, SUM(CAST(d.creditor_value AS DECIMAL(24, 2))) as credit')->get();
        $accounts = DB::table('fn_accounts')->select('id', 'parent_id', 'code', 'name', 'type')->orderBy('code')->get();
        $report = $this->build($accounts->all(), $balances->all());
        $report['date'] = $date;
        $report['pending_count'] = Constraint::query()->pendingApproval()->whereDate('date', '<=', $date)->count();
        return $report;
    }

    /** Pure aggregation in integer halalas; each posting contributes exactly once. */
    public function build(array $accounts, array $balances): array
    {
        $indexed = [];
        foreach ($accounts as $account) {
            $indexed[(int) $account->id] = $account;
        }
        $groups = $paths = $issues = [];
        foreach ($indexed as $id => $account) {
            $path = [];
            $cursor = $id;
            while (isset($indexed[$cursor])) {
                if (isset($path[$cursor])) {
                    $issues[] = 'توجد حلقة في شجرة الحسابات عند الحساب ' . $account->code;
                    $path = [];
                    break;
                }
                $path[$cursor] = $indexed[$cursor];
                $cursor = (int) $indexed[$cursor]->parent_id;
                if (!$cursor) {
                    break;
                }
            }
            if ($cursor && !isset($indexed[$cursor])) {
                $issues[] = 'الحساب ' . $account->code . ' مرتبط بحساب أب غير موجود';
                $path = [];
            }
            $path = array_reverse($path, true);
            $group = 'unclassified';
            foreach ($path as $node) {
                $group = $this->classify($node, $group);
            }
            $groups[$id] = $group;
            $paths[$id] = $path;
        }
        $own = [];
        $ledger = 0;
        $postingCount = 0;
        foreach ($balances as $balance) {
            $id = (int) $balance->account_id;
            $net = $this->minor($balance->debit) - $this->minor($balance->credit);
            $ledger += $net;
            $postingCount++;
            $own[$id] = ($own[$id] ?? 0) + $net;
            if (!isset($indexed[$id])) {
                $indexed[$id] = (object) ['id' => $id, 'parent_id' => null, 'code' => $id, 'name' => 'حساب غير موجود في الدليل'];
                $groups[$id] = 'unclassified';
                $paths[$id] = [$id => $indexed[$id]];
                $issues[] = 'توجد حركة على حساب غير موجود في الدليل: ' . $id;
            }
        }
        $totals = array_fill_keys(array_merge(array_keys(self::SECTIONS), ['income', 'expense']), 0);
        $rollups = [];
        foreach ($own as $id => $net) {
            $group = $groups[$id];
            $signed = in_array($group, ['current_assets', 'non_current_assets', 'other_assets', 'expense', 'unclassified'], true) ? $net : -$net;
            $totals[$group] += $signed;
            foreach ($paths[$id] as $ancestorId => $ancestor) {
                if ($groups[$ancestorId] === $group) {
                    $rollups[$ancestorId] = ($rollups[$ancestorId] ?? 0) + $signed;
                }
            }
            // Invalid trees still appear individually in the unclassified section.
            if (!$paths[$id]) {
                $rollups[$id] = $signed;
            }
        }
        $sections = [];
        foreach (self::SECTIONS as $group => $label) {
            $rows = [];
            foreach ($indexed as $id => $account) {
                if ($groups[$id] !== $group) {
                    continue;
                }
                $ancestors = array_filter($paths[$id], fn ($node) => $groups[(int) $node->id] === $group && (int) $node->id !== $id);
                $rows[] = ['id' => $id, 'code' => $account->code, 'name' => $account->name,
                    'depth' => count($ancestors), 'path' => array_keys($paths[$id]),
                    'amount' => $rollups[$id] ?? 0, 'own' => $own[$id] ?? 0];
            }
            // Tree order rather than numeric code order (legacy codes are not prefixes).
            usort($rows, function ($a, $b) use ($indexed) {
                $left = $a['path'] ?: [$a['id']];
                $right = $b['path'] ?: [$b['id']];
                foreach ($left as $i => $id) {
                    if (!isset($right[$i])) return 1;
                    if ($id !== $right[$i]) return strnatcmp((string) $indexed[$id]->code, (string) $indexed[$right[$i]]->code) ?: ($id <=> $right[$i]);
                }
                return count($left) <=> count($right);
            });
            $sections[$group] = compact('label', 'rows') + ['total' => $totals[$group]];
        }
        $assets = $totals['current_assets'] + $totals['non_current_assets'] + $totals['other_assets'];
        $liabilities = $totals['current_liabilities'] + $totals['non_current_liabilities'] + $totals['other_liabilities'];
        $result = $totals['income'] - $totals['expense'];
        $equity = $totals['equity'] + $result;
        $incomplete = false;
        foreach (['other_assets', 'other_liabilities', 'unclassified'] as $key) {
            foreach ($sections[$key]['rows'] as $row) {
                if ($row['own'] !== 0) $incomplete = true;
            }
        }
        $difference = $assets - $liabilities - $equity;
        return compact('sections', 'assets', 'liabilities', 'equity', 'result', 'difference', 'ledger', 'incomplete', 'postingCount') + [
            'issues' => array_values(array_unique($issues)),
            'balanced' => $difference === 0 && $ledger === 0,
            'working_capital' => $totals['current_assets'] - $totals['current_liabilities'],
            'current_ratio' => !$incomplete && $totals['current_liabilities'] > 0 ? $totals['current_assets'] / $totals['current_liabilities'] : null,
        ];
    }

    private function classify(object $account, string $inherited): string
    {
        $code = (string) $account->code;
        $type = (int) ($account->type ?? 1);
        if (!$account->parent_id) {
            return match ($code) {
                '1' => 'other_assets', '2' => 'other_liabilities', '3' => 'equity',
                '4' => 'income', '5' => 'expense',
                default => match ($type) {
                    43 => 'income', 44 => 'expense', 106 => 'non_current_assets',
                    107 => 'current_assets', 108 => 'current_liabilities',
                    109 => 'non_current_liabilities', 110 => 'equity', default => 'unclassified',
                },
            };
        }
        // Explicit legacy group codes from the supplied chart; never infer from a prefix or a name.
        if ($inherited === 'other_assets') {
            if ($type === 107 || in_array($code, ['12', '102'], true)) return 'current_assets';
            if ($type === 106 || in_array($code, ['11', '13'], true)) return 'non_current_assets';
        }
        if ($inherited === 'other_liabilities') {
            if ($type === 108 || in_array($code, ['14', '22'], true)) return 'current_liabilities';
            if ($type === 109 || $code === '21') return 'non_current_liabilities';
        }
        return $inherited;
    }

    private function minor($value): int
    {
        $value = (string) ($value ?? '0');
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '+-'), 2), 2, '');
        $fraction = str_pad($fraction, 3, '0');
        $minor = (int) $whole * 100 + (int) substr($fraction, 0, 2) + ((int) $fraction[2] >= 5 ? 1 : 0);
        return $negative ? -$minor : $minor;
    }
}
