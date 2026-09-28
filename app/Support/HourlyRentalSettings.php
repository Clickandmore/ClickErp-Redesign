<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات موديل «التأجير بالساعة» — تُقرأ من جدول new_settings بالمفتاح "hourly_rental".
 *
 * التفعيل من SystemModuleManager فقط؛ إعدادات الساعات لا تحمل مفتاح تشغيل مستقلًا.
 * ولا تُقرأ جداوله. الربط بسير العاملات وبشاشات الإيواء اختياري ويُضبط من هنا أيضًا،
 * حتى يمكن تشغيل الموديل تدريجيًا دون أي أثر على الاستقدام أو الإيجار الشهري.
 *
 * القاعدة الثابتة: هذا الصنف لا يمس contract_infos.is_outside_shelter ولا أي كيان قائم.
 */
class HourlyRentalSettings
{
    public const SETTINGS_KEY = 'hourly_rental';

    /** مزودا الخرائط المدعومان في الموديول. */
    public const MAP_OSM = 'osm';
    public const MAP_GOOGLE = 'google';
    public const MAP_PROVIDERS = [self::MAP_OSM, self::MAP_GOOGLE];

    private static ?array $cached = null;

    public static function all(): array
    {
        if (self::$cached !== null) {
            return array_replace(self::$cached, ['is_enabled' => self::isEnabled()]);
        }

        $settings = self::defaults();

        if (!Schema::hasTable('new_settings')) {
            return self::$cached = $settings;
        }

        $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
        $stored = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($stored)) {
            return self::$cached = $settings;
        }

        return self::$cached = self::normalize(array_replace($settings, $stored));
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    /** المفتاح الرئيسي: كل شيء في الموديل يُفحص عبره. */
    public static function isEnabled(): bool
    {
        return SystemModuleManager::isEnabled('hourly_rental');
    }

    /** إظهار بطاقة «التأجير بالساعة» في سيرة العاملة وشاشاتها. */
    public static function maidProfilesLinked(): bool
    {
        return self::isEnabled() && (bool) self::all()['link_maid_profiles'];
    }

    /** إظهار حركة الخروج والعودة اليومية في شاشات الإيواء. */
    public static function shelterLinked(): bool
    {
        return self::isEnabled() && (bool) self::all()['link_shelter'];
    }

    /**
     * أسباب تمنع الإطفاء الآن: زيارات جارية أو سجلات حركة مفتوحة.
     * الجداول تُنشأ في مراحل لاحقة، لذلك يُفحص وجودها أولًا.
     */
    public static function disableBlockers(): array
    {
        $blockers = [];

        if (Schema::hasTable('hourly_visits')) {
            $open = \DB::table('hourly_visits')
                ->whereIn('status', ['en_route', 'delivered', 'in_progress', 'worker_finished', 'picked_up'])
                ->count();
            if ($open > 0) {
                $blockers[] = "يوجد {$open} زيارة جارية خارج الإيواء الآن.";
            }
        }

        if (Schema::hasTable('worker_daily_movements')) {
            $out = \DB::table('worker_daily_movements')
                ->whereIn('status', ['out', 'overdue'])
                ->count();
            if ($out > 0) {
                $blockers[] = "يوجد {$out} عاملة لم تُسجَّل عودتها إلى الإيواء بعد.";
            }
        }

        return $blockers;
    }

    /**
     * السقوف السارية الآن: العادية أو الرمضانية بحسب المفتاح.
     * محرك الإتاحة يقارن بها ساعات العاملة المخططة قبل أي إسناد.
     */
    public static function effectiveHourCaps(): array
    {
        $s = self::all();

        return [
            'per_day' => $s['ramadan_mode'] ? $s['ramadan_max_hours_per_day'] : $s['max_hours_per_day'],
            'per_week' => $s['ramadan_mode'] ? $s['ramadan_max_hours_per_week'] : $s['max_hours_per_week'],
            'per_month' => $s['max_hours_per_month'],
            'visits_per_day' => $s['max_visits_per_day'],
            'rest_between_visits' => $s['min_rest_hours_between_visits'],
        ];
    }

