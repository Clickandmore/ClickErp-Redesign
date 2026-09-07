<?php

namespace Nami\Finance\Http\Controllers;

use App\Support\BranchReportFilter;
use App\Http\Controllers\admin\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Services\ConfirmSettingService;


class TaxDeclarationController extends Controller
{
    public $folderPath = "finance::finance.tax_declaration";
    public $getData = ["from_date", "to_date"];

    public $mainRoute = "finance.tax-declaration";

    public function index(Request $request, ConfirmSettingService $confirmSettingService)
    {
        $dataSearch = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
        ], array_filter($request->only($this->getData), fn ($value) => $value !== null && $value !== ''));

        if ($request->ajax()) {
            $revenueTaxAccountId = $this->settingAccountId($confirmSettingService, ConfirmSettingTypes::RevenueTaxAccount->value);
            $expenseTaxAccountId = $this->settingAccountId($confirmSettingService, ConfirmSettingTypes::ExpenseTaxAccount->value);
            $revenueTaxRate = $this->settingRate($confirmSettingService, ConfirmSettingTypes::TaxRateRevenue->value);
            $expenseTaxRate = $this->settingRate($confirmSettingService, ConfirmSettingTypes::TaxRateExpenditure->value);

            $data['sales'] = $this->taxSummaryRows($revenueTaxAccountId, $dataSearch, 'creditor', $revenueTaxRate);
            $data['purchases'] = $this->taxSummaryRows($expenseTaxAccountId, $dataSearch, 'debtor', $expenseTaxRate);

            $dateRange = $this->taxDateRange(array_filter([$revenueTaxAccountId, $expenseTaxAccountId]), $dataSearch);

            $data['min_date'] = $dateRange->min_date ?? null;
            $data['max_date'] = $dateRange->max_date ?? null;
            $returnHTML = view($this->folderPath . ".result", $data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["bladeTitle"] = __("finance::finance.tax-declaration");
        $data["addButtonText"] = __("finance::finance.tax-declaration");
        $data["filters"] = $dataSearch;
        return view($this->folderPath . '.index', $data);
    }

    private function settingAccountId(ConfirmSettingService $confirmSettingService, string $settingType): ?int
    {
        $value = $confirmSettingService->getConfirmSetting($settingType)?->value;

        return filled($value) ? (int) $value : null;
    }

    private function settingRate(ConfirmSettingService $confirmSettingService, string $settingType): float
    {
        $value = $confirmSettingService->getConfirmSetting($settingType)?->value;
        $rate = $value ? \Nami\Finance\Models\TaxSetting::whereKey($value)->value('value') : null;

        return $rate !== null ? (float) $rate : 15.0;
    }

    private function taxSummaryRows(?int $accountId, array $filters, string $normalSide, float $taxRate)
    {
        if (!$accountId || $taxRate <= 0) {
            return collect();
        }

        $summary = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->where('fn_constraint_details.account_id', $accountId)
            ->when($filters['from_date'] !== '', fn ($query) => $query->whereDate('fn_constraint_details.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] !== '', fn ($query) => $query->whereDate('fn_constraint_details.date', '<=', $filters['to_date']))
            ->selectRaw('SUM(fn_constraint_details.debtor_value) as debtor, SUM(fn_constraint_details.creditor_value) as creditor')
            ->first();

        $taxValue = $normalSide === 'creditor'
            ? (float) ($summary->creditor ?? 0) - (float) ($summary->debtor ?? 0)
            : (float) ($summary->debtor ?? 0) - (float) ($summary->creditor ?? 0);

        $taxValue = round($taxValue, 2);

        if (abs($taxValue) < 0.01) {
            return collect();
        }

        return collect([(object) [
            'tax_per' => $taxRate,
            'sum_value' => round($taxValue / ($taxRate / 100), 2),
            'sum_total_tax_value' => $taxValue,
        ]]);
    }

    private function taxDateRange(array $accountIds, array $filters)
    {
        if (empty($accountIds)) {
            return null;
        }

        return BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.account_id', $accountIds)
            ->when($filters['from_date'] !== '', fn ($query) => $query->whereDate('fn_constraint_details.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] !== '', fn ($query) => $query->whereDate('fn_constraint_details.date', '<=', $filters['to_date']))
            ->selectRaw('MIN(fn_constraint_details.date) as min_date, MAX(fn_constraint_details.date) as max_date')
            ->first();
    }
}
