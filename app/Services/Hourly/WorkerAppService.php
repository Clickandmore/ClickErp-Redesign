<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder, HourlyVisit, HourlyWorkerSosAlert};
use App\Models\ingaz\Maid;
use App\Support\HourlyRentalSettings;
use App\Support\HourlyStaffOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * تطبيق العاملة: ساعاتي · زيارات اليوم (وصلت / انتهيت) · إجازاتي · أرباحي.
 *
 * معرّف الزيارة في مسارات العاملة هو `hourly_visit_workers.id` (العاملة في الزيارة) لا الزيارة نفسها،
 * لأن لكل عاملة حالة تنفيذ مستقلة في العمود `status` المضاف على ذلك الجدول.
 * حالة الزيارة العامة (hourly_visits.status) تتقدم فقط عندما تتفق كل عاملات الزيارة على الحالة نفسها،
 * فتبقى بوابة السائق ولوحة التحكم على نفس المسار القديم بلا تغيير.
 */
class WorkerAppService
{
    /** حالة العاملة المشتقة من حالة الزيارة عندما لا تكون مسجّلة على صفها. */
    private const FROM_VISIT = [
        'unscheduled' => 'assigned', 'scheduled' => 'assigned', 'en_route' => 'en_route', 'delivered' => 'delivered',
        'in_progress' => 'delivered', 'worker_finished' => 'finished', 'picked_up' => 'picked_up', 'completed' => 'completed',
        'cancelled' => 'cancelled', 'cancelled_by_client' => 'cancelled', 'expired' => 'cancelled', 'no_show' => 'no_show',
    ];
    private const DONE = ['finished', 'picked_up', 'returned', 'completed'];

    public function needsTransport(Maid $maid): bool
    {
        return (bool) (($maid->hourly_settings['requires_driver'] ?? true));
    }

    public function profile(Maid $maid): array
    {
        $team = Schema::hasTable('hourly_team_members') ? $maid->hourlyTeamMemberships()->with('team:id,name')->first()?->team : null;
        return ['id' => $maid->id, 'name' => $maid->name, 'phone' => StaffAppAuth::workerPhone($maid),
            'username' => $maid->hourly_username, 'needs_transport' => $this->needsTransport($maid),
            'team' => $team ? ['id' => $team->id, 'name' => $team->name] : null,
            'tracking' => HourlyRentalSettings::trackingConfig(), 'cash_custody'=>app(CashCollectionService::class)->custody('worker',(int)$maid->id)];
    }

