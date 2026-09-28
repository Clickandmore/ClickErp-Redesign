<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory};
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات الفئة كما تحتاجها نماذج الطلب (الويب والتطبيق وشاشة الطلب في اللوحة):
 * الأعلام الظاهرة والعدّادات وطرق الدفع للقناة، والملاحظات المخصصة مع الملاحظات التلقائية.
 */
class HourlyCategoryConfig
{
    public function __construct(private HourlyBookingRules $rules) {}

    /** @param string $channel web_app | panel */
    public function forCategory(HourlyServiceCategory $category, string $channel = 'web_app', ?string $locale = null, ?HourlyPackage $product = null): array
    {
        $locale ??= app()->getLocale();
        $s = $this->rules->settings();
        $item = $product ?? ['service_category_id' => $category->id];
        return [
            'category' => ['id' => $category->id, 'slug' => $category->slug, 'name' => $locale === 'en' && $category->name_en ? $category->name_en : $category->name_ar,
                'description' => $locale === 'en' && $category->description_en ? $category->description_en : $category->description_ar,
                'icon_type' => $category->icon_type, 'icon' => $category->icon_type === 'library' ? $category->icon : $category->icon_url, 'color' => $category->color],
            'channel' => $channel,
            'ignore_closed_days' => (bool) ($product?->ignore_closed_days ?? false),
            'fields' => $product ? app(HourlyBookingOptions::class)->fields($product) : [],
            'auto_assign' => (bool) $s->value($item, 'auto_assign', $channel),
            'auto_assign_driver' => (bool) $s->value($item, 'auto_assign_driver', $channel),
            'auto_confirm_postpaid' => (bool) $s->value($item, 'auto_confirm_postpaid', $channel),
            'display' => [
                'show_tax_separately' => $s->value($item, 'show_tax_separately', $channel),
                'show_custom_fee_separately' => $s->value($item, 'show_custom_fee_separately'),
                'show_material_option' => $s->value($item, 'show_material_option'),
                'show_time_options' => $channel === 'panel' || $s->value($item, 'show_time_options_app'),
                'show_service_type_field' => $s->value($item, 'show_service_type_field'),
                'package_price_display' => $s->value($item, 'package_price_display'),
                'show_packages' => $channel === 'panel' || $s->value($item, 'show_packages'),
                'show_one_off' => $channel === 'panel' || $s->value($item, 'show_one_off'),
            ],
            'tax_rate' => (float) $s->value($item, 'tax_rate'),
            'steppers' => array_values($s->steppers($item, $channel)),
            'customer_worker_selection' => $s->value($item, 'customer_worker_selection'),
            'worker_selection' => ['enabled'=>(bool)$s->value($item, 'customer_worker_selection'), 'mode'=>'optional_partial', 'min_selected'=>0, 'request_field'=>'selected_maid_ids', 'count_field'=>'workers_per_visit', 'auto_fill_remaining'=>(bool)$s->value($item,'auto_assign',$channel)],
            'tracking_enabled' => (bool) $s->value($item, 'tracking_enabled'),
            'booking' => [
                'future_days' => $s->value($item, $channel === 'panel' ? 'dispatcher_future_days' : 'customer_future_days'),
                'time_selection' => $channel === 'panel' || $s->value($item, 'show_time_options_app') ? 'time' : 'date',
                'min_date' => now()->subDays($channel === 'panel' ? $s->value($item, 'dispatcher_past_days') : 0)->toDateString(),
                'max_date' => now()->addDays($s->value($item, $channel === 'panel' ? 'dispatcher_future_days' : 'customer_future_days'))->toDateString(),
                'past_days' => $channel === 'panel' ? $s->value($item, 'dispatcher_past_days') : 0,
                'same_day_cutoff' => $s->value($item, 'same_day_cutoff'),
                'handpick_session_dates' => $channel === 'panel' || $s->value($item, 'handpick_session_dates'),
                'allow_multiple_sessions_per_day' => $s->value($item, 'allow_multiple_sessions_per_day'),
                'working_days' => $s->value($item, 'working_days'),
            ],
            'reschedule' => ['allowed' => $s->value($item, 'allow_reschedule'), 'hours_before' => $s->value($item, 'reschedule_hours_before'), 'max_per_visit' => $s->value($item, 'max_reschedules_per_visit')],
            'payment_methods' => $this->rules->paymentMethods($item, $channel === 'panel' ? 'employee' : 'web_app'),
            'notes' => $this->notes($category, $locale, $product),
        ];
    }

