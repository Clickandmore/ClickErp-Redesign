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
        $data['accounts'] = $service->balanceAccountsByTypeNoNormal($request->all());
        if($request->ajax()){
            $data['accounts'] = $service->balanceAccountsByTypeNoNormal($request->all());
            $returnHTML = view($this->folderPath . ".result")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] =__("finance::finance.account-balance-differed-normal");
        return view($this->folderPath . '.index', $data);
    }

}
