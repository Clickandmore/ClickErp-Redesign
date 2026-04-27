<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Services\BillSalesService as ObjService;


class SalesAnalysisController extends Controller
{
    public $folderPath = "finance::finance.sales_analysis";
    public $mainRoute = "finance.sales-analysis";
    public $getData = ["from_date", "to_date"];

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataSearch = $request->only($this->getData);
            $data['result'] = $service->getByType(SalesTypeEnum::Sales->value, $dataSearch);
            $returnHTML = view($this->folderPath . ".result",$data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data['dataTableRoute'] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.sales-analysis");
        $data["addButtonText"] = __("finance::finance.sales-analysis");
        return view($this->folderPath . '.index', $data);
    }

}
