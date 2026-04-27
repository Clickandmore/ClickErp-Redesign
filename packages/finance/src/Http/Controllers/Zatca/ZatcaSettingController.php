<?php

namespace Nami\Finance\Http\Controllers\Zatca;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ZatcaSettingRequest;
use Nami\Finance\Services\ZatcaSettingService;

class ZatcaSettingController extends Controller
{
    public string $folderPath = "finance::finance.zatca.setting";
    public string $mainRoute = "zatca.zatca-settings";


    public function index(Request $request, ZatcaSettingService $service)
    {
        $data = [
            "bladeTitle" => __("finance::zatca.Zatca setting"),
            "storeRoute" => route($this->mainRoute . ".store"),
            "zatca_setting" => $service->getSetting(),
        ];

        return view($this->folderPath . '.index', $data);
    }


    public function create(Request $request, ZatcaSettingService $service)
    {
        //
    }


    public function store(ZatcaSettingRequest $request, ZatcaSettingService $service)
    {
        $dataInsert = $request->validated();
        try {
            $service->updateZatca($dataInsert);
            return jsonSuccess(null,null,201);
        }
        catch (\Exception $e){
            return jsonValid(["غير قادر علي استخراج الشهادة يرجي مراجعة البيانات ".$e->getMessage()]);
        }
    }


    public function show(int $id)
    {
        //
    }


    public function edit(int $id, Request $request, ZatcaSettingService $service)
    {
        //
    }


    public function update(ZatcaSettingRequest $request, int $id, ZatcaSettingService $service)
    {
        //
    }


    public function destroy(int $id, ZatcaSettingService $service)
    {
        //
    }
}
