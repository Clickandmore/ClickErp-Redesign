<?php

namespace App\Models\Hourly;

use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * بيانات العميل الخاصة بالساعات بجانب سجله في users (المشترك مع بقية الأنظمة).
 * الحقول المشتركة (الاسم، الجوال، البريد، الهوية، الميلاد، الهاتف 2) تبقى في users.
 * الحذف هنا مؤقت ويخص قسم الساعات فقط؛ سجل العميل المشترك لا يُحذف.
 */
class HourlyCustomerProfile extends Model
{
    use LogsActivity, SoftDeletes;

    public const TYPES = ['individual', 'company'];
    public const IDENTITY_TYPES = ['national_id', 'iqama', 'passport'];
    public const LANGUAGES = ['ar', 'en'];
    /** حدود عمر العميل الفرد بالسنوات (تاريخ الميلاد). */
    public const MIN_AGE = 18;
    public const MAX_AGE = 100;
    public const MARKETING_SOURCES = ['google', 'facebook', 'instagram', 'snapchat', 'tiktok', 'x', 'whatsapp', 'referral', 'other'];
    /** مدة الدفع؛ null = غير محدد (منطق الدفع الحالي كما هو). */
    public const PAYMENT_TERMS = ['prepaid', 'after_visit', 'weekly', 'days_15', 'monthly'];

    protected $guarded = ['id'];
    protected $casts = ['verified_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(HourlyCustomerGroup::class, 'group_id');
    }

    /** مدة الدفع المحفوظة للعميل (لملف غير محذوف)، أو null إن لم تُحدد أو لم تُرحَّل الأعمدة بعد. */
    public static function termFor(int $userId): ?string
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('hourly_customer_profiles', 'payment_term')) return null;
        $term = static::where('user_id', $userId)->value('payment_term');

        return in_array($term, self::PAYMENT_TERMS, true) ? $term : null;
    }

    /** تاريخ استحقاق الدفع: المقدم اليوم، وبعد الزيارة يوم أول زيارة، والبقية بعد أول زيارة بالمدة. */
    public static function dueDate(string $term, string $firstVisit): string
    {
        $visit = \Carbon\Carbon::parse($firstVisit)->startOfDay();

        return match ($term) {
            'prepaid' => today()->toDateString(),
            'weekly' => $visit->addWeek()->toDateString(),
            'days_15' => $visit->addDays(15)->toDateString(),
            'monthly' => $visit->addMonthNoOverflow()->toDateString(),
            default => $visit->toDateString(),
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs()->useLogName('hourly_customers');
    }
}
