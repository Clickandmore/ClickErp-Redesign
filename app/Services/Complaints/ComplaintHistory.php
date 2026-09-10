<?php

namespace App\Services\Complaints;

use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintComment;
use App\Models\ingaz\ComplaintAttachment;

class ComplaintHistory
{
    public function forComplaint(Complaint $complaint)
    {
        $activityModel = config('activitylog.activity_model');
        return $activityModel::with('causer')->where(function ($query) use ($complaint) {
            $query->where(function ($query) use ($complaint) {
                $query->where('subject_type', $complaint->getMorphClass())->where('subject_id', $complaint->id);
            })->orWhere(function ($query) use ($complaint) {
                $query->whereIn('subject_type', [(new ComplaintComment)->getMorphClass(), (new ComplaintAttachment)->getMorphClass()])
                    ->where(function ($query) use ($complaint) {
                        $query->where('properties->attributes->complaint_id', $complaint->id)
                            ->orWhere('properties->old->complaint_id', $complaint->id)
                            ->orWhere(function ($query) use ($complaint) {
                                $query->where('subject_type', (new ComplaintComment)->getMorphClass())
                                    ->whereIn('subject_id', $complaint->comments()->select('id'));
                            })->orWhere(function ($query) use ($complaint) {
                                $query->where('subject_type', (new ComplaintAttachment)->getMorphClass())
                                    ->whereIn('subject_id', $complaint->attachmentFiles()->select('id'));
                            });
                    });
            });
        })->orderByDesc('id')->paginate(30, ['*'], 'history_page');
    }

    public function summary(Complaint $complaint): array
    {
        $model = config('activitylog.activity_model');
        $creation = $model::with('causer')->where('subject_type', $complaint->getMorphClass())
            ->where('subject_id', $complaint->id)->where(function ($query) {
                $query->where('event', 'created')->orWhere('description', 'created');
            })->oldest('id')->first();
        $finished = in_array($complaint->status, ['resolved', 'closed', 'rejected']);
        $end = $finished ? ($complaint->closed_at ?: $complaint->resolved_at) : now();
        return [
            'creator' => $creation ? self::actor($creation) : ($complaint->source_created_by ?: 'غير مسجل'),
            'duration_label' => $finished ? 'مدة المعالجة' : 'المدة منذ فتح الشكوى',
            'duration' => $end ? $complaint->created_at->locale('ar')->diffForHumans($end, [
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2,
            ]) : 'غير متاحة لعدم تسجيل تاريخ الإنهاء',
        ];
    }

    public static function actor($event): string
    {
        return $event->properties->get('actor_name') ?: $event->causer?->name ?: 'غير مسجل / النظام';
    }

    public static function present($event): array
    {
        $kind = $event->event ?: $event->description;
        $isComment = $event->subject_type === (new ComplaintComment)->getMorphClass();
        $isAttachment = $event->subject_type === (new ComplaintAttachment)->getMorphClass();
        $before = $event->properties->get('old', []);
        $after = $event->properties->get('attributes', []);
        $title = $event->description;
        if ($isComment) {
            $title = ['created' => 'أضاف تعليقاً', 'updated' => 'عدّل تعليقاً', 'deleted' => 'حذف تعليقاً'][$kind] ?? 'تحديث تعليق';
            $allowed = ['comment'];
        } elseif ($isAttachment) {
            $title = ['created' => 'أضاف مرفقاً', 'updated' => 'عدّل مرفقاً', 'deleted' => 'حذف مرفقاً'][$kind] ?? 'تحديث مرفق';
            $allowed = [isset($after['display_name']) || isset($before['display_name']) ? 'display_name' : 'original_name'];
        } else {
            $title = ['created' => 'أضاف الشكوى', 'updated' => 'حدّث الشكوى', 'deleted' => 'حذف الشكوى'][$kind] ?? $title;
            // Creation is a single event; the complaint's contents already appear above.
            $allowed = $kind === 'created' ? [] : [
                'status', 'priority', 'assigned_to', 'due_at', 'schedule_notes',
                'subject', 'details', 'message', 'response', 'last_response',
                'relation_type', 'contract_id', 'order_id', 'related_user_id', 'recruitment_office_id',
                'relation_reference', 'relation_notes', 'complaint_type_id',
                'complainant_name', 'complainant_phone', 'against_name', 'against_phone',
                'maid_passport', 'maid_nationality_id', 'closure_reason',
            ];
            if (array_key_exists('details', $after) || array_key_exists('details', $before)) {
                $allowed = array_diff($allowed, ['message']);
            }
            if (($after['last_response'] ?? null) === ($after['response'] ?? null)) {
                $allowed = array_diff($allowed, ['last_response']);
            }
        }
        $changes = [];
        foreach ($allowed as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ((blank($old) && blank($new)) || $old === $new) continue;
            $changes[] = [
                'label' => self::label($key), 'before' => self::value($key, $old),
                'after' => self::value($key, $new), 'has_before' => filled($old),
                'removed' => blank($new),
            ];
        }
        return ['title' => $title, 'changes' => $changes];
    }

