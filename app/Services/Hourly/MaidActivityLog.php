<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyDriver;
use App\Models\ingaz\Maid;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;

/**
 * سجل تغييرات ملف العاملة مبنيّ على activitylog الموجود في نموذج Maid:
 * كل نشاط يُفكّ إلى صف لكل حقل تغيّر (بما فيها مفاتيح hourly_settings المتداخلة).
 */
class MaidActivityLog
{
    private const SKIP = ['updated_at', 'created_at', 'deleted_at', 'hourly_password', 'hourly_auth_version'];

    private const LABELS = [
        'name' => 'الاسم', 'nationality_id' => 'الجنسية', 'religion_id' => 'الديانة', 'passport_num' => 'رقم جواز السفر',
        'phone_1' => 'الهاتف', 'phone_2' => 'الهاتف الثاني', 'border_number' => 'رقم الحدود', 'border_expiry_date' => 'انتهاء رقم الحدود',
        'id_number' => 'رقم الإقامة', 'id_expiry_date' => 'انتهاء الإقامة', 'identity_proof_type' => 'نوع إثبات الهوية',
        'has_final_exit_visa' => 'تأشيرة خروج نهائي', 'final_exit_visa_issue_date' => 'إصدار تأشيرة الخروج', 'final_exit_visa_expiry_date' => 'انتهاء تأشيرة الخروج',
        'has_medical_insurance' => 'تأمين طبي', 'medical_insurance_expiry_date' => 'انتهاء التأمين الطبي',
        'birth_date' => 'تاريخ الميلاد', 'age' => 'العمر', 'height' => 'الطول', 'weight' => 'الوزن', 'languages' => 'اللغات', 'skills' => 'المهارات',
        'experience_years' => 'سنوات الخبرة', 'experience_status' => 'الخبرة', 'salary' => 'الراتب', 'price' => 'السعر', 'office_id' => 'المكتب',
        'status' => 'الحالة', 'available' => 'متاحة', 'available_at' => 'تاريخ الإتاحة', 'arrival_date' => 'تاريخ الوصول', 'is_show' => 'ظاهرة',
        'is_pinned' => 'مثبّتة', 'image' => 'الصورة', 'cv_file' => 'ملف السيرة الذاتية', 'cv_preview' => 'معاينة السيرة الذاتية',
        'passport_image_path' => 'صورة الجواز', 'rental_source' => 'مصدر التأجير', 'rental_mode' => 'نظام التأجير',
        'hourly_username' => 'اسم مستخدم تطبيق الساعات', 'hourly_is_suspended' => 'تعليق إتاحة الساعات',
        'hourly_settings' => 'إعدادات الساعات',
    ];

    private const SEGMENTS = [
        'requires_driver' => 'تحتاج سائقًا', 'max_orders_per_hour' => 'أقصى طلبات بالساعة', 'account_enabled' => 'حساب التطبيق مفعّل',
        'login_access' => 'قنوات الدخول', 'login_phone' => 'هاتف الدخول', 'work_schedule' => 'أيام وساعات العمل', 'service_category_ids' => 'الخدمات',
        'mode' => 'النظام', 'start' => 'البداية', 'end' => 'النهاية', 'profile' => 'الملف', 'rates' => 'أسعار الخدمات',
        'service_category_id' => 'الخدمة', 'payment_type' => 'نوع الدفع', 'payment_value' => 'المبلغ',
        'bank_name' => 'اسم البنك', 'iban_owner' => 'صاحب الحساب', 'iban' => 'IBAN',
    ];

    /** حقول نعم/لا تُحفظ أحيانًا كـ "1"/"0". */
    private const FLAGS = ['requires_driver', 'account_enabled', 'available', 'is_show', 'is_pinned', 'has_final_exit_visa', 'has_medical_insurance', 'hourly_is_suspended'];

    private const VALUES = [
        'off' => 'إجازة', 'all_day' => 'طوال اليوم', 'hours' => 'ساعات محددة', 'hourly' => 'بالساعة', 'monthly' => 'راتب شهري',
        'regular' => 'عادي', 'inside' => 'داخلي', 'outside' => 'خارجي', 'both' => 'الويب والتطبيق', 'web' => 'الويب', 'mobile' => 'التطبيق',
        'female' => 'أنثى', 'male' => 'ذكر',
    ];

    public function paginate(Maid $maid, int $perPage = 15): LengthAwarePaginator
    {
        $page = Activity::query()->with('causer')
            ->where('subject_type', $maid->getMorphClass())->where('subject_id', $maid->getKey())
            ->latest('id')->paginate($perPage);

        return $page->setCollection($page->getCollection()->flatMap(fn (Activity $activity) => $this->rows($activity))->values());
    }

