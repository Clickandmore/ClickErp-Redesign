<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/** Customer-facing fields; never serialize operational notes or gateway payloads. */
class CustomerHourlyData
{
    /** حالة الزيارة كما تُعرض للعميل؛ التنفيذ الميداني أدق من أن يُعرض له كما هو. */
    public const VISIT_DISPLAY = [
        'unscheduled' => 'unscheduled', 'scheduled' => 'scheduled', 'en_route' => 'on_the_way',
        'delivered' => 'in_progress', 'in_progress' => 'in_progress', 'worker_finished' => 'completed',
        'picked_up' => 'completed', 'completed' => 'completed', 'cancelled' => 'cancelled',
        'cancelled_by_client' => 'cancelled', 'cancelled_by_admin' => 'cancelled', 'expired' => 'expired', 'no_show' => 'no_show',
    ];

    /** تقييمات كل طلب تُقرأ استعلامًا واحدًا لكل طلب، فلا يتضاعف الاستعلام بعدد الزيارات. */
    private array $ratings = [];

    public function package(HourlyPackage $package, ?int $workers = null, ?int $quantity = null): array
    {
        $rules = app(HourlyBookingRules::class);
        $defaults = $rules->defaultCounts($package, 'web_app');
        $workers ??= $defaults['workers'];
        $quantity ??= $defaults['quantity'];
        if ($workers < $package->workers_per_visit) HourlyOrderService::reject('عدد العاملات أقل من عدد الباقة.');
        $quote = $rules->quote($package, $workers, $quantity, false);
        $pricing = $this->pricing($quote);
        $presentation = app(HourlyBookingOptions::class)->presentation($package, $quote, 'web_app');
        $pricing['presentation'] = $this->presentationInRiyals($presentation);
        $translation = $package->translations->firstWhere('locale', app()->getLocale()) ?? $package->translations->first();
        $category = $rules->category($package);
        return [
            'id' => $package->id, 'code' => $package->code,
            'title' => $translation?->title ?? $package->code, 'description' => $translation?->description,
            'service_category_id' => $package->service_category_id, 'category_id' => $package->service_category_id,
            'package_tier_id' => $package->package_tier_id,
            'attribute_id' => $package->attribute_id,
            'service' => $category ? ['id' => $category->id, 'name' => app()->getLocale() === 'en' ? ($category->name_en ?: $category->name_ar) : $category->name_ar, 'icon' => $category->icon, 'color' => $category->color] : ['name' => $package->category],
            'cities' => $package->effectiveCities()->get()->map(fn ($city) => ['id' => $city->id, 'name' => $city->name ?: $city->name_ar])->values(),
            'visits_count' => (int) $package->visits_count, 'hours_per_visit' => (float) $package->hours_per_visit,
            'total_hours' => (float) $package->hours_per_visit * $package->visits_count,
            'workers_per_visit' => (int) $package->workers_per_visit, 'validity_days' => (int) $package->validity_days,
            'extra_hour_price' => (float) $package->extra_hour_price,
            // Compatibility fields for app versions that read a flat price instead of pricing.display_amount.
            'price' => $pricing['display_amount'], 'total_price' => $pricing['total_amount'],
            'total_amount' => $pricing['total_amount'], 'price_per_visit' => $pricing['visit_prices'][0] ?? 0,
            'currency' => $pricing['currency'],
            'transport_included' => (bool) $package->transport_included, 'materials_included' => (bool) $package->materials_included,
            'pricing' => $pricing,
            'booking_config' => $category ? app(HourlyCategoryConfig::class)->forCategory($category, 'web_app', null, $package) : null,
            'cancellation_window_hours' => (int) ($package->cancellation_window_hours ?? HourlyRentalSettings::get('cancellation_window_hours')),
            'timezone' => config('app.timezone'),
        ];
    }

    public function pricing(array $quote): array
    {
        $policy=$quote['payment_policy']??($quote['price_display']==='per_visit'?'per_visit':'legacy');
        return ['currency' => 'SAR', 'unit' => 'SAR', 'tax_included' => true,
            'payment_policy'=>$policy,'payment_policy_label'=>PaymentScheduleService::LABELS[$policy],
            'installment_deposit'=>min($quote['total'],$quote['installment_deposit']??0)/100,'installment_monthly'=>($quote['installment_monthly']??0)/100,
            'subtotal' => $quote['subtotal'] / 100, 'tax_amount' => $quote['tax'] / 100,
            'tax_rate' => $quote['tax_rate'], 'total_amount' => $quote['total'] / 100,
            'charge_now' => $quote['charge_now'] / 100, 'price_display' => $quote['price_display'],
            'display_amount' => ($quote['price_display'] === 'per_visit' ? $quote['visit_prices'][0] : $quote['total']) / 100,
            'workers' => $quote['workers'], 'quantity' => $quote['quantity'],
            'visit_prices' => array_map(fn ($price) => $price / 100, $quote['visit_prices'])];
    }

