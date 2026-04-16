<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Http\Traits\ContractSMSTrait;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Contact;
use App\Models\ingaz\ContractNote;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\TravelHistory;
use App\Models\RecruitmentOffices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\City;
use App\Models\Country;
use App\Actions\OrderAction;
use App\Actions\MaidAction;
use App\Actions\ClientAction;
use App\Actions\NationalityAction;
use App\Actions\ReligionAction;
use App\Actions\JobTitleAction;
use App\Actions\JobTypeAction;
use Illuminate\Support\Facades\DB;
use App\NewPermKeys;
use PDF;
use App\Models\ingaz\Invoice;

class RecruitmentContractController extends Controller
{
    use ContractSMSTrait;
    public $folderPath = "admin.ingaz.RecruitmentContract";
    public $mainRoute = "RecruitmentContract";
    public $contractInfoData = [
        'visa_number',
        'visa_hijry_date',
        'visa_milad_date',
        'working_hours_num',
        'arrival_date',
        'sponsorship_price',
        'salary_after_sponsorship',
        'monthly_cost',
        'contract_duration',
        'contract_value',
        'contract_date',
        'ingaz_from',
        'ingaz_to',
        'previous_sponsorship',
        'msaned_number',
        'tafwed_number'
    ];

    private function canSelectAnyMarketer($admin): bool
    {
        if (!$admin) {
            return false;
        }

        return $admin->admin_type === 'super_admin'
            || $admin->hasAnyRole(['super_admin', 'admin', 'Administrator']);
    }

    private function getManualStatusTransition(?string $status): ?array
    {
        return match ($status) {
            ContractStatusEnum::New->value => [
                'next_status' => ContractStatusEnum::Msaned->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
                'button_text' => 'انتقال إلى مساند',
            ],
            ContractStatusEnum::AccreditationContracts->value => [
                'next_status' => ContractStatusEnum::Msaned->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
                'button_text' => 'اعتماد العقد إلى مساند',
            ],
            ContractStatusEnum::Msaned->value => [
                'next_status' => ContractStatusEnum::Tafwed->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED,
                'button_text' => 'انتقال إلى تفويض',
            ],
            ContractStatusEnum::Tafwed->value => [
                'next_status' => ContractStatusEnum::Taffez->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ,
                'button_text' => 'انتقال إلى تفييز',
            ],
            ContractStatusEnum::Taffez->value => [
                'next_status' => ContractStatusEnum::Tazker->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER,
                'button_text' => 'انتقال إلى تذكرة',
            ],
            default => null,
        };
    }

    private function getBlockedTransitionReason(?string $status): string
    {
        return match ($status) {
            ContractStatusEnum::PendingMaidSelection->value => 'يجب اختيار العاملة أولاً قبل تحديث حالة العقد',
            ContractStatusEnum::Tazker->value => 'هذه المرحلة تتطلب إضافة رحلة أو إكمالها قبل الانتقال',
            ContractStatusEnum::Arrival->value => 'هذا العقد في مرحلة الوصول ويتم استكماله تلقائياً حسب حالة الضمان',
            ContractStatusEnum::Completed->value => 'العقد مكتمل ولا يمكن تحديث حالته يدوياً',
            ContractStatusEnum::Shelter->value => 'العقد موجود في الإيواء ولا يقبل التحديث من هذه الشاشة',
            ContractStatusEnum::Back->value => 'العقد مرتجع ولا يمكن تحديثه من المراحل الجارية',
            ContractStatusEnum::Closed->value => 'العقد مغلق ولا يمكن تحديث حالته يدوياً',
            ContractStatusEnum::Sari->value => 'هذا العقد ليس ضمن مراحل الاستقدام الحالية',
            default => 'لا يتوفر انتقال تلقائي لهذه المرحلة حالياً',
        };
    }

    private function resolveContractTransitionContext(Contract $contract): array
    {
        $transition = $this->getManualStatusTransition($contract->status);

        if (!$transition) {
            return [
                'can_update' => false,
                'http_status' => 400,
                'current_status' => $contract->status,
                'current_status_text' => getContractStatus($contract->status),
                'next_status' => null,
                'next_status_text' => null,
                'button_text' => null,
                'reason' => $this->getBlockedTransitionReason($contract->status),
            ];
        }

        $hasPermission = @AdminGuardHasPerm($transition['permission']);

        return [
            'can_update' => $hasPermission,
            'http_status' => $hasPermission ? 200 : 403,
            'current_status' => $contract->status,
            'current_status_text' => getContractStatus($contract->status),
            'next_status' => $transition['next_status'],
            'next_status_text' => getContractStatus($transition['next_status']),
            'button_text' => $transition['button_text'],
            'permission' => $transition['permission'],
            'reason' => $hasPermission ? null : 'ليس لديك صلاحية لتحديث هذه المرحلة',
        ];
    }

    private function resolveBulkActionContext(Contract $contract): array
    {
        $transitionContext = $this->resolveContractTransitionContext($contract);

        if ($transitionContext['can_update']) {
            return array_merge($transitionContext, [
                'can_select' => true,
                'action_type' => 'status_update',
                'action_text' => $transitionContext['next_status_text'],
            ]);
        }

        $travelHistory = $contract->relationLoaded('travelHistory')
            ? $contract->travelHistory
            : $contract->travelHistory()->latest('id')->first();

        if ($contract->status === ContractStatusEnum::Tazker->value) {
            if ($travelHistory) {
                return [
                    'can_select' => false,
                    'http_status' => 400,
                    'action_type' => 'travel_create',
                    'current_status' => $contract->status,
                    'current_status_text' => getContractStatus($contract->status),
                    'next_status' => null,
                    'next_status_text' => null,
                    'action_text' => 'إضافة رحلة',
                    'reason' => 'يوجد رحلة مسجلة بالفعل لهذا العقد',
                ];
            }

            if (!@AdminGuardHasPerm(NewPermKeys::REC_CONTRACTS_ADD_TRAVEL) || !@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL)) {
                return [
                    'can_select' => false,
                    'http_status' => 403,
                    'action_type' => 'travel_create',
                    'current_status' => $contract->status,
                    'current_status_text' => getContractStatus($contract->status),
                    'next_status' => null,
                    'next_status_text' => null,
                    'action_text' => 'إضافة رحلة',
                    'reason' => 'ليس لديك صلاحية لإنشاء رحلة لهذا العقد',
                ];
            }

            return [
                'can_select' => true,
                'http_status' => 200,
                'action_type' => 'travel_create',
                'current_status' => $contract->status,
                'current_status_text' => getContractStatus($contract->status),
                'next_status' => null,
                'next_status_text' => null,
                'action_text' => 'إضافة رحلة',
                'reason' => null,
            ];
        }

