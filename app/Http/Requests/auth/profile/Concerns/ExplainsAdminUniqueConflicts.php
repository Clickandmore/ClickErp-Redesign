<?php

namespace App\Http\Requests\auth\profile\Concerns;

use App\Models\auth\Admin;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;

/**
 * يحوّل خطأ "القيمة مستخدمة من قبل" على البريد/اسم المستخدم/رقم الهوية إلى رسالة تشرح السبب:
 * هل القيمة لحساب نشط (ومن هو)، أم لحساب محذوف حذفاً مؤقتاً يمكن استعادته أو حذفه نهائياً
 * من صفحة الموظفين المحذوفين. وعند التعارض مع حساب محذوف يُرفق بالرد بيانات الحساب ورابط
 * الصفحة حتى تعرض الواجهة زر انتقال مباشر.
 *
 * الرسائل تُبنى في uniqueConflictMessages() (تُدمج داخل messages() في الطلب) فلا نحتاج
 * لتعديل MessageBag بعد الفحص؛ المدقق يستخدمها فقط عند فشل قاعدة unique.
 */
trait ExplainsAdminUniqueConflicts
{
    /** @var array<string, Admin|null> */
    private array $uniqueConflictCache = [];

    /**
     * الأعمدة الفريدة في جدول admins والاسم المعروض لكل منها.
     *
     * @return array<string, string>
     */
    protected function uniqueAdminColumns(): array
    {
        return [
            'email' => __('auth.email'),
            'username' => __('admin.username'),
            'identity_number' => __('admin.identity_number'),
        ];
    }

    /**
     * معرّف الحساب الذي يجب تجاهله (في التعديل) أو null (في الإنشاء).
     */
    protected function ignoredAdminId(): ?int
    {
        return null;
    }

    /**
     * رسائل unique المخصصة لكل عمود فريد بحسب القيمة المُرسلة فعلياً.
     *
     * @return array<string, string>
     */
    protected function uniqueConflictMessages(): array
    {
        $messages = [];

        foreach ($this->uniqueAdminColumns() as $column => $label) {
            $value = trim((string) $this->input($column));
            if ($value === '') {
                continue;
            }

            $messages[$column . '.unique'] = $this->describeUniqueConflict(
                $this->existingAdminFor($column, $value),
                $label,
                $value
            );
        }

        return $messages;
    }

    protected function existingAdminFor(string $column, string $value): ?Admin
    {
        $cacheKey = $column . ':' . $value;

        if (!array_key_exists($cacheKey, $this->uniqueConflictCache)) {
            $this->uniqueConflictCache[$cacheKey] = Admin::withTrashed()
                ->where($column, $value)
                ->when($this->ignoredAdminId(), fn ($query, $id) => $query->whereKeyNot($id))
                // الحساب النشط أولاً: لو وُجد نشط ومحذوف بنفس القيمة فالنشط هو سبب المنع الحقيقي
                ->orderByRaw('deleted_at IS NULL DESC')
                ->first(['id', 'name', 'deleted_at']);
        }

        return $this->uniqueConflictCache[$cacheKey];
    }

    protected function describeUniqueConflict(?Admin $existing, string $label, string $value): string
    {
        if ($existing && $existing->trashed()) {
            return __('admin.unique_conflict.trashed', [
                'field' => $label,
                'value' => $value,
                'name' => $existing->name,
                'date' => optional($existing->deleted_at)->format('Y-m-d'),
            ]);
        }

        if ($existing) {
            return __('admin.unique_conflict.active', [
                'field' => $label,
                'value' => $value,
                'name' => $existing->name,
            ]);
        }

        return __('admin.unique_conflict.active_unknown', [
            'field' => $label,
            'value' => $value,
        ]);
    }

    /**
     * التعارضات مع حسابات محذوفة فقط، مأخوذة من القواعد التي فشلت فعلاً.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function trashedConflictsFrom(Validator $validator): array
    {
        $conflicts = [];

        foreach ($this->uniqueAdminColumns() as $column => $label) {
            if (!isset($validator->failed()[$column]['Unique'])) {
                continue;
            }

            $value = trim((string) $this->input($column));
            $existing = $value === '' ? null : $this->existingAdminFor($column, $value);

            if (!$existing || !$existing->trashed()) {
                continue;
            }

            $conflicts[] = [
                'field' => $column,
                'label' => $label,
                'value' => $value,
                'id' => (int) $existing->id,
                'name' => $existing->name,
                'deleted_at' => optional($existing->deleted_at)->format('Y-m-d H:i'),
            ];
        }

        return $conflicts;
    }

    /**
     * رد JSON يحمل، إضافة إلى message/errors المعتادين، تفاصيل الحساب المحذوف ورابط صفحة المحذوفين.
     */
    protected function failedValidation(Validator $validator)
    {
        $conflicts = ($this->expectsJson() || $this->ajax()) ? $this->trashedConflictsFrom($validator) : [];

        if ($conflicts !== []) {
            $first = $conflicts[0];
            $trashedUrl = Route::has('admins.trashed')
                ? route('admins.trashed', ['search' => $first['value']])
                : null;

            throw new HttpResponseException(response()->json([
                'message' => __('admin.trashed_admins.conflict_title'),
                'errors' => $validator->errors()->toArray(),
                'trashed_conflict' => $first + ['trashed_url' => $trashedUrl],
                'trashed_conflicts' => $conflicts,
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
