<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyOrderPayment};
use App\Models\ingaz\{User, UserAddress};
use App\Models\City;
use App\Services\Hourly\{HourlyOrderService, HourlyPaymentService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }
    /** الزيارات المستهلكة: ما لا يعود إلى رصيد الطلب. مطابقة لحساب balance في الموديل. */
    public const CONSUMED_VISIT_STATUSES = ['completed', 'no_show', 'cancelled_by_client'];

    public function index(Request $request)
    {
        $search=app(\App\Services\Hourly\OrderSearch::class);
        $filters=$search->filters($request); $query=$search->query($filters);
        $consumed=self::CONSUMED_VISIT_STATUSES;
        $orders=(clone $query)->with(['user','serviceCategory'])
            ->withCount(['visits',
                'visits as visits_cancelled'=>fn ($q)=>$q->where('status','cancelled'),
                'visits as visits_available'=>fn ($q)=>$q->where('status','unscheduled'),
                'visits as visits_consumed'=>fn ($q)=>$q->whereIn('status',$consumed),
                'visits as visits_expired'=>fn ($q)=>$q->where('status','expired')])
            ->latest('id')->paginate(20)->withQueryString();
        $summary=$query->selectRaw("COUNT(*) AS orders_count, COALESCE(SUM(total_amount),0) AS total_amount, COALESCE(SUM(paid_amount),0) AS paid_amount,"
            ." COALESCE(SUM(status='pending_confirmation'),0) AS pending_count, COALESCE(SUM(status='active'),0) AS active_count, COALESCE(SUM(status='expired'),0) AS expired_count")->first();
        $recent = fn (bool $employee) => HourlyRentalOrder::query()
            ->when($employee,
                fn ($query) => $query->where('source', 'admin'),
                fn ($query) => $query->where('source', '!=', 'admin'))
            ->with(['user', 'city', 'serviceCategory', 'creator'])
            ->latest('id')
            ->limit(10)
            ->get();
        return view('admin.hourly.orders',[
            'orders'=>$orders,
            // تنبيه تشغيلي: زيارات اليوم المتوقفة على تعيين سائق، ومنها ما فات موعده.
            'driverGaps'=>app(\App\Services\Hourly\DriverGaps::class)->today(),
            'latestCustomerOrders'=>$recent(false),
            'latestEmployeeOrders'=>$recent(true),
            'cities'=>City::saudi()->active()->get(),
            'admins'=>\App\Models\auth\Admin::all(),
            'categories'=>\App\Models\Hourly\HourlyServiceCategory::ordered()->get(),
            'exportColumns'=>\App\Services\Hourly\OrderSearch::COLUMNS,
            'summary'=>$summary,
            'filters'=>$filters,
        ]);
    }
    public function create(Request $request)
    {
        $users=User::query()->when($request->filled('search'),fn ($q)=>$q->where(function ($q) use ($request) { $q->where('name','like','%'.$request->search.'%')->orWhere('phone','like','%'.$request->search.'%'); }))->limit(50)->get();
        $addresses=$request->filled('user_id') ? UserAddress::where('user_id',$request->user_id)->get() : collect();
        return view('admin.hourly.order-form',['cities'=>City::where('is_active',true)->get(), 'users'=>$users,'addresses'=>$addresses,'packages'=>HourlyPackage::where('is_active',true)->get()]);
    }
    public static function rules(): array
    {
        return ['selected_maid_ids'=>['sometimes','array','max:20'], 'selected_maid_ids.*'=>['required','integer','distinct','exists:maids,id']]
            + \App\Services\Hourly\HourlyBookingOptions::rules() + ['scheduled_dates'=>['nullable','array','max:365'], 'scheduled_dates.*'=>['required','date_format:Y-m-d','distinct'], 'attribute_id'=>['nullable','integer'],'quantity'=>['nullable','integer','between:1,50'],'preferred_maid_id'=>['nullable','integer','exists:maids,id'],'payment_channel'=>['nullable','in:employee,branch'],'scheduled_starts'=>['nullable','array','max:365'],'scheduled_starts.*'=>['required','date_format:Y-m-d H:i','distinct'], 'hourly_package_id'=>['required','integer','exists:hourly_packages,id'],'address_id'=>['required','integer'],'district_id'=>['nullable','integer'],
            'workers_per_visit'=>['nullable','integer','min:1','max:20'],'payment_method'=>['required','in:cash,card_on_delivery,bank_transfer,moyasar,wallet'], 'notes'=>['nullable','string','max:5000']];
    }
    public function location(Request $request, int $address)
    {
        $data=$request->validate([
            'user_id'=>['required','integer','exists:users,id'], 'address'=>['required','string','max:500'],
            'latitude'=>['required','numeric','between:-90,90'], 'longitude'=>['required','numeric','between:-180,180'],
            'city_id'=>['required','integer','exists:cities,id'], 'district'=>['nullable','string','max:150'],
            'building'=>['nullable','string','max:255'], 'unit'=>['nullable','string','max:255'], 'contact_phone'=>['nullable','string','max:30'],
        ]);
        $model=UserAddress::where('user_id',$data['user_id'])->findOrFail($address);
        $model->update(collect($data)->except('user_id')->all());
        return redirect()->route('admin.hourly-orders.create',['user_id'=>$data['user_id']])->with('success','تم حفظ موقع العنوان.');
    }
    public function store(Request $request, HourlyOrderService $service)
    {
        $data=$request->validate(self::rules()+['user_id'=>['required','integer','exists:users,id']]);
        $order=$service->create($data['user_id'],$data,auth('admin')->id());
        return redirect()->route('admin.hourly-orders.summary',$order)->with('success','تم إنشاء الطلب ورصيد زياراته.');
    }
    public function show(HourlyRentalOrder $order) { return $this->orderView($order,'admin.hourly.order-manage'); }
    public function summary(HourlyRentalOrder $order) { return $this->orderView($order,'admin.hourly.order'); }
    private function orderView(HourlyRentalOrder $order, string $view) {
        $data=['order'=>$order->load(['user','serviceCategory','visits.workers','visits.deliveryDriver','visits.pickupDriver','visits.teamLeader','payments','statusLogs']), 'creator'=>\App\Models\auth\Admin::find($order->created_by_admin_id)];
        if($view==='admin.hourly.order-manage'){
            $data['logAdmins']=\App\Models\auth\Admin::whereIn('id',$order->statusLogs->where('actor_type','admin')->pluck('actor_id')->filter()->unique())->get()->pluck('name','id');
            $data['paymentMethods']=app(\App\Services\Hourly\HourlyBookingRules::class)
                ->paymentMethods($order->package_snapshot ?? [], 'employee');
            $data['visitAllocations']=app(\App\Services\Hourly\VisitPaymentAllocation::class)->forOrder($order);
            $pricedVisits=$order->visits->filter(fn($v)=>!$v->is_free && (float)$v->visit_price>0 && !in_array($v->status,['cancelled','cancelled_by_client','expired']));
            $data['averageVisitPrice']=$pricedVisits->avg('visit_price')??0;
            $data['audits']=\Illuminate\Support\Facades\DB::table('hourly_order_audits')->where('hourly_rental_order_id',$order->id)->orderByDesc('id')->get();
            $data['reviews']=\Illuminate\Support\Facades\DB::table('hourly_worker_reviews as r')->leftJoin('maids as m','m.id','=','r.maid_id')->where('r.hourly_rental_order_id',$order->id)->select('r.*','m.name as worker_name')->orderByDesc('r.id')->get();
            $data['workers']=\App\Models\ingaz\Maid::where('rental_mode','hourly')->orderBy('name')->get(['id','name']);
            $data['drivers']=\App\Models\Hourly\HourlyDriver::where('is_active',true)->orderBy('name')->get(['id','name']);
        }
        return view($view,$data);
    }
    public function reschedule(Request $request, HourlyRentalOrder $order, int $visit, HourlyOrderService $service)
    {
        $data=$request->validate(['starts_at'=>'required|date_format:Y-m-d\\TH:i','expected_start_at'=>'nullable|date_format:Y-m-d\\TH:i']);
        $order->visits()->findOrFail($visit);
        app(\App\Services\Hourly\OrderManagementService::class)->apply($order,
            ['action'=>'reschedule','visit_ids'=>[$visit],'starts_at'=>$data['starts_at'],'expected_start_at'=>$data['expected_start_at']??null,'reason'=>'تعديل موعد الزيارة'],auth('admin')->id());
        return $request->expectsJson()?response()->json(['message'=>__('visit_schedule.saved')]):back()->with('success',__('visit_schedule.saved'));
    }
    public function export(Request $request, \App\Services\Hourly\OrderSearch $search)
    {
        $data=$request->validate(['columns'=>'required|array|min:1','columns.*'=>['required',Rule::in(array_keys($search::COLUMNS))]]);
        $columns=array_values(array_unique($data['columns']));$query=$search->query($search->filters($request));
        abort_if((clone $query)->count()>10000,422,'حدد نطاقًا لا يتجاوز 10000 طلب للتصدير.');
        $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setRightToLeft(true);
        $sheet->fromArray(array_map(fn($c)=>$search::COLUMNS[$c],$columns),null,'A1');
        $row=2;
        foreach($query->with(['user','city','serviceCategory'])->orderBy('id')->lazy(200) as $order){foreach($columns as $i=>$key)$sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).$row,(string)$search->cell($order,$key),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$row++;}
        foreach(range(1,count($columns)) as $i)$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        $sheet->freezePane('A2');
        return response()->streamDownload(function()use($book){(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');$book->disconnectWorksheets();},'hourly-orders.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
    public function note(Request $request, HourlyRentalOrder $order)
    {
        if(is_string($request->input('note')))$request->merge(['note'=>trim($request->input('note'))]);
        $data=$request->validate(['note'=>'required|string|max:5000','visit_id'=>'nullable|integer',
            'audience'=>['nullable',Rule::in(\App\Services\Hourly\OperationalNotes::AUDIENCES)],'note_token'=>'nullable|uuid']);
        $data['audience']=$data['audience'] ?? 'admin';
        if($data['audience']==='address' && !empty($data['visit_id']))throw \Illuminate\Validation\ValidationException::withMessages(['visit_id'=>__('operational_notes.address_all')]);
        if (!empty($data['visit_id'])) $order->visits()->findOrFail($data['visit_id']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($order,$data) {
            $locked=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $metadata=$locked->booking_metadata ?? [];
            if(!empty($data['note_token']) && collect($metadata['notes'] ?? [])->contains('id',$data['note_token']))return;
            $metadata['notes'][]=['text'=>$data['note'],'visit_id'=>$data['visit_id'] ?? null,
                'id'=>$data['note_token'] ?? (string)\Illuminate\Support\Str::uuid(),'audience'=>$data['audience'],
                'author'=>auth('admin')->user()->name,'admin_id'=>auth('admin')->id(),'created_at'=>now()->toDateTimeString()];
            $locked->update(['booking_metadata'=>$metadata]);
            app(\App\Services\Hourly\OrderManagementService::class)->audit($locked,'note',[],['note'=>$data['note'],'audience'=>$data['audience']],auth('admin')->id(),$data['visit_id']??null,'إضافة ملاحظة تشغيلية');
        });
        return $request->expectsJson()?response()->json(['message'=>__('operational_notes.saved')]):back()->with('success',__('operational_notes.saved'));
    }
    public function confirm(HourlyRentalOrder $order, HourlyOrderService $service) { $service->confirm($order,auth('admin')->id()); return back()->with('success','تم تأكيد الطلب.'); }
    public function closeUnpaid(Request $request, HourlyRentalOrder $order)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_close_unpaid'),403);
        $data=$request->validate(['reason'=>['required','string','max:1000'],'confirmed'=>['required','accepted']]);
        app(\App\Services\Hourly\HourlyOrderService::class)->closeUnpaid($order,(int)auth('admin')->id(),$data['reason']);
        return back()->with('success','تم إغلاق الطلب مع بقاء المتبقي مسجّلًا عليه.');
    }

    public function collect(Request $request, HourlyRentalOrder $order, HourlyPaymentService $service)
    {
        $data=$request->validate(['amount'=>['required','numeric','min:0.01'], 'method'=>['nullable','in:cash,card_on_delivery'], 'attachment'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120']]);
        if ($request->hasFile('attachment')) $data['attachment_path']=$request->file('attachment')->store('hourly/payments','local');
        try { $payment=$service->collect($order,$data,auth('admin')->id()); }
        catch (\Throwable $e) { if (!empty($data['attachment_path'])) Storage::disk('local')->delete($data['attachment_path']); throw $e; }
        return back()->with('success','تم تسجيل التحصيل وإنشاء سند القبض.')
            ->with('receipt_url',route('admin.hourly-orders.receipt',[$order,$payment]));
    }
    public function reviewBankTransfer(Request $request, HourlyRentalOrder $order, HourlyOrderPayment $payment, HourlyPaymentService $service)
    {
        abort_unless((int)$payment->hourly_rental_order_id===(int)$order->id,404);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['accept','reject'])],
            'rejection_reason'=>['nullable','required_if:decision,reject','string','max:1000'],
        ]);
        $service->reviewBankTransfer($order,$payment,$data['decision'],$data['rejection_reason']??null,auth('admin')->id());
        $message=$data['decision']==='accept'?'تم قبول التحويل البنكي وتسجيل السداد.':'تم رفض التحويل البنكي وإشعار العميل بالسبب.';
        return back()->with('success',$message);
    }
    public function attachment(HourlyRentalOrder $order, HourlyOrderPayment $payment)
    {
        abort_unless($payment->hourly_rental_order_id === $order->id && $payment->attachment_path,404);
        abort_unless(Storage::disk('local')->exists($payment->attachment_path),404);
        if($payment->method!=='bank_transfer') return Storage::disk('local')->download($payment->attachment_path);
        $path=Storage::disk('local')->path($payment->attachment_path);
        return response()->file($path,['Content-Type'=>mime_content_type($path)?:'application/octet-stream']);
    }
    public function receipt(HourlyRentalOrder $order, HourlyOrderPayment $payment)
    {
        abort_unless($payment->hourly_rental_order_id === $order->id && $payment->status === 'paid' && (float) $payment->amount > 0, 404);
        $collector = match ($payment->collected_by_type) {
            'admin' => \App\Models\auth\Admin::find($payment->collected_by_id)?->name,
            'maid' => \App\Models\ingaz\Maid::find($payment->collected_by_id)?->name,
            'driver' => \App\Models\Hourly\HourlyDriver::withTrashed()->find($payment->collected_by_id)?->name,
            default => null,
        };

        return self::receiptView($order, $payment, $collector);
    }

    /** سند القبض للطلب، يُستخدم أيضًا لسند التحصيل النقدي في شاشة العهد وتطبيق الطاقم. */
    public static function receiptView(HourlyRentalOrder $order, HourlyOrderPayment $payment, ?string $collector)
    {
        $logo = null;
        if ($path = \Illuminate\Support\Facades\View::shared('printSettings')?->logo) {
            $storage = app(\App\Services\ManagedFileStorage::class);
            if ($storage->exists($path)) $logo = 'data:'.$storage->mimeType($path).';base64,'.base64_encode($storage->contents($path));
        }
        // موقف حساب الطلب لحظة إصدار السند: ما سُدِّد قبله وبه وما تبقّى بعده.
        $paidThrough = (float) $order->payments()->where('status', 'paid')->where('id', '<=', $payment->id)->sum('amount');

        return view('admin.hourly.payment-receipt', [
            'order' => $order->loadMissing(['user', 'serviceCategory']),
            'payment' => $payment,
            'collector' => $collector,
            'receiptLogo' => $logo,
            'paidBefore' => $paidThrough - (float) $payment->amount,
            'paidThrough' => $paidThrough,
        ]);
    }
    public function invoice(HourlyRentalOrder $order)
    {
        $printSettings=\Illuminate\Support\Facades\View::shared('printSettings');
        $logo=null;
        if ($path=$printSettings?->logo) {
            $storage=app(\App\Services\ManagedFileStorage::class);
            if ($storage->exists($path)) $logo='data:'.$storage->mimeType($path).';base64,'.base64_encode($storage->contents($path));
        }
        $pdf=new \Mpdf\Mpdf(array_replace(\App\Services\Hourly\HourlyPdf::mpdfConfig(), ['format'=>'A4','margin_left'=>12,'margin_right'=>12,'margin_top'=>12,'margin_bottom'=>12]));
        $pdf->SetTitle('فاتورة '.$order->invoice_number);
        $pdf->WriteHTML(view('admin.hourly.invoice',['order'=>$order->load(['user','serviceCategory','visits']),'invoiceLogo'=>$logo])->render());
        return response($pdf->Output('',\Mpdf\Output\Destination::STRING_RETURN),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$order->invoice_number.'.pdf"']);
    }
}
