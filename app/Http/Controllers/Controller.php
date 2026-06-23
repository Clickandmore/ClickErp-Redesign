<?php

namespace App\Http\Controllers;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Services\BranchAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function usersList(){
        return User::where('is_blocked', 0)->pluck('name', 'id')->all();
    }

    public function maidsList(){
        return Maid::where('available', 1)
            // ->whereHas('job_types', function ($query) {
            //     $query->where('job_type_id', 2);
            // })
            ->pluck('passport_num', 'id')
            ->all();
    }

    protected function applyRequestBranchFilter($query, Request $request, string $table, ?string $qualifiedColumn = null)
    {
        return app(BranchAccessService::class)->applyToQuery($query, $request, $table, $qualifiedColumn);
    }

    protected function assertCanAccessBranchModel(Model $model, ?string $table = null): void
    {
        app(BranchAccessService::class)->assertCanAccessModel($model, null, $table);
    }

    protected function branchSelectorData(Request $request): array
    {
        return app(BranchAccessService::class)->selectorData($request);
    }

    protected function buildActivityTimeline($activities, array $fieldLabels = [], ?string $defaultSubjectLabel = null): Collection
    {
        return collect($activities)
            ->filter()
            ->sortByDesc(fn($activity) => $activity->created_at)
            ->values()
            ->map(function ($activity) use ($fieldLabels, $defaultSubjectLabel) {
                $event = strtolower((string) ($activity->event ?: $activity->description ?: ''));
                $actorName = $this->resolveActivityCauserName($activity);
                $actorRole = $this->resolveActivityCauserRole($activity);
                $subjectLabel = $this->resolveActivitySubjectLabel($activity, $defaultSubjectLabel);
                $actionLabel = $this->resolveActivityActionLabel($event);
                $customDescription = $this->resolveActivityDescription($activity, $event);
                $changedFields = $this->extractActivityChangedFields($activity, $fieldLabels);
                $createdAt = $activity->created_at;

                return [
                    'id' => $activity->id,
                    'actor_name' => $actorName,
                    'actor_role' => $actorRole,
                    'subject_label' => $subjectLabel,
                    'action_label' => $actionLabel,
                    'summary' => trim($actionLabel . ' ' . $subjectLabel),
                    'description' => $customDescription,
                    'changed_fields' => $changedFields,
                    'timestamp_label' => $createdAt ? $createdAt->format('Y-m-d h:i A') : '-',
                    'date_label' => $createdAt ? $createdAt->translatedFormat('l، d F Y') : '-',
                    'relative_time' => $createdAt ? $createdAt->diffForHumans() : null,
                ];
            });
    }

    protected function resolveActivityCauserName($activity): string
    {
        return optional($activity->causer)->name
            ?? optional($activity->causer)->email
            ?? 'النظام';
    }

    protected function resolveActivityCauserRole($activity): ?string
    {
        return match (class_basename((string) $activity->causer_type)) {
            'Admin' => 'الإدارة',
            'User' => 'العميل',
            default => null,
        };
    }

    protected function resolveActivitySubjectLabel($activity, ?string $defaultSubjectLabel = null): string
    {
        $subjectType = class_basename((string) $activity->subject_type);

        return match ($subjectType) {
            'Maid' => 'السيرة الذاتية',
            'Order' => 'الطلب',
            'OrderDetail' => 'تفاصيل الطلب',
            'OrderTransfers' => 'ملف الطلب',
            'RentalContract' => 'عقد التأجير',
            'RentalContractInfo' => 'بيانات عقد التأجير',
            default => $defaultSubjectLabel ?: ($subjectType ? Str::headline($subjectType) : 'السجل'),
        };
    }

    protected function resolveActivityActionLabel(string $event): string
    {
        return match ($event) {
            'created' => 'إضافة',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'maid_changed' => 'تغيير',
            default => 'تنفيذ إجراء على',
        };
    }

    protected function resolveActivityDescription($activity, string $event): ?string
    {
        $description = trim((string) $activity->description);

        if ($description === '' || in_array(strtolower($description), ['created', 'updated', 'deleted'], true)) {
            return null;
        }

        if ($description === $event) {
            return null;
        }

        return $description;
    }

    protected function extractActivityChangedFields($activity, array $fieldLabels = []): array
    {
        $attributes = (array) data_get($activity, 'properties.attributes', []);
        $old = (array) data_get($activity, 'properties.old', []);
        $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

        return collect(array_unique(array_merge(array_keys($attributes), array_keys($old))))
            ->reject(fn($key) => in_array($key, $ignoredFields, true))
            ->filter(function ($key) use ($attributes, $old, $activity) {
                if (($activity->event ?? null) !== 'updated') {
                    return true;
                }

                return data_get($attributes, $key) != data_get($old, $key);
            })
            ->map(function ($key) use ($fieldLabels) {
                return $fieldLabels[$key] ?? Str::headline(str_replace('_', ' ', (string) $key));
            })
            ->unique()
            ->values()
            ->all();
    }
}
