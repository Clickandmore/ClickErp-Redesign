<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

class CashMovementReportService
{
    public function accounts(): array
    {
        $all = DB::table('fn_accounts')->select('id', 'parent_id', 'code', 'name', 'type')->orderBy('code')->get()->keyBy('id');
        $result = [];
        foreach ($all as $account) {
            $cursor = $account;
            $visited = [];
            while ($cursor && !isset($visited[$cursor->id])) {
                $visited[$cursor->id] = true;
                $kind = match ((int) $cursor->type) {
                    16, 17 => 'cash', 14, 15, 111 => 'bank', 32, 33 => 'wallet', default => null,
                };
                if (!$kind && in_array((string) $cursor->code, ['121', '122'], true)) {
                    $kind = (string) $cursor->code === \Nami\Finance\Support\AccountCodeAliases::current('121') ? 'cash' : 'bank';
                }
                if ($kind) {
                    $account->kind = $kind;
                    $result[$account->id] = $account;
                    break;
                }
                $cursor = $all->get($cursor->parent_id);
            }
        }
        return $result;
    }

    public function query()
    {
        $query = DB::table('fn_constraint_details as d')->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->whereNull('d.deleted_at')->whereNull('c.deleted_at');
        if (Constraint::hasApprovalColumns()) $query->where('c.approval_status', Constraint::APPROVAL_APPROVED);
        return $query;
    }

    public function report(array $accounts, string $from, string $to, int $page = 1, bool $paginate = true): array
    {
        $end = Carbon::parse($to)->addDay()->toDateString();
        $ids = array_keys($accounts);
        $opening = $this->query()->whereIn('d.account_id', $ids)->where('d.date', '<', $from)
            ->groupBy('d.account_id')->selectRaw('d.account_id, SUM(CAST(d.debtor_value AS DECIMAL(24,2))) as debit, SUM(CAST(d.creditor_value AS DECIMAL(24,2))) as credit')->get();
        $movements = $this->query()->whereIn('d.account_id', $ids)->where('d.date', '>=', $from)->where('d.date', '<', $end)
            ->orderBy('d.date')->orderBy('d.constraint_id')->orderBy('d.id')
            ->select('d.id', 'd.account_id', 'd.constraint_id', 'd.date', 'd.statement', 'c.code', 'c.type', 'c.statement as entry_statement')
            ->selectRaw('CAST(d.debtor_value AS DECIMAL(24,2)) as debit, CAST(d.creditor_value AS DECIMAL(24,2)) as credit')->get();
        $report = $this->aggregate($accounts, $opening->all(), $movements->all());
        $report['pending'] = Constraint::query()->pendingApproval()->whereDate('date', '<=', $to)
            ->whereExists(function ($q) use ($ids, $end) {
                $q->selectRaw('1')->from('fn_constraint_details as pending_details')
                    ->whereColumn('pending_details.constraint_id', 'fn_constraints.id')
                    ->whereNull('pending_details.deleted_at')->whereIn('pending_details.account_id', $ids)->where('pending_details.date', '<', $end);
            })->count();
        $report['pages'] = max(1, (int) ceil(count($report['movements']) / 100));
        $report['page'] = min($page, $report['pages']);
        $report['count'] = count($report['movements']);
        if (!$paginate) return $report + compact('from', 'to');
        $report['movements'] = array_slice($report['movements'], ($report['page'] - 1) * 100, 100);
        $constraintIds = array_unique(array_column($report['movements'], 'constraint_id'));
        $counterparts = DB::table('fn_constraint_details as d')->leftJoin('fn_accounts as a', 'a.id', '=', 'd.account_id')
            ->whereIn('d.constraint_id', $constraintIds)->whereNull('d.deleted_at')
            ->select('d.constraint_id', 'd.account_id', 'a.code', 'a.name')->distinct()->get()->groupBy('constraint_id');
        foreach ($report['movements'] as &$row) {
            $row['counterparts'] = $counterparts->get($row['constraint_id'], collect())
                ->where('account_id', '!=', $row['account_id'])->map(fn ($a) => ($a->code ?? $a->account_id) . ' — ' . ($a->name ?? 'حساب غير موجود'))->values()->all();
        }
        unset($row);
        return $report + compact('from', 'to');
    }

    public function aggregate(array $accounts, array $opening, array $movements): array
    {
        $summaries = [];
        foreach ($accounts as $id => $account) {
            $summaries[$id] = ['account' => $account, 'opening' => 0, 'receipts' => 0, 'payments' => 0, 'closing' => 0];
        }
        foreach ($opening as $row) {
            $summaries[$row->account_id]['opening'] += $this->minor($row->debit) - $this->minor($row->credit);
        }
        $totalOpening = array_sum(array_column($summaries, 'opening'));
        foreach ($summaries as &$row) $row['closing'] = $row['opening'];
        unset($row);
        $running = $totalOpening;
        $days = $rows = [];
        foreach ($movements as $movement) {
            $id = $movement->account_id;
            $debit = $this->minor($movement->debit);
            $credit = $this->minor($movement->credit);
            $day = substr($movement->date, 0, 10);
            if (!isset($days[$day])) $days[$day] = ['date' => $day, 'opening' => $running, 'receipts' => 0, 'payments' => 0, 'closing' => $running];
            $summaries[$id]['receipts'] += $debit;
            $summaries[$id]['payments'] += $credit;
            $summaries[$id]['closing'] += $debit - $credit;
            $running += $debit - $credit;
            $days[$day]['receipts'] += $debit;
            $days[$day]['payments'] += $credit;
            $days[$day]['closing'] = $running;
            $rows[] = (array) $movement + ['receipts' => $debit, 'payments' => $credit, 'balance' => $running, 'account_balance' => $summaries[$id]['closing'], 'account' => $accounts[$id]];
        }
        $receipts = array_sum(array_column($summaries, 'receipts'));
        $payments = array_sum(array_column($summaries, 'payments'));
        return ['accounts' => $summaries, 'opening' => $totalOpening, 'receipts' => $receipts, 'payments' => $payments,
            'net' => $receipts - $payments, 'closing' => $running, 'days' => array_values($days), 'movements' => $rows,
            'negative_accounts' => count(array_filter($summaries, fn ($row) => $row['closing'] < 0))];
    }

    private function minor($value): int
    {
        $value = (string) $value;
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-+'), 2), 2, '');
        $amount = (int) $whole * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
        return str_starts_with($value, '-') ? -$amount : $amount;
    }
}
