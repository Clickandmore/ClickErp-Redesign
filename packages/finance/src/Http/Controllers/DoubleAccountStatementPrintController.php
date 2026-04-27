<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Middleware\CheckActiveYear;
use Nami\Finance\Services\AccountStatementService as objService;
use Nami\Finance\Services\DoubleAccountService;
use Nami\Finance\Services\YearService;

class DoubleAccountStatementPrintController extends Controller
{
    public string $folderPath = "finance::finance.double_account_statment";
    public $getData = ["from_date", "to_date","type" ,'double_account_id','other_account'];
    public string $mainRoute = "finance.double-account-statement-print";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create','edit']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objService $service, DoubleAccountService $accountService)
    {
        $dataSearch = $request->only($this->getData);
        $accountList = $service->getDoubleAccountSubAccountsArr($dataSearch["double_account_id"]);
        $data = $service->filterDoubleAccount($dataSearch, $accountList);
        $otherAccount = ($dataSearch["other_account"] == "yes")
            ? $service->filterConstraints($dataSearch, $accountList)
            : [];

        $accounts = $accountService->find($dataSearch["double_account_id"]);
        $accountName = $accounts["name"];
        // إعداد البيانات اللازمة للعرض
        $viewData = [
            "data" => $data,
            "accountName" => $accountName,
            "title" => "كشف حساب مزدوج",
            "otherAccount" => $otherAccount,
            "show_other_account" => $dataSearch["other_account"],
        ];

        // عرض البيانات في صفحة الطباعة
        return view($this->folderPath . '.print', $viewData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ObjService $service, YearService $yearService)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ObjService $service , $id)
    {
        $data['obj'] = $service->find($id);
        return view($this->folderPath . '.print', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, ObjService $service, YearService $yearService)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {

    }
}
