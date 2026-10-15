<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory, HourlyTeam, HourlyTeamArea, ServiceDistrict};
use Illuminate\Support\Facades\{DB, Schema};

/**
 * قواعد الحجز الموحدة لطبقة الفئات. كل من حساب التوفر والسعر وإنشاء الطلب يمر من هنا،
 * فلا تُطبَّق القواعد في الواجهة وحدها.
 *
 * التوريث: إعداد العنصر > إعداد الفئة > الإعداد العام (null = يرث من الأعلى).
 */
class HourlyBookingRules
{
    /** فئة العنصر إن كانت طبقة الفئات مهيأة (تُتجاهل في قواعد البيانات التي لم تُرحَّل بعد). */
    public function category(HourlyPackage $item): ?HourlyServiceCategory
    {
        if (!$item->service_category_id || !Schema::hasTable('hourly_service_categories')) return null;
        return $item->relationLoaded('serviceCategory') ? $item->serviceCategory : $item->serviceCategory()->first();
    }

    public function settings(): HourlyCategorySettings
    {
        return $this->settings ??= new HourlyCategorySettings();
    }
    private ?HourlyCategorySettings $settings = null;

    public static function channel(bool $panel): string { return $panel ? 'panel' : 'web_app'; }

    public function defaultCounts(HourlyPackage $item, string $channel): array
    {
        $steppers = $this->settings()->steppers($item, $channel);
        return ['workers' => max((int) $item->workers_per_visit, (int) ($steppers['workers']['min'] ?? 1)),
            'quantity' => (int) ($steppers['quantity']['min'] ?? 1)];
    }

    // ------------------------------------------------------------------ التقويم وأيام العمل

    /**
     * نافذة الحجز لليوم: null إن كان اليوم خارج المسموح، وإلا [ignore_notice, buffer_hours].
     * - أيام العمل (is_on) تُتجاهل للعناصر التي فيها ignore_closed_days.
     * - موظف التشغيل: dispatcher_future_days للأمام وdispatcher_past_days للخلف (الماضي يتجاوز مهلة الحجز).
     * - العميل: لا ماضٍ، وcustomer_future_days للأمام، وsame_day_cutoff لحجز اليوم نفسه، وorder_buffer_hours لليوم.
     */
    public function calendarWindow(array $item, string $date, bool $dispatcher): ?array
    {
        if (empty($item['service_category_id'])) return ['ignore_notice' => false, 'buffer_hours' => 0];
        $s = $this->settings();
        $day = \Carbon\Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();
        $workingDay = $s->value($item, 'working_days')[$day->dayOfWeek];
        if (empty($item['ignore_closed_days']) && !$workingDay['is_on']) return null;

        if ($dispatcher) {
            if ($day->gt($today->copy()->addDays($s->value($item, 'dispatcher_future_days')))) return null;
            if ($day->lt($today)) {
                return $today->diffInDays($day) <= $s->value($item, 'dispatcher_past_days') ? ['ignore_notice' => true, 'buffer_hours' => 0] : null;
            }
            return ['ignore_notice' => false, 'buffer_hours' => 0];
        }
        if ($day->lt($today) || $day->gt($today->copy()->addDays($s->value($item, 'customer_future_days')))) return null;
        $cutoff = $s->value($item, 'same_day_cutoff');
        if ($day->eq($today) && is_string($cutoff) && preg_match('/^\d{2}:\d{2}$/', $cutoff) && now()->format('H:i') >= $cutoff) return null;
        return ['ignore_notice' => false, 'buffer_hours' => (int) $workingDay['order_buffer_hours']];
    }

    /** مهلة اليوم (order_buffer_hours) تحذف المواعيد التي تبدأ قبل الآن + المهلة. */
    public function withBuffer(array $slots, int $bufferHours): array
    {
        if ($bufferHours <= 0) return $slots;
        $earliest = now()->addHours($bufferHours);
        return array_values(array_filter($slots, fn ($slot) => \Carbon\Carbon::parse($slot['starts_at'])->gte($earliest)));
    }

    // ------------------------------------------------------------------ القنوات وسياسة المواعيد

