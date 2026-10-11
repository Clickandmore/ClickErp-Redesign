<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * قواعد صيغ وحدود حقول السيرة الذاتية (إضافة/تعديل).
 *
 * مصدر واحد تستخدمه MaidRequest للتحقق في الخادم، والواجهة
 * (admin.ingaz.maids.partials.field-rules) لحدود التواريخ والتنبيه الفوري.
 */
class MaidFieldRules
{
    /** أقل وأكبر عمر مقبول للعاملة عند إدخال تاريخ الميلاد. */
    public const MIN_AGE = 18;
    public const MAX_AGE = 60;

    /** أقل عمر لبداية خبرة عمل سابقة أو للوصول إلى المملكة، محسوبًا من تاريخ الميلاد. */
    public const MIN_WORK_AGE = 18;

    /** مدة العقد: عدد صحيح من الشهور. */
    public const DURATION_MIN_MONTHS = 1;
    public const DURATION_MAX_MONTHS = 60;
    public const DURATION_REGEX = '/^[0-9]{1,2}$/';

    /** رقم الهوية: 10 أرقام يبدأ بـ 1 (هوية وطنية) أو 2 (إقامة). */
    public const ID_NUMBER_REGEX = '/^[12][0-9]{9}$/';

    /** أقدم تاريخ إصدار مقبول لجواز السفر (بالسنوات من اليوم). */
    public const PASSPORT_ISSUE_MAX_YEARS = 20;

    /** اسم لاتيني: حروف إنجليزية ومسافات ونقطة وفاصلة علوية وشرطة. */
    public const LATIN_NAME_REGEX = "/^[A-Za-z][A-Za-z .'\-]*$/";

    /** رقم الجواز: حروف إنجليزية وأرقام فقط. */
    public const PASSPORT_MIN_LENGTH = 6;
    public const PASSPORT_MAX_LENGTH = 20;
    public const PASSPORT_REGEX = '/^[A-Za-z0-9]{6,20}$/';

    /** رقم الحدود: أرقام فقط. */
    public const BORDER_NUMBER_MAX_LENGTH = 20;
    public const BORDER_NUMBER_REGEX = '/^[0-9]{1,20}$/';

    /** الراتب: رقم صحيح موجب. */
    public const SALARY_MIN = 1;
    public const SALARY_MAX = 50000;

    /** الطول (سم) والوزن (كجم) وعدد الأطفال: أرقام صحيحة ضمن حدود منطقية. */
    public const HEIGHT_MIN = 100;
    public const HEIGHT_MAX = 220;
    public const WEIGHT_MIN = 30;
    public const WEIGHT_MAX = 150;
    public const CHILDREN_MIN = 0;
    public const CHILDREN_MAX = 15;

    /** حقول نصية تقبل الحروف (عربية أو إنجليزية) والمسافات فقط. */
    public const LETTERS_ONLY_REGEX = '/^[\p{L}\p{M} ]+$/u';

    /** الحد الأقصى لعدد الحروف في الحقول النصية. */
    public const MAX_LENGTHS = [
        'name:en' => 100,
        'passport_issue_place' => 100,
        'relative_name' => 100,
        'residence_city' => 100,
        'duration' => 50,
        'additional_notes' => 2000,
        // يقبل كود iframe كاملًا قبل استخراج الرابط منه.
        'video_link' => 2000,
        'musaned_link' => 2000,
    ];
    public const DEFAULT_TEXT_MAX_LENGTH = 255;
    public const DEFAULT_TEXTAREA_MAX_LENGTH = 2000;
    /** حقول type="number" (مثل مدة الخبرة ومبالغ بنود التأجير): أقصى عدد خانات. */
    public const NUMBER_MAX_LENGTH = 10;

