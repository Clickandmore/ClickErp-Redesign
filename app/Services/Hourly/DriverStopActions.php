<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

/**
 * إجراءات السائق على محطة: وصلت · استلام من الإيواء · تسليم بالرمز · استلام من العميل · عودة للإيواء · تعذّر.
 *
 * الانتقالات هي نفسها الموجودة في بوابة السائق على الويب (DriverPortalController):
 * scheduled → en_route → delivered → worker_finished → picked_up → completed،
 * فلا يوجد مسار تنفيذ ثانٍ للزيارة. كل إجراء في معاملة واحدة، وإعادة إرساله لا تُكرّر أثره.
 */
class DriverStopActions
{
    private const EXECUTION = ['delivery' => ['shelter_pickup', 'drop_off'], 'pickup' => ['pick_up', 'shelter_return']];

    public function arrived(HourlyDriver $driver, int $stopId, array $gps): array
    {
        return $this->apply($driver, $stopId, null, function ($stop, $visit) {
            if ($stop->status === 'arrived') return [];
            return ['stop' => ['status' => 'arrived', 'arrived_at' => now()]];
        }, $gps, 'arrived');
    }

    public function shelterPickup(HourlyDriver $driver, int $stopId, array $gps): array
    {
        return $this->apply($driver, $stopId, 'shelter_pickup', function ($stop, $visit) use ($driver) {
            // خروج مؤقت يُسجَّل في سجل الحركة وحده؛ حالة الخروج التعاقدية في الإيواء لا تُمس.
            app(ShelterPresence::class)->open($visit, 'driver', $driver->id);
            return ['visit' => ['status' => 'en_route'], 'stop' => ['status' => 'arrived', 'arrived_at' => $stop->arrived_at ?? now()]];
        }, $gps, 'shelter_pickup');
    }

    public function deliver(HourlyDriver $driver, int $stopId, array $gps, ?string $code, ?float $cash): array
    {
        return $this->apply($driver, $stopId, 'drop_off', function ($stop, HourlyVisit $visit, HourlyRentalOrder $order) use ($driver, $code, $cash) {
            VisitConfirmation::assertDriver($visit, VisitConfirmation::ARRIVAL);
            DeliveryCode::assertValid($visit, $code);
            ServiceTime::recordDelivery($visit);
            $collected = $this->collect($order, $driver, $cash, $visit);
            // delivery_at هو الوقت المخطَّط من إدارة المسار؛ وقت التنفيذ الفعلي يُسجَّل على المحطة.
            return ['visit' => ['status' => 'delivered'],
                'stop' => ['status' => 'completed', 'completed_at' => now(), 'cash_collected' => $collected],
                'extra' => ['cash_collected' => $collected]];
        }, $gps, 'deliver');
    }

    public function pickUp(HourlyDriver $driver, int $stopId, array $gps): array
    {
        return $this->apply($driver, $stopId, 'pick_up', function ($stop, HourlyVisit $visit) {
            if ($visit->status !== 'worker_finished') HourlyOrderService::reject('لم تُعلن العاملة انتهاءها بعد؛ الاستلام غير متاح.');
            return ['visit' => ['status' => 'picked_up'],
                'stop' => ['status' => 'arrived', 'arrived_at' => $stop->arrived_at ?? now()]];
        }, $gps, 'pick_up');
    }

    public function shelterReturn(HourlyDriver $driver, int $stopId, array $gps): array
    {
        return $this->apply($driver, $stopId, 'shelter_return', function ($stop, HourlyVisit $visit) use ($driver) {
            VisitConfirmation::assertDriver($visit, VisitConfirmation::RETURN);
            DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->whereIn('status', ['finished', 'picked_up'])->update(['status' => 'returned', 'updated_at' => now()]);
            app(ShelterPresence::class)->close($visit, 'driver', $driver->id);
            return ['visit' => ['status' => 'completed'], 'stop' => ['status' => 'completed', 'completed_at' => now()], 'close_order' => true];
        }, $gps, 'shelter_return');
    }

    public function fail(HourlyDriver $driver, int $stopId, array $gps, string $reason): array
    {
        return $this->apply($driver, $stopId, null, function ($stop) use ($reason) {
            return ['stop' => ['status' => 'failed', 'fail_reason' => mb_substr($reason, 0, 200), 'completed_at' => now()]];
        }, $gps, 'fail');
    }

