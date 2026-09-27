<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\AccountStatementExportService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Throwable;

class AccountStatementController extends Controller
{
    public string $folderPath = "finance::finance.account_statement";
    public $getData = ["from_date", "to_date", "type", 'account_id', 'other_account'];
    public string $mainRoute = "finance.account-statement";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service,AccountService $accountService)
    {
        $dataSearch = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
            'type' => 'both',
            'account_id' => '',
            'other_account' => 'yes',
        ], $request->only($this->getData));

        if ($request->ajax()) {
            $result = $this->buildStatementResult($dataSearch, $service, $accountService);

            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $result['data'],
                "firstBalance" => $result['firstBalance'],
                "otherAccount" => $result['otherAccount'],
                "show_other_account" => $dataSearch["other_account"],
                "accountName" => $result['selectedAccount']->name ?? null,
                "filters" => $dataSearch,
            ])->render();

            return jsonSuccess([
                "html" => $returnHTML,
                "count" => $result['hasData'] ? 1 : 0,
            ]);
        }
        $selectedAccount = !empty($dataSearch['account_id'])
            ? Account::query()->select(['id', 'code', 'name'])->find($dataSearch['account_id'])
            : null;

        // The account list is loaded remotely to keep this page fast even when
        // the chart of accounts contains thousands of records.
        $data["accounts"] = $selectedAccount ? collect([$selectedAccount]) : collect();
        $data["accountSearchRoute"] = route($this->mainRoute . '.account-options');
        $data["bladeTitle"] = __("finance::finance.account-statment");
        $data["filters"] = $dataSearch;
        $data["initialResultHtml"] = null;
        $data["initialResultCount"] = 0;

        // A statement opened from the account tree or another report should be
        // useful immediately, even if JavaScript/AJAX is slow or unavailable.
        if ($selectedAccount) {
            $result = $this->buildStatementResult($dataSearch, $service, $accountService);
            $data["initialResultHtml"] = view($this->folderPath . ".result")->with([
                "data" => $result['data'],
                "firstBalance" => $result['firstBalance'],
                "otherAccount" => $result['otherAccount'],
                "show_other_account" => $dataSearch["other_account"],
                "accountName" => $selectedAccount->name,
                "filters" => $dataSearch,
            ])->render();
            $data["initialResultCount"] = $result['hasData'] ? 1 : 0;
        }

        return view($this->folderPath . '.index', $data);
    }

    public function export(Request $request, AccountStatementExportService $exporter, string $format)
    {
        return $exporter->download($request, $format);
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
                ? $service->filterCounterAccountsForStatement($statementData)
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

    public function accountOptions(Request $request): JsonResponse
    {
        $search = trim(mb_substr((string) $request->input('q', ''), 0, 100));
        $page = max(1, (int) $request->input('page', 1));

        $query = Account::query()->select(['id', 'code', 'name']);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(30, ['id', 'code', 'name'], 'page', $page);

        return response()->json([
            'results' => collect($accounts->items())->map(static fn (Account $account) => [
                'id' => $account->id,
                'code' => (string) $account->code,
                'name' => $account->name,
                'text' => trim($account->code . ' — ' . $account->name),
            ])->values(),
            'pagination' => ['more' => $accounts->hasMorePages()],
        ]);
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
