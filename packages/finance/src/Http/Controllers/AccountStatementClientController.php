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
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\AccountStatementExportService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Throwable;

class AccountStatementClientController extends Controller
{
    public string $folderPath = "finance::finance.account_statement.clients";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'other_account'];
    public string $mainRoute = "finance.account-statement-clients";

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
        ], $request->only($this->getData));

        if ($request->ajax()) {
            $result = $this->buildStatementResult($dataSearch, $service, $accountService);

            $returnHTML = view("finance::finance.account_statement.result")->with([
                "data" => $result['data'],
                "firstBalance" => $result['firstBalance'],
                "otherAccount" => $result['otherAccount'],
                "show_other_account" => $dataSearch["other_account"],
                "accountName" => $result['selectedAccount']->name ?? null,
                "filters" => $dataSearch,
                "reportTitle" => __("finance::finance.account-statement-clients"),
            ])->render();

            return jsonSuccess([
                "html" => $returnHTML,
                "count" => $result['hasData'] ? 1 : 0,
            ]);
        }

        $data["accounts"] = $service->getAccounts(AccountTypesEnum::Client->value);
        $data["ajaxRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.account-statement-clients");
        $data["filters"] = $dataSearch;
        $data["statementRoute"] = route($this->mainRoute . ".index");
        $data["printRoute"] = route("finance.account-statement-clients-print.index");
        $data["printPermission"] = 'finance_account_statement_clients_export';
        $data["exportExcelRoute"] = route($this->mainRoute . '.export', 'excel');
        $data["exportPdfRoute"] = route($this->mainRoute . '.export', 'pdf');
        $data["exportScopeDescription"] = 'جميع حسابات العملاء عند ترك الحساب فارغًا';
        $data["initialResultHtml"] = null;
        $data["initialResultCount"] = 0;

        // Links from the clients page already contain the account ID, so render
        // that statement during the initial request instead of depending on a
        // second AJAX request in the browser.
        if (!empty($dataSearch['account_id'])) {
            $result = $this->buildStatementResult($dataSearch, $service, $accountService);

            if (!$data["accounts"]->contains('id', $result['selectedAccount']->id)) {
                $data["accounts"]->push($result['selectedAccount']);
            }

            $data["initialResultHtml"] = view("finance::finance.account_statement.result")->with([
                "data" => $result['data'],
                "firstBalance" => $result['firstBalance'],
                "otherAccount" => $result['otherAccount'],
                "show_other_account" => $dataSearch["other_account"],
                "accountName" => $result['selectedAccount']->name,
                "filters" => $dataSearch,
                "reportTitle" => __("finance::finance.account-statement-clients"),
            ])->render();
            $data["initialResultCount"] = $result['hasData'] ? 1 : 0;
        }

        return view('finance::finance.account_statement.index', $data);
    }

    private function buildStatementResult(
        array $filters,
        ObjService $service,
        AccountService $accountService
    ): array {
        $statementData = collect();
        $firstBalance = null;
        $otherAccount = [];
        $selectedAccount = null;

        if (!empty($filters['account_id'])) {
            $statementData = $service->filterAccount($filters, true);
            $firstBalance = $service->getFirstBalance($filters);
            $otherAccount = $filters['other_account'] === 'yes'
                ? $service->filterConstraints($filters, $statementData->pluck('constraint_id')->all())
                : [];
            $selectedAccount = $accountService->find($filters['account_id']);
        }

        $hasOpeningBalance = abs((float) ($firstBalance->sum_debtor_value ?? 0)) > 0.01
            || abs((float) ($firstBalance->sum_creditor_value ?? 0)) > 0.01;

        return [
            'data' => $statementData,
            'firstBalance' => $firstBalance,
            'otherAccount' => $otherAccount,
            'selectedAccount' => $selectedAccount,
            'hasData' => $statementData->isNotEmpty() || $hasOpeningBalance,
        ];
    }

    public function export(Request $request, AccountStatementExportService $exporter, string $format)
    {
        return $exporter->download(
            $request,
            $format,
            AccountTypesEnum::Client->value,
            'التصدير الشامل لكشوف حسابات العملاء',
            'client-account-statements'
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
