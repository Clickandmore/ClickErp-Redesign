<?php

namespace App\Support;

use App\Models\IngazDelegation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class IngazDelegationTimeline
{
    public const LOG_NAME = 'ingaz_delegation';

    private const EVENT_META = [
        'delegation_created' => ['title' => 'تمت إضافة التفويض', 'icon' => 'fa-plus', 'color' => 'success'],
        'delegation_updated' => ['title' => 'تم تعديل بيانات التفويض', 'icon' => 'fa-pen', 'color' => 'primary'],
        'delegation_status_updated' => ['title' => 'تم تغيير حالة التفويض', 'icon' => 'fa-exchange-alt', 'color' => 'warning'],
        'delegation_cost_confirmed' => ['title' => 'تم تأكيد تكلفة التفويض', 'icon' => 'fa-money-check-alt', 'color' => 'success'],
        'invoice_created' => ['title' => 'تمت إضافة فاتورة', 'icon' => 'fa-file-invoice-dollar', 'color' => 'success'],
        'musaned_transfer_created' => ['title' => 'تمت إضافة تحويل مساند', 'icon' => 'fa-money-check-alt', 'color' => 'info'],
        'musaned_transfer_deleted' => ['title' => 'تم حذف تحويل مساند', 'icon' => 'fa-trash-alt', 'color' => 'danger'],
        'credit_note_created' => ['title' => 'تمت إضافة إشعار دائن', 'icon' => 'fa-file-invoice', 'color' => 'danger'],
        'legacy_updated' => ['title' => 'آخر تعديل مسجل', 'icon' => 'fa-history', 'color' => 'secondary'],
    ];

    private const FIELD_LABELS = [
        'delegation_date_gregorian' => 'التاريخ الميلادي',
        'delegation_date_hijri' => 'التاريخ الهجري',
        'nationality_id' => 'الجنسية',
        'country_id' => 'الدولة',
        'profession_id' => 'المهنة',
        'user_id' => 'العميل',
        'maid_id' => 'العاملة',
        'marketer_id' => 'المسوق',
        'client_source_type' => 'نوع العميل',
        'visa_type' => 'نوع التأشيرة',
        'worker_name' => 'اسم العامل',
        'worker_passport_number' => 'رقم الجواز',
        'worker_passport_issue_date' => 'تاريخ إصدار الجواز',
        'visa_number' => 'رقم التأشيرة',
        'visa_date' => 'تاريخ التأشيرة',
        'worker_religion' => 'الديانة',
        'worker_experience' => 'الخبرة',
        'external_office_name' => 'المكتب الخارجي',
        'external_office_license_number' => 'ترخيص المكتب الخارجي',
        'external_office_address' => 'عنوان المكتب الخارجي',
        'external_office_phone' => 'هاتف المكتب الخارجي',
        'status' => 'حالة التفويض',
        'ingaz_number' => 'رقم إنجاز',
        'msaned_contract_number' => 'رقم عقد مساند',
        'notes' => 'الملاحظات',
        'attachments' => 'المرفقات',
    ];

    public static function record(IngazDelegation $delegation, string $event, array $properties = []): void
    {
        try {
            if (!config('activitylog.enabled', true)
                || !Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
                return;
            }

            $logger = activity(self::LOG_NAME)
                ->performedOn($delegation)
                ->event($event)
                ->withProperties($properties);

            $actor = Auth::guard('admin')->user() ?: Auth::user();
            if ($actor) {
                $logger->causedBy($actor);
            }

            $logger->log(self::meta($event)['title']);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public static function changes(IngazDelegation $delegation, array $before): array
    {
        return collect($delegation->getChanges())
            ->except(['updated_at'])
            ->map(function ($newValue, string $field) use ($before) {
                return [
                    'field' => self::FIELD_LABELS[$field] ?? Str::headline($field),
                    'old' => self::displayValue($field, $before[$field] ?? null),
                    'new' => self::displayValue($field, $newValue),
                ];
            })
            ->values()
            ->all();
    }

    public static function for(IngazDelegation $delegation): Collection
    {
        $activities = collect();

        if (Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
            $activities = Activity::query()
                ->with('causer')
                ->where('subject_type', IngazDelegation::class)
                ->where('subject_id', $delegation->id)
                ->where('log_name', self::LOG_NAME)
                ->oldest('created_at')
                ->oldest('id')
                ->get();
        }

        $timeline = $activities->map(fn (Activity $activity) => self::activityEntry($activity));
        $events = $activities->pluck('event')->filter();

        if (!$events->contains('delegation_created')) {
            $timeline->push(self::legacyEntry(
                'delegation_created',
                $delegation->created_at,
                ['summary' => 'أُضيف التفويض قبل تفعيل السجل التفصيلي؛ اسم المنفذ غير متوفر.']
            ));
        }

        if ($delegation->updated_at && $delegation->created_at
            && $delegation->updated_at->gt($delegation->created_at)
            && !$events->intersect(['delegation_updated', 'delegation_status_updated'])->count()) {
            $timeline->push(self::legacyEntry(
                'legacy_updated',
                $delegation->updated_at,
                ['summary' => 'يوجد تعديل سابق على التفويض، لكن تفاصيله واسم المنفذ غير متوفرين.']
            ));
        }

        if ($delegation->invoice && !$events->contains('invoice_created')) {
            $timeline->push(self::legacyEntry('invoice_created', $delegation->invoice->created_at, [
                'invoice_id' => $delegation->invoice->id,
                'total' => (float) $delegation->invoice->total_due,
                'summary' => 'فاتورة موجودة قبل تفعيل السجل التفصيلي.',
            ]));
        }

        $transfer = $delegation->invoice?->musanedTransferItem?->transfer;
        if ($transfer && !$events->contains('musaned_transfer_created')) {
            $timeline->push(self::legacyEntry('musaned_transfer_created', $transfer->created_at, [
                'transfer_id' => $transfer->id,
                'reference_number' => $transfer->reference_number,
                'transferred_amount' => (float) ($delegation->invoice->musanedTransferItem->transferred_amount ?? 0),
                'summary' => 'تحويل مساند موجود قبل تفعيل السجل التفصيلي.',
            ], $transfer->createdBy?->name));
        }

        foreach ($delegation->invoice?->recruitmentCreditNotes ?? [] as $creditNote) {
            $alreadyLogged = $activities->contains(function (Activity $activity) use ($creditNote) {
                return $activity->event === 'credit_note_created'
                    && (int) $activity->properties->get('credit_note_id') === (int) $creditNote->id;
            });

            if (!$alreadyLogged) {
                $timeline->push(self::legacyEntry('credit_note_created', $creditNote->created_at, [
                    'credit_note_id' => $creditNote->id,
                    'total' => (float) $creditNote->total,
                    'reason' => $creditNote->reason,
                    'summary' => 'إشعار دائن موجود قبل تفعيل السجل التفصيلي.',
                ], $creditNote->createdBy?->name));
            }
        }

        return $timeline
            ->filter(fn (array $entry) => $entry['occurred_at'] !== null)
            ->sortByDesc(fn (array $entry) => $entry['occurred_at']->getTimestamp())
            ->values();
    }

    private static function activityEntry(Activity $activity): array
    {
        $properties = $activity->properties->toArray();

        return self::entry(
            (string) ($activity->event ?: $activity->description),
            $activity->created_at,
            $properties,
            $activity->causer?->name ?: 'غير مسجل',
            false
        );
    }

    private static function legacyEntry(string $event, $occurredAt, array $properties, ?string $actor = null): array
    {
        return self::entry($event, $occurredAt, $properties, $actor ?: 'غير مسجل (سجل سابق)', true);
    }

    private static function entry(string $event, $occurredAt, array $properties, string $actor, bool $legacy): array
    {
        return [
            'event' => $event,
            'title' => self::meta($event)['title'],
            'icon' => self::meta($event)['icon'],
            'color' => self::meta($event)['color'],
            'actor' => $actor,
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : null,
            'properties' => $properties,
            'legacy' => $legacy,
        ];
    }

    private static function meta(string $event): array
    {
        return self::EVENT_META[$event] ?? [
            'title' => 'تم تحديث التفويض',
            'icon' => 'fa-history',
            'color' => 'secondary',
        ];
    }

    private static function displayValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $optionLabels = [
            'client_source_type' => [
                'existing_in_program' => 'موجود في البرنامج',
                'separate_client' => 'عميل منفصل',
            ],
            'visa_type' => [
                'electronic_ingaz_delegation' => 'تفويض إنجاز إلكتروني',
            ],
            'worker_religion' => [
                'muslim' => 'مسلم',
                'non_muslim' => 'غير مسلم',
                'unspecified' => 'غير محدد',
            ],
            'worker_experience' => [
                'previously_worked' => 'سبق العمل',
                'not_previously_worked' => 'لم يسبق العمل',
                'appointed_by_client' => 'معينة من قبل العميل',
                'unspecified' => 'غير محدد',
                'known_service' => 'خدمة معروفة',
            ],
        ];

        if (isset($optionLabels[$field][(string) $value])) {
            return $optionLabels[$field][(string) $value];
        }

        if ($field === 'status') {
            return IngazDelegation::statusOptions((string) $value)[(string) $value] ?? (string) $value;
        }

        if ($field === 'attachments') {
            $attachments = is_array($value) ? $value : json_decode((string) $value, true);

            return is_array($attachments) ? count($attachments) . ' مرفق' : '-';
        }

        $relatedValue = self::relatedValue($field, $value);
        if ($relatedValue !== null) {
            return $relatedValue;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return Str::limit((string) $value, 160);
    }

    private static function relatedValue(string $field, $value): ?string
    {
        try {
            $model = match ($field) {
                'country_id' => \App\Models\Country::query()->find($value),
                'nationality_id' => \App\Models\ingaz\Nationality::query()->find($value),
                'profession_id' => \App\Models\ingaz\JobTitle::query()->find($value),
                'user_id' => \App\Models\ingaz\User::query()->find($value),
                'maid_id' => \App\Models\ingaz\Maid::query()->find($value),
                'marketer_id' => \App\Models\auth\Admin::query()->find($value),
                default => null,
            };

            if (!$model) {
                return null;
            }

            return (string) ($model->name
                ?? $model->title
                ?? $model->sub_title
                ?? $model->name_ar
                ?? $model->name_en
                ?? $value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
