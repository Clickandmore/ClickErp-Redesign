<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ShelterPlaces;
use App\Support\HourlyRentalSettings;
use App\Support\HourlyWebsocketSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * شاشة تفعيل موديل التأجير بالساعة وضبط ربطه بالسير والإيواء.
 *
 * هذه الشاشة وحدها تظهر دائمًا (بصلاحيتها)؛ أما بقية الموديل فلا تظهر
 * إلا بعد التفعيل من هنا، عبر middleware feature.hourly_rental.
 */
class HourlyRentalSettingsController extends Controller
{
    public function index()
    {
        return view('admin.hourly_rental_settings.index', $this->pageData());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'websocket' => ['sometimes', 'array:enabled,key,secret,app_id,cluster,host,port,scheme'],
            'working_start' => ['sometimes', 'required', 'date_format:H:i'],
            'closed_weekdays' => ['nullable','array'], 'closed_weekdays.*' => ['integer','between:0,6','distinct'],
            'working_end' => ['sometimes', 'required', 'date_format:H:i', 'after:working_start'],
            'slot_minutes' => ['sometimes', 'required', 'integer', 'in:15,30,60'],
            'is_enabled' => ['nullable', 'boolean'],
            'link_maid_profiles' => ['nullable', 'boolean'],
            'link_shelter' => ['nullable', 'boolean'],
            'default_shelter_place_id' => ['nullable', 'integer', 'exists:shelter_places,id'],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['integer'],
            'max_visits_per_day' => ['required', 'integer', 'min:1', 'max:5'],
            'max_hours_per_day' => ['required', 'integer', 'min:1', 'max:12'],
            'max_hours_per_week' => ['required', 'integer', 'min:1', 'max:72'],
            'max_hours_per_month' => ['required', 'integer', 'min:1', 'max:312'],
            'ramadan_mode' => ['nullable', 'boolean'],
            'ramadan_max_hours_per_day' => ['required', 'integer', 'min:1', 'max:12'],
            'ramadan_max_hours_per_week' => ['required', 'integer', 'min:1', 'max:72'],
            'min_rest_hours_between_visits' => ['required', 'integer', 'min:0', 'max:12'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'offer_timeout_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'cancellation_window_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'cancellation_fee_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'renewal_reminder_days' => ['required', 'integer', 'min:0', 'max:60'],
            'renewal_count_free_visits' => ['nullable', 'boolean'],
            'curfew_time' => ['required', 'date_format:H:i'],
            'map_provider' => ['required', 'in:' . implode(',', HourlyRentalSettings::MAP_PROVIDERS)],
            'google_maps_browser_key' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'geocoding_url' => ['sometimes', 'required', 'url', 'max:2048', 'regex:/^https?:\/\//'],
            'route_client_to_client' => ['nullable', 'boolean'],
            'tracking_enabled' => ['nullable', 'boolean'],
            'tracking_background' => ['nullable', 'boolean'],
            'tracking_interval_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
            'tracking_active_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'route_distance_warning_km' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'staff_app_driver_acceptance' => ['nullable', 'boolean'],
            'staff_app_require_delivery_code' => ['nullable', 'boolean'],
            'staff_app_stop_radius_meters' => ['required', 'integer', 'min:0', 'max:5000'],
            'staff_app_otp_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'staff_app_driver_stop_rate' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'staff_app_worker_hour_rate' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ], [
            'default_shelter_place_id.exists' => 'مكان الإيواء المختار غير موجود.',
            'curfew_time.date_format' => 'وقت الحظر يجب أن يكون بصيغة ساعة:دقيقة.',
            'map_provider.in' => 'اختر مزود خرائط صالحًا.',
            'google_maps_browser_key.regex' => 'مفتاح Google يتكوّن من حروف وأرقام وشرطات فقط، بلا مسافات.',
        ]);

