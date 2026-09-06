<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nami\Finance\Services\CashMovementReportService;

class CashMovementController extends Controller
{
    public function index(Request $request, CashMovementReportService $service)
    {
        $request->validate(['view' => ['nullable', 'in:reference,movement']]);
        if ($request->input('view', 'reference') === 'reference') {
            $input = $request->validate([
                'from_date' => ['nullable', 'date_format:Y-m-d'],
                'to_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
                'approval' => ['nullable', 'in:approved,all'],
                'export' => ['nullable', 'in:xlsx'],
            ]);
            $from = $input['from_date'] ?? null;
            $to = $input['to_date'] ?? now()->toDateString();
            if ($from && $from > $to) {
                throw \Illuminate\Validation\ValidationException::withMessages(['from_date' => 'تاريخ البداية يجب ألا يتجاوز تاريخ النهاية.']);
            }
            $report = app(\Nami\Finance\Services\DailyCashReferenceService::class)->report($from, $to, $input['approval'] ?? 'approved');
            if (($input['export'] ?? null) === 'xlsx') {
                return app(\Nami\Finance\Services\CashMovementExportService::class)->downloadReference($report);
            }
            return view('finance::finance.account_statement.cash_movements.reference', ['bladeTitle'=>'تقرير حركة صندوق اليومية', 'report'=>$report]);
        }
        $accounts = $service->accounts();
        $request->merge([
            'from_date' => $request->input('from_date', now()->startOfMonth()->toDateString()),
            'to_date' => $request->input('to_date', now()->toDateString()),
        ]);
        $input = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'account_id' => ['nullable', 'integer', Rule::in(array_keys($accounts))],
            'export' => ['nullable', 'in:xlsx'],
            'scope' => ['nullable', 'in:all,cash,bank,wallet'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $scope = $input['scope'] ?? 'all';
        $selected = array_filter($accounts, fn ($account) =>
            (!empty($input['account_id']) ? (int) $account->id === (int) $input['account_id'] : ($scope === 'all' || $account->kind === $scope)));
        if (($input['export'] ?? null) === 'xlsx') {
            return app(\Nami\Finance\Services\CashMovementExportService::class)->download(
                $service->report($selected, $input['from_date'], $input['to_date'], 1, false)
            );
        }
        return view('finance::finance.account_statement.cash_movements.index', [
            'bladeTitle' => 'حركة الصندوق والبنوك اليومية', 'accounts' => $accounts, 'scope' => $scope,
            'selectedAccount' => $input['account_id'] ?? '',
            'report' => $service->report($selected, $input['from_date'], $input['to_date'], (int) ($input['page'] ?? 1)),
        ]);
    }
}