    /** الملاحظات المخصصة بالترتيب ثم التلقائية المولدة من الإعدادات الفعلية. */
    public function notes(HourlyServiceCategory $category, ?string $locale = null, ?HourlyPackage $product = null): array
    {
        $locale ??= app()->getLocale();
        $custom = Schema::hasTable('hourly_category_notes')
            ? $category->notes()->get()->map(fn ($note) => ['id' => $note->id, 'type' => 'custom', 'text' => $note->text($locale)])->all()
            : [];
        return array_merge($custom, array_map(fn ($text) => ['id' => null, 'type' => 'automatic', 'text' => $text], $this->automaticNotes($category, $locale,$product)));
    }

    public function automaticNotes(HourlyServiceCategory $category, ?string $locale = null, ?HourlyPackage $product = null): array
    {
        $en = ($locale ?? app()->getLocale()) === 'en';
        $s = $this->rules->settings();
        $item = $product ?? ['service_category_id' => $category->id];
        $notes = [];

        $tax = rtrim(rtrim(number_format((float) $s->value($item, 'tax_rate'), 2, '.', ''), '0'), '.');
        $notes[] = $en ? "Prices include {$tax}% VAT." : "الأسعار تشمل {$tax}% ضريبة القيمة المضافة.";

        $items = $product ? collect([$product]) : HourlyPackage::where('service_category_id', $category->id)->where('is_active', true)->get(['validity_days', 'workers_per_visit', 'visits_count']);
        $packages = $items->where('visits_count', '>', 1);
        if ($packages->isNotEmpty()) {
            [$min, $max] = [$packages->min('validity_days'), $packages->max('validity_days')];
            $range = $min === $max ? $min : "{$min}–{$max}";
            $notes[] = $en ? "Packages are valid for {$range} days from purchase." : "صلاحية الباقة {$range} يومًا من تاريخ الشراء.";
        }
        $workers = $s->steppers($item, 'web_app')['workers'] ?? null;
        if ($workers) {
            $notes[] = $en ? "You can choose {$workers['min']} to {$workers['max']} workers per visit." : "يمكن اختيار من {$workers['min']} إلى {$workers['max']} عاملات لكل زيارة.";
        } elseif ($items->isNotEmpty()) {
            [$min, $max] = [$items->min('workers_per_visit'), $items->max('workers_per_visit')];
            $count = $min === $max ? $min : "{$min}–{$max}";
            $notes[] = $en ? "Each visit includes {$count} worker(s)." : "عدد العاملات في كل زيارة: {$count}.";
        }
        if ($s->value($item, 'package_price_display') === 'per_visit') {
            $notes[] = $en ? 'Online payment collects the first visit price now; the rest is due before later visits.' : 'عند الدفع الإلكتروني يُحصَّل سعر الزيارة الأولى الآن، والباقي قبل الزيارات التالية.';
        }
        if ($s->value($item, 'allow_reschedule')) {
            $hours = $s->value($item, 'reschedule_hours_before'); $max = $s->value($item, 'max_reschedules_per_visit');
            $notes[] = $en ? "A visit can be rescheduled up to {$max} time(s), at least {$hours} hours before it starts." : "يمكن إعادة جدولة الزيارة حتى {$max} مرة، قبل موعدها بـ {$hours} ساعة على الأقل.";
        } else {
            $notes[] = $en ? 'Visits in this category cannot be rescheduled.' : 'لا يمكن إعادة جدولة زيارات هذه الفئة.';
        }
        if ($cutoff = $s->value($item, 'same_day_cutoff')) {
            $notes[] = $en ? "Same-day bookings close at {$cutoff}." : "حجز اليوم نفسه متاح حتى الساعة {$cutoff}.";
        }
        return $notes;
    }
}
