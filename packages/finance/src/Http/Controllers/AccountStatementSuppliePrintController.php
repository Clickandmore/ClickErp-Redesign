<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;

class AccountStatementSuppliePrintController extends Controller
{
    public string $folderPath = "finance::finance.account_statement";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'other_account'];
    public string $mainRoute = "finance.account-statement-supplier-print";

    public function __construct()
    {
        $this->middleware('checkActiveYear')->only(['create','edit']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service, AccountService $accountService)
    {
        $dataSearch = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
            'type' => 'both',
            'account_id' => '',
            'other_account' => 'yes',
        ], $request->only($this->getData));

        $data = collect();
        $firstBalance = null;
        $otherAccount = [];
        $accountName = null;

        if (!empty($dataSearch['account_id'])) {
            $data = $service->filterAccount($dataSearch);
            $firstBalance = $service->getFirstBalance($dataSearch);
            $otherAccount = ($dataSearch["other_account"] == "yes") ? $service->filterConstraints($dataSearch) : [];
            $accountName = optional($accountService->find($dataSearch["account_id"]))->name;
        }

        return view($this->folderPath . ".print")->with([
            "data" => $data,
            "firstBalance" => $firstBalance,
            "otherAccount" => $otherAccount,
            "accountName" => $accountName,
            "title" => "كشف حساب مورد",
            "show_other_account" => $dataSearch["other_account"],
            "filters" => $dataSearch,
            "reportTitle" => __("finance::finance.account-statement-suppliers"),
            "showDollarEquivalent" => true,
        ]);
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
