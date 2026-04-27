<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\RentalMaidFinanceService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ConfirmSettingRequest as ObjRequest;
use Nami\Finance\Services\ConfirmSettingService as ObjService;
use Throwable;

class ConfirmSettingController extends Controller
{
    public string $folderPath = "finance::finance.confirm_settings";
    public string $mainRoute = "finance.confirm-settings";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service, RentalMaidFinanceService $rentalMaidFinanceService)
    {
        $settings = $service->getAllSettings();
        $data = [
            "bladeTitle" => __("finance::finance.Account settings"),
            "addButtonText" => __("finance::finance.Account settings"),
            "storeRoute" => route($this->mainRoute . ".store"),
            "obj" => $settings,
            "accounts" => $service->getAccounts(),
            "debtor_accounts" => $service->getDebtorAccounts(),
            "revenue_tax_accounts" => $service->getRevenueTaxAccounts(),
            "musaned_source_accounts" => $service->getMusanedSourceAccounts(),
            "musaned_accounts" => $service->getMusanedIntermediateAccounts(),
            "tax_settings" => $service->getTaxSetting(),
            "rental_maid_expense_definitions" => $rentalMaidFinanceService->getSettingDefinitions($settings),
        ];
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
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $service->storeData($dataInsert);
        return jsonSuccess(null,null,201);
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
    public function update(ObjRequest $request, int $id, ObjService $service)
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
