<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\OrderManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderManagementController extends Controller
{
    public function drivers(Request $request,HourlyRentalOrder $order,OrderManagementService $service){
        $data=$request->validate(['visit_ids'=>'required|array|min:1|max:365','visit_ids.*'=>'required|integer|distinct',
            'driver_role'=>'required|in:delivery,pickup','driver_time'=>'required|date_format:H:i']);
        $visits=$order->visits()->whereIn('id',$data['visit_ids'])->get();
        abort_unless($visits->count()===count($data['visit_ids']),404);
        abort_unless(in_array($order->status,['active','pending_confirmation']) && $visits->every(fn($visit)=>!in_array($visit->status,['completed','cancelled','expired','no_show','cancelled_by_client'])),422);
        return response()->json(['drivers'=>$service->availableDrivers($visits,$data['driver_role'],$data['driver_time'])->map(fn($driver)=>['id'=>$driver->id,'name'=>$driver->name])]);
    }
    public function leaders(Request $request,HourlyRentalOrder $order,int $visit){
        $data=$request->validate(['all_teams'=>'sometimes|boolean','search'=>'nullable|string|max:150','per_page'=>'nullable|integer|in:10,25,50','page'=>'nullable|integer|min:1','sort'=>'nullable|in:id,name','direction'=>'nullable|in:asc,desc']);
        $model=$order->visits()->findOrFail($visit);
        abort_unless(in_array($order->status,['active','pending_confirmation']) && !in_array($model->status,['completed','cancelled','expired','no_show','cancelled_by_client']),422);
        $query=app(\App\Services\Hourly\TeamLeaderChoices::class)->query($model->hourly_team_id,(bool)($data['all_teams']??false));
        if(!empty($data['search']))$query->where('name','like','%'.$data['search'].'%');
        $result=$query->orderBy($data['sort']??'name',$data['direction']??'asc')->orderBy('id')->paginate($data['per_page']??10,['id','name']);
        return response()->json(['leaders'=>$result,'selected_id'=>$model->team_leader_id]);
    }
    public function workers(Request $request,HourlyRentalOrder $order,int $visit){
        abort_unless(AdminGuardHasPerm($request->boolean('selection')?'hourly_orders_create':'hourly_orders_price_override'),403);
        $model=$order->visits()->findOrFail($visit);
        abort_unless(in_array($order->status,['active','pending_confirmation']) && !in_array($model->status,['completed','cancelled','expired','no_show','cancelled_by_client']),422);
        if($request->boolean('selection')){
            abort_unless($model->scheduled_start_at && $model->scheduled_start_at->isFuture(),422);
            $workers=app(OrderManagementService::class)->availableWorkers($order,$model);
            return response()->json(['required'=>(int)$model->workers_count,'selected'=>$model->workers()->pluck('maids.id'),
                'workers'=>$workers->map(fn($worker)=>['id'=>$worker->id,'name'=>$worker->name])->values(),
                'excluded_workers'=>app(\App\Services\Hourly\HourlyWorkerAssignment::class)->excludedWorkers($model,$workers),
                'starts_at'=>$model->scheduled_start_at->format('Y-m-d H:i'),'ends_at'=>$model->scheduled_end_at?->format('H:i')]);
        }
        $item=\App\Models\Hourly\HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
        $assignment=app(\App\Services\Hourly\HourlyWorkerAssignment::class);
        $assigned=$model->workers()->pluck('maids.id')->all();
        $workers=collect();
        if($model->scheduled_start_at && $model->scheduled_start_at->isFuture()){
            $workers=$assignment->candidates($item,$model->hourly_team_id,(bool)($order->package_snapshot['assign_by_product_attribute']??false),(bool)($order->booking_metadata['all_workers']??false))
                ->whereNotIn('maids.id',$assigned)->with('hourlyTeamMemberships.team')->orderBy('maids.name')->get()
                ->pipe(fn($maids)=>app(\App\Services\Hourly\CustomerDirectory::class)->favoritesFirst($maids,(int)$order->user_id))
                ->filter(fn($m)=>$assignment->available($m,$model->scheduled_start_at->toDateTimeString(),$model->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$model->id))
                ->map(fn($m)=>['id'=>$m->id,'name'=>$m->name,'category_ids'=>$m->hourly_settings['service_category_ids']??null,
                    'teams'=>$m->hourlyTeamMemberships->filter(fn($membership)=>$membership->is_active && $membership->team?->is_active)->map(fn($membership)=>['id'=>$membership->team->id,'name'=>$membership->team->name])->values()])->values();
        }
        return response()->json(['workers'=>$workers,'excluded_workers'=>$assignment->excludedWorkers($model,$workers,$assigned,true),'price'=>(float)$model->visit_price,'workers_count'=>(int)$model->workers_count,
            'default_fee'=>count($assigned)<$model->workers_count?0:round($model->visit_price/max(1,$model->workers_count),2),
            'date'=>$model->scheduled_start_at?->format('H:i d/m/Y'),'team_id'=>$model->hourly_team_id,'category_id'=>$order->service_category_id,
            'categories'=>\App\Models\Hourly\HourlyServiceCategory::where('is_active',true)->get()->map(fn($c)=>['id'=>$c->id,'name'=>$c->name])]);
    }
    public function update(Request $request,HourlyRentalOrder $order,OrderManagementService $service){
        $data=$request->validate([
            'action'=>['required',Rule::in(OrderManagementService::ACTIONS)],'reason'=>'required_if:action,refund|nullable|string|max:2000',
            'refund_method'=>'required_if:action,refund|nullable|in:wallet,cash,bank_transfer,none',
            'external_refund_confirmed'=>'sometimes|boolean',
            'visit_ids'=>'required_unless:action,cancel_order,payment_method,free_visit,refund|array|min:1|max:365','visit_ids.*'=>'integer|distinct',
            'price'=>'required_if:action,price|nullable|numeric|min:0|max:9999999',
            'hours'=>'required_if:action,duration|nullable|numeric|min:0.5|max:24',
            'duration_price_adjustment'=>'nullable|numeric|between:-9999999,9999999',
            'expected_price'=>'nullable|numeric|min:0','expected_hours'=>'nullable|numeric|min:0.5',
            'ignore_hour_caps'=>'sometimes|boolean','ignore_break'=>'sometimes|boolean',
            // إعادة الجدولة: موعد من الأوقات المتاحة (زيارة فائتة واحدة) أو تاريخ جديد أو إزاحة بالأيام.
            'shift_days'=>'exclude_unless:action,reschedule|required_without_all:new_date,starts_at|nullable|integer|between:-365,365',
            'new_date'=>'exclude_unless:action,reschedule|nullable|date_format:Y-m-d',
            'start_time'=>'required_if:action,start_time|nullable|date_format:H:i',
            'starts_at'=>'required_if:action,free_visit|nullable|date_format:Y-m-d\\TH:i',
            'allow_unassigned'=>'sometimes|boolean','ignore_notice'=>'sometimes|boolean',
            'worker_id'=>'required_if:action,add_worker,remove_worker,unassign_worker|nullable|integer',
            'worker_ids'=>'required_if:action,assign_workers|array|min:1|max:20','worker_ids.*'=>'integer|distinct',
            'expected_worker_ids'=>'sometimes|array|max:20','expected_worker_ids.*'=>'integer|distinct',
            'additional_fee'=>'nullable|numeric|min:0|max:9999999','expected_workers'=>'nullable|integer|min:1|max:20',
            'driver_id'=>'required_if:action,assign_driver|nullable|integer',
            'driver_role'=>'required_if:action,assign_driver,unassign_driver|nullable|in:delivery,pickup',
            'driver_time'=>'required_if:action,assign_driver|nullable|date_format:H:i',
            'driver_direction'=>'required_if:action,auto_driver|nullable|in:delivery,pickup,together',
            'offered'=>'required_if:action,offer|boolean',
            'leader_id'=>'required_if:action,team_leader|nullable|integer',
            'all_teams'=>'sometimes|boolean',
            'payment_method'=>'required_if:action,payment_method|nullable|in:cash,card_on_delivery,cash_and_card,bank_transfer,cheque,customer_balance,moyasar',
        ]);
        if ($data['action']==='refund' || (in_array($data['action'],['cancel_visit','cancel_order'],true)
            && ($data['refund_method'] ?? 'wallet')!=='none' && (float)$order->paid_amount>0)) abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        $data['reason']??=null; // السبب اختياري في كل مودالات الطلب؛ يُسجَّل فارغًا في السجلات إن لم يُكتب
        if(in_array($data['action'],['price','duration','free_visit','add_worker','remove_worker']))abort_unless(AdminGuardHasPerm('hourly_orders_price_override'),403);
        if(!empty($data['ignore_notice']))abort_unless($data['action']==='free_visit' && AdminGuardHasPerm('hourly_orders_notice_override'),403);
        if($data['action']==='duration' && abs($data['hours']*4-round($data['hours']*4))>0.00001)throw \Illuminate\Validation\ValidationException::withMessages(['hours'=>'تُعدّل المدة بمضاعفات 15 دقيقة.']);
        $result=$service->apply($order,$data,auth('admin')->id());
        return $request->expectsJson()?response()->json(['message'=>'تم تنفيذ الإجراء وتحديث الطلب.']+$result):back()->with('success','تم تنفيذ الإجراء وتحديث الطلب.')->with('warning',$result['warning']??null);
    }
}