    public function presentationInRiyals(array $presentation): array
    {
        foreach ($presentation['lines'] as &$line) $line['amount'] /= 100;
        unset($line);
        if ($presentation['tax_line']) $presentation['tax_line']['amount'] /= 100;
        return $presentation + ['currency' => 'SAR', 'unit' => 'SAR', 'tax_included' => true];
    }

    public function order(HourlyRentalOrder $order, bool $details = true): array
    {
        $order->loadMissing('visits');
        $counts = $order->visits->countBy('status');
        $consumed = ($counts['completed'] ?? 0) + ($counts['no_show'] ?? 0) + ($counts['cancelled_by_client'] ?? 0);
        $available = $counts['unscheduled'] ?? 0;
        $expired = $counts['expired'] ?? 0;
        $reserved = $order->visits->count() - $consumed - $available - $expired - ($counts['cancelled'] ?? 0);
        $remaining = $available + $reserved;
        $status = $order->status;
        if (in_array($status, ['active', 'pending_confirmation']) && $order->expires_at->lte(now())) $status = 'expired';
        elseif ($status === 'completed' || ($status === 'active' && $remaining === 0)) $status = 'exhausted';
        $snapshot = $order->package_snapshot;
        $translation = collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale());
        $data = $order->only(['id','order_number','hourly_package_id','status','payment_method','payment_status','visits_total','hours_per_visit','workers_per_visit','subtotal','tax_amount','tax_rate','total_amount','paid_amount']);
        foreach (['hours_per_visit','subtotal','tax_amount','tax_rate','total_amount','paid_amount'] as $field) $data[$field]=(float)$data[$field];
        foreach (['visits_total','workers_per_visit'] as $field) $data[$field]=(int)$data[$field];
        $data += ['order_id' => (int) $order->id,
            'booking_options' => $snapshot['booking_options'] ?? [],
            'quantity' => (int) ($snapshot['quantity'] ?? 1),
            'pricing_presentation' => $this->presentationInRiyals(app(HourlyBookingOptions::class)->presentation($snapshot,
                ['total' => (int) round($order->total_amount * 100), 'tax' => (int) round($order->tax_amount * 100),
                    'components' => $snapshot['pricing_components'] ?? ['service' => (int) round($order->total_amount * 100)]], 'web_app')),
            'package_title' => $translation['title'] ?? $snapshot['title'] ?? $snapshot['code'] ?? '',
            'display_status' => $status, 'currency' => 'SAR', 'timezone' => config('app.timezone'),
            'starts_at' => $order->starts_at?->toIso8601String(), 'expires_at' => $order->expires_at?->toIso8601String(),
            // لقوائم «حجوزاتي»: موعد الزيارة القادمة أهم للعميل من تاريخ إنشاء الطلب.
            'created_at' => $order->created_at?->toIso8601String(),
            'next_visit_at' => $order->nextVisit()?->scheduled_start_at?->toIso8601String(),
            'last_visit_at' => $order->lastVisit()?->scheduled_start_at?->toIso8601String(),
            'balance' => ['available' => $available, 'reserved' => $reserved, 'consumed' => $consumed, 'expired' => $expired],
            'remaining_visits' => $remaining, 'completed_visits' => $counts['completed'] ?? 0,
            'progress_percent' => $order->visits_total ? round($consumed / $order->visits_total * 100, 1) : 0,
            'address' => $order->only(['city_id','address_text','latitude','longitude','district','building','unit','contact_phone'])];
        $data['payment_schedule']=app(PaymentScheduleService::class)->forOrder($order);
        $data['refundable_amount'] = max(0,round((float)$order->paid_amount-(float)$order->total_amount,2));
        if ($details) $data['refunded_amount'] = -(float)$order->payments()->where('status','paid')->where('amount','<',0)->sum('amount');
        if ($details && CashCollectionService::ready()) $data['cash_receipts']=DB::table('hourly_cash_collections')->where('order_id',$order->id)->get(['id','payment_id','amount'])->map(fn($r)=>[
            'collection_id'=>(int)$r->id,'payment_id'=>(int)$r->payment_id,'amount'=>(float)$r->amount,'url'=>url('api/app/hourly/cash-receipts/'.$r->id)])->all();
        if ($details) $data['visits'] = $order->visits->sortBy('visit_number')->map(fn ($visit) => $this->visit($visit, $order))->values();
        return $data;
    }

    public function visit(HourlyVisit $visit, HourlyRentalOrder $order): array
    {
        $data = $visit->only(['id','visit_number','status','planned_hours','workers_count','visit_price','cancellation_fee']);
        foreach (['planned_hours','visit_price','cancellation_fee'] as $field) $data[$field]=(float)$data[$field];
        $settings=app(HourlyBookingRules::class)->settings();
        $snapshot=(array)$order->package_snapshot;
        $open=in_array($order->status,['active','pending_confirmation']) && $order->expires_at->isFuture();
        $data['financial']=app(PaymentScheduleService::class)->forOrder($order)['visits'][$visit->id]??null;
        $policy=app(CustomerVisitActions::class)->policy($visit,$order);
        $rating=$this->rating($visit,$order);
        return $data + ['hourly_rental_order_id' => $order->id,
            // Keep the stable status code for app logic and provide its localized label for display.
            'status_label' => __('hourly.'.$visit->display_status),
            'is_missed' => $visit->is_missed,
            'cash_collection'=>app(CashCollectionService::class)->info($visit),
            'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
            'timezone' => config('app.timezone'),
            'can_schedule' => $visit->status === 'unscheduled' && $open,
            'can_reschedule' => $open && $visit->status==='scheduled' && $visit->scheduled_start_at?->isFuture()
                && $settings->value($snapshot,'allow_reschedule')
                && (int)$visit->reschedule_count<$settings->value($snapshot,'max_reschedules_per_visit')
                && now()->diffInMinutes($visit->scheduled_start_at,false)>=$settings->value($snapshot,'reschedule_hours_before')*60,
            // أزرار إجراءات العميل الثلاثة وسياستها، ليعرضها التطبيق دون أن يستنتجها بنفسه.
            'can_cancel' => $policy['can_cancel'],
            'cancellation_policy' => ['window_hours'=>$policy['window_hours'],'fee_percent'=>$policy['fee_percent'],
                'fee_if_cancelled_now'=>$policy['fee_if_cancelled_now'],'free_until'=>$policy['free_until'],'currency'=>'SAR'],
            'can_request_extra_hour' => $open && in_array($visit->status,CustomerVisitActions::EXTENDABLE,true) && $visit->scheduled_end_at?->isFuture(),
            'can_track' => app(VisitTracking::class)->canTrack($visit, $order),
            'extra_hour_price' => (float) $order->extra_hour_price,
            'extra_hours' => round(max(0,(float)$visit->planned_hours-(float)$order->hours_per_visit),2),
            'can_rate' => in_array($visit->status,CustomerVisitActions::RATEABLE,true) && !$rating,
            // حالة مفهومة للعميل مهما تقدّم التنفيذ الميداني، والحالة الخام تبقى في status.
            'display_status' => $visit->is_missed ? 'missed' : (self::VISIT_DISPLAY[$visit->status] ?? $visit->status),
            // رمز التسليم يُعرض للعميل قبل وصول العاملة فقط، ويطلبه السائق عند التسليم.
            'delivery_code' => !$visit->is_missed && in_array($visit->status,['scheduled','en_route'],true) ? ($visit->delivery_code ?: null) : null,
            'rating' => $rating];
    }

    /** تقييم العميل لهذه الزيارة (متوسط تقييم عاملاتها) أو null إن لم يقيّمها بعد. */
    private function rating(HourlyVisit $visit, HourlyRentalOrder $order): ?array
    {
        if (!in_array($visit->status, CustomerVisitActions::RATEABLE, true) || !Schema::hasTable('hourly_worker_reviews')) return null;
        $rows = $this->ratings[$order->id] ??= DB::table('hourly_worker_reviews')
            ->where('hourly_rental_order_id', $order->id)->where('user_id', $order->user_id)->get()->groupBy('visit_id');
        $own = $rows->get($visit->id);
        if (!$own || $own->isEmpty()) return null;
        return ['value' => (int) round($own->avg('rating')), 'comment' => $own->first()->comment,
            'workers' => $own->count(), 'rated_at' => Carbon::parse($own->max('updated_at'))->toIso8601String()];
    }
}
