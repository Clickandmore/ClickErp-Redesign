<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\AccountTransferService as ObjService;
use Nami\Finance\Services\ConstraintService;

class AccountTransferConfirmController extends Controller
{
    public function show($id, ObjService $service, ConstraintService $constraintService)
    {
        $accountTransfer = $service->confirmTransfer($id);
        $lastCode = $constraintService->lastCode();
        $admin_id = $service->getAdminID();
        $service->storeTransferConstraint($accountTransfer,$lastCode,$admin_id);
        return jsonSuccess(null);

    }

}
