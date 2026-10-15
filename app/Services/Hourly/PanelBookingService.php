<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder};
use App\Models\ingaz\{User, UserAddress};
use Illuminate\Support\Facades\DB;

class PanelBookingService
{
    public function quote(HourlyPackage $package, array $data, bool $lock = false): array
    {
        if ((int)$data['workers_per_visit'] < (int)$package->workers_per_visit) HourlyOrderService::reject('عدد الموظفين أقل من الحد الأدنى لعنصر الخدمة.');
        app(HourlyBookingOptions::class)->resolve($package, $data);
        $quote = app(HourlyBookingRules::class)->quote($package, (int) $data['workers_per_visit'], (int) ($data['quantity'] ?? 1), true);
        $quote['original_total'] = $quote['total'];
        if (isset($data['manual_total'])) $quote['total'] = (int) round($data['manual_total'] * 100);
        $quote['components']['adjustment'] = $quote['total'] - $quote['original_total'];
        $quote['discount'] = 0;
        $quote['coupon_id'] = null;
        if (!empty($data['coupon_code'])) {
            $query = DB::table('hourly_coupons')->where('code', strtoupper(trim($data['coupon_code'])));
            $coupon = ($lock ? $query->lockForUpdate() : $query)->first();
            if (!$coupon || !$coupon->is_active || ($coupon->starts_at && now()->lt($coupon->starts_at))
                || ($coupon->expires_at && now()->gt($coupon->expires_at))
                || ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit)
                || ($coupon->service_category_id && $coupon->service_category_id != $package->service_category_id)
                || $quote['total'] < round($coupon->minimum_amount * 100)
                || !in_array($coupon->type, ['fixed','percent']) || $coupon->value <= 0) {
                HourlyOrderService::reject('الكوبون غير صالح لهذا الطلب أو انتهت صلاحيته.');
            }
            $discount = $coupon->type === 'percent' ? round($quote['total'] * min(100,$coupon->value) / 100) : round($coupon->value * 100);
            $quote['discount'] = min($quote['total'], (int) $discount);
            $quote['total'] -= $quote['discount'];
            $quote['coupon_id'] = $coupon->id;
        }
        $quote['subtotal'] = (int) round($quote['total'] / (1 + $quote['tax_rate'] / 100));
        $quote['components']['discount'] = -$quote['discount'];
        $quote['tax'] = $quote['total'] - $quote['subtotal'];
        $count = (int) $package->visits_count;
        $quote['visit_prices'] = array_map(fn ($i) => intdiv($quote['total'], $count) + ($i < $quote['total'] % $count ? 1 : 0), range(0,$count-1));
        $quote['charge_now'] = $package->payment_policy==='prepaid'?$quote['total']:($package->payment_policy==='installments'?min($quote['total'],(int)round(($package->deposit_amount??0)*100)):($package->payment_policy==='per_visit' || $quote['price_display']==='per_visit'?$quote['visit_prices'][0]:$quote['total']));
        $quote['presentation'] = app(HourlyBookingOptions::class)->presentation($package, $quote, 'panel');
        return $quote;
    }

    public function coverage(HourlyPackage $package, array $address): array
    {
        if (!\App\Models\City::saudi()->active()->whereKey($address['city_id'] ?? null)->exists()) {
            HourlyOrderService::reject('يرجى اختيار مدينة سعودية مفعّلة للخدمة.');
        }
        $category = app(HourlyBookingRules::class)->category($package);
        if (!$package->is_active || !$category || !$category->is_active || $category->trashed()
            || !$package->effectiveCities()->where('cities.id',$address['city_id'])->where('is_active',true)->exists()
            || !app(ProductAttributeService::class)->available($package)) HourlyOrderService::reject('الخدمة غير متاحة لهذا العنوان.');
        return app(HourlyBookingRules::class)->assertDistrictCovered($category, (int)$address['city_id'], $address['latitude'] ?? null, $address['longitude'] ?? null, $address['district'] ?? null, $address['hourly_district_id'] ?? null);
    }

    public function candidates(HourlyPackage $package, array $address, bool $allTeams = false, bool $lock = false)
    {
        [, $link] = $this->coverage($package, $address);
        $query = app(HourlyWorkerAssignment::class)->candidates($package, $link->team_id ? (int)$link->team_id : null,
            (bool)app(HourlyBookingRules::class)->settings()->value($package,'assign_by_product_attribute'), $allTeams)->orderBy('maids.id');
        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    public function create(array $data, int $adminId): HourlyRentalOrder
    {
        return DB::transaction(function () use ($data, $adminId) {
            // Serialize repeated submissions for this dispatcher before checking the unique token.
            DB::table('admins')->where('id',$adminId)->lockForUpdate()->first();
            $existing = HourlyRentalOrder::where('booking_token',$data['booking_token'])->first();
            if ($existing) { abort_unless((int)$existing->created_by_admin_id === $adminId,403); return $existing; }
            if ($data['customer_mode'] === 'new') {
                $customer = $data['customer'];
                $user = User::create([
                    'name'=>$customer['type']==='company' ? $customer['company_name'] : $customer['first_name'].' '.$customer['last_name'], 'phone'=>$customer['phone'], 'phone_code'=>$customer['phone_code'] ?? '966',
                    'email'=>$customer['email'], 'password'=>bcrypt($customer['password']), 'register_from'=>'admin',
                    'hourly_customer_profile'=>json_encode(collect($customer)->except(['password','email','phone','phone_code'])->all(),JSON_UNESCAPED_UNICODE),
                ]);
                // ملف «عملاء الساعات» (قائمة العملاء وتصنيفها) يُنشأ مع العميل الجديد؛ الهوية والميلاد في حقول users المشتركة.
                if (\Illuminate\Support\Facades\Schema::hasTable('hourly_customer_profiles')) {
                    $user->update(['national_num'=>$customer['identity'] ?? null,'birthdate'=>$customer['birthdate'] ?? null]);
                    \App\Models\Hourly\HourlyCustomerProfile::create(['user_id'=>$user->id,'customer_type'=>$customer['type'],
                        'first_name'=>$customer['first_name'] ?? null,'last_name'=>$customer['last_name'] ?? null,'company_name'=>$customer['company_name'] ?? null,
                        'company_id'=>$customer['company_id'] ?? null,'identity_type'=>!empty($customer['identity']) ? 'national_id' : null,
                        'language'=>$customer['language'] ?? 'ar','note'=>$customer['note'] ?? null]);
                }
            } else $user = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            if ($user->is_blocked) HourlyOrderService::reject('حساب العميل موقوف.');
            if ($data['address_mode'] === 'new') {
                $addressData = $data['address'];
                $district = \App\Models\Hourly\ServiceDistrict::where('city_id',$addressData['city_id'])->findOrFail($addressData['hourly_district_id']);
                $address = UserAddress::createUnique($addressData + ['user_id'=>$user->id, 'district'=>$district->name,
                    'name'=>$addressData['contact_first_name'].' '.$addressData['contact_last_name'],
                    'phone'=>$addressData['contact_phone'],'title'=>$addressData['building']]);
            } else $address = UserAddress::where('user_id',$user->id)->findOrFail($data['address_id']);
            $package = HourlyPackage::whereKey($data['hourly_package_id'])->lockForUpdate()->firstOrFail();
            [$district,$link] = $this->coverage($package,$address->toArray());
            DB::table('hourly_teams')->where('id',$link->team_id)->lockForUpdate()->first();
            $quote = $this->quote($package,$data,true);
            $candidates = $this->candidates($package,$address->toArray(),(bool)($data['all_workers'] ?? false),true);
            $dates = array_column($data['visits'],'starts_at');
            $source = !empty($data['renewed_from_order_id']) ? $this->renewalSource((int)$data['renewed_from_order_id'], $user->id, (bool)($data['allow_renew_again'] ?? false)) : null;
            if (count($dates) !== (int)$package->visits_count) HourlyOrderService::reject('حدد موعدًا لكل زيارة من زيارات الباقة.');
            $order = app(HourlyOrderService::class)->create($user->id, [
                'hourly_package_id'=>$package->id, 'address_id'=>$address->id, 'district_id'=>$district->id,
                'workers_per_visit'=>$data['workers_per_visit'], 'payment_method'=>$data['payment_method'],
                'scheduled_starts'=>$dates, 'notes'=>$data['notes'] ?? null, 'panel_assignment'=>true,
                'quantity'=>$data['quantity'] ?? 1,
                'bring_materials'=>$data['bring_materials'] ?? null, 'service_type'=>$data['service_type'] ?? null,
            ],$adminId);
            $snapshot = $order->package_snapshot;
            $snapshot['charge_now'] = $quote['charge_now']/100;
            $snapshot['pricing_components'] = $quote['components'];
            $snapshot['original_total'] = $quote['original_total']/100;
            $snapshot['manual_total'] = $data['manual_total'] ?? null;
            // مدة الدفع من ملف العميل إن حُددت: «مقدم» يطلب الإجمالي قبل التفعيل، والمؤجلة لا تشترط سدادًا قبل التفعيل.
            // بلا مدة يبقى charge_now كما حسبه quote (منطق الدفع الحالي دون تغيير).
            $term = \App\Models\Hourly\HourlyCustomerProfile::termFor($user->id);
            if ($term && empty($snapshot['payment_policy'])) $snapshot['charge_now'] = $term === 'prepaid' ? $quote['total']/100 : 0;
            $order->update(['booking_token'=>$data['booking_token'],'coupon_code'=>$data['coupon_code'] ?? null,
                'booking_metadata'=>['notify_email'=>(bool)($data['notify_email'] ?? false),'notify_app'=>(bool)($data['notify_app'] ?? false),'all_workers'=>(bool)($data['all_workers'] ?? false)]
                    + ($term ? ['payment_term'=>$term,'payment_due_date'=>\App\Models\Hourly\HourlyCustomerProfile::dueDate($term,min($dates))] : []),
                'package_snapshot'=>$snapshot,'total_amount'=>$quote['total']/100,'subtotal'=>$quote['subtotal']/100,
                'tax_amount'=>$quote['tax']/100,'discount_amount'=>$quote['discount']/100]);
            app(PaymentScheduleService::class)->generateInstallments($order,true);
            foreach ($order->visits()->orderBy('visit_number')->get() as $i=>$visit) {
                $selection = $data['visits'][$i];
                $ids = array_values(array_filter($selection['worker_ids'] ?? []));
                if (count($ids) !== count(array_unique($ids)) || count($selection['worker_ids'] ?? []) !== (int)$data['workers_per_visit']) HourlyOrderService::reject('اختر العاملة أو تخطي التعيين لكل مقدم خدمة، بدون تكرار العاملة.');
                $available = $candidates->filter(fn ($maid) => app(HourlyWorkerAssignment::class)->available($maid,$visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$snapshot));
                // Unassigned bookings still reserve capacity in their service team and interval.
                $unassigned = $this->unassignedCapacity($link->team_id, $visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->id);
                $when = 'الزيارة '.($i + 1).' ('.$visit->scheduled_start_at->format('Y-m-d H:i').')';
                if ($available->count() - $unassigned < (int)$data['workers_per_visit']) HourlyOrderService::reject('لم يعد العدد المطلوب متاحًا في موعد '.$when.'. ارجع لخطوة المواعيد واختر لها موعدًا آخر.');
                foreach ($ids as $id) if (!$available->contains('id',(int)$id)) HourlyOrderService::reject('العاملة «'.($candidates->firstWhere('id',(int)$id)?->name ?? \App\Models\ingaz\Maid::find($id)?->name ?? '#'.$id).'» لم تعد متاحة في '.$when.'. اختر عاملة أخرى أو تخطَّ التعيين.');
                if (app(HourlyBookingRules::class)->settings()->value($package, 'auto_assign', 'panel')) {
                    $ids = array_merge($ids, $available->whereNotIn('id', $ids)
                        ->take((int) $data['workers_per_visit'] - count($ids))->pluck('id')->all());
                }
                $visit->workers()->sync($ids);
                // Reserve anonymous capacity without displaying it as an assigned worker.
                foreach ($available->whereNotIn('id',$ids)->take((int)$data['workers_per_visit']-count($ids)) as $maid) {
                    DB::table('hourly_visit_reservations')->insert(['hourly_visit_id'=>$visit->id,'maid_id'=>$maid->id,'created_at'=>now(),'updated_at'=>now()]);
                }
                $visit->update(['hourly_team_id'=>$link->team_id,'visit_price'=>$quote['visit_prices'][$i]/100,
                    'delivery_driver_id'=>$selection['delivery_driver_id'] ?? null,'pickup_driver_id'=>$selection['pickup_driver_id'] ?? null]);
                if (app(HourlyBookingRules::class)->settings()->value($package,'auto_assign_driver','panel')) app(NearestDriverAssignment::class)->assign($visit,false);
                $visitId=(int)$visit->id;
                DB::afterCommit(fn()=>app(StaffPushService::class)->assignmentChanged($visitId,[
                    'worker_ids'=>[],'delivery_driver_id'=>null,'pickup_driver_id'=>null,
                ]));
            }
            if ($quote['coupon_id']) DB::table('hourly_coupons')->where('id',$quote['coupon_id'])->increment('used_count');
            if ($data['payment_method'] === 'customer_balance') {
                $balance = (int)round($user->hourly_wallet_balance*100);
                if ($balance < $quote['total']) HourlyOrderService::reject('رصيد العميل غير كافٍ.');
                $user->update(['hourly_wallet_balance'=>($balance-$quote['total'])/100]);
                DB::table('hourly_wallet_transactions')->insert(['user_id'=>$user->id,'hourly_rental_order_id'=>$order->id,'amount'=>-$quote['total']/100,'balance_after'=>($balance-$quote['total'])/100,'admin_id'=>$adminId,'created_at'=>now(),'updated_at'=>now()]);
                if ($quote['total'] > 0) app(HourlyPaymentService::class)->collect($order,['amount'=>$quote['total']/100],$adminId);
            } elseif (!empty($data['collect_now'])) {
                $collectNow=app(PaymentScheduleService::class)->policy($order)==='legacy'?$quote['total']:(int)round(app(PaymentScheduleService::class)->forOrder($order->fresh())['due_now']*100);
                if($quote['total']>0 && $collectNow<=0)HourlyOrderService::reject('لا توجد دفعة مستحقة الآن؛ التحصيل عند موعد الزيارة أو القسط.');
                if (!in_array($data['payment_method'],['cash','card_on_delivery','cash_and_card'],true)) HourlyOrderService::reject('التحصيل الفوري متاح للنقدي والبطاقة فقط.');
                if ($data['payment_method']==='cash_and_card' && $collectNow>0) {
                    $cash=(int)round(($data['cash_amount'] ?? 0)*100);
                    if ($cash<=0 || $cash>=$collectNow) HourlyOrderService::reject('يجب أن يكون الجزء النقدي أكبر من صفر وأقل من الإجمالي.');
                    app(HourlyPaymentService::class)->collect($order,['amount'=>$cash/100,'method'=>'cash'],$adminId);
                    app(HourlyPaymentService::class)->collect($order,['amount'=>($collectNow-$cash)/100,'method'=>'card_on_delivery'],$adminId);
                } elseif ($collectNow > 0) app(HourlyPaymentService::class)->collect($order,['amount'=>$collectNow/100],$adminId);
            }
            if (!$quote['total']) {
                $order->update(['payment_status'=>'paid']);
                app(HourlyOrderService::class)->transition($order,'active','admin',$adminId,'طلب بدون مبلغ مستحق');
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('hourly_rental_orders','initial_amount')) $order->update(['initial_amount'=>$order->total_amount]);
            if ($source) $this->linkRenewal($source, $order, $adminId);
            return $order;
        });
    }

    /**
     * الطلب الأصلي للتجديد: يُقفل حتى لا يُجدَّد مرتين بالتوازي، ويجب أن يخص نفس العميل وألا يكون ملغى،
     * وألا يكون له تجديد قائم إلا بصلاحية hourly_orders_renew_again.
     */
    private function renewalSource(int $sourceId, int $userId, bool $allowAgain): HourlyRentalOrder
    {
        $source = HourlyRentalOrder::whereKey($sourceId)->lockForUpdate()->first();
        if (!$source || (int)$source->user_id !== $userId) HourlyOrderService::reject('طلب التجديد لا يخص العميل المختار.');
        if ($source->status === 'cancelled') HourlyOrderService::reject('لا يمكن تجديد طلب ملغى.');
        if($reason=app(RenewalEligibility::class)->reason($source))HourlyOrderService::reject($reason);
        $existing = $source->renewedBy()->first();
        if ($existing && !$allowAgain) HourlyOrderService::reject('هذا الطلب جُدِّد سابقًا بالطلب #'.$existing->order_number.'. تجديده مرة أخرى يتطلب صلاحية «تجديد طلب سبق تجديده».');
        return $source;
    }

    /** يربط الطلب الجديد بأصله ويُسجّل ذلك في سجلات الطلبين، ثم يُحدّث عداد التجديدات. */
    private function linkRenewal(HourlyRentalOrder $source, HourlyRentalOrder $order, int $adminId): void
    {
        $previous = $source->renewedBy()->where('id', '!=', $order->id)->first();
        $order->update(['renewed_from_order_id' => $source->id]);
        $audit = app(OrderManagementService::class);
        $audit->audit($order, 'renewed_from', [], ['source_order_id' => $source->id, 'source_order_number' => $source->order_number], $adminId, null,
            'تجديد للطلب #'.$source->order_number);
        $audit->audit($source, 'renewed_to', $previous ? ['renewal_order_id' => $previous->id, 'renewal_order_number' => $previous->order_number] : [],
            ['renewal_order_id' => $order->id, 'renewal_order_number' => $order->order_number], $adminId, null,
            ($previous ? 'تجديد إضافي (سبق تجديده بالطلب #'.$previous->order_number.') — ' : '').'تم التجديد بالطلب #'.$order->order_number);
        DB::afterCommit(fn () => app(RenewalCounter::class)->forget());
    }

    public function unassignedCapacity(?int $teamId, string $start, string $end, ?int $exceptOrder = null): int
    {
        $visits = \App\Models\Hourly\HourlyVisit::where('hourly_team_id',$teamId)
            ->whereNotIn('status',['cancelled','expired'])->whereHas('order',fn ($q)=>$q->whereNotIn('status',['cancelled','expired']))
            ->when($exceptOrder,fn ($q)=>$q->where('hourly_rental_order_id','!=',$exceptOrder))
            ->where('scheduled_start_at','<',$end)->where('scheduled_end_at','>',$start)->withCount('workers as assigned_count')->get();
        return $visits->sum(fn ($v)=>max(0,$v->workers_count-$v->assigned_count-DB::table('hourly_visit_reservations')->where('hourly_visit_id',$v->id)->count()));
    }
}
