<?php

namespace App\Support;

use App\Models\HomeSectionSetting;
use App\Support\Audit\AuditedSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إظهار/إخفاء سكاشن الصفحة الرئيسية في المواقع الخارجية المرتبطة بالداشبورد.
 *
 * القاعدة الأساسية: أي قسم ليس له صف محفوظ يُعتبر ظاهراً. والمواقع نفسها تتعامل
 * مع أي مفتاح غير موجود في sections_visibility على أنه ظاهر، فلا يتغير شكل أي
 * موقع إلا عندما يُخفي المسؤول قسماً صراحةً.
 */
class HomeSectionSettings
{
    private const CACHE_KEY = 'home_section_settings.visibility';

    private const AVAILABLE_CACHE_KEY = 'home_section_settings.available';

    /** مفتاح dev_superadmin_settings الذي يحفظ أقسام تصميم موقع هذا العميل. */
    private const AVAILABLE_SETTING_KEY = 'home_sections_available';

    /**
     * مفاتيح الأقسام الموجودة فعلاً في تصميم موقع العميل كما حددها المطور.
     * null = لم تُحدَّد بعد، فتُعرض كل الأقسام مع تنويه.
     *
     * @return array<int, string>|null
     */
    public static function availableKeys(): ?array
    {
        try {
            $stored = Cache::remember(self::AVAILABLE_CACHE_KEY, now()->addMinutes(5), function () {
                if (!Schema::hasTable('dev_superadmin_settings')) {
                    return null;
                }

                return DB::table('dev_superadmin_settings')
                    ->where('key', self::AVAILABLE_SETTING_KEY)
                    ->value('value');
            });
        } catch (\Throwable) {
            return null;
        }

        $keys = is_string($stored) ? json_decode($stored, true) : null;

        if (!is_array($keys)) {
            return null;
        }

        // نحافظ على ترتيب config ونتجاهل أي مفتاح حُذف من التعريفات.
        return array_values(array_intersect(array_keys(self::definitions()), $keys));
    }

    /**
     * الأقسام التي تظهر لمسؤول الموقع في الشاشة.
     */
    public static function availableDefinitions(): array
    {
        $available = self::availableKeys();

        return $available === null
            ? self::definitions()
            : array_intersect_key(self::definitions(), array_flip($available));
    }

    /**
     * @param  array<int, string>|null  $keys  null يعيد الوضع «غير محدد» (كل الأقسام).
     */
    public static function updateAvailableKeys(?array $keys): void
    {
        if (!Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $value = $keys === null
            ? null
            : json_encode(array_values(array_intersect(array_keys(self::definitions()), $keys)));

        AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::AVAILABLE_SETTING_KEY],
            [
                'value' => $value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::AVAILABLE_CACHE_KEY);
    }

    /**
     * @return array<string, array{label: string, sections: array<int, string>}>
     */
    public static function presets(): array
    {
        return (array) config('home_sections.presets', []);
    }

    /**
     * الأقسام المعرّفة في config/home_sections.php مرتبة كما تظهر في الشاشة.
     *
     * @return array<string, array{label_ar: string, label_en: string, hint_ar?: string, hint_en?: string}>
     */
    public static function definitions(): array
    {
        return (array) config('home_sections.sections', []);
    }

    /**
     * خريطة [مفتاح القسم => ظاهر؟] لكل الأقسام المعرّفة.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $stored = self::stored();
        $visibility = [];

        foreach (array_keys(self::definitions()) as $key) {
            $visibility[$key] = $stored[$key] ?? true;
        }

        return $visibility;
    }

    public static function isVisible(string $key): bool
    {
        return self::stored()[$key] ?? true;
    }

    /**
     * يحفظ حالة كل الأقسام المعرّفة. المفاتيح غير المعرّفة تُتجاهل.
     *
     * @param  array<string, bool>  $visibility
     */
    public static function update(array $visibility): void
    {
        if (!self::tableAvailable()) {
            return;
        }

        foreach (array_keys(self::definitions()) as $key) {
            if (!array_key_exists($key, $visibility)) {
                continue;
            }

            $setting = HomeSectionSetting::query()->firstOrNew(['key' => $key]);
            $setting->is_visible = (bool) $visibility[$key];
            $setting->save();
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::AVAILABLE_CACHE_KEY);
    }

    /**
     * @return array<string, bool>
     */
    private static function stored(): array
    {
        try {
            // فحص الجدول داخل الكاش حتى لا يُضاف استعلام لكل طلب على /client/home.
            return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
                if (!self::tableAvailable()) {
                    return [];
                }

                return HomeSectionSetting::query()
                    ->pluck('is_visible', 'key')
                    ->map(fn ($value) => (bool) $value)
                    ->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    private static function tableAvailable(): bool
    {
        return Schema::hasTable('home_section_settings');
    }
}