        // اختيار المزود المدفوع بلا مفتاح يترك الخريطة معطّلة بصمت، فنمنعه هنا.
        if (($validated['map_provider'] ?? null) === HourlyRentalSettings::MAP_GOOGLE
            && empty($validated['google_maps_browser_key'])) {
            throw ValidationException::withMessages([
                'google_maps_browser_key' => 'أدخل مفتاح Google Maps، أو اختر مزود الخرائط المجاني.',
            ]);
        }

        $socketSettings = $request->has('websocket') ? HourlyWebsocketSettings::prepare($validated['websocket']) : null;
        unset($validated['websocket']);
        $willBeEnabled = $request->boolean('is_enabled');
        if ($willBeEnabled && collect($this->readiness())->contains(fn ($stage) => ($stage['required'] ?? true) && !$stage['done'])) {
            throw ValidationException::withMessages(['is_enabled' => 'أكمل تركيب جداول الساعات أولًا باستخدام الأمر hourly:install.']);
        }
        $currentlyEnabled = HourlyRentalSettings::isEnabled();

        // الإطفاء أثناء وجود عاملات خارج الإيواء يترك سجلات مفتوحة بلا شاشة تغلقها.
        if ($currentlyEnabled && !$willBeEnabled) {
            $blockers = HourlyRentalSettings::disableBlockers();
            if ($blockers) {
                throw ValidationException::withMessages([
                    'is_enabled' => 'لا يمكن إطفاء الموديل الآن: ' . implode(' ', $blockers),
                ]);
            }
        }

        $payload = $validated;
        $payload['closed_weekdays'] = $validated['closed_weekdays'] ?? [];
        $payload['is_enabled'] = $willBeEnabled;
        $payload['link_maid_profiles'] = $request->boolean('link_maid_profiles');
        $payload['link_shelter'] = $request->boolean('link_shelter');
        $payload['ramadan_mode'] = $request->boolean('ramadan_mode');
        $payload['renewal_count_free_visits'] = $request->boolean('renewal_count_free_visits');
        foreach (['route_client_to_client', 'tracking_enabled', 'tracking_background',
            'staff_app_driver_acceptance', 'staff_app_require_delivery_code'] as $switch) {
            $payload[$switch] = $request->boolean($switch);
        }
        $payload['city_ids'] = array_map('intval', (array) ($validated['city_ids'] ?? []));

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $socketSettings) {
                HourlyRentalSettings::persist($payload, auth('admin')->id());
                if ($socketSettings !== null) HourlyWebsocketSettings::persist($socketSettings);
            });
        } catch (Throwable $e) {
            Log::error('Failed to update hourly rental settings', ['message' => $e->getMessage()]);

            HourlyRentalSettings::flush();
            return back()->withInput($request->except('websocket'))->with('error', 'تعذر حفظ إعدادات موديل التأجير بالساعة حاليًا.');
        }

        return redirect()
            ->route('dev-superadmin.hourly-rental-settings.index')
            ->with('success', $willBeEnabled
                ? 'تم حفظ الإعدادات وتفعيل موديل التأجير بالساعة.'
                : 'تم حفظ الإعدادات. موديل التأجير بالساعة متوقف.');
    }

    private function pageData(): array
    {
        $settings = HourlyRentalSettings::all();

        $shelters = Schema::hasTable('shelter_places')
            ? ShelterPlaces::query()->orderBy(Schema::hasColumn('shelter_places', 'sort_order') ? 'sort_order' : 'id')->get()
            : collect();

        $cities = Schema::hasTable('cities')
            ? City::query()->saudi()->where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en'])
            : collect();

        return [
            'hourly' => $settings,
            'socketSettings' => HourlyWebsocketSettings::forForm(),
            'shelters' => $shelters,
            'cities' => $cities,
            'blockers' => $settings['is_enabled'] ? HourlyRentalSettings::disableBlockers() : [],
            'readiness' => $this->readiness(),
        ];
    }

    /**
     * حالة بناء الموديل: أي مراحل نُفّذت فعلًا (بوجود جداولها) وأيها لم يُنفَّذ بعد.
     * تُعرض في الشاشة حتى يعرف المسؤول ما الذي سيظهر بعد التفعيل.
     */
    private function readiness(): array
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
