<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Services\PurchaseService as ObjService;


class PurchasesAnalysisController extends Controller
{
    public $folderPath = "finance::finance.purchases_analysis";
    public $mainRoute = "finance.purchases-analysis";
    public $getData = ["from_date", "to_date"];


    public function index(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $dataSearch = $request->only($this->getData);
            $data['result'] = $action->getByType(PurchaseTypeEnum::Purchase->value, $dataSearch);
            $returnHTML = view($this->folderPath . ".result",$data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data['dataTableRoute'] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.purchases-analysis");
        $data["addButtonText"] = __("finance::finance.purchases-analysis");
        return view($this->folderPath . '.index', $data);
    }

}
