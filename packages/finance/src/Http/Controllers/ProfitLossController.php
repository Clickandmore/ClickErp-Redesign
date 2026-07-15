<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConstraintDetailsService as ObjService;
use Nami\Finance\Services\YearService;

class ProfitLossController extends Controller
{
    public string $folderPath = "finance::finance.profit_loss";

    public function index(Request $request, ObjService $service, YearService $yearService)
    {
        $data['levels'] = $service->getIncomeReportLevels();
        $data['report_period'] = $yearService->getReportPeriod();

        if ($request->ajax()) {
            $reportData = $service->filterProfitLoss($request);

            $returnHTML = view($this->folderPath . ".result")->with([
                "data" => $reportData,
            ])->render();

            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["dataTableRoute"] = route('finance.profit-loss.index');
        $data["bladeTitle"] = __("finance::finance.profit_loss_statement");

        return view($this->folderPath . '.index', $data);
    }
}
