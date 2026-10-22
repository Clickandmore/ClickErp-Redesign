<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyVisit};
use App\Models\ingaz\{User, UserAddress};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HourlyOrderService
{
    public static function enabled(): void { abort_unless(HourlyRentalSettings::isEnabled(), 404); }
    public static function reject(string $message): void { throw ValidationException::withMessages(['hourly' => $message]); }

    public function create(int $userId, array $data, ?int $adminId = null, ?\Illuminate\Support\Collection $customerCandidates = null): HourlyRentalOrder
    {
        self::enabled();
        return DB::transaction(function () use ($userId, $data, $adminId, $customerCandidates) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            // قفل رصيد العميل قبل إنشاء طلب المحفظة يمنع خصمين متزامنين من الرصيد نفسه.
            $walletUser = $data['payment_method'] === 'wallet'
                ? User::query()->whereKey($userId)->lockForUpdate()->firstOrFail()
                : null;
            $package = HourlyPackage::query()->lockForUpdate()->findOrFail($data['hourly_package_id']);
            if(!app(ProductAttributeService::class)->available($package))self::reject('سمة العنصر أو السمة الرئيسية متوقفة.');
            if(isset($data['attribute_id']) && (int)$data['attribute_id']!==(int)$package->attribute_id)self::reject('اختر عنصر الخدمة المطابق للسمة المحددة.');
            if (!$adminId && ($package->is_private || $package->show_on_app === false)) self::reject('هذه الباقة غير متاحة للحجز من التطبيق.');
            $address = UserAddress::query()->where('user_id', $userId)->findOrFail($data['address_id']);
            if (!$package->is_active || !$package->effectiveCities()->where('cities.id', $address->city_id)->where('is_active', true)->exists()) self::reject('الباقة غير متاحة في مدينة العنوان.');
            $cities = HourlyRentalSettings::get('city_ids');
            if ($cities && !in_array((int) $address->city_id, $cities, true)) self::reject('المدينة خارج نطاق التشغيل.');
            if ($address->latitude === null || $address->longitude === null) self::reject('حدد موقع العنوان على الخريطة أولاً.');
            $rules = app(HourlyBookingRules::class);
            $category = $rules->category($package);
            $categoryColumns = [];
            $district = null;
            $coverage = null;
            if ($category) {
                if (!$category->is_active || $category->trashed()) self::reject(__('hourly.category_inactive'));
                [$district, $coverage] = $rules->assertDistrictCovered($category, (int) $address->city_id, (float) $address->latitude, (float) $address->longitude, $address->district, isset($data['district_id']) ? (int) $data['district_id'] : null);
                if (Schema::hasColumn('hourly_rental_orders', 'service_category_id')) $categoryColumns = ['service_category_id'=>$category->id, 'service_district_id'=>$district?->id];
            }
            $panel = (bool) $adminId;
            $channel = HourlyBookingRules::channel($panel);
            $settings = $rules->settings();
            if ($category && !$panel && !$rules->visibleToCustomers($package)) self::reject('هذا النوع من العناصر غير معروض للعملاء في هذه الفئة.');
            $rules->assertPaymentMethod($package, $data['payment_method'], $panel, $data['payment_channel'] ?? 'employee');
            $defaults = $rules->defaultCounts($package, $channel);
            $workers = (int) ($data['workers_per_visit'] ?? $defaults['workers']);
            $selected = array_map('intval', $data['selected_maid_ids'] ?? []);
            if (array_key_exists('selected_maid_ids', $data)) {
                if (!empty($data['preferred_maid_id'])) self::reject('أرسل selected_maid_ids أو preferred_maid_id، وليس كليهما.');
                if (count($selected) > $workers || count(array_unique($selected)) !== count($selected)) self::reject('عدد العاملات المختارات يتجاوز المطلوب أو يحتوي على تكرار.');
                if ($selected && !$panel && !$settings->value($package, 'customer_worker_selection')) self::reject('اختيار العاملات غير متاح لهذه الخدمة.');
                // Match the public worker list, including visibility and district coverage.
                if (!$panel && $district && $customerCandidates === null) $customerCandidates = app(HourlyWebsiteCatalog::class)->candidates($package, $district, true);
            }
            if ($workers < $package->workers_per_visit || $workers > 20) self::reject('عدد العاملات غير صالح.');
            if (!$panel && !empty($data['preferred_maid_id']) && !$settings->value($package, 'customer_worker_selection')) self::reject('اختيار العاملة غير متاح للعملاء في هذه الفئة.');
            $start = now(); $expires = $start->copy()->addDays($package->validity_days);
            $snapshot = $package->toArray();
            foreach (['min_notice_hours','cancellation_window_hours','cancellation_fee_percent'] as $key) $snapshot[$key] = $package->$key ?? HourlyRentalSettings::get($key);
            $snapshot['service_district_id'] = $district?->id;
            $dates = $data['scheduled_starts'] ?? [];
            $options = app(HourlyBookingOptions::class)->resolve($package, $data);
            if (!empty($data['scheduled_dates'])) {
                if ($dates) self::reject('أرسل تواريخ الزيارات أو مواعيدها، وليس كليهما.');
                $dates = app(HourlyDateBooking::class)->resolve($package, $data['scheduled_dates'], $snapshot, $panel,
                    $coverage?->team_id, $workers, isset($data['preferred_maid_id']) ? (int) $data['preferred_maid_id'] : null, null, $selected, $customerCandidates, !array_key_exists('selected_maid_ids',$data));
            }
            if (!empty($data['preferred_maid_id']) && !$dates) self::reject('اختر موعد الزيارة قبل اختيار العاملة.');
            if ($selected && !$dates) self::reject('اختر موعد الزيارة قبل اختيار العاملات.');
            if (count($dates) > $package->visits_count) self::reject('عدد المواعيد يتجاوز زيارات الباقة.');
            $rules->assertSessionDates($package, $dates, $panel);
            $slots = app(HourlySlotService::class)->validate($dates, (float) $package->hours_per_visit, (int) $snapshot['min_notice_hours'], $expires, $snapshot, $panel);
            app(DuplicateBookingService::class)->assertAvailable($userId,$address->toArray(),$package,$slots,null,$panel);
            // السعر الموحد: ضريبة موروثة، مراحل العاملات والكمية، وطريقة عرض سعر الباقة.
            $quote = $rules->quote($package, $workers, (int) ($data['quantity'] ?? $defaults['quantity']), $panel);
            $total = $quote['total']; $subtotal = $quote['subtotal'];
            $autoAssign = $category && $settings->value($package, 'auto_assign', $channel);
            if ($panel && !empty($data['panel_assignment'])) $autoAssign = false;
            $snapshot += ['tax_rate_effective'=>$quote['tax_rate'], 'quantity'=>$quote['quantity'], 'price_display'=>$quote['price_display'], 'charge_now'=>$quote['charge_now']/100,
                'booking_options'=>$options, 'pricing_components'=>$quote['components'],
                'selected_maid_ids'=>$selected, 'preferred_maid_id'=>$data['preferred_maid_id'] ?? null, 'assign_by_product_attribute'=>(bool) $settings->value($package, 'assign_by_product_attribute')];
            $location = ['address_text'=>$address->address, 'latitude'=>$address->latitude,'longitude'=>$address->longitude,'city_id'=>$address->city_id];
            foreach (['district','building','unit','contact_phone'] as $key) $location[$key] = $address->$key;
            $order = HourlyRentalOrder::create($location + $categoryColumns + [
                'order_number'=>'HR-'.Str::upper((string) Str::uuid()), 'invoice_number'=>'HI-'.Str::upper((string) Str::uuid()),
                'user_id'=>$userId,'hourly_package_id'=>$package->id,'source'=>$adminId ? 'admin' : 'app','created_by_admin_id'=>$adminId,
                'schedule_pattern'=>$slots, 'package_snapshot'=>$snapshot,'hours_per_visit'=>$package->hours_per_visit,'workers_per_visit'=>$workers,'visits_total'=>$package->visits_count,
                'price_per_visit'=>$package->price_per_visit,'extra_worker_price'=>$package->extra_worker_price,'extra_hour_price'=>$package->extra_hour_price,
                'transport_fee'=>$package->transport_included ? 0 : $package->transport_fee,'tax_rate'=>$quote['tax_rate'],
                'subtotal'=>$subtotal/100,'tax_amount'=>($total-$subtotal)/100,'total_amount'=>$total/100,
                'starts_at'=>$start,'expires_at'=>$expires,'status'=>'pending_confirmation','payment_method'=>$data['payment_method'], 'notes'=>$data['notes'] ?? null,
            ]);
            // The primary key is already unique, so expose it as a compact, readable order number.
            // A temporary UUID is used only for the insert because the id does not exist beforehand.
            $order->update(['order_number'=>'HR-'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT)]);
            // Generate all credit rows at purchase, never maintain competing balance counters.
            // فريق التغطية من بيانات الزيارة نفسها، حتى لو كان الإسناد الفوري معطلًا.
            // تركه فارغًا يجعل الإسناد التشغيلي اللاحق يبحث داخل فريق غير موجود.
            $team = $coverage?->team_id;
            for ($i=1; $i <= $package->visits_count; $i++) {
                $slot = $slots[$i-1] ?? null;
                $schedule = $slot ? ['scheduled_date'=>substr($slot['starts_at'],0,10), 'scheduled_start_at'=>$slot['starts_at'], 'scheduled_end_at'=>$slot['ends_at'], 'status'=>'scheduled'] : ['status'=>'unscheduled'];
                $visit=$order->visits()->create($schedule + $location + ['visit_number'=>$i,'hourly_team_id'=>$team,'planned_hours'=>$package->hours_per_visit,'workers_count'=>$workers,'visit_price'=>$quote['visit_prices'][$i-1]/100,'status'=>'unscheduled']);
                if($slot && ($autoAssign || $selected || !empty($data['preferred_maid_id'])))app(HourlyWorkerAssignment::class)->assign($visit,$package,isset($data['preferred_maid_id'])?(int)$data['preferred_maid_id']:null,(bool)$settings->value($package,'assign_by_product_attribute'),$customerCandidates,array_key_exists('selected_maid_ids',$data)?$selected:null,(bool)$autoAssign);
                if($slot && empty($data['panel_assignment']) && $settings->value($package,'auto_assign_driver',$channel))app(NearestDriverAssignment::class)->assign($visit);
            }
            $this->log($order, null, 'pending_confirmation', $adminId ? 'admin' : 'client', $adminId ?? $userId, 'إنشاء الطلب');
            // التأكيد التلقائي للدفع الآجل (نقدي/بطاقة عند التسليم) حسب القناة.
            if ($category && !in_array($snapshot['payment_policy']??null,['prepaid','installments'],true) && in_array($order->payment_method, ['cash','card_on_delivery'], true) && $settings->value($package, 'auto_confirm_postpaid', $channel)) {
                $this->transition($order, 'active', $panel ? 'admin' : 'system', $adminId, 'تأكيد تلقائي للدفع الآجل');
            }
            app(PaymentScheduleService::class)->generateInstallments($order);
            if ($walletUser) $this->payFromWallet($order, $walletUser);
            return $order;
        });
    }

    /** خصم كامل قيمة الطلب من محفظة العميل وتسجيل الدفعة والحركة في المعاملة نفسها. */
    private function payFromWallet(HourlyRentalOrder $order, User $user): void
    {
        if (!Schema::hasTable('hourly_wallet_transactions') || !Schema::hasColumn('users', 'hourly_wallet_balance')) {
            self::reject('المحفظة غير مهيأة على الخادم.');
        }
        $amount = (int) round((float) $order->total_amount * 100);
        $balance = (int) round((float) $user->hourly_wallet_balance * 100);
        if ($balance < $amount) self::reject('رصيد المحفظة غير كافٍ.');

        $newBalance = $balance - $amount;
        DB::table('users')->where('id',$user->id)->update(['hourly_wallet_balance' => $newBalance / 100]);
        if ($amount > 0) {
            $transaction = [
                'user_id'=>$user->id, 'hourly_rental_order_id'=>$order->id,
                'amount'=>-$amount/100, 'balance_after'=>$newBalance/100,
                'admin_id'=>null, 'created_at'=>now(), 'updated_at'=>now(),
            ];
            if (Schema::hasColumn('hourly_wallet_transactions','type')) $transaction += [
                'type'=>'order_payment', 'payment_method'=>'wallet', 'collected_by_type'=>'system',
                'description'=>'سداد طلب '.$order->order_number.' من المحفظة', 'paid_at'=>now(),
            ];
            DB::table('hourly_wallet_transactions')->insert($transaction);
            $order->payments()->create([
                'user_id'=>$user->id, 'public_id'=>(string) Str::uuid(), 'method'=>'wallet', 'provider'=>'wallet',
                'amount'=>$amount/100, 'status'=>'paid', 'paid_at'=>now(), 'collected_by_type'=>'system',
            ]);
        }
        $order->update(['paid_amount'=>$amount/100, 'payment_status'=>'paid']);
        $this->transition($order, 'active', 'system', null, 'السداد من محفظة العميل');
    }

    public function confirm(HourlyRentalOrder $order, int $adminId): HourlyRentalOrder
    {
        self::enabled();
        return DB::transaction(function () use ($order, $adminId) {
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'pending_confirmation' || $order->expires_at->isPast()) self::reject('الطلب غير قابل للتأكيد.');
            $explicitPolicy=$order->package_snapshot['payment_policy']??null;
            if(in_array($explicitPolicy,['prepaid','installments'],true) && !HourlyPaymentService::activationPaid($order))self::reject('يجب تحصيل المبلغ المطلوب مقدمًا قبل تفعيل الطلب.');
            if (!in_array($order->payment_method, ['cash','card_on_delivery'], true) && $order->payment_status !== 'paid' && !HourlyPaymentService::activationPaid($order)) self::reject('يجب تأكيد تحصيل المبلغ قبل تفعيل هذا الطلب.');
            $this->transition($order, 'active', 'admin', $adminId, 'تأكيد الصرّاف');
            return $order;
        });
    }

    /**
     * إعادة جدولة زيارة وفق إعدادات الفئة: allow_reschedule وreschedule_hours_before (للعميل) وmax_reschedules_per_visit.
     * الموعد الجديد يمر بنفس قواعد التوفر (التقويم وأيام العمل والإغلاقات).
     */
    public function schedule(HourlyVisit $visit, string $start): HourlyVisit
    {
        return $this->reschedule($visit, $start, null, true);
    }

    public function reschedule(HourlyVisit $visit, string $start, ?int $adminId = null, bool $initial = false,bool $incidentResolution=false): HourlyVisit
    {
        self::enabled();
        return DB::transaction(function () use ($visit, $start, $adminId, $initial,$incidentResolution) {
            User::whereKey(HourlyRentalOrder::whereKey($visit->hourly_rental_order_id)->value('user_id'))->lockForUpdate()->firstOrFail();
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($visit->hourly_rental_order_id);
            $visit = $order->visits()->lockForUpdate()->findOrFail($visit->id);
            if(!$incidentResolution && ServiceIncident::waiting($order,$visit->id)) self::reject('للزيارة بلاغ معتمد؛ استخدم طلب المعالجة للحفاظ على حقوقك.');
            if (!$incidentResolution && $visit->has_started_execution) self::reject('بدأ تنفيذ الزيارة بالفعل؛ لا يمكن تغيير موعدها.');
            $snapshot = (array) $order->package_snapshot + ['service_category_id' => $order->service_category_id ?? null];
            $settings = app(HourlyBookingRules::class)->settings();
            $panel = (bool) $adminId;
            if (!$initial && !$settings->value($snapshot, 'allow_reschedule')) self::reject('إعادة الجدولة غير متاحة في هذه الفئة.');
            if ($order->status !== 'active' && $order->status !== 'pending_confirmation') self::reject('الطلب غير قابل لإعادة الجدولة.');
            if ($order->expires_at->lte(now())) self::reject('انتهت صلاحية الباقة.');
            if ($initial && ($visit->status !== 'unscheduled' || $visit->scheduled_start_at)) self::reject('هذه الزيارة لديها موعد بالفعل أو غير قابلة للجدولة.');
            if (!$initial && ($visit->status !== 'scheduled' || !$visit->scheduled_start_at || !$visit->scheduled_start_at->isFuture())) self::reject('يمكن إعادة جدولة الزيارات المجدولة القادمة فقط.');
            if (!$initial && (int) $visit->reschedule_count >= $settings->value($snapshot, 'max_reschedules_per_visit')) self::reject('تم بلوغ الحد الأقصى لإعادة جدولة هذه الزيارة.');
            if (!$initial && !$panel && now()->diffInMinutes($visit->scheduled_start_at, false) < $settings->value($snapshot, 'reschedule_hours_before') * 60) {
                self::reject('إعادة الجدولة متاحة قبل '.$settings->value($snapshot, 'reschedule_hours_before').' ساعة على الأقل من موعد الزيارة.');
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
                $item = HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
                $assigned = DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->pluck('maid_id')->all();
                if (Schema::hasTable('hourly_visit_reservations')) $assigned = array_unique(array_merge($assigned,
                    DB::table('hourly_visit_reservations')->where('hourly_visit_id', $visit->id)->pluck('maid_id')->all()));
                [$start] = app(HourlyDateBooking::class)->resolve($item, [$start], $snapshot, $panel,
                    $visit->hourly_team_id, (int) $visit->workers_count, null, $visit->id, $assigned);
            }
            [$slot] = app(HourlySlotService::class)->validate([$start], (float) $order->hours_per_visit, (int) ($snapshot['min_notice_hours'] ?? 0), $order->expires_at, $snapshot, $panel);
            $otherDates=$order->visits()->where('id','!=',$visit->id)->whereNotIn('status',['cancelled','cancelled_by_client','expired'])->whereNotNull('scheduled_start_at')->lockForUpdate()->get()
                ->map(fn($other)=>$other->scheduled_start_at->format('Y-m-d H:i'))->all();
            app(HourlyBookingRules::class)->assertSessionDates($snapshot,array_merge($otherDates,[$start]),true);
            $rest=(int)($snapshot['break_minutes']??0)+(int)($snapshot['buffer_minutes']??0);
            $conflict=$order->visits()->where('id','!=',$visit->id)->whereNotIn('status',['cancelled','cancelled_by_client','expired'])
                ->where('scheduled_start_at','<',Carbon::parse($slot['ends_at'])->addMinutes($rest))
                ->where('scheduled_end_at','>',Carbon::parse($slot['starts_at'])->subMinutes($rest))->exists();
            if($conflict) self::reject('الموعد يتداخل مع زيارة أخرى أو فترة الراحة.');
            if(Schema::hasTable('hourly_visit_workers')){
                $ids=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->pluck('maid_id');
                if (Schema::hasTable('hourly_visit_reservations')) $ids=$ids->merge(DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->pluck('maid_id'))->unique();
                foreach($ids->isEmpty()?[]:\App\Models\ingaz\Maid::whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get() as $maid){
                    if(!app(HourlyWorkerAssignment::class)->available($maid,$slot['starts_at'],$slot['ends_at'],$snapshot,$visit->id))self::reject('العاملة المسندة غير متاحة في الموعد الجديد.');
                }
            }
            app(DuplicateBookingService::class)->assertAvailable((int)$order->user_id,$order->only(['city_id','address_text','latitude','longitude','building','unit']),HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id),[$slot],(int)$visit->id,$panel);
            $visit->update(['scheduled_date'=>substr($slot['starts_at'], 0, 10), 'scheduled_start_at'=>$slot['starts_at'], 'scheduled_end_at'=>$slot['ends_at'], 'status'=>'scheduled', 'reschedule_count'=>(int) $visit->reschedule_count + ($initial ? 0 : 1)]);
            if ($initial && !empty($snapshot['service_category_id'])) {
                $package = HourlyPackage::withTrashed()->findOrFail($order->hourly_package_id);
                $category = app(HourlyBookingRules::class)->category($package);
                [, $coverage] = app(HourlyBookingRules::class)->assertDistrictCovered($category, (int)$order->city_id, (float)$order->latitude, (float)$order->longitude, $order->district, $order->service_district_id);
                $team = $coverage?->team_id;
                $visit->update(['hourly_team_id'=>$team]);
                if ($settings->value($snapshot, 'auto_assign', HourlyBookingRules::channel($panel))) {
                    app(HourlyWorkerAssignment::class)->assign($visit, $package, null, (bool) $settings->value($snapshot, 'assign_by_product_attribute'));
                }
                if ($settings->value($snapshot,'auto_assign_driver',HourlyBookingRules::channel($panel))) app(NearestDriverAssignment::class)->assign($visit);
            }
            $this->log($order, $order->status, $order->status, $panel ? 'admin' : 'client', $adminId ?? $order->user_id, ($initial ? 'جدولة الزيارة ' : 'إعادة جدولة الزيارة ').$visit->visit_number.' إلى '.$slot['starts_at']);
            return $visit;
        });
    }

    /** All visits are finished but money is still owed; closes to "completed" once settled. */
    public const AWAITING_PAYMENT = 'awaiting_payment';

    public static function owes(HourlyRentalOrder $order): bool
    {
        return (int) round((float) $order->total_amount * 100) > (int) round((float) $order->paid_amount * 100);
    }

    /** Label of an order status; "awaiting payment" tells unpaid from partially paid. */
    public static function statusLabel(HourlyRentalOrder $order): string
    {
        if ($order->status !== self::AWAITING_PAYMENT) return __('hourly.'.$order->status);
        return __('hourly.'.((float) $order->paid_amount > 0 ? 'awaiting_payment_partial' : 'awaiting_payment_unpaid'));
    }

    /** Close an order that finished its visits once its balance reaches zero. */
    public function settle(HourlyRentalOrder $order, string $actorType, ?int $actorId): void
    {
        if ($order->status === self::AWAITING_PAYMENT && !self::owes($order)) {
            $this->transition($order, 'completed', $actorType, $actorId, 'اكتمال السداد بعد انتهاء الزيارات');
        }
    }

    /** Supervisor closes an order still owing money; the balance stays recorded on the order. */
    public function closeUnpaid(HourlyRentalOrder $order, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($order, $adminId, $reason) {
            $order = HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== self::AWAITING_PAYMENT) self::reject('الإغلاق الاستثنائي متاح للطلبات المنتظرة للسداد فقط.');
            $owed = round((float) $order->total_amount - (float) $order->paid_amount, 2);
            $this->transition($order, 'completed', 'admin', $adminId, 'إغلاق استثنائي دون اكتمال السداد (المتبقي '.number_format($owed, 2).' ر.س): '.$reason, true);
        });
    }

    public function transition(HourlyRentalOrder $order, string $status, string $actorType, ?int $actorId, ?string $reason = null, bool $closeUnpaid = false): void
    {
        // Finishing the last visit does not close an order that still owes money.
        if ($status === 'completed' && !$closeUnpaid && self::owes($order)) {
            $status = self::AWAITING_PAYMENT;
            $reason = trim(($reason ?? '').' — بانتظار سداد المتبقي');
        }
        $from = $order->status;
        if ($from === $status) return;
        $order->update(['status'=>$status]);
        $this->log($order, $from, $status, $actorType, $actorId, $reason);
        // هذه الدالة هي المعبر الوحيد لكل تغييرات حالة الطلب، فإشعار العميل هنا
        // يغطي مسارات التفعيل الستة والإلغاء والإكمال وانتهاء الصلاحية معاً.
        // afterCommit لأن أغلب المستدعين داخل معاملة، ولا يصح إشعار بتغيير قد يُرجَع.
        $orderId=(int)$order->id;
        DB::afterCommit(fn()=>app(CustomerTrackingPushService::class)->orderStatus($orderId,$status));
    }

    private function log(HourlyRentalOrder $order, ?string $from, string $to, string $actor, ?int $id, ?string $reason): void
    {
        $order->statusLogs()->create(['from_status'=>$from,'to_status'=>$to,'actor_type'=>$actor,'actor_id'=>$id,'reason'=>$reason]);
    }
}