    /** هل يُعرض العنصر للعميل؟ (show_packages للباقات متعددة الزيارات وshow_one_off للزيارة الواحدة) */
    public function visibleToCustomers($item): bool
    {
        if($item instanceof HourlyPackage && !app(ProductAttributeService::class)->available($item))return false;
        if($item instanceof HourlyPackage && \Illuminate\Support\Facades\Schema::hasTable('hourly_package_tiers')){
            $tier=$item->package_tier_id ? $item->packageTier : \App\Models\Hourly\HourlyPackageTier::where('code','one_off')->first();
            if(!$tier || !$tier->is_active)return false;
            return (bool)$this->settings()->value($item,$tier->frequency==='one_off'?'show_one_off':'show_packages');
        }
        $visits = (int) ($item instanceof HourlyPackage ? $item->visits_count : ($item['visits_count'] ?? 1));
        return (bool) $this->settings()->value($item, $visits > 1 ? 'show_packages' : 'show_one_off');
    }

    /** اختيار العميل لتواريخ الجلسات وتعدد الجلسات في اليوم. */
    public function assertSessionDates($item, array $dates, bool $panel): void
    {
        if (!$dates) return;
        $s = $this->settings();
        if (!$panel && count($dates) > 1 && !$s->value($item, 'handpick_session_dates')) {
            HourlyOrderService::reject('اختيار مواعيد كل الجلسات غير متاح في هذه الفئة؛ اختر موعد الجلسة الأولى فقط.');
        }
        if (!$s->value($item, 'allow_multiple_sessions_per_day')) {
            $days = array_map(fn ($d) => substr($d, 0, 10), $dates);
            if (count($days) !== count(array_unique($days))) HourlyOrderService::reject('لا يُسمح بأكثر من جلسة في اليوم نفسه في هذه الفئة.');
        }
    }

    // ------------------------------------------------------------------ طرق الدفع

    /** القيم العامة التي يفهمها تطبيق العميل حرفيًا. */
    public const PAYMENT_METHODS = ['moyasar', 'wallet', 'bank_transfer', 'cash'];
    public const PANEL_PAYMENT_METHODS = ['cash', 'card_on_delivery', 'cash_and_card', 'bank_transfer', 'cheque', 'customer_balance'];
    public const ALL_PAYMENT_METHODS = ['moyasar', 'wallet', 'cash', 'card_on_delivery', 'bank_transfer', 'cash_and_card', 'cheque', 'customer_balance'];

    /**
     * الطرق المسموحة للفئة في قناة: web_app (التطبيق/الموقع) أو employee (لوحة الموظف) أو branch.
     * فئة بلا إعداد دفع ترث الطرق الآمنة، أما المحفظة فلا تعمل قبل تفعيلها صراحةً.
     */
    public function paymentMethods($item, string $channel): array
    {
        $categoryId = $item instanceof HourlyPackage ? $item->service_category_id : ($item['service_category_id'] ?? null);
        $methods = $channel === 'web_app' ? self::PAYMENT_METHODS : array_values(array_unique(array_merge(self::PAYMENT_METHODS, self::PANEL_PAYMENT_METHODS)));
        $policy=$item instanceof HourlyPackage?$item->payment_policy:($item['payment_policy']??null);
        if(in_array($policy,['per_visit','installments'],true))$methods=array_values(array_diff($methods,['wallet','customer_balance']));
        if (!$categoryId || !Schema::hasTable('hourly_category_payment_methods')) return array_values(array_filter($methods, fn ($method) => $method !== 'wallet'));
        $rows = DB::table('hourly_category_payment_methods')->where('service_category_id', $categoryId)->get()->keyBy('payment_method');
        // المحفظة ميزة مالية حساسة، لذلك تبدأ متوقفة حتى تُفعّل صراحةً للفئة.
        return array_values(array_filter($methods, fn ($method) => isset($rows[$method])
            ? (bool) $rows[$method]->{$channel}
            : $method !== 'wallet'));
    }

    public function assertPaymentMethod($item, string $method, bool $panel, string $panelChannel = 'employee'): void
    {
        if (!in_array($method, $this->paymentMethods($item, $panel ? $panelChannel : 'web_app'), true)) HourlyOrderService::reject('طريقة الدفع غير متاحة.');
    }

