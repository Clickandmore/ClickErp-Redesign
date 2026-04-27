<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\AccountTransferFileService as ObjService;


class AccountTransferFileController extends Controller
{
    /**
     * @param int $id
     * @param ObjService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->deleteWithFile($id,'file');
        return jsonSuccess();
    }
}