    /** صيغ الصور التي يعرضها المتصفح في المعاينة. */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'bmp', 'webp'];

    /** صيغ الصور المقبولة في الخادم (قاعدة mimes تتعرف على jfif كـ jpg). */
    public const IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /** الحد الأقصى لحجم الصور والمرفقات (كيلوبايت). */
    public const FILE_MAX_KB = 10240;
    public const VIDEO_MAX_KB = 204800;
    /** ملف الجواز في السيرة المختصرة. */
    public const PASSPORT_FILE_MAX_KB = 5120;

    /**
     * الصيغ والحجم المسموح لكل حقل ملف؛ تُفحص فور اختيار الملف أو سحبه وإفلاته.
     * (قواعد الخادم لكل حقل في MaidRequest.)
     */
    public static function fileRules(): array
    {
        return [
            'image' => ['extensions' => self::IMAGE_EXTENSIONS, 'max_kb' => self::FILE_MAX_KB],
            'cv_file' => ['extensions' => array_merge(['pdf'], self::IMAGE_EXTENSIONS), 'max_kb' => self::FILE_MAX_KB],
            'maid_cv_file' => ['extensions' => ['pdf'], 'max_kb' => self::FILE_MAX_KB],
            'driving_license_file' => ['extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 'max_kb' => self::FILE_MAX_KB],
            'video' => ['extensions' => ['mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv', 'm4v'], 'max_kb' => self::VIDEO_MAX_KB],
        ];
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::today();
    }

    /** أقدم تاريخ ميلاد مقبول: عمر MAX_AGE لم يكمل بعد MAX_AGE + 1. */
    public static function birthDateMin(): string
    {
        return self::today()->subYears(self::MAX_AGE + 1)->addDay()->toDateString();
    }

    /** أحدث تاريخ ميلاد مقبول: أكمل MIN_AGE سنة. */
    public static function birthDateMax(): string
    {
        return self::today()->subYears(self::MIN_AGE)->toDateString();
    }

    public static function passportIssueDateMin(): string
    {
        return self::today()->subYears(self::PASSPORT_ISSUE_MAX_YEARS)->toDateString();
    }

    public static function imageAccept(): string
    {
        return implode(',', array_map(fn ($ext) => '.' . $ext, self::IMAGE_EXTENSIONS));
    }

    /** القيم التي تحتاجها الواجهة (حدود التواريخ والصيغ). */
    public static function clientConfig(): array
    {
        return [
            'today' => self::today()->toDateString(),
            'minAge' => self::MIN_AGE,
            'maxAge' => self::MAX_AGE,
            'birthDateMin' => self::birthDateMin(),
            'birthDateMax' => self::birthDateMax(),
            'passportIssueMaxYears' => self::PASSPORT_ISSUE_MAX_YEARS,
            'passportIssueDateMin' => self::passportIssueDateMin(),
            'minWorkAge' => self::MIN_WORK_AGE,
            'durationMin' => self::DURATION_MIN_MONTHS,
            'durationMax' => self::DURATION_MAX_MONTHS,
            'videoMaxMb' => self::VIDEO_MAX_KB / 1024,
            'passportMinLength' => self::PASSPORT_MIN_LENGTH,
            'passportMaxLength' => self::PASSPORT_MAX_LENGTH,
            'borderNumberMaxLength' => self::BORDER_NUMBER_MAX_LENGTH,
            'imageExtensions' => self::IMAGE_EXTENSIONS,
            'numbers' => [
                'salary' => ['min' => self::SALARY_MIN, 'max' => self::SALARY_MAX],
                'height' => ['min' => self::HEIGHT_MIN, 'max' => self::HEIGHT_MAX],
                'weight' => ['min' => self::WEIGHT_MIN, 'max' => self::WEIGHT_MAX],
                'childrens_count' => ['min' => self::CHILDREN_MIN, 'max' => self::CHILDREN_MAX],
                'duration' => ['min' => self::DURATION_MIN_MONTHS, 'max' => self::DURATION_MAX_MONTHS],
            ],
            'maxLengths' => self::MAX_LENGTHS,
            'defaultTextMaxLength' => self::DEFAULT_TEXT_MAX_LENGTH,
            'defaultTextareaMaxLength' => self::DEFAULT_TEXTAREA_MAX_LENGTH,
            'numberMaxLength' => self::NUMBER_MAX_LENGTH,
            'files' => self::fileRules(),
        ];
    }
}
