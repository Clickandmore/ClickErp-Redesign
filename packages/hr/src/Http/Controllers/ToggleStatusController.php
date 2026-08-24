<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Support\StatusModelRegistry;

class ToggleStatusController extends Controller
{
    public function toggleStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'table_name' => ['required', 'string'],
        ]);
        $modelClass = StatusModelRegistry::modelFor($data['table_name']);

        if (!$modelClass) {
            return jsonValid(['message' => 'جدول الحالة غير مسموح به']);
        }

        $obj = $modelClass::query()->find($data['id']);

        if ($obj && isset($obj->status)) {
            $newStatus = !(bool) $obj->status;
            $obj->update(['status' => $newStatus]);
            $statusText = $newStatus ? 'نشط' : 'غير نشط';
            $message = "تم تحديث الحالة بنجاح إلى $statusText";
            return jsonSuccess(['message' => $message, 'status' => $statusText]);
        }
        return jsonValid(['message' => 'لا يوجد حالات']);
    }
}
