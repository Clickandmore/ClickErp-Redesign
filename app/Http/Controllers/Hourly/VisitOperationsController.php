<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyVisit,HourlyTeam,HourlyDriver,HourlyServiceCategory,HourlyProductAttribute};
use App\Services\Hourly\{VisitSearch,VisitBatchService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitOperationsController extends Controller
{
    public function index(Request $request,VisitSearch $search,string $mode='all')
    {
        $mode=$request->route('mode')??$mode;
        abort_unless(in_array($mode,['all','unassigned','past','latest']),404);
        $filters=$search->filters($request,$mode);$query=$search->query($filters,$mode);
        $total=(clone $query)->count();
        $assigned=(clone $query)->whereRaw('(select count(*) from hourly_visit_workers w where w.hourly_visit_id=hourly_visits.id)>=workers_count')->count();
        $visits=$search->sort($query,$filters)->with(['order.user','order.serviceCategory','order.visits','order.payments','workers','deliveryDriver','pickupDriver','teamLeader'])->withCount('workers as assigned_workers_count')->paginate($filters['per_page']??25)->withQueryString();
        $allocations=[];foreach($visits->getCollection()->pluck('order')->unique('id') as $order)$allocations+=app(\App\Services\Hourly\VisitPaymentAllocation::class)->forOrder($order);
        $timeExpression=DB::connection()->getDriverName()==='sqlite'?"strftime('%H:%M', scheduled_start_at)":"DATE_FORMAT(scheduled_start_at, '%H:%i')";
        $startTimes=HourlyVisit::whereNotNull('scheduled_start_at')->selectRaw($timeExpression.' as time')->distinct()->orderBy('time')->pluck('time')->all();
        // Half-hour choices plus existing exceptional times so older bookings remain searchable.
        for($minute=0;$minute<1440;$minute+=30)$startTimes[]=sprintf('%02d:%02d',intdiv($minute,60),$minute%60);
        $startTimes=array_unique($startTimes);sort($startTimes);
        return view('admin.hourly_visits.index',[
            'visits'=>$visits,'filters'=>$filters,'mode'=>$mode,'total'=>$total,'assigned'=>$assigned,'allocations'=>$allocations,'startTimes'=>$startTimes,
            'customerSources'=>DB::table('hourly_customer_sources')->orderBy('name')->pluck('name','code')->all(),
            'teams'=>HourlyTeam::orderBy('name')->get(['id','name']), 'drivers'=>HourlyDriver::where('is_active',true)->orderBy('name')->get(['id','name']),
            'workers'=>\App\Models\ingaz\Maid::where('rental_mode','hourly')->orderBy('name')->get(['id','name']),
            'admins'=>\App\Models\auth\Admin::orderBy('name')->get(['id','name']),
            'cities'=>\App\Models\City::saudi()->active()->get(),'services'=>HourlyServiceCategory::ordered()->get(),
            'attributes'=>HourlyProductAttribute::whereNotNull('parent_id')->orderBy('name_ar')->get(['id','name_ar','name_en']),
            'canEdit'=>AdminGuardHasPerm('hourly_visits_edit'),'canCollect'=>AdminGuardHasPerm('hourly_visits_collect'),'canPrice'=>AdminGuardHasPerm('hourly_visits_price_override'),
        ]);
    }

    public function workers(HourlyVisit $visit,\App\Services\Hourly\OrderManagementService $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_visits_view') && AdminGuardHasPerm('hourly_visits_edit'),403);
        $workers=$service->availableWorkers($visit->order,$visit);
        return response()->json(['required'=>(int)$visit->workers_count,'selected'=>$visit->workers()->pluck('maids.id'),
            'starts_at'=>$visit->scheduled_start_at?->format('Y-m-d H:i'),'ends_at'=>$visit->scheduled_end_at?->format('H:i'),
            'workers'=>$workers->map(fn($w)=>['id'=>$w->id,'name'=>$w->name])->values(),
            'excluded_workers'=>app(\App\Services\Hourly\HourlyWorkerAssignment::class)->excludedWorkers($visit,$workers)]);
    }

    private function visitData(HourlyVisit $visit): array
    {
        $visit->load(['workers','deliveryDriver','pickupDriver','teamLeader','order.user','order.payments','order.visits','order.serviceCategory']);
        $order=$visit->order;
        $allocation=app(\App\Services\Hourly\VisitPaymentAllocation::class)->forOrder($order)[$visit->id];
        $collectors=\App\Models\auth\Admin::whereIn('id',$order->payments->where('collected_by_type','admin')->pluck('collected_by_id'))->pluck('name','id');
        $driverCollectors=HourlyDriver::withTrashed()->whereIn('id',$order->payments->where('collected_by_type','driver')->pluck('collected_by_id'))->pluck('name','id');
        $workerCollectors=\App\Models\ingaz\Maid::whereIn('id',$order->payments->where('collected_by_type','maid')->pluck('collected_by_id'))->pluck('name','id');
        return compact('visit','order','allocation','collectors','driverCollectors','workerCollectors');
    }

    public function payments(HourlyVisit $visit)
    {
        abort_unless(AdminGuardHasPerm('hourly_visits_view'),403);
        return view('admin.hourly_visits._payments',$this->visitData($visit));
    }

    public function show(HourlyVisit $visit)
    {
        $data=$this->visitData($visit);
        $data['logs']=DB::table('hourly_order_audits as a')->leftJoin('admins','admins.id','=','a.admin_id')->where('a.visit_id',$visit->id)->select('a.*','admins.name as admin_name')->orderByDesc('a.id')->paginate(30);
        $data['notes']=collect($visit->order->booking_metadata['notes']??[])->filter(fn($note)=>(int)($note['visit_id']??0)===$visit->id);
        return view('admin.hourly_visits.show',$data);
    }

    public function invoice(HourlyVisit $visit)
    {
        return view('admin.hourly_visits.invoice',$this->visitData($visit));
    }

    public function batch(Request $request,VisitBatchService $service)
    {
        $data=$request->validate($service::rules());
        abort_unless(AdminGuardHasPerm('hourly_visits_view'),403);
        abort_unless(AdminGuardHasPerm($data['action']==='collect'?'hourly_visits_collect':'hourly_visits_edit'),403);
        if(in_array($data['action'],['price','duration']))abort_unless(AdminGuardHasPerm('hourly_visits_price_override'),403);
        if($data['action']==='cancel_visit' && $data['refund_method']!=='none')abort_unless(AdminGuardHasPerm('hourly_visits_collect'),403);
        if($data['action']==='duration' && abs($data['hours']*4-round($data['hours']*4))>.00001)\App\Services\Hourly\HourlyOrderService::reject('المدة بمضاعفات 15 دقيقة.');
        if($request->hasFile('attachment')){
            // Stable path makes duplicate submissions use the same idempotency hash.
            $file=$request->file('attachment');
            $data['attachment_path']=$file->storeAs('hourly-visit-batches/'.$data['batch_id'],hash_file('sha256',$file->getRealPath()).'.'.$file->extension(),'local');
        }
        unset($data['attachment']);
        $result=$service->run($data,auth('admin')->id());
        return response()->json($result);
    }

    public function receipt(int $receipt)
    {
        $receipt=DB::table('hourly_visit_receipts')->find($receipt);abort_unless($receipt,404);
        $user=\App\Models\ingaz\User::findOrFail($receipt->user_id);
        $notices=DB::table('hourly_visit_notices')->where('batch_id',$receipt->batch_id)->where('user_id',$receipt->user_id)->get();
        return view('admin.hourly_visits.receipt',compact('receipt','user','notices'));
    }
}
