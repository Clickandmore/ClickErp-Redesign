<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyVisit,HourlyPackage,HourlyRentalOrder};
use App\Services\Hourly\{HourlyWorkerAssignment,OrderManagementService,HourlyOrderService};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkerVisitOffersController extends Controller
{
    private function worker(Request $request)
    {
        $account=auth($request->is('api/*')?'hourly_maid_api':'hourly_maid')->user();
        abort_unless($account,401);
        return \App\Models\ingaz\Maid::findOrFail($account->id);
    }
    private function eligible(HourlyVisit $visit,$worker): bool
    {
        if(!$visit->offered_to_workers || !$visit->scheduled_start_at || !$visit->scheduled_start_at->isFuture() || !in_array($visit->order->status,['active','pending_confirmation']) || !in_array($visit->status,['scheduled','unscheduled']) || $visit->workers()->count()>=$visit->workers_count)return false;
        $service=app(HourlyWorkerAssignment::class);$order=$visit->order;
        $item=HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        return $service->candidates($item,$visit->hourly_team_id,(bool)($order->package_snapshot['assign_by_product_attribute']??false),(bool)($order->booking_metadata['all_workers']??false))->whereKey($worker->id)->exists()
            && $service->available($worker,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id);
    }
    public function index(Request $request)
    {
        $worker=$this->worker($request);
        // Eligibility cannot be expressed as a single query because it also checks the
        // worker schedule, leaves, hour caps and visit conflicts. Filter the complete
        // offer set before paginating; paginating first can produce an empty page even
        // when this worker has an eligible offer on a later global page.
        $eligible=HourlyVisit::where('offered_to_workers',true)->where('scheduled_start_at','>',now())
            ->whereIn('status',['scheduled','unscheduled'])->with('order.serviceCategory')
            ->orderBy('scheduled_start_at')->orderBy('id')->get()
            ->filter(fn($visit)=>$this->eligible($visit,$worker))->values();
        $perPage=25;$page=max(1,(int)$request->input('page',1));
        $visits=new LengthAwarePaginator($eligible->forPage($page,$perPage)->values(),$eligible->count(),$perPage,$page,[
            'path'=>$request->url(),'query'=>$request->query(),
        ]);
        if($request->is('api/*'))return response()->json(['data'=>$visits->getCollection()->map(fn($v)=>['id'=>$v->id,'start'=>$v->scheduled_start_at->toIso8601String(),'end'=>$v->scheduled_end_at->toIso8601String(),'service'=>$v->order->serviceCategory?->name,'district'=>$v->district,'hours'=>$v->planned_hours]),'next_page_url'=>$visits->nextPageUrl()]);
        return view('hourly_maid.offers',compact('visits'));
    }
    public function claim(Request $request,int $visit)
    {
        $worker=$this->worker($request);
        DB::transaction(function()use($worker,$visit){
            $orderId=HourlyVisit::findOrFail($visit)->hourly_rental_order_id;
            $order=HourlyRentalOrder::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $model=HourlyVisit::whereKey($visit)->lockForUpdate()->firstOrFail();$model->setRelation('order',$order);
            if(!$this->eligible($model,$worker))HourlyOrderService::reject('الزيارة لم تعد متاحة لك. حدّث القائمة.');
            app(OrderManagementService::class)->apply($order,['action'=>'add_worker','visit_ids'=>[$visit],'worker_id'=>$worker->id,'additional_fee'=>0,'expected_workers'=>$model->workers_count,'reason'=>'قبول الزيارة من العاملة #'.$worker->id],null);
            if($model->workers()->count()>=$model->workers_count)$model->update(['offered_to_workers'=>false]);
        });
        return $request->is('api/*')?response()->json(['message'=>'تم تعيينك للزيارة.']):back()->with('success','تم تعيينك للزيارة.');
    }
}