    /**
     * القالب المشترك: قفل المحطة والزيارة والطلب، التحقق من الملكية والدور والقرب،
     * ثم تطبيق التغييرات وكتابة سجل التدقيق، وإرجاع المشوار كاملًا كما يتوقعه التطبيق.
     */
    private function apply(HourlyDriver $driver, int $stopId, ?string $expected, callable $mutate, array $gps, string $action): array
    {
        HourlyOrderService::enabled();
        $board = app(DriverAppService::class);
        return DB::transaction(function () use ($driver, $stopId, $expected, $mutate, $gps, $action, $board) {
            $stop = DB::table('hourly_route_stops')->where('id', $stopId)->lockForUpdate()->first();
            abort_unless($stop && (int) $stop->hourly_driver_id === (int) $driver->id, 404, 'المحطة غير موجودة أو ليست من مهامك.');
            $visit = HourlyVisit::query()->findOrFail($stop->hourly_visit_id);
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($visit->hourly_rental_order_id);
            $visit = HourlyVisit::query()->lockForUpdate()->findOrFail($stop->hourly_visit_id);
            if ($order->status !== 'active') HourlyOrderService::reject('الطلب غير نشط ولا يقبل تحديثات من السائق.');
            if (in_array($visit->status, DriverAppService::VOID, true)) HourlyOrderService::reject('الزيارة ملغاة أو منتهية.');
            $this->assertTrip($stop);
            if ($stop->role === 'delivery' && $visit->status === 'scheduled' && $action !== 'fail') {
                DeliveryStartWindow::assertAllowed($visit);
            }
            $visit->loadMissing('workers:id,name');
            $task = ['role' => $stop->role, 'visit' => $visit, 'row' => $stop, 'team' => null, 'time' => null, 'stop_id' => $stop->id];
            $current = $board->action($task);
            if ($expected !== null && $current !== $expected) {
                HourlyOrderService::reject('هذا الإجراء ليس دور هذه المحطة الآن ('.$this->label($current).').');
            }
            if ($expected !== null && in_array($stop->status, ['completed', 'failed', 'skipped'], true)) {
                HourlyOrderService::reject('المحطة مغلقة بالفعل.');
            }
            $this->assertNear($visit, $gps, $expected);

            $changes = $mutate($stop, $visit, $order);
            $before = $visit->getAttributes();
            if (!empty($changes['visit'])) $visit->update($changes['visit']);
            if (isset($changes['visit']['status']) && $before['status'] !== $changes['visit']['status']) {
                $customerStage = ['en_route' => 'on_the_way', 'delivered' => 'arrived'][$changes['visit']['status']] ?? null;
                if ($customerStage) {
                    $visitId = (int) $visit->id;
                    DB::afterCommit(fn () => app(CustomerTrackingPushService::class)->notify($visitId, $customerStage));
                }
            }
            if (!empty($changes['stop'])) DB::table('hourly_route_stops')->where('id', $stop->id)->update($changes['stop'] + ['updated_at' => now()]);
            $this->ping($driver, $visit, $gps);
            if (Schema::hasTable('hourly_order_audits')) {
                app(OrderManagementService::class)->audit($order, 'driver_'.$action, $before,
                    $visit->fresh()->getAttributes() + ($changes['extra'] ?? []) + ['stop_id' => $stop->id, 'stop' => $changes['stop'] ?? []],
                    null, $visit->id, 'تطبيق السائق '.$driver->name.' (#'.$driver->id.'): '.$this->label($action));
            }
            if (!empty($changes['close_order']) && !$order->visits()->whereNotIn('status', array_merge(DriverAppService::VOID, ['completed']))->exists()) {
                app(HourlyOrderService::class)->transition($order, 'completed', 'driver', $driver->id, 'إكمال آخر زيارة من تطبيق السائق');
            }

            $trips = $board->sync($driver, \Carbon\Carbon::parse($stop->route_date)->toDateString());
            return ['trip' => collect($trips)->first(fn ($trip) => collect($trip['stops'])->contains('id', (int) $stop->id)),
                'trips' => array_values(array_filter($trips, fn ($trip) => $trip['status'] !== 'offered'))] + ($changes['extra'] ?? []);
        });
    }

