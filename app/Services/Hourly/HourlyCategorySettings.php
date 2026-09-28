<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyCategorySetting, HourlyPackage};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\Schema;

/**
 * كتالوج إعدادات الفئة وحلّ التوريث: إعداد العنصر > إعداد الفئة > الإعداد العام.
 *
 * - القيمة null (أو المفتاح الغائب) في أي مستوى تعني «يرث من الأعلى».
 * - الإعدادات ذات القناتين (web_app / panel) تُورَّث لكل قناة على حدة.
 * - الإعداد العام يُقرأ من HourlyRentalSettings إن وُجد المفتاح هناك، وإلا فمن GENERAL.
 */
class HourlyCategorySettings
{
    public const CHANNELS = ['web_app', 'panel'];
    public const WEEKDAYS = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
    public const STEPPER_KEYS = ['workers', 'quantity'];

    /** نوع كل إعداد وقيمته العامة الافتراضية. */
    public const GENERAL = [
        // الضريبة
        'tax_rate' => ['decimal', 15],
        'show_tax_separately' => ['channels', ['web_app' => false, 'panel' => true]],
        'show_custom_fee_separately' => ['bool', false],
        // إعادة الجدولة
        'allow_reschedule' => ['bool', true],
        'reschedule_hours_before' => ['int', 24],
        'max_reschedules_per_visit' => ['int', 1],
        // القنوات
        'show_packages' => ['bool', true],
        'show_one_off' => ['bool', true],
        'package_price_display' => ['enum:total,per_visit', 'total'],
        // الإسناد
        'assign_by_product_attribute' => ['bool', false],
        'auto_assign' => ['channels', ['web_app' => false, 'panel' => false]],
        'auto_assign_driver' => ['channels', ['web_app' => false, 'panel' => false]],
        'customer_worker_selection' => ['bool', false],
        // التتبع يرث مفتاح النظام العام ويمكن إيقافه لفئة بعينها.
        'tracking_enabled' => ['bool', true],
        'auto_confirm_postpaid' => ['channels', ['web_app' => false, 'panel' => false]],
        'auto_complete_visits_at' => ['time', null],
        // التقويم
        'dispatcher_past_days' => ['int', 0],
        'dispatcher_future_days' => ['int', 60],
        'customer_future_days' => ['int', 30],
        'same_day_cutoff' => ['time', null],
        // الباقات
        'handpick_session_dates' => ['bool', true],
        'allow_multiple_sessions_per_day' => ['bool', false],
        // الواجهة
        'show_material_option' => ['bool', false],
        'show_time_options_app' => ['bool', true],
        'show_service_type_field' => ['bool', false],
        'order_form_steppers' => ['steppers', []],
        // أيام العمل
        'working_days' => ['working_days', null],
    ];

    /** إعدادات يمكن أن يخصصها العنصر نفسه (عمود بنفس الاسم في hourly_packages). */
    public const ITEM_KEYS = ['tax_rate'];

    private array $cache = [];

    /** القيم المحفوظة للفئة كما هي (المخصص فقط). */
    public function stored(?int $categoryId): array
    {
        if (!$categoryId || !Schema::hasTable('hourly_category_settings')) return [];
        return $this->cache[$categoryId] ??= (array) (HourlyCategorySetting::where('service_category_id', $categoryId)->value('settings') ?? []);
    }

    public function forget(?int $categoryId = null): void
    {
        $categoryId ? $this->cache = array_diff_key($this->cache, [$categoryId => true]) : $this->cache = [];
    }

    public function general(string $key)
    {
        [$type, $default] = self::GENERAL[$key];
        $custom=HourlyRentalSettings::get('service_defaults',[]);
        if ($key === 'working_days') {
            $closed = HourlyRentalSettings::get('closed_weekdays', []);
            return collect(self::WEEKDAYS)->mapWithKeys(fn ($label, $day) => [$day => ['is_on' => $custom[$key][$day]['is_on'] ?? !in_array($day, $closed, true), 'order_buffer_hours' => $custom[$key][$day]['order_buffer_hours'] ?? 0]])->all();
        }
        if($type==='channels')return array_replace($default,array_filter($custom[$key]??[],fn($v)=>$v!==null));
        if(array_key_exists($key,$custom) && $custom[$key]!==null)return $custom[$key];
        return HourlyRentalSettings::all()[$key] ?? $default;
    }

