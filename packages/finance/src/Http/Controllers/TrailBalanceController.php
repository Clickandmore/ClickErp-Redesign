<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\TrailBalanceService;
use Nami\Finance\Services\YearService;

class TrailBalanceController extends Controller
{
    public string $folderPath = "finance::finance.trail_balance";
    public string $mainRoute = "finance.trail-balance";


    public function index(Request $request,TrailBalanceService $service,YearService $yearService)
    {
        $data['levels'] = $service->showLevels();
        $data['active_year'] = $yearService->getActiveYear();
        if ($request->ajax()) {
            $includeFirstBalance = isset($request['show_first_balance']) && $request['show_first_balance'];
            $requestedLevel = $request->input('level', $data['levels'][0] ?? 1);
            $levelSummary = $requestedLevel === 'all' ? 'all' : (int) $requestedLevel;
            $fromDate = $request->input('from_date', $data['active_year']['from_date'] ?? null);
            $toDate = $request->input('to_date', $data['active_year']['to_date'] ?? null);

            $accountsWithChildren = $service->getAccountSummaryForTrailBalance(
                $levelSummary,
                $data['active_year']['id'],
                $fromDate,
                $toDate,
                $includeFirstBalance
            );

            $returnHTML = view($this->folderPath . ".result")->with([
                'accountsWithChildren' => $accountsWithChildren,
                'includeFirstBalance' => $includeFirstBalance,
                'allLevels' => $levelSummary === 'all',
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("finance::finance.trail-balance");
        return view($this->folderPath . '.index', $data);
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
