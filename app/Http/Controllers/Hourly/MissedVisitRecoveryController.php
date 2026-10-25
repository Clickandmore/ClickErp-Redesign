<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\MissedVisitRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MissedVisitRecoveryController extends Controller
{
    public function store(Request $request,int $id,int $visit,MissedVisitRecovery $service)
    {
        $data=$request->validate(['type'=>'required|in:reschedule,refund,replacement','request_token'=>'required|uuid',
            'requested_start'=>'required_if:type,reschedule|nullable|date_format:Y-m-d\\TH:i|after:now','note'=>'nullable|string|max:1000']);
        $order=HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id);
        return response()->json(['data'=>$service->request($order,$visit,$data)]);
    }
    public function customerSlots(Request $request,int $id,int $visit,MissedVisitRecovery $service)
    {
        $data=$request->validate(['date'=>'required|date_format:Y-m-d|after_or_equal:today']);
        $order=HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id);
        return response()->json(['data'=>$service->customerAvailableSlots($order,$visit,$data['date'])]);
    }
    public function index(MissedVisitRecovery $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_view'),403);
        $orders=HourlyRentalOrder::whereNotNull('booking_metadata->missed_visit_requests')->with('user:id,name')->latest('id')->paginate(20);
        $slotChecks=[];
        foreach($orders as $order)foreach(array_keys($order->booking_metadata['missed_visit_requests']??[]) as $visitId)
            if($check=$service->requestedSlotStatus($order,(int)$visitId))$slotChecks[$order->id][$visitId]=$check;
        return view('admin.hourly.missed-requests',compact('orders','slotChecks'));
    }
    public function review(Request $request,HourlyRentalOrder $order,int $visit,MissedVisitRecovery $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $reschedule=(($order->booking_metadata['missed_visit_requests'][$visit]['type']??null)==='reschedule');
                $data=Validator::make($request->all(),['request_id'=>'required|uuid','decision'=>'required|in:approve,reject',
            'verified_non_execution'=>'sometimes|boolean','new_start'=>'prohibited','reason'=>'required|string|max:2000',
            'replacement_worker_ids'=>'sometimes|array|max:20','replacement_worker_ids.*'=>'nullable|integer'])->validate();
        if($data['decision']==='approve' && (float)$order->paid_amount>0 && (($order->booking_metadata['missed_visit_requests'][$visit]['type']??null)==='refund'))
            abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        $result=$service->review($order,$visit,$data,(int)auth('admin')->id());
        if($request->expectsJson())return response()->json(['data'=>$result]);
        return back()->with('success','تمت مراجعة طلب معالجة الزيارة.');
    }
    public function slots(Request $request,HourlyRentalOrder $order,int $visit,MissedVisitRecovery $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $data=$request->validate(['request_id'=>'required|uuid','date'=>'required|date_format:Y-m-d|after_or_equal:today']);
        return response()->json(['data'=>$service->availableSlots($order,$visit,$data['request_id'],$data['date'])]);
    }
}