    /**
     * القيمة الفعلية. $item: عنصر خدمة أو مصفوفة (لقطة) فيها service_category_id وأعمدة العنصر.
     * للإعدادات ذات القناتين مرّر $channel لتحصل على قيمة قناة واحدة.
     */
    public function value($item, string $key, ?string $channel = null)
    {
        $data = $item instanceof HourlyPackage ? $item->toArray() : (array) $item;
        $overrides=(array)($data['settings_overrides']??[]);
        if (in_array($key, self::ITEM_KEYS, true) && isset($data[$key]) && $data[$key] !== '') return $this->cast($key, $data[$key]);
        $stored = $this->stored(isset($data['service_category_id']) ? (int) $data['service_category_id'] : null);
        $type = self::GENERAL[$key][0];
        $general = $this->general($key);

        if ($type === 'channels') {
            $merged = [];
            foreach (self::CHANNELS as $c) $merged[$c] = (bool)($overrides[$key][$c] ?? $stored[$key][$c] ?? $general[$c]);
            return $channel ? $merged[$channel] : $merged;
        }
        if ($type === 'working_days') {
            $days = [];
            foreach (self::WEEKDAYS as $day => $label) {
                $own = array_replace($stored[$key][$day] ?? [],array_filter($overrides[$key][$day]??[],fn($v)=>$v!==null));
                $days[$day] = [
                    'is_on' => isset($own['is_on']) ? (bool) $own['is_on'] : $general[$day]['is_on'],
                    'order_buffer_hours' => isset($own['order_buffer_hours']) ? (int) $own['order_buffer_hours'] : $general[$day]['order_buffer_hours'],
                ];
            }
            return $days;
        }
        if(array_key_exists($key,$overrides) && $overrides[$key]!==null)return $this->cast($key,$overrides[$key]);
        return array_key_exists($key, $stored) && $stored[$key] !== null ? $this->cast($key, $stored[$key]) : $general;
    }

    /** مصدر القيمة لعرضه في الواجهة: item | category | general. */
    public function source($item, string $key): string
    {
        $data = $item instanceof HourlyPackage ? $item->toArray() : (array) $item;
        if(isset($data['settings_overrides'][$key]))return 'item';
        if (in_array($key, self::ITEM_KEYS, true) && isset($data[$key]) && $data[$key] !== '') return 'item';
        $stored = $this->stored(isset($data['service_category_id']) ? (int) $data['service_category_id'] : null);
        return array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== [] ? 'category' : 'general';
    }

    /** كل القيم الفعلية لفئة (بلا عنصر). */
    public function resolved(int $categoryId): array
    {
        $item = ['service_category_id' => $categoryId];
        return collect(array_keys(self::GENERAL))->mapWithKeys(fn ($key) => [$key => $this->value($item, $key)])->all();
    }

    /** مراحل (steppers) قناة معينة مفهرسة بالمفتاح. */
    public function steppers($item, string $channel): array
    {
        return collect($this->value($item, 'order_form_steppers'))
            ->filter(fn ($s) => in_array($channel, $s['channels'] ?? [], true))
            ->keyBy('key')->all();
    }

    /** تنظيف مدخلات النموذج قبل الحفظ: تُحذف القيم الفارغة (= يرث) وتُحوَّل الأنواع. */
    public function normalizeInput(array $input): array
    {
        $out = [];
        foreach (self::GENERAL as $key => [$type]) {
            if (!array_key_exists($key, $input)) continue;
            $value = $input[$key];
            if ($type === 'channels') {
                $channels = [];
                foreach (self::CHANNELS as $c) if (isset($value[$c]) && $value[$c] !== '' && $value[$c] !== 'inherit') $channels[$c] = (bool) (int) $value[$c];
                if ($channels) $out[$key] = $channels;
                continue;
            }
            if ($type === 'working_days') {
                $days = [];
                foreach (self::WEEKDAYS as $day => $label) {
                    $row = array_filter((array) ($value[$day] ?? []), fn ($v) => $v !== null && $v !== '' && $v !== 'inherit');
                    if (isset($row['is_on'])) $row['is_on'] = (bool) (int) $row['is_on'];
                    if (isset($row['order_buffer_hours'])) $row['order_buffer_hours'] = (int) $row['order_buffer_hours'];
                    if ($row) $days[$day] = $row;
                }
                if ($days) $out[$key] = $days;
                continue;
            }
            if ($type === 'steppers') {
                if (($input['order_form_steppers_mode'] ?? 'inherit') !== 'custom') continue;
                $out[$key] = collect((array) $value)->values()->map(fn ($s) => [
                    'key' => $s['key'], 'label' => trim((string) ($s['label'] ?? '')) ?: $s['key'],
                    'channels' => array_values(array_intersect(self::CHANNELS, (array) ($s['channels'] ?? []))),
                    'min' => (int) $s['min'], 'max' => (int) $s['max'], 'scale_price' => (bool) (int) ($s['scale_price'] ?? 0),
                ])->all();
                continue;
            }
            if ($value === null || $value === '' || $value === 'inherit') continue;
            if ($key === 'auto_complete_visits_at' && $value === 'disabled') { $out[$key] = 'disabled'; continue; }
            $out[$key] = $this->cast($key, $value);
        }
        return $out;
    }

    private function cast(string $key, $value)
    {
        $type = self::GENERAL[$key][0];
        return match (true) {
            $type === 'decimal' => round((float) $value, 2),
            $type === 'int' => (int) $value,
            $type === 'bool' => (bool) (int) $value,
            default => $value,
        };
    }

    /** وقت الإغلاق التلقائي الفعلي، أو null إن كان معطلًا. */
    public function autoCompleteAt($item): ?string
    {
        $value = $this->value($item, 'auto_complete_visits_at');
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : null;
    }
}
