<?php

namespace Nami\Finance\Http\Controllers;


use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\AccountTransferService as ObjService;

class AccountTransferPrintController extends Controller
{
    protected string $folderPath = "finance::finance.account_transfer";
    protected string $mainRoute = "finance.account-transfer-print";


    public function show($id, Request $request, ObjService $service, AccountService $accountService)
    {
        return view($this->folderPath . ".print")->with([
            "obj" => $service->find($id),
            'accounts' => $accountService->getAccountWithNoChild()]);

    }

}
