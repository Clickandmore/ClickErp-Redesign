<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Cache;

/**
 * قوالب رسائل روابط دفع ميسر التي تُرسل للعميل لكل نوع سجل.
 * تُحفظ في new_settings وتُعدّل من صفحة إعدادات ميسر؛ القالب الفارغ يرجع للنص الافتراضي.
 */
class PaymentMessageTemplates
{
    public const COMPANY_NAME = 'payment_message.company_name';
    public const CACHE_KEY = 'payment_message_templates.values';

    public const TYPES = [
        'rental' => ['label' => 'عقد التأجير', 'icon' => 'fa-file-signature'],
        'transfer' => ['label' => 'طلب نقل الخدمات', 'icon' => 'fa-people-arrows'],
    ];

    public const VARIABLES = [
        'company_name' => 'اسم الشركة',
        'customer_name' => 'اسم العميل',
        'contract_number' => 'رقم العقد / الطلب',
        'amount' => 'المبلغ المستحق',
        'payment_link' => 'رابط الدفع',
    ];

    public static function key(string $type): string
    {
        return "payment_message.$type.moyasar";
    }

    public static function keys(): array
    {
        return array_merge([self::COMPANY_NAME], array_map([self::class, 'key'], array_keys(self::TYPES)));
    }

    /** تعذر قراءة الإعدادات لا يوقف إرسال رابط الدفع؛ تُستخدم النصوص الافتراضية. */
    public static function all(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 300, fn () => NewSettings::query()
                ->whereIn('key', self::keys())->pluck('value', 'key')->toArray());
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);

            return [];
        }
    }

    public static function companyName(): string
    {
        $name = trim((string) (self::all()[self::COMPANY_NAME] ?? ''));

        return $name !== '' ? $name : (string) (MoyasarSettings::current()['company_name'] ?? '');
    }

    public static function template(string $type): string
    {
        $saved = trim((string) (self::all()[self::key($type)] ?? ''));

        return $saved !== '' ? $saved : self::defaultTemplate($type);
    }

    /** نص عقد التأجير هو نفس الرسالة التي كانت تُرسل قبل إضافة القوالب. */
    public static function defaultTemplate(string $type): string
    {
        if ($type === 'transfer') {
            return implode("\n", [
                'عميلنا {customer_name}،',
                'تم إنشاء طلب نقل خدمات رقم {contract_number} بقيمة {amount} ريال.',
                'يمكنك إتمام الدفع إلكترونياً عبر الرابط:',
                '{payment_link}',
                'شكراً لتعاملكم معنا.',
            ]);
        }

        return implode("\n", [
            'عميلنا {customer_name}،',
            'تم إنشاء عقد إيجار رقم {contract_number} بقيمة {amount} ريال.',
            'يمكنك إتمام الدفع إلكترونياً عبر الرابط:',
            '{payment_link}',
            'شكراً لتعاملكم معنا.',
        ]);
    }

    /** نص الرسالة بعد التعويض؛ السطر الذي يحوي متغيراً غير متوفر يُحذف كاملاً حتى لا يصل للعميل سطر ناقص. */
    public static function render(string $type, array $variables): string
    {
        $variables += ['company_name' => self::companyName()];
        $lines = [];
        foreach (preg_split('/\R/u', self::template($type)) as $line) {
            $missing = false;
            $line = preg_replace_callback('/\{(\w+)\}/', function ($match) use ($variables, &$missing) {
                if (!array_key_exists($match[1], self::VARIABLES)) {
                    return $match[0];
                }
                $value = trim((string) ($variables[$match[1]] ?? ''));
                if ($value === '') {
                    $missing = true;
                }

                return $value;
            }, $line);
            if (!$missing) {
                $lines[] = rtrim($line);
            }
        }

        return trim(implode("\n", $lines));
    }

    public static function amount($value): string
    {
        return number_format((float) $value, 2);
    }

    public static function formValues(): array
    {
        $values = ['company_name' => trim((string) (self::all()[self::COMPANY_NAME] ?? ''))];
        foreach (array_keys(self::TYPES) as $type) {
            $values[$type] = self::template($type);
        }

        return $values;
    }

    /** القالب المطابق للافتراضي يُحفظ فارغاً ليتبع أي تحسين لاحق للنص الافتراضي. */
    public static function save(array $input): void
    {
        NewSettings::query()->updateOrCreate(['key' => self::COMPANY_NAME], ['value' => trim((string) ($input['company_name'] ?? ''))]);

        foreach (array_keys(self::TYPES) as $type) {
            $text = trim(str_replace("\r\n", "\n", (string) ($input[$type] ?? '')));
            NewSettings::query()->updateOrCreate(
                ['key' => self::key($type)],
                ['value' => $text === self::defaultTemplate($type) ? '' : $text]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }
}
