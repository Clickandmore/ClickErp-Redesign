<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\SupplierFileService as ObjService;


class SupplierFileController extends Controller
{
    public function destroy(int $id, ObjService $service)
    {
        $service->deleteWithFile($id,'file');
        return jsonSuccess();
    }
}