    public static function persist(array $payload, ?int $adminId = null): array
    {
        $current = self::all();
        $settings = self::normalize(array_replace($current, $payload));
        unset($settings['is_enabled']);
        $settings['updated_by'] = $adminId;
        $settings['updated_at'] = now()->toDateTimeString();

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE)]
        );

        self::flush();

        return self::all();
    }

    public static function flush(): void
    {
        self::$cached = null;
    }

    public static function defaults(): array
    {
        return [
            'is_enabled' => self::isEnabled(),
            'link_maid_profiles' => true,
            'link_shelter' => true,
            'default_shelter_place_id' => null,
            'city_ids' => [],
            'max_visits_per_day' => 1,
            // السقوف القانونية الافتراضية (نظام العمل م.98: 8 ساعات يوميًا / 48 أسبوعيًا، ورمضان 6 / 36).
            // تُطبَّق على كل عاملة، وملف العاملة يستطيع خفضها لا رفعها.
            'max_hours_per_day' => 8,
            'max_hours_per_week' => 48,
            'max_hours_per_month' => 208,
            'ramadan_mode' => false,
            'ramadan_max_hours_per_day' => 6,
            'ramadan_max_hours_per_week' => 36,
            'min_rest_hours_between_visits' => 1,
            'working_start' => '08:00',
            'closed_weekdays' => [],
            'service_defaults' => [],
            'working_end' => '20:00',
            'slot_minutes' => 30,
            'min_notice_hours' => 24,
            'offer_timeout_minutes' => 10,
            'cancellation_window_hours' => 12,
            'cancellation_fee_percent' => 50,
            // تذكير العميل بتجديد الباقة قبل آخر زيارة بهذا العدد من الأيام (0 = معطّل).
            'renewal_reminder_days' => 7,
            // هل تدخل الزيارات المجانية/التعويضية في تاريخ انتهاء العقد؟ الملغاة لا تدخل أبدًا (ContractPeriod).
            'renewal_count_free_visits' => false,
            'curfew_time' => '21:00',
            // مزود الخرائط في شاشات الموديول: osm مجاني بلا مفتاح، google مدفوع بحصة شهرية.
            'map_provider' => self::MAP_OSM,
            'google_maps_browser_key' => '',
            'geocoding_url' => 'https://nominatim.openstreetmap.org/search',
            // إدارة المسار: المسافة تُحسب من مقر الفريق لكل مهمة، أو من العميل السابق عند التفعيل.
            'route_client_to_client' => false,
            // المسافة التي تظهر فوقها المهمة بلون تحذيري في «إدارة المسار» (كم).
            'route_distance_warning_km' => 50,
            // تتبع GPS من تطبيقات الساعات: الفاصل بين الإرسالات، والإبقاء في الخلفية، ومتى يُعد المستخدم «نشطًا».
            'tracking_enabled' => true,
            'tracking_background' => false,
            'tracking_interval_seconds' => 10,
            'tracking_active_minutes' => 60,
            // تطبيق الفريق الميداني (سائق/عاملة): مفاتيح تشغيل مسار التنفيذ.
            // acceptance = هل يُعرض المشوار على السائق ليقبله قبل التنفيذ، أم يُنفَّذ كما أسندته اللوحة مباشرة.
            'staff_app_driver_acceptance' => false,
            'staff_app_require_delivery_code' => true,
            'staff_app_stop_radius_meters' => 0,
            'staff_app_otp_minutes' => 10,
            // مدة بقاء السائق والعاملة مسجّلين حين يطلب التطبيق «تذكّرني» (بالأيام).
            // بدونها تسري مدة jwt.ttl العامة (60 دقيقة) فيتكرر الدخول كل ساعة.
            'staff_app_session_days' => 30,
            // أسعار مؤقتة للمستحقات حتى تُبنى المرحلة ٤ (hourly_visit_earnings): ريال لكل محطة منفّذة، وريال لكل ساعة عمل.
            'staff_app_driver_stop_rate' => 0,
            'staff_app_worker_hour_rate' => 0,
            'updated_by' => null,
            'updated_at' => null,
        ];
    }

    /**
     * مفتاح Google: من إعدادات الشاشة أولًا، ثم من البيئة للتوافق مع التثبيتات القديمة.
     * يعود فارغًا إذا كان المزود المختار مجانيًا، حتى لا يُحمَّل سكربت Google بلا داعٍ.
     */
    public static function googleMapsKey(): string
    {
        if (self::get('map_provider') !== self::MAP_GOOGLE) {
            return '';
        }

        return (string) self::get('google_maps_browser_key', '');
    }

    /** المزود الفعلي بعد التحقق من توفر مفتاح Google؛ بلا مفتاح يسقط إلى المجاني. */
    public static function mapProvider(): string
    {
        return self::googleMapsKey() ? self::MAP_GOOGLE : self::MAP_OSM;
    }

    /** ما يحتاجه تطبيق الموظفين (والعميل) لضبط إرسال الموقع؛ يُعاد مع me وبعد كل إرسال. */
    public static function trackingConfig(): array
    {
        $s = self::all();

        return ['enabled' => $s['tracking_enabled'], 'background' => $s['tracking_background'], 'interval_seconds' => $s['tracking_interval_seconds']];
    }

    private static function normalize(array $settings): array
    {
        $defaults = self::defaults();

        $bool = fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);
        $int = fn ($v, $d, $min, $max) => max($min, min($max, (int) ($v ?? $d)));

        $cityIds = $settings['city_ids'] ?? [];
        $cityIds = is_array($cityIds) ? $cityIds : [];
        $cityIds = array_values(array_unique(array_filter(array_map('intval', $cityIds))));

        $curfew = trim((string) ($settings['curfew_time'] ?? $defaults['curfew_time']));
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $curfew)) {
            $curfew = $defaults['curfew_time'];
        }

        return [
            'closed_weekdays' => array_values(array_intersect(range(0,6), (array) ($settings['closed_weekdays'] ?? []))),
            'service_defaults' => (array) ($settings['service_defaults'] ?? []),
            'working_start' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($settings['working_start'] ?? '')) ? $settings['working_start'] : '08:00',
            'working_end' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($settings['working_end'] ?? '')) ? $settings['working_end'] : '20:00',
            'slot_minutes' => in_array((int) ($settings['slot_minutes'] ?? 30), [15,30,60], true) ? (int) ($settings['slot_minutes'] ?? 30) : 30,
            'is_enabled' => self::isEnabled(),
            'link_maid_profiles' => $bool($settings['link_maid_profiles'] ?? $defaults['link_maid_profiles']),
            'link_shelter' => $bool($settings['link_shelter'] ?? $defaults['link_shelter']),
            'default_shelter_place_id' => !empty($settings['default_shelter_place_id']) ? (int) $settings['default_shelter_place_id'] : null,
            'city_ids' => $cityIds,
            'max_visits_per_day' => $int($settings['max_visits_per_day'] ?? null, $defaults['max_visits_per_day'], 1, 5),
            'max_hours_per_day' => $int($settings['max_hours_per_day'] ?? null, $defaults['max_hours_per_day'], 1, 12),
            'max_hours_per_week' => $int($settings['max_hours_per_week'] ?? null, $defaults['max_hours_per_week'], 1, 72),
            'max_hours_per_month' => $int($settings['max_hours_per_month'] ?? null, $defaults['max_hours_per_month'], 1, 312),
            'ramadan_mode' => $bool($settings['ramadan_mode'] ?? false),
            'ramadan_max_hours_per_day' => $int($settings['ramadan_max_hours_per_day'] ?? null, $defaults['ramadan_max_hours_per_day'], 1, 12),
            'ramadan_max_hours_per_week' => $int($settings['ramadan_max_hours_per_week'] ?? null, $defaults['ramadan_max_hours_per_week'], 1, 72),
            'min_rest_hours_between_visits' => $int($settings['min_rest_hours_between_visits'] ?? null, $defaults['min_rest_hours_between_visits'], 0, 12),
            'min_notice_hours' => $int($settings['min_notice_hours'] ?? null, $defaults['min_notice_hours'], 0, 168),
            'offer_timeout_minutes' => $int($settings['offer_timeout_minutes'] ?? null, $defaults['offer_timeout_minutes'], 1, 120),
            'cancellation_window_hours' => $int($settings['cancellation_window_hours'] ?? null, $defaults['cancellation_window_hours'], 0, 168),
            'cancellation_fee_percent' => $int($settings['cancellation_fee_percent'] ?? null, $defaults['cancellation_fee_percent'], 0, 100),
            'renewal_reminder_days' => $int($settings['renewal_reminder_days'] ?? null, $defaults['renewal_reminder_days'], 0, 60),
            'renewal_count_free_visits' => $bool($settings['renewal_count_free_visits'] ?? $defaults['renewal_count_free_visits']),
            'curfew_time' => $curfew,
            'map_provider' => in_array($settings['map_provider'] ?? null, self::MAP_PROVIDERS, true) ? $settings['map_provider'] : $defaults['map_provider'],
            // المفتاح يُحفظ كما هو بعد التشذيب؛ لا يُمسح عند العودة للمجاني حتى لا يُعاد إدخاله.
            'google_maps_browser_key' => mb_substr(trim((string) ($settings['google_maps_browser_key'] ?? '')), 0, 200),
            'geocoding_url' => trim((string) ($settings['geocoding_url'] ?? $defaults['geocoding_url'])),
            'route_client_to_client' => $bool($settings['route_client_to_client'] ?? $defaults['route_client_to_client']),
            'route_distance_warning_km' => $int($settings['route_distance_warning_km'] ?? null, $defaults['route_distance_warning_km'], 1, 1000),
            'tracking_enabled' => $bool($settings['tracking_enabled'] ?? $defaults['tracking_enabled']),
            'tracking_background' => $bool($settings['tracking_background'] ?? $defaults['tracking_background']),
            'tracking_interval_seconds' => $int($settings['tracking_interval_seconds'] ?? null, $defaults['tracking_interval_seconds'], 5, 600),
            'tracking_active_minutes' => $int($settings['tracking_active_minutes'] ?? null, $defaults['tracking_active_minutes'], 1, 240),
            'staff_app_driver_acceptance' => $bool($settings['staff_app_driver_acceptance'] ?? $defaults['staff_app_driver_acceptance']),
            'staff_app_require_delivery_code' => $bool($settings['staff_app_require_delivery_code'] ?? $defaults['staff_app_require_delivery_code']),
            'staff_app_stop_radius_meters' => $int($settings['staff_app_stop_radius_meters'] ?? null, $defaults['staff_app_stop_radius_meters'], 0, 5000),
            'staff_app_otp_minutes' => $int($settings['staff_app_otp_minutes'] ?? null, $defaults['staff_app_otp_minutes'], 1, 60),
            'staff_app_session_days' => $int($settings['staff_app_session_days'] ?? null, $defaults['staff_app_session_days'], 1, 365),
            'staff_app_driver_stop_rate' => round((float) ($settings['staff_app_driver_stop_rate'] ?? $defaults['staff_app_driver_stop_rate']), 2),
            'staff_app_worker_hour_rate' => round((float) ($settings['staff_app_worker_hour_rate'] ?? $defaults['staff_app_worker_hour_rate']), 2),
            'updated_by' => isset($settings['updated_by']) ? (int) $settings['updated_by'] : null,
            'updated_at' => $settings['updated_at'] ?? null,
        ];
    }

    public static function installationStages(): array
    {
        $has = fn (string ...$tables) => collect($tables)->every(fn ($t) => Schema::hasTable($t));

        // الأسماء هنا هي الجداول الفعلية التي بناها الفريق، لا أسماء الخطة الأولى.
        return [
            ['label' => 'إحداثيات عناوين العملاء', 'done' => Schema::hasColumn('user_addresses', 'latitude')],
            ['label' => 'المرحلة ١ — الباقات والطلبات والتحصيل ومعالج الحجز', 'done' => $has('hourly_packages', 'hourly_rental_orders', 'hourly_order_payments') && Schema::hasColumn('hourly_rental_orders', 'booking_token')],
            ['label' => 'المرحلة ٢ — الزيارات والإسناد وملفات عاملات الساعات وجدول الموظفين', 'done' => $has('hourly_visits', 'hourly_visit_workers', 'hourly_worker_leaves', 'hourly_visit_batches') && Schema::hasColumn('maids', 'rental_mode')],
            ['label' => 'المرحلة ٣ — السائقون وإدارة المسار وتتبع GPS', 'done' => $has('hourly_drivers', 'hourly_route_stops', 'location_pings')],
            ['label' => 'عملاء الساعات والمحفظة والتجديدات', 'done' => $has('hourly_customer_profiles', 'hourly_wallet_transactions') && Schema::hasColumn('hourly_rental_orders', 'renewed_from_order_id')],
            ['label' => 'حركة الإيواء اليومية (خروج وعودة) مع ربط الإيواء', 'done' => $has('worker_daily_movements')],
            ['label' => 'المرحلة ٤ — الإغلاق المالي ومستحقات العاملات', 'done' => $has('hourly_visit_earnings'), 'required' => false],
        ];
    }
}
