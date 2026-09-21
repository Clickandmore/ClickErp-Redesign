<?php

namespace App\Http\Controllers\admin\office;

use App\Http\Controllers\admin\Controller;
use App\Models\office\Office;
use App\Services\office\OfficeCvsSuspensionService;

/**
 * زر «تعليق السير الذاتية» في شاشة الوكلاء الخارجيين.
 * محمي بصلاحية مستقلة: PermKeys::OFFICE_SUSPEND_CVS عبر AdminRoutePermissionMatrix.
 */
class OfficeCvsSuspensionController extends Controller
{
    public function update($officeId, OfficeCvsSuspensionService $service)
    {
        if (! $service->isSupported()) {
            return jsonError(__('ingaz.office_cvs_suspension_unavailable'));
        }

        $office = Office::withoutBranchScope()->findOrFail($officeId);

        $result = $service->toggle($office);

        return jsonSuccess(
            [
                'cvs_suspended' => $result['suspended'],
                'affected' => $result['affected'],
            ],
            __($result['suspended']
                ? 'ingaz.office_cvs_suspended_done'
                : 'ingaz.office_cvs_resumed_done', ['count' => $result['affected']])
        );
    }
}
