<?php

namespace App\Support;

class ShelterDocumentFields
{
    public static function kinds(): array
    {
        return ['entry' => 'استلام عاملة للإيواء', 'release' => 'تسليم عاملة من الإيواء'];
    }

    public static function fields(string $kind): array
    {
        $common = [
            'date' => ['التاريخ / Date', 'date'],
            'contract_number' => ['رقم العقد / Contract No.', 'text'],
            'worker_name' => ['اسم العامل/ة / Worker Name', 'text'],
            'nationality' => ['الجنسية / Nationality', 'text'],
            'passport' => ['رقم الجواز / Passport No.', 'text'],
            'worker_id' => ['الإقامة / الحدود / Iqama / Border No.', 'text'],
        ];
        $specific = $kind === 'entry' ? [
            'client_name' => ['اسم العميل / Client Name', 'text'],
            'client_id' => ['هوية العميل / Client ID', 'text'],
            'client_phone' => ['جوال العميل / Client Mobile', 'text'],
            'arrival_date' => ['تاريخ الوصول / Arrival Date', 'date'],
            'entry_date' => ['تاريخ الاستلام / Receipt Date', 'date'],
            'reason' => ['سبب الاستلام / Reason for Receipt', 'choices', [
                'refusal' => 'رفض العمل / Refusal to Work', 'unsuitable' => 'عدم المناسبة / Not Suitable',
                'return' => 'طلب العودة / Return Home', 'dispute' => 'خلاف / Dispute',
                'termination' => 'إنهاء العقد / Contract Termination', 'transfer' => 'نقل خدمات / Service Transfer',
                'health' => 'أسباب صحية / Health Reasons', 'other' => 'أخرى / Other',
            ]],
            'details' => ['التفاصيل / Details', 'textarea'],
        ] : [
            'exit_time' => ['وقت الخروج / Exit Time', 'time'],
            'entry_date' => ['تاريخ دخول الإيواء / Accommodation Entry Date', 'date'],
            'reason' => ['سبب الخروج / Reason for Release', 'choices', [
                'employer' => 'تسليم لصاحب العمل / Handover to Employer', 'transfer' => 'نقل خدمات / Service Transfer',
                'repatriation' => 'ترحيل / Repatriation', 'authority' => 'تسليم للجهة المختصة / Handover to Authority',
                'return' => 'عودة للعمل / Return to Work', 'other' => 'أخرى / Other',
            ]],
            'recipient_name' => ['اسم المستلم / Recipient Name', 'text'],
            'recipient_id' => ['هوية المستلم / Recipient ID', 'text'],
            'recipient_phone' => ['جوال المستلم / Recipient Mobile', 'text'],
            'recipient_capacity' => ['صفة المستلم / Capacity', 'text'],
            'department' => ['القسم / Department', 'text'],
            'job_title' => ['المسمى الوظيفي / Job Title', 'text'],
        ];
        return $common + $specific + [
            'other_reason' => ['سبب آخر / Other Reason', 'text'],
            'belongings' => ['المستندات والمتعلقات / Documents & Belongings', 'choices', [
                'passport' => 'جواز السفر / Passport', 'iqama' => 'الإقامة / Iqama',
                'personal' => 'أمتعة شخصية / Personal Belongings', 'other' => 'أخرى / Other',
            ]],
            'other_belongings' => ['متعلقات أخرى / Other Belongings', 'text'],
            'remarks' => ['ملاحظات / Remarks', 'textarea'],
            'employee_name' => ['الموظف المسؤول / Responsible Employee', 'text'],
            'supervisor_name' => ['مسؤول الإيواء / Accommodation Supervisor', 'text'],
        ];
    }

    // Groups the fields the way the paper forms are laid out. `signatures` fields are
    // printed only as names in the signature table.
    public static function sections(string $kind): array
    {
        $sections = $kind === 'entry' ? [
            'meta' => ['', ['date', 'contract_number']],
            'client' => ['بيانات العميل | Client Information', ['client_name', 'client_id', 'client_phone']],
            'worker' => ['بيانات العامل/ة | Worker Information', ['worker_name', 'nationality', 'passport', 'worker_id', 'arrival_date', 'entry_date']],
            'reason' => ['سبب الاستلام | Reason for Receipt', ['reason', 'other_reason', 'details']],
            'belongings' => ['المستندات والمتعلقات | Documents & Belongings', ['belongings', 'other_belongings', 'remarks']],
            'signatures' => ['', ['employee_name', 'supervisor_name']],
        ] : [
            'meta' => ['', ['date', 'exit_time', 'contract_number']],
            'worker' => ['بيانات العامل/ة | Worker Information', ['worker_name', 'nationality', 'passport', 'worker_id', 'entry_date']],
            'reason' => ['سبب الخروج | Reason for Release', ['reason', 'other_reason']],
            'recipient' => ['بيانات المستلم | Recipient Information', ['recipient_name', 'recipient_id', 'recipient_phone', 'recipient_capacity']],
            'employee' => ['بيانات الموظف المسؤول عن خروج العامل/ة | Responsible Employee', ['employee_name', 'department', 'job_title']],
            'belongings' => ['المستندات والمتعلقات المسلمة | Documents & Belongings', ['belongings', 'other_belongings', 'remarks']],
            'signatures' => ['', ['supervisor_name']],
        ];
        $placed = array_merge(...array_column($sections, 1));
        $rest = array_values(array_diff(array_keys(self::fields($kind)), $placed));
        if ($rest) $sections['other'] = ['بيانات إضافية | Additional Information', $rest];

        return $sections;
    }

    // Free-text companion printed next to the "other" checkbox of a choices field.
    public static function otherField(string $key): ?string
    {
        return ['reason' => 'other_reason', 'belongings' => 'other_belongings'][$key] ?? null;
    }

    // "التاريخ / Date" → ['التاريخ', 'Date']; the English part starts at the first "/ <Latin>".
    public static function labelParts(string $label): array
    {
        return preg_match('/^(.*?)\s*[\/|]\s*([A-Za-z].*)$/u', $label, $m) ? [trim($m[1]), trim($m[2])] : [$label, ''];
    }

    public static function starter(string $kind): array
    {
        return [
            'kind' => $kind,
            'name' => self::kinds()[$kind],
            'company_name' => '[اسم_الشركة]',
            'title' => $kind === 'entry'
                ? 'نموذج استلام عاملة من العميل | DOMESTIC WORKER RECEIPT FORM'
                : 'نموذج خروج عاملة من الإيواء | WORKER ACCOMMODATION RELEASE FORM',
            'declaration' => $kind === 'entry'
                ? 'أقر بتسليم العاملة الموضحة بياناتها أعلاه إلى [اسم_الشركة]، وبصحة بيانات وسبب التسليم والمتعلقات الموضحة في هذا النموذج.'
                : 'تم خروج العاملة الموضحة بياناتها أعلاه من الإيواء وتسليمها للجهة/الشخص الموضح بياناته في هذا النموذج، بعلم الموظف المسؤول الموضح أعلاه.',
            'footer' => '',
            'active' => true,
        ];
    }
}