    // ------------------------------------------------------------------ السعر

    /**
     * حساب السعر الموحد بالهللات. يطبق ضريبة الفئة الموروثة، ومراحل العاملات والكمية (المدى وتدرج السعر)،
     * وطريقة عرض سعر الباقة (per_visit: المستحق الآن هو سعر الزيارة الأولى).
     */
    public function quote(HourlyPackage $item, int $workers, int $quantity, bool $panel): array
    {
        $s = $this->settings();
        $channel = self::channel($panel);
        $steppers = $s->steppers($item, $channel);
        foreach (['workers' => $workers, 'quantity' => $quantity] as $key => $value) {
            if (!isset($steppers[$key])) continue;
            if ($value < $steppers[$key]['min'] || $value > $steppers[$key]['max']) {
                HourlyOrderService::reject(($steppers[$key]['label'] ?: $key).' يجب أن يكون بين '.$steppers[$key]['min'].' و'.$steppers[$key]['max'].'.');
            }
        }
        if ($quantity !== 1 && !isset($steppers['quantity'])) HourlyOrderService::reject('اختيار الكمية غير متاح في هذه الفئة.');

        $cents = fn ($v) => (int) round((float) $v * 100);
        $workerExtra = isset($steppers['workers']) && !$steppers['workers']['scale_price'] ? 0 : ($workers - $item->workers_per_visit) * $cents($item->extra_worker_price);
        $extras = $workerExtra + ($item->transport_included ? 0 : $cents($item->transport_fee));
        $visits = (int) $item->visits_count;
        $baseTotal = $item->total_price !== null ? $cents($item->total_price) : $cents($item->price_per_visit) * $visits;
        $baseTotal+=app(ProductAttributeService::class)->modifier($item);
        if($baseTotal<1)HourlyOrderService::reject('السعر الناتج بعد تعديل السمة يجب أن يكون موجبًا.');
        $multiplier = isset($steppers['quantity']) && $steppers['quantity']['scale_price'] ? $quantity : 1;

        $visitPrices = [];
        for ($i = 1; $i <= $visits; $i++) {
            $visitPrices[] = (intdiv($baseTotal, $visits) + ($i <= $baseTotal % $visits ? 1 : 0) + $extras) * $multiplier;
        }
        $total = array_sum($visitPrices);
        $tax = (float) $s->value($item, 'tax_rate');
        $subtotal = (int) round($total / (1 + $tax / 100));
        $display = $s->value($item, 'package_price_display');

        return [
            'tax_rate' => $tax, 'total' => $total, 'subtotal' => $subtotal, 'tax' => $total - $subtotal,
            'visit_prices' => $visitPrices, 'quantity' => $quantity, 'workers' => $workers,
            'price_display' => $display,
            'payment_policy' => $item->payment_policy ?: null,
            'installment_deposit' => $cents($item->deposit_amount??0), 'installment_monthly' => $cents($item->monthly_amount??0),
            'components' => ['service' => $baseTotal * $multiplier,
                'extra_workers' => $workerExtra * $visits * $multiplier,
                'transport' => ($item->transport_included ? 0 : $cents($item->transport_fee)) * $visits * $multiplier],
            // الدفع الإلكتروني مع عرض سعر الزيارة: يُحصَّل سعر الزيارة الأولى الآن.
            'charge_now' => $item->payment_policy==='prepaid'?$total:($item->payment_policy==='installments'?min($total,$cents($item->deposit_amount??0)):($item->payment_policy==='per_visit' || $display==='per_visit'?$visitPrices[0]:$total)),
        ];
    }

    // ------------------------------------------------------------------ الإغلاقات المؤقتة

