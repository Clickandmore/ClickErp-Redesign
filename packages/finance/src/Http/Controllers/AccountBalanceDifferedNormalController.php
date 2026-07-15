<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService as ObjService;

class AccountBalanceDifferedNormalController extends Controller
{
    public string $folderPath = "finance::finance.account_balance_differed_normal";
    public string $mainRoute = "finance.account-balance-differed-normal";


    public function index(Request $request, ObjService $service)
    {
        $filters = $request->only(['from_date', 'to_date', 'keyword']);
        $data['accounts'] = $service->balanceAccountsDifferedFromNature($filters);
        $data['filters'] = $filters;

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".result")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] =__("finance::finance.account-balance-differed-normal");
        $data["resultView"] = $this->folderPath . '.result';
        $data["exportFileName"] = 'accounts-differed-from-nature.xlsx';

        return view('finance::finance.creditor_accounts.index', $data);
    }

}
