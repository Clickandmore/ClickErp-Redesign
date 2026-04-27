<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\PrincipleService as ObjService;

class GroupDepreciationController extends Controller
{
    public $folderPath = "finance::finance.group_depreciation";
    public $mainRoute = "finance.group-depreciation";

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            // Get the principles and report data from the service
            $reportData = $service->getPrincipleReportWithConstraints($request);

            // Render the view with the data
            $returnHTML = view($this->folderPath . ".result")->with([
                "data_report" => $reportData['data_report'],
                "group_code"  => $reportData['group_code'],
                "group_name"  => $reportData['group_name'],
                "principles"  => $reportData['principles'],
                "type_search" => $reportData['type_search'],
            ])->render();

            return jsonSuccess(["html" => $returnHTML]);
        }

        // Prepare non-AJAX request data
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["accounts"] = $service->getTypes();
        $data["bladeTitle"] = __("finance::finance.group-depreciation");

        return view($this->folderPath . '.index', $data);
    }
}
