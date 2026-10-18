<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\WorkerReplacement;
use Illuminate\Http\Request;

class WorkerReplacementController extends Controller
{
    public function store(Request $request,HourlyRentalOrder $order,int $visit,WorkerReplacement $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $data=$request->validate(['worker_id'=>'required|integer','replacement_worker_id'=>'required|integer|different:worker_id',
            'reason'=>'required|string|max:1000']);
        $service->replace($order,$visit,$data,(int)auth('admin')->id());
        return back()->with('success','تم استبدال العاملة مع الحفاظ على قيمة الزيارة وإرسال إشعار للعميل.');
    }
}
