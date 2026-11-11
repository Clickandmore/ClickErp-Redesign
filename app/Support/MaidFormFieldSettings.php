<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * التحكم في حقول نموذج إضافة/تعديل السيرة لكل شركة.
 *
 * لكل حقل قابل للتحكم ثلاث حالات:
 *  - default : كالمعتاد (الإلزام حسب نوع السيرة كما هو اليوم)
 *  - optional: ظاهر لكنه غير إلزامي في كل الأنواع
 *  - hidden  : لا يظهر في النموذج
 *  - required: إلزامي دائمًا (لحقول محددة مثل ملف السيرة الجاهزة)
 *
 * الحقول الأساسية في {@see LOCKED} تعتمد عليها العقود والمالية وبوابة الوكلاء،
 * فلا تظهر في الإعداد ولا يمكن إخفاؤها. الراتب والجنس مستبعدان أيضًا: الراتب تقرأه
 * فواتير التأجير ويُملأ تلقائيًا من الباقة، والجنس يحدد منطق رخصة القيادة وصيغ النصوص.
 */
class MaidFormFieldSettings
{
    public const COLUMN = 'maid_form_fields';

    public const DEFAULT = 'default';
    public const OPTIONAL = 'optional';
    public const HIDDEN = 'hidden';
    public const REQUIRED = 'required';

    public const LOCKED = [
        'type_id' => 'نوع السيرة',
        'name:en' => 'اسم العاملة بالإنجليزية',
        'nationality_id' => 'الجنسية',
        'passport_num' => 'رقم الجواز',
        'office_id' => 'المكتب الخارجي',
        'job_id' => 'المهنة',
        'salary' => 'الراتب',
        'gender' => 'الجنس',
    ];

    public const GROUPS = [
        'main' => 'بيانات أساسية (يمكن جعلها اختيارية فقط)',
        'documents' => 'الوثائق والملفات',
        'personal' => 'البيانات الشخصية',
        'experience' => 'المهارات والخبرة',
        'media' => 'الوسائط والعرض',
    ];

    private static ?array $states = null;

    /**
     * الحقول القابلة للتحكم.
     *  - names        : أسماء عناصر الإدخال في النموذج
     *  - selector     : بديل عن names لتحديد العنصر (اختياري)
     *  - wrapper      : محدد الغلاف المراد إخفاؤه إن لم يكن أقرب عمود col-*
     *  - can_require  : هل يكون إلزاميًا في بعض الأنواع اليوم (فيظهر خيار "اختياري")
     *  - can_hide     : هل يُسمح بإخفائه
     *  - can_force    : هل يُسمح بجعله إلزاميًا دائمًا
     */
    public static function catalog(): array
    {
        return [
            'image' => ['label' => 'الصورة الشخصية', 'group' => 'main', 'names' => ['image'], 'can_require' => true, 'can_hide' => false,
                'hint' => 'تظهر في الموقع الخارجي والسيرة المولّدة.'],
            'birth_date' => ['label' => 'تاريخ الميلاد', 'group' => 'main', 'names' => ['birth_date'], 'can_require' => true, 'can_hide' => false,
                'hint' => 'يُرسل عمر العاملة للمكتب الخارجي ويُستخدم في فلاتر الموقع.'],
            'marital_status' => ['label' => 'الحالة الاجتماعية', 'group' => 'main', 'names' => ['marital_status:ar'], 'can_require' => true, 'can_hide' => false],

            'cv_file' => ['label' => 'الصورة الكاملة', 'group' => 'documents', 'names' => ['cv_file'], 'can_require' => false, 'can_hide' => true,
                'hint' => 'عند إخفائها يتوقف زر "قراءة بيانات السيرة" لأنه يقرأ من هذا الملف.'],
            'maid_cv_file' => ['label' => 'ملف السيرة الجاهزة (صورة أو PDF)', 'group' => 'documents', 'names' => ['maid_cv_file'], 'can_require' => false, 'can_hide' => true, 'can_force' => true,
                'hint' => 'للشركات التي ترفع صورة السيرة بدل إدخال كل البيانات. اجعله إلزاميًا إذا كانت كل السير تُرفع جاهزة.'],
            'id_fields' => ['label' => 'رقم الهوية وتاريخ انتهائها', 'group' => 'documents', 'names' => ['id_number', 'id_expiry_date'], 'can_require' => true, 'can_hide' => true],
            'passport_extra' => ['label' => 'رقم الحدود وتواريخ ومكان إصدار الجواز', 'group' => 'documents', 'names' => ['border_number', 'passport_issue_date', 'passport_issue_place', 'passport_expiry_date'], 'can_require' => false, 'can_hide' => true],

            'age' => ['label' => 'العمر', 'group' => 'personal', 'names' => ['age'], 'can_require' => false, 'can_hide' => true],
            'height_weight' => ['label' => 'الطول والوزن', 'group' => 'personal', 'names' => ['height', 'weight'], 'can_require' => true, 'can_hide' => true],
            'childrens_count' => ['label' => 'عدد الأطفال', 'group' => 'personal', 'names' => ['childrens_count'], 'can_require' => false, 'can_hide' => true],
            'phone' => ['label' => 'رقم جوال العاملة', 'group' => 'personal', 'names' => ['phone'], 'can_require' => false, 'can_hide' => true],
            'relative' => ['label' => 'اسم قريب العاملة وجواله', 'group' => 'personal', 'names' => ['relative_name', 'relative_phone'], 'can_require' => false, 'can_hide' => true],
            'residence_city' => ['label' => 'مدينة سكن العاملة', 'group' => 'personal', 'names' => ['residence_city'], 'can_require' => false, 'can_hide' => true],
            'education' => ['label' => 'المؤهل التعليمي', 'group' => 'personal', 'names' => ['education', 'education_en'], 'can_require' => false, 'can_hide' => true],
            'languages' => ['label' => 'اللغات', 'group' => 'personal', 'selector' => 'input.lang-check', 'wrapper' => '.col-12', 'can_require' => false, 'can_hide' => true],
            'driving_license' => ['label' => 'رخصة القيادة', 'group' => 'personal', 'selector' => '#driving_license_field, #driving_license_file_field', 'wrapper' => 'self', 'names' => ['has_driving_license', 'driving_license_file'], 'can_require' => false, 'can_hide' => true],
            'duration' => ['label' => 'مدة العقد', 'group' => 'personal', 'names' => ['duration'], 'can_require' => false, 'can_hide' => true],

            'skills' => ['label' => 'المهارات', 'group' => 'experience', 'names' => ['skills[]'], 'can_require' => false, 'can_hide' => true],
            'experience_details' => ['label' => 'تفاصيل الخبرة (الدولة والمدة والوظيفة)', 'group' => 'experience', 'selector' => '#experience_section', 'wrapper' => 'self', 'can_require' => true, 'can_hide' => true],
            'cooking_experience' => ['label' => 'خبرة الطبخ', 'group' => 'experience', 'selector' => '#cooking_experience_field', 'wrapper' => 'self', 'names' => ['cooking_experience'], 'can_require' => true, 'can_hide' => true],

            'video' => ['label' => 'ملف الفيديو', 'group' => 'media', 'names' => ['video'], 'can_require' => false, 'can_hide' => true],
            'video_link' => ['label' => 'رابط الفيديو', 'group' => 'media', 'names' => ['video_link'], 'can_require' => false, 'can_hide' => true],
            'musaned_link' => ['label' => 'رابط ملف العاملة على مساند', 'group' => 'media', 'names' => ['musaned_link'], 'can_require' => false, 'can_hide' => true],
            'additional_notes' => ['label' => 'الملاحظات الإضافية', 'group' => 'media', 'names' => ['additional_notes'], 'can_require' => false, 'can_hide' => true],
            'offer' => ['label' => 'بيانات العرض (المدة والتواريخ والوصف)', 'group' => 'media', 'names' => ['offer_duration', 'offer_starts_at', 'offer_expires_at', 'offer_description'], 'can_require' => false, 'can_hide' => true],
        ];
    }

