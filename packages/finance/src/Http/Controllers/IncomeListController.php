<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConstraintDetailsService as ObjService;

class IncomeListController extends Controller
{
    public string$folderPath = "finance::finance.income_list";
    public string $mainRoute = "finance.incomeList";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data = $service->filterIncomeList($request);

            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $data,
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["bladeTitle"] = __("finance::finance.incomeList");
        $data["canPermission"] = __("incomeList add");
        return view($this->folderPath . '.index', $data);
    }
}
