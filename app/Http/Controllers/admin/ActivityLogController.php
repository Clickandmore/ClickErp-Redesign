<?php

namespace App\Http\Controllers\admin;

use App\Enums\ingaz\ContractStatusEnum;
use App\Keys;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\User;
use App\Models\office\Office;
use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use App\Models\TransferServiceContract;
use App\Services\ingaz\LegacyActivityLogImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    private const PER_PAGE = 20;
    private const MAX_VISIBLE_CHANGES = 6;
    private const MAX_EXPANDED_CHANGES = 14;

    private array $contractStatusCache = [];

    public function index(Request $request)
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->orderBy(Keys::id, Keys::desc);

        $this->applyFilters($query, $request);

        $data = $query->paginate(self::PER_PAGE)->withQueryString();

        $data->setCollection(
            $data->getCollection()->map(fn(Activity $activity) => $this->transformActivity($activity))
        );

        return view('admin.activity_log.index', [
            'data' => $data,
            'filters' => $request->only([
                'employee_id',
                'action',
                'subject_type',
                'subject_id',
                'date_from',
                'date_to',
                'log_name',
            ]),
            'filterOptions' => $this->buildFilterOptions(),
            'stats' => [
                'total' => Activity::query()->count(),
                'filtered' => $data->total(),
                'active_filters' => $this->countActiveFilters($request),
            ],
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('employee_id')) {
            $query->where('causer_type', Admin::class)
                ->where('causer_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('action')) {
            $action = strtolower(trim((string) $request->input('action')));

            $query->where(function (Builder $builder) use ($action) {
                $builder->where('description', $action)
                    ->orWhere('event', $action);
            });
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        if ($request->filled('subject_id') && is_numeric($request->input('subject_id'))) {
            $query->where('subject_id', (int) $request->input('subject_id'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
    }

    private function buildFilterOptions(): array
    {
        $employees = Admin::query()
            ->activeForOperations()
            ->whereIn(
                'id',
                Activity::query()
                    ->where('causer_type', Admin::class)
                    ->whereNotNull('causer_id')
                    ->distinct()
                    ->pluck('causer_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $actions = Activity::query()
            ->whereNotNull('event')
            ->where('event', '!=', '')
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter()
            ->mapWithKeys(fn($event) => [
                $event => $this->resolveActivityActionLabel(strtolower((string) $event)),
            ])
            ->all();

        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->filter()
            ->mapWithKeys(fn($subjectType) => [
                $subjectType => $this->resolveSubjectTypeLabel((string) $subjectType),
            ])
            ->all();

        return [
            'employees' => $employees,
            'actions' => $actions,
            'subject_types' => $subjectTypes,
            'log_names' => Activity::query()
                ->whereNotNull('log_name')
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name')
                ->filter()
                ->mapWithKeys(fn ($logName) => [
                    $logName => $logName === LegacyActivityLogImportService::LOG_NAME
                        ? 'سجل النظام السابق'
                        : ($logName === 'default' ? 'سجل النظام الحالي' : Str::headline((string) $logName)),
                ])
                ->all(),
        ];
    }

    private function countActiveFilters(Request $request): int
    {
        return collect([
            'employee_id',
            'action',
            'subject_type',
            'subject_id',
            'date_from',
            'date_to',
            'log_name',
        ])->filter(fn($key) => filled($request->input($key)))->count();
    }

    private function transformActivity(Activity $activity): array
    {
        $event = strtolower((string) ($activity->event ?: $activity->description ?: ''));
        $isLegacy = $activity->log_name === LegacyActivityLogImportService::LOG_NAME;
        $subjectType = (string) $activity->subject_type;
        $subjectTypeLabel = $isLegacy
            ? 'سجل النظام السابق'
            : $this->resolveSubjectTypeLabel($subjectType, $activity->subject);
        $subjectId = $this->resolveSubjectId($activity);
        $subjectDescriptor = $this->resolveSubjectDescriptor($activity);
        $subjectLabel = trim($subjectTypeLabel . ($subjectId ? ' #' . $subjectId : ''));
        $changes = $this->buildChangeSet($activity, $event);
        $visibleChanges = array_slice($changes, 0, self::MAX_VISIBLE_CHANGES);
        $expandedChanges = array_slice($changes, self::MAX_VISIBLE_CHANGES, self::MAX_EXPANDED_CHANGES - self::MAX_VISIBLE_CHANGES);
        $createdAt = $activity->created_at;
        $targetUrl = $this->resolveTargetUrl($activity, $event);

        return [
            'id' => $activity->id,
            'actor_name' => $this->resolveActivityCauserName($activity),
            'actor_role' => $this->resolveActorRoleLabel($activity),
            'action' => $event,
            'action_label' => $this->resolveActivityActionLabel($event),
            'action_class' => $this->resolveActionClass($event),
            'subject_type_label' => $subjectTypeLabel,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'subject_descriptor' => $subjectDescriptor,
            'summary' => $isLegacy
                ? trim((string) $activity->description)
                : $this->buildSummary($activity, $event, $subjectLabel, $subjectDescriptor),
            'description' => $isLegacy
                ? 'سجل مستورد من نظام الرافدين السابق.'
                : $this->buildDescription($activity, $event, $changes),
            'changes_preview' => $visibleChanges,
            'changes_extra' => $expandedChanges,
            'changes_count' => count($changes),
            'changes_hidden_count' => max(0, count($changes) - count($visibleChanges)),
            'changes_raw_hidden_count' => max(0, count($changes) - (count($visibleChanges) + count($expandedChanges))),
            'created_at_label' => $createdAt ? $createdAt->translatedFormat('d F Y - h:i A') : '-',
            'created_at_relative' => $createdAt ? $createdAt->diffForHumans() : null,
            'target_url' => $targetUrl,
            'target_label' => $this->resolveTargetLabel($subjectType),
            'properties_pretty' => $this->formatPropertiesPretty($activity),
        ];
    }

    private function buildSummary(Activity $activity, string $event, string $subjectLabel, ?string $subjectDescriptor): string
    {
        $actor = $this->resolveActivityCauserName($activity);
        $action = $this->resolveActivityActionLabel($event);
        $summary = 'قام ' . $actor . ' بـ' . $action . ' ' . $subjectLabel;

        if ($subjectDescriptor) {
            $summary .= ' - ' . $subjectDescriptor;
        }

        return $summary;
    }

    private function buildDescription(Activity $activity, string $event, array $changes): string
    {
        $customDescription = $this->resolveActivityDescription($activity, $event);

        if ($customDescription) {
            return $customDescription;
        }

        if ($event === 'updated' && !empty($changes)) {
            $fieldLabels = collect($changes)->pluck('label')->take(4)->implode('، ');
            $suffix = count($changes) > 4 ? ' وغير ذلك' : '';

            return 'تم تعديل ' . count($changes) . ' حقل' . (count($changes) > 1 ? 'اً' : '') . '، أهمها: ' . $fieldLabels . $suffix . '.';
        }

        return match ($event) {
            'created' => 'تم إنشاء هذا السجل وإضافة بياناته الأساسية.',
            'deleted' => 'تم حذف هذا السجل أو إزالته من سياق العمل.',
            default => 'تم تنفيذ هذا الإجراء على السجل المحدد.',
        };
    }

    private function buildChangeSet(Activity $activity, string $event): array
    {
        $attributes = (array) data_get($activity, 'properties.attributes', []);
        $old = (array) data_get($activity, 'properties.old', []);
        $ignoredFields = [
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
            'token',
            'verification_code',
        ];

        return collect(array_unique(array_merge(array_keys($attributes), array_keys($old))))
            ->reject(fn($field) => in_array($field, $ignoredFields, true))
            ->map(function ($field) use ($activity, $event, $attributes, $old) {
                $newValue = $attributes[$field] ?? null;
                $oldValue = $old[$field] ?? null;

                return [
                    'field' => $field,
                    'label' => $this->resolveFieldLabel($field),
                    'new_display' => $this->formatActivityValue($activity, $field, $newValue),
                    'old_display' => $this->formatActivityValue($activity, $field, $oldValue),
                    'new_value' => $newValue,
                    'old_value' => $oldValue,
                ];
            })
            ->filter(function (array $change) use ($event) {
                if ($event === 'updated') {
                    return $change['new_value'] != $change['old_value'];
                }

                return filled($change['new_value']) || filled($change['old_value']);
            })
            ->sortBy(fn(array $change) => sprintf('%03d-%s', $this->fieldPriority($change['field']), $change['label']))
            ->values()
            ->all();
    }

    protected function resolveActivityActionLabel(string $event): string
    {
        return match ($event) {
            'created' => 'إضافة',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'legacy' => 'نشاط سابق',
            default => 'تنفيذ إجراء',
        };
    }

    private function resolveActionClass(string $event): string
    {
        return match ($event) {
            'created' => 'is-created',
            'updated' => 'is-updated',
            'deleted' => 'is-deleted',
            default => 'is-generic',
        };
    }

    private function resolveActorRoleLabel(Activity $activity): string
    {
        if ($activity->log_name === LegacyActivityLogImportService::LOG_NAME) {
            return 'مستخدم النظام السابق';
        }

        if (!$activity->causer) {
            return 'تنفيذ آلي';
        }

        if ((string) $activity->causer_type === Admin::class) {
            $roleLabel = optional($activity->causer->role)->display_name
                ?: optional($activity->causer->role)->name;

            if ($roleLabel) {
                return match (strtolower((string) $roleLabel)) {
                    'administrator', 'admin' => 'إدارة النظام',
                    default => $roleLabel,
                };
            }

            return 'موظف';
        }

        if ((string) $activity->causer_type === User::class) {
            return 'عميل';
        }

        return class_basename((string) $activity->causer_type);
    }

    private function resolveSubjectTypeLabel(string $subjectType, $subject = null): string
    {
        return match ($subjectType) {
            Contract::class => $this->resolveContractLabelFromSubject($subject, 'عقد الاستقدام'),
            ContractInfo::class => 'بيانات العقد',
            RentalContract::class => 'عقد الإيجار',
            SponsorshipTransfer::class => 'حركة نقل الخدمات',
            TransferServiceContract::class => 'عقد نقل الخدمات',
            Order::class => 'الطلب',
            RecruitmentRequest::class => 'طلب الاستقدام',
            Invoice::class => 'الفاتورة',
            Maid::class => 'السيرة الذاتية',
            User::class => 'العميل',
            Office::class => 'مكتب الاستقدام',
            default => $subjectType ? Str::headline(class_basename($subjectType)) : 'السجل',
        };
    }

    private function resolveContractLabelFromSubject($subject, string $fallback): string
    {
        $status = data_get($subject, 'status');

        if ($status === ContractStatusEnum::Shelter->value) {
            return 'عقد الإيواء';
        }

        return $fallback;
    }

    private function resolveSubjectId(Activity $activity): ?int
    {
        $subjectId = $activity->subject_id
            ?: data_get($activity, 'subject.id')
            ?: data_get($activity, 'properties.attributes.id')
            ?: data_get($activity, 'properties.old.id');

        return is_numeric($subjectId) ? (int) $subjectId : null;
    }

    private function resolveSubjectDescriptor(Activity $activity): ?string
    {
        $candidates = [
            data_get($activity, 'subject.name'),
            data_get($activity, 'subject.title'),
            data_get($activity, 'subject.contract_number'),
            data_get($activity, 'subject.passport_num'),
            data_get($activity, 'subject.phone'),
            data_get($activity, 'subject.email'),
            data_get($activity, 'properties.attributes.name'),
            data_get($activity, 'properties.attributes.title'),
            data_get($activity, 'properties.attributes.contract_number'),
            data_get($activity, 'properties.attributes.passport_num'),
            data_get($activity, 'properties.attributes.phone'),
            data_get($activity, 'properties.attributes.email'),
        ];

        $value = collect($candidates)->first(fn($candidate) => filled($candidate));

        return $value ? Str::limit(trim((string) $value), 80) : null;
    }

    private function resolveTargetUrl(Activity $activity, string $event): ?string
    {
        $subject = $activity->subject;
        $subjectType = (string) $activity->subject_type;

        if (!$subject && $event === 'deleted') {
            return null;
        }

        if ($subjectType === ContractInfo::class) {
            $contractId = data_get($subject, 'contract_id')
                ?: data_get($activity, 'properties.attributes.contract_id')
                ?: data_get($activity, 'properties.old.contract_id');

            return $contractId ? $this->resolveContractUrl((int) $contractId) : null;
        }

        $subjectId = $this->resolveSubjectId($activity);

        if (!$subjectId) {
            return null;
        }

        return match ($subjectType) {
            Contract::class => $this->resolveContractUrl($subjectId, $subject),
            RentalContract::class => $this->routeIfExists('RentContract.show', $subjectId),
            SponsorshipTransfer::class => $this->routeIfExists('SponsorshipTransfer.show', $subjectId),
            TransferServiceContract::class => $this->routeIfExists('TransferServiceContract.show', $subjectId),
            Order::class => $this->routeIfExists('orders.show', $subjectId),
            RecruitmentRequest::class => $this->routeIfExists('recruitment_requests.show', $subjectId),
            Invoice::class => $this->routeIfExists('invoices.show', $subjectId),
            Maid::class => $this->routeIfExists('showMaidDetails', $subjectId),
            User::class => $this->routeIfExists('clients.show', $subjectId),
            Office::class => $this->routeIfExists('offices.show', $subjectId),
            default => null,
        };
    }

    private function resolveContractUrl(int $contractId, $subject = null): ?string
    {
        $status = data_get($subject, 'status') ?: $this->resolveContractStatus($contractId);
        $route = $status === ContractStatusEnum::Shelter->value
            ? 'ShelterContract.show'
            : 'RecruitmentContract.show';

        return $this->routeIfExists($route, $contractId);
    }

    private function resolveContractStatus(int $contractId): ?string
    {
        if (!array_key_exists($contractId, $this->contractStatusCache)) {
            $this->contractStatusCache[$contractId] = Contract::query()
                ->whereKey($contractId)
                ->value('status');
        }

        return $this->contractStatusCache[$contractId];
    }

    private function resolveTargetLabel(string $subjectType): string
    {
        return match ($subjectType) {
            Contract::class,
            ContractInfo::class,
            RentalContract::class,
            TransferServiceContract::class => 'تفاصيل العقد',
            SponsorshipTransfer::class => 'تفاصيل الحركة',
            Order::class,
            RecruitmentRequest::class => 'تفاصيل الطلب',
            Invoice::class => 'تفاصيل الفاتورة',
            Maid::class => 'تفاصيل السيرة',
            User::class => 'تفاصيل العميل',
            Office::class => 'تفاصيل المكتب',
            default => 'تفاصيل السجل',
        };
    }

    private function routeIfExists(string $routeName, int $id): ?string
    {
        return Route::has($routeName) ? route($routeName, $id) : null;
    }

    private function resolveFieldLabel(string $field): string
    {
        $labels = [
            'status' => 'الحالة',
            'warranty_status' => 'حالة الضمان',
            'user_id' => 'العميل',
            'maid_id' => 'العاملة',
            'order_id' => 'الطلب',
            'contract_id' => 'العقد',
            'rental_contract_id' => 'عقد الإيجار',
            'account_id' => 'الحساب المالي',
            'customer_id' => 'الموظف المسؤول',
            'admin_id' => 'الموظف المسؤول',
            'marketer_id' => 'المسوق',
            'office_id' => 'المكتب',
            'nationality_id' => 'الجنسية',
            'religion_id' => 'الديانة',
            'job_type_id' => 'نوع الوظيفة',
            'package_id' => 'الباقة',
            'address_id' => 'العنوان',
            'name' => 'الاسم',
            'title' => 'العنوان',
            'phone' => 'رقم الجوال',
            'phone2' => 'الجوال الإضافي',
            'email' => 'البريد الإلكتروني',
            'national_num' => 'رقم الهوية',
            'passport_num' => 'رقم الجواز',
            'contract_number' => 'رقم العقد',
            'invoice_number' => 'رقم الفاتورة',
            'trial_days' => 'مدة التجربة',
            'trial_start_date' => 'بداية التجربة',
            'contract_value' => 'قيمة العقد',
            'paid_amount' => 'المبلغ المدفوع',
            'remaining_amount' => 'المبلغ المتبقي',
            'notes' => 'الملاحظات',
            'available' => 'الإتاحة',
            'is_paid' => 'حالة السداد',
            'is_blocked' => 'الحظر',
            'is_pinned' => 'التثبيت',
            'is_show' => 'الظهور',
            'request_type' => 'نوع الطلب',
            'price' => 'السعر',
            'salary' => 'الراتب',
            'birth_date' => 'تاريخ الميلاد',
            'arrival_date' => 'تاريخ الوصول',
            'has_passport' => 'وجود الجواز',
            'salary_received' => 'استلام الراتب',
            'has_deportation_letter' => 'خطاب الترحيل',
            'has_settlement_session' => 'جلسة التسوية',
            'has_final_exit' => 'الخروج النهائي',
            'old_sponsor_id' => 'الكفيل السابق',
            'new_sponsor_id' => 'الكفيل الجديد',
        ];

        return $labels[$field] ?? Str::headline(str_replace('_', ' ', $field));
    }

    private function fieldPriority(string $field): int
    {
        $priorityFields = [
            'status',
            'warranty_status',
            'contract_number',
            'invoice_number',
            'user_id',
            'maid_id',
            'order_id',
            'contract_id',
            'rental_contract_id',
            'old_sponsor_id',
            'new_sponsor_id',
            'account_id',
            'name',
            'title',
            'phone',
            'email',
            'passport_num',
            'trial_start_date',
            'trial_days',
            'contract_value',
            'paid_amount',
            'remaining_amount',
            'notes',
        ];

        $index = array_search($field, $priorityFields, true);

        return $index === false ? 999 : $index;
    }

    private function formatActivityValue(Activity $activity, string $field, $value): string
    {
        if ($value === null) {
            return 'غير محدد';
        }

        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($value instanceof Carbon) {
            return $value->translatedFormat('Y-m-d h:i A');
        }

        if (is_array($value)) {
            if (empty($value)) {
                return 'لا يوجد';
            }

            if ($this->isAssocArray($value)) {
                return Str::limit(
                    json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    90
                );
            }

            return collect($value)
                ->map(fn($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                ->implode('، ');
        }

        if (is_numeric($value) && Str::endsWith($field, '_id')) {
            return '#' . $value;
        }

        if (is_numeric($value) && in_array($field, ['price', 'salary', 'contract_value', 'paid_amount', 'remaining_amount'], true)) {
            return number_format((float) $value, 2);
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 'فارغ';
        }

        if ($this->isBooleanLikeField($field)) {
            return $this->normalizeBooleanString($stringValue);
        }

        if ($field === 'status' || $field === 'warranty_status') {
            return $this->translateStatusValue($activity, $stringValue, $field);
        }

        if ($this->looksLikeJson($stringValue)) {
            $decoded = json_decode($stringValue, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->formatActivityValue($activity, $field, $decoded);
            }
        }

        if ($this->looksLikeDate($stringValue)) {
            try {
                return Carbon::parse($stringValue)->translatedFormat('Y-m-d h:i A');
            } catch (\Throwable $exception) {
            }
        }

        if (Str::contains($stringValue, ['/','\\']) && preg_match('/\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i', $stringValue)) {
            return basename($stringValue);
        }

        return Str::limit($stringValue, 90);
    }

    private function isBooleanLikeField(string $field): bool
    {
        return Str::startsWith($field, ['is_', 'has_'])
            || in_array($field, ['available', 'display_on_site', 'salary_received'], true);
    }

    private function normalizeBooleanString(string $value): string
    {
        $normalized = strtolower($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return 'نعم';
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return 'لا';
        }

        return $value;
    }

    private function translateStatusValue(Activity $activity, string $value, string $field): string
    {
        if ($field === 'warranty_status') {
            return [
                'active' => 'نشط',
                'expired' => 'منتهي',
                'not_applicable' => 'غير مطبق',
            ][$value] ?? $this->humanizeEnumValue($value);
        }

        if ((string) $activity->subject_type === Contract::class || (string) $activity->subject_type === ContractInfo::class) {
            return ContractStatusEnum::tryFrom($value)?->lang() ?? $this->humanizeEnumValue($value);
        }

        $statusMap = [
            'new' => 'جديد',
            'pending' => 'في الانتظار',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'available' => 'متاح',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'trial' => 'في التجربة',
            'trial_started' => 'بدأت التجربة',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'waiting_contract_send' => 'بانتظار إرسال العقد',
            'failed' => 'فشل',
            'transferred' => 'تم النقل',
            'suspended' => 'معلق',
        ];

        return $statusMap[$value] ?? $this->humanizeEnumValue($value);
    }

    private function humanizeEnumValue(string $value): string
    {
        return str_replace('_', ' ', $value);
    }

    private function formatPropertiesPretty(Activity $activity): ?string
    {
        $properties = $activity->properties;

        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if (empty($properties)) {
            return null;
        }

        return json_encode(
            $properties,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function looksLikeJson(string $value): bool
    {
        return Str::startsWith($value, ['[', '{']);
    }

    private function looksLikeDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $value);
    }

    private function isAssocArray(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}
