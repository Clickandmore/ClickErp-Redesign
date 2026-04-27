<?php

namespace Nami\Finance\Http\Controllers;



use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\PrincipleImageService as ObjService;

class PrincipleImageController extends Controller
{
    public function destroy(int $id, ObjService $service)
    {
        $service->deleteWithFile($id,'file');
        return jsonSuccess();
    }
}
