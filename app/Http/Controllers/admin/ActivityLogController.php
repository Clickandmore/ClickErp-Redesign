<?php

namespace App\Http\Controllers\admin;

use App\Enums\ingaz\ContractStatusEnum;
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
    private const EXPORT_CHUNK = 500;
    public const MAX_EXPORT_ROWS = 50000;
    // mPDF يبني الجدول كاملاً في الذاكرة، لذلك حد ملف PDF أصغر بكثير من CSV
    public const MAX_PDF_ROWS = 500;
    private const MAX_PDF_CHANGES = 8;

    private const FILTER_KEYS = [
        'employee_id',
        'action',
        'subject_type',
        'subject_id',
        'date_from',
        'date_to',
        'log_name',
    ];

    private array $contractStatusCache = [];

    public function index(Request $request)
    {
        $this->validateFilters($request);

        $query = Activity::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applyFilters($query, $request);

        $data = $query->paginate(self::PER_PAGE)->withQueryString();

        \App\Support\Audit\ActivityRelations::load($data->getCollection());

        $data->setCollection(
            $data->getCollection()->map(fn(Activity $activity) => $this->transformActivity($activity))
        );

        return view('admin.activity_log.index', [
            'data' => $data,
            'filters' => $request->only(self::FILTER_KEYS),
            'filterOptions' => $this->buildFilterOptions(),
            'stats' => [
                'total' => Activity::query()->count(),
                'filtered' => $data->total(),
                'active_filters' => $this->countActiveFilters($request),
            ],
            'maxExportRows' => self::MAX_EXPORT_ROWS,
            'maxPdfRows' => self::MAX_PDF_ROWS,
        ]);
    }

    /**
     * تصدير السجلات المطابقة للفلاتر الحالية كملف CSV (يفتح مباشرة في Excel).
     */
    public function export(Request $request)
    {
        $this->validateFilters($request);

        $query = Activity::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applyFilters($query, $request);

        $headings = [
            'رقم السجل',
            'التاريخ',
            'الوقت',
            'منفذ العملية',
            'الصفة',
            'نوع العملية',
            'مصدر السجل',
            'نوع الكيان',
            'المعرف',
            'مرجع إضافي',
            'الملخص',
            'الوصف',
            'عدد التغييرات',
            'التغييرات',
        ];

        $callback = function () use ($query, $headings) {
            @set_time_limit(0);

            $handle = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";
            fputcsv($handle, $headings);

            $exported = 0;
            $logNameLabels = [];
            $query->chunk(self::EXPORT_CHUNK, function (Collection $activities) use ($handle, &$exported, &$logNameLabels) {
                \App\Support\Audit\ActivityRelations::load($activities);

                foreach ($activities as $activity) {
                    if ($exported >= self::MAX_EXPORT_ROWS) {
                        return false;
                    }

                    $logName = (string) $activity->log_name;
                    $logNameLabels[$logName] ??= $logName !== '' ? $this->resolveLogNameLabel($logName) : '-';

                    fputcsv($handle, $this->buildExportRow($activity, $logNameLabels[$logName]));
                    $exported++;
                }

                flush();
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, 'activity-log-' . now()->format('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * تصدير السجلات المطابقة للفلاتر الحالية كملف PDF (أحدث MAX_PDF_ROWS سجل).
     */
    public function exportPdf(Request $request)
    {
        $this->validateFilters($request);

        $query = Activity::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applyFilters($query, $request);

        $total = (clone $query)->count();
        $activities = $query->limit(self::MAX_PDF_ROWS)->get();

        \App\Support\Audit\ActivityRelations::load($activities);

        $rows = $activities->map(function (Activity $activity) {
            $entry = $this->describeActivity($activity);
            $isUpdated = $entry['action'] === 'updated';

            return [
                'id' => $activity->id,
                'date' => optional($activity->created_at)->format('Y-m-d') ?? '-',
                'time' => optional($activity->created_at)->format('h:i A') ?? '',
                'actor_name' => $entry['actor_name'],
                'actor_role' => $entry['actor_role'],
                'action_label' => $entry['action_label'],
                'action_class' => $this->resolveActionClass($entry['action']),
                'subject' => $entry['subject_label'],
                'subject_descriptor' => $entry['subject_descriptor'],
                'summary' => $entry['summary'],
                'is_updated' => $isUpdated,
                'changes' => array_slice($entry['changes'], 0, self::MAX_PDF_CHANGES),
                'changes_more' => max(0, count($entry['changes']) - self::MAX_PDF_CHANGES),
            ];
        });

        // جدول بمئات الصفوف يتجاوز حد pcre الافتراضي (1MB) الذي تعتمد عليه mPDF في تحليل HTML
        @ini_set('pcre.backtrack_limit', '20000000');
        @set_time_limit(300);

        $pdf = \misterspelik\LaravelPdf\Facades\Pdf::loadView('admin.activity_log.export-pdf', [
            'rows' => $rows,
            'total' => $total,
            'maxRows' => self::MAX_PDF_ROWS,
            'filterSummary' => $this->describeFilters($request),
            'printedAt' => now(),
            'printedBy' => optional(auth('admin')->user())->name,
        ], [], [
            'format' => 'A4-L',
            'mode' => 'utf-8',
        ]);

        // يُسمح بالطباعة والنسخ فقط؛ التعديل يحتاج كلمة مرور مالك عشوائية لا تُحفظ
        $pdf->setProtection(['print', 'copy'], '', Str::random(32));

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="activity-log-' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    /**
     * وصف مقروء للفلاتر المطبقة لعرضه في رأس ملف PDF.
     */
    private function describeFilters(Request $request): array
    {
        $summary = [];

        if ($request->filled('employee_id')) {
            $summary['الموظف'] = Admin::withTrashed()->whereKey((int) $request->input('employee_id'))->value('name')
                ?? '#' . $request->input('employee_id');
        }
        if ($request->filled('action')) {
            $summary['العملية'] = $this->resolveActivityActionLabel(strtolower((string) $request->input('action')));
        }
        if ($request->filled('subject_type')) {
            $summary['نوع السجل'] = $this->resolveSubjectTypeLabel((string) $request->input('subject_type'));
        }
        if ($request->filled('subject_id')) {
            $summary['الرقم'] = '#' . $request->input('subject_id');
        }
        if ($request->filled('log_name')) {
            $summary['المصدر'] = $this->resolveLogNameLabel((string) $request->input('log_name'));
        }
        if ($request->filled('date_from')) {
            $summary['من'] = $request->input('date_from');
        }
        if ($request->filled('date_to')) {
            $summary['إلى'] = $request->input('date_to');
        }

        return $summary;
    }

    private function buildExportRow(Activity $activity, string $logNameLabel): array
    {
        $entry = $this->describeActivity($activity);
        $isUpdated = $entry['action'] === 'updated';

        $changes = collect($entry['changes'])
            ->map(fn (array $change) => $isUpdated
                ? $change['label'] . ': ' . $change['old_display'] . ' ← ' . $change['new_display']
                : $change['label'] . ': ' . ($change['new_display'] !== 'غير محدد' ? $change['new_display'] : $change['old_display']))
            ->implode("\n");

        return [
            $activity->id,
            optional($activity->created_at)->format('Y-m-d') ?? '-',
            optional($activity->created_at)->format('h:i A') ?? '-',
            $entry['actor_name'],
            $entry['actor_role'],
            $entry['action_label'],
            $logNameLabel,
            $entry['subject_type_label'],
            $entry['subject_id'] ?? '-',
            $entry['subject_descriptor'] ?? '-',
            $entry['summary'],
            $entry['description'],
            count($entry['changes']),
            $changes !== '' ? $changes : '-',
        ];
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('date_from') ? ['after_or_equal:date_from'] : []),
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
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
            ->withTrashed()
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
                ->mapWithKeys(fn ($logName) => [$logName => $this->resolveLogNameLabel((string) $logName)])
                ->all(),
        ];
    }

    private function resolveLogNameLabel(string $logName): string
    {
        if ($logName === LegacyActivityLogImportService::LOG_NAME) {
            return 'سجل النظام السابق';
        }

        return [
            'default' => 'سجل النظام الحالي',
            'domain_history' => 'السجلات التاريخية المدمجة',
            'finance' => 'المالية',
            'hr' => 'الموارد البشرية',
            'crm' => 'علاقات العملاء',
            'authentication' => 'الدخول والحسابات',
            'permissions' => 'الصلاحيات',
            'settings' => 'إعدادات النظام',
        ][$logName] ?? Str::headline($logName);
    }

    private function countActiveFilters(Request $request): int
    {
        return collect(self::FILTER_KEYS)->filter(fn($key) => filled($request->input($key)))->count();
    }

    /**
     * البيانات المشتركة بين العرض والتصدير (بدون الروابط التي تحتاج استعلامات إضافية).
     */
    private function describeActivity(Activity $activity): array
    {
        $activity->properties = \App\Support\Audit\AuditProperties::sanitize($activity->properties?->toArray() ?? []);
        $event = strtolower((string) ($activity->event ?: $activity->description ?: ''));
        $isLegacy = $activity->log_name === LegacyActivityLogImportService::LOG_NAME;
        $subjectTypeLabel = $isLegacy
            ? 'سجل النظام السابق'
            : $this->resolveSubjectTypeLabel((string) $activity->subject_type, $activity->subject);
        $subjectId = $this->resolveSubjectId($activity);
        $subjectDescriptor = $this->resolveSubjectDescriptor($activity);
        $subjectLabel = trim($subjectTypeLabel . ($subjectId ? ' #' . $subjectId : ''));
        $changes = $this->buildChangeSet($activity, $event);

        return [
            'action' => $event,
            'actor_name' => $this->resolveActivityCauserName($activity),
            'actor_role' => $this->resolveActorRoleLabel($activity),
            'action_label' => $this->resolveActivityActionLabel($event),
            'subject_type_label' => $subjectTypeLabel,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'subject_descriptor' => $subjectDescriptor,
            'summary' => data_get($activity, 'properties.historical_import')
                ? 'سجل تاريخي — ' . trim((string) $activity->description)
                : ($isLegacy
                ? trim((string) $activity->description)
                : $this->buildSummary($activity, $event, $subjectLabel, $subjectDescriptor)),
            'description' => $isLegacy
                ? 'سجل مستورد من نظام الرافدين السابق.'
                : $this->buildDescription($activity, $event, $changes),
            'changes' => $changes,
        ];
    }

    private function transformActivity(Activity $activity): array
    {
        $entry = $this->describeActivity($activity);
        $event = $entry['action'];
        $changes = $entry['changes'];
        $subjectType = (string) $activity->subject_type;
        $visibleChanges = array_slice($changes, 0, self::MAX_VISIBLE_CHANGES);
        $expandedChanges = array_slice($changes, self::MAX_VISIBLE_CHANGES, self::MAX_EXPANDED_CHANGES - self::MAX_VISIBLE_CHANGES);
        $createdAt = $activity->created_at;
        $targetUrl = $this->resolveTargetUrl($activity, $event);

        return [
            'id' => $activity->id,
            'actor_name' => $entry['actor_name'],
            'actor_role' => $entry['actor_role'],
            'action' => $event,
            'action_label' => $entry['action_label'],
            'action_class' => $this->resolveActionClass($event),
            'subject_type_label' => $entry['subject_type_label'],
            'subject_id' => $entry['subject_id'],
            'subject_label' => $entry['subject_label'],
            'subject_descriptor' => $entry['subject_descriptor'],
            'summary' => $entry['summary'],
            'description' => $entry['description'],
            'changes_preview' => $visibleChanges,
            'changes_extra' => $expandedChanges,
            'changes_count' => count($changes),
            'changes_hidden_count' => max(0, count($changes) - count($visibleChanges)),
            'changes_raw_hidden_count' => max(0, count($changes) - (count($visibleChanges) + count($expandedChanges))),
            'created_at_label' => $createdAt ? $createdAt->translatedFormat('d F Y - h:i A') : '-',
            'created_at_relative' => $createdAt ? $createdAt->diffForHumans() : null,
            'created_at_time' => $createdAt ? $createdAt->translatedFormat('h:i A') : '-',
            'day_key' => $createdAt ? $createdAt->format('Y-m-d') : 'unknown',
            'day_label' => $createdAt
                ? ($createdAt->isToday() ? 'اليوم' : ($createdAt->isYesterday() ? 'أمس' : $createdAt->translatedFormat('l d F Y')))
                : 'تاريخ غير معروف',
            'target_url' => $targetUrl,
            'target_label' => $this->resolveTargetLabel($subjectType),
            'properties_pretty' => $this->formatPropertiesPretty($activity),
        ];
    }

    private function buildSummary(Activity $activity, string $event, string $subjectLabel, ?string $subjectDescriptor): string
    {
        $actor = $this->resolveActivityCauserName($activity);
        $action = $this->resolveActivityActionLabel($event);
        $hasActor = $activity->causer_id || filled(data_get($activity, 'properties.actor_name'));
        $summary = $hasActor
            ? 'قام ' . $actor . ' بـ' . $action . ' ' . $subjectLabel
            : 'تم ' . $action . ' ' . $subjectLabel;


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

        if ($event === 'updated' && empty($changes)) {
            $fields = array_unique(array_merge(
                array_keys((array) data_get($activity, 'properties.attributes', [])),
                array_keys((array) data_get($activity, 'properties.old', []))
            ));
            if ($fields !== [] && array_diff($fields, ['updated_at']) === []) {
                return 'تم تحديث توقيت حفظ السجل فقط، دون تغيير بياناته.';
            }
        }

        if ($event === 'updated' && !empty($changes)) {
            $fieldLabels = collect($changes)->pluck('label')->take(4)->implode('، ');
            $suffix = count($changes) > 4 ? ' وغير ذلك' : '';

            return 'تم تعديل ' . count($changes) . ' حقل' . (count($changes) > 1 ? 'اً' : '') . '، أهمها: ' . $fieldLabels . $suffix . '.';
        }

        return match ($event) {
            'created' => 'تم إنشاء هذا السجل وإضافة بياناته الأساسية.',
            'deleted' => 'تم حذف هذا السجل أو إزالته من سياق العمل.',
            'force_deleted' => 'تم حذف هذا السجل نهائياً ولا يمكن استرجاعه.',
            'restored' => 'تمت إعادة هذا السجل بعد حذفه.',
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
            'force_deleted' => 'حذف نهائي',
            'restored' => 'استرجاع',
            'legacy' => 'نشاط سابق',
            'login' => 'تسجيل الدخول',
            'logout' => 'تسجيل الخروج',
            'login_failed' => 'محاولة دخول فاشلة',
            'impersonated' => 'الدخول بحساب آخر',
            'role_attached' => 'إسناد دور',
            'role_detached' => 'إلغاء دور',
            'permission_attached' => 'منح صلاحية',
            'permission_detached' => 'إلغاء صلاحية',
            default => 'تنفيذ إجراء',
        };
    }

    private function resolveActionClass(string $event): string
    {
        return match ($event) {
            'created' => 'is-created',
            'updated' => 'is-updated',
            'deleted', 'force_deleted' => 'is-deleted',
            'restored' => 'is-created',
            default => 'is-generic',
        };
    }

    private function resolveActorRoleLabel(Activity $activity): string
    {
        if ($activity->log_name === LegacyActivityLogImportService::LOG_NAME) {
            return 'مستخدم النظام السابق';
        }

        if (data_get($activity, 'properties.execution_source') === 'console') {
            return 'تنفيذ آلي للنظام';
        }

        if (!$activity->causer) {
            return $activity->causer_id ? 'مستخدم سابق' : 'لم تُحفظ هوية المنفذ عند تسجيل العملية';
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
        $label = config('audit.subject_labels.' . $subjectType);
        if ($label) {
            return $label;
        }

        if (Str::startsWith($subjectType, 'Nami\\Finance\\Models\\')) {
            return $this->resolvePackageSubjectLabel($subjectType, 'finance');
        }

        if (Str::startsWith($subjectType, 'Nami\\Hr\\Models\\')) {
            return $this->resolvePackageSubjectLabel($subjectType, 'hr');
        }

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
            Admin::class => 'مستخدم النظام',
            Office::class => 'مكتب الاستقدام',
            default => $subjectType ? Str::headline(class_basename($subjectType)) : 'السجل',
        };
    }

    private function resolvePackageSubjectLabel(string $subjectType, string $package): string
    {
        $model = class_basename($subjectType);
        $labels = [
            'Account' => 'الحساب المالي',
            'AccountTransfer' => 'التحويل بين الحسابات',
            'Bank' => 'البنك',
            'BankAccount' => 'الحساب البنكي',
            'Bond' => 'السند المالي',
            'Box' => 'الصندوق',
            'Category' => 'التصنيف المالي',
            'Client' => 'العميل المالي',
            'Constraint' => 'القيد المحاسبي',
            'ConstraintDetails' => 'تفاصيل القيد المحاسبي',
            'CostCenter' => 'مركز التكلفة',
            'FnYear' => 'السنة المالية',
            'Inventory' => 'الجرد',
            'Item' => 'الصنف',
            'Purchase' => 'فاتورة المشتريات',
            'PurchaseOrder' => 'أمر الشراء',
            'Sale' => 'فاتورة المبيعات',
            'SalesOrder' => 'أمر البيع',
            'Stock' => 'المخزون',
            'StockMovement' => 'حركة المخزون',
            'Supplier' => 'المورد',
            'TaxSetting' => 'إعدادات الضريبة',
            'ZatcaInvoice' => 'فاتورة هيئة الزكاة والضريبة',
            'Advance' => 'السلفة',
            'Allowance' => 'البدل',
            'Department' => 'القسم',
            'Employee' => 'الموظف',
            'EmployeeAllowance' => 'بدل الموظف',
            'EmployeeAttendances' => 'حضور الموظف',
            'EmployeeCustody' => 'عهدة الموظف',
            'EmployeeDelay' => 'تأخر الموظف',
            'EmployeeInsurance' => 'تأمين الموظف',
            'EmployeePermission' => 'إذن الموظف',
            'EmployeeReward' => 'مكافأة الموظف',
            'EmployeeSalary' => 'راتب الموظف',
            'EmployeeSanction' => 'جزاء الموظف',
            'EmployeeShift' => 'وردية الموظف',
            'EmployeeVacation' => 'إجازة الموظف',
            'Insurance' => 'التأمين',
            'Job' => 'المسمى الوظيفي',
            'OfficialVacation' => 'الإجازة الرسمية',
            'Payroll' => 'مسير الرواتب',
            'PayrollItem' => 'بند مسير الرواتب',
            'Permission' => 'نوع الإذن',
            'Reward' => 'المكافأة',
            'Sanction' => 'الجزاء',
            'Shift' => 'الوردية',
            'Tax' => 'الاستقطاع',
            'Vacation' => 'نوع الإجازة',
            'VacationYear' => 'سنة الإجازات',
        ];

        if (isset($labels[$model])) {
            return $labels[$model];
        }

        $prefix = $package === 'finance' ? 'سجل مالي' : 'سجل موارد بشرية';

        return $prefix . ' (' . Str::headline($model) . ')';
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
        $candidates = [];
        foreach (['properties.attributes', 'properties.old', 'subject'] as $source) {
            foreach (['name', 'title', 'contract_number', 'passport_num', 'phone', 'email'] as $field) {
                $candidates[] = data_get($activity, $source . '.' . $field);
            }
        }

        $value = collect($candidates)->first(fn($candidate) => filled($candidate));

        return $value ? Str::limit(trim((string) $value), 80) : null;
    }

    private function resolveTargetUrl(Activity $activity, string $event): ?string
    {
        $subject = $activity->subject;
        $subjectType = (string) $activity->subject_type;

        if (!$subject) {
            return null;
        }

        if (in_array($subjectType, [\App\Models\ContractStatusLog::class, \App\Models\RecruitmentContractRefund::class, \App\Models\ContractPayment::class, \App\Models\DeportationProcedure::class], true)) {
            return $subject->contract_id ? $this->resolveContractUrl((int) $subject->contract_id) : null;
        }
        if ($subjectType === \App\Models\DeportationProcedureLog::class) {
            $procedure = \App\Models\DeportationProcedure::find($subject->deportation_procedure_id);
            return $procedure?->contract_id ? $this->resolveContractUrl((int) $procedure->contract_id) : null;
        }
        if ($subjectType === \App\Models\FinancialRequestStatusLog::class) {
            return \App\Models\FinancialRequest::whereKey($subject->financial_request_id)->exists()
                ? $this->routeIfExists('financial-requests.show', (int) $subject->financial_request_id) : null;
        }
        if (in_array($subjectType, [\App\Models\RentalContractInvoice::class, \App\Models\RentalFinancialAdjustment::class, \App\Models\RentalContractExtension::class], true)) {
            return RentalContract::whereKey($subject->rental_contract_id)->exists()
                ? $this->routeIfExists('RentContract.show', (int) $subject->rental_contract_id) : null;
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
            \App\Models\MusanedTransfer::class => $this->routeIfExists('musaned-transfers.show', $subjectId),
            \App\Models\IngazDelegation::class => $this->routeIfExists('ingaz-delegations.show', $subjectId),
            \App\Models\FinancialRequest::class => $this->routeIfExists('financial-requests.show', $subjectId),
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
        if (!$subject && !Contract::whereKey($contractId)->exists()) {
            return null;
        }

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
            'debit_account_id' => 'الحساب المدين',
            'credit_account_id' => 'الحساب الدائن',
            'constraint_id' => 'القيد المحاسبي',
            'cost_center_id' => 'مركز التكلفة',
            'employee_id' => 'الموظف',
            'department_id' => 'القسم',
            'deparment_id' => 'القسم',
            'job_id' => 'المسمى الوظيفي',
            'shift_id' => 'الوردية',
            'payroll_id' => 'مسير الرواتب',
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
            'value' => 'القيمة',
            'date' => 'التاريخ',
            'code' => 'الرقم المرجعي',
            'statement' => 'البيان',
            'month_year' => 'شهر المسير',
            'total_gross_salary' => 'إجمالي الرواتب',
            'total_deductions' => 'إجمالي الاستقطاعات',
            'total_net_salary' => 'صافي الرواتب',
            'birth_date' => 'تاريخ الميلاد',
            'arrival_date' => 'تاريخ الوصول',
            'has_passport' => 'وجود الجواز',
            'salary_received' => 'استلام الراتب',
            'has_deportation_letter' => 'خطاب الترحيل',
            'has_settlement_session' => 'جلسة التسوية',
            'has_final_exit' => 'الخروج النهائي',
            'old_sponsor_id' => 'الكفيل السابق',
            'new_sponsor_id' => 'الكفيل الجديد',
            'username' => 'اسم الدخول',
            'role_id' => 'الصلاحية',
            'branch_id' => 'الفرع',
            'is_branch_manager' => 'مدير الفرع',
            'admin_type' => 'نوع المستخدم',
            'is_developer' => 'حساب مطوّر',
            'identity_number' => 'رقم الهوية',
            'identity_expiry_date' => 'انتهاء الهوية',
            'whatsapp' => 'واتساب',
            'lang' => 'اللغة',
            'note' => 'الملاحظات',
            'previous_status' => 'الحالة السابقة',
            'from_status' => 'من الحالة',
            'to_status' => 'إلى الحالة',
            'old_value' => 'القيمة السابقة',
            'new_value' => 'القيمة الجديدة',
            'field_name' => 'الحقل المعدل',
            'performed_by_id' => 'منفذ الإجراء',
            'action_type' => 'نوع الإجراء',
            'description' => 'الوصف',
            'key' => 'مفتاح الإعداد',
            'permission_ids' => 'الصلاحيات',
            'amount' => 'المبلغ',
            'subtotal' => 'الإجمالي قبل الضريبة',
            'vat_amount' => 'قيمة الضريبة',
            'total_due' => 'الإجمالي المستحق',
            'refund_amount' => 'المبلغ المسترد',
            'penalty_amount' => 'مبلغ الخصم',
            'commission_amount' => 'قيمة العمولة',
            'attachments' => 'المرفقات',
            'remarks' => 'ملاحظات الإجراء',
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
