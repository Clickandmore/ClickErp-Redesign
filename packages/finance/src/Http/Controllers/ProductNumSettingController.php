<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ProductNumSettingRequest as ObjRequest;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\ProductNumSettingService as ObjService;
use Throwable;

class ProductNumSettingController extends Controller
{
    public string $folderPath = "finance::finance.product-num-settings";
    public string $mainRoute = "finance.product-num-settings";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service, ItemService $itemService)
    {
        $data["bladeTitle"] = __("finance::finance.product-num-settings");
        $data['storeRoute'] = route($this->mainRoute . ".store");
        $data['mainCategories'] = $service->getMainCategories();
        $data['subCategories'] = $service->getSubCategories();
        $data['setting_type'] = $service->first()->type ?? 'allProducts';
        $data['setting_code'] = $service->get();
        $data['items'] = $itemService->get();
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        if($service->get()->count() > 0){
            $service->deleteAll();
        }
        $data = $service->storeSetting($dataInsert);
        return jsonSuccess($data,null, 201);
    }


    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
