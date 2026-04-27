<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\CostCenterRequest as ObjRequest;
use Nami\Finance\Services\CostCenterService as ObjService;
use Throwable;

class CostCenterNotificationController extends Controller
{
    public string $folderPath = "finance::finance.cost_centers_notifications";
    public string $mainRoute = "finance.cost-center-notification";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

     public function index(Request $request, ObjService $service)
     {
         $data["dataTableRoute1"] = route("finance.stocks.index") . "?notification=true";
//         $data["dataTableRoute2"] = route("employees.index") . "?notification=true";
        $data["bladeTitle"] = __("finance::finance.cost_centers");
        return view($this->folderPath . '.index', $data);
     }

     /**
      * @param Request $request
      * @param ObjService $service
      * @return JsonResponse|void
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

     }
 }
