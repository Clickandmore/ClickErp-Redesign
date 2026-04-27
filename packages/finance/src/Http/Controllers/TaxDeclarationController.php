<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Services\BillSalesService;
use Nami\Finance\Services\ConstraintDetailsService as ObService;
use Nami\Finance\Services\PurchaseService;


class TaxDeclarationController extends Controller
{
    public $folderPath = "finance::finance.tax_declaration";
    public $getData = ["from_date", "to_date"];

    public $mainRoute = "finance.tax-declaration";

    public function index(Request $request, ObService $service, PurchaseService $purchaseService, BillSalesService $saleService)
    {
        if ($request->ajax()) {
            $dataSearch = $request->only($this->getData);
            $data['sales'] = $saleService->getByTypeGroupBy(SalesTypeEnum::Sales->value, $dataSearch);
            $data['purchases'] = $purchaseService->getByTypeGroupBy(PurchaseTypeEnum::Purchase->value, $dataSearch);

            $purchaseDates = $purchaseService->getMinMaxDate($dataSearch);
            $saleDates = $saleService->getMinMaxDate($dataSearch);
            $minDates = array_filter([
                $purchaseDates->min_date ?? null,
                $saleDates->min_date ?? null
            ]);

            $maxDates = array_filter([
                $purchaseDates->max_date ?? null,
                $saleDates->max_date ?? null
            ]);

            $data['min_date'] = !empty($minDates) ? min($minDates) : null;
            $data['max_date'] = !empty($maxDates) ? max($maxDates) : null;
            $returnHTML = view($this->folderPath . ".result", $data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["bladeTitle"] = __("finance::finance.tax_declaration");
        $data["addButtonText"] = __("finance::finance.tax_declaration");
        return view($this->folderPath . '.index', $data);
    }
}
