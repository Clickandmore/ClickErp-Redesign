<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Throwable;

class AccountStatementSupplierController extends Controller
{
    public string $folderPath = "finance::finance.account_statement.suppliers";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'other_account', 'worker_search'];
    public string $mainRoute = "finance.account-statement-suppliers";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service, AccountService $accountService)
    {
        $dataSearch = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
            'type' => 'both',
            'account_id' => '',
            'other_account' => 'no',
            'supplier_workers' => true,
            'worker_search' => '',
        ], $request->only($this->getData));

        if ($request->ajax()) {
            $data = collect();
            $firstBalance = null;
            $otherAccount = [];
            $selectedAccount = null;

            if (!empty($dataSearch['account_id'])) {
                $data = $service->filterAccount($dataSearch, true);
                $firstBalance = $service->getFirstBalance($dataSearch);
                $otherAccount = ($dataSearch["other_account"] == "yes") ? $service->filterConstraints($dataSearch, $data->pluck('constraint_id')->all()) : [];
                $selectedAccount = $accountService->find($dataSearch['account_id']);
            }

            $hasOpeningBalance = abs((float) ($firstBalance->sum_debtor_value ?? 0)) > 0.01
                || abs((float) ($firstBalance->sum_creditor_value ?? 0)) > 0.01;

            $returnHTML = view("finance::finance.account_statement.result")->with([
                "data" => $data,
                "firstBalance" => $firstBalance,
                "otherAccount" => $otherAccount,
                "show_other_account" => $dataSearch["other_account"],
                "accountName" => $selectedAccount->name ?? null,
                "filters" => $dataSearch,
                "reportTitle" => __("finance::finance.account-statement-suppliers"),
                "showDollarEquivalent" => true,
            ])->render();

            return jsonSuccess([
                "html" => $returnHTML,
                "count" => ($data->count() > 0 || $hasOpeningBalance) ? 1 : 0,
            ]);
        }

        $data['showWorkerSearch'] = true;
        $data["accounts"] = $accountService->getSupplierStatementAccounts();
        $data["ajaxRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.account-statement-suppliers");
        $data["filters"] = $dataSearch;
        $data["statementRoute"] = route($this->mainRoute . ".index");
        $data["printRoute"] = route("finance.account-statement-supplier-print.index");
        $data["printPermission"] = 'finance_account_statement_suppliers_export';

        return view('finance::finance.account_statement.index', $data);
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
