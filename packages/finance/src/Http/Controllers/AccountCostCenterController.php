<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountCostCenterService as ObjService;
use Throwable;

class AccountCostCenterController extends Controller
{
    public string $folderPath = "finance::finance.account_cost_centers";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'cost_center_id', 'other_account'];
    public string $mainRoute = "finance.account-cost-centers";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        $dataSearch = $request->only($this->getData);
        if ($request->ajax()) {
//            dd($dataSearch);
            $data = $service->filterAccount($dataSearch);
            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $data,
                "linkSummary" => $service->getCostCenterLinkSummary(
                    isset($dataSearch['cost_center_id']) ? (int) $dataSearch['cost_center_id'] : null
                ),
//                "otherAccount" => $otherAccount,
//                "show_other_account" => $dataSearch["other_account"],
            ])->render();

            return jsonSuccess(["html" => $returnHTML,'count'=>$data->count()]);
        }
        $data["accounts"] = $service->getAccounts();
        $data["cost_centers"] = $service->getCostCenters();
        $data["ajaxRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.accounts-cost-centers");
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(Request $request, ObjService $service)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id)
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(Request $request, int $id, ObjService $service)
    {
        //
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
        //
    }
}
