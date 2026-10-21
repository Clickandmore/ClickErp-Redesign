<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyVisit,HourlyRentalOrder,HourlyOrderPayment,HourlyDriver};
use App\Models\ingaz\Maid;
use App\Services\Hourly\{CashCollectionService,OrderManagementService,HourlyOrderService,WorkerAppService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashCollectionController extends Controller
{
    private function cashInput(Request $request): array { return $request->validate(['amount'=>'required|numeric|min:0.01|max:100000','request_token'=>'required|uuid']); }
    public function workerCollect(Request $request,int $visit,CashCollectionService $cash) {
        $data=$this->cashInput($request);$maid=Maid::findOrFail(auth('hourly_maid_api')->id());
        return response()->json(['data'=>$cash->workerCollect($maid,$visit,(float)$data['amount'],$data['request_token'])]);
    }
    public function workerCustody(CashCollectionService $cash) { return response()->json(['data'=>$cash->custody('worker',(int)auth('hourly_maid_api')->id())]); }
    public function driverCustody(CashCollectionService $cash) { return response()->json(['data'=>$cash->custody('driver',(int)auth('hourly_driver_api')->id())]); }
    public function webCollect(Request $request,int $visit,CashCollectionService $cash) {
        $data=$this->cashInput($request);$result=$cash->workerCollect(Maid::findOrFail(auth('hourly_maid')->id()),$visit,(float)$data['amount'],$data['request_token']);
        return back()->with('success','تم تسجيل التحصيل وسند '.$result['receipt_number'].'؛ المبلغ في عهدتك حتى توريده للإدارة.');
    }
    public function webAction(Request $request,int $visit,WorkerAppService $workers) {
        $data=$request->validate(['action'=>'required|in:arrived,finish,return','early_reason'=>['nullable',\Illuminate\Validation\Rule::in(array_keys(\App\Services\Hourly\EarlyFinish::REASONS))],'note'=>'nullable|string|max:500']);$maid=Maid::findOrFail(auth('hourly_maid')->id());
        if($data['action']==='arrived')$workers->arrived($maid,$visit,[]);elseif($data['action']==='finish')$workers->finish($maid,$visit,$data['note']??null,[],$data['early_reason']??null);else $workers->returnToShelter($maid,$visit);
        return back()->with('success','تم تحديث حالة الزيارة.');
    }
    public function collector(Request $request,HourlyRentalOrder $order,HourlyVisit $visit) {
        abort_unless(AdminGuardHasPerm('hourly_orders_create') && AdminGuardHasPerm('hourly_orders_collect'),403);
        abort_unless(CashCollectionService::ready(),503);
        $data=$request->validate(['type'=>'required|in:auto,driver,worker,admin','worker_id'=>'nullable|integer','reason'=>'required|string|max:500']);
        DB::transaction(function()use($order,$visit,$data){
            $order=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $visit=HourlyVisit::whereKey($visit->id)->where('hourly_rental_order_id',$order->id)->lockForUpdate()->firstOrFail();
            if(app(\App\Services\Hourly\PaymentScheduleService::class)->policy($order)==='prepaid')HourlyOrderService::reject('لا يلزم تحديد مسؤول تحصيل لباقة مدفوعة مقدمًا بالكامل.');
            if($visit->delivery_driver_id && $data['type']!=='driver')HourlyOrderService::reject('السائق المعيّن للتوصيل هو مسؤول التحصيل لهذه الزيارة.');
            if($order->status!=='active' || ($visit->status!=='scheduled' || $visit->has_started_execution))HourlyOrderService::reject('حدد مسؤول التحصيل قبل بدء الزيارة.');
            if(DB::table('hourly_cash_collections')->where('visit_id',$visit->id)->exists())HourlyOrderService::reject('لا يمكن تغيير المسؤول بعد تسجيل تحصيل.');
            $worker=$data['type']==='worker'?($data['worker_id'] ?? null):null;
            if($data['type']==='worker' && (!$worker || !$visit->workers()->where('maids.id',$worker)->exists()))HourlyOrderService::reject('اختر عاملة مسندة إلى الزيارة لتكون مسؤولة عن التحصيل.');
            $before=$visit->only(['cash_collector_type','cash_collector_worker_id']);
            $visit->update(['cash_collector_type'=>$data['type'],'cash_collector_worker_id'=>$worker]);
            app(OrderManagementService::class)->audit($order,'cash_collector',$before,$visit->only(['cash_collector_type','cash_collector_worker_id']),auth('admin')->id(),$visit->id,$data['reason']);
        });
        return back()->with('success','تم تحديد مسؤول التحصيل.');
    }
    public function index(Request $request) {
        abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        abort_unless(CashCollectionService::ready(),503,'يجب تشغيل ترحيل العهد النقدية أولًا.');
        $data=$request->validate(['holder_type'=>'nullable|in:worker,driver','holder_id'=>'nullable|integer|min:1','all'=>'nullable|boolean']);
        $query=DB::table('hourly_cash_collections')->whereIn('order_id',HourlyRentalOrder::select('id'));
        if(!empty($data['holder_type']))$query->where('holder_type',$data['holder_type']);
        if(!empty($data['holder_id']))$query->where('holder_id',$data['holder_id']);
        $totals=(clone $query)->selectRaw('COALESCE(SUM(amount),0) as collected, COALESCE(SUM(remitted_amount),0) as remitted, COALESCE(SUM(amount-remitted_amount),0) as due, COALESCE(SUM(remitted_amount<amount),0) as open_count')->first();
        $outstanding=$totals->due;
        // أعلى أرصدة النقد لدى المحصّلين: تُعرض جانبًا لتوجيه المتابعة والتوريد.
        $holders=(clone $query)->whereColumn('remitted_amount','<','amount')->selectRaw('holder_type, holder_id, SUM(amount-remitted_amount) as due, COUNT(*) as entries')->groupBy('holder_type','holder_id')->orderByDesc('due')->limit(8)->get();
        if(empty($data['all']))$query->whereColumn('remitted_amount','<','amount');
        $entries=$query->orderByDesc('id')->paginate(25)->withQueryString();
        $people=$entries->getCollection()->concat($holders);
        $workers=Maid::whereIn('id',$people->where('holder_type','worker')->pluck('holder_id'))->pluck('name','id');
        $drivers=HourlyDriver::withTrashed()->whereIn('id',$people->where('holder_type','driver')->pluck('holder_id'))->pluck('name','id');
        $remittances=DB::table('hourly_cash_remittances')->whereIn('collection_id',$entries->pluck('id'))->orderByDesc('id')->get()->groupBy('collection_id');
        $orderNumbers=HourlyRentalOrder::whereIn('id',$entries->pluck('order_id'))->pluck('order_number','id');
        $receivers=\App\Models\auth\Admin::whereIn('id',$remittances->flatten()->pluck('admin_id')->unique())->get()->pluck('name','id');
        return view('admin.hourly.cash-custody',compact('entries','workers','drivers','outstanding','remittances','totals','holders','orderNumbers','receivers'));
    }
    public function remit(Request $request,int $collection,CashCollectionService $cash) {
        abort_unless(CashCollectionService::ready(),503);
        abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        $data=$this->cashInput($request)+$request->validate(['reference'=>'required|string|max:255','confirmed'=>'required|accepted']);
        $row=DB::table('hourly_cash_collections')->where('id',$collection)->first();abort_unless($row,404);HourlyRentalOrder::findOrFail($row->order_id);
        $cash->remit($collection,(float)$data['amount'],(int)auth('admin')->id(),$data['reference'],$data['request_token']);
        return back()->with('success','تم تسجيل سند توريد العهدة دون إضافة دفعة أخرى على العميل.');
    }
    public function driverCollect(Request $request,int $visit,CashCollectionService $cash) {
        $data=$this->cashInput($request);
        return response()->json(['data'=>$cash->collect(HourlyVisit::findOrFail($visit),'driver',(int)auth('hourly_driver_api')->id(),(float)$data['amount'],$data['request_token'])]);
    }
    public function driverWebCollect(Request $request,int $visit,CashCollectionService $cash) {
        $data=$this->cashInput($request);
        $result=$cash->collect(HourlyVisit::findOrFail($visit),'driver',(int)auth('hourly_driver')->id(),(float)$data['amount'],$data['request_token']);
        return back()->with('success','تم تسجيل التحصيل وسند '.$result['receipt_number'].' وإضافته إلى عهدتك.');
    }
    public function remittanceReceipt(int $remittance) {
        abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);
        abort_unless(CashCollectionService::ready(),503);
        $remit=DB::table('hourly_cash_remittances')->where('id',$remittance)->first();abort_unless($remit,404);
        $entry=DB::table('hourly_cash_collections')->where('id',$remit->collection_id)->first();abort_unless($entry,404);
        $order=HourlyRentalOrder::findOrFail($entry->order_id);
        $holder=$entry->holder_type==='worker'?Maid::find($entry->holder_id)?->name:HourlyDriver::withTrashed()->find($entry->holder_id)?->name;
        $admin=DB::table('admins')->where('id',$remit->admin_id)->value('name');
        return view('admin.hourly.cash-remittance-receipt',compact('remit','entry','order','holder','admin'));
    }
    private function receipt(int $collection) {
        abort_unless(CashCollectionService::ready(),503);
        $row=DB::table('hourly_cash_collections')->where('id',$collection)->first();abort_unless($row,404);
        $payment=HourlyOrderPayment::findOrFail($row->payment_id);$order=HourlyRentalOrder::with(['user','serviceCategory'])->findOrFail($row->order_id);
        $collector=$row->holder_type==='worker'?Maid::find($row->holder_id)?->name:HourlyDriver::withTrashed()->find($row->holder_id)?->name;
        return OrderController::receiptView($order,$payment,$collector);
    }
    public function staffReceipt(int $collection) {
        abort_unless(CashCollectionService::ready(),503);
        $row=DB::table('hourly_cash_collections')->where('id',$collection)->first();abort_unless($row && $row->holder_type==='worker' && (int)$row->holder_id===(int)auth('hourly_maid')->id(),404);
        return $this->receipt($collection);
    }
    public function driverReceipt(int $collection) {
        abort_unless(CashCollectionService::ready(),503);
        $row=DB::table('hourly_cash_collections')->where('id',$collection)->first();abort_unless($row && $row->holder_type==='driver' && (int)$row->holder_id===(int)auth('hourly_driver')->id(),404);
        return $this->receipt($collection);
    }
    public function customerReceipt(int $collection) {
        abort_unless(CashCollectionService::ready(),503);
        $row=DB::table('hourly_cash_collections')->where('id',$collection)->first();abort_unless($row,404);
        $order=HourlyRentalOrder::whereKey($row->order_id)->where('user_id',auth('api')->id())->first();abort_unless($order,404);
        return $this->receipt($collection);
    }
    public function adminReceipt(int $collection) { abort_unless(AdminGuardHasPerm('hourly_orders_collect'),403);return $this->receipt($collection); }
}
