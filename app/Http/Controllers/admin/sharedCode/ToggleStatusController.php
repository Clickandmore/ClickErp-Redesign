<?php

namespace App\Http\Controllers\admin\sharedCode;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToggleStatusController extends Controller
{
    public function toggleStatus(Request $request)
    {
        $id = $request->input('id');
        $table =  $request->table_name;
        $obj = DB::table($table)->find($id);
        if(isset($obj->status)) {
            $newStatus = !$obj->status;
            DB::table($table)->where('id',$id)->update(['status' => $newStatus]);
            $statusText = $newStatus ? 'نشط' : 'غير نشط';
            $message = "تم تحديث الحالة بنجاح إلى $statusText";
            return jsonSuccess(['message' => $message, 'status' => $statusText]);
        }
        return jsonValid(['message' => 'لا يوجد حالات']);
    }
}
