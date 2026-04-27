<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\StockSettingRequest as ObjRequest;
use Nami\Finance\Services\StockSettingService as ObjService;

class StockSettingController extends Controller
{
    public string $folderPath = "finance::finance.stock_settings";
    public string $mainRoute = "finance.stock-settings";


    public function index(Request $request, ObjService $service)
    {
        $data = [
            "bladeTitle" => __("finance::finance.Stock settings"),
            "addButtonText" => __("finance::finance.Stock settings"),
            "storeRoute" => route($this->mainRoute . ".store"),
            "obj" => $service->getSetting(),
        ];

        return view($this->folderPath . '.index', $data);
    }


    public function create(Request $request, ObjService $service)
    {
        //
    }


    public function store(ObjRequest $request, ObjService $service)
    {
        $row = $service->getSetting();
        $dataInsert = $request->validated();
        $row->update($dataInsert);
        return jsonSuccess(null,null,201);
    }


    public function show(int $id)
    {
        //
    }


    public function edit(int $id, Request $request, ObjService $service)
    {
        //
    }


    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        //
    }


    public function destroy(int $id, ObjService $service)
    {
        //
    }
}
