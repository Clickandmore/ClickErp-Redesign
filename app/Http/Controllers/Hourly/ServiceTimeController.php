<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Services\Hourly\ServiceTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ServiceTimeController extends Controller
{
    private function input(Request $request): array {
        return $request->validate(['reason'=>['required',Rule::in(array_keys(ServiceTime::REASONS))],
            'note'=>'nullable|string|max:1000','actual_finished_at'=>'required_if:reason,forgot_finish|nullable|date']);
    }
    public function worker(Request $request,int $visit,ServiceTime $service){
        $result=$service->reportReason(Maid::findOrFail(auth('hourly_maid_api')->id()),$visit,$this->input($request));
        return response()->json(['data'=>$result,'message'=>'تم إرسال السبب للمراجعة دون إضافة مبلغ تلقائيًا.']);
    }
    public function workerWeb(Request $request,int $visit,ServiceTime $service){
        $service->reportReason(Maid::findOrFail(auth('hourly_maid')->id()),$visit,$this->input($request));
        return back()->with('success','تم إرسال السبب للمراجعة دون إضافة مبلغ تلقائيًا.');
    }
    public function index(Request $request){
        abort_unless(AdminGuardHasPerm('hourly_orders_view'),403);abort_unless(ServiceTime::ready(),503,'يجب تهيئة متابعة مدة الخدمة.');
        $status=$request->validate(['status'=>['nullable',Rule::in(['open','resolved','all'])]])['status']??'open';
        $checks=DB::table('hourly_service_time_checks as c')->leftJoin('maids as m','m.id','=','c.maid_id')
            ->leftJoin('hourly_rental_orders as o','o.id','=','c.order_id')->select('c.*','m.name as worker_name','o.order_number')
            ->when($status==='open',fn($q)=>$q->whereIn('c.status',['awaiting_reason','awaiting_admin']))
            ->when($status==='resolved',fn($q)=>$q->where('c.status','resolved'))->orderByDesc('c.id')->paginate(25)->withQueryString();
        $pending=DB::table('hourly_service_time_checks')->whereIn('status',['awaiting_reason','awaiting_admin'])->count();
        return view('admin.hourly.service-time',compact('checks','pending','status'));
    }
    public function scan(ServiceTime $service){
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);abort_unless(ServiceTime::ready(),503);
        $count=$service->scan();return back()->with('success','تم فحص الزيارات الجارية وتحديث '.$count.' سجل متابعة دون تعديل مالي.');
    }
    public function review(Request $request,int $check,ServiceTime $service){
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);abort_unless(ServiceTime::ready(),503);
        $data=$request->validate(['decision'=>['required',Rule::in(array_keys(ServiceTime::DECISIONS))],'note'=>'required|string|max:2000','verified'=>'required|accepted']);
        $service->review($check,$data,(int)auth('admin')->id());return back()->with('success','تم توثيق القرار؛ لم تتغير مبالغ الطلب أو المحفظة.');
    }
}
