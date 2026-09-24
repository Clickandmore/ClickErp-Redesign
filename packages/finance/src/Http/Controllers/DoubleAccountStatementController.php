<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountStatementService as objService;
use Nami\Finance\Services\ComprehensiveTableExportService;
use Nami\Finance\Services\DoubleAccountStatementExportService;

class DoubleAccountStatementController extends Controller
{

    public string $folderPath = "finance::finance.double_account_statment";
    public $getData = ["from_date", "to_date","type" ,'double_account_id','other_account'];
    public string $mainRoute = "finance.double-account-statement";

    public function index(Request $request, objService $service)
    {
        if ($request->ajax()) {
            $dataSearch = $request->only($this->getData);
            $accountList = $service->getDoubleAccountSubAccountsArr($dataSearch["double_account_id"]);
            $data = $service->filterDoubleAccount($dataSearch, $accountList, true);
            $otherAccount = ($dataSearch["other_account"] == "yes")? $service->filterConstraints($dataSearch, $accountList, $data->pluck('constraint_id')->all()):[];
            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $data,
                "otherAccount" => $otherAccount,
                "show_other_account" => $dataSearch["other_account"],
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["double_accounts"] =  $service->getDoubleAccounts();
        $data["bladeTitle"] = __("finance::finance.double-account-statment");
        return view($this->folderPath . '.index', $data);
    }

    public function export(
        Request $request,
        DoubleAccountStatementExportService $service,
        ComprehensiveTableExportService $exporter,
        string $format
    ) {
        return $service->download($request, $format, $exporter);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
