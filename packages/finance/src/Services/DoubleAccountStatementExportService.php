<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoubleAccountStatementExportService
{
    public function download(Request $request, string $format, ComprehensiveTableExportService $exporter)
    {
        $type = in_array($request->query('type'), ['debtor', 'creditor'], true)
            ? $request->query('type')
            : 'both';
        $balances = [];

        $movements = DB::table('fn_double_account_details')
            ->select([
                'fn_double_accounts.id as group_id',
                'fn_double_accounts.name as group_name',
                'fn_accounts.id as account_id',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_constraints.code as constraint_code',
                'admins.name as created_by_name',
            ])
            ->join('fn_double_accounts', 'fn_double_accounts.id', '=', 'fn_double_account_details.double_account_id')
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_double_account_details.account_id')
            ->join('fn_constraint_details', 'fn_constraint_details.account_id', '=', 'fn_accounts.id')
            ->tap(fn ($q) => BranchReportFilter::apply($q, 'fn_constraint_details'))
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->leftJoin('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->when($request->filled('double_account_id'), fn ($query) =>
                $query->where('fn_double_accounts.id', (int) $request->query('double_account_id')))
            ->when($request->filled('from_date'), fn ($query) =>
                $query->whereDate('fn_constraint_details.date', '>=', $request->query('from_date')))
            ->when($request->filled('to_date'), fn ($query) =>
                $query->whereDate('fn_constraint_details.date', '<=', $request->query('to_date')))
            ->when($type !== 'both', fn ($query) => $query->where('fn_constraint_details.type', $type))
            ->orderBy('fn_double_accounts.name')
            ->orderBy('fn_accounts.code')
            ->orderBy('fn_constraint_details.date')
            ->orderBy('fn_constraint_details.id')
            ->get();

        $rows = $movements->map(function ($movement) use (&$balances) {
            $balanceKey = $movement->group_id . ':' . $movement->account_id;
            $balances[$balanceKey] = ($balances[$balanceKey] ?? 0)
                + (float) $movement->debtor_value
                - (float) $movement->creditor_value;

            return [
                (string) $movement->group_name,
                (string) $movement->account_code,
                (string) $movement->account_name,
                (string) $movement->date,
                (string) ($movement->constraint_code ?? ''),
                (string) $movement->statement,
                (float) $movement->debtor_value,
                (float) $movement->creditor_value,
                (float) $balances[$balanceKey],
                (string) ($movement->created_by_name ?? ''),
            ];
        })->all();

        return $exporter->download(
            $format,
            'التصدير الشامل لكشوف الحسابات المزدوجة',
            'double-account-statements',
            ['المجموعة', 'رمز الحساب', 'اسم الحساب', 'التاريخ', 'رقم القيد', 'البيان', 'مدين', 'دائن', 'الرصيد', 'أنشأ بواسطة'],
            $rows,
            [6, 7, 8]
        );
    }
}
