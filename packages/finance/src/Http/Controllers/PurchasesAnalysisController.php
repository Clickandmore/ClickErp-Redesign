<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\ConfirmSettingService;
use Nami\Finance\Services\ConstraintService;


class PurchasesAnalysisController extends Controller
{
    public $folderPath = "finance::finance.purchases_analysis";
    public $mainRoute = "finance.purchases-analysis";
    public $getData = ["from_date", "to_date"];


    public function index(
        Request $request,
        ConstraintService $constraintService,
        AccountService $accountService,
        ConfirmSettingService $confirmSettingService
    )
    {
        $accounts = $this->taxAccounts($confirmSettingService, ConfirmSettingTypes::ExpenseTaxAccount->value);
        $defaultAccountId = $accounts->first()?->id;
        $dataSearch = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
            'type' => 'both',
            'account_id' => $defaultAccountId,
            'other_account' => 'yes',
        ], array_filter($request->only(['from_date', 'to_date', 'type', 'account_id', 'other_account']), fn ($value) => $value !== null && $value !== ''));

        if (!$accounts->contains('id', (int) ($dataSearch['account_id'] ?? 0))) {
            $dataSearch['account_id'] = $defaultAccountId;
        }

        if ($request->ajax()) {
            $data = collect();
            $firstBalance = null;
            $otherAccount = [];
            $selectedAccount = null;

            if (!empty($dataSearch['account_id'])) {
                $data = $constraintService->filterAccount($dataSearch);
                $firstBalance = $constraintService->getFirstBalance($dataSearch);
                $otherAccount = $dataSearch['other_account'] === 'yes' ? $constraintService->filterConstraints($dataSearch) : [];
                $selectedAccount = $accountService->find($dataSearch['account_id']);
            }

            $hasOpeningBalance = abs((float) ($firstBalance->sum_debtor_value ?? 0)) > 0.01
                || abs((float) ($firstBalance->sum_creditor_value ?? 0)) > 0.01;

            $returnHTML = view('finance::finance.account_statement.result')->with([
                'data' => $data,
                'firstBalance' => $firstBalance,
                'otherAccount' => $otherAccount,
                'show_other_account' => $dataSearch['other_account'],
                'accountName' => $selectedAccount->name ?? null,
                'filters' => $dataSearch,
                'reportTitle' => __('finance::finance.purchases-analysis'),
            ])->render();

            return jsonSuccess([
                'html' => $returnHTML,
                'count' => ($data->count() > 0 || $hasOpeningBalance) ? 1 : 0,
            ]);
        }

        return view('finance::finance.account_statement.index', [
            'accounts' => $accounts,
            'bladeTitle' => __('finance::finance.purchases-analysis'),
            'filters' => $dataSearch,
            'statementRoute' => route($this->mainRoute . '.index'),
            'printRoute' => route('finance.account-statement-print.index'),
            'printPermission' => 'finance::finance.account-statement-print-read',
        ]);
    }

    private function taxAccounts(ConfirmSettingService $confirmSettingService, string $settingType)
    {
        $accountId = $confirmSettingService->getConfirmSetting($settingType)?->value;

        if ($accountId) {
            $configuredAccount = Account::query()
                ->whereKey((int) $accountId)
                ->whereDoesntHave('children')
                ->where('status', 'debtor')
                ->first();

            if ($configuredAccount) {
                return collect([$configuredAccount]);
            }
        }

        $vatAccount = Account::query()
            ->whereDoesntHave('children')
            ->where('status', 'debtor')
            ->where('code', 1282)
            ->first();

        if ($vatAccount) {
            return collect([$vatAccount]);
        }

        return Account::query()
            ->whereDoesntHave('children')
            ->where('status', 'debtor')
            ->where(function ($query) {
                $query->where('name', 'like', '%ضريبة%مصروف%')
                    ->orWhere('name', 'like', '%ضريبة%مشتريات%')
                    ->orWhere('name', 'like', '%ضريبة%قيمة%مضافة%');
            })
            ->orderBy('code')
            ->get();
    }
}
