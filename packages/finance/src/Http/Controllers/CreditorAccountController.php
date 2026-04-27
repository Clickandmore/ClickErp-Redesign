<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService as ObjService;

class CreditorAccountController extends Controller
{
    public string $folderPath = "finance::finance.creditor_accounts";
    public string $mainRoute = "finance.creditor-accounts";


    public function index(Request $request, ObjService $service)
    {
        $data['accounts'] = $service->balanceAccountsByType('creditor',$request->all());
        if($request->ajax()){
            $data['accounts'] = $service->balanceAccountsByType('creditor',$request->all());
            $returnHTML = view($this->folderPath . ".result")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.creditor-accounts");

        return view($this->folderPath . '.index', $data);
    }

}