    /** المشوار يجب أن يكون مقبولًا؛ أول إجراء يبدؤه تلقائيًا إن لم يبدأه السائق. */
    private function assertTrip(object $stop): void
    {
        if (!$stop->hourly_driver_trip_id) return;
        $trip = DB::table('hourly_driver_trips')->where('id', $stop->hourly_driver_trip_id)->first();
        if (!$trip) return;
        if (in_array($trip->status, ['offered', 'rejected', 'expired'], true)) HourlyOrderService::reject('اقبل المشوار أولًا ثم ابدأه.');
        if ($trip->status === 'accepted') {
            DB::table('hourly_driver_trips')->where('id', $trip->id)->update(['status' => 'started', 'started_at' => now(), 'updated_at' => now()]);
        }
    }

    /** تسجيل النقد المحصَّل باسم السائق على نفس مسار مدفوعات الطلب. */
    private function collect(HourlyRentalOrder $order, HourlyDriver $driver, ?float $cash, HourlyVisit $visit): float
    {
        $amount = round((float) ($cash ?? 0), 2);
        if ($amount <= 0) return 0.0;
        if (CashCollectionService::ready()) {
            return app(CashCollectionService::class)->collect($visit,'driver',(int)$driver->id,$amount,'delivery:'.$visit->id.':'.$driver->id)['amount'];
        }
        $due = round((float) $order->total_amount - (float) $order->paid_amount, 2);
        if ($due <= 0) HourlyOrderService::reject('لا يوجد مبلغ متبقٍ على هذا الطلب.');
        if ($amount > $due) HourlyOrderService::reject('المبلغ المحصَّل يتجاوز المتبقي ('.$due.' ر.س).');
        if ($order->payment_method === 'moyasar') HourlyOrderService::reject('طلب مدفوع إلكترونيًا؛ لا يُحصَّل نقدًا.');
        $order->payments()->create(['user_id' => $order->user_id, 'public_id' => (string) Str::uuid(),
            'method' => $order->payment_method === 'cash_and_card' ? 'cash' : $order->payment_method,
            'amount' => $amount, 'status' => 'paid', 'paid_at' => now(),
            'collected_by_type' => 'driver', 'collected_by_id' => $driver->id]);
        app(HourlyPaymentService::class)->recalculate($order->fresh(), 'driver', $driver->id);
        return $amount;
    }

    private function assertNear(HourlyVisit $visit, array $gps, ?string $action): void
    {
        $radius = (int) HourlyRentalSettings::get('staff_app_stop_radius_meters', 0);
        if ($radius <= 0 || !$action || in_array($action, ['shelter_pickup', 'shelter_return'], true)) return;
        if (!isset($gps['latitude'], $gps['longitude']) || $visit->latitude === null || $visit->longitude === null) return;
        $distance = $this->metres((float) $gps['latitude'], (float) $gps['longitude'], (float) $visit->latitude, (float) $visit->longitude);
        if ($distance > $radius) HourlyOrderService::reject('أنت بعيد عن موقع المحطة ('.round($distance).' م)؛ اقترب ثم أعد المحاولة.');
    }

    private function metres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** الموقع المرفق مع الإجراء يُخزَّن بنفس خدمة تتبع الموديول. */
    private function ping(HourlyDriver $driver, HourlyVisit $visit, array $gps): void
    {
        if (!isset($gps['latitude'], $gps['longitude']) || !Schema::hasTable('location_pings')) return;
        if (!HourlyRentalSettings::trackingConfig()['enabled']) return;
        app(LocationPings::class)->record('driver', (int) $driver->id,
            [['lat' => (float) $gps['latitude'], 'lng' => (float) $gps['longitude'], 'speed' => $gps['speed'] ?? null,
                'visit_id' => (int) $visit->id]]);
    }

    private function label(string $key): string
    {
        return [
            'shelter_pickup' => 'استلام من الإيواء', 'drop_off' => 'تسليم للعميل', 'deliver' => 'تسليم للعميل',
            'pick_up' => 'استلام من العميل', 'shelter_return' => 'عودة للإيواء', 'arrived' => 'وصلت', 'fail' => 'تعذّر التنفيذ',
        ][$key] ?? $key;
    }
}