    /** @return array<int, array{at:mixed,field:string,old:string,new:string,by:string,source:string}> */
    public function rows(Activity $activity): array
    {
        $by = $activity->causer?->name ?? ($activity->causer_id ? '#'.$activity->causer_id : 'النظام');
        $source = $this->source($activity->causer_type);
        $base = ['at' => $activity->created_at, 'by' => $by, 'source' => $source];

        if ($activity->event === 'created') return [$base + ['field' => 'إنشاء ملف العاملة', 'old' => '—', 'new' => '—']];
        if ($activity->event === 'deleted') return [$base + ['field' => 'حذف ملف العاملة', 'old' => '—', 'new' => '—']];

        $new = Arr::except((array) ($activity->properties['attributes'] ?? []), self::SKIP);
        $old = Arr::except((array) ($activity->properties['old'] ?? []), self::SKIP);
        $rows = [];
        foreach (array_unique(array_merge(array_keys($new), array_keys($old))) as $field) {
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;
            $changes = is_array($before) || is_array($after)
                ? $this->nestedChanges($field, (array) $before, (array) $after)
                : [$field => [$before, $after]];
            foreach ($changes as $path => [$from, $to]) {
                if ($this->same($from, $to) || str_contains($path, 'password')) continue;
                $flag = in_array(last(explode('.', $path)), self::FLAGS, true);
                $rows[] = $base + ['field' => $this->label($path), 'old' => $this->format($from, $flag), 'new' => $this->format($to, $flag)];
            }
        }

        return $rows;
    }

    /** فرق المفاتيح المتداخلة؛ القوائم (كالخدمات) تُقارن كقيمة واحدة. */
    private function nestedChanges(string $field, array $before, array $after): array
    {
        $list = fn ($value) => is_array($value) && array_is_list($value) && !array_filter($value, 'is_array');
        $flat = function (array $items, string $prefix) use (&$flat, $list): array {
            $out = [];
            foreach ($items as $key => $value) {
                $path = $prefix.'.'.$key;
                if (is_array($value) && !$list($value) && $value !== []) $out += $flat($value, $path);
                else $out[$path] = $value;
            }
            return $out;
        };
        $a = $flat($before, $field);
        $b = $flat($after, $field);
        $changes = [];
        foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $path) {
            $changes[$path] = [$a[$path] ?? null, $b[$path] ?? null];
        }

        return $changes;
    }

    /** تحويلات الأنواع (null ↔ false أو "") ليست تغييرًا فعليًا. */
    private function same($from, $to): bool
    {
        $blank = fn ($v) => $v === null || $v === '' || $v === [] || $v === false;
        if ($blank($from) && $blank($to)) return true;
        if (is_array($from) || is_array($to)) return json_encode($from) === json_encode($to);

        return (string) (is_bool($from) ? (int) $from : $from) === (string) (is_bool($to) ? (int) $to : $to);
    }

    private function label(string $path): string
    {
        $parts = explode('.', $path);
        $labels = [self::LABELS[array_shift($parts)] ?? $path];
        foreach ($parts as $part) {
            $labels[] = HourlyDriver::DAYS[$part] ?? self::SEGMENTS[$part] ?? (MaidProfileService::FIELDS[$part][0] ?? (is_numeric($part) ? '#'.($part + 1) : $part));
        }

        return implode(' › ', $labels);
    }

    private function format($value, bool $flag = false): string
    {
        if ($value === null || $value === '' || $value === []) return '—';
        if (is_bool($value) || ($flag && in_array((string) $value, ['0', '1'], true))) return $value ? 'نعم' : 'لا';
        if (is_array($value)) return implode('، ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value));
        $value = (string) $value;
        // التواريخ محفوظة بتوقيت UTC في السجل؛ نعرضها بتوقيت التطبيق، وبلا ساعة إن كانت منتصف الليل.
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            $date = \Carbon\Carbon::parse($value)->timezone(config('app.timezone'));
            return $date->format($date->format('H:i') === '00:00' ? 'Y-m-d' : 'Y-m-d H:i');
        }
        if (str_starts_with($value, 'r2://') || preg_match('#^images/#', $value)) return 'ملف مرفق';

        return self::VALUES[$value] ?? $value;
    }

    private function source(?string $causerType): string
    {
        return match (true) {
            $causerType === null => 'النظام',
            str_contains($causerType, 'HourlyMaidAccount') => 'تطبيق العاملة',
            str_contains($causerType, 'Admin') => 'لوحة التحكم',
            default => class_basename($causerType),
        };
    }
}
