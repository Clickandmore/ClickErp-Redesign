<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyRentalOrder,HourlyVisit};
use App\Services\Hourly\{ServiceIncident,OrderManagementService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};

class ServiceIncidentController extends Controller
{
    private function reports(string $actor,int $id)
    {
        abort_unless(Schema::hasTable('hourly_service_incidents'),503);
        return response()->json(['data'=>DB::table('hourly_service_incidents')->where('reporter_type',$actor)->where('reporter_id',$id)
            ->select('id','visit_id','maid_id','category','status','created_at','reviewed_at')->orderByDesc('id')->limit(100)->get()]);
    }
    public function driverReports(){return $this->reports('driver',(int)auth('hourly_driver_api')->id());}
    public function workerReports(){return $this->reports('worker',(int)auth('hourly_maid_api')->id());}
    private function reportData(Request $request): array
    {
        return $request->validate(['request_token'=>'required|uuid','category'=>'required|in:illness,accident,emergency',
            'note'=>'required|string|max:2000','maid_id'=>'sometimes|integer']);
    }
    public function worker(Request $request,int $visit,ServiceIncident $service)
    {
        $maid=(int)auth('hourly_maid_api')->id();
        $row=DB::table('hourly_visit_workers')->where('id',$visit)->where('maid_id',$maid)->first();abort_unless($row,404);
        return response()->json(['data'=>$service->report((int)$row->hourly_visit_id,$maid,'worker',$maid,$this->reportData($request))]);
    }
    public function driver(Request $request,int $visit,ServiceIncident $service)
    {
        $data=$this->reportData($request);$request->validate(['maid_id'=>'required|integer']);
        return response()->json(['data'=>$service->report($visit,(int)$data['maid_id'],'driver',(int)auth('hourly_driver_api')->id(),$data)]);
    }
    public function driverWeb(Request $request,int $visit,ServiceIncident $service)
    {
        $data=$this->reportData($request);$request->validate(['maid_id'=>'required|integer']);
        $service->report($visit,(int)$data['maid_id'],'driver',(int)auth('hourly_driver')->id(),$data);
        return back()->with('success','تم إرسال البلاغ للإدارة للتحقق؛ البلاغ لا يلغي الزيارة أو يسجل تنفيذها.');
    }
    public function workerWeb(Request $request,int $visit,ServiceIncident $service)
    {
        $maid=(int)auth('hourly_maid')->id();$data=$this->reportData($request);
        $service->report($visit,$maid,'worker',$maid,$data);
        return back()->with('success','تم إرسال البلاغ للإدارة للتحقق.');
    }
    public function adminReport(Request $request,HourlyRentalOrder $order,int $visit,ServiceIncident $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);$order->visits()->findOrFail($visit);
        $data=$this->reportData($request);$request->validate(['maid_id'=>'required|integer']);
        $service->report($visit,(int)$data['maid_id'],'admin',(int)auth('admin')->id(),$data);
        return redirect()->route('admin.hourly-incidents.index')->with('success','تم تسجيل البلاغ؛ راجعه واعتمده لتفعيل المعالجة.');
    }
    public function index()
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_view'),403);
        abort_unless(Schema::hasTable('hourly_service_incidents'),503,'يجب تشغيل ترحيل بلاغات تعذر الخدمة.');
        $incidents=DB::table('hourly_service_incidents as i')->leftJoin('maids as m','m.id','=','i.maid_id')
            ->leftJoin('hourly_visits as v','v.id','=','i.visit_id')->leftJoin('hourly_rental_orders as o','o.id','=','v.hourly_rental_order_id')
            ->select('i.*','m.name as worker_name','o.order_number','o.id as order_id')->orderByRaw("CASE WHEN i.status='pending' THEN 0 ELSE 1 END")
            ->orderByDesc('i.id')->paginate(25);
        $waitingScope=fn($q)=>$q->where('booking_metadata','like','%awaiting_admin%')->orWhere('booking_metadata','like','%awaiting_customer%')->orWhere('booking_metadata','like','%manual_review%');
        $affected=HourlyRentalOrder::where($waitingScope)->with('visits')->latest('id')->paginate(25,['*'],'followup_page');
        // أرقام ملخص الصفحة: البلاغات حسب حالتها، والزيارات التي ما زالت تنتظر المعالجة في كل الطلبات.
        $counts=DB::table('hourly_service_incidents')->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status');
        $followups=HourlyRentalOrder::where($waitingScope)->get(['id','booking_metadata'])
            ->sum(fn($order)=>collect($order->booking_metadata['service_disruptions']??[])->filter(fn($state)=>in_array($state['status']??null,ServiceIncident::WAITING,true))->count());
        return view('admin.hourly.incidents',compact('incidents','affected','counts','followups'));
    }
    public function review(Request $request,int $incident,ServiceIncident $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $data=$request->validate(['decision'=>'required|in:approve,reject','reason'=>'required|string|max:2000',
            'verified'=>'sometimes|boolean','unavailable_until'=>'required_if:decision,approve|nullable|date_format:Y-m-d\\TH:i|after:now']);
        $service->review($incident,$data,(int)auth('admin')->id());
        return back()->with('success','تمت مراجعة البلاغ وتحديث الزيارات المتأثرة.');
    }
    public function alternatives(Request $request,int $id,int $visit,ServiceIncident $service)
    {
        $order=HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id);$model=$order->visits()->findOrFail($visit);
        abort_unless(!app(\App\Services\Hourly\WorkerReplacement::class)->allowed($order),403);
        return response()->json(['data'=>$service->alternatives($order,$model)]);
    }
    public function choose(Request $request,int $id,int $visit,ServiceIncident $service)
    {
        $data=$request->validate(['disruption_id'=>'required|uuid','replacement_worker_id'=>'required|integer']);
        $order=HourlyRentalOrder::where('user_id',auth('api')->id())->findOrFail($id);
        $service->choose($order,$visit,$data);
        return response()->json(['data'=>['status'=>'confirmed'],'message'=>'تم تأكيد البديلة دون تكلفة إضافية.']);
    }
    public function close(Request $request,HourlyRentalOrder $order,int $visit)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $data=$request->validate(['reason'=>'required|string|max:2000']);
        DB::transaction(function()use($order,$visit,$data){
            $order=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();$model=$order->visits()->lockForUpdate()->findOrFail($visit);
            abort_unless(in_array($model->status,['completed','cancelled','cancelled_by_client'],true) && !ServiceIncident::hasOpenMovement($visit),422,'أغلق التنفيذ وحركة العودة وسوِّ المستحقات قبل إغلاق المتابعة.');
            $meta=$order->booking_metadata??[];$state=$meta['service_disruptions'][$visit]??null;abort_unless($state,404);
            $meta['service_disruptions'][$visit]['status']='resolved';$order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'incident_followup_closed',$state,$meta['service_disruptions'][$visit],(int)auth('admin')->id(),$visit,$data['reason']);
        });
        return back()->with('success','تم إغلاق متابعة البلاغ.');
    }
    public function recordReturn(Request $request,HourlyRentalOrder $order,int $visit)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'),403);
        $data=$request->validate(['confirmed_return'=>'required|accepted','reason'=>'required|string|max:2000']);
        DB::transaction(function()use($order,$visit,$data){
            \App\Models\ingaz\User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $order=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $model=$order->visits()->lockForUpdate()->findOrFail($visit);$state=ServiceIncident::state($order,$visit);
            abort_unless(($state['status']??null)==='manual_review',422,'هذا الإجراء لحالة متابعة تنفيذ جارٍ فقط.');
            if(!empty($state['return_recorded_at']))return;
            app(\App\Services\Hourly\ShelterPresence::class)->close($model,'admin',(int)auth('admin')->id());
            $meta=$order->booking_metadata??[];$meta['service_disruptions'][$visit]['return_recorded_at']=now()->toIso8601String();
            $order->update(['booking_metadata'=>$meta]);
            app(OrderManagementService::class)->audit($order,'incident_return_recorded',$state,$meta['service_disruptions'][$visit],
                (int)auth('admin')->id(),$visit,$data['reason']);
        });
        return back()->with('success','تم تسجيل العودة فقط. حالة التنفيذ والمبالغ كما هي إلى أن تراجع الإدارة التسوية.');
    }
}
