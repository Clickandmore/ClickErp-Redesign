<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use App\Services\Hourly\HourlyOrderService;
use Carbon\Carbon;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\Rule;

class DriverPortalController extends Controller
{
    private const CLOSED = ['completed', 'cancelled', 'cancelled_by_client', 'expired', 'no_show'];

    public function index(Request $request)
    {
        $driver = auth('hourly_driver')->user();
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $selectedDate = Carbon::createFromFormat('Y-m-d', $data['date'] ?? now()->toDateString())->startOfDay();
        $month = Carbon::createFromFormat('Y-m-d', ($data['month'] ?? $selectedDate->format('Y-m')).'-01')->startOfMonth();

        $base = HourlyVisit::query()
            ->where(fn ($query) => $query->where('delivery_driver_id', $driver->id)->orWhere('pickup_driver_id', $driver->id))
            ->where(fn($query)=>$query->whereHas('order', fn ($order) => $order->where('status', 'active'))
                ->orWhereIn('status',\App\Services\Hourly\DriverAppService::ONGOING));

        $visits = (clone $base)
            ->where(function($query)use($selectedDate){
                $query->whereDate('scheduled_start_at',$selectedDate);
                if($selectedDate->isToday()) $query->orWhere(fn($running)=>$running
                    ->whereIn('status',\App\Services\Hourly\DriverAppService::ONGOING)
                    ->where('scheduled_start_at','<',$selectedDate));
            })
            ->with(['order.user:id,name,phone', 'order.serviceCategory', 'workers:id,name'])
            ->orderBy('scheduled_start_at')->orderBy('id')->get();

        $monthCounts = (clone $base)
            ->whereBetween('scheduled_start_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('DATE(scheduled_start_at) as task_date, SUM(CASE WHEN delivery_driver_id = ? THEN 1 ELSE 0 END + CASE WHEN pickup_driver_id = ? THEN 1 ELSE 0 END) as total', [$driver->id, $driver->id])
            ->groupBy('task_date')->pluck('total', 'task_date');

        $tracking=\App\Support\HourlyRentalSettings::trackingConfig();
        $trackingVisit=(clone $base)->where(function($query)use($driver){
            $query->where(fn($q)=>$q->where('delivery_driver_id',$driver->id)->where('status','en_route'))
                ->orWhere(fn($q)=>$q->where('pickup_driver_id',$driver->id)->where('status','picked_up'));
        })->first();
        return view('hourly_driver.home', compact('driver', 'visits', 'selectedDate', 'month', 'monthCounts','tracking','trackingVisit'));
    }

    public function action(Request $request, HourlyVisit $visit): RedirectResponse
    {
        $driver = auth('hourly_driver')->user();
        $data = $request->validate([
            'action' => ['required', Rule::in(['start_delivery', 'confirm_delivery', 'confirm_pickup', 'complete'])],
            'delivery_code'=>['nullable','string','max:100'],
            'cash_amount'=>['nullable','numeric','min:0','max:100000'], 'request_token'=>['required_with:cash_amount','nullable','uuid'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        DB::transaction(function () use ($visit, $driver, $data) {
            $visit = HourlyVisit::whereKey($visit->id)->firstOrFail();
            $order = HourlyRentalOrder::whereKey($visit->hourly_rental_order_id)->lockForUpdate()->firstOrFail();
            $visit = HourlyVisit::whereKey($visit->id)->lockForUpdate()->firstOrFail();
            [$role, $from, $to, $label] = $this->transition($data['action']);

            if ($order->status !== 'active') {
                HourlyOrderService::reject('الطلب غير نشط ولا يقبل تحديثات من السائق.');
            }
            abort_unless((int) $visit->{$role.'_driver_id'} === (int) $driver->id, 403);
            if ($visit->status !== $from || in_array($visit->status, self::CLOSED, true)) {
                HourlyOrderService::reject('تغيّرت حالة المهمة أو تم تنفيذها بالفعل. حدّث الصفحة ثم حاول مرة أخرى.');
            }

            if ($data['action'] === 'start_delivery') \App\Services\Hourly\DeliveryStartWindow::assertAllowed($visit);
            if ($data['action'] === 'confirm_delivery') \App\Services\Hourly\DeliveryCode::assertValid($visit,$data['delivery_code']??null);
            if ($data['action']==='confirm_delivery' && (float)($data['cash_amount']??0)>0) {
                app(\App\Services\Hourly\CashCollectionService::class)->collect($visit,'driver',(int)$driver->id,(float)$data['cash_amount'],$data['request_token']);
            }
            $before = $visit->getAttributes();
            $visit->update(['status' => $to]);
            if($to==='delivered')\App\Services\Hourly\ServiceTime::recordDelivery($visit);
            if (Schema::hasTable('hourly_order_audits')) {
                DB::table('hourly_order_audits')->insert([
                    'hourly_rental_order_id' => $order->id,
                    'visit_id' => $visit->id,
                    'admin_id' => null,
                    'action' => 'driver_'.$data['action'],
                    'reason' => $label.' بواسطة السائق '.$driver->name.' (#'.$driver->id.')',
                    'before' => json_encode($before, JSON_UNESCAPED_UNICODE),
                    'after' => json_encode($visit->fresh()->getAttributes(), JSON_UNESCAPED_UNICODE),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            if ($to === 'completed' && !$order->visits()->whereNotIn('status', self::CLOSED)->exists()) {
                app(HourlyOrderService::class)->transition($order, 'completed', 'driver', $driver->id, 'إكمال آخر زيارة من بوابة السائق');
            }

            // نفس إشعارات تطبيق السائق: كان العميل يُشعَر إن استخدم السائق التطبيق
            // ولا يُشعَر إن استخدم بوابة الويب، مع أن الحدث واحد.
            $customerStage = ['en_route' => 'on_the_way', 'delivered' => 'arrived'][$to] ?? null;
            if ($customerStage) {
                $visitId = (int) $visit->id;
                DB::afterCommit(fn () => app(\App\Services\Hourly\CustomerTrackingPushService::class)->notify($visitId, $customerStage));
            }
        });

        return redirect()->route('hourly-driver.home', array_filter([
            'date' => $data['date'] ?? null,
            'month' => $data['month'] ?? null,
        ]))->with('success', 'تم تحديث حالة المهمة بنجاح.');
    }

    /** @return array{string,string,string,string} */
    private function transition(string $action): array
    {
        return match ($action) {
            'start_delivery' => ['delivery', 'scheduled', 'en_route', 'بدء مشوار التوصيل'],
            'confirm_delivery' => ['delivery', 'en_route', 'delivered', 'تأكيد التوصيل'],
            'confirm_pickup' => ['pickup', 'worker_finished', 'picked_up', 'تأكيد الاستلام'],
            'complete' => ['pickup', 'picked_up', 'completed', 'إنهاء المشوار'],
        };
    }
}
