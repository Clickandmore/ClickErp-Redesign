<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Imports\ItemImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Nami\Finance\Http\Requests\ItemImportRequest as ObjRequest;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\ItemService as ObjService;
use Nami\Finance\Services\ProductNumSettingService;
use Nami\Finance\Services\UnitService;


class ItemImportController extends Controller
{
    public function __construct(
        protected ItemService $itemService,
        protected UnitService $unitService,
        protected ProductNumSettingService $productNumSettingService
    ) {
    }
    public string $folderPath = "finance::finance.items";
    public string $mainRoute = "finance.items-import";

    public function create(Request $request, ObjService $service)
    {
        $data["storeRoute"] = route($this->mainRoute . ".store");
        $data["bladeTitle"] = __("finance::finance.items");
        $data = array_merge($data, $service->getCreateData());
        return view($this->folderPath . '.import_excel', $data);
    }

    public function store(objRequest $request, objService $service)
    {
        $dataInsert = $request->validated();
        Excel::import(
            new ItemImport($this->itemService, $dataInsert, $this->productNumSettingService, $this->unitService),
            $dataInsert['file']
        );
        return jsonSuccess(null, null, 201);
    }
}
