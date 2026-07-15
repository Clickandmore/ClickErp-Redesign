<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService as ObjService;

class DebtorAccountController extends Controller
{
    public string $folderPath = "finance::finance.debtor_accounts";
    public string $mainRoute = "finance.debtor-accounts";


    public function index(Request $request, ObjService $service)
    {
        $filters = $request->only(['from_date', 'to_date', 'keyword']);
        $data['accounts'] = $service->balanceAccountsByActualSide('debtor', $filters);
        $data['filters'] = $filters;

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".result")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.debtor-accounts");
        $data["resultView"] = $this->folderPath . '.result';
        $data["exportFileName"] = 'debtor-accounts.xlsx';

        return view('finance::finance.creditor_accounts.index', $data);
    }

}