        return array_merge($transitionContext, [
            'can_select' => false,
            'action_type' => 'status_update',
            'action_text' => $transitionContext['next_status_text'],
        ]);
    }

    private function updateContractStatusRecord(Contract $contract, string $nextStatus, ?string $date = null): array
    {
        $currentStatus = $contract->status;
        $statusDate = $date ?: now()->toDateString();

        DB::transaction(function () use ($contract, $currentStatus, $nextStatus, $statusDate) {
            $contract->update(['status' => $nextStatus]);

            $contract->contract_status_logs()->create([
                'status' => $nextStatus,
                'date' => $statusDate,
                'notes' => 'تم تحديث الحالة من ' . getContractStatus($currentStatus) . ' إلى ' . getContractStatus($nextStatus)
            ]);
        });

        $contract->refresh();

        return [
            'current_status' => $currentStatus,
            'next_status' => $nextStatus,
            'current_status_text' => getContractStatus($currentStatus),
            'next_status_text' => getContractStatus($nextStatus),
        ];
    }


    public function index(Request $request)
    {
        // التحقق من صلاحية عرض العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض العقود'
            ], 403);
        }

        if ($request->ajax()) {


            $query = Contract::with([
                'user', // جميع بيانات العميل
                'user.nationality', // جنسية العميل (Country model)
                'maid', // جميع بيانات العاملة
                'travelHistory',
                'maid.nationality.translations', // جنسية العاملة مع الترجمات
                'maid.religion.translations', // ديانة العاملة مع الترجمات
                'maid.office.translations', // المكتب الخارجي مع الترجمات
                'requiredMaidNationality.translations', // الجنسية المفضلة للعاملة
                'info', // جميع بيانات العقد
                'info.manualMaidNationality.translations',
                'info.manualMaidOffice.translations',
                'info.shelter.translations', // بيانات الإيواء
                'contract_status_logs', // سجل حالات العقد (مطلوب لبعض العمليات)
                'order', // بيانات الطلب
                'order.details', // تفاصيل الطلب
                'order.details.admin' // بيانات المسوق
            ])
                ->withCount('notes')
                ->whereIn('type', ['recruitment', 'maid']);

            // فلتر الحالة - استخدام query parameter فقط
            $statusFilter = $request->get('status');

            if ($statusFilter !== null && $statusFilter !== '') {
                if ($statusFilter == 'current') {
                    $query->whereIn('status', [ContractStatusEnum::PendingMaidSelection, ContractStatusEnum::New, ContractStatusEnum::Msaned, ContractStatusEnum::Tafwed, ContractStatusEnum::Taffez, ContractStatusEnum::Tazker]);
                } elseif ($statusFilter == 'completed_stage') {
                    $query->whereIn('status', [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value]);
                } elseif ($statusFilter == 'completed') {
                    $query->where('status', ContractStatusEnum::Completed->value);
                } else {
                    $query->where('status', $statusFilter);
                }
            }

            // فلتر حالة الضمان إذا تم تمريرها (حسب تاريخ انتهاء الضمان)
            if ($request->filled('warranty_status')) {
                $warrantyStatuses = array_filter(array_map('trim', explode(',', $request->warranty_status)));
                if (!empty($warrantyStatuses)) {
                    $today = \Carbon\Carbon::now()->toDateString();
                    $query->where(function ($q) use ($warrantyStatuses, $today) {
                        foreach ($warrantyStatuses as $status) {
                            switch ($status) {
                                case 'active':
                                    $q->orWhere(function ($sub) use ($today) {
                                        $sub->whereNotNull('warranty_end_date')
                                            ->whereDate('warranty_end_date', '>=', $today);
                                    });
                                    break;
                                case 'expired':
                                    $q->orWhere(function ($sub) use ($today) {
                                        $sub->whereNotNull('warranty_end_date')
                                            ->whereDate('warranty_end_date', '<', $today);
                                    });
                                    break;
                                case 'not_applicable':
                                    $q->orWhereNull('warranty_end_date');
                                    break;
                                default:
                                    $q->orWhere('warranty_status', $status);
                                    break;
                            }
                        }
                    });
                }
            }


            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // فلتر رقم جواز العاملة
            if ($request->filled('maid_passport')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
                });
            }

            // فلتر اسم العاملة
            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            if ($request->filled('request_type')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->ofRequestType($request->request_type);
                });
            }

            // فلتر رقم العقد
            if ($request->filled('contract_number')) {
                $query->where('id', 'like', '%' . $request->contract_number . '%');
            }

            // فلتر جنسية العاملة
            if ($request->filled('nationality')) {
                $query->whereHas('maid.nationality.translations', function ($q) use ($request) {
                    $q->where('sub_title', $request->nationality);
                });
            }

            // فلتر ديانة العاملة
            if ($request->filled('religion')) {
                $query->whereHas('maid.religion.translations', function ($q) use ($request) {
                    $q->where('title', $request->religion);
                });
            }

            // فلتر المكتب الخارجي
            if ($request->filled('office_id')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('office_id', $request->office_id);
                });
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            // فلتر البحث الشامل
            if ($request->filled('global_search')) {
                $this->applyGlobalSearchFilter($query, $request->global_search);
            }

            // فلتر المسوق
            if ($request->filled('marketer_id')) {
                $query->where(function ($q) use ($request) {
                    // البحث في marketer_id المباشر
                    $q->where('marketer_id', $request->marketer_id)
                        // أو البحث في order.details.admin_id
                        ->orWhereHas('order.details', function ($orderQuery) use ($request) {
                            $orderQuery->where('admin_id', $request->marketer_id);
                        });
                });
            }

            $rows = $query->orderBy('id', 'desc');

            logger()->info('Query prepared for DataTables', [
                'filters_applied' => $request->except(['draw', 'start', 'length', 'search', 'order', 'columns']),
                'status_filter' => $statusFilter
            ]);

            // استخدام DataTables::eloquent بدلاً من get() لتحسين الأداء
            return DataTables::eloquent($rows)
                ->addIndexColumn()
                ->addColumn('bulk_select', function ($row) {
                    $bulkActionContext = $this->resolveBulkActionContext($row);

                    if (!$bulkActionContext['can_select']) {
                        $iconClass = $bulkActionContext['http_status'] === 403 ? 'fa-lock' : 'fa-ban';

                        return '<span class="text-muted" title="' . e($bulkActionContext['reason']) . '">
                                    <i class="fas ' . $iconClass . '"></i>
                                </span>';
                    }

                    return '<div class="form-check d-flex justify-content-center mb-0">
                                <input class="form-check-input contract-bulk-checkbox" type="checkbox"
                                    value="' . $row->id . '"
                                    data-contract-id="' . $row->id . '"
                                    data-action-type="' . e($bulkActionContext['action_type']) . '"
                                    data-action-text="' . e($bulkActionContext['action_text']) . '"
                                    data-current-status="' . e($bulkActionContext['current_status']) . '"
                                    data-current-status-text="' . e($bulkActionContext['current_status_text']) . '"
                                    data-next-status="' . e($bulkActionContext['next_status']) . '"
                                    data-next-status-text="' . e($bulkActionContext['next_status_text']) . '">
                            </div>';
                })
                ->editColumn('status', function ($row) {
                    $statusText = getContractStatus($row->status);
                    $statusColor = getContractStatusColor($row->status);
                    $statusDisplay = '<span class="badge bg-' . $statusColor . '">' . $statusText . '</span>';

                    // إضافة تاريخ آخر تحديث
                    $lastUpdate = $row->updated_at ? $row->updated_at->format('Y-m-d') : '';
                    if ($lastUpdate) {
                        $statusDisplay .= '<br><small class="text-muted text-nowrap"><span class="text-secondary">آخر تحديث: </span>' . $lastUpdate . '</small>';
                    }

                    // إضافة حالة الضمان للعقود المكتملة، مع دعم حالة الوصول القديمة
                    if (in_array($row->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true)) {
                        $warrantyStatus = $row->warranty_status ?? 'not_applicable';

                        // حساب الأيام المتبقية من تاريخ انتهاء الضمان
                        $remainingDays = 0;
                        if ($row->warranty_end_date) {
                            $endDate = \Carbon\Carbon::parse($row->warranty_end_date);
                            $now = \Carbon\Carbon::now();

                            // إذا كان تاريخ انتهاء الضمان في المستقبل (ضمان نشط)
                            if ($endDate->isFuture()) {
                                $remainingDays = $now->diffInDays($endDate);
                                // تحديث حالة الضمان إذا كانت غير صحيحة
                                if ($warrantyStatus !== 'active') {
                                    $row->update(['warranty_status' => 'active']);
                                    $warrantyStatus = 'active';
                                }
                            }
                            // إذا كان تاريخ انتهاء الضمان في الماضي (ضمان منتهي)
                            else {
                                $remainingDays = 0;
                                // تحديث حالة الضمان إذا كانت غير صحيحة
                                if ($warrantyStatus !== 'expired') {
                                    $row->update(['warranty_status' => 'expired']);
                                    $warrantyStatus = 'expired';
                                }
                            }
                        }

                        switch ($warrantyStatus) {
                            case 'active':
                                $warrantyColor = 'success';
                                $warrantyIcon = 'fas fa-shield-alt';
                                $warrantyText = $remainingDays > 0 ? "ضمان نشط ({$remainingDays} يوم)" : "ضمان نشط";
                                break;
                            case 'expired':
                                $warrantyColor = 'danger';
                                $warrantyIcon = 'fas fa-times-circle';
                                $warrantyText = 'ضمان منتهي';
                                break;
                            case 'not_applicable':
                            default:
                                $warrantyColor = 'secondary';
                                $warrantyIcon = 'fas fa-minus-circle';
                                $warrantyText = 'لا يوجد ضمان';
                                break;
                        }

                        $statusDisplay .= '<br><small><span class="badge bg-' . $warrantyColor . '"><i class="' . $warrantyIcon . ' me-1"></i>' . $warrantyText . '</span></small>';
                    }

                    return $statusDisplay;
                })

                ->editColumn('clirnt_data', function ($row) {
                    $phoneInfo = '';
                    if ($row->user) {
                        $phoneInfo  = '<div class="d-flex flex-column gap-3">';

                        // Name
                        $phoneInfo .= '<div class="fw-semibold text-dark">'
                            . $row->user->name
                            . '</div>';

                        // Phone
                        $phoneInfo .= '<div>'
                            . getPhoneWithTag($row->user->phone_code . $row->user->phone)
                            . '</div>';

                        // Birthdate
                        if ($row->user->birthdate) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-cake-candles text-secondary"></i>'
                                . '<span><span class="text-secondary">تاريخ الميلاد: </span>'
                                . \Carbon\Carbon::parse($row->user->birthdate)->format('Y-m-d')
                                . '</span>'
                                . '</div>';
                        }

                        // National ID
                        if ($row->user->national_num) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-id-card text-secondary"></i>'
                                . '<span><span class="text-secondary">رقم الهوية: </span>' . $row->user->national_num . '</span>'
                                . '</div>';
                        }

                        // Address
                        if ($row->user->address) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-location-dot text-secondary"></i>'
                                . '<span style="max-width:180px;"><span class="text-secondary font-bold"><span class="text-secondary">العنوان: </span></span></br>' . $row->user->address . '</span>'
                                . '</div>';
                        }

                        $phoneInfo .= '</div>';
                    }
                    return $phoneInfo;
                })
                ->editColumn('maid_id', function ($row) {
                    $maidInfo = '';
                    if ($row->maid) {
                        $maidInfo  = '<div class="d-flex flex-column gap-3">';

                        // Name
                        $maidInfo .= '<div class="fw-semibold text-dark">'
                            . $row->maid->name
                            . '</div>';

                        // Passport
                        if ($row->maid->passport_num) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-passport text-secondary"></i>'
                                . '<span><span class="text-secondary">جواز: </span>' . $row->maid->passport_num . '</span>'
                                . '</div>';
                        }

                        // Nationality
                        if ($row->maid->nationality) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-flag text-secondary"></i>'
                                . '<span><span class="text-secondary">الجنسية: </span>' . $row->maid->nationality->sub_title . '</span>'
                                . '</div>';
                        }

                        // Office
                        if ($row->maid->office) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-building text-secondary"></i>'
                                . '<span><span class="text-secondary">المكتب الخارجي: </span>' . $row->maid->office->title . '</span>'
                                . '</div>';
                        }

                        $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                            . '<i class="fas fa-diagram-project text-secondary"></i>'
                            . '<span><span class="text-secondary">نوع الطلب: </span>' . e($row->maid->request_type_label) . '</span>'
                            . '</div>';

                        // Religion
                        if ($row->maid->religion) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-praying-hands text-secondary"></i>'
                                . '<span><span class="text-secondary">الديانة: </span>' . $row->maid->religion->title . '</span>'
                                . '</div>';
                        }

                        $maidInfo .= '</div>';
                    } elseif ($row->info && $row->info->hasManualMaidSummary()) {
                        $manualNationality = $row->info->manualMaidNationality?->sub_title ?? 'غير محدد';
                        $manualOffice = $row->info->manualMaidOffice?->title ?? 'غير محدد';

                        $maidInfo  = '<div class="d-flex flex-column gap-2">';
                        $maidInfo .= '<div class="fw-semibold text-dark">'
                            . e($row->info->manual_maid_name ?: 'بيانات عاملة مختصرة')
                            . '</div>';

                        if ($row->info->manual_maid_passport_num) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-passport text-secondary"></i>'
                                . '<span><span class="text-secondary">جواز: </span>' . e($row->info->manual_maid_passport_num) . '</span>'
                                . '</div>';
                        }

                        $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                            . '<i class="fas fa-flag text-secondary"></i>'
                            . '<span><span class="text-secondary">الجنسية: </span>' . e($manualNationality) . '</span>'
                            . '</div>';

                        $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                            . '<i class="fas fa-building text-secondary"></i>'
                            . '<span><span class="text-secondary">المكتب الخارجي: </span>' . e($manualOffice) . '</span>'
                            . '</div>';

                        if ($row->info->manual_maid_attachment && showFile($row->info->manual_maid_attachment)) {
                            $maidInfo .= '<div>'
                                . '<a class="btn btn-sm btn-outline-primary" href="' . e(showFile($row->info->manual_maid_attachment)) . '" target="_blank" rel="noopener">'
                                . '<i class="fas fa-paperclip me-1"></i>عرض المرفق'
                                . '</a>'
                                . '</div>';
                        }

                        $maidInfo .= '<div><span class="badge bg-warning text-dark">بيانات مختصرة مؤقتة</span></div>';
                        $maidInfo .= '</div>';
                    } else {
                        $maidInfo = '<div class="d-flex flex-column gap-2">';

                        // Status message
                        if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                            $maidInfo .= '<div class="text-warning fw-semibold d-flex align-items-center gap-2">'
                                . '<i class="fas fa-clock"></i>'
                                . '<span>في انتظار اختيار العاملة</span>'
                                . '</div>';
                        } else {
                            $maidInfo .= '<div class="text-danger fw-semibold d-flex align-items-center gap-2">'
                                . '<i class="fas fa-circle-xmark"></i>'
                                . '<span>لا توجد عاملة مخصصة</span>'
                                . '</div>';
                        }

                        // Preferred requirements
                        $preferredRequirements = [];

                        // Nationality
                        if ($row->required_maid_nationality_id && $row->requiredMaidNationality) {
                            $preferredRequirements[] =
                                '<span class="badge bg-info-subtle text-info">'
                                . '<i class="fas fa-flag me-1 text-secondary"></i>'
                                . $row->requiredMaidNationality->sub_title
                                . '</span>';
                        }

                        // Religion
                        if ($row->required_maid_religion_id && $row->requiredMaidReligion) {
                            $preferredRequirements[] =
                                '<span class="badge bg-success-subtle text-success">'
                                . '<i class="fas fa-praying-hands me-1 text-secondary"></i>'
                                . $row->requiredMaidReligion->title
                                . '</span>';
                        }

                        // Age
                        if ($row->required_maid_age_min || $row->required_maid_age_max) {
                            $ageText = $row->required_maid_age_min && $row->required_maid_age_max
                                ? $row->required_maid_age_min . '-' . $row->required_maid_age_max
                                : ($row->required_maid_age_min
                                    ? 'من ' . $row->required_maid_age_min
                                    : 'إلى ' . $row->required_maid_age_max);

                            $preferredRequirements[] =
                                '<span class="badge bg-warning-subtle text-warning">'
                                . '<i class="fas fa-user-clock me-1 text-secondary"></i>'
                                . 'العمر: ' . $ageText
                                . '</span>';
                        }

                        // Experience
                        if ($row->required_maid_experience_min) {
                            $preferredRequirements[] =
                                '<span class="badge bg-primary-subtle text-primary">'
                                . '<i class="fas fa-briefcase me-1 text-secondary"></i>'
                                . $row->required_maid_experience_min . ' سنوات خبرة'
                                . '</span>';
                        }

                        // Job title
                        if ($row->required_maid_job_title_id && $row->requiredMaidJobTitle) {
                            $preferredRequirements[] =
                                '<span class="badge bg-secondary-subtle text-secondary">'
                                . '<i class="fas fa-user-tag me-1 text-secondary"></i>'
                                . $row->requiredMaidJobTitle->title
                                . '</span>';
                        }

                        // Render requirements
                        if (!empty($preferredRequirements)) {
                            $maidInfo .= '<div class="small text-muted">المتطلبات المفضلة:</div>';
                            $maidInfo .= '<div class="d-flex flex-wrap gap-2">'
                                . implode('', $preferredRequirements)
                                . '</div>';
                        }

                        $maidInfo .= '</div>';
                    }
                    return $maidInfo;
                })
                ->addColumn('contract_info', function ($row) {
                    $contractInfo = '';
                    if ($row->info) {
                        $contractInfo .= '<div class="contract-info d-flex flex-column gap-3">';
                        if ($row->info->msaned_number) {
                            $contractInfo .= '<div> <i class="fas fa-file-invoice-dollar text-secondary"></i>
                            <span class="text-secondary">مساند: </span> ' . $row->info->msaned_number . '
                            </div>';
                        }
                        // if ($row->info->tafwed_number) {
                        //     $contractInfo .= '<div><strong>تفويض:</strong> ' . $row->info->tafwed_number . '</div>';
                        // }
                        if ($row->info->visa_number) {
                            $contractInfo .= '<div class="d-flex align-items-center gap-2"><i class="fas fa-passport text-secondary"></i><span class="text-secondary">تأشيرة:</span> ' . $row->info->visa_number . '</div>';
                        }
                        if ($row->info->contract_date) {
                            $contractInfo .= '<div class="d-flex align-items-center gap-2 text-nowrap"><i class="fas fa-calendar-day text-secondary"></i><span class="text-secondary text-nowrap">تاريخ العقد:</span> ' . \Carbon\Carbon::parse($row->info->contract_date)->format('Y-m-d') . '</div>';
                        }

                        // إضافة معلومات الضمان للعقود المكتملة، مع دعم حالة الوصول القديمة
                        if (in_array($row->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true) && $row->warranty_end_date) {
                            $endDate = \Carbon\Carbon::parse($row->warranty_end_date);
                            $now = \Carbon\Carbon::now();

                            if ($endDate->isFuture()) {
                                $remainingDays = $now->diffInDays($endDate);
                                $contractInfo .= '<div class="text-success d-flex align-items-center gap-2"><i class="fas fa-shield-check text-secondary"></i><span class="text-secondary">أيام الضمان:</span> ' . $remainingDays . ' يوم</div>';
                            } else {
                                $contractInfo .= '<div class="text-danger d-flex align-items-center gap-2"><i class="fas fa-shield-exclamation text-secondary"></i><span class="text-secondary">الضمان:</span> منتهي</div>';
                            }
                        }

                        $contractInfo .= '</div>';
                    }

                    // إضافة المتطلبات المفضلة للعاملة
                    $preferredRequirements = [];

                    if ($row->required_maid_nationality_id && $row->requiredMaidNationality) {
                        $preferredRequirements[] = '<span class="badge bg-info">الجنسية: ' . $row->requiredMaidNationality->sub_title . '</span>';
                    }

                    if ($row->required_maid_religion_id && $row->requiredMaidReligion) {
                        $preferredRequirements[] = '<span class="badge bg-success">الديانة: ' . $row->requiredMaidReligion->title . '</span>';
                    }

                    if ($row->required_maid_age_min && $row->required_maid_age_max) {
                        $preferredRequirements[] = '<span class="badge bg-warning">العمر: ' . $row->required_maid_age_min . '-' . $row->required_maid_age_max . '</span>';
                    } elseif ($row->required_maid_age_min) {
                        $preferredRequirements[] = '<span class="badge bg-warning">العمر: من ' . $row->required_maid_age_min . '</span>';
                    } elseif ($row->required_maid_age_max) {
                        $preferredRequirements[] = '<span class="badge bg-warning">العمر: إلى ' . $row->required_maid_age_max . '</span>';
                    }

                    if ($row->required_maid_experience_min) {
                        $preferredRequirements[] = '<span class="badge bg-primary">الخبرة: ' . $row->required_maid_experience_min . ' سنوات</span>';
                    }

                    if ($row->required_maid_job_title_id && $row->requiredMaidJobTitle) {
                        $preferredRequirements[] = '<span class="badge bg-secondary">المهنة: ' . $row->requiredMaidJobTitle->title . '</span>';
                    }

                    // if (!empty($preferredRequirements)) {
                    //     $contractInfo .= '<br><div class="mt-2"><strong>المتطلبات المفضلة:</strong><br>' . implode(' ', $preferredRequirements) . '</div>';
                    // }

                    return $contractInfo ?: '<span class="text-muted"><i class="fas fa-minus-circle me-1"></i>غير محدد</span>';
                })
                ->addColumn('marketer', function ($row) {
                    $marketerInfo = '';

                    // البحث عن المسوق من marketer_id المباشر
                    if ($row->marketer_id) {
                        $marketer = \App\Models\auth\Admin::find($row->marketer_id);
                        if ($marketer) {
                            $marketerInfo = '<div class="marketer-info text-nowrap text-secondary">';
                            $marketerInfo .= '<i class="fas fa-user-tie me-1 text-secondary"></i>';
                            $marketerInfo .= '<span>' . htmlspecialchars($marketer->name) . '</span>';
                            $marketerInfo .= '</div>';
                        }
                    }

                    // إذا لم يوجد marketer_id، البحث في order.details.admin
                    if (empty($marketerInfo) && $row->order && $row->order->resolveResponsibleAdmin()) {
                        $marketer = $row->order->resolveResponsibleAdmin();
                        $marketerInfo = '<div class="marketer-info">';
                        $marketerInfo .= '<i class="fas fa-user-tie me-1 text-secondary"></i>';
                        $marketerInfo .= '<strong>' . htmlspecialchars($marketer->name) . '</strong>';
                        $marketerInfo .= '<br><small class="text-muted">من الطلب</small>';
                        $marketerInfo .= '</div>';
                    }

                    return $marketerInfo ?: '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>غير محدد</span>';
                })
                ->addColumn('recruitment_office', function ($row) {
                    $office = $row->maid?->office ?? $row->maid?->recruitmentOffice ?? $row->info?->manualMaidOffice;
                    return $office?->title ?  '<span class="text-muted">' . $office?->title .'</span>' : '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('updateStatus', function ($row) {
                    if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                        // زر إضافة عاملة للعقود التي في حالة بانتظار اختيار العاملة
                        return '<a href="' . route('RecruitmentContract.selectMaid', $row->id) . '" class="btn btn-warning btn-sm btn-action" title="إضافة عاملة">
                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                        </a>';
                    } else if ($row->status == ContractStatusEnum::Tazker->value) {
                        // التحقق من صلاحيات إدارة الرحلات
                        $hasTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL);
                        $hasAddTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL);
                        $hasCompleteTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL);

                        // check if there is a travel history for this contract
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();

                        if ($travelHistory) {
                            if ($travelHistory->status === 'completed') {
                                return '<span class="badge bg-success">تم إكمال الرحلة</span>';
                            } else {
                                // عرض زر إكمال الرحلة لجميع الرحلات غير المكتملة
                                if ($hasCompleteTravelPermission) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                } else {
                                    $statusText = $travelHistory->status === 'active' ? 'رحلة نشطة' : 'رحلة قيد الإعداد';
                                    return '<span class="badge bg-warning">' . $statusText . '</span>';
                                }
                            }
                        } else {
                            if ($hasAddTravelPermission) {
                                return '<button class="btn btn-primary btn-sm add-travel-btn btn-action" style="padding: 10px; margin: 10px;" data-contract-id="' . $row->id . '" title="إضافة رحلة">
                                    <i class="fas fa-plane me-1"></i>إضافة رحلة
                                </button>';
                            } else {
                                return '<span class="text-muted">لا توجد صلاحية لإضافة رحلة</span>';
                            }
                        }
                    } else {
                        // التحقق من الصلاحيات حسب حالة العقد
                        $hasPermission = false;
                        $permissionRequired = '';
                        $nextStatus = '';
                        $buttonText = '';

                        switch ($row->status) {
                            case ContractStatusEnum::New->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                                $nextStatus = ContractStatusEnum::Msaned->value;
                                $buttonText = 'انتقال إلى مساند';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::AccreditationContracts->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                                $nextStatus = ContractStatusEnum::Msaned->value;
                                $buttonText = 'اعتماد العقد إلى مساند';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Msaned->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                                $nextStatus = ContractStatusEnum::Tafwed->value;
                                $buttonText = 'انتقال إلى تفويض';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Tafwed->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                                $nextStatus = ContractStatusEnum::Taffez->value;
                                $buttonText = 'انتقال إلى تفييز';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Taffez->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                                $nextStatus = ContractStatusEnum::Tazker->value;
                                $buttonText = 'انتقال إلى تذكرة';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Arrival->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                return '<span class="text-muted">يُستكمل تلقائياً بعد انتهاء الضمان</span>';
                            case ContractStatusEnum::Sari->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Shelter->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::UnderWarranty->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Closed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Completed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            default:
                                break;
                        }

                        if ($hasPermission) {
                            $lastContractStatusLog = $row->getLastContractStatusLog();
                            return '<button class="btn btn-success btn-sm update-status-btn btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-next-status="' . $nextStatus . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '" title="' . $buttonText . '">
                                <i class="fas fa-arrow-up me-1"></i>' . $buttonText . '
                            </button>';
                        } else {
                            return '<span class="text-muted">لا توجد صلاحية</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"  aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // تفاصيل العقد
                    $actions .= '<li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض تفاصيل العقد
                                </a></li>';

                    if (in_array($row->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true)) {
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('contract_download_form.download', ['contract_id' => $row->id])) . '" target="_blank" rel="noopener">
                                    <i class="fas fa-file-signature"></i> عرض نموذج تسليم العاملة
                                </a></li>';
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('contract_download_form.download', ['contract_id' => $row->id, 'driver' => 'dompdf', 'download' => 1])) . '" target="_blank" rel="noopener">
                                    <i class="fas fa-file-pdf"></i> تنزيل نموذج التسليم PDF
                                </a></li>';
                    }

                    // الفاتورة
                    $invoice = Invoice::with('musanedTransferItem.transfer')->where('contract_id', $row->id)->first();
                    if ($invoice) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('invoices.show', $invoice->id) . '" target="_blank">
                                    <i class="fas fa-file-pdf"></i> عرض الفاتورة
                                </a></li>';

                        if ($invoice->musanedTransferItem && $invoice->musanedTransferItem->transfer) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('musaned-transfers.show', $invoice->musanedTransferItem->transfer->id) . '" target="_blank">
                                    <i class="fas fa-money-bill-transfer"></i> عرض تحويل مساند
                                </a></li>';
                        } else {
                            $actions .= '<li><a class="dropdown-item" href="' . route('musaned-transfers.create', ['contract_ids' => [$row->id]]) . '" target="_blank">
                                    <i class="fas fa-money-bill-transfer"></i> تسجيل تحويل مساند
                                </a></li>';
                        }
                    } else {
                        $actions .= '<li><a class="dropdown-item" href="' . route('invoices.create') . '?contract_id=' . $row->id . '" target="_blank">
                                    <i class="fas fa-file-invoice"></i> اضافة فاتورة
                                </a></li>';
                    }

                    // منع التعديل والحذف للعقود المكتملة والمرتجعة والتي في الإيواء
                    if (
                        $row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Back->value &&
                        $row->status !== ContractStatusEnum::Shelter->value
                    ) {

                        // زر التعديل
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
                            $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                                            <i class="fas fa-edit"></i> تعديل العقد
                                        </a></li>';
                        }

                        // زر الحذف
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                            $actions .= '<li><button class="dropdown-item delete-contract-btn" data-contract-id="' . $row->id . '" data-contract-number="' . $row->id . '">
                                            <i class="fas fa-trash"></i> حذف العقد
                                        </button></li>';
                        }
                    } else {
                        // للعقود المكتملة والمرتجعة والتي في الإيواء - إضافة رسالة توضيحية
                        if ($row->status === ContractStatusEnum::Completed->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-success">العقد مكتمل - لا يمكن التعديل</span>
                                        </span></li>';
                        } elseif ($row->status === ContractStatusEnum::Shelter->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-info">العقد في الإيواء - لا يمكن التعديل</span>
                                        </span></li>';
                        } else {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-danger">العقد مرتجع - لا يمكن التعديل</span>
                                        </span></li>';
                        }
                    }

                    // زر إكمال الرحلة - يظهر فقط للعقود التي لديها رحلة فعلية وليس في مرحلة التذكرة
                    $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                    if ($travelHistory && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                        // التأكد من أن العقد ليس في مرحلة التذكرة وأن الرحلة موجودة ولم تكتمل
                        if (
                            $row->status !== ContractStatusEnum::Tazker->value &&
                            $travelHistory->status !== 'completed'
                        ) {
                            $actions .= '<li><button class="dropdown-item complete-trip-btn" data-trip-id="' . $travelHistory->id . '">
                                            <i class="fas fa-check"></i> إكمال الرحلة
                                        </button></li>';
                        }
                    }

                    // زر إضافة رحلة - يظهر للعقود في مرحلة التذكرة التي لا تحتوي على رحلة
                    if (
                        $row->status === ContractStatusEnum::Tazker->value &&
                        !$travelHistory &&
                        @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL)
                    ) {
                        $actions .= '<li><button class="dropdown-item add-travel-btn" data-contract-id="' . $row->id . '">
                                        <i class="fas fa-plane"></i> إضافة رحلة
                                    </button></li>';
                    }

                    // زر الإرجاع - لا يظهر للعقود المكتملة أو التي في الإيواء
                    if ($row->status !== ContractStatusEnum::Completed->value && $row->status !== ContractStatusEnum::Shelter->value) {
                        if ($row->status == ContractStatusEnum::Back->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-warning">مرتجع</span>
                                        </span></li>';
                        } else {
                            // التحقق من صلاحية إرجاع العقد
                            if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                                $backStatus = contractBackStatus($row->status);

                                if ($backStatus->value !== $row->status) {
                                    $backStatusLabel = e(getContractStatus($backStatus->value));

                                    $actions .= '<li><button class="dropdown-item backContract" data-row-id="' . $row->id . '" data-target-status="' . e($backStatus->value) . '" data-target-label="' . $backStatusLabel . '">
                                                    <i class="fas fa-undo"></i> إرجاع إلى ' . $backStatusLabel . '
                                                </button></li>';
                                }
                            }
                        }
                    }

                    // زر إلغاء العقد - لا يظهر للعقود المكتملة أو المرتجعة أو التي في الإيواء
                    if (
                        $row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Back->value &&
                        $row->status !== ContractStatusEnum::Shelter->value
                    ) {
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                            $actions .= '<li><button class="dropdown-item cancel-contract-btn" data-contract-id="' . $row->id . '" data-contract-number="' . $row->id . '" data-client-name="' . ($row->user ? $row->user->name : 'غير محدد') . '" data-maid-name="' . ($row->maid ? $row->maid->name : 'غير محدد') . '">
                                            <i class="fas fa-times-circle"></i> إلغاء العقد
                                        </button></li>';
                        }
                    }
                    
                    // اضافة ملاحظة على العقد
                    $actions .= '<li><button class="dropdown-item add-contract-note-btn" data-contract-id="' . $row->id . '" data-notes-count="' . ($row->notes_count ?? 0) . '" data-bs-toggle="modal"
                                    data-bs-target="#addContractNoteModal">
                                            <i class="fas fa-plus"></i> اضافة ملاحظة
                                        </button></li>';

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->escapeColumns([])
                ->make(true);
        }

        // جلب خيارات الفلترة
        $filterOptions = $this->getFilterOptions();

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data["status"] = $status ?? $request->get('status', null);
        $data["filterOptions"] = $filterOptions;
        return view($this->folderPath . '.index', $data);
    }


    public function details($id)
    {
        // التحقق من صلاحية عرض تفاصيل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض تفاصيل العقد'
            ], 403);
        }

        $contract = Contract::with([
            'user',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'info.prevSponsorship',
            'info.manualMaidNationality',
            'info.manualMaidOffice',
            'info.shelter',
            'contract_status_logs' => function ($query) {
                $query->orderByDesc('date')->orderByDesc('id');
            },
            'notes' => function ($query) {
                $query->latest();
            },
            'notes.admin'
        ])->findOrFail($id);

        // إضافة علاقة order فقط إذا كان order_id موجود
        if ($contract->order_id) {
            $contract->load(['order.details.admin']);
        }

        $info = $contract->info ?? new ContractInfo();

        // الحصول على بيانات المسوق
        $marketer = null;
        try {
            // أولاً: التحقق من marketer_id في العقد مباشرة
            if ($contract->marketer_id) {
                $marketer = \App\Models\auth\Admin::find($contract->marketer_id);
            }
            // ثانياً: التحقق من بيانات الطلب إذا لم يوجد marketer_id
            elseif ($contract->order) {
                $marketer = $contract->order->resolveResponsibleAdmin();
            }
        } catch (\Exception $e) {
            logger()->warning('Could not load marketer for contract: ' . $contract->id, [
                'error' => $e->getMessage(),
                'contract_marketer_id' => $contract->marketer_id,
                'contract_order_id' => $contract->order_id
            ]);
        }

        //        return $contract;
        return view('admin.ingaz.RecruitmentContract.details', compact('contract', 'info', 'marketer'));
    }

    public function storeNote(Request $request, $contractId)
    {
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإضافة ملاحظات للعقد'
                ], 403);
            }

            return redirect()->back()->with('error', 'ليس لديك صلاحية لإضافة ملاحظات للعقد');
        }

        $request->validate([
            'note' => 'required|string|max:1000'
        ]);

        $contract = Contract::findOrFail($contractId);

        $note = ContractNote::create([
            'contract_id' => $contract->id,
            'admin_id' => Auth::guard('admin')->id() ?? auth()->id(),
            'note' => $request->note
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            $note->load('admin');
            $notesCount = $contract->notes()->count();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الملاحظة بنجاح',
                'note' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'admin_name' => $note->admin->name ?? 'غير محدد',
                    'created_at' => $note->created_at ? $note->created_at->format('Y-m-d H:i') : ''
                ],
                'notes_count' => $notesCount,
                'contract_id' => $contract->id
            ]);
        }

        return redirect()->back()->with('success', 'تم إضافة الملاحظة بنجاح');
    }

    public function create()
    {
        // التحقق من صلاحية إنشاء عقد جديد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $orderId = request('orderId');
        logger()->info('Loading create page with orderId: ' . $orderId);

        $eligibleOrdersQuery = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'jobType.translations'
        ])
            ->where('job_type_id', 1)
            ->where('status', OrderEnum::UnderProcess->value)
            ->orderByDesc('created_at');

        // Load the selected order with all necessary relationships
        $selectedOrder = null;
        if ($orderId) {
            $selectedOrder = (clone $eligibleOrdersQuery)->find($orderId);
            logger()->info('Selected order loaded:', ['order' => $selectedOrder ? $selectedOrder->toArray() : null]);
        }

        // Only recruitment orders that are under process are eligible for contract creation.
        $orders = (clone $eligibleOrdersQuery)->get();

        // Get ended orders with relationships
        $endedOrders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'jobType.translations'
        ])
            ->where('status', 'ended')
            ->get();

        // Get all available maids with relationships - استبعاد العاملات التي لها عقود نشطة
        $all_available_maids = Maid::with([
            'nationality',
            'religion',
            'jobTitles',
            'office'
        ])
            ->where('available', true)
            ->where('status', 'available')
            ->where('is_show', true)
            ->where('available_at', '<=', now()->toDateString())
            ->whereNotNull('name')
            ->where('name', '!=', '')
            // استبعاد العاملات التي لها عقود نشطة أو معلقة
            ->whereDoesntHave('contracts', function ($q) {
                $q->whereNotIn('status', ['back', 'closed', 'completed']);
            })
            // استبعاد العاملات التي لها طلبات نشطة
            ->whereDoesntHave('orders', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'in_progress']);
            })
            ->get();

        // Get existing clients for new contract
        $existing_clients = User::with('nationality')->get();

        // If we have a selected order and it's not in the new orders list, add it
        if ($selectedOrder && !$orders->contains('id', $selectedOrder->id)) {
            $orders->prepend($selectedOrder);
        }

        $countries = Country::active()->ordered()->get();
        $maidNationalities = Nationality::with('translations')->get();
        $recruitmentOffices = RecruitmentOffices::with('translations')->orderBy('id')->get();
        $saudiCities = City::query()
            ->whereHas('country', function ($query) {
                $query->where('code', 'SA');
            })
            ->active()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en']);

        // Get current user info
        $currentUser = Auth::guard('admin')->user();
        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentUser);
        $marketers = $canSelectAnyMarketer
            ? \App\Models\auth\Admin::whereNull('deleted_at')->orderBy('name')->get()
            : collect([$currentUser])->filter();

        return view('admin.ingaz.RecruitmentContract.create', compact('orders', 'endedOrders', 'countries', 'maidNationalities', 'recruitmentOffices', 'saudiCities', 'all_available_maids', 'selectedOrder', 'orderId', 'existing_clients', 'currentUser', 'canSelectAnyMarketer', 'marketers'));
    }


    public function store(StoreContractRequest $request)
    {
        // التحقق من صلاحية إنشاء العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديد المستخدم الحالي'
            ], 401);
        }

        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentAdmin);
        $marketerId = $canSelectAnyMarketer
            ? (int) $request->marketer_id
            : (int) $currentAdmin->id;

        // logger()->info('Recruitment Contract Creation Request:', $request->all());
        // logger()->info('Request validation:', [
        //     'order_id' => $request->order_id,
        //     'client_type' => $request->client_type,
        //     'contract_date' => $request->contract_date,
        //     'msaned_number' => $request->msaned_number,
        //     'visa_number' => $request->visa_number,
        //     'contract_duration' => $request->contract_duration,
        //     'client_national_num' => $request->client_national_num,
        //     'new_client_national_num' => $request->new_client_national_num,
        //     'client_birthdate' => $request->client_birthdate,
        //     'new_client_birthdate' => $request->new_client_birthdate,
        //     'client_address' => $request->address,
        //     'new_client_address' => $request->new_client_address,
        //     'client_phone2' => $request->phone2,
        //     'new_client_phone2' => $request->new_client_phone2,
        //     'client_city' => $request->city,
        //     'new_client_city' => $request->new_client_city
        // ]);

        try {
            DB::beginTransaction();

            // دعم حالة عقد بدون عاملة أو تأجيل اختيار العاملة
            $maidSelectionMode = $request->input('maid_selection_mode', 'existing');
            $maidId = $request->maid_id ?? $request->new_maid_id;
            $isNoMaid = ($maidId === 'no_maid');
            $isMaidSelectionPostponed = $request->maid_selection_postponed == '1';
            $isManualMaidSummary = $maidSelectionMode === 'manual'
                && (
                    $request->filled('manual_maid_name')
                    || $request->filled('manual_maid_passport_num')
                    || $request->filled('manual_maid_nationality_id')
                    || $request->filled('manual_maid_office_id')
                    || $request->hasFile('manual_maid_attachment')
                );
            $shouldCreateWithoutAssignedMaid = $isNoMaid || $isMaidSelectionPostponed || $isManualMaidSummary;

            // تحديد حالة العقد
            $contractStatus = ContractStatusEnum::New->value;
            if ($shouldCreateWithoutAssignedMaid) {
                $contractStatus = ContractStatusEnum::PendingMaidSelection->value;
            }

            $contractData = [
                'status' => $contractStatus,
                'maid_id' => $shouldCreateWithoutAssignedMaid ? null : $maidId,
                'required_maid_nationality_id' => $request->required_maid_nationality, // حفظ الجنسية المفضلة
                'required_maid_religion_id' => $request->required_maid_religion, // حفظ الدين المطلوب
                'required_maid_age_min' => $request->required_maid_age_min, // حفظ الحد الأدنى للعمر
                'required_maid_age_max' => $request->required_maid_age_max, // حفظ الحد الأقصى للعمر
                'required_maid_experience_min' => $request->required_maid_experience_min, // حفظ الحد الأدنى للخبرة
                'required_maid_job_title_id' => $request->required_maid_job_title, // حفظ المهنة المطلوبة
                'required_maid_notes' => $request->required_maid_notes, // حفظ الملاحظات
            ];

            if ($request->order_id) {
                $order = Order::findOrFail($request->order_id);
                logger()->info('Found order:', ['order' => $order->toArray()]);

                // تحديث بيانات المستخدم من الطلب المسبق
                $user = $order->user;
                if ($user) {
                    logger()->info('Updating user from order - Request data:', [
                        'new_client_national_num' => $request->new_client_national_num,
                        'client_national_num' => $request->client_national_num,
                        'new_client_birthdate' => $request->new_client_birthdate,
                        'client_birthdate' => $request->client_birthdate,
                        'new_client_address' => $request->new_client_address,
                        'address' => $request->address,
                        'new_client_phone2' => $request->new_client_phone2,
                        'phone2' => $request->phone2,
                        'new_client_city' => $request->new_client_city,
                        'city' => $request->city,
                        'user_current_national_num' => $user->national_num
                    ]);

                    $user->update([
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num ?? $user->national_num,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate ?? $user->birthdate,
                        'address' => $request->new_client_address ?? $request->address ?? $user->address,
                        'phone2' => $request->new_client_phone2 ?? $request->phone2 ?? $user->phone2,
                        'city' => $request->new_client_city ?? $request->city ?? $user->city,
                        'beds_num' => $request->new_client_beds_num ?? $request->beds_num ?? $user->beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->family_num ?? $user->family_num,
                        'children' => $request->new_client_children ?? $request->children ?? $user->children,
                        'job' => $request->new_client_job ?? $request->job ?? $user->job,
                    ]);
                    logger()->info('Updated user from order:', ['user' => $user->toArray()]);
                }

                $contractData['user_id'] = $order->user_id;
                $contractData['maid_id'] = $shouldCreateWithoutAssignedMaid ? null : $order->maid_id;
                $contractData['order_id'] = $order->id; // إضافة order_id للعقد
                $contractData['marketer_id'] = $marketerId; // تقييد المسوق حسب صلاحية المستخدم الحالي

                logger()->info('Contract data from order:', $contractData);
            } else {
                // Handle new contract without existing order
                logger()->info('Creating/updating user for new contract - Request data:', [
                    'client_phone' => $request->client_phone,
                    'client_name' => $request->client_name,
                    'new_client_national_num' => $request->new_client_national_num,
                    'client_national_num' => $request->client_national_num,
                    'new_client_birthdate' => $request->new_client_birthdate,
                    'client_birthdate' => $request->client_birthdate,
                    'new_client_address' => $request->new_client_address,
                    'client_address' => $request->client_address,
                    'new_client_phone2' => $request->new_client_phone2,
                    'client_phone2' => $request->client_phone2,
                    'new_client_city' => $request->new_client_city,
                    'client_city' => $request->client_city
                ]);

                $user = null;

                if ($request->filled('client_id')) {
                    $user = User::find($request->client_id);
                }

                if (!$user && $request->filled('client_phone')) {
                    $user = User::where('phone', $request->client_phone)->first();
                }

                if (!$user) {
                    $user = User::create([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'phone' => $request->client_phone,
                        'password' => bcrypt('123456'), // Default password
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num,
                        'email' => $request->new_client_email ?? $request->client_email,
                        'phone2' => $request->new_client_phone2 ?? $request->client_phone2,
                        'city' => $request->new_client_city ?? $request->client_city,
                        'beds_num' => $request->new_client_beds_num ?? $request->client_beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->client_family_num,
                        'children' => $request->new_client_children ?? $request->client_children,
                        'job' => $request->new_client_job ?? $request->client_job,
                        'address' => $request->new_client_address ?? $request->client_address,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate,
                    ]);
                    logger()->info('Created new user:', ['user' => $user->toArray()]);
                } else {
                    // Update existing user with new data
                    $user->update([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num,
                        'email' => $request->new_client_email ?? $request->client_email,
                        'phone2' => $request->new_client_phone2 ?? $request->client_phone2,
                        'city' => $request->new_client_city ?? $request->client_city,
                        'beds_num' => $request->new_client_beds_num ?? $request->client_beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->client_family_num,
                        'children' => $request->new_client_children ?? $request->client_children,
                        'job' => $request->new_client_job ?? $request->client_job,
                        'address' => $request->new_client_address ?? $request->client_address,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate,
                        'password' => bcrypt('123456'), // Update password
                    ]);
                    logger()->info('Updated existing user:', ['user' => $user->toArray()]);
                }
                $contractData['user_id'] = $user->id;
                $contractData['marketer_id'] = $marketerId; // تقييد المسوق حسب صلاحية المستخدم الحالي
            }

            logger()->info('Creating contract with data:', $contractData);
            logger()->info('Final user data before contract creation:', [
                'user_id' => $contractData['user_id'],
                'user_national_num' => $user->national_num,
                'user_birthdate' => $user->birthdate,
                'user_address' => $user->address,
                'user_phone2' => $user->phone2,
                'user_city' => $user->city
            ]);

            $contractData['contract_date'] = $request->contract_date;
            $contract = Contract::create($contractData);
            logger()->info('Contract created:', ['contract' => $contract->toArray()]);
            logger()->info('Contract user data after creation:', [
                'user_id' => $contract->user_id,
                'order_id' => $contract->order_id,
                'user_national_num' => $contract->user->national_num,
                'user_birthdate' => $contract->user->birthdate,
                'user_address' => $contract->user->address,
                'user_phone2' => $contract->user->phone2,
                'user_city' => $contract->user->city
            ]);

            // حفظ معلومات العقد
            $contractInfo = [
                'contract_date' => $request->contract_date,
                'msaned_number' => $request->msaned_number,
                'tafwed_number' => $request->tafwed_number,
                'visa_number' => $request->visa_number,
                'visa_milad_date' => $request->visa_milad_date,
                'visa_hijry_date' => $request->visa_hijry_date,
                'contract_duration' => $request->contract_duration,
                'manual_maid_name' => $isManualMaidSummary ? $request->manual_maid_name : null,
                'manual_maid_passport_num' => $isManualMaidSummary ? $request->manual_maid_passport_num : null,
                'manual_maid_nationality_id' => $isManualMaidSummary ? $request->manual_maid_nationality_id : null,
                'manual_maid_office_id' => $isManualMaidSummary ? $request->manual_maid_office_id : null,
            ];

            if ($isManualMaidSummary && $request->hasFile('manual_maid_attachment')) {
                $contractInfo['manual_maid_attachment'] = uploadFile(
                    $request->file('manual_maid_attachment'),
                    'contracts/manual_maid_attachments/'
                );
            }

            logger()->info('Creating contract info:', $contractInfo);

            $contract->info()->create($contractInfo);
            logger()->info('Contract info created');

            // حفظ سجل حالة العقد
            $contract->contract_status_logs()->create(['status' => $contractStatus]);
            logger()->info('Contract status log created');

            // تحديث حالة الطلب إذا كان العقد من طلب
            if ($request->order_id) {
                $order->update(['status' => OrderEnum::Completed->value]);
                logger()->info('Order status updated to completed');
            }

            // تحديث حالة العاملات تلقائياً
            if ($contract->maid_id && !$isNoMaid && !$isMaidSelectionPostponed && !$isManualMaidSummary) {
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    $maid->update([
                        'available' => false,
                        'status' => 'reserved'
                    ]);
                    logger()->info('Maid status updated to reserved', [
                        'maid_id' => $maid->id,
                        'maid_name' => $maid->name,
                        'contract_id' => $contract->id
                    ]);
                }
            }

            // حفظ ملاحظة العقد
            if ($request->filled('contract_note')) {
                ContractNote::create([
                    'contract_id' => $contract->id,
                    'admin_id'    => auth()->id(),
                    'note'        => $request->contract_note,
                ]);
            }

            DB::commit();

            logger()->info('Contract created successfully', ['contract_id' => $contract->id]);

            $successMessage = 'تم إنشاء العقد بنجاح';
            if ($isManualMaidSummary) {
                $successMessage = 'تم إنشاء العقد بنجاح مع حفظ بيانات عاملة مختصرة. يمكنك ربط عاملة فعلية لاحقاً من صفحة العقود.';
            } elseif ($isNoMaid || $isMaidSelectionPostponed) {
                $successMessage = 'تم إنشاء العقد بنجاح. يمكنك إضافة عاملة لاحقاً من صفحة العقود.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'contract' => $contract->load(['user', 'maid', 'info'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            logger()->error('Error creating contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'order_id' => $request->order_id,
                'client_type' => $request->client_type
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show($id, Request $request)
    {
        try {
            $row = Contract::find($id);

            // التحقق من وجود العقد
            if (!$row) {
                logger()->warning('Contract not found', ['id' => $id, 'request' => $request->all()]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من أن العقد من نوع الاستقدام
            // تم إزالة هذا التحقق لأن جميع العقود في هذا Controller هي عقود استقدام
            // if ($row->type !== 'recruitment') {
            //     logger()->warning('Contract is not recruitment type', ['id' => $id, 'type' => $row->type]);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'هذا العقد ليس من نوع الاستقدام'
            //     ], 400);
            // }

            // إذا كان الطلب لتحديث الحالة
            if ($request->has('status') && $request->has('next_status')) {
                $currentStatus = $request->status;
                $nextStatus = $request->next_status;
                $statusDate = $request->date ?? now()->format('Y-m-d');

                // منع الإغلاق اليدوي للعقود في مرحلة الضمان
                if ($nextStatus === ContractStatusEnum::Closed->value) {
                    return response()->json([
                        'success' => false,
                        'message' => 'إغلاق العقد يتم تلقائياً بعد انتهاء فترة الضمان'
                    ], 400);
                }

                // التحقق من الصلاحيات حسب حالة العقد الحالية
                $hasPermission = false;
                $permissionRequired = '';

                switch ($currentStatus) {
                    case ContractStatusEnum::New->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Msaned->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Tafwed->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Taffez->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Arrival->value:
                        // لا يوجد انتقال يدوي من حالة الوصول/الضمان
                        $hasPermission = false;
                        break;
                    default:
                        break;
                }

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية لتغيير حالة العقد من ' . getContractStatus($currentStatus) . ' إلى المرحلة التالية'
                    ], 403);
                }

                // تحديث حالة العقد
                $row->status = $nextStatus;
                $row->save();

                // حفظ سجل حالة العقد
                $row->contract_status_logs()->create([
                    'status' => $nextStatus,
                    'date' => $statusDate
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث حالة العقد بنجاح من ' . getContractStatus($currentStatus) . ' إلى ' . getContractStatus($nextStatus)
                ]);
            }

            // إذا كان الطلب لعرض معلومات العقد فقط
            return response()->json([
                'success' => true,
                'message' => 'تم تحميل معلومات العقد بنجاح',
                'contract' => $row->load(['user', 'maid', 'info'])
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in show contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    public function back($id)
    {
        try {
            // التحقق من صلاحية إرجاع العقد
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإرجاع العقد'
                ], 403);
            }

            $contract = Contract::find($id);
            if (!$contract) {
                logger()->warning('Contract not found in back function', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // منع إرجاع العقود في الإيواء
            if ($contract->status === ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرجاع العقد الذي في الإيواء. يجب حذفه من الإيواء أولاً'
                ], 400);
            }

            // منع إرجاع العقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرجاع العقد المكتمل'
                ], 400);
            }

            $newStatus = contractBackStatus($contract->status);
            if ($newStatus->value === $contract->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرجاع العقد من مرحلة ' . getContractStatus($contract->status)
                ], 400);
            }

            $contract->update(['status' => $newStatus->value]);
            $contract->contract_status_logs()->create(['status' => $newStatus->value]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع العقد بنجاح إلى ' . getContractStatus($newStatus->value)
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in back contract', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرجاع العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // التحقق من صلاحية تعديل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقد');
        }

        $contract = Contract::with(['user', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])->find($id);

        if (!$contract) {
            return redirect()->back()->with('error', 'العقد غير موجود');
        }

        // منع تعديل العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد المكتمل');
        }

        // منع تعديل العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد المرتجع');
        }

        // منع تعديل العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد الذي في الإيواء');
        }

        $countries = Country::all();
        $currentAdmin = Auth::guard('admin')->user();
        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentAdmin);
        $marketers = $canSelectAnyMarketer
            ? \App\Models\auth\Admin::whereNull('deleted_at')->orderBy('name')->get()
            : collect([$currentAdmin])->filter();

        return view('admin.ingaz.RecruitmentContract.edit', compact('contract', 'countries', 'currentAdmin', 'canSelectAnyMarketer', 'marketers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // التحقق من صلاحية تعديل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل العقد'
            ], 403);
        }

        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديد المستخدم الحالي'
            ], 401);
        }

        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentAdmin);
        $marketerRules = ['required', 'exists:admins,id'];
        if (!$canSelectAnyMarketer) {
            $marketerRules[] = 'in:' . $currentAdmin->id;
        }
        $request->validate([
            'marketer_id' => $marketerRules,
        ]);

        $marketerId = $canSelectAnyMarketer
            ? (int) $request->marketer_id
            : (int) $currentAdmin->id;

        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        // منع تحديث حالة العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد المكتمل'
            ], 400);
        }

        // منع تحديث حالة العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد المرتجع'
            ], 400);
        }

        // منع تحديث حالة العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد الذي في الإيواء'
            ], 400);
        }

        try {
            // تحديث بيانات العميل
            if ($contract->user) {
                $contract->user->update([
                    'name' => $request->client_name,
                    'phone' => $request->client_phone,
                    'email' => $request->client_email,
                    'nationality_id' => $request->client_nationality_id,
                    'national_num' => $request->client_national_num,
                    'address' => $request->client_address,
                ]);
            }

            // تحديث بيانات العاملة
            if ($contract->maid) {
                $contract->maid->update([
                    'name' => $request->maid_name,
                    'passport_num' => $request->maid_passport,
                    'salary' => $request->maid_salary,
                ]);
            }

            // تحديث معلومات العقد
            if ($contract->info) {
                $contract->info->update([
                    'contract_date' => $request->contract_date,
                    'msaned_number' => $request->msaned_number,
                    'visa_number' => $request->visa_number,
                    'tafwed_number' => $request->tafwed_number,
                    'visa_milad_date' => $request->visa_milad_date,
                    'visa_hijry_date' => $request->visa_hijry_date,
                    'contract_duration' => $request->contract_duration,
                ]);
            }

            // تحديث حالة العقد
            $contract->update([
                'status' => $request->status,
                'contract_date' => $request->contract_date,
                'marketer_id' => $marketerId,
            ]);

            // تحديث حالة العاملات تلقائياً
            if ($contract->maid_id) {
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    // التحقق من حالة العقد
                    $isActiveContract = !in_array($request->status, ['back', 'closed', 'completed']);

                    if ($isActiveContract) {
                        // العقد نشط، العاملة يجب أن تكون محجوزة
                        $maid->update([
                            'available' => false,
                            'status' => 'reserved'
                        ]);
                    } else {
                        // العقد غير نشط، التحقق من وجود عقود أخرى نشطة
                        $activeContracts = $maid->contracts()->whereNotIn('status', ['back', 'closed', 'completed'])->get();
                        $activeOrders = $maid->orders()->whereIn('status', ['pending', 'approved', 'in_progress'])->get();

                        if ($activeContracts->count() == 0 && $activeOrders->count() == 0) {
                            // لا توجد عقود أو طلبات نشطة، العاملة يمكن أن تكون متاحة
                            $maid->update([
                                'available' => true,
                                'status' => 'available'
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث العقد بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        // التحقق من صلاحية حذف العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف العقد'
            ], 403);
        }

        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        // منع حذف العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد المكتمل'
            ], 400);
        }

        // منع حذف العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد المرتجع'
            ], 400);
        }

        // منع حذف العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد الذي في الإيواء'
            ], 400);
        }

        // التحقق من وجود عاملة في العقد
        if ($contract->maid_id) {
            $maidStatus = $request->input('maid_status', 'available');

            // تحديث حالة العاملة
            $maid = \App\Models\ingaz\Maid::find($contract->maid_id);
            if ($maid) {
                if ($maidStatus === 'back_out') {
                    $maid->update([
                        'status' => 'back_out',
                        'available' => false
                    ]);
                } else {
                    $maid->update([
                        'status' => 'available',
                        'available' => true
                    ]);
                }
            }
        }

        $contract->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقد بنجاح'
        ]);
    }

    public function getMaidByNationality($nationalityId)
    {
        $maids = Maid::where('nationality_id', $nationalityId)->get();
        return response()->json($maids->toArray());
    }

    public function getClientDetails($id)
    {
        $client = User::with('nationality')->find($id);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return response()->json($client);
    }

    public function exportPdf()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة عقود الاستقدام</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة عقود الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>تاريخ العقد</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ النهاية</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($contracts as $contract) {
            $html .= '<tr>
                <td>' . $contract->id . '</td>
                <td>' . ($contract->user ? $contract->user->name : '') . '</td>
                <td>' . ($contract->user ? getPhoneWithTag($contract->user->phone_code . $contract->user->phone) : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->name : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->passport_num : '') . '</td>
                <td>' . ($contract->info ? $contract->info->contract_date : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_from : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_to : '') . '</td>
                <td>' . getEnumValue('contract_statuses', $contract->status) . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="contracts.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العقد',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'تاريخ العقد',
            'تاريخ البداية',
            'تاريخ النهاية',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($contracts as $contract) {
            $sheet->setCellValue('A' . $row, $contract->id);
            $sheet->setCellValue('B' . $row, $contract->user ? $contract->user->name : '');
            $sheet->setCellValue('C' . $row, $contract->user ? $contract->user->phone_code . $contract->user->phone : '');
            $sheet->setCellValue('D' . $row, $contract->maid ? $contract->maid->name : '');
            $sheet->setCellValue('E' . $row, $contract->maid ? $contract->maid->passport_num : '');
            $sheet->setCellValue('F' . $row, $contract->info ? $contract->info->contract_date : '');
            $sheet->setCellValue('G' . $row, $contract->info ? $contract->info->ingaz_from : '');
            $sheet->setCellValue('H' . $row, $contract->info ? $contract->info->ingaz_to : '');
            $sheet->setCellValue('I' . $row, getEnumValue('contract_statuses', $contract->status));
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contracts.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'contracts.xlsx', $headers)->deleteFileAfterSend(true);
    }

    public function completeTrip(Request $request, $tripId = null)
    {
        try {
            // التحقق من صلاحية إكمال الرحلة
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإكمال الرحلة'
                ], 403);
            }

            $tripId = $tripId ?: $request->input('trip_id');
            if (!$tripId) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد الرحلة المطلوبة'
                ], 422);
            }

            $travelHistory = TravelHistory::find($tripId);
            if (!$travelHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرحلة غير موجودة'
                ], 404);
            }

            // البحث عن العقد المرتبط بالرحلة
            $contract = Contract::find($travelHistory->order_id);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد المرتبط بالرحلة غير موجود'
                ], 404);
            }

            DB::beginTransaction();

            try {
                if (!$travelHistory->arrival_date) {
                    $travelHistory->arrival_date = now()->toDateString();
                }
                if (!$travelHistory->arrival_time) {
                    $travelHistory->arrival_time = now()->format('H:i:s');
                }

                // تحديث حالة الرحلة إلى مكتملة
                $travelHistory->status = 'completed';
                $travelHistory->save();

                // بعد إكمال الرحلة ينتقل العقد إلى الوصول، ثم يحدد الضمان الحالة النهائية
                $contract->update(['status' => ContractStatusEnum::Arrival->value]);

                // حساب حالة الضمان تلقائياً
                $contract->calculateWarrantyStatus();
                $contract->refresh();

                $statusLogNote = $contract->status === ContractStatusEnum::Completed->value
                    ? 'تم إكمال الرحلة وانتهاء فترة الضمان، لذلك تم تحديث العقد إلى مكتمل'
                    : 'تم إكمال الرحلة ونقل العقد إلى مرحلة الوصول وتفعيل الضمان';

                // إنشاء سجل حالة العقد
                $contract->contract_status_logs()->create([
                    'status' => $contract->status,
                    'date' => now(),
                    'notes' => $statusLogNote
                ]);

                DB::commit();

                logger()->info('Trip completed and contract status updated', [
                    'trip_id' => $tripId,
                    'contract_id' => $contract->id,
                    'warranty_status' => $contract->warranty_status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $contract->status === ContractStatusEnum::Completed->value
                        ? 'تم إكمال الرحلة وتحديث حالة العقد إلى مكتمل بنجاح'
                        : 'تم إكمال الرحلة وتحديث حالة العقد إلى مرحلة الوصول وتفعيل الضمان بنجاح'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            logger()->error('Error in completeTrip', [
                'tripId' => $tripId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكمال الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addTravel(Request $request)
    {
        try {
            // التحقق من صلاحية إضافة رحلة
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإضافة رحلة'
                ], 403);
            }

            $contractId = $request->contract_id;
            $contract = Contract::find($contractId);

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من عدم وجود رحلة نشطة للعقد
            $existingTravel = TravelHistory::where('order_id', $contractId)->first();
            if ($existingTravel) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد رحلة نشطة بالفعل لهذا العقد'
                ], 400);
            }

            // إنشاء رحلة جديدة
            TravelHistory::create([
                'order_id' => $contractId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الرحلة بنجاح'
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in addTravel', [
                'contractId' => $request->contract_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'row_id' => 'required|integer|exists:contracts,id',
                'next_status' => 'required|string',
                'date' => 'nullable|date',
            ]);

            $rowId = (int) $request->row_id;
            $requestedNextStatus = $request->next_status;
            $date = $request->date;
            $sendSms = filter_var($request->input('send_sms'), FILTER_VALIDATE_BOOLEAN);
            $contract = Contract::with('user')->findOrFail($rowId);
            $transitionContext = $this->resolveContractTransitionContext($contract);

            if (!$transitionContext['can_update']) {
                return response()->json([
                    'success' => false,
                    'message' => $transitionContext['reason']
                ], $transitionContext['http_status']);
            }

            if ($requestedNextStatus !== $transitionContext['next_status']) {
                return response()->json([
                    'success' => false,
                    'message' => 'المرحلة التالية المحددة لا تطابق المرحلة الحالية للعقد'
                ], 422);
            }

            $updateResult = $this->updateContractStatusRecord($contract, $transitionContext['next_status'], $date);

            $smsResult = false;
            if ($sendSms) {
                $smsResult = $this->sendContractStatusSMS($contract->fresh('user'), $transitionContext['next_status']);
            }

            $message = 'تم تحديث الحالة بنجاح من ' . $updateResult['current_status_text'] . ' إلى ' . $updateResult['next_status_text'];

            if ($sendSms) {
                if ($smsResult) {
                    $message .= ' وتم إرسال رسالة نصية للعميل';
                } else {
                    $message .= ' لكن فشل في إرسال الرسالة النصية للعميل';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'sms_sent' => $smsResult
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in updateStatus', [
                'rowId' => $request->row_id,
                'nextStatus' => $request->next_status,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'row_ids' => 'required|array|min:1',
                'row_ids.*' => 'integer|distinct',
                'date' => 'nullable|date',
            ]);

            $rowIds = collect($request->input('row_ids', []))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($rowIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي عقود للتحديث'
                ], 422);
            }

            $sendSms = filter_var($request->input('send_sms'), FILTER_VALIDATE_BOOLEAN);
            $date = $request->date;
            $contracts = Contract::with('user')
                ->whereIn('id', $rowIds)
                ->get()
                ->keyBy('id');

            $updatedContracts = [];
            $skippedContracts = [];
            $smsSentCount = 0;

            foreach ($rowIds as $rowId) {
                /** @var \App\Models\Contract|null $contract */
                $contract = $contracts->get($rowId);

                if (!$contract) {
                    $skippedContracts[] = [
                        'id' => $rowId,
                        'message' => 'العقد غير موجود'
                    ];
                    continue;
                }

                $transitionContext = $this->resolveContractTransitionContext($contract);

                if (!$transitionContext['can_update']) {
                    $skippedContracts[] = [
                        'id' => $contract->id,
                        'message' => $transitionContext['reason']
                    ];
                    continue;
                }

                try {
                    $updateResult = $this->updateContractStatusRecord($contract, $transitionContext['next_status'], $date);

                    $smsResult = false;
                    if ($sendSms) {
                        $smsResult = $this->sendContractStatusSMS($contract->fresh('user'), $transitionContext['next_status']);
                        if ($smsResult) {
                            $smsSentCount++;
                        }
                    }

                    $updatedContracts[] = [
                        'id' => $contract->id,
                        'from' => $updateResult['current_status_text'],
                        'to' => $updateResult['next_status_text'],
                        'sms_sent' => $smsResult,
                    ];
                } catch (\Throwable $throwable) {
                    logger()->error('Error in bulk contract status update item', [
                        'contract_id' => $contract->id,
                        'message' => $throwable->getMessage(),
                        'trace' => $throwable->getTraceAsString()
                    ]);

                    $skippedContracts[] = [
                        'id' => $contract->id,
                        'message' => 'تعذر تحديث العقد حالياً'
                    ];
                }
            }

            $updatedCount = count($updatedContracts);
            $skippedCount = count($skippedContracts);

            $message = $updatedCount > 0
                ? 'تم تحديث ' . $updatedCount . ' عقد بنجاح'
                : 'لم يتم تحديث أي عقد';

            if ($skippedCount > 0) {
                $message .= ' وتعذر تحديث ' . $skippedCount . ' عقد';
            }

            if ($sendSms) {
                $message .= $smsSentCount > 0
                    ? ' وتم إرسال ' . $smsSentCount . ' إشعار للعميل'
                    : ' ولم يتم إرسال أي إشعار للعميل';
            }

            return response()->json([
                'success' => $updatedCount > 0,
                'message' => $message,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'sms_sent_count' => $smsSentCount,
                'updated_contracts' => $updatedContracts,
                'skipped_contracts' => $skippedContracts,
            ], $updatedCount > 0 ? 200 : 422);
        } catch (\Exception $e) {
            logger()->error('Error in bulkUpdateStatus', [
                'rowIds' => $request->input('row_ids', []),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث الجماعي: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب العاملات المتاحة
     */
    public function getAvailableMaids(Request $request)
    {
        logger()->info('getAvailableMaids called', ['request' => $request->all()]);

        try {
            $query = Maid::with(['nationality'])
                ->where('available', true)
                ->where('status', 'available') // التأكد من أن الحالة متاحة
                ->whereDoesntHave('contracts', function ($q) {
                    $q->whereIn('status', [
                        'new',
                        'msaned',
                        'tafwed',
                        'taffez',
                        'tazker',
                        'arrival'
                    ]);
                })
                ->whereDoesntHave('orders', function ($q) {
                    $q->whereIn('status', [
                        'new',
                        'under_process',
                        'contracted'
                    ]);
                });

            logger()->info('Query built', ['sql' => $query->toSql()]);

            // فلترة حسب الجنسية
            if ($request->has('nationality_id') && $request->nationality_id) {
                $query->where('nationality_id', $request->nationality_id);
            }

            // فلترة حسب الديانة
            if ($request->has('religion_id') && $request->religion_id) {
                $query->where('religion_id', $request->religion_id);
            }

            // البحث بالاسم أو رقم الجواز
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('passport_num', 'LIKE', "%{$search}%")
                        ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            // هذا الـ endpoint يُستخدم داخل مودال تغيير العاملة ويجب أن يعرض كل النتائج المتاحة
            $availableMaids = $query->latest()->get();

            // Log debug info
            logger()->info('نتائج البحث عن العاملات:', [
                'count' => $availableMaids->count(),
                'search' => $request->search,
                'nationality_id' => $request->nationality_id,
                'religion_id' => $request->religion_id,
                'job_title_id' => $request->job_title_id,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // إضافة رسالة تحذير إذا لم توجد عاملات متاحة
            if ($availableMaids->count() === 0) {
                $warningMessage = 'لا توجد عاملات متاحة حالياً';

                if ($request->filled('search') || $request->filled('nationality_id') || $request->filled('religion_id') || $request->filled('job_title_id')) {
                    $warningMessage .= ' - جرب تغيير الفلاتر أو إعادة تعيين البحث';
                } else {
                    $warningMessage .= ' - جميع العاملات إما محجوزة أو غير متاحة';
                }

                session()->flash('warning', $warningMessage);
            }

            // تحويل البيانات للعرض
            $maidsData = $availableMaids->map(function ($maid) {
                return [
                    'id' => $maid->id,
                    'name' => $maid->name ?? 'غير محدد',
                    'passport_num' => $maid->passport_num ?? 'غير محدد',
                    'nationality' => $maid->nationality->sub_title ?? 'غير محدد'
                ];
            });

            logger()->info('Available maids loaded', [
                'count' => $maidsData->count(),
                'maids_preview' => $maidsData->take(10)->toArray()
            ]);

            return response()->json([
                'success' => true,
                'maids' => $maidsData
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in getAvailableMaids', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العاملات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب خيارات الفلترة
     */
    public function getFilterOptions()
    {
        // استخدام cache لتسريع التحميل
        return \Cache::remember('recruitment_contract_filter_options', 300, function () { // 5 دقائق
            // جلب الجنسيات مع الترجمات وتحويلها للشكل المطلوب
            $nationalities = \App\Models\ingaz\Nationality::with('translations')->get()->map(function ($nationality) {
                $translation = $nationality->translations->where('locale', app()->getLocale())->first();
                if (!$translation) {
                    $translation = $nationality->translations->first(); // fallback to first translation
                }
                return (object)[
                    'id' => $nationality->id,
                    'sub_title' => $translation ? $translation->sub_title : $nationality->sub_title
                ];
            });

            // جلب الديانات مع الترجمات وتحويلها للشكل المطلوب
            $religions = \App\Models\ingaz\Religion::with('translations')->get()->map(function ($religion) {
                $translation = $religion->translations->where('locale', app()->getLocale())->first();
                if (!$translation) {
                    $translation = $religion->translations->first(); // fallback to first translation
                }
                return (object)[
                    'id' => $religion->id,
                    'title' => $translation ? $translation->title : $religion->title
                ];
            });

            // جلب المكاتب الخارجية مع الترجمات وتحويلها للشكل المطلوب
            $offices = \App\Models\RecruitmentOffices::with('translations')->get()->map(function ($office) {
                $translation = $office->translations->where('locale', app()->getLocale())->first();
                if (!$translation) {
                    $translation = $office->translations->first(); // fallback to first translation
                }
                return (object)[
                    'id' => $office->id,
                    'title' => $translation ? $translation->title : $office->title
                ];
            });

            return [
                'nationalities' => $nationalities,
                'religions' => $religions,
                'offices' => $offices,
                'marketers' => \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->orderBy('name')->get(),
            ];
        });
    }

    /**
     * إضافة عاملة للعقد
     */
    public function addMaidToContract(Request $request, $contractId)
    {
        try {
            $contract = Contract::findOrFail($contractId);

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة عاملة لهذا العقد'
                ], 400);
            }

            // التحقق من صحة البيانات
            $request->validate([
                'maid_id' => 'required|exists:maids,id'
            ]);

            // التحقق من أن العاملة متاحة
            $maid = Maid::find($request->maid_id);
            if (!$maid || !$maid->available) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير متاحة'
                ], 400);
            }

            // تحديث العقد
            $contract->update([
                'maid_id' => $request->maid_id,
                'status' => ContractStatusEnum::New->value
            ]);

            // تحديث حالة العاملات تلقائياً
            if ($maid) {
                $maid->update([
                    'available' => false,
                    'status' => 'reserved'
                ]);
            }

            // إضافة سجل حالة العقد
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::New->value,
                'date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العاملة للعقد بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العاملة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * صفحة اختيار عاملة للعقد
     */
    public function selectMaidForContract($contractId, Request $request)
    {
        try {
            $contract = Contract::with(['user', 'info', 'requiredMaidNationality'])->find($contractId);

            // التحقق من وجود العقد
            if (!$contract) {
                // البحث عن عقود بانتظار اختيار العاملة
                $pendingContracts = Contract::where('status', ContractStatusEnum::PendingMaidSelection->value)->pluck('id')->toArray();

                $errorMessage = 'العقد غير موجود';
                if (!empty($pendingContracts)) {
                    $errorMessage .= ' - العقود المتاحة بانتظار اختيار العاملة: ' . implode(', ', $pendingContracts);
                }

                return redirect()->route('RecruitmentContract.index')
                    ->with('error', $errorMessage);
            }

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return redirect()->route('RecruitmentContract.index')
                    ->with('error', 'لا يمكن إضافة عاملة لهذا العقد - الحالة الحالية: ' . $contract->status);
            }

            // جلب العاملات المتاحة مع الفلاتر
            $query = Maid::with(['nationality', 'religion', 'jobTitles'])
                ->where('available', true)
                ->where('status', 'available')
                ->where('is_show', true)
                ->where('available_at', '<=', now()->toDateString())
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->whereDoesntHave('contracts', function ($q) {
                    $q->whereIn('status', ['active', 'pending']);
                })
                ->whereDoesntHave('orders', function ($q) {
                    $q->whereIn('status', ['pending', 'approved', 'in_progress']);
                });

            // فلترة تلقائية بناءً على الجنسية المفضلة في العقد
            if ($contract->required_maid_nationality_id && !$request->has('show_all_nationalities')) {
                $query->where('nationality_id', $contract->required_maid_nationality_id);

                // إضافة رسالة إعلامية
                $preferredNationality = \App\Models\ingaz\Nationality::find($contract->required_maid_nationality_id);
                if ($preferredNationality) {
                    session()->flash('info', 'تم فلترة العاملات حسب الجنسية المفضلة: ' . $preferredNationality->sub_title . ' - يمكنك النقر على "عرض جميع الجنسيات" لإلغاء الفلترة');
                }
            } elseif ($request->has('show_all_nationalities') && $contract->required_maid_nationality_id) {
                // رسالة إعلامية عند عرض جميع الجنسيات
                $preferredNationality = \App\Models\ingaz\Nationality::find($contract->required_maid_nationality_id);
                if ($preferredNationality) {
                    session()->flash('info', 'تم عرض جميع الجنسيات - الجنسية المفضلة: ' . $preferredNationality->sub_title);
                }
            }

            // تطبيق الفلاتر الإضافية
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('passport_num', 'LIKE', "%{$search}%")
                        ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('nationality_id')) {
                $query->where('nationality_id', $request->nationality_id);
            }

            if ($request->filled('religion_id')) {
                $query->where('religion_id', $request->religion_id);
            }

            if ($request->filled('job_title_id')) {
                $query->whereHas('jobTitles', function ($q) use ($request) {
                    $q->where('job_title_id', $request->job_title_id);
                });
            }

            $availableMaids = $query->latest()->paginate(20)->withQueryString();

            // Log debug info
            logger()->info('نتائج البحث عن العاملات:', [
                'count' => $availableMaids->count(),
                'search' => $request->search,
                'nationality_id' => $request->nationality_id,
                'religion_id' => $request->religion_id,
                'job_title_id' => $request->job_title_id,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // إضافة رسالة تحذير إذا لم توجد عاملات متاحة
            if ($availableMaids->count() === 0) {
                $warningMessage = 'لا توجد عاملات متاحة حالياً';

                if ($request->filled('search') || $request->filled('nationality_id') || $request->filled('religion_id') || $request->filled('job_title_id')) {
                    $warningMessage .= ' - جرب تغيير الفلاتر أو إعادة تعيين البحث';
                } else {
                    $warningMessage .= ' - جميع العاملات إما محجوزة أو غير متاحة';
                }

                session()->flash('warning', $warningMessage);
            }

            // جلب قوائم الفلترة
            $nationalities = \App\Models\ingaz\Nationality::all();
            $religions = \App\Models\ingaz\Religion::all();
            $jobTitles = \App\Models\ingaz\JobTitle::all();



            return view('admin.ingaz.RecruitmentContract.select_maid', compact(
                'contract',
                'availableMaids',
                'nationalities',
                'religions',
                'jobTitles'
            ));
        } catch (\Exception $e) {
            logger()->error('Error in selectMaidForContract', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'contractId' => $contractId,
                'request' => $request->all()
            ]);

            return redirect()->route('RecruitmentContract.index')
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function statistics(Request $request)
    {
        try {
            $baseQuery = Contract::query()
                ->whereIn('type', ['recruitment', 'maid']);

            // تطبيق نفس فلاتر البحث (بدون الحالة وحالة الضمان) للحصول على إحصائيات قابلة للتنقل
            $this->applyContractFilters($baseQuery, $request, true, true);

            $today = \Carbon\Carbon::now()->toDateString();
            $currentStatuses = [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
            ];
            $completedStageStatuses = [
                ContractStatusEnum::Arrival->value,
                ContractStatusEnum::Completed->value,
            ];

            $counts = [
                'total' => (clone $baseQuery)->count(),
                'current_total' => (clone $baseQuery)->whereIn('status', $currentStatuses)->count(),
                'completed_total' => (clone $baseQuery)->whereIn('status', $completedStageStatuses)->count(),
                'stage_back' => (clone $baseQuery)->where('status', ContractStatusEnum::Back->value)->count(),
                'stage_pending_maid_selection' => (clone $baseQuery)->where('status', ContractStatusEnum::PendingMaidSelection->value)->count(),
                'stage_new' => (clone $baseQuery)->where('status', ContractStatusEnum::New->value)->count(),
                'stage_msaned' => (clone $baseQuery)->where('status', ContractStatusEnum::Msaned->value)->count(),
                'stage_tafwed' => (clone $baseQuery)->where('status', ContractStatusEnum::Tafwed->value)->count(),
                'stage_taffez' => (clone $baseQuery)->where('status', ContractStatusEnum::Taffez->value)->count(),
                'stage_tazker' => (clone $baseQuery)->where('status', ContractStatusEnum::Tazker->value)->count(),
                'stage_arrival' => (clone $baseQuery)->where('status', ContractStatusEnum::Arrival->value)->count(),
                'stage_completed' => (clone $baseQuery)->where('status', ContractStatusEnum::Completed->value)->count(),
                'warranty_active' => (clone $baseQuery)->whereIn('status', $completedStageStatuses)
                    ->whereNotNull('warranty_end_date')
                    ->whereDate('warranty_end_date', '>=', $today)
                    ->count(),
                'warranty_expired' => (clone $baseQuery)->where('status', ContractStatusEnum::Completed->value)
                    ->whereNotNull('warranty_end_date')
                    ->whereDate('warranty_end_date', '<', $today)
                    ->count(),
                'warranty_not_applicable' => (clone $baseQuery)->where('status', ContractStatusEnum::Completed->value)
                    ->whereNull('warranty_end_date')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $counts
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in contract statistics:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    private function applyContractFilters($query, Request $request, bool $ignoreStatus = false, bool $ignoreWarranty = false): void
    {
        // فلتر الحالة - استخدام query parameter فقط
        if (!$ignoreStatus) {
            $statusFilter = $request->get('status');
            if ($statusFilter !== null && $statusFilter !== '') {
                if ($statusFilter == 'current') {
                    $query->whereIn('status', [
                        ContractStatusEnum::PendingMaidSelection,
                        ContractStatusEnum::New,
                        ContractStatusEnum::Msaned,
                        ContractStatusEnum::Tafwed,
                        ContractStatusEnum::Taffez,
                        ContractStatusEnum::Tazker
                    ]);
                } elseif ($statusFilter == 'completed_stage') {
                    $query->whereIn('status', [
                        ContractStatusEnum::Arrival->value,
                        ContractStatusEnum::Completed->value,
                    ]);
                } elseif ($statusFilter == 'completed') {
                    $query->where('status', ContractStatusEnum::Completed->value);
                } else {
                    $query->where('status', $statusFilter);
                }
            }
        }

        // فلتر حالة الضمان
        if (!$ignoreWarranty && $request->filled('warranty_status')) {
            $warrantyStatuses = array_filter(array_map('trim', explode(',', $request->warranty_status)));
            if (!empty($warrantyStatuses)) {
                $today = \Carbon\Carbon::now()->toDateString();
                $query->where(function ($q) use ($warrantyStatuses, $today) {
                    foreach ($warrantyStatuses as $status) {
                        switch ($status) {
                            case 'active':
                                $q->orWhere(function ($sub) use ($today) {
                                    $sub->whereNotNull('warranty_end_date')
                                        ->whereDate('warranty_end_date', '>=', $today);
                                });
                                break;
                            case 'expired':
                                $q->orWhere(function ($sub) use ($today) {
                                    $sub->whereNotNull('warranty_end_date')
                                        ->whereDate('warranty_end_date', '<', $today);
                                });
                                break;
                            case 'not_applicable':
                                $q->orWhereNull('warranty_end_date');
                                break;
                            default:
                                $q->orWhere('warranty_status', $status);
                                break;
                        }
                    }
                });
            }
        }

        // فلتر التاريخ من
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // فلتر التاريخ إلى
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر رقم جوال العميل
        if ($request->filled('client_phone')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->client_phone . '%');
            });
        }

        // فلتر اسم العميل
        if ($request->filled('client_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        // فلتر رقم جواز العاملة
        if ($request->filled('maid_passport')) {
            $query->whereHas('maid', function ($q) use ($request) {
                $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
            });
        }

        // فلتر اسم العاملة
        if ($request->filled('maid_name')) {
            $query->whereHas('maid', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->maid_name . '%');
            });
        }

        if ($request->filled('request_type')) {
            $query->whereHas('maid', function ($q) use ($request) {
                $q->ofRequestType($request->request_type);
            });
        }

        // فلتر رقم العقد
        if ($request->filled('contract_number')) {
            $query->where('id', 'like', '%' . $request->contract_number . '%');
        }

        // فلتر جنسية العاملة
        if ($request->filled('nationality')) {
            $query->whereHas('maid.nationality.translations', function ($q) use ($request) {
                $q->where('sub_title', $request->nationality);
            });
        }

        // فلتر ديانة العاملة
        if ($request->filled('religion')) {
            $query->whereHas('maid.religion.translations', function ($q) use ($request) {
                $q->where('title', $request->religion);
            });
        }

        // فلتر المكتب الخارجي
        if ($request->filled('office_id')) {
            $query->whereHas('maid', function ($q) use ($request) {
                $q->where('office_id', $request->office_id);
            });
        }

        // فلتر مدينة العميل
        if ($request->filled('city')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('city', 'like', '%' . $request->city . '%');
            });
        }

        // فلتر البحث الشامل
        if ($request->filled('global_search')) {
            $this->applyGlobalSearchFilter($query, $request->global_search);
        }
    }

    private function applyGlobalSearchFilter($query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $likeSearch = '%' . $search . '%';

        $query->where(function ($q) use ($likeSearch) {
            // بيانات العقد الأساسية
            $q->where('id', 'like', $likeSearch)
                ->orWhere('order_id', 'like', $likeSearch)
                ->orWhere('status', 'like', $likeSearch);

            // بيانات العميل
            $q->orWhereHas('user', function ($userQuery) use ($likeSearch) {
                $userQuery->where('name', 'like', $likeSearch)
                    ->orWhere('phone_code', 'like', $likeSearch)
                    ->orWhere('phone', 'like', $likeSearch)
                    ->orWhereRaw("CONCAT(COALESCE(phone_code,''), COALESCE(phone,'')) LIKE ?", [$likeSearch])
                    ->orWhere('phone2', 'like', $likeSearch)
                    ->orWhere('national_num', 'like', $likeSearch)
                    ->orWhere('email', 'like', $likeSearch)
                    ->orWhere('city', 'like', $likeSearch)
                    ->orWhere('address', 'like', $likeSearch)
                    ->orWhere('birthdate', 'like', $likeSearch)
                    ->orWhere('job', 'like', $likeSearch);
            });

            // بيانات العاملة
            $q->orWhereHas('maid', function ($maidQuery) use ($likeSearch) {
                $maidQuery->where('name', 'like', $likeSearch)
                    ->orWhere('passport_num', 'like', $likeSearch);
            });

            // بيانات العقد التفصيلية (رقم التأشيرة، رقم مساند، رقم التفويض...)
            $q->orWhereHas('info', function ($infoQuery) use ($likeSearch) {
                $infoQuery->where('visa_number', 'like', $likeSearch)
                    ->orWhere('msaned_number', 'like', $likeSearch)
                    ->orWhere('tafwed_number', 'like', $likeSearch)
                    ->orWhere('visa_hijry_date', 'like', $likeSearch)
                    ->orWhere('visa_milad_date', 'like', $likeSearch)
                    ->orWhere('contract_date', 'like', $likeSearch)
                    ->orWhere('arrival_date', 'like', $likeSearch)
                    ->orWhere('ingaz_from', 'like', $likeSearch)
                    ->orWhere('ingaz_to', 'like', $likeSearch)
                    ->orWhere('contract_value', 'like', $likeSearch)
                    ->orWhere('contract_duration', 'like', $likeSearch)
                    ->orWhere('monthly_cost', 'like', $likeSearch)
                    ->orWhere('salary_after_sponsorship', 'like', $likeSearch)
                    ->orWhere('sponsorship_price', 'like', $likeSearch)
                    ->orWhere('working_hours_num', 'like', $likeSearch);
            });

            // بيانات الطلب المرتبط بالعقد
            $q->orWhereHas('order', function ($orderQuery) use ($likeSearch) {
                $orderQuery->where('id', 'like', $likeSearch)
                    ->orWhere('order_code', 'like', $likeSearch);
            });
        });
    }

    /**
     * تغيير العاملة في العقد
     */
    public function changeMaid(Request $request, $contractId)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتعديل العقود'
                ], 403);
            }

            // التحقق من وجود العقد
            $contract = Contract::with(['maid', 'user', 'order'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استبدال العاملة في العقد المكتمل'
                ], 400);
            }

            // منع استبدال العاملة للعقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استبدال العاملة في العقد المرتجع'
                ], 400);
            }

            // التحقق من أن العقد من النوع الصحيح
            if (!in_array($contract->type, ['recruitment', 'maid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تغيير العاملة في هذا النوع من العقود'
                ], 400);
            }

            // التحقق من البيانات المطلوبة
            $request->validate([
                'new_maid_id' => 'required|exists:maids,id',
                'old_maid_status' => 'required|in:available,backout',
                'reason' => 'nullable|string|max:500'
            ]);

            $newMaidId = $request->new_maid_id;
            $oldMaidStatus = $request->old_maid_status;
            $reason = $request->reason;

            // التحقق من أن العاملة الجديدة متاحة
            $newMaid = Maid::findOrFail($newMaidId);

            // التحقق من أن العاملة الجديدة ليس لها عقود نشطة
            $hasActiveContracts = Contract::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    ContractStatusEnum::New->value,
                    ContractStatusEnum::Msaned->value,
                    ContractStatusEnum::Tafwed->value,
                    ContractStatusEnum::Taffez->value,
                    ContractStatusEnum::Tazker->value,
                    ContractStatusEnum::Arrival->value
                ])
                ->exists();

            if ($hasActiveContracts) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة لها عقود نشطة ولا يمكن استخدامها'
                ], 400);
            }

            // التحقق من أن العاملة الجديدة ليس لها طلبات نشطة
            $hasActiveOrders = Order::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    OrderEnum::New->value,
                    OrderEnum::UnderProcess->value,
                    OrderEnum::Contracted->value,
                    OrderEnum::ContractedWithoutContract->value
                ])
                ->exists();

            if ($hasActiveOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة لها طلبات نشطة ولا يمكن استخدامها'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // حفظ العاملة القديمة
                $oldMaid = $contract->maid;
                $oldMaidId = $oldMaid->id;

                logger()->info('Changing maid in contract', [
                    'contract_id' => $contractId,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_status' => $oldMaidStatus,
                    'old_maid_current_status' => $oldMaid->status,
                    'old_maid_current_available' => $oldMaid->available
                ]);

                // تحديث العقد بالعاملة الجديدة
                $contract->update([
                    'maid_id' => $newMaidId
                ]);

                // مزامنة العاملة الجديدة أيضاً مع الطلب المرتبط بالعقد إن وجد
                if ($contract->order) {
                    $contract->order->update([
                        'maid_id' => $newMaidId
                    ]);
                }

                // تحديث حالة العاملة الجديدة إلى محجوزة
                $newMaid->update([
                    'status' => 'reserved',
                    'available' => false
                ]);

                // إعادة تحميل البيانات للتأكد من التحديث
                $newMaid->refresh();

                logger()->info('New maid status updated', [
                    'new_maid_id' => $newMaidId,
                    'new_status' => $newMaid->status,
                    'new_available' => $newMaid->available
                ]);

                // تحديد الحالة النهائية للعاملة القديمة بعد نقل العقد/الطلب عنها
                if ($oldMaidStatus === 'backout') {
                    $oldMaid->update([
                        'status' => 'backout',
                        'available' => false
                    ]);

                    \Artisan::call('maids:add-to-unavailable', [
                        'maid-id' => $oldMaidId,
                        '--contract-id' => $contractId,
                        '--reason' => $reason ?? 'تم تغيير العاملة من عقد',
                        '--admin-id' => Auth::guard('admin')->id()
                    ]);
                } else {
                    $oldMaidActiveContracts = Contract::where('maid_id', $oldMaidId)
                        ->whereIn('status', [
                            ContractStatusEnum::New->value,
                            ContractStatusEnum::Msaned->value,
                            ContractStatusEnum::Tafwed->value,
                            ContractStatusEnum::Taffez->value,
                            ContractStatusEnum::Tazker->value,
                            ContractStatusEnum::Arrival->value
                        ])
                        ->exists();

                    $oldMaidActiveOrders = Order::where('maid_id', $oldMaidId)
                        ->whereIn('status', [
                            OrderEnum::New->value,
                            OrderEnum::UnderProcess->value,
                            OrderEnum::Contracted->value,
                            OrderEnum::ContractedWithoutContract->value
                        ])
                        ->exists();

                    $oldMaid->update([
                        'status' => ($oldMaidActiveContracts || $oldMaidActiveOrders) ? 'reserved' : 'available',
                        'available' => !($oldMaidActiveContracts || $oldMaidActiveOrders)
                    ]);
                }

                $oldMaid->refresh();

                logger()->info('Old maid status updated', [
                    'old_maid_id' => $oldMaidId,
                    'new_status' => $oldMaid->status,
                    'new_available' => $oldMaid->available
                ]);

                // تسجيل العملية في سجل التغييرات
                $adminName = Auth::guard('admin')->user()?->name ?? 'غير محدد';
                $statusRequestText = $oldMaidStatus === 'available' ? 'متاحة' : 'باك اوت';
                $statusAppliedText = match ($oldMaid->status) {
                    'available' => 'متاحة',
                    'reserved' => 'محجوزة',
                    'backout' => 'باك اوت',
                    default => $oldMaid->status ?? 'غير محدد',
                };

                $changeNotes = [
                    'تم تغيير العاملة بواسطة: ' . $adminName,
                    'العاملة السابقة: ' . ($oldMaid->name ?? 'غير محدد') . ' (#' . $oldMaid->id . ')',
                    'العاملة الجديدة: ' . ($newMaid->name ?? 'غير محدد') . ' (#' . $newMaid->id . ')',
                    'الحالة المطلوبة للعاملة السابقة: ' . $statusRequestText,
                    'الحالة النهائية للعاملة السابقة: ' . $statusAppliedText,
                ];

                if ($reason) {
                    $changeNotes[] = 'السبب: ' . $reason;
                }

                $contract->contract_status_logs()->create([
                    'status' => $contract->status,
                    'date' => now()->toDateString(),
                    'notes' => implode(PHP_EOL, $changeNotes),
                ]);

                logger()->info('Maid changed in contract', [
                    'contract_id' => $contractId,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_status' => $oldMaidStatus,
                    'reason' => $reason,
                    'changed_by' => Auth::guard('admin')->id()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم تغيير العاملة بنجاح',
                    'data' => [
                        'contract_id' => $contractId,
                        'old_maid' => [
                            'id' => $oldMaid->id,
                            'name' => $oldMaid->name,
                            'status' => $oldMaid->status,
                            'available' => $oldMaid->available
                        ],
                        'new_maid' => [
                            'id' => $newMaid->id,
                            'name' => $newMaid->name,
                            'status' => $newMaid->status,
                            'available' => $newMaid->available
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            logger()->error('Error changing maid in contract:', [
                'contract_id' => $contractId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير العاملة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صفحة تغيير العاملة
     */
    public function showChangeMaid($contractId)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE)) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقود');
            }

            $contract = Contract::with(['maid', 'user'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return redirect()->back()->with('error', 'لا يمكن استبدال العاملة في العقد المكتمل');
            }

            // منع استبدال العاملة للعقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return redirect()->back()->with('error', 'لا يمكن استبدال العاملة في العقد المرتجع');
            }

            $availableMaids = Maid::where('available', true)
                ->whereDoesntHave('contracts', function ($q) {
                    $q->whereIn('status', [
                        ContractStatusEnum::New->value,
                        ContractStatusEnum::Msaned->value,
                        ContractStatusEnum::Tafwed->value,
                        ContractStatusEnum::Taffez->value,
                        ContractStatusEnum::Tazker->value,
                        ContractStatusEnum::Arrival->value
                    ]);
                })
                ->whereDoesntHave('orders', function ($q) {
                    $q->whereIn('status', [
                        OrderEnum::New->value,
                        OrderEnum::UnderProcess->value,
                        OrderEnum::Contracted->value
                    ]);
                })
                ->get();

            return view($this->folderPath . '.change_maid', compact('contract', 'availableMaids'));
        } catch (\Exception $e) {
            logger()->error('Error showing change maid page:', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    /**
     * إلغاء العقد
     */
    public function cancel(Request $request)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإلغاء العقود'
                ], 403);
            }

            // التحقق من البيانات المطلوبة
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'cancel_reason' => 'required|string|max:500',
                'maid_status_after_cancel' => 'required|in:available,backout'
            ]);

            $contract = Contract::with(['maid', 'user'])->findOrFail($request->contract_id);

            // منع إلغاء العقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء العقد المكتمل'
                ], 400);
            }

            // منع إلغاء العقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد مسبقاً مرتجع'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // تحديث حالة العقد إلى مرتجع
                $contract->update([
                    'status' => ContractStatusEnum::Back->value
                ]);

                // إنشاء سجل حالة العقد
                $contract->contract_status_logs()->create([
                    'status' => ContractStatusEnum::Back->value,
                    'date' => now(),
                    'notes' => 'تم إلغاء العقد - السبب: ' . $request->cancel_reason
                ]);

                // تحديث حالة العاملة
                if ($contract->maid) {
                    $maid = $contract->maid;

                    if ($request->maid_status_after_cancel === 'available') {
                        // التحقق من عدم وجود عقود أخرى نشطة للعاملة
                        $activeContracts = $maid->contracts()
                            ->where('id', '!=', $contract->id)
                            ->whereNotIn('status', ['back', 'closed', 'completed'])
                            ->count();

                        $activeOrders = $maid->orders()
                            ->whereIn('status', ['pending', 'approved', 'in_progress'])
                            ->count();

                        if ($activeContracts === 0 && $activeOrders === 0) {
                            $maid->update([
                                'available' => true,
                                'status' => 'available'
                            ]);
                        }
                    } else {
                        // باك اوت
                        $maid->update([
                            'available' => false,
                            'status' => 'backout'
                        ]);
                    }
                }

                // تسجيل العملية
                logger()->info('Contract cancelled', [
                    'contract_id' => $contract->id,
                    'cancel_reason' => $request->cancel_reason,
                    'maid_status_after_cancel' => $request->maid_status_after_cancel,
                    'cancelled_by' => Auth::guard('admin')->id()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء العقد بنجاح',
                    'contract' => $contract->load(['user', 'maid', 'info'])
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            logger()->error('Error cancelling contract:', [
                'contract_id' => $request->contract_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }
}
