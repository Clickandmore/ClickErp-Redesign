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
        $filters = $request->only(['from_date', 'to_date', 'keyword']);
        $data['accounts'] = $service->balanceAccountsByActualSide('creditor', $filters);
        $data['filters'] = $filters;

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".result")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.creditor-accounts");
        $data["resultView"] = $this->folderPath . '.result';
        $data["exportFileName"] = 'creditor-accounts.xlsx';

        return view($this->folderPath . '.index', $data);
    }

}