    /**
     * يحذف المواعيد المتقاطعة مع إغلاق مؤقت للفئة. $item مصفوفة العنصر (أو لقطة الطلب) وفيها
     * service_category_id وignore_closed_days واختياريًا service_district_id.
     */
    public function withoutClosures(array $item, array $slots): array
    {
        if (!$slots || !empty($item['ignore_closed_days']) || empty($item['service_category_id']) || !Schema::hasTable('hourly_category_closures')) return $slots;
        $from = min(array_column($slots, 'starts_at'));
        $to = max(array_column($slots, 'ends_at'));
        $district = isset($item['service_district_id']) ? (int) $item['service_district_id'] : null;
        $closures = \App\Models\Hourly\HourlyCategoryClosure::where('service_category_id', $item['service_category_id'])
            ->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get()
            ->filter(fn ($closure) => $closure->coversDistrict($district));
        if ($closures->isEmpty()) return $slots;
        return array_values(array_filter($slots, fn ($slot) => !$closures->contains(
            fn ($closure) => $closure->starts_at->lt(\Carbon\Carbon::parse($slot['ends_at'])) && $closure->ends_at->gt(\Carbon\Carbon::parse($slot['starts_at']))
        )));
    }

    // ------------------------------------------------------------------ مناطق الخدمة

    /** هل عرّفت الفئة مناطق خدمة؟ فئة بلا مناطق تبقى على نطاق المدن القديم حتى لا يتوقف الحجز بعد الترحيل. */
    public function hasCoverage(HourlyServiceCategory $category): bool
    {
        return Schema::hasTable('hourly_category_district') && DB::table('hourly_category_district')->where('service_category_id', $category->id)->exists();
    }

    /**
     * يحدد حي العنوان: المعرّف الصريح (ضمن مدينة العنوان)، ثم وقوع الإحداثيات داخل حدود الحي، ثم مطابقة الاسم.
     */
    public function resolveDistrict(int $cityId, ?float $lat, ?float $lng, ?string $districtName, ?int $districtId = null): ?ServiceDistrict
    {
        if ($districtId) return ServiceDistrict::where('city_id', $cityId)->find($districtId);
        $districts = ServiceDistrict::where('city_id', $cityId)->get();
        if ($lat !== null && $lng !== null) {
            $inside = $districts->first(fn ($d) => is_array($d->polygon) && HourlyTeamArea::pointInPolygon($lat, $lng, $d->polygon));
            if ($inside) return $inside;
        }
        $name = mb_strtolower(trim((string) $districtName));
        if ($name === '') return null;
        $strip = fn ($v) => preg_replace('/^(حي|district)\s+/u', '', mb_strtolower(trim((string) $v)));
        return $districts->first(fn ($d) => in_array($strip($name), [$strip($d->name), $strip($d->name_en)], true));
    }

    /**
     * ارتباط الحي بالفئة، أو رفض الحجز. يعيد [district, pivot] أو [null, null] إن لم تعرّف الفئة مناطق.
     * @return array{0: ?ServiceDistrict, 1: ?object}
     */
    public function assertDistrictCovered(HourlyServiceCategory $category, int $cityId, ?float $lat, ?float $lng, ?string $districtName, ?int $districtId = null): array
    {
        if(!Schema::hasTable('hourly_category_district'))return [null,null];
        if (!$this->hasCoverage($category)) HourlyOrderService::reject('لا توجد مناطق خدمة مفعلة لهذه الفئة.');
        $district = $this->resolveDistrict($cityId, $lat, $lng, $districtName, $districtId);
        if (!$district) HourlyOrderService::reject('تعذر تحديد حي العنوان. اختر الحي من القائمة.');
        $link = DB::table('hourly_category_district')->where('service_category_id', $category->id)->where('district_id', $district->id)->first();
        if (!$link) HourlyOrderService::reject('الحي «'.$district->name.'» خارج مناطق خدمة هذه الفئة.');
        if (!$link->is_active || !$district->is_active) HourlyOrderService::reject('الخدمة متوقفة مؤقتًا في حي «'.$district->name.'».');
        // An explicit category team takes precedence; otherwise use the district's active team.
        // Resolve at booking time so removing or disabling coverage takes effect immediately.
        if (!$link->team_id || !HourlyTeam::query()->whereKey($link->team_id)->where('is_active', true)->exists()) {
            $link->team_id = $district->teams()->where('hourly_teams.is_active', true)
                ->wherePivot('is_active', true)->orderBy('hourly_teams.id')->value('hourly_teams.id');
        }
        return [$district, $link];
    }
}
