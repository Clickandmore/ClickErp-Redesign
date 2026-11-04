<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\TrailBalanceService;
use Nami\Finance\Services\TrailBalanceExportService;
use Nami\Finance\Services\ComprehensiveTableExportService;
use Nami\Finance\Services\YearService;

class TrailBalanceController extends Controller
{
    public string $folderPath = "finance::finance.trail_balance";
    public string $mainRoute = "finance.trail-balance";


    public function index(Request $request,TrailBalanceService $service,YearService $yearService)
    {
        $data['levels'] = $service->showLevels();
        $data['report_period'] = $yearService->getReportPeriod();
        if ($request->ajax()) {
            $includeFirstBalance = isset($request['show_first_balance']) && $request['show_first_balance'];
            $requestedLevel = $request->input('level', $data['levels'][0] ?? 1);
            $levelSummary = $requestedLevel === 'all' ? 'all' : (int) $requestedLevel;
            $fromDate = $request->input('from_date', $data['report_period']->from_date ?? null);
            $toDate = $request->input('to_date', $data['report_period']->to_date ?? null);

            $accountsWithChildren = $service->getAccountSummaryForTrailBalance(
                $levelSummary,
                $fromDate,
                $toDate,
                $includeFirstBalance
            );
            $accountsWithChildren = $service->applyFilters($accountsWithChildren, $service->filtersFromRequest($request));

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

    public function export(
        Request $request,
        TrailBalanceExportService $exportService,
        TrailBalanceService $service,
        YearService $yearService,
        ComprehensiveTableExportService $exporter,
        string $format
    ) {
        return $exportService->download($request, $format, $service, $yearService, $exporter);
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
