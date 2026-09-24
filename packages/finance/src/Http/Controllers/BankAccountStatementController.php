<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Services\BankAccountStatementService as ObjService;
use Nami\Finance\Services\AccountStatementExportService;
use Throwable;

class BankAccountStatementController extends Controller
{
    public string $folderPath = "finance::finance.bank_account_statement";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'other_account'];
    public string $mainRoute = "finance.bank-account-statement";

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
            $data = $service->filterAccount($dataSearch, true);
            $firstBalance  = $service->getFirstBalance($dataSearch);
            $otherAccount = ($dataSearch["other_account"] == "yes") ? $service->filterConstraints($dataSearch, $data->pluck('constraint_id')->all()) : [];
            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $data,
                "firstBalance" => $firstBalance,
                "otherAccount" => $otherAccount,
                "show_other_account" => $dataSearch["other_account"],
            ])->render();
            return jsonSuccess(["html" => $returnHTML,'count'=>$data->count()]);
        }
        $data["accounts"] = $service->getAccounts(AccountTypesEnum::Bank->value);
        $data["ajaxRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.bank-account-statement");
        return view($this->folderPath . '.index', $data);
    }

    public function export(Request $request, AccountStatementExportService $exporter, string $format)
    {
        return $exporter->download(
            $request,
            $format,
            AccountTypesEnum::Bank->value,
            'التصدير الشامل لكشوف الحسابات البنكية',
            'bank-account-statements'
        );
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