    /** المستهلك مقابل السقف الساري (يشمل وضع رمضان وسقوف الإعدادات). */
    public function hours(Maid $maid): array
    {
        $caps = HourlyRentalSettings::effectiveHourCaps();
        $now = now();
        $windows = [
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), (float) $caps['per_day']],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), (float) $caps['per_week']],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), (float) $caps['per_month']],
        ];
        $out = [];
        foreach ($windows as $key => [$from, $to, $max]) {
            $used = (float) $this->visitQuery($maid)->whereBetween('v.scheduled_start_at', [$from, $to])->sum('v.planned_hours');
            $out[$key] = ['used' => round($used, 2), 'max' => $max, 'remaining' => round(max(0, $max - $used), 2)];
        }
        return $out + ['visits_per_day' => (int) $caps['visits_per_day'], 'ramadan_mode' => (bool) HourlyRentalSettings::get('ramadan_mode')];
    }

    public function today(Maid $maid, ?string $date = null): array
    {
        $day = Carbon::parse($date ?: now()->toDateString())->toDateString();
        $rows = $this->visitQuery($maid)->whereDate('v.scheduled_start_at', $day)
            ->select('w.id as worker_visit_id', 'w.status as worker_status', 'w.arrived_at', 'w.finished_at', 'o.hours_per_visit as order_hours', 'o.package_snapshot as order_snapshot', 'o.booking_metadata as order_metadata', 'v.*')
            ->orderBy('v.scheduled_start_at')->get();
        $drivers = HourlyDriver::withTrashed()->whereIn('id', $rows->pluck('delivery_driver_id')->merge($rows->pluck('pickup_driver_id'))->filter()->unique())
            ->pluck('name', 'id');
        $needsTransport = $this->needsTransport($maid);
        $driverRequiredVisits = DB::table('hourly_visit_workers as w')->join('maids as m', 'm.id', '=', 'w.maid_id')
            ->whereIn('w.hourly_visit_id', $rows->pluck('id'))
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(m.hourly_settings, '$.requires_driver')) IN ('1','true')")
            ->distinct()->pluck('w.hourly_visit_id')->flip();

        $cash=app(CashCollectionService::class);
        $cashVisits=CashCollectionService::ready()?HourlyVisit::with(['workers','order'])->whereIn('id',$rows->pluck('id'))->get()->keyBy('id'):collect();
        return $rows->map(function ($row) use ($drivers, $needsTransport, $driverRequiredVisits, $maid, $cash, $cashVisits) {
            $visitNeedsTransport = $needsTransport || !empty($row->delivery_driver_id) || isset($driverRequiredVisits[$row->id]);
            $canExecuteDay = Carbon::parse($row->scheduled_start_at)->startOfDay()->lte(now());
            $status = $row->worker_status ?: (($row->status === 'delivered' && empty($row->arrived_at)) ? 'assigned' : (self::FROM_VISIT[$row->status] ?? 'assigned'));
            $arrivalBlockedReason = in_array($status, ['assigned', 'en_route'], true)
                ? $this->workerSelfArrivalBlockReason($maid, $row, (object)['arrived_at' => $row->arrived_at]) : null;
            $meta=json_decode($row->order_metadata??'{}',true)??[];
            $incidentStatus=$meta['service_disruptions'][$row->id]['status']??null;
            $held=in_array($incidentStatus,ServiceIncident::WAITING,true);
            $timeVisit=(new HourlyVisit)->setRawAttributes((array)$row);
            $timeVisit->setRelation('order',(new HourlyRentalOrder)->forceFill(['id'=>$row->hourly_rental_order_id,'booking_metadata'=>$meta]));
            $timeWorker=(object)['id'=>$row->worker_visit_id,'status'=>$row->worker_status,'arrived_at'=>$row->arrived_at,'finished_at'=>$row->finished_at];
            return [
                'id' => (int) $row->worker_visit_id, 'visit_id' => (int) $row->id, 'visit_number' => (int) $row->visit_number,
                'status' => $status, 'visit_status' => $row->status,
                'booking_options' => (json_decode($row->order_snapshot ?? '{}', true) ?: [])['booking_options'] ?? [],
                // الخصوصية: العميل بحيّه لا باسمه.
                'client' => ['name' => 'عميل — '.($row->district ?: 'غير محدد')],
                'address' => $row->address_text, 'date' => Carbon::parse($row->scheduled_start_at)->toDateString(),
                'start_time' => Carbon::parse($row->scheduled_start_at)->format('H:i'),
                'end_time' => $row->scheduled_end_at ? Carbon::parse($row->scheduled_end_at)->format('H:i') : null,
                'planned_hours' => (float) $row->planned_hours,
                'extra_hours' => round(max(0, (float) $row->planned_hours - (float) ($row->order_hours ?? $row->planned_hours)), 2),
                'latitude' => (float) $row->latitude, 'longitude' => (float) $row->longitude,
                'driver' => isset($drivers[$row->delivery_driver_id]) ? ['id' => (int) $row->delivery_driver_id, 'name' => $drivers[$row->delivery_driver_id]] : null,
                'needs_transport' => $visitNeedsTransport,
                'incident_status'=>$incidentStatus,
                'arrival_blocked_reason' => $arrivalBlockedReason,
                'can_arrive' => !$arrivalBlockedReason && $canExecuteDay && !$held && !$visitNeedsTransport && in_array($status, ['assigned', 'en_route'], true),
                'can_finish' => $canExecuteDay && $status === 'delivered',
                // نهاية الخدمة المتوقعة من وقت الوصول؛ «انتهيت» قبلها إنهاء مبكر يطلب التطبيق له سببًا من early_finish_reasons.
                'can_return' => ShelterPresence::linked() && in_array($status, ['finished', 'picked_up'], true) && !in_array($row->status, DriverAppService::VOID, true),
                'planned_end_at' => $status === 'delivered' ? $this->plannedEnd($row) : null,
                'early_finish_reasons' => EarlyFinish::REASONS,
                'service_time' => app(ServiceTime::class)->workerData($timeVisit,$timeWorker),
                'financial'=>isset($cashVisits[$row->id])?app(PaymentScheduleService::class)->forOrder($cashVisits[$row->id]->order)['visits'][$row->id]??null:null,
                'cash'=>isset($cashVisits[$row->id])?$cash->info($cashVisits[$row->id],'worker',(int)$maid->id):['enabled'=>false,'can_collect'=>false],
            ];
        })->values()->all();
    }

    /** «وصلت» لمن لا تحتاج توصيلًا: بدء الزيارة بنفسها. */
    public function arrived(Maid $maid, int $workerVisitId, array $gps): array
    {
        return $this->act($maid, $workerVisitId, ['assigned', 'en_route'], 'delivered', 'arrived_at', 'delivered', $gps, null);
    }

    /**
     * «انتهيت»: تنتهي العاملة، ويُنشأ طلب الاستلام للسائق.
     * الإنهاء قبل اكتمال مدة الخدمة لا يُمنع، بل يُسجَّل ويُسأل العميل عنه (EarlyFinish)؛ $earlyReason سببه إن أرسله التطبيق.
     */
    public function finish(Maid $maid, int $workerVisitId, ?string $note, array $gps = [], ?string $earlyReason = null): array
    {
        return $this->act($maid, $workerVisitId, ['delivered'], 'finished', 'finished_at', 'worker_finished', $gps, $note, $earlyReason);
    }

    public function returnToShelter(Maid $maid, int $workerVisitId): array
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($maid, $workerVisitId) {
            $row = DB::table('hourly_visit_workers')->where('id', $workerVisitId)->lockForUpdate()->first();
            abort_unless($row && (int) $row->maid_id === (int) $maid->id, 404, 'Visit not found or not assigned to you.');
            $visit = HourlyVisit::query()->findOrFail($row->hourly_visit_id);
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($visit->hourly_rental_order_id);
            $visit = HourlyVisit::query()->lockForUpdate()->findOrFail($visit->id);
            if ($row->status === 'returned') return $this->result($maid, $visit, (int) $row->id);
            if ($order->status !== 'active') HourlyOrderService::reject('Order is not active.');
            if (!in_array($row->status, ['finished', 'picked_up', 'returned'], true) || in_array($visit->status, DriverAppService::VOID, true)) HourlyOrderService::reject('Return confirmation is available after finishing the visit and physically reaching the shelter.');
            if ($row->status === 'returned') return $this->result($maid, $visit, (int) $row->id);
            $before = $visit->getAttributes();
            DB::table('hourly_visit_workers')->where('id', $row->id)->update(['status' => 'returned', 'updated_at' => now()]);
            app(ShelterPresence::class)->closeWorker($visit, (int) $maid->id, 'worker', (int) $maid->id);
            app(ShelterPresence::class)->flush();
            $pending = DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['returned', 'completed']))->exists();
            if (!$pending) {
                $visit->update(['status' => 'completed']);
                if (Schema::hasTable('hourly_route_stops')) DB::table('hourly_route_stops')->where('hourly_visit_id', $visit->id)->where('role', 'pickup')->whereNotIn('status', ['failed', 'skipped', 'completed'])->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
                if (!$order->visits()->whereNotIn('status', array_merge(DriverAppService::VOID, ['completed']))->exists()) app(HourlyOrderService::class)->transition($order, 'completed', 'worker', (int) $maid->id, 'All workers confirmed return from worker app');
            }
            if (Schema::hasTable('hourly_order_audits')) app(OrderManagementService::class)->audit($order, 'worker_returned', $before, $visit->fresh()->getAttributes() + ['worker_visit_id' => (int) $row->id, 'maid_id' => (int) $maid->id], null, $visit->id, 'Worker confirmed return to shelter.');
            return $this->result($maid, $visit->fresh(), (int) $row->id);
        });
    }
    public function leaves(Maid $maid): array
    {
        abort_unless(Schema::hasTable('hourly_worker_leaves'), 503, 'الإجازات غير مهيأة على الخادم.');
        return DB::table('hourly_worker_leaves')->where('maid_id', $maid->id)->orderByDesc('id')->limit(100)->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id, 'type' => $row->type ?? 'leave',
                'starts_at' => Carbon::parse($row->starts_at)->toDateString(), 'ends_at' => Carbon::parse($row->ends_at)->toDateString(),
                'description' => $row->reason, 'status' => $row->status ?? 'approved',
                'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toIso8601String() : null,
            ])->all();
    }

    public function requestLeave(Maid $maid, array $data): array
    {
        abort_unless(Schema::hasTable('hourly_worker_leaves'), 503, 'الإجازات غير مهيأة على الخادم.');
        $from = Carbon::parse($data['starts_at'])->startOfDay();
        $to = Carbon::parse($data['ends_at'])->endOfDay();
        $type = $data['type'] ?? 'leave';
        if ($to->lt($from)) HourlyOrderService::reject('تاريخ النهاية قبل البداية.');
        if ($type === 'sick' ? $from->lt(now()->subDays(7)->startOfDay()) : $from->lt(now()->startOfDay())) {
            HourlyOrderService::reject($type === 'sick' ? 'الإجازة المرضية تُسجَّل لسبعة أيام ماضية كحد أقصى.' : 'اختر تاريخًا من اليوم أو بعده.');
        }
        if (DB::table('hourly_worker_leaves')->where('maid_id', $maid->id)->whereIn('status', ['pending', 'approved'])
            ->where('starts_at', '<', $to)->where('ends_at', '>', $from)->exists()) {
            HourlyOrderService::reject('لديك طلب إجازة يتقاطع مع هذه المدة.');
        }
        // الزيارات المسندة لا تمنع الطلب؛ يدخل pending وغير فاعل حتى تعيد الإدارة الإسناد وتعتمده.
        $conflicts = $this->visitQuery($maid)->whereBetween('v.scheduled_start_at', [$from, $to])->count();
        $id = DB::table('hourly_worker_leaves')->insertGetId([
            'maid_id' => $maid->id, 'starts_at' => $from, 'ends_at' => $to, 'reason' => $data['description'] ?? null,
            'is_active' => false, 'status' => 'pending', 'type' => $type, 'source' => 'worker_app',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['id' => $id, 'status' => 'pending', 'conflicting_visits' => $conflicts,
            'message' => $conflicts > 0
                ? 'تم إرسال الطلب. لديك '.$conflicts.' زيارة في هذه المدة، وستعيد الإدارة إسنادها قبل الاعتماد.'
                : 'تم إرسال الطلب وسيُعتمد من الإدارة.'];
    }

    /** نداء النجدة: المنطق مشترك مع السائق في StaffSos، وهذه الدوال تبقى واجهة العاملة. */
    public function sosAlerts(Maid $maid): array
    {
        return app(StaffSos::class)->alerts(HourlyStaffOtp::WORKER, (int) $maid->id);
    }

    public function triggerSos(Maid $maid, array $data): array
    {
        return app(StaffSos::class)->trigger(HourlyStaffOtp::WORKER, (int) $maid->id, $data);
    }

    public function cancelSos(Maid $maid, int $alertId, ?string $reason): array
    {
        return app(StaffSos::class)->cancel(HourlyStaffOtp::WORKER, (int) $maid->id, $alertId, $reason);
    }

    /** أرباح مؤقتة بسعر الساعة من الإعدادات حتى تُبنى المرحلة ٤ (hourly_visit_earnings). */
    public function earnings(Maid $maid): array
    {
        $rate = (float) HourlyRentalSettings::get('staff_app_worker_hour_rate', 0);
        $rows = $this->visitQuery($maid)->whereIn('v.status', ['worker_finished', 'picked_up', 'completed'])
            ->where('v.scheduled_start_at', '>=', now()->subMonths(3))
            ->select('v.id', 'v.scheduled_start_at', 'v.planned_hours', 'v.district')
            ->orderByDesc('v.scheduled_start_at')->limit(100)->get();
        $items = $rows->map(fn ($row) => [
            'visit_id' => (int) $row->id, 'client' => ['name' => 'عميل — '.($row->district ?: 'غير محدد')],
            'date' => Carbon::parse($row->scheduled_start_at)->toDateString(),
            'hours' => (float) $row->planned_hours, 'amount' => round($rate * (float) $row->planned_hours, 2), 'status' => 'accrued',
        ])->all();
        $month = array_filter($items, fn ($item) => Carbon::parse($item['date'])->isSameMonth(now()));

        return [
            'currency' => 'SAR',
            'total_due' => round(array_sum(array_column($items, 'amount')), 2),
            'month' => ['visits_count' => count($month), 'amount' => round(array_sum(array_column($month, 'amount')), 2)],
            'items' => $items,
            'note' => $rate > 0 ? null : 'لم تُضبط قيمة ساعة العاملة في إعدادات الساعات بعد.',
        ];
    }

    /** زيارات العاملة الفاعلة: صف الربط + الزيارة + طلب نشط. */
    private function visitQuery(Maid $maid)
    {
        return DB::table('hourly_visit_workers as w')
            ->join('hourly_visits as v', 'v.id', '=', 'w.hourly_visit_id')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')
            ->where('w.maid_id', $maid->id)
            ->whereNotIn('v.status', DriverAppService::VOID)
            ->whereNotIn('o.status', ['cancelled', 'expired']);
    }

    /**
     * تغيير حالة العاملة في الزيارة، ثم تقديم حالة الزيارة إن اتفقت كل العاملات عليها.
     * @param array<int,string> $allowed الحالات المسموح الانتقال منها
     */
    private function act(Maid $maid, int $workerVisitId, array $allowed, string $target, string $stamp, string $visitTarget, array $gps, ?string $note, ?string $earlyReason = null): array
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($maid, $workerVisitId, $allowed, $target, $stamp, $visitTarget, $gps, $note, $earlyReason) {
            $row = DB::table('hourly_visit_workers')->where('id', $workerVisitId)->first();
            abort_unless($row && (int) $row->maid_id === (int) $maid->id, 404, 'الزيارة غير موجودة أو ليست من زياراتك.');
            $reference = HourlyVisit::findOrFail($row->hourly_visit_id);
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($reference->hourly_rental_order_id);
            $visit = HourlyVisit::query()->lockForUpdate()->findOrFail($reference->id);
            $row = DB::table('hourly_visit_workers')->where('id', $workerVisitId)->lockForUpdate()->first();
            abort_unless($row && (int)$row->maid_id===(int)$maid->id && (int)$row->hourly_visit_id===(int)$visit->id,404);
            if ($order->status !== 'active') HourlyOrderService::reject('الطلب غير نشط.');
            if($visitTarget==='delivered' && ServiceIncident::waiting($order,$visit->id))
                HourlyOrderService::reject('للزيارة بلاغ معتمد؛ راجع الإدارة قبل بدء تنفيذها.');
            if (in_array($visit->status, DriverAppService::VOID, true)) HourlyOrderService::reject('الزيارة ملغاة أو منتهية.');
            if ($visit->scheduled_start_at && $visit->scheduled_start_at->copy()->startOfDay()->isFuture()) {
                HourlyOrderService::reject('لا يمكن تسجيل الوصول أو الانتهاء قبل يوم الزيارة المحدد.');
            }
            $current = $row->status ?: (($visit->status === 'delivered' && empty($row->arrived_at)) ? 'assigned' : (self::FROM_VISIT[$visit->status] ?? 'assigned'));
            if ($current === $target) return $this->result($maid, $visit, $row->id);
            if ($visitTarget === 'delivered') $this->assertWorkerMaySelfArrive($maid, $visit, $row);
            if (!in_array($current, $allowed, true)) {
                HourlyOrderService::reject($target === 'finished' ? 'لا يمكن إعلان الانتهاء قبل بدء الزيارة.' : 'حالة الزيارة لا تسمح بهذا الإجراء.');
            }

            $before = $visit->getAttributes();
            if($target==='finished')app(ServiceTime::class)->capture($visit,$row,true);
            DB::table('hourly_visit_workers')->where('id', $row->id)->update(['status' => $target, $stamp => now(), 'updated_at' => now()]);
            if ($target === 'delivered') { if (ShelterPresence::linked()) app(ShelterPresence::class)->openWorker($visit, (int) $maid->id, 'worker', (int) $maid->id); }
            $early = $target === 'finished' && app(EarlyFinish::class)->detect($order, $visit, $row, $maid, $earlyReason, $note);
            // كل العاملات على نفس الحالة ⇒ الزيارة تتقدم.
            $pendingQuery = DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id);
            if ($target === 'finished') {
                $pendingQuery->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['finished', 'picked_up', 'returned', 'completed']));
            } else {
                $pendingQuery->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', $target));
            }
            $pending = $pendingQuery->count();
            $advanced = false;
            if ($visitTarget === 'delivered' && in_array($visit->status, ['scheduled', 'en_route'], true)) {
                $visit->update(['status' => 'delivered']);
                $advanced = true;
            } elseif ($pending === 0 && $visit->status !== $visitTarget) {
                // وقت وصول العاملة يُسجَّل على صفها (arrived_at)؛ delivery_at يبقى وقت السائق المخطَّط.
                $visit->update(['status' => $visitTarget]);
                $advanced = true;
            }
            // من لا تحتاج توصيلًا ولا سائق استلام: الزيارة تُغلق بانتهائها بلا مشوار عودة.
            if ($advanced && $visitTarget === 'worker_finished' && !$visit->pickup_driver_id && !$this->needsTransport($maid) && !ShelterPresence::linked()) {
                $visit->update(['status' => 'completed']);
                if (!$order->visits()->whereNotIn('status', array_merge(DriverAppService::VOID, ['completed']))->exists()) {
                    app(HourlyOrderService::class)->transition($order, 'completed', 'worker', $maid->id, 'إكمال آخر زيارة من تطبيق العاملة');
                }
            }
            if ($advanced && $visitTarget === 'worker_finished') {
                $visitId=(int)$visit->id;
                if ($visit->pickup_driver_id) {
                    DB::afterCommit(fn()=>app(StaffPushService::class)->pickupRequested($visitId));
                }
                // العميل تابع «في الطريق» ثم «وصل»، فكان يُترك بلا خبر عند الانتهاء.
                // الإنهاء المبكر أرسل إشعاره الخاص (تأكيد العميل)، فلا يُضاف إشعار «انتهت الزيارة» العادي.
                if (!$early) DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->notify($visitId, 'finished'));
            }
            if ($advanced && $visitTarget === 'delivered') {
                $visitId = (int) $visit->id;
                DB::afterCommit(fn () => app(CustomerTrackingPushService::class)->notify($visitId, 'arrived'));
            }
            if (Schema::hasTable('hourly_order_audits')) {
                app(OrderManagementService::class)->audit($order, 'worker_'.$target, $before,
                    $visit->fresh()->getAttributes() + array_filter(['note' => $note, 'worker_visit_id' => $row->id, 'maid_id' => $maid->id]),
                    null, $visit->id, 'تطبيق العاملة '.$maid->name.' (#'.$maid->id.'): '.($target === 'finished' ? 'انتهيت' : 'وصلت'));
            }
            if (isset($gps['latitude'], $gps['longitude']) && HourlyRentalSettings::trackingConfig()['enabled']) {
                app(LocationPings::class)->record('maid', (int) $maid->id, [['lat' => (float) $gps['latitude'],
                    'lng' => (float) $gps['longitude'], 'visit_id' => (int) $visit->id]]);
            }

            return $this->result($maid, $visit->fresh(), $row->id);
        });
    }

    private function assertWorkerMaySelfArrive(Maid $maid, HourlyVisit $visit, object $assignment): void
    {
        if ($reason = $this->workerSelfArrivalBlockReason($maid, $visit, $assignment)) HourlyOrderService::reject($reason);
    }

    private function workerSelfArrivalBlockReason(Maid $maid, object $visit, object $assignment): ?string
    {
        if (!empty($assignment->arrived_at)) return null;
        if ($reason = OverdueWorkerReturn::reason((int) $maid->id, (int) $visit->id)) return $reason;
        $requiresDriver = DB::table('hourly_visit_workers as w')->join('maids as m', 'm.id', '=', 'w.maid_id')
            ->where('w.hourly_visit_id', $visit->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(m.hourly_settings, '$.requires_driver')) IN ('1','true')")
            ->exists();
        if (!$visit->delivery_driver_id && ($requiresDriver || $this->needsTransport($maid)))
            return 'تضم الزيارة عاملة تحتاج إلى سائق؛ يجب تعيين السائق أولًا، وهو من يسجل الوصول عند التسليم للعميل.';
        if ($visit->delivery_driver_id) return 'وصول الطاقم يُسجّل بواسطة السائق عند التسليم للعميل.';
        if (Schema::hasTable('worker_daily_movements') && DB::table('worker_daily_movements')->where('maid_id', $maid->id)->whereNull('in_at')->exists())
            return 'لا يمكن تسجيل الوصول قبل تأكيد عودتك إلى الإيواء وإغلاق حركة الخروج السابقة.';
        if (!\App\Services\Rental\RentalMaidQuery::applyInsideShelter(Maid::query()->whereKey($maid->id))->exists())
            return 'لا يمكن تسجيل الوصول؛ حالة العاملة لا تثبت وجودها داخل الإيواء. راجعي الإدارة لتأكيد العودة أولًا.';
        return null;
    }
    private function plannedEnd(object $row): ?string
    {
        $visit = (new HourlyVisit)->forceFill(['scheduled_start_at' => $row->scheduled_start_at, 'planned_hours' => $row->planned_hours]);
        return app(EarlyFinish::class)->plannedEnd($row->arrived_at ?? null, $visit)?->toIso8601String();
    }

    private function result(Maid $maid, HourlyVisit $visit, int $workerVisitId): array
    {
        $today = $this->today($maid, Carbon::parse($visit->scheduled_start_at)->toDateString());
        return ['visit' => collect($today)->firstWhere('id', $workerVisitId), 'visits' => $today, 'hours' => $this->hours($maid)];
    }
}
