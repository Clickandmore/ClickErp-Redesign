<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\ConstraintFileService as ObjService;

class ConstraintFileController extends Controller
{
    /**
     * @param int $id
     * @param ObjService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id, ObjService $service, ConstraintService $constraintService)
    {
        $file = $service->find($id);
        $constraintId = $file->constraint_id;
        $service->deleteWithFile($id,'file');
        $constraintService->logConstraintActivity(
            $constraintService->find($constraintId),
            'file_deleted',
            __('finance::finance.constraint_file_deleted_log_description')
        );

        return jsonSuccess();
    }
}