    public static function label(string $key): string
    {
        return [
            'subject' => 'الموضوع', 'message' => 'نص الشكوى', 'details' => 'التفاصيل',
            'status' => 'الحالة', 'priority' => 'الأولوية', 'assigned_to' => 'الموظف المختص',
            'complainant_name' => 'مقدم الشكوى', 'complainant_phone' => 'جوال مقدم الشكوى',
            'complainant_type' => 'صفة مقدم الشكوى', 'against_type' => 'صفة المشتكى عليه',
            'against_name' => 'المشتكى عليه', 'against_phone' => 'جوال المشتكى عليه',
            'relation_type' => 'نوع الربط', 'contract_id' => 'العقد', 'order_id' => 'الطلب',
            'related_user_id' => 'العميل المرتبط', 'recruitment_office_id' => 'المكتب الخارجي',
            'relation_reference' => 'مرجع الربط', 'relation_notes' => 'ملاحظات الربط', 'related_to' => 'مرتبط بـ',
            'due_at' => 'الموعد المستهدف', 'schedule_notes' => 'ملاحظات الموعد',
            'last_response' => 'آخر رد', 'closure_reason' => 'سبب الإغلاق',
            'response' => 'الرد', 'comment' => 'التعليق', 'added_by' => 'أضيف بواسطة',
            'resolved_at' => 'تاريخ الحل', 'resolved_by' => 'تم الحل بواسطة', 'closed_at' => 'تاريخ الإغلاق',
            'complaint_type_id' => 'نوع الشكوى', 'original_name' => 'اسم الملف الأصلي', 'display_name' => 'اسم المرفق',
            'path' => 'مسار المرفق', 'file' => 'الملف', 'attachments' => 'المرفقات', 'branch_id' => 'الفرع',
            'maid_passport' => 'جواز العاملة', 'maid_nationality_id' => 'جنسية العاملة',
            'complaint_date' => 'تاريخ الشكوى', 'submission_method' => 'طريقة التقديم', 'user_id' => 'العميل',
        ][$key] ?? $key;
    }

    public static function value(string $key, $value): string
    {
        if ($value === null || $value === '') return '—';
        if (in_array($key, ['due_at', 'resolved_at', 'closed_at'])) {
            return \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        if (in_array($key, ['assigned_to', 'added_by', 'resolved_by'])) {
            return (\App\Models\auth\Admin::find($value)?->name ?: 'موظف') . ' (#' . $value . ')';
        }
        $models = [
            'complaint_type_id' => [\App\Models\ingaz\ComplaintType::class, 'name'],
            'maid_nationality_id' => [\App\Models\ingaz\Nationality::class, 'title'],
            'recruitment_office_id' => [\App\Models\RecruitmentOffices::class, 'title'],
            'related_user_id' => [\App\Models\ingaz\User::class, 'name'],
        ];
        if (isset($models[$key])) {
            [$model, $attribute] = $models[$key];
            return $model::find($value)?->{$attribute} ?: 'سجل غير متاح';
        }
        $labels = [
            'status' => ['pending' => 'قيد الانتظار', 'in_progress' => 'قيد المعالجة', 'resolved' => 'تم الحل', 'closed' => 'مغلقة', 'rejected' => 'مرفوضة'],
            'priority' => ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية', 'urgent' => 'عاجلة'],
            'relation_type' => ['none' => 'بدون ربط', 'contract' => 'عقد', 'order' => 'طلب', 'office' => 'مكتب خارجي', 'client' => 'عميل', 'manual' => 'يدوي'],
        ];
        return $labels[$key][$value] ?? (string) $value;
    }
}
