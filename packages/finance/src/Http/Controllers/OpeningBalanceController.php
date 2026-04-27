<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\OpeningBalanceService;
use Nami\Finance\Services\YearService;


class OpeningBalanceController extends Controller
{
    public string $folderPath = "finance::finance.opening_balance";


    public function index(request $request,OpeningBalanceService $service,YearService $yeService)
    {
//        return $service->getOpeningBalances($request->all());
        if ($request->ajax()) {
            $data['data'] = $service->getOpeningBalances($request->all());
//            dd($data);
            $html = view($this->folderPath . '.table',$data)->render();
            return jsonSuccess(["html" => $html]);
        }
        $data["bladeTitle"] = __("finance::finance.opening-balance");
        $data["years"] = $yeService->get();

        return view($this->folderPath . '.index', $data);
    }


    public function create()
    {

    }

    public function store(Request $request,objService $service)
    {
        $service->storeFirstBalanceConstraint($request->all());
    }

    public function show(string $id)
    {

    }

    public function edit(string $id)
    {

    }


    public function update(Request $request, string $id)
    {

    }


    public function destroy(string $id)
    {

    }
}
