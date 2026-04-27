<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\PrincipleService as ObjService;

class PrincipleReportController extends Controller
{
    public $folderPath = "finance::finance.principle_report";
    public $mainRoute = "finance.principle-report";

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            // Fetch and prepare report data through the service
            $reportData = $service->getPrincipleReport($request);

            // Render the view with the data
            $returnHTML = view($this->folderPath . ".result")->with([
                "data_report" => $reportData['data_report'],
                "group_code" => $reportData['group_code'],
                "group_name" => $reportData['group_name'],
            ])->render();

            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["accounts"] = $service->getTypes();
        $data["bladeTitle"] = __("finance::finance.principle-report");

        return view($this->folderPath . '.index', $data);
    }
}