    /** الحالات المسموحة لحقل معيّن. */
    public static function allowedStates(string $key): array
    {
        $field = self::catalog()[$key] ?? null;
        if (!$field) {
            return [self::DEFAULT];
        }

        return array_values(array_filter([
            self::DEFAULT,
            $field['can_require'] ? self::OPTIONAL : null,
            $field['can_hide'] ? self::HIDDEN : null,
            !empty($field['can_force']) ? self::REQUIRED : null,
        ]));
    }

    /** @return array<string,string> حالة كل حقل في الكتالوج */
    public static function states(): array
    {
        if (self::$states !== null) {
            return self::$states;
        }

        $stored = [];
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', self::COLUMN)) {
            $raw = Setting::query()->without('translations')->value(self::COLUMN);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $stored = is_array($decoded) ? $decoded : [];
        }

        return self::$states = self::normalize($stored);
    }

    /** يُبقي فقط الحقول المعروفة والحالات المسموحة لكل حقل. */
    public static function normalize(array $input): array
    {
        $states = [];
        foreach (array_keys(self::catalog()) as $key) {
            $state = $input[$key] ?? self::DEFAULT;
            $states[$key] = in_array($state, self::allowedStates($key), true) ? $state : self::DEFAULT;
        }

        return $states;
    }

    public static function state(string $key): string
    {
        return self::states()[$key] ?? self::DEFAULT;
    }

    public static function isHidden(string $key): bool
    {
        return self::state($key) === self::HIDDEN;
    }

    /** الحقل لم يعد إلزاميًا (اختياري أو مخفي). */
    public static function isRelaxed(string $key): bool
    {
        return in_array(self::state($key), [self::OPTIONAL, self::HIDDEN], true);
    }

    /** ما يحتاجه النموذج في المتصفح: الحقول التي تغيّرت حالتها فقط. */
    public static function clientConfig(): array
    {
        $config = [];
        foreach (self::catalog() as $key => $field) {
            $state = self::state($key);
            if ($state === self::DEFAULT) {
                continue;
            }
            $config[$key] = [
                'state' => $state,
                'names' => $field['names'] ?? [],
                'selector' => $field['selector'] ?? null,
                'wrapper' => $field['wrapper'] ?? null,
            ];
        }

        return $config;
    }

    public static function encode(array $states): string
    {
        return json_encode(array_filter(
            self::normalize($states),
            static fn ($state) => $state !== self::DEFAULT
        ), JSON_UNESCAPED_UNICODE);
    }

    public static function clearCache(): void
    {
        self::$states = null;
    }

    /** تثبيت الحالات داخل الاختبارات دون صف إعدادات. */
    public static function fake(?array $states): void
    {
        self::$states = $states === null ? null : self::normalize($states);
    }
}
