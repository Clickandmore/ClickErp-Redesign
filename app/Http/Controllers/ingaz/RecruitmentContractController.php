<?php

namespace App\Http\Controllers\ingaz;

use App\Actions\ClientAction;
use App\Actions\JobTitleAction;
use App\Actions\JobTypeAction;
use App\Actions\MaidAction;
use App\Actions\NationalityAction;
use App\Actions\OrderAction;
use App\Actions\ReligionAction;
use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Http\Traits\ContractSMSTrait;
use App\Models\Branch;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Country;
use App\Models\ingaz\ContractNote;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\RecruitmentContractAttachment;
use App\Models\ingaz\Religion;
use App\Models\ingaz\User;
use App\Models\MaidUnavailableRecord;
use App\Models\RecruitmentOffices;
use App\Models\RecruitmentContractStageStep;
use App\Models\RecruitmentContractStepCompletion;
use App\Models\TravelHistory;
use App\NewPermKeys;
use App\PermKeys;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Services\ingaz\RecruitmentContractRefundService;
use App\Services\ingaz\RecruitmentContractReturnRestorationService;
use App\Services\ingaz\RecruitmentContractStageRollbackService;
use App\Services\ingaz\RecruitmentContractStageStepService;
use App\Services\ingaz\CompletedRecruitmentMaidReleaseService;
use App\Services\ingaz\RentalMaidArrivalService;
use App\Services\ManagedFileStorage;
use App\Support\SystemModuleManager;
use App\Support\InvoiceAuthorization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentContractController extends Controller
{
    use ContractSMSTrait;

    private const DELAYED_ARRIVAL_DAYS = 45;

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
        return (bool) $admin;
    }

    private function canViewRecruitmentContracts(): bool
    {
        return @AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_VIEW,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ]);
    }

    private function canCreateRecruitmentContracts(): bool
    {
        return @AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_CREATE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ]);
    }

    private function canUpdateRecruitmentContracts(): bool
    {
        return @AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_UPDATE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ]);
    }

    private function canViewInvoiceCreditNotes(): bool
    {
        return InvoiceAuthorization::canViewCreditNotes()
            || InvoiceAuthorization::canPrintCreditNotes();
    }

    private function canCreateInvoiceCreditNotes(): bool
    {
        return InvoiceAuthorization::canCreateCreditNotes();
    }

    private function canSelectMaidForRecruitmentContracts(): bool
    {
        return @AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_CREATE,
            NewPermKeys::REC_CONTRACTS_UPDATE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ]);
    }

    private function contractableRecruitmentOrderStatuses(): array
    {
        return [
            OrderEnum::UnderProcess->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
        ];
    }

    private function canCreateContractForOrder(Order $order): bool
    {
        return $order->job_type_id == 1
            && in_array($order->status, $this->contractableRecruitmentOrderStatuses(), true)
            && !$order->contracts()->exists();
    }

    private function getManualStatusTransition(?string $status): ?array
    {
        $transition = match ($status) {
            ContractStatusEnum::New->value => [
                'next_status' => ContractStatusEnum::Msaned->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
                'button_prefix' => 'انتقال إلى',
            ],
            ContractStatusEnum::AccreditationContracts->value => [
                'next_status' => ContractStatusEnum::Msaned->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
                'button_prefix' => 'اعتماد العقد إلى',
            ],
            ContractStatusEnum::Msaned->value => [
                'next_status' => ContractStatusEnum::Tafwed->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED,
                'button_prefix' => 'انتقال إلى',
            ],
            ContractStatusEnum::Tafwed->value => [
                'next_status' => ContractStatusEnum::Taffez->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ,
                'button_prefix' => 'انتقال إلى',
            ],
            ContractStatusEnum::Taffez->value => [
                'next_status' => ContractStatusEnum::Tazker->value,
                'permission' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER,
                'button_prefix' => 'انتقال إلى',
            ],
            default => null,
        };

        if (!$transition) {
            return null;
        }

        $transition['button_text'] = $transition['button_prefix'] . ' ' . getContractStatus($transition['next_status']);
        unset($transition['button_prefix']);

        return $transition;
    }

    private function getBlockedTransitionReason(?string $status): string
    {
        return match ($status) {
            ContractStatusEnum::PendingMaidSelection->value => 'يجب اختيار العاملة أولاً قبل تحديث حالة العقد',
            ContractStatusEnum::Tazker->value => 'هذه المرحلة تتطلب إضافة رحلة أو إكمالها قبل الانتقال',
            ContractStatusEnum::Arrival->value => 'هذا العقد في مرحلة الوصول ويتم استكماله تلقائياً حسب حالة الضمان',
            ContractStatusEnum::Completed->value => 'العقد مكتمل؛ لا يمكن تقديمه، ويمكن فقط إرجاعه خطوة واحدة إلى مرحلة الوصول من زر الإرجاع',
            ContractStatusEnum::Refunded->value => 'العقد مسترد ومكتمل ولا يمكن تحديث حالته يدوياً',
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

        if (
            $transitionContext['can_update'] &&
            $contract->status !== ContractStatusEnum::Taffez->value
        ) {
            return array_merge($transitionContext, [
                'can_select' => true,
                'action_type' => 'status_update',
                'action_text' => $transitionContext['next_status_text'],
            ]);
        }

        $travelHistory = $contract->relationLoaded('travelHistory')
            ? $contract->travelHistory
            : $contract->travelHistory()->latest('id')->first();

        if (in_array($contract->status, [ContractStatusEnum::Taffez->value, ContractStatusEnum::Tazker->value], true)) {
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
                    'reason' => $contract->status === ContractStatusEnum::Taffez->value
                        ? 'يوجد رحلة مسجلة بالفعل لهذا العقد وسيتم استكمالها من مرحلة التذاكر'
                        : 'يوجد رحلة مسجلة بالفعل لهذا العقد',
                ];
            }

            if (!@AdminGuardHasPerm(NewPermKeys::REC_CONTRACTS_ADD_TRAVEL) || !@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE)) {
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

    private function buildTravelManagementActions(TravelHistory $travelHistory, string $context = 'buttons'): string
    {
        $canEditTravel = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT_TRAVEL)
            && @AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT);
        $canDeleteTravel = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE_TRAVEL)
            && @AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE);

        if (!$canEditTravel && !$canDeleteTravel) {
            return '';
        }

        if ($context === 'dropdown') {
            $html = '';

            if ($canEditTravel) {
                $html .= '<li><a class="dropdown-item" href="' . e(route('travel.edit', $travelHistory->id)) . '">
                            <i class="fas fa-edit"></i> تعديل الرحلة
                        </a></li>';
            }

            if ($canDeleteTravel) {
                $html .= '<li><button type="button" class="dropdown-item text-danger cancel-travel-btn" data-trip-id="' . $travelHistory->id . '">
                            <i class="fas fa-ban"></i> إلغاء الرحلة
                        </button></li>';
            }

            return $html;
        }

        $html = '<div class="btn-group btn-group-sm travel-management-actions" role="group" aria-label="إدارة الرحلة">';

        if ($canEditTravel) {
            $html .= '<a class="btn btn-outline-primary btn-action" href="' . e(route('travel.edit', $travelHistory->id)) . '" title="تعديل الرحلة">
                        <i class="fas fa-edit me-1"></i>تعديل الرحلة
                    </a>';
        }

        if ($canDeleteTravel) {
            $html .= '<button type="button" class="btn btn-outline-danger btn-action cancel-travel-btn" data-trip-id="' . $travelHistory->id . '" title="إلغاء الرحلة دون تغيير حالة العقد أو القيود المالية">
                        <i class="fas fa-ban me-1"></i>إلغاء الرحلة
                    </button>';
        }

        $html .= '</div>';

        return $html;
    }

    private function renderTravelArrivalDateBadge(?TravelHistory $travelHistory, bool $hasArrived = false): string
    {
        $arrivalDateTime = $travelHistory?->resolveArrivalDateTime();

        if (!$arrivalDateTime) {
            return '';
        }

        $badgeClass = $hasArrived
            ? 'contract-arrival-note contract-arrival-note--arrived'
            : 'contract-arrival-note contract-arrival-note--expected';
        $label = $hasArrived ? 'تاريخ الوصول' : 'الوصول المتوقع';
        $timeLabel = filled($travelHistory?->arrival_time)
            || (filled($travelHistory?->arrival_date) && preg_match('/\d{1,2}:\d{2}/', (string) $travelHistory->arrival_date))
                ? $arrivalDateTime->format('H:i')
                : null;

        return '<span class="' . $badgeClass . '">'
            . '<i class="fas fa-plane-arrival me-1"></i>'
            . e($label) . ': '
            . '<span dir="ltr">' . e($arrivalDateTime->format('Y-m-d')) . '</span>'
            . ($timeLabel ? ' - <span dir="ltr">' . e($timeLabel) . '</span>' : '')
            . '</span>';
    }

    private function updateContractStatusRecord(Contract $contract, string $nextStatus, ?string $date = null): array
    {
        $currentStatus = $contract->status;
        $statusDate = $date ?: now()->toDateString();

        DB::transaction(function () use ($contract, $currentStatus, $nextStatus, $statusDate) {
            app(RecruitmentContractStageStepService::class)->assertStageComplete($contract);
            $contract->update(['status' => $nextStatus]);

            $statusLogData = [
                'status' => $nextStatus,
                'date' => $statusDate,
                'notes' => 'تم تحديث الحالة من ' . getContractStatus($currentStatus) . ' إلى ' . getContractStatus($nextStatus)
            ];
            if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                $statusLogData['previous_status'] = $currentStatus;
            }
            $contract->contract_status_logs()->create($statusLogData);
        });

        $contract->refresh();

        return [
            'current_status' => $currentStatus,
            'next_status' => $nextStatus,
            'current_status_text' => getContractStatus($currentStatus),
            'next_status_text' => getContractStatus($nextStatus),
        ];
    }

    private function getCurrentContractStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Sari->value,
        ];
    }

    private function isRefundableRecruitmentContractStatus(?string $status): bool
    {
        return in_array($status, $this->getCurrentContractStatuses(), true);
    }

    private function getDelayedArrivalStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
        ];
    }

    private function getCompletedStageStatuses(): array
    {
        return [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::Refunded->value,
        ];
    }

    private function getStatusFilterForRequest(Request $request): ?string
    {
        if ($this->shouldTreatAsCompletedTotalRequest($request)) {
            return 'completed_stage';
        }

        return $request->get('status');
    }

    private function shouldTreatAsCompletedTotalRequest(Request $request): bool
    {
        if ($request->get('status') !== ContractStatusEnum::Arrival->value || !$request->filled('warranty_status')) {
            return false;
        }

        $warrantyStatuses = array_values(array_unique(array_filter(array_map('trim', explode(',', $request->warranty_status)))));
        sort($warrantyStatuses);

        return $warrantyStatuses === ['expired', 'not_applicable'];
    }

    private function getEffectiveContractDateSql(): string
    {
        return "DATE(COALESCE(NULLIF((SELECT ci.contract_date FROM contract_infos as ci WHERE ci.contract_id = contracts.id LIMIT 1), '0000-00-00'), NULLIF(contracts.contract_date, '0000-00-00')))";
    }

    private function getEffectiveArrivalDateSql(): string
    {
        return "DATE(COALESCE(
            NULLIF(contracts.warranty_start_date, '0000-00-00 00:00:00'),
            (SELECT COALESCE(NULLIF(th.arrival_date, '0000-00-00'), th.arrived_at)
                FROM travel_histories as th
                WHERE th.order_id = contracts.id
                    AND th.deleted_at IS NULL
                    AND th.status = 'completed'
                ORDER BY th.id DESC
                LIMIT 1),
            CASE WHEN contracts.status IN ('arrival', 'completed')
                THEN NULLIF((SELECT ci.arrival_date FROM contract_infos as ci WHERE ci.contract_id = contracts.id LIMIT 1), '0000-00-00')
                ELSE NULL
            END,
            CASE WHEN contracts.status IN ('arrival', 'completed')
                THEN NULLIF((SELECT m.arrival_date FROM maids as m WHERE m.id = contracts.maid_id LIMIT 1), '0000-00-00')
                ELSE NULL
            END
        ))";
    }

    private function sponsorshipTransferDisplayStatuses(): array
    {
        return [
            'transferred',
            'completed',
            'trial_started',
            'trial',
            'maid_delivered',
            'contract_signed',
            'contract_sent',
            'waiting_contract_send',
        ];
    }

    private function resolveDisplayedContractClient(Contract $contract): ?User
    {
        $transfer = $contract->relationLoaded('sponsorshipTransfers')
            ? $contract->sponsorshipTransfers
                ->whereIn('status', $this->sponsorshipTransferDisplayStatuses())
                ->sortByDesc('id')
                ->first()
            : $contract->sponsorshipTransfers()
                ->with('oldSponsor')
                ->whereIn('status', $this->sponsorshipTransferDisplayStatuses())
                ->latest('id')
                ->first();

        return $transfer?->oldSponsor ?: $contract->user;
    }

    private function applyDisplayedClientFilter($query, callable $userFilter): void
    {
        $displayStatuses = $this->sponsorshipTransferDisplayStatuses();

        $query->where(function ($clientQuery) use ($userFilter, $displayStatuses) {
            $clientQuery->where(function ($transferClientQuery) use ($userFilter, $displayStatuses) {
                $transferClientQuery
                    ->whereHas('sponsorshipTransfers', function ($transferQuery) use ($userFilter, $displayStatuses) {
                        $transferQuery
                            ->whereIn('status', $displayStatuses)
                            ->whereHas('oldSponsor', $userFilter);
                    });
            })->orWhere(function ($directClientQuery) use ($userFilter, $displayStatuses) {
                $directClientQuery
                    ->whereDoesntHave('sponsorshipTransfers', function ($transferQuery) use ($displayStatuses) {
                        $transferQuery->whereIn('status', $displayStatuses);
                    })
                    ->whereHas('user', $userFilter);
            });
        });
    }

    private function applyStatusFilter($query, ?string $statusFilter): void
    {
        if ($statusFilter === null || $statusFilter === '') {
            return;
        }

        if ($statusFilter === 'current') {
            $query->whereIn('status', $this->getCurrentContractStatuses())
                ->whereDoesntHave('travelHistory', function ($travelQuery) {
                    $travelQuery->where('status', 'completed');
                });
            return;
        }

        if ($statusFilter === 'completed_stage') {
            $query->where(function ($completedQuery) {
                $completedQuery
                    ->whereIn('status', $this->getCompletedStageStatuses())
                    ->orWhereHas('travelHistory', function ($travelQuery) {
                        $travelQuery->where('status', 'completed');
                    });
            });
            return;
        }

        if ($statusFilter === 'completed') {
            $query->where('status', ContractStatusEnum::Completed->value);
            return;
        }

        if ($statusFilter === 'arrival_today') {
            $query->whereIn('status', [
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value,
            ])->whereRaw(
                "DATE((SELECT th.arrival_date FROM travel_histories as th WHERE th.order_id = contracts.id AND th.deleted_at IS NULL ORDER BY th.id DESC LIMIT 1)) = ?",
                [Carbon::today()->toDateString()]
            );
            return;
        }

        if ($statusFilter === 'delayed_arrival') {
            $query->whereIn('status', $this->getDelayedArrivalStatuses())
                ->whereRaw($this->getEffectiveContractDateSql() . ' < ?', [
                    Carbon::today()->subDays(self::DELAYED_ARRIVAL_DAYS)->toDateString(),
                ]);
            return;
        }

        if ($statusFilter === 'expired_over_2y') {
            $query->whereRaw($this->getEffectiveArrivalDateSql() . ' < ?', [
                Carbon::today()->subYears(2)->toDateString(),
            ]);
            return;
        }

        $query->where('status', $statusFilter);
    }

    private function canViewShelterRecruitmentContracts(): bool
    {
        return @AdminGuardHasPerm(NewPermKeys::REC_CONTRACTS_VIEW_SHELTER);
    }

    private function excludeShelterContracts($query): void
    {
        $query->where(function ($contractQuery) {
            $contractQuery
                ->whereNull('status')
                ->orWhere('status', '!=', ContractStatusEnum::Shelter->value);
        });
    }


    public function index(Request $request)
    {
        // التحقق من صلاحية عرض العقود
        if (!$this->canViewRecruitmentContracts()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض العقود'
            ], 403);
        }

        $shelterContractsMode = $request->boolean('shelter_contracts');

        if ($shelterContractsMode && !$this->canViewShelterRecruitmentContracts()) {
            abort(403, 'ليس لديك صلاحية لعرض عقود الإيواء من شاشة عقود الاستقدام');
        }

        if ($request->ajax()) {
            $branchesEnabled = $this->branchesEnabledFor('contracts');

            $query = Contract::with(array_filter([
                'user', // جميع بيانات العميل
                'user.nationality', // جنسية العميل (Country model)
                'maid', // جميع بيانات العاملة
                'travelHistory',
                'marketer',
                'externalOfficeDispatch',
                'maid.nationality.translations', // جنسية العاملة مع الترجمات
                'maid.religion.translations', // ديانة العاملة مع الترجمات
                'maid.recruitmentOffice.translations', // المكتب الخارجي مع الترجمات
                'requiredMaidNationality.translations', // الجنسية المفضلة للعاملة
                'requiredMaidReligion.translations',
                'requiredMaidJobTitle.translations',
                'info', // جميع بيانات العقد
                'info.manualMaidNationality.translations',
                'info.manualMaidOffice.translations',
                'info.shelter.translations', // بيانات الإيواء
                'contract_status_logs',
                'latestContractStatusLog', // آخر تحديث للحالة بدون تحميل كامل السجل
                'latestEscapeReport',
                'invoice.musanedTransferItem.transfer',
                'recruitmentRefund',
                'sponsorshipTransfers.oldSponsor.nationality',
                'order', // بيانات الطلب
                'order.details', // تفاصيل الطلب
                'order.details.admin',
                $branchesEnabled ? 'branch' : null,
            ]))
                ->withCount(['notes', 'attachments']);

            // فلتر الحالة - استخدام query parameter فقط
            $statusFilter = $this->getStatusFilterForRequest($request);

            $query->whereIn('type', ['recruitment', 'maid']);
            if ($shelterContractsMode) {
                $query->where('status', ContractStatusEnum::Shelter->value);
            } else {
                $this->excludeShelterContracts($query);
                $this->applyStatusFilter($query, $statusFilter);
            }

            // فلاتر توفر المستندات والإجراءات المالية/التشغيلية
            $this->applyDocumentPresenceFilters($query, $request);

            if ($request->boolean('exclude_back')) {
                $query->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhere('status', '!=', ContractStatusEnum::Back->value);
                });
            }

            // فلتر حالة الضمان إذا تم تمريرها (حسب تاريخ انتهاء الضمان)
            if (!$shelterContractsMode && !$this->shouldTreatAsCompletedTotalRequest($request) && $request->filled('warranty_status')) {
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
                $query->whereRaw($this->getEffectiveContractDateSql() . ' >= ?', [$request->date_from]);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereRaw($this->getEffectiveContractDateSql() . ' <= ?', [$request->date_to]);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
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
                $this->applyOfficeFilter($query, $request->office_id);
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
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

            $this->applyRequestBranchFilter($query, $request, 'contracts');

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
                    if ($row->latestEscapeReport) {
                        return '<span class="badge bg-danger"><i class="fas fa-running me-1"></i>بلاغ هروب</span>';
                    }
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
                                $warrantyStatus = 'active';
                            }
                            // إذا كان تاريخ انتهاء الضمان في الماضي (ضمان منتهي)
                            else {
                                $remainingDays = 0;
                                $warrantyStatus = 'expired';
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
                    $displayClient = $this->resolveDisplayedContractClient($row);

                    if ($displayClient) {
                        $phoneInfo  = '<div class="d-flex flex-column gap-3">';

                        // Name
                        $phoneInfo .= '<div class="fw-semibold text-dark">'
                            . e($displayClient->name)
                            . '</div>';

                        // Client number (same value used across the clients screens)
                        $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                            . '<i class="fas fa-hashtag text-secondary"></i>'
                            . '<span><span class="text-secondary">رقم العميل: </span>'
                            . e($displayClient->clientNumber())
                            . '</span>'
                            . '</div>';

                        // Phone
                        $phoneInfo .= '<div>'
                            . getPhoneWithTag($displayClient->primaryPhoneValue())
                            . '</div>';

                        // Birthdate
                        if ($displayClient->birthdate) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-cake-candles text-secondary"></i>'
                                . '<span><span class="text-secondary">تاريخ الميلاد: </span>'
                                . \Carbon\Carbon::parse($displayClient->birthdate)->format('Y-m-d')
                                . '</span>'
                                . '</div>';
                        }

                        // Client document number
                        if ($displayClient->documentNumber()) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-id-card text-secondary"></i>'
                                . '<span><span class="text-secondary">' . e($displayClient->documentNumberLabel()) . ': </span>' . e($displayClient->documentNumber()) . '</span>'
                                . '</div>';
                        }

                        // Address
                        if ($displayClient->address) {
                            $phoneInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-location-dot text-secondary"></i>'
                                . '<span style="max-width:180px;"><span class="text-secondary font-bold"><span class="text-secondary">العنوان: </span></span></br>' . e($displayClient->address) . '</span>'
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

                        // Gender, in the conventional pink/blue so it reads at a
                        // glance in a column that is otherwise all muted grey.
                        if ($row->maid->gender_label) {
                            $isFemale = $row->maid->gender_label === 'أنثى';
                            $genderColor = $isFemale ? '#d63384' : '#0d6efd';
                            $genderIcon = $isFemale ? 'fa-venus' : 'fa-mars';

                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas ' . $genderIcon . '" style="color:' . $genderColor . '"></i>'
                                . '<span><span class="text-secondary">الجنس: </span>'
                                . '<span class="fw-semibold" style="color:' . $genderColor . '">'
                                . e($row->maid->gender_label)
                                . '</span></span>'
                                . '</div>';
                        }

                        // Office
                        if ($row->maid->recruitmentOffice) {
                            $maidInfo .= '<div class="text-muted small d-flex align-items-center gap-2">'
                                . '<i class="fas fa-building text-secondary"></i>'
                                . '<span><span class="text-secondary">المكتب الخارجي: </span>' . $row->maid->recruitmentOffice->title . '</span>'
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
                    $contractArrival = $this->resolveWorkerArrivalDetails($row);

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

                    if ($row->isCurrentRecruitmentContract() || $row->externalOfficeDispatch) {
                        $dispatchState = $row->getExternalOfficeDispatchState();

                        if ($dispatchState['is_completed'] ?? false) {
                            $dispatchBadge = $this->getExternalOfficeDispatchBadgeConfig($row);
                            $contractInfo .= '<div class="mt-2">'
                                . '<span class="badge bg-' . e($dispatchBadge['class']) . '" title="' . e($dispatchBadge['message']) . '">'
                                . e($dispatchBadge['label'])
                                . '</span>'
                                . '</div>';
                        }
                    }

                    // تاريخ الوصول كملاحظة واضحة على كل عقد، سواء وصلت العاملة أو ما زالت مجدولة.
                    if ($contractArrival['date_label']) {
                        $arrivalNoteClass = $contractArrival['has_arrived']
                            ? 'contract-arrival-note contract-arrival-note--arrived'
                            : 'contract-arrival-note contract-arrival-note--expected';
                        $arrivalNoteLabel = $contractArrival['has_arrived'] ? 'تاريخ الوصول' : 'الوصول المتوقع';
                        $contractInfo .= '<div class="mt-2">'
                            . '<span class="' . $arrivalNoteClass . '">'
                            . '<i class="fas fa-plane-arrival me-1"></i>'
                            . $arrivalNoteLabel . ': '
                            . '<span dir="ltr">' . e($contractArrival['date_label']) . '</span>'
                            . ($contractArrival['time_label']
                                ? ' - <span dir="ltr">' . e($contractArrival['time_label']) . '</span>'
                                : '')
                            . '</span>'
                            . '</div>';
                    }

                    if ((int) ($row->notes_count ?? 0) > 0) {
                        $notesCount = (int) $row->notes_count;
                        $contractInfo .= '<div class="mt-2">'
                            . '<a class="contract-notes-indicator" href="' . e(route('contracts.details', $row->id) . '#contractNotesSection') . '" title="عرض ملاحظات العقد">'
                            . '<i class="fas fa-sticky-note"></i>'
                            . '<span>عليه ملاحظات</span>'
                            . '<span class="badge bg-warning text-dark">' . $notesCount . '</span>'
                            . '</a>'
                            . '</div>';
                    }

                    return $contractInfo ?: '<span class="text-muted"><i class="fas fa-minus-circle me-1"></i>غير محدد</span>';
                })
                ->addColumn('arrival_info', function ($row) {
                    $arrival = $this->resolveWorkerArrivalDetails($row);

                    $html = '<div class="arrival-info-cell">';
                    $html .= $arrival['has_arrived']
                        ? '<div class="arrival-info-status">'
                            . '<i class="fas fa-circle-check"></i>'
                            . '<span>وصلت العاملة</span>'
                            . '</div>'
                        : '<div class="arrival-info-status text-warning">'
                            . '<i class="fas fa-plane-departure"></i>'
                            . '<span>لم تصل بعد</span>'
                            . '</div>';

                    if ($arrival['date_label']) {
                        $html .= '<div class="arrival-info-date">'
                            . '<span>' . ($arrival['has_arrived'] ? 'تاريخ الوصول للمملكة' : 'تاريخ الوصول المتوقع') . '</span>'
                            . '<strong dir="ltr">' . e($arrival['date_label']) . '</strong>'
                            . '</div>';
                    } else {
                        $html .= '<div class="arrival-date-missing">'
                            . '<i class="fas fa-triangle-exclamation"></i>'
                            . '<span>' . ($arrival['has_arrived']
                                ? 'تاريخ الوصول الفعلي غير مسجل'
                                : 'لم يُحدد موعد الوصول بعد') . '</span>'
                            . '</div>';
                    }

                    if ($arrival['time_label']) {
                        $html .= '<div class="arrival-info-row">'
                            . '<i class="fas fa-clock text-secondary"></i>'
                            . '<span>وقت الوصول: <strong dir="ltr">' . e($arrival['time_label']) . '</strong></span>'
                            . '</div>';
                    }

                    if ($arrival['station']) {
                        $html .= '<div class="arrival-info-row">'
                            . '<i class="fas fa-location-dot text-secondary"></i>'
                            . '<span>محطة الوصول: <strong>' . e($arrival['station']) . '</strong></span>'
                            . '</div>';
                    }

                    if ($arrival['flight_company']) {
                        $html .= '<div class="arrival-info-row">'
                            . '<i class="fas fa-building text-secondary"></i>'
                            . '<span>شركة الطيران: <strong>' . e($arrival['flight_company']) . '</strong></span>'
                            . '</div>';
                    }

                    if ($arrival['flight_number']) {
                        $html .= '<div class="arrival-info-row">'
                            . '<i class="fas fa-hashtag text-secondary"></i>'
                            . '<span>رقم الرحلة: <strong dir="ltr">' . e($arrival['flight_number']) . '</strong></span>'
                            . '</div>';
                    }

                    return $html . '</div>';
                })
                ->addColumn('duration', function ($row) {
                    $duration = $this->buildContractDurationSummary($row);
                    $durationState = $duration['worker_arrived']
                        ? 'duration-completed'
                        : ($duration['total_days'] > 90
                            ? 'duration-critical'
                            : ($duration['total_days'] > self::DELAYED_ARRIVAL_DAYS
                                ? 'duration-warning'
                                : 'duration-active'));

                    $html = '<div class="application-duration-cell ' . $durationState . '">';
                    $html .= '<div class="application-duration-main">'
                        . '<i class="fas fa-hourglass-half"></i>'
                        . '<span class="application-duration-value">' . e($duration['total_label']) . '</span>'
                        . '</div>';

                    $html .= '<div class="application-duration-range">'
                        . '<span><i class="fas fa-calendar-plus"></i> من <strong dir="auto">' . e($duration['started_at_label']) . '</strong></span>'
                        . '<span><i class="fas fa-calendar-check"></i> حتى <strong dir="auto">' . e($duration['ended_at_label']) . '</strong></span>'
                        . '</div>';

                    if ($duration['worker_arrived']) {
                        $html .= '<div class="application-duration-note">'
                            . '<i class="fas fa-circle-check"></i> توقفت المدة عند وصول العاملة'
                            . '</div>';
                    }

                    if (!$duration['worker_arrived'] && $duration['msaned_started']) {
                        $badgeClass = $duration['msaned_badge_class'] ?? 'bg-success-subtle text-success';
                        $html .= '<div class="application-duration-msaned">'
                            . '<i class="fas fa-stopwatch text-secondary"></i>'
                            . '<span>من مساند: <strong>' . e($duration['msaned_days_label']) . '</strong></span>'
                            . '</div>';
                        $html .= '<div><span class="badge ' . $badgeClass . '">' . e($duration['msaned_progress_label']) . '</span></div>';
                    } elseif (!$duration['worker_arrived']) {
                        $html .= '<div class="application-duration-msaned">'
                            . '<i class="fas fa-clock text-secondary"></i>'
                            . '<span>لم يبدأ عداد مساند بعد</span>'
                            . '</div>';
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('marketer', function ($row) {
                    $marketerInfo = '';

                    // البحث عن المسوق من marketer_id المباشر
                    if ($row->marketer) {
                        $marketerInfo = '<div class="marketer-info text-nowrap text-secondary">';
                        $marketerInfo .= '<i class="fas fa-user-tie me-1 text-secondary"></i>';
                        $marketerInfo .= '<span>' . htmlspecialchars($row->marketer->name) . '</span>';
                        $marketerInfo .= '</div>';
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
                ->addColumn('branch', function ($row) use ($branchesEnabled) {
                    if (!$branchesEnabled) {
                        return '-';
                    }

                    return $row->branch ? e($row->branch->name) : '-';
                })
                ->addColumn('recruitment_office', function ($row) {
                    $office = $row->maid?->recruitmentOffice ?? $row->info?->manualMaidOffice;
                    return $office?->title ?  '<span class="text-muted">' . $office?->title .'</span>' : '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('updateStatus', function ($row) {
                    $currentStageStep = app(RecruitmentContractStageStepService::class)->currentStep($row);
                    if ($currentStageStep) {
                        return '<button type="button" class="btn btn-warning btn-sm btn-action complete-stage-step-btn"'
                            . ' data-contract-id="' . e($row->id) . '"'
                            . ' data-step-id="' . e($currentStageStep->id) . '"'
                            . ' data-step-name="' . e($currentStageStep->displayName(app()->getLocale())) . '"'
                            . ' data-stage-name="' . e(\App\Support\RecruitmentContractStageLabels::label($row->status) ?: $row->status) . '"'
                            . ' data-url="' . e(route('RecruitmentContract.stageSteps.index', $row->id)) . '"'
                            . ' title="تنفيذ المراحل الفرعية">'
                            . '<i class="fas fa-list-check me-1"></i>مراحل ' . e(\App\Support\RecruitmentContractStageLabels::label($row->status) ?: $row->status)
                            . '</button>';
                    }

                    if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                        // زر إضافة عاملة للعقود التي في حالة بانتظار اختيار العاملة
                        return '<a href="' . route('RecruitmentContract.selectMaid', $row->id) . '" class="btn btn-warning btn-sm btn-action" title="إضافة عاملة">
                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                        </a>';
                    } else if (in_array($row->status, [ContractStatusEnum::Taffez->value, ContractStatusEnum::Tazker->value], true)) {
                        // التحقق من صلاحيات إدارة الرحلات
                        $hasAddTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL);
                        $hasCompleteTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL);

                        // check if there is a travel history for this contract
                        $travelHistory = $row->travelHistory;

                        if ($travelHistory) {
                            if ($row->status === ContractStatusEnum::Taffez->value) {
                                if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER)) {
                                    $lastContractStatusLog = $row->getLastContractStatusLog();

                                    $buttonText = 'انتقال إلى ' . getContractStatus(ContractStatusEnum::Tazker->value);

                                    return '<button class="btn btn-success btn-sm update-status-btn btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-next-status="' . ContractStatusEnum::Tazker->value . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '" title="' . e($buttonText) . '">
                                        <i class="fas fa-arrow-up me-1"></i>' . e($buttonText) . '
                                    </button>';
                                }

                                return '<span class="badge bg-warning">رحلة مسجلة - بانتظار مرحلة التذاكر</span>';
                            }

                            $arrivalBadge = $this->renderTravelArrivalDateBadge(
                                $travelHistory,
                                $travelHistory->status === 'completed'
                            );

                            if ($travelHistory->status === 'completed') {
                                return '<div class="d-flex flex-column align-items-start gap-2">'
                                    . '<span class="badge bg-success">تم إكمال الرحلة</span>'
                                    . $arrivalBadge
                                    . '</div>';
                            } else {
                                if ($hasCompleteTravelPermission) {
                                    return '<div class="d-flex flex-column align-items-start gap-2">'
                                        . $arrivalBadge
                                        . '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                            <i class="fas fa-check me-1"></i>إكمال الرحلة
                                        </button>'
                                        . '</div>';
                                } else {
                                    $statusText = $travelHistory->status === 'active' ? 'رحلة نشطة' : 'رحلة قيد الإعداد';
                                    return '<div class="d-flex flex-column align-items-start gap-2">'
                                        . $arrivalBadge
                                        . '<span class="badge bg-warning">' . $statusText . '</span>'
                                        . '</div>';
                                }
                            }
                        } else {
                            if ($hasAddTravelPermission) {
                                return '<button type="button" class="btn btn-primary btn-sm add-travel-btn btn-action" style="padding: 10px; margin: 10px;" data-contract-id="' . $row->id . '" title="إضافة رحلة">
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
                                $buttonText = 'انتقال إلى ' . getContractStatus($nextStatus);
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::AccreditationContracts->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                                $nextStatus = ContractStatusEnum::Msaned->value;
                                $buttonText = 'اعتماد العقد إلى ' . getContractStatus($nextStatus);
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Msaned->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                                $nextStatus = ContractStatusEnum::Tafwed->value;
                                $buttonText = 'انتقال إلى ' . getContractStatus($nextStatus);
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Tafwed->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                                $nextStatus = ContractStatusEnum::Taffez->value;
                                $buttonText = 'انتقال إلى ' . getContractStatus($nextStatus);
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Taffez->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                                $nextStatus = ContractStatusEnum::Tazker->value;
                                $buttonText = 'انتقال إلى ' . getContractStatus($nextStatus);
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Arrival->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }

                                if ($travelHistory) {
                                    $travelActions = $this->buildTravelManagementActions($travelHistory);
                                    if ($travelActions !== '') {
                                        return $travelActions;
                                    }
                                }

                                return '<span class="text-muted">يُستكمل تلقائياً بعد انتهاء الضمان</span>';
                            case ContractStatusEnum::Sari->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Shelter->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::UnderWarranty->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Closed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Completed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = $row->travelHistory;
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }

                                // العقد المكتمل لا يُقدَّم ولا يقفز بين المراحل؛ يُرجع خطوة واحدة فقط (إلى الوصول)
                                // بعد فحص القيود المالية في RecruitmentContractStageRollbackService.
                                $completedActions = [];
                                if ($travelHistory) {
                                    $travelActions = $this->buildTravelManagementActions($travelHistory);
                                    if ($travelActions !== '') {
                                        $completedActions[] = $travelActions;
                                    }
                                }

                                $backButton = $this->renderStageRollbackButton($row);
                                if ($backButton !== '') {
                                    $completedActions[] = $backButton;
                                }

                                if ($completedActions !== []) {
                                    return '<div class="d-flex flex-column align-items-start gap-2">' . implode('', $completedActions) . '</div>';
                                }

                                return '<span class="badge bg-success">عقد مكتمل</span>';
                            default:
                                break;
                        }

                        if ($hasPermission) {
                            $lastContractStatusLog = $row->getLastContractStatusLog();
                            $buttonPrefix = $row->status === ContractStatusEnum::AccreditationContracts->value
                                ? 'اعتماد العقد إلى'
                                : 'انتقال إلى';
                            $buttonText = $buttonPrefix . ' ' . getContractStatus($nextStatus);

                            return '<button class="btn btn-success btn-sm update-status-btn btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-next-status="' . $nextStatus . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '" title="' . e($buttonText) . '">
                                <i class="fas fa-arrow-up me-1"></i>' . e($buttonText) . '
                            </button>';
                        } else {
                            return '<span class="text-muted">لا توجد صلاحية</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) use ($statusFilter) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"  aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    if ($row->status === 'completed') {
                        $escapePermission = $row->latestEscapeReport ? PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW : PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE;
                        if (@AdminGuardHasPerm($escapePermission)) {
                            $actions .= '<li><a class="dropdown-item text-danger" href="' . e(route('RecruitmentContract.escapeReports.show', $row->id)) . '"><i class="fas fa-running"></i> ' . ($row->latestEscapeReport ? 'إدارة بلاغ الهروب' : 'إضافة بلاغ هروب') . '</a></li>';
                        }
                    }

                    // تفاصيل العقد
                    $actions .= '<li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض تفاصيل العقد
                                </a></li>';

                    if (
                        in_array($statusFilter, ['completed_stage', ContractStatusEnum::Completed->value], true)
                        && $row->status === ContractStatusEnum::Completed->value
                        && @AdminGuardHasPerm(NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION)
                    ) {
                        $releaseEligibility = app(CompletedRecruitmentMaidReleaseService::class)->eligibility($row);
                        if ($releaseEligibility['eligible']) {
                            $actions .= '<li><form method="POST" class="release-completed-maid-form" action="' . e(route('RecruitmentContract.releaseMaidAfterCompletion', $row->id)) . '" data-contract-id="' . e($row->id) . '" data-maid-name="' . e($row->maid?->name ?: ('العاملة #' . $row->maid_id)) . '">'
                                . csrf_field()
                                . '<button type="submit" class="dropdown-item text-success"><i class="fas fa-plane-departure"></i> اكتمال العقد وتسفير العاملة</button>'
                                . '</form></li>';
                        }
                    }

                    if (
                        in_array($row->status, [
                            ContractStatusEnum::Arrival->value,
                            ContractStatusEnum::Completed->value,
                            ContractStatusEnum::UnderWarranty->value,
                            ContractStatusEnum::Closed->value,
                        ], true)
                        && @AdminGuardHasAnyPerm([
                            \App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE,
                            \App\PermKeys::SHELTER_CONTRACT_CREATE,
                            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                        ])
                    ) {
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('ShelterContract.create', ['contract_id' => $row->id])) . '">
                                        <i class="fas fa-house-user"></i> إضافة العاملة للإيواء
                                    </a></li>';
                    }

                    if ($row->isCurrentRecruitmentContract() || $row->externalOfficeDispatch) {
                        $dispatchState = $row->getExternalOfficeDispatchState();
                        $dispatchManageLabel = $row->isCurrentRecruitmentContract()
                            ? ($dispatchState['is_completed']
                                ? 'عرض إرسالية المكتب الخارجي'
                                : 'استكمال إرسالية المكتب الخارجي')
                            : 'عرض إرسالية المكتب الخارجي';

                        $actions .= '<li><a class="dropdown-item" href="' . e(route('RecruitmentContract.externalOfficeDispatch.edit', $row->id)) . '">
                                        <i class="fas fa-paper-plane"></i> ' . e($dispatchManageLabel) . '
                                    </a></li>';
                    }

                    // الفاتورة
                    $invoice = $row->invoice;
                    if (!$invoice) {
                        if ($row->isInvoiceEligible() && InvoiceAuthorization::canCreate()) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('invoices.create') . '?contract_id=' . $row->id . '" target="_blank">
                                        <i class="fas fa-file-invoice"></i> اضافة فاتورة
                                    </a></li>';
                        }
                    } elseif ($invoice->musanedTransferItem && $invoice->musanedTransferItem->transfer) {
                        $musanedTransfer = $invoice->musanedTransferItem->transfer;
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('musaned-transfers.show', $musanedTransfer->id)) . '">
                                    <i class="fas fa-money-bill-transfer"></i> عرض تحويل مساند
                                </a></li>';
                        if (@AdminGuardHasAnyPerm([
                            \App\PermKeys::MUSANED_TRANSFERS_DELETE,
                            \App\PermKeys::MUSANED_TRANSFERS_CONTROL,
                        ])) {
                            $actions .= '<li><a class="dropdown-item text-danger" href="' . e(route('musaned-transfers.show', $musanedTransfer->id) . '#deleteMusanedTransfer') . '">
                                        <i class="fas fa-trash"></i> حذف تحويل مساند
                                    </a></li>';
                        }
                    } else {
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('musaned-transfers.create', ['contract_ids' => [$row->id]])) . '">
                                    <i class="fas fa-money-bill-transfer"></i> تسجيل تحويل مساند
                                </a></li>';
                    }

                    if ($row->recruitmentRefund) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '#contractRefundSection">
                                        <i class="fas fa-receipt"></i> عرض تفاصيل الاسترداد
                                    </a></li>';
                    }

                    // منع التعديل والحذف للعقود المكتملة والمرتجعة والتي في الإيواء
                    if (
                        $row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Refunded->value &&
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
                        if (!$invoice && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
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

                    // زر إكمال الرحلة - يظهر للعقود في مرحلة التذاكر التي لديها رحلة فعلية
                    $travelHistory = $row->travelHistory;
                    if ($travelHistory) {
                        $travelManagementActions = $this->buildTravelManagementActions($travelHistory, 'dropdown');
                        if ($travelManagementActions !== '') {
                            $actions .= '<li><hr class="dropdown-divider"></li>';
                            $actions .= $travelManagementActions;
                        }
                    }

                    if ($travelHistory && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                        if (
                            $row->status === ContractStatusEnum::Tazker->value &&
                            $travelHistory->status !== 'completed'
                        ) {
                            $actions .= '<li><button class="dropdown-item complete-trip-btn" data-trip-id="' . $travelHistory->id . '">
                                            <i class="fas fa-check"></i> إكمال الرحلة
                                        </button></li>';
                        }
                    }

                    // زر إضافة رحلة - يظهر للعقود في مرحلة التفييز التي لا تحتوي على رحلة
                    if (
                        $row->status === ContractStatusEnum::Taffez->value &&
                        !$travelHistory &&
                        @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL)
                    ) {
                        $actions .= '<li><button type="button" class="dropdown-item add-travel-btn" data-contract-id="' . $row->id . '">
                                        <i class="fas fa-plane"></i> إضافة رحلة
                                    </button></li>';
                    }

                    // زر الإرجاع - خطوة واحدة إلى الوراء ضمن تسلسل المراحل (لا يظهر للمسترد أو الذي في الإيواء)
                    if (!in_array($row->status, [ContractStatusEnum::Refunded->value, ContractStatusEnum::Shelter->value], true)) {
                        if ($row->status == ContractStatusEnum::Back->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-warning">مرتجع</span>
                                        </span></li>';
                            if (@AdminGuardHasAnyPerm([
                                \App\NewPermKeys::REC_CONTRACTS_RETURN,
                                \App\NewPermKeys::REC_CONTRACTS_MANAGE,
                            ])) {
                                $actions .= '<li><button class="dropdown-item text-success restore-returned-contract-btn" data-contract-id="' . $row->id . '">
                                                <i class="fas fa-rotate-right"></i> التراجع عن الإرجاع
                                            </button></li>';
                            }
                        } else {
                            // التحقق من صلاحية إرجاع العقد
                            if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                                $backStatus = app(RecruitmentContractStageRollbackService::class)->previousStatus($row);

                                if ($backStatus) {
                                    $backStatusLabel = e(getContractStatus($backStatus));

                                    $actions .= '<li><button class="dropdown-item backContract" data-row-id="' . $row->id . '" data-target-status="' . e($backStatus) . '" data-target-label="' . $backStatusLabel . '">
                                                    <i class="fas fa-undo"></i> إرجاع إلى ' . $backStatusLabel . '
                                                </button></li>';
                                }
                            }
                        }
                    }

                    // إجراء الاسترداد بدلاً من إلغاء العقد
                    if (
                        $row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Refunded->value &&
                        $row->status !== ContractStatusEnum::Back->value &&
                        $row->status !== ContractStatusEnum::Shelter->value
                    ) {
                        if (
                            !$row->recruitmentRefund &&
                            $this->isRefundableRecruitmentContractStatus($row->status) &&
                            @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)
                        ) {
                            if ($invoice) {
                                $hasMusanedTransfer = $invoice->musanedTransferItem && $invoice->musanedTransferItem->transfer;
                                $actions .= '<li><button class="dropdown-item action-refund refund-contract-btn"
                                                data-contract-id="' . $row->id . '"
                                                data-contract-number="' . $row->id . '"
                                                data-client-name="' . e($this->resolveDisplayedContractClient($row)?->name ?? 'غير محدد') . '"
                                                data-has-musaned-transfer="' . ($hasMusanedTransfer ? '1' : '0') . '"
                                                data-contract-amount="' . e(number_format((float) $invoice->total_due, 2, '.', '')) . '">
                                                <i class="fas fa-rotate-left"></i> استرداد العقد
                                            </button></li>';
                            } else {
                                $actions .= '<li><span class="dropdown-item disabled">
                                                <span class="badge bg-warning text-dark">أضف فاتورة قبل الاسترداد</span>
                                            </span></li>';
                            }
                        }
                    }

                    $notesCount = (int) ($row->notes_count ?? 0);
                    if ($notesCount > 0) {
                        $actions .= '<li><a class="dropdown-item" href="' . e(route('contracts.details', $row->id) . '#contractNotesSection') . '">
                                        <i class="fas fa-sticky-note"></i>
                                        <span>عرض ملاحظات العقد</span>
                                        <span class="badge bg-warning text-dark attachment-count-badge">' . $notesCount . '</span>
                                    </a></li>';
                    }

                    // اضافة ملاحظة على العقد
                    $actions .= '<li><button class="dropdown-item add-contract-note-btn" data-contract-id="' . $row->id . '" data-notes-count="' . $notesCount . '" data-bs-toggle="modal"
                                    data-bs-target="#addContractNoteModal">
                                            <i class="fas fa-plus"></i> اضافة ملاحظة
                                        </button></li>';

                    $actions .= '<li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '#contractAttachmentsSection">
                                    <i class="fas fa-paperclip"></i>
                                    <span>مرفقات العقد والمالية</span>
                                    <span class="badge bg-secondary attachment-count-badge">' . (int) ($row->attachments_count ?? 0) . '</span>
                                </a></li>';

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
        $data['shelterContractsMode'] = $shelterContractsMode;
        $data['branchesEnabled'] = $this->branchesEnabledFor('contracts');
        $data['branches'] = $data['branchesEnabled']
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();
        return view($this->folderPath . '.index', $data);
    }


    public function details($id)
    {
        // التحقق من صلاحية عرض تفاصيل العقد
        if (!$this->canViewRecruitmentContracts()) {
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
            'maid.recruitmentOffice',
            'info.prevSponsorship',
            'info.manualMaidNationality',
            'info.manualMaidOffice',
            'info.shelter',
            'travelHistory',
            'externalOfficeDispatch',
            'sponsorshipTransfers.oldSponsor',
            'sponsorshipTransfers.newSponsor',
            'recruitmentRefund.refundConstraint.constraintDetails.account',
            'recruitmentRefund.creditNote.constraint',
            'recruitmentRefund.creditNote.financeSale',
            'recruitmentRefund.createdBy',
            'attachments.uploader',
            'invoice.financeSale',
            'invoice.recruitmentCreditNotes.constraint',
            'invoice.recruitmentCreditNotes.financeSale',
            'invoice.musanedTransferItem.transfer',
            'invoice.arrivalRevenueConstraint.constraintDetails.account',
            'invoice.externalOfficeCostConstraint.constraintDetails.account',
            'financeBonds.createdBy',
            'activities.causer',
            'contract_status_logs' => function ($query) {
                $query->with('performer')->orderByDesc('date')->orderByDesc('id');
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
        $statusTimeline = $this->buildContractStageTimeline($contract);
        $durationSummary = $this->buildContractDurationSummary($contract);
        $contractAttachments = $this->collectRecruitmentContractAttachments($contract);
        $contractDocuments = $this->collectRecruitmentContractDocuments($contract);
        $statusHistory = $contract->contract_status_logs->map(function ($log) use ($contract) {
            $performer = $this->resolveContractStatusPerformer($contract, $log->status, $log);

            return [
                'status' => $log->status,
                'status_label' => getEnumValue('contract_statuses', $log->status) ?? getContractStatus($log->status),
                'display_date' => $this->formatContractStatusLogDate($log, $contract),
                'notes' => $log->notes,
                'performer_name' => $performer['name'],
                'performer_source' => $performer['source'],
                'is_inferred' => false,
            ];
        });

        if ($statusHistory->isEmpty() || ($statusHistory->first()['status'] ?? null) !== $contract->status) {
            $performer = $this->resolveContractStatusPerformer($contract, $contract->status);
            $statusActivity = $contract->activities
                ->filter(fn ($activity) => (string) data_get($activity->properties, 'attributes.status') === $contract->status)
                ->sortByDesc('created_at')
                ->first();

            $inferredCurrentStatus = [
                'status' => $contract->status,
                'status_label' => getEnumValue('contract_statuses', $contract->status) ?? getContractStatus($contract->status),
                'display_date' => optional($statusActivity?->created_at ?? $contract->created_at)->format('Y-m-d H:i'),
                'notes' => $statusHistory->isEmpty()
                    ? 'الحالة المسجلة عند إنشاء أو استيراد العقد'
                    : 'انتقال مستنتج من سجل النشاط لعدم وجود سجل حالة مستقل',
                'performer_name' => $performer['name'],
                'performer_source' => $performer['source'],
                'is_inferred' => true,
            ];

            $statusHistory = collect([$inferredCurrentStatus])->concat($statusHistory)->values();
        }
        $financialSummary = $this->buildRecruitmentContractFinancialSummary($contract);
        $canViewInvoiceCreditNotes = $this->canViewInvoiceCreditNotes();
        $canCreateInvoiceCreditNotes = $this->canCreateInvoiceCreditNotes();
        $contractStageSteps = $this->getContractStageSteps($contract);
        $currentContractStageStep = app(RecruitmentContractStageStepService::class)->currentStep($contract);
        $canUpdateContractStageSteps = $this->canUpdateRecruitmentContracts();

        $contractExtensionRequests = \App\Models\ContractExtensionRequest::with(['requestedBy', 'processedBy'])
            ->where('contract_id', $contract->id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.ingaz.RecruitmentContract.details', compact(
            'contract',
            'info',
            'marketer',
            'statusTimeline',
            'durationSummary',
            'contractAttachments',
            'contractDocuments',
            'statusHistory',
            'financialSummary',
            'contractExtensionRequests',
            'canViewInvoiceCreditNotes',
            'canCreateInvoiceCreditNotes',
            'contractStageSteps',
            'currentContractStageStep',
            'canUpdateContractStageSteps'
        ));
    }

    private function getContractStageSteps(Contract $contract)
    {
        $nationalityId = $contract->maid?->nationality_id
            ?: $contract->required_maid_nationality_id
            ?: $contract->info?->manual_maid_nationality_id;

        return RecruitmentContractStageStep::withTrashed()
            ->with([
                'nationality.translations',
                'completions' => fn ($query) => $query
                    ->where('contract_id', $contract->id)
                    ->with('completedBy'),
            ])
            ->where(function ($query) use ($contract, $nationalityId) {
                $query->where(function ($currentSteps) use ($contract, $nationalityId) {
                    $currentSteps->whereNull('deleted_at')
                        ->where('is_active', true)
                        ->where('stage', $contract->status)
                        ->where(function ($nationalities) use ($nationalityId) {
                            $nationalities->whereNull('nationality_id');
                            if ($nationalityId) {
                                $nationalities->orWhere('nationality_id', $nationalityId);
                            }
                        });
                })->orWhereHas('completions', function ($completions) use ($contract) {
                    $completions->where('contract_id', $contract->id)
                        ->whereNotNull('completed_at');
                });
            })
            ->orderByRaw('CASE WHEN stage = ? THEN 0 ELSE 1 END', [$contract->status])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function stageSteps(Contract $contract)
    {
        abort_unless($this->canUpdateRecruitmentContracts(), 403);
        abort_unless(in_array($contract->type, ['recruitment', 'maid'], true), 404);
        $steps = app(RecruitmentContractStageStepService::class)->applicableSteps($contract);
        $completions = RecruitmentContractStepCompletion::query()
            ->where('contract_id', $contract->id)->whereIn('stage_step_id', $steps->pluck('id'))
            ->get()->keyBy('stage_step_id');

        return response()->json([
            'stage' => $contract->status,
            'today' => now()->toDateString(),
            'steps' => $steps->map(fn ($step) => [
                'id' => $step->id,
                'name' => $step->displayName(app()->getLocale()),
                'completed_at' => $completions->get($step->id)?->completed_at?->format('Y-m-d'),
                'notes' => $completions->get($step->id)?->notes,
            ]),
        ])->header('Cache-Control', 'no-store');
    }

    public function completeStageSteps(Request $request, Contract $contract)
    {
        abort_unless($this->canUpdateRecruitmentContracts(), 403);
        abort_unless(in_array($contract->type, ['recruitment', 'maid'], true), 404);
        $validated = $request->validate([
            'stage' => ['required', 'string'],
            'steps' => ['required', 'array', 'min:1', 'max:100'],
            'steps.*.id' => ['required', 'integer', 'distinct'],
            'steps.*.completed_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'steps.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);
        app(RecruitmentContractStageStepService::class)->completeMany(
            $contract, $validated['stage'], $validated['steps'], Auth::guard('admin')->id()
        );

        return response()->json(['success' => true, 'message' => 'تم تنفيذ المراحل المحددة بنجاح.']);
    }

    public function updateStageStep(Request $request, Contract $contract, RecruitmentContractStageStep $step)
    {
        abort_unless($this->canUpdateRecruitmentContracts(), 403);
        abort_unless(in_array($contract->type, ['recruitment', 'maid'], true), 404);

        $validated = $request->validate([
            'completed' => ['required', 'boolean'],
            'completed_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $nationalityId = $contract->maid?->nationality_id
            ?: $contract->required_maid_nationality_id
            ?: $contract->info?->manual_maid_nationality_id;
        $appliesToContract = $step->is_active
            && $step->stage === $contract->status
            && (!$step->nationality_id || (int) $step->nationality_id === (int) $nationalityId);

        abort_unless($appliesToContract, 422, 'هذه الخطوة لا تنطبق على مرحلة العقد أو جنسية العاملة الحالية.');

        $stepService = app(RecruitmentContractStageStepService::class);

        if (!$validated['completed']) {
            $stepService->reopenFrom($contract, $step);
            RecruitmentContractStepCompletion::query()
                ->where('contract_id', $contract->id)
                ->where('stage_step_id', $step->id)
                ->update(['notes' => trim((string) ($validated['notes'] ?? '')) ?: null]);
        } else {
            $existingCompletion = RecruitmentContractStepCompletion::query()
                ->where('contract_id', $contract->id)
                ->where('stage_step_id', $step->id)
                ->first();

            if (!$existingCompletion?->completed_at) {
                $stepService->assertCurrentStep($contract, $step);
            }

            $completion = RecruitmentContractStepCompletion::firstOrNew([
                'contract_id' => $contract->id,
                'stage_step_id' => $step->id,
            ]);
            $completion->notes = trim((string) ($validated['notes'] ?? '')) ?: null;
            if (!$completion->completed_at) {
                $completion->completed_at = $validated['completed_at'] ?? now();
                $completion->completed_by_id = Auth::guard('admin')->id();
            }
            $completion->save();
        }

        $message = $validated['completed'] ? 'تم إنجاز خطوة العقد بنجاح.' : 'تمت إعادة فتح خطوة العقد.';

        if ($request->expectsJson() || $request->ajax()) {
            $nextStep = $stepService->currentStep($contract->fresh());

            return response()->json([
                'success' => true,
                'message' => $message,
                'stage_complete' => $nextStep === null,
                'next_step' => $nextStep ? [
                    'id' => $nextStep->id,
                    'name' => $nextStep->displayName(app()->getLocale()),
                ] : null,
            ]);
        }

        return redirect()->route('contracts.details', $contract->id)->with('success', $message);
    }

    public function storeAttachments(Request $request, $contractId)
    {
        if (!$this->canUpdateRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لإضافة مرفقات على العقد.');
        }

        $contract = Contract::whereIn('type', ['recruitment', 'maid'])->findOrFail($contractId);

        $validated = $request->validate([
            'attachment_type' => 'nullable|string|in:invoice,financial,operational,contract,other',
            'attachments' => ['required', 'array'],
            'attachments.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
            'attachment_titles' => ['nullable', 'array'],
            'attachment_titles.*' => ['nullable', 'string', 'max:190'],
        ], [
            'attachments.required' => 'يرجى اختيار مرفق واحد على الأقل.',
            'attachments.*.mimes' => 'صيغة المرفق غير مدعومة.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        $type = $validated['attachment_type'] ?? 'other';
        $titles = $validated['attachment_titles'] ?? [];

        foreach ($request->file('attachments', []) as $index => $file) {
            $path = app(ManagedFileStorage::class)->uploadFile(
                $file,
                'recruitment-contracts/' . $contract->id
            );

            try {
                RecruitmentContractAttachment::create([
                    'contract_id' => $contract->id,
                    'title' => $titles[$index] ?? null,
                    'type' => $type ?: 'other',
                    'disk' => 'managed',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize() ?: 0,
                    'uploaded_by' => auth('admin')->id(),
                ]);
            } catch (\Throwable $exception) {
                deleteFile($path);
                throw $exception;
            }
        }

        return redirect()
            ->route('contracts.details', $contract->id)
            ->with('success', 'تم رفع مرفقات العقد بنجاح.');
    }

    public function downloadAttachments(Request $request, $contractId)
    {
        if (!$this->canViewRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتحميل مرفقات العقد.');
        }

        $contract = Contract::with('attachments')
            ->whereIn('type', ['recruitment', 'maid'])
            ->findOrFail($contractId);

        $attachments = $this->collectRecruitmentContractAttachments($contract);
        $selectedKeys = array_filter((array) $request->input('attachments', []));

        if (!empty($selectedKeys)) {
            $attachments = array_values(array_filter($attachments, function ($attachment) use ($selectedKeys) {
                return in_array($attachment['key'], $selectedKeys, true);
            }));
        } elseif ($request->boolean('selected_only')) {
            $attachments = [];
        }

        if (empty($attachments)) {
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتحميل في هذا العقد.');
        }

        $zip = new \ZipArchive();
        $zipFile = tempnam(sys_get_temp_dir(), 'recruitment-contract-attachments-');

        if ($zip->open($zipFile, \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'تعذر تجهيز ملف المرفقات.');
        }

        $storage = app(ManagedFileStorage::class);
        foreach ($attachments as $attachment) {
            $stream = $storage->readStream($attachment['path']);
            if (!is_resource($stream)) {
                continue;
            }

            $zip->addFromString($attachment['filename'], stream_get_contents($stream));
            fclose($stream);
        }

        $zip->close();

        if (filesize($zipFile) <= 0) {
            @unlink($zipFile);
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتحميل في هذا العقد.');
        }

        return response()
            ->download($zipFile, 'recruitment-contract-' . $contract->id . '-attachments.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadAttachment($contractId, RecruitmentContractAttachment $attachment)
    {
        if (!$this->canViewRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتحميل مرفقات العقد.');
        }

        if ((int) $attachment->contract_id !== (int) $contractId) {
            abort(404);
        }

        $storage = app(ManagedFileStorage::class);
        if (!$storage->exists($attachment->path)) {
            return redirect()->back()->with('error', 'ملف المرفق غير موجود.');
        }

        return $storage->downloadResponse(
            $attachment->path,
            $attachment->original_name,
            $attachment->mime_type ?: 'application/octet-stream'
        );
    }

    public function deleteAttachment($contractId, RecruitmentContractAttachment $attachment)
    {
        if (!$this->canUpdateRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لحذف مرفقات العقد.');
        }

        if ((int) $attachment->contract_id !== (int) $contractId) {
            abort(404);
        }

        deleteFile($attachment->path);
        $attachment->delete();

        return redirect()
            ->route('contracts.details', $contractId)
            ->with('success', 'تم حذف المرفق بنجاح.');
    }

    private function collectRecruitmentContractAttachments(Contract $contract): array
    {
        $contract->loadMissing('attachments');

        return $contract->attachments
            ->filter(fn ($attachment) => app(ManagedFileStorage::class)->exists($attachment->path))
            ->values()
            ->map(function ($attachment, $index) use ($contract) {
                $extension = pathinfo($attachment->original_name, PATHINFO_EXTENSION);
                $label = $attachment->title ?: $attachment->original_name;
                $safeLabel = Str::slug($label, '-');

                if ($safeLabel === '') {
                    $safeLabel = 'attachment-' . $attachment->id;
                }

                return [
                    'key' => 'uploaded-' . $attachment->id,
                    'id' => $attachment->id,
                    'label' => $label,
                    'type' => $attachment->type,
                    'type_label' => $this->getRecruitmentAttachmentTypeLabel($attachment->type),
                    'path' => $attachment->path,
                    'disk' => $attachment->disk ?: 'public',
                    'url' => route('RecruitmentContract.attachments.download.single', [
                        'contractId' => $contract->id,
                        'attachment' => $attachment->id,
                    ]),
                    'filename' => ($index + 1) . '-' . $safeLabel . ($extension ? '.' . $extension : ''),
                    'original_name' => $attachment->original_name,
                    'size_label' => $attachment->size_label,
                    'uploaded_by' => $attachment->uploader?->name,
                    'created_at' => optional($attachment->created_at)->format('Y-m-d H:i'),
                ];
            })
            ->all();
    }

    private function collectRecruitmentContractDocuments(Contract $contract): array
    {
        $contract->loadMissing([
            'invoice.financeSale',
            'invoice.recruitmentCreditNotes.constraint',
            'invoice.recruitmentCreditNotes.financeSale',
            'invoice.musanedTransferItem.transfer',
            'recruitmentRefund.refundConstraint',
            'externalOfficeDispatch',
            'financeBonds',
        ]);

        $documents = [];
        $financialDocuments = [];
        $addDocument = function (string $key, string $label, string $category, string $url, string $icon = 'fas fa-file') use (&$documents) {
            $documents[] = compact('key', 'label', 'category', 'url', 'icon');
        };
        $addFinancialDocument = function (string $key, string $label, string $url, string $icon = 'fas fa-file') use (&$financialDocuments) {
            $financialDocuments[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
                'icon' => $icon,
            ];
        };

        if ($contract->invoice) {
            if (InvoiceAuthorization::canView()) {
                $addFinancialDocument('invoice-view', 'عرض الفاتورة', route('invoices.show', $contract->invoice->id), 'fas fa-file-invoice');
            }
            if (InvoiceAuthorization::canPrint()) {
                $addFinancialDocument('invoice-download', 'طباعة / حفظ الفاتورة PDF', route('invoice.download', ['id' => $contract->invoice->id, 'autoprint' => 1]), 'fas fa-download');
            }

            if ($contract->invoice->invoice_constraint_id) {
                $addFinancialDocument('invoice-constraint', 'القيد المالي للفاتورة', route('finance.add-constraint.show', [
                    'add_constraint' => $contract->invoice->invoice_constraint_id,
                ]), 'fas fa-book');
            }

            if ($contract->invoice->finance_sale_id) {
                $addFinancialDocument('finance-sale', 'فاتورة المبيعات المحاسبية', route('finance.bill-sales.show', $contract->invoice->finance_sale_id), 'fas fa-file-lines');
            }

            if (InvoiceAuthorization::canPrintCreditNotes()) {
                foreach ($contract->invoice->recruitmentCreditNotes as $creditNote) {
                    $addFinancialDocument(
                        'invoice-credit-note-' . $creditNote->id,
                        'إشعار دائن #' . $creditNote->id . ' — ' . number_format((float) $creditNote->total, 2) . ' ريال',
                        route('recruitment-credit-notes.download', ['id' => $creditNote->id, 'autoprint' => 1]),
                        'fas fa-file-invoice-dollar'
                    );

                    if ($creditNote->constraint_id) {
                        $addFinancialDocument(
                            'invoice-credit-note-constraint-' . $creditNote->id,
                            'قيد إشعار الدائن #' . $creditNote->id,
                            route('finance.add-constraint.show', ['add_constraint' => $creditNote->constraint_id]),
                            'fas fa-book-open'
                        );
                    }
                }
            }

            if ($contract->invoice->arrival_revenue_constraint_id) {
                $addFinancialDocument('invoice-arrival-revenue-constraint', 'قيد إيراد الوصول', route('finance.add-constraint.show', [
                    'add_constraint' => $contract->invoice->arrival_revenue_constraint_id,
                    'autoprint' => 1,
                ]), 'fas fa-book');
            }

            if ($contract->invoice->external_office_cost_constraint_id) {
                $addFinancialDocument('invoice-office-cost-constraint', 'قيد تكلفة المكتب الخارجي', route('finance.add-constraint.show', [
                    'add_constraint' => $contract->invoice->external_office_cost_constraint_id,
                    'autoprint' => 1,
                ]), 'fas fa-book-open');
            }

            $transfer = $contract->invoice->musanedTransferItem?->transfer;
            if ($transfer) {
                $addFinancialDocument('musaned-transfer', 'تحويل مساند', route('musaned-transfers.show', $transfer->id), 'fas fa-money-bill-transfer');

                if ($transfer->settlement_constraint_id) {
                    $addFinancialDocument('musaned-settlement-constraint', 'قيد تسوية مساند', route('finance.add-constraint.show', [
                        'add_constraint' => $transfer->settlement_constraint_id,
                        'autoprint' => 1,
                    ]), 'fas fa-book');
                }

                if ($transfer->bank_transfer_constraint_id) {
                    $addFinancialDocument('musaned-bank-transfer-constraint', 'قيد تحويل البنك لمساند', route('finance.add-constraint.show', [
                        'add_constraint' => $transfer->bank_transfer_constraint_id,
                        'autoprint' => 1,
                    ]), 'fas fa-book-open');
                }
            }
        }

        foreach ($contract->financeBonds->where('type', 'qabd')->sortBy('id') as $bond) {
            $bondLabel = 'سند قبض العميل #' . ($bond->code ?: $bond->id);
            $addFinancialDocument(
                'receipt-bond-' . $bond->id,
                $bondLabel,
                route('finance.bonds-print.show', ['bonds_print' => $bond->id, 'type' => 'qabd']),
                'fas fa-receipt'
            );

            if ($bond->constraint_id) {
                $addFinancialDocument(
                    'receipt-bond-constraint-' . $bond->id,
                    'القيد المالي — ' . $bondLabel,
                    route('finance.add-constraint.show', ['add_constraint' => $bond->constraint_id]),
                    'fas fa-book-open'
                );
            }
        }

        if ($contract->recruitmentRefund?->refund_constraint_id) {
            $addFinancialDocument('refund-constraint', 'قيد استرداد العقد', route('finance.add-constraint.show', [
                'add_constraint' => $contract->recruitmentRefund->refund_constraint_id,
                'autoprint' => 1,
            ]), 'fas fa-rotate-left');
        }

        if ($contract->recruitmentRefund?->credit_note_id) {
            $addFinancialDocument('refund-credit-note', 'إشعار دائن استرداد العقد', route('recruitment-credit-notes.download', [
                'id' => $contract->recruitmentRefund->credit_note_id,
                'autoprint' => 1,
            ]), 'fas fa-file-invoice-dollar');
        }

        if (!empty($financialDocuments)) {
            $documents[] = [
                'key' => 'financial-attachments',
                'label' => 'المرفقات المالية',
                'category' => 'فاتورة ومعاملات مالية',
                'url' => $financialDocuments[0]['url'],
                'icon' => 'fas fa-folder-open',
                'items' => $financialDocuments,
            ];
        }

        if ($contract->externalOfficeDispatch) {
            $addDocument('external-office-dispatch', 'إرسالية المكتب الخارجي', 'إجراء تشغيلي', route('RecruitmentContract.externalOfficeDispatch.print', $contract->id), 'fas fa-paper-plane');
            $addDocument('external-office-dispatch-pdf', 'تنزيل إرسالية المكتب الخارجي PDF', 'إجراء تشغيلي', route('RecruitmentContract.externalOfficeDispatch.print', [
                'contract' => $contract->id,
                'driver' => 'dompdf',
                'download' => 1,
            ]), 'fas fa-file-pdf');
        }

        if (in_array($contract->status, [ContractStatusEnum::Arrival->value, ContractStatusEnum::Completed->value], true)) {
            $addDocument('delivery-form', 'نموذج تسليم العاملة', 'إجراء تشغيلي', route('contract_download_form.download', ['contract_id' => $contract->id]), 'fas fa-file-signature');
            $addDocument('delivery-form-pdf', 'تنزيل نموذج التسليم PDF', 'إجراء تشغيلي', route('contract_download_form.download', [
                'contract_id' => $contract->id,
                'driver' => 'dompdf',
                'download' => 1,
            ]), 'fas fa-file-pdf');
        }

        return $documents;
    }

    private function getRecruitmentAttachmentTypeLabel(?string $type): string
    {
        return match ($type) {
            'invoice' => 'فاتورة',
            'financial' => 'معاملة مالية',
            'operational' => 'إجراء تشغيلي',
            'contract' => 'مستند عقد',
            default => 'أخرى',
        };
    }

    private function resolveContractStatusPerformer(Contract $contract, string $status, $log = null): array
    {
        if ($log?->performer) {
            return [
                'name' => $log->performer->name ?: 'موظف غير محدد',
                'source' => 'status_log',
            ];
        }

        $matchingActivity = $contract->activities
            ->filter(function ($activity) use ($status) {
                return (string) data_get($activity->properties, 'attributes.status') === $status;
            })
            ->when($log?->created_at, function ($activities) use ($log) {
                return $activities->sortBy(function ($activity) use ($log) {
                    return abs(optional($activity->created_at)->diffInSeconds($log->created_at, false) ?? PHP_INT_MAX);
                });
            }, fn ($activities) => $activities->sortByDesc('created_at'))
            ->first();

        if ($matchingActivity?->causer) {
            return [
                'name' => $matchingActivity->causer->name
                    ?? $matchingActivity->causer->email
                    ?? 'موظف غير محدد',
                'source' => 'activity_log',
            ];
        }

        return [
            'name' => 'النظام',
            'source' => 'system',
        ];
    }

    private function buildRecruitmentContractFinancialSummary(Contract $contract): array
    {
        $invoice = $contract->invoice;
        $transferItem = $invoice?->musanedTransferItem;
        $linkedBonds = $contract->financeBonds->sortByDesc('date')->values();
        $confirmedReceiptBonds = $linkedBonds
            ->where('type', 'qabd')
            ->where('is_confirm', 1);
        $total = (float) ($invoice?->total_due ?? $contract->info?->contract_value ?? 0);
        $paid = $confirmedReceiptBonds->isNotEmpty()
            ? (float) $confirmedReceiptBonds->sum('value')
            : ($transferItem?->customer_paid_amount !== null
                ? (float) $transferItem->customer_paid_amount
                : ($invoice?->is_paid ? $total : 0.0));

        return [
            'has_invoice' => (bool) $invoice,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->legacy_invoice_number ?: $invoice?->id,
            'invoice_date' => optional($invoice?->created_at)->format('Y-m-d H:i'),
            'subtotal' => (float) ($invoice?->subtotal ?? 0),
            'discount' => (float) ($invoice?->discount ?? 0),
            'vat_amount' => (float) ($invoice?->vat_amount ?? 0),
            'total_due' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max($total - $paid, 0),
            'is_paid' => $total > 0 && $paid >= $total,
            'paid_at' => optional($invoice?->paid_at)->format('Y-m-d H:i'),
            'contract_info_value' => (float) ($contract->info?->contract_value ?? 0),
            'finance_sale_id' => $invoice?->finance_sale_id,
            'musaned_transfer_id' => $transferItem?->musaned_transfer_id,
            'arrival_revenue_constraint_id' => $invoice?->arrival_revenue_constraint_id,
            'external_office_cost_constraint_id' => $invoice?->external_office_cost_constraint_id,
            'external_office_purchase_id' => $invoice?->external_office_purchase_id,
            'payment_source_label' => $confirmedReceiptBonds->isNotEmpty()
                ? 'سندات القبض المؤكدة المرتبطة بالعقد'
                : ($transferItem ? 'تحويل مساند المرتبط بالفاتورة' : 'حالة السداد المسجلة في الفاتورة'),
            'linked_bonds' => $linkedBonds->map(function ($bond) {
                return [
                    'id' => $bond->id,
                    'code' => $bond->code ?: $bond->bond_book_num ?: $bond->id,
                    'type' => $bond->type,
                    'type_label' => $bond->type === 'qabd' ? 'سند قبض' : 'سند صرف',
                    'value' => (float) $bond->value,
                    'date' => $bond->date ? Carbon::parse($bond->date)->format('Y-m-d') : null,
                    'is_confirmed' => (bool) $bond->is_confirm,
                    'created_by' => $bond->createdBy?->name,
                ];
            })->all(),
            'source_label' => $invoice
                ? 'الفاتورة المرتبطة بالعقد'
                : 'بيانات العقد (لا توجد فاتورة مرتبطة)',
        ];
    }

    private function buildContractStageTimeline(Contract $contract): array
    {
        $logs = $contract->contract_status_logs
            ->map(function ($log) use ($contract) {
                $stageMoment = $this->resolveContractStageMoment($log, $contract);
                $stageDate = $stageMoment['at'] ?? null;

                if (!$stageDate) {
                    return null;
                }

                return [
                    'status' => $log->status,
                    'status_label' => getEnumValue('contract_statuses', $log->status) ?? getContractStatus($log->status),
                    'status_color' => getContractStatusColor($log->status),
                    'started_at' => $stageDate,
                    'started_precision' => $stageMoment['precision'] ?? 'time',
                    'notes' => $log->notes,
                    'performer' => $this->resolveContractStatusPerformer($contract, $log->status, $log),
                    'is_inferred' => false,
                ];
            })
            ->filter()
            ->sortBy(function ($log) {
                return $log['started_at']->getTimestamp();
            })
            ->values();

        if ($logs->isNotEmpty() && ($logs->last()['status'] ?? null) !== $contract->status) {
            $statusActivity = $contract->activities
                ->filter(fn ($activity) => (string) data_get($activity->properties, 'attributes.status') === $contract->status)
                ->sortByDesc('created_at')
                ->first();

            $logs->push([
                'status' => $contract->status,
                'status_label' => getEnumValue('contract_statuses', $contract->status) ?? getContractStatus($contract->status),
                'status_color' => getContractStatusColor($contract->status),
                'started_at' => $statusActivity?->created_at?->copy() ?? Carbon::parse($contract->updated_at),
                'started_precision' => 'time',
                'notes' => 'انتقال مستنتج من سجل النشاط لعدم وجود سجل حالة مستقل',
                'performer' => $this->resolveContractStatusPerformer($contract, $contract->status),
                'is_inferred' => true,
            ]);
        }

        if ($logs->isEmpty()) {
            $fallbackStart = $contract->created_at ? Carbon::parse($contract->created_at) : now();

            $logs = collect([
                [
                    'status' => $contract->status,
                    'status_label' => getEnumValue('contract_statuses', $contract->status) ?? getContractStatus($contract->status),
                    'status_color' => getContractStatusColor($contract->status),
                    'started_at' => $fallbackStart,
                    'notes' => null,
                    'performer' => $this->resolveContractStatusPerformer($contract, $contract->status),
                    'is_inferred' => true,
                ],
            ]);
        }

        $now = now();
        $timelineItems = [];

        foreach ($logs as $index => $log) {
            $nextLog = $logs->get($index + 1);
            $startedAt = $log['started_at']->copy();
            $shouldTrackUntilNow = !$nextLog && $this->shouldTrackCurrentStageUntilNow($log['status']);
            $endedAt = $nextLog
                ? $nextLog['started_at']->copy()
                : ($shouldTrackUntilNow ? $now->copy() : $startedAt->copy());

            if ($endedAt->lt($startedAt)) {
                $endedAt = $startedAt->copy();
            }

            $durationInSeconds = max($startedAt->diffInSeconds($endedAt, false), 0);

            $timelineItems[] = [
                'status' => $log['status'],
                'status_label' => $log['status_label'],
                'status_color' => $log['status_color'],
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'started_at_formatted' => $this->formatResolvedStageDate($startedAt, $log['started_precision'] ?? 'time'),
                'ended_at_formatted' => $nextLog
                    ? $this->formatResolvedStageDate($endedAt, $nextLog['started_precision'] ?? 'time')
                    : ($shouldTrackUntilNow ? 'حتى الآن' : $this->formatResolvedStageDate($endedAt, $log['started_precision'] ?? 'time')),
                'duration_in_seconds' => $durationInSeconds,
                'duration_human' => $nextLog || $shouldTrackUntilNow
                    ? $this->formatTimelineDuration($durationInSeconds)
                    : 'مرحلة نهائية',
                'is_current' => !$nextLog,
                'is_active_stage' => $shouldTrackUntilNow,
                'notes' => $log['notes'],
                'performer_name' => $log['performer']['name'] ?? 'النظام',
                'performer_source' => $log['performer']['source'] ?? 'system',
                'is_inferred' => (bool) ($log['is_inferred'] ?? false),
            ];
        }

        $timelineItems = collect($timelineItems);
        $firstStage = $timelineItems->first();
        $currentStage = $timelineItems->last();
        $longestStage = $timelineItems->sortByDesc('duration_in_seconds')->first();

        return [
            'items' => $timelineItems,
            'started_at' => $firstStage['started_at'] ?? null,
            'started_at_formatted' => $firstStage['started_at_formatted'] ?? null,
            'total_duration' => $firstStage
                ? $this->formatTimelineDuration(
                    max($firstStage['started_at']->diffInSeconds($currentStage['ended_at'], false), 0)
                )
                : null,
            'current_stage_label' => $currentStage['status_label'] ?? null,
            'current_stage_duration' => $currentStage['duration_human'] ?? null,
            'longest_stage_label' => $longestStage['status_label'] ?? null,
            'longest_stage_duration' => $longestStage['duration_human'] ?? null,
        ];
    }

    private function resolveContractStageDate($log, Contract $contract): ?Carbon
    {
        return $this->resolveContractStageMoment($log, $contract)['at'] ?? null;
    }

    private function resolveContractStageMoment($log, Contract $contract): ?array
    {
        $loggedDate = $this->normalizeContractStageDate($log->date ?? null);
        $createdAt = $this->normalizeContractStageDate($log->created_at ?? null);

        if ($loggedDate && $createdAt) {
            if ($loggedDate->isSameDay($createdAt)) {
                return [
                    'at' => $createdAt->copy(),
                    'precision' => 'time',
                ];
            }

            return [
                'at' => $loggedDate->copy()->startOfDay(),
                'precision' => 'date',
            ];
        }

        if ($createdAt) {
            return [
                'at' => $createdAt->copy(),
                'precision' => 'time',
            ];
        }

        if ($loggedDate) {
            return [
                'at' => $loggedDate->copy()->startOfDay(),
                'precision' => 'date',
            ];
        }

        $contractCreatedAt = $this->normalizeContractStageDate($contract->created_at ?? null);

        if ($contractCreatedAt) {
            return [
                'at' => $contractCreatedAt->copy(),
                'precision' => 'time',
            ];
        }

        return null;
    }

    private function normalizeContractStageDate($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            $parsed = $value->copy();
        } else {
            $normalized = trim((string) $value);

            if ($normalized === '' || in_array($normalized, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                return null;
            }

            try {
                $parsed = Carbon::parse($normalized);
            } catch (\Throwable $exception) {
                return null;
            }
        }

        return (int) $parsed->year <= 1 ? null : $parsed;
    }

    private function formatResolvedStageDate(Carbon $date, string $precision = 'time'): string
    {
        return $precision === 'date'
            ? $date->format('Y-m-d')
            : $date->format('Y-m-d H:i');
    }

    private function formatContractStatusLogDate($log, Contract $contract): string
    {
        $resolvedDate = $this->resolveContractStageMoment($log, $contract);

        if (!$resolvedDate) {
            return 'غير محدد';
        }

        return $this->formatResolvedStageDate(
            $resolvedDate['at'],
            $resolvedDate['precision'] ?? 'time'
        );
    }

    private function shouldTrackCurrentStageUntilNow(string $status): bool
    {
        return !in_array($status, [
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::Refunded->value,
            ContractStatusEnum::Back->value,
            ContractStatusEnum::Closed->value,
            ContractStatusEnum::UnderWarranty->value,
            ContractStatusEnum::Sari->value,
        ], true);
    }

    private function formatTimelineDuration(int $durationInSeconds): string
    {
        if ($durationInSeconds < 60) {
            return 'أقل من دقيقة';
        }

        $totalMinutes = intdiv($durationInSeconds, 60);
        $days = intdiv($totalMinutes, 1440);
        $remainingMinutes = $totalMinutes % 1440;
        $hours = intdiv($remainingMinutes, 60);
        $minutes = $remainingMinutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $this->formatArabicDurationUnit($days, 'يوم', 'يومين', 'أيام');
        }

        if ($hours > 0) {
            $parts[] = $this->formatArabicDurationUnit($hours, 'ساعة', 'ساعتين', 'ساعات');
        }

        if ($days === 0 && $minutes > 0) {
            $parts[] = $this->formatArabicDurationUnit($minutes, 'دقيقة', 'دقيقتين', 'دقائق');
        }

        return implode(' و ', array_slice($parts, 0, 2));
    }

    private function formatArabicDurationUnit(int $value, string $single, string $dual, string $plural): string
    {
        if ($value === 1) {
            return $single . ' واحدة';
        }

        if ($value === 2) {
            return $dual;
        }

        return $value . ' ' . $plural;
    }

    private function buildContractDurationSummary(Contract $contract): array
    {
        $logs = $contract->contract_status_logs
            ->map(function ($log) use ($contract) {
                $resolvedAt = $this->resolveContractStageDate($log, $contract);

                if (!$resolvedAt) {
                    return null;
                }

                return [
                    'status' => $log->status,
                    'resolved_at' => $resolvedAt,
                ];
            })
            ->filter()
            ->sortBy(fn($log) => $log['resolved_at']->getTimestamp())
            ->values();

        $contractStartedAt = $this->resolveEffectiveContractStartDate($contract);
        $arrival = $this->resolveWorkerArrivalDetails($contract);
        $isFinalStatus = in_array($contract->status, [
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::Refunded->value,
            ContractStatusEnum::Back->value,
            ContractStatusEnum::Closed->value,
        ], true);
        $endedAt = $arrival['has_arrived'] && $arrival['date_time']
            ? $arrival['date_time']->copy()
            : $this->resolveContractDurationEndDate($contract, $logs);

        $totalDays = max($contractStartedAt->copy()->startOfDay()->diffInDays($endedAt->copy()->startOfDay(), false), 0);
        $msanedLog = $logs->firstWhere('status', ContractStatusEnum::Msaned->value);
        $msanedStartedAt = $msanedLog['resolved_at'] ?? null;
        $msanedDays = null;
        $msanedProgressLabel = null;
        $msanedBadgeClass = 'bg-success-subtle text-success';

        if ($msanedStartedAt) {
            $msanedDays = max($msanedStartedAt->copy()->startOfDay()->diffInDays($endedAt->copy()->startOfDay(), false), 0);

            if ($msanedDays < 90) {
                $remainingDays = 90 - $msanedDays;
                $msanedProgressLabel = 'مضى ' . $this->formatArabicDaysCount($msanedDays) . ' والمتبقي ' . $this->formatArabicDaysCount($remainingDays);
                $msanedBadgeClass = $remainingDays <= 15 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success';
            } elseif ($msanedDays === 90) {
                $msanedProgressLabel = 'اكتملت 90 يوم';
                $msanedBadgeClass = 'bg-warning-subtle text-warning';
            } else {
                $extraDays = $msanedDays - 90;
                $msanedProgressLabel = 'متجاوز ' . $this->formatArabicDaysCount($extraDays) . ' بعد 90 يوم';
                $msanedBadgeClass = 'bg-danger-subtle text-danger';
            }
        }

        return [
            'total_days' => $totalDays,
            'total_label' => $this->formatArabicDaysCount($totalDays),
            'started_at_label' => $contractStartedAt->format('Y-m-d'),
            'ended_at_label' => ($arrival['has_arrived'] || $isFinalStatus)
                ? $endedAt->format('Y-m-d')
                : 'حتى الآن',
            'worker_arrived' => $arrival['has_arrived'],
            'msaned_started' => (bool) $msanedStartedAt,
            'msaned_days' => $msanedDays,
            'msaned_days_label' => $msanedDays !== null ? $this->formatArabicDaysCount($msanedDays) : null,
            'msaned_progress_label' => $msanedProgressLabel,
            'msaned_badge_class' => $msanedBadgeClass,
        ];
    }

    private function resolveEffectiveContractStartDate(Contract $contract): Carbon
    {
        return ($this->normalizeContractStageDate($contract->info?->contract_date)
            ?: $this->normalizeContractStageDate($contract->contract_date)
            ?: $this->normalizeContractStageDate($contract->created_at)
            ?: now())->startOfDay();
    }

    private function resolveWorkerArrivalDetails(Contract $contract): array
    {
        $travelHistory = $contract->relationLoaded('travelHistory')
            ? $contract->travelHistory
            : $contract->travelHistory()->latest('id')->first();

        $hasArrived = $travelHistory?->status === 'completed'
            || in_array($contract->status, [
                ContractStatusEnum::Arrival->value,
                ContractStatusEnum::Completed->value,
                ContractStatusEnum::UnderWarranty->value,
                ContractStatusEnum::Closed->value,
            ], true);

        $arrivalDateTime = $travelHistory?->resolveArrivalDateTime();

        // بيانات العقد والعاملة تواريخ فعلية قديمة وليست موعداً مجدولاً.
        // لذلك لا تستخدم كـ "وصول متوقع" قبل اكتمال الرحلة أو بلوغ مرحلة الوصول.
        if ($hasArrived && !$arrivalDateTime && $contract->info?->arrival_date) {
            $arrivalDateTime = $this->normalizeContractStageDate($contract->info->arrival_date);
        }

        if ($hasArrived && !$arrivalDateTime && $contract->maid?->arrival_date) {
            $arrivalDateTime = $this->normalizeContractStageDate($contract->maid->arrival_date);
        }

        $hasExplicitArrivalTime = filled($travelHistory?->arrival_time)
            || (filled($travelHistory?->arrival_date)
                && preg_match('/\d{1,2}:\d{2}/', (string) $travelHistory->arrival_date));

        return [
            'has_arrived' => $hasArrived,
            'date_time' => $arrivalDateTime,
            'date_label' => $arrivalDateTime?->format('Y-m-d'),
            'time_label' => $arrivalDateTime && $hasExplicitArrivalTime
                ? $arrivalDateTime->format('H:i')
                : null,
            'station' => filled($travelHistory?->arrival_airport)
                ? trim((string) $travelHistory->arrival_airport)
                : (filled($contract->externalOfficeDispatch?->arrival_station)
                    ? trim((string) $contract->externalOfficeDispatch->arrival_station)
                    : null),
            'flight_company' => filled($travelHistory?->flight_company)
                ? trim((string) $travelHistory->flight_company)
                : null,
            'flight_number' => filled($travelHistory?->flight_number)
                ? trim((string) $travelHistory->flight_number)
                : null,
        ];
    }

    private function resolveContractDurationEndDate(Contract $contract, $logs): Carbon
    {
        $finalStatuses = [
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::Refunded->value,
            ContractStatusEnum::Back->value,
            ContractStatusEnum::Closed->value,
        ];

        if (in_array($contract->status, $finalStatuses, true)) {
            $matchingLog = collect($logs)->reverse()->first(function ($log) use ($contract) {
                return $log['status'] === $contract->status;
            });

            if (!empty($matchingLog['resolved_at'])) {
                return $matchingLog['resolved_at']->copy();
            }

            if ($contract->updated_at) {
                return Carbon::parse($contract->updated_at);
            }
        }

        return now();
    }

    private function formatArabicDaysCount(int $days): string
    {
        if ($days <= 0) {
            return '0 يوم';
        }

        return $this->formatArabicDurationUnit($days, 'يوم', 'يومين', 'أيام');
    }

    private function getExternalOfficeDispatchBadgeConfig(Contract $contract): array
    {
        $state = $contract->getExternalOfficeDispatchState();

        return match ($state['code']) {
            'completed' => [
                'class' => 'success',
                'label' => 'إرسالية المكتب: مكتملة',
                'message' => $state['message'],
            ],
            'outdated' => [
                'class' => 'warning text-dark',
                'label' => 'إرسالية المكتب: تحتاج تحديث',
                'message' => $state['message'],
            ],
            'incomplete' => [
                'class' => 'danger',
                'label' => 'إرسالية المكتب: غير مكتملة',
                'message' => $state['message'],
            ],
            default => [
                'class' => 'secondary',
                'label' => 'إرسالية المكتب: غير مكتملة',
                'message' => $state['message'],
            ],
        };
    }

    public function storeNote(Request $request, $contractId)
    {
        if (!$this->canUpdateRecruitmentContracts()) {
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
        if (!$this->canCreateRecruitmentContracts()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $orderId = request('orderId');
        logger()->info('Loading create page with orderId: ' . $orderId);

        $eligibleOrdersQuery = Order::query()
            ->select(['id', 'user_id', 'maid_id', 'job_type_id', 'status', 'created_at'])
            ->with([
                'user:id,name,phone,email,national_num,client_type,tax_number,license_number,birthdate,phone2,address,city,nationality_id,rooms_num,family_num,children,job',
                'maid:id,name,passport_num,nationality_id,religion_id,salary,experience_years,age',
                'jobType:id',
                'jobType.translations',
            ])
            ->where('job_type_id', 1)
            ->whereIn('status', $this->contractableRecruitmentOrderStatuses())
            ->whereDoesntHave('contracts')
            ->whereDoesntHave('rentalContracts')
            ->orderByDesc('created_at');

        // Load the selected order with all necessary relationships
        $selectedOrder = null;
        if ($orderId) {
            $selectedOrder = (clone $eligibleOrdersQuery)->find($orderId);
            logger()->info('Selected order loaded:', ['order' => $selectedOrder ? $selectedOrder->toArray() : null]);
        }

        // Only recruitment orders that are under process are eligible for contract creation.
        $orders = (clone $eligibleOrdersQuery)->get();

        // Large select2 lists are loaded lazily through AJAX to keep the create page fast.
        $endedOrders = collect();
        $all_available_maids = collect();
        $existing_clients = collect();

        // If we have a selected order and it's not in the new orders list, add it
        if ($selectedOrder && !$orders->contains('id', $selectedOrder->id)) {
            $orders->prepend($selectedOrder);
        }

        $countries = Country::active()->ordered()->get();
        $maidNationalities = Nationality::with('translations')->get();
        $maidReligions = Religion::with('translations')->get();
        $maidJobTitles = JobTitle::with('translations')->get();
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
            ? \App\Models\auth\Admin::query()
                ->activeForOperations()
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$currentUser?->id ?? 0])
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect([$currentUser])->filter();

        return view('admin.ingaz.RecruitmentContract.create', compact('orders', 'endedOrders', 'countries', 'maidNationalities', 'maidReligions', 'maidJobTitles', 'recruitmentOffices', 'saudiCities', 'all_available_maids', 'selectedOrder', 'orderId', 'existing_clients', 'currentUser', 'canSelectAnyMarketer', 'marketers'));
    }

    private function createMaidFromManualContractSummary(StoreContractRequest $request): Maid
    {
        $maidData = [
            'name' => trim((string) $request->manual_maid_name),
            'gender' => $request->manual_maid_gender ?: 'female',
            'passport_num' => trim((string) $request->manual_maid_passport_num),
            'nationality_id' => $request->manual_maid_nationality_id,
            'office_id' => $request->manual_maid_office_id,
            'religion_id' => Religion::query()->value('id'),
            'price' => 0,
            'salary' => 0,
            'age' => 0,
            'height' => '160',
            'weight' => '60',
            'status' => 'available',
            'available' => true,
            'available_at' => now()->toDateString(),
            'arrival_date' => now()->toDateString(),
            'is_show' => false,
            'experience_status' => 'no_experience',
            'experience_years' => '0',
            'request_type' => Maid::resolveRequestType(null),
            'added_by' => Auth::guard('admin')->id() ?? auth()->id(),
        ];

        if ($request->hasFile('manual_maid_attachment')) {
            $uploadedPath = uploadMaidFile($request->file('manual_maid_attachment'), 'maids/');
            $maidData['cv_file'] = $uploadedPath;

            $extension = strtolower((string) $request->file('manual_maid_attachment')->getClientOriginalExtension());
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'], true)) {
                $maidData['image'] = $uploadedPath;
            }
        }

        $maid = Maid::create($maidData);
        $maid->jobTypes()->syncWithoutDetaching([1]);
        if ($request->filled('manual_maid_job_title_id')) {
            $maid->jobTitles()->syncWithoutDetaching([(int) $request->manual_maid_job_title_id]);
        }

        return $maid;
    }


    public function store(StoreContractRequest $request)
    {
        // التحقق من صلاحية إنشاء العقد
        if (!$this->canCreateRecruitmentContracts()) {
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
        // المسوق مطلوب في StoreContractRequest، فلا يوجد رجوع تلقائي للمستخدم الحالي هنا.
        $marketerId = $canSelectAnyMarketer
            ? (int) $request->input('marketer_id')
            : (int) $currentAdmin->id;

        if (!\App\Models\auth\Admin::query()->activeForOperations()->whereKey($marketerId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اختيار موظف حالته معلق كمسؤول عن العقد'
            ], 422);
        }

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

            $manualMaid = null;
            if ($isManualMaidSummary) {
                $manualMaid = $this->createMaidFromManualContractSummary($request);
                $maidId = $manualMaid->id;
                $isNoMaid = false;
                $isMaidSelectionPostponed = false;
            }

            $shouldCreateWithoutAssignedMaid = $isNoMaid || $isMaidSelectionPostponed || $isManualMaidSummary;
            if ($manualMaid) {
                $shouldCreateWithoutAssignedMaid = false;
            }

            // تحديد حالة العقد
            $contractStatus = ContractStatusEnum::New->value;
            if ($shouldCreateWithoutAssignedMaid) {
                $contractStatus = ContractStatusEnum::PendingMaidSelection->value;
            }

            $accountClientType = $request->input('account_client_type') === 'company' ? 'company' : 'person';

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

                if (!$this->canCreateContractForOrder($order)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن إضافة عقد لهذا الطلب. يجب أن يكون طلب استقدام بدون عقد وفي حالة تحت الإجراء أو منتهية بدون عقد.'
                    ], 422);
                }

                $user = $order->user;
                app(\App\Services\ingaz\RecruitmentOrderClientCompletionService::class)->complete($user, $request);

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

                if (!$user) {
                    $documentNumber = $request->new_client_national_num ?? $request->client_national_num;
                    $userPayload = [
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'phone' => $request->client_phone,
                        'password' => bcrypt('123456'), // Default password
                        'nationality_id' => $request->client_nationality_id,
                        'client_type' => $accountClientType,
                        'email' => $request->new_client_email ?? $request->client_email,
                        'phone2' => $request->new_client_phone2 ?? $request->client_phone2,
                        'city' => $request->new_client_city ?? $request->client_city,
                        'beds_num' => $request->new_client_beds_num ?? $request->client_beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->client_family_num,
                        'children' => $request->new_client_children ?? $request->client_children,
                        'job' => $request->new_client_job ?? $request->client_job,
                        'address' => $request->new_client_address ?? $request->client_address,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate,
                    ];

                    if ($accountClientType === 'company') {
                        foreach (['national_num', 'birthdate', 'rooms_num', 'family_num', 'children', 'job'] as $field) {
                            unset($userPayload[$field]);
                        }

                        $userPayload['tax_number'] = $documentNumber;
                        $userPayload['license_number'] = $request->new_client_license_number ?? $request->client_license_number;
                    } else {
                        $userPayload['national_num'] = $documentNumber;
                        $userPayload['tax_number'] = null;
                        $userPayload['license_number'] = null;
                    }

                    $user = User::create($userPayload);
                    logger()->info('Created new user:', ['user' => $user->toArray()]);
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

            if (!$request->order_id) {
                $order = Order::create([
                    'maid_id' => $contractData['maid_id'],
                    'user_id' => $contractData['user_id'],
                    'payment_method' => 'cash',
                    'order_code' => Order::generateUniqueCode(),
                    'job_type_id' => 1,
                    'status' => OrderEnum::Completed->value,
                    'customer_id' => $marketerId,
                ]);
                $order->syncResponsibleAdmin($marketerId);
                $contractData['order_id'] = $order->id;
            }

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
                'manual_maid_name' => null,
                'manual_maid_passport_num' => null,
                'manual_maid_nationality_id' => null,
                'manual_maid_office_id' => null,
            ];

            if ($isManualMaidSummary && !$manualMaid && $request->hasFile('manual_maid_attachment')) {
                $contractInfo['manual_maid_attachment'] = app(ManagedFileStorage::class)->uploadFile(
                    $request->file('manual_maid_attachment'),
                    'contracts/manual_maid_attachments'
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
            if ($contract->maid_id && !$isNoMaid && !$isMaidSelectionPostponed) {
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
                $successMessage = 'تم إنشاء العقد بنجاح مع إنشاء عاملة مختصرة وربطها بالعقد.';
            } elseif ($isNoMaid || $isMaidSelectionPostponed) {
                $successMessage = 'تم إنشاء العقد بنجاح. يرجى اختيار العاملة لإكمال العقد.';
            }

            $nextUrl = $shouldCreateWithoutAssignedMaid
                ? route('RecruitmentContract.selectMaid', $contract->id)
                : null;

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'next_url' => $nextUrl,
                'next_action' => $shouldCreateWithoutAssignedMaid ? 'select_maid' : null,
                'contract_id' => $contract->id,
                'contract' => $contract->load(['user', 'maid', 'info'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'يرجى مراجعة بيانات العميل',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();

            logger()->error('Error creating contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'order_id' => $request->order_id,
                'client_type' => $request->client_type
            ]);

            if ($e->getCode() === '23000' && Str::contains($e->getMessage(), 'users_phone_unique')) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم جوال العميل مسجل مسبقاً في النظام، يرجى اختيار العميل الموجود أو إدخال رقم آخر',
                    'errors' => [
                        'client_phone' => [
                            'رقم جوال العميل مسجل مسبقاً في النظام، يرجى اختيار العميل الموجود أو إدخال رقم آخر',
                        ],
                    ],
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد'
            ], 500);
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
                'message' => 'حدث خطأ أثناء إنشاء العقد'
            ], 500);
        }
    }


    public function show($id, Request $request)
    {
        try {
            if (!$request->ajax() && !$request->expectsJson() && !$request->has('status') && !$request->has('next_status')) {
                return $this->details($id);
            }

            if (!$this->canViewRecruitmentContracts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لعرض العقد'
                ], 403);
            }

            $row = Contract::find($id);

            // التحقق من وجود العقد
            if (!$row) {
                logger()->warning('Contract not found', ['id' => $id, 'request' => $request->all()]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            $this->assertCanAccessBranchModel($row, 'contracts');

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
                $currentStatus = $row->status;
                $nextStatus = $request->next_status;
                $statusDate = $request->date ?? now()->format('Y-m-d');

                $expectedTransition = $this->getManualStatusTransition($currentStatus);
                if (!$expectedTransition || $nextStatus !== $expectedTransition['next_status']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المرحلة التالية المحددة لا تطابق المرحلة الحالية للعقد',
                    ], 422);
                }

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

                app(RecruitmentContractStageStepService::class)->assertStageComplete($row);

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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
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

        $this->assertCanAccessBranchModel($contract, 'contracts');

        // الإرجاع خطوة واحدة فقط ضمن تسلسل المراحل، مع فحص القيود المالية المرتبطة بالعقد.
        try {
            $rolledBack = app(RecruitmentContractStageRollbackService::class)
                ->rollback($contract, Auth::guard('admin')->id());

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع العقد بنجاح إلى ' . getContractStatus($rolledBack->status),
                'status' => $rolledBack->status,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first() ?: $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $e) {
            logger()->error('Error in back contract', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرجاع العقد. لم يتم إجراء أي تغيير.'
            ], 500);
        }
    }

    public function edit($id)
    {
        // التحقق من صلاحية تعديل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقد');
        }

        $contract = Contract::with(['user', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info', 'order.details.admin', 'marketer'])->find($id);

        if (!$contract) {
            return redirect()->back()->with('error', 'العقد غير موجود');
        }

        $this->assertCanAccessBranchModel($contract, 'contracts');

        // منع تعديل العقود المكتملة
        if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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

        $countries = Country::active()->ordered()->get();
        $defaultClientNationalityId = optional($countries->firstWhere('code', 'SA'))->id;
        $saudiCities = City::query()
            ->whereHas('country', function ($query) {
                $query->where('code', 'SA');
            })
            ->active()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en']);
        $maidNationalities = Nationality::with('translations')->get();
        $maidReligions = Religion::with('translations')->get();
        $maidJobTitles = JobTitle::with('translations')->get();
        $currentAdmin = Auth::guard('admin')->user();
        $currentUser = $currentAdmin;
        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentAdmin);
        $marketers = $canSelectAnyMarketer
            ? \App\Models\auth\Admin::query()
                ->activeForOperations()
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$currentAdmin?->id ?? 0])
                ->orderBy('name')
                ->get()
            : collect([$currentAdmin])->filter();

        return view('admin.ingaz.RecruitmentContract.edit', compact('contract', 'countries', 'defaultClientNationalityId', 'saudiCities', 'maidNationalities', 'maidReligions', 'maidJobTitles', 'currentAdmin', 'currentUser', 'canSelectAnyMarketer', 'marketers'));
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

        $contract = Contract::with(['user', 'order'])->find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        $canSelectAnyMarketer = $this->canSelectAnyMarketer($currentAdmin);
        $defaultClientNationalityId = Country::query()->where('code', 'SA')->value('id');
        $resolvedMarketerId = $contract->marketer_id ?: $contract->order?->resolveResponsibleAdminId();
        $fallbackMarketerId = $resolvedMarketerId && \App\Models\auth\Admin::query()->activeForOperations()->whereKey($resolvedMarketerId)->exists()
            ? $resolvedMarketerId
            : $currentAdmin->id;
        $allowedMarketerIds = array_values(array_unique(array_filter([
            $currentAdmin->id,
            $fallbackMarketerId,
        ])));

        $request->merge([
            'account_client_type' => $request->input('account_client_type') === 'company' ? 'company' : 'person',
            'client_nationality_id' => $request->input('client_nationality_id') ?: $defaultClientNationalityId,
            // بدون رجوع تلقائي للمستخدم الحالي: المسوق اختيار صريح مطلوب حتى عند التعديل.
            'marketer_id' => $request->input('marketer_id') ?: null,
        ]);

        $hasClient = (bool) $contract->user;
        $marketerRules = [
            'required',
            Rule::exists('admins', 'id')->where(function ($query) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')
                        ->orWhere('status', '!=', 'suspended');
                });
            }),
        ];
        if (!$canSelectAnyMarketer) {
            $marketerRules[] = Rule::in($allowedMarketerIds);
        }
        $request->validate([
            'client_name' => ($hasClient ? 'required' : 'nullable') . '|string|max:255',
            'client_phone' => ($hasClient ? 'required' : 'nullable') . '|string|max:50',
            'client_nationality_id' => 'nullable|exists:countries,id',
            'client_email' => 'nullable|email',
            'account_client_type' => 'nullable|in:person,company',
            'client_national_num' => 'nullable|string|max:100',
            'client_license_number' => 'nullable|string|max:100',
            'client_birthdate' => 'nullable|string|max:50',
            'phone2' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'beds_num' => 'nullable',
            'family_num' => 'nullable',
            'children' => 'nullable|integer|min:0|max:50',
            'job' => 'nullable|string|max:255',
            'maid_name' => 'nullable|string|max:255',
            'maid_passport' => 'nullable|string|max:100',
            'maid_salary' => 'nullable',
            'contract_date' => 'required',
            'msaned_number' => 'required|string|max:255',
            'visa_number' => 'required|string|max:255',
            'tafwed_number' => 'nullable|string|max:255',
            'visa_milad_date' => 'nullable|date',
            'visa_hijry_date' => 'nullable|date',
            'contract_duration' => 'required|integer|min:1',
            'status' => ['nullable', Rule::in(array_column(ContractStatusEnum::cases(), 'value'))],
            'marketer_id' => $marketerRules,
            'required_maid_nationality' => 'nullable|exists:nationalities,id',
            'required_maid_religion' => 'nullable|exists:religions,id',
            'required_maid_age_min' => 'nullable|integer|min:18|max:80',
            'required_maid_age_max' => 'nullable|integer|min:18|max:80',
            'required_maid_experience_min' => 'nullable|integer|min:0|max:50',
            'required_maid_job_title' => 'nullable|exists:job_titles,id',
            'required_maid_notes' => 'nullable|string|max:2000',
        ]);

        $marketerId = (int) $request->marketer_id;

        // منع تحديث حالة العقود المكتملة
        if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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
                $accountClientType = $request->input('account_client_type') === 'company' ? 'company' : 'person';
                $contract->user->client_type = $accountClientType;

                $contract->user->update($contract->user->clientTypeAwarePayload([
                    'client_type' => $accountClientType,
                    'name' => $request->client_name,
                    'phone' => $request->client_phone,
                    'email' => $request->client_email,
                    'nationality_id' => $request->client_nationality_id,
                    'license_number' => $accountClientType === 'company' ? $request->client_license_number : null,
                    'tax_number' => $accountClientType === 'person' ? null : $contract->user->tax_number,
                    'birthdate' => $request->client_birthdate,
                    'phone2' => $request->phone2,
                    'address' => $request->address,
                    'city' => $request->city,
                    'rooms_num' => $accountClientType === 'company' ? null : $request->beds_num,
                    'beds_num' => $accountClientType === 'company' ? null : $request->beds_num,
                    'family_num' => $request->family_num,
                    'children' => $request->children,
                    'job' => $request->job,
                ], $request->client_national_num));
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

            // لا يتم تعديل حالة العقد من شاشة تعديل البيانات.
            $contract->update([
                'contract_date' => $request->contract_date,
                'marketer_id' => $marketerId,
                'required_maid_nationality_id' => $request->required_maid_nationality,
                'required_maid_religion_id' => $request->required_maid_religion,
                'required_maid_age_min' => $request->required_maid_age_min,
                'required_maid_age_max' => $request->required_maid_age_max,
                'required_maid_experience_min' => $request->required_maid_experience_min,
                'required_maid_job_title_id' => $request->required_maid_job_title,
                'required_maid_notes' => $request->required_maid_notes,
            ]);

            // تحديث حالة العاملات تلقائياً
            if ($contract->maid_id) {
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    // التحقق من حالة العقد
                    $isActiveContract = !in_array($contract->status, ['back', 'closed', 'completed']);

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
            return response()->json(['success' => false, 'message' => 'العقد غير موجود'], 404);
        }
        $this->assertCanAccessBranchModel($contract, 'contracts');

        // لا يمكن حذف عقد ترتبت عليه فاتورة، بغض النظر عن حالة العقد أو الفاتورة.
        if ($contract->invoice()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد لوجود فاتورة مرتبطة به'
            ], 400);
        }

        // لا يمكن حذف عقد له سند مالي (قبض/صرف) مرتبط به.
        if (Schema::hasColumn('fn_bonds', 'recruitment_contract_id')
            && DB::table('fn_bonds')->where('recruitment_contract_id', $contract->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد لوجود سند مالي مرتبط به'
            ], 400);
        }

        // لا يمكن حذف عقد بدأ له إجراء ترحيل.
        if ($contract->deportationProcedure()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد لوجود إجراء ترحيل مرتبط به'
            ], 400);
        }

        // منع حذف العقود المكتملة
        if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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

        DB::transaction(function () use ($contract, $request) {
            // التحقق من وجود عاملة في العقد
            if ($contract->maid_id) {
                $maidStatus = $request->input('maid_status', 'available');

                // تحديث حالة العاملة
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    if ($maidStatus === 'back_out') {
                        // القيمة المخزنة في النظام هي 'backout' (بلا شرطة سفلية).
                        $maid->update([
                            'status' => 'backout',
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

            // Some legacy/imported databases do not have the original cascade
            // constraint, so remove the dependent data explicitly.
            ContractInfo::where('contract_id', $contract->id)->delete();
            $contract->delete();
        });

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

    public function searchClients(Request $request)
    {
        if (!$this->canCreateRecruitmentContracts()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $search = trim((string) $request->input('term', $request->input('q', '')));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $clients = User::query()
            ->select([
                'id',
                'name',
                'phone',
                'email',
                'nationality_id',
                'national_num',
                'client_type',
                'tax_number',
                'license_number',
                'birthdate',
                'phone2',
                'address',
                'city',
                'rooms_num',
                'family_num',
                'children',
                'job',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_num', 'like', "%{$search}%")
                        ->orWhere('tax_number', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $clients->getCollection()->map(function ($client) {
                $documentNumber = $client->searchableDocumentNumber();
                $label = collect([$client->name, $client->phone, $documentNumber])
                    ->filter()
                    ->implode(' - ');

                return [
                    'id' => $client->id,
                    'text' => $label ?: ('عميل #' . $client->id),
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'email' => $client->email,
                    'nationality' => $client->nationality_id,
                    'national_num' => $documentNumber,
                    'document_number' => $documentNumber,
                    'document_label' => $client->documentNumberLabel(),
                    'client_type' => $client->client_type,
                    'tax_number' => $client->tax_number,
                    'license_number' => $client->license_number,
                    'birthdate' => $client->birthdate,
                    'phone2' => $client->phone2,
                    'address' => $client->address,
                    'city' => $client->city,
                    'beds_num' => $client->rooms_num,
                    'family_num' => $client->family_num,
                    'children_count' => $client->children,
                    'job' => $client->job,
                ];
            })->values(),
            'pagination' => [
                'more' => $clients->hasMorePages(),
            ],
        ]);
    }

    private function recruitmentContractExportQuery(Request $request)
    {
        $query = Contract::query()->with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'maid.office.nationality',
            'info.manualMaidNationality',
            'info.manualMaidOffice.nationality',
            'marketer',
            'order.details.admin',
            'order.customer',
            'sponsorshipTransfers.oldSponsor',
        ]);

        $query->whereIn('type', ['recruitment', 'maid']);
        $this->excludeShelterContracts($query);

        $this->applyContractFilters($query, $request);

        return $query;
    }

    /** Keep both export formats aligned whenever contract columns are changed. */
    private function recruitmentContractExportColumns(): array
    {
        return [
            'contract_number' => 'رقم العقد',
            'order_number' => 'رقم الطلب',
            'client_name' => 'اسم العميل',
            'client_document' => 'رقم الهوية / السجل التجاري',
            'client_phone' => 'رقم هاتف العميل',
            'client_email' => 'البريد الإلكتروني للعميل',
            'maid_name' => 'اسم العاملة',
            'maid_passport' => 'رقم جواز العاملة',
            'maid_nationality' => 'جنسية العاملة',
            'maid_religion' => 'ديانة العاملة',
            'maid_job' => 'مهنة العاملة',
            'external_office' => 'الوكيل / المكتب الخارجي',
            'external_office_nationality' => 'دولة الوكيل الخارجي',
            'external_office_phone' => 'هاتف الوكيل الخارجي',
            'external_office_license' => 'رقم ترخيص الوكيل الخارجي',
            'external_office_manager' => 'مدير الوكيل الخارجي',
            'external_office_manager_phone' => 'هاتف مدير الوكيل الخارجي',
            'external_office_manager_email' => 'بريد مدير الوكيل الخارجي',
            'external_office_address' => 'عنوان الوكيل الخارجي',
            'visa_number' => 'رقم التأشيرة',
            'visa_milad_date' => 'تاريخ التأشيرة الميلادي',
            'visa_hijry_date' => 'تاريخ التأشيرة الهجري',
            'msaned_number' => 'رقم عقد مساند',
            'tafwed_number' => 'رقم التفويض',
            'contract_date' => 'تاريخ العقد',
            'contract_duration' => 'مدة العقد',
            'contract_value' => 'قيمة العقد',
            'working_hours' => 'ساعات العمل',
            'ingaz_from' => 'تاريخ البداية',
            'ingaz_to' => 'تاريخ النهاية',
            'arrival_date' => 'تاريخ الوصول',
            'marketer' => 'المسوق',
            'status' => 'الحالة',
            'created_at' => 'تاريخ إنشاء العقد',
        ];
    }

    private function recruitmentContractExportRow(Contract $contract): array
    {
        $info = $contract->info;
        $maid = $contract->maid;
        $office = $maid?->office ?? $maid?->recruitmentOffice ?? $info?->manualMaidOffice;
        $marketer = $contract->marketer ?? $contract->order?->resolveResponsibleAdmin();

        return [
            'contract_number' => (string) $contract->id,
            'order_number' => (string) ($contract->order?->order_code ?: $contract->order_id ?: ''),
            'client_name' => (string) ($contract->user?->name ?? ''),
            'client_document' => (string) ($contract->user?->documentNumber() ?? ''),
            'client_phone' => (string) (($contract->user?->phone_code ?? '') . ($contract->user?->phone ?? '')),
            'client_email' => (string) ($contract->user?->email ?? ''),
            'maid_name' => (string) ($maid?->name ?? $info?->manual_maid_name ?? ''),
            'maid_passport' => (string) ($maid?->passport_num ?? $info?->manual_maid_passport_num ?? ''),
            'maid_nationality' => (string) ($maid?->nationality?->sub_title ?? $maid?->nationality?->title ?? $info?->manualMaidNationality?->sub_title ?? $info?->manualMaidNationality?->title ?? ''),
            'maid_religion' => (string) ($maid?->religion?->title ?? ''),
            'maid_job' => (string) ($maid?->jobTitles?->first()?->title ?? ''),
            'external_office' => (string) ($office?->title ?? ''),
            'external_office_nationality' => (string) ($office?->nationality?->sub_title ?? $office?->nationality?->title ?? ''),
            'external_office_phone' => (string) ($office?->phone_office ?? ''),
            'external_office_license' => (string) ($office?->license_number ?? ''),
            'external_office_manager' => (string) ($office?->manager_name ?? ''),
            'external_office_manager_phone' => (string) ($office?->manager_phone ?? ''),
            'external_office_manager_email' => (string) ($office?->manager_email ?? ''),
            'external_office_address' => (string) ($office?->street ?? ''),
            'visa_number' => (string) ($info?->visa_number ?: $contract->visa_number ?: ''),
            'visa_milad_date' => (string) ($info?->visa_milad_date ?? ''),
            'visa_hijry_date' => (string) ($info?->visa_hijry_date ?? ''),
            'msaned_number' => (string) ($info?->msaned_number ?: $contract->msaned_number ?: ''),
            'tafwed_number' => (string) ($info?->tafwed_number ?: $contract->authorization_num ?: ''),
            'contract_date' => (string) ($info?->contract_date ?: $contract->contract_date ?: ''),
            'contract_duration' => (string) ($info?->contract_duration ?? ''),
            'contract_value' => (string) ($info?->contract_value ?? $contract->contract_cost ?? ''),
            'working_hours' => (string) ($info?->working_hours_num ?? ''),
            'ingaz_from' => (string) ($info?->ingaz_from ?? ''),
            'ingaz_to' => (string) ($info?->ingaz_to ?? ''),
            'arrival_date' => (string) ($info?->arrival_date ?? $maid?->arrival_date ?? ''),
            'marketer' => (string) ($marketer?->name ?? ''),
            'status' => (string) getEnumValue('contract_statuses', $contract->status),
            'created_at' => $contract->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function exportPdf(Request $request)
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        // Read records in small batches so large tenants do not exhaust PHP memory.
        $contracts = $this->recruitmentContractExportQuery($request)->lazyById(250);

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
                    @page {
                        size: landscape;
                    }
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
                    <tr>';

        foreach ($this->recruitmentContractExportColumns() as $heading) {
            $html .= '<th>' . e($heading) . '</th>';
        }

        $html .= '</tr>
                </thead>
                <tbody>';

        foreach ($contracts as $contract) {
            $html .= '<tr>';
            foreach ($this->recruitmentContractExportRow($contract) as $value) {
                $html .= '<td>' . e($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="contracts.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        // Keep only one batch of Eloquent models in memory while building the sheet.
        $contracts = $this->recruitmentContractExportQuery($request)->lazyById(250);

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = array_values($this->recruitmentContractExportColumns());

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
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($contracts as $contract) {
            foreach (array_values($this->recruitmentContractExportRow($contract)) as $index => $value) {
                $sheet->setCellValueExplicit(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $row,
                    (string) $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
            $row++;
        }

        // Auto-size columns
        for ($columnIndex = 1; $columnIndex <= count($headers); $columnIndex++) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex)
            )->setAutoSize(true);
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

    public function completeTrip(
        Request $request,
        RecruitmentTripAccountingService $tripAccountingService,
        RentalMaidArrivalService $rentalMaidArrivalService,
        $tripId = null
    )
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

            $dispatchError = RecruitmentContractExternalOfficeDispatchController::ensureDispatchCompleted($contract);
            if ($dispatchError) {
                return response()->json([
                    'success' => false,
                    'message' => $dispatchError
                ], 422);
            }

            $accountingResult = null;

            DB::beginTransaction();

            try {
                app(RecruitmentContractStageStepService::class)->assertStageComplete($contract);
                $travelHistory->normalizeArrivalDateAndTime();

                RecruitmentContractExternalOfficeDispatchController::syncArrivalStationFromDispatch($contract, $travelHistory);

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

                $accountingResult = $tripAccountingService->recordCompletionEntries($contract, $travelHistory);
                $movedRentalMaidToShelter = $rentalMaidArrivalService->moveToShelter($contract, $travelHistory);

                DB::commit();

                logger()->info('Trip completed and contract status updated', [
                    'trip_id' => $tripId,
                    'contract_id' => $contract->id,
                    'warranty_status' => $contract->warranty_status,
                    'rental_maid_moved_to_shelter' => $movedRentalMaidToShelter,
                ]);

                $message = $contract->status === ContractStatusEnum::Completed->value
                    ? 'تم إكمال الرحلة وتحديث حالة العقد إلى مكتمل بنجاح'
                    : 'تم إكمال الرحلة وتحديث حالة العقد إلى مرحلة الوصول وتفعيل الضمان بنجاح';

                if ($accountingResult['supplier_purchase_id'] ?? null) {
                    $message .= ' وتم إنشاء فاتورة المورد #'
                        . ($accountingResult['supplier_purchase_code'] ?: $accountingResult['supplier_purchase_id'])
                        . ' وقيد الإغلاق #'
                        . ($accountingResult['external_office_cost_constraint_code'] ?: $accountingResult['external_office_cost_constraint_id'])
                        . '.';
                } elseif (($accountingResult['created_revenue_constraint'] ?? false) || ($accountingResult['created_external_office_cost_constraint'] ?? false)) {
                    $message .= ' وتم إنشاء القيود المحاسبية المرتبطة بالفاتورة.';
                } elseif (($accountingResult['skipped_reason'] ?? null) === 'missing_invoice') {
                    $message .= ' لم يتم إنشاء القيود المحاسبية لعدم وجود فاتورة على العقد.';
                } elseif (($accountingResult['skipped_reason'] ?? null) === 'automatic_entries_disabled') {
                    $message .= ' لم يتم إنشاء قيود محاسبية لأن القيود التلقائية غير مفعلة من إعدادات الحسابات.';
                }

                if ($movedRentalMaidToShelter) {
                    $message .= ' وتمت إضافة العاملة إلى الإيواء وإتاحتها للتأجير.';
                }

                $accountingWarnings = array_values(array_unique($accountingResult['warnings'] ?? []));
                if (!empty($accountingWarnings)) {
                    $message .= ' تنبيه: ' . implode(' ', $accountingWarnings);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'accounting_warnings' => $accountingWarnings,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
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
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL) || !@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE)) {
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

            if (!in_array($contract->status, [ContractStatusEnum::Taffez->value, ContractStatusEnum::Tazker->value], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'إضافة الرحلة متاحة فقط في مرحلة التفييز أو التذاكر'
                ], 422);
            }

            // التحقق من عدم وجود رحلة نشطة للعقد
            $existingTravel = TravelHistory::where('order_id', $contractId)->first();
            if ($existingTravel) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد رحلة نشطة بالفعل لهذا العقد'
                ], 400);
            }

            $movedToTickets = false;

            DB::transaction(function () use ($contract, $contractId, &$movedToTickets) {
                if ($contract->status === ContractStatusEnum::Taffez->value) {
                    app(RecruitmentContractStageStepService::class)->assertStageComplete($contract);
                }

                TravelHistory::create([
                    'order_id' => $contractId,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if ($contract->status === ContractStatusEnum::Taffez->value) {
                    $contract->update(['status' => ContractStatusEnum::Tazker->value]);
                    $movedToTickets = true;
                    $contract->contract_status_logs()->create([
                        'status' => ContractStatusEnum::Tazker->value,
                        'date' => now()->toDateString(),
                        'notes' => 'تم إضافة رحلة للعقد ونقله تلقائياً من التفييز إلى مرحلة التذاكر',
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => $movedToTickets
                    ? 'تم إضافة الرحلة بنجاح ونقل العقد إلى مرحلة التذاكر'
                    : 'تم إضافة الرحلة بنجاح'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
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

    /**
     * زر الإرجاع خطوة واحدة ضمن تسلسل المراحل (نفس زر «إرجاع إلى» في قائمة الإجراءات).
     */
    private function renderStageRollbackButton(Contract $row): string
    {
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
            return '';
        }

        $backStatus = app(RecruitmentContractStageRollbackService::class)->previousStatus($row);
        if (!$backStatus) {
            return '';
        }

        $backStatusLabel = e(getContractStatus($backStatus));

        return '<button type="button" class="btn btn-outline-warning btn-sm backContract btn-action" data-row-id="' . $row->id . '" data-target-status="' . e($backStatus) . '" data-target-label="' . $backStatusLabel . '" title="إرجاع خطوة واحدة إلى ' . $backStatusLabel . '">
                    <i class="fas fa-undo me-1"></i>إرجاع إلى ' . $backStatusLabel . '
                </button>';
    }


    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'row_ids' => 'required|array|min:1',
                'row_ids.*' => 'integer|exists:contracts,id',
                'date' => 'nullable|date',
            ]);

            $rowIds = collect($request->input('row_ids', []))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $date = $request->date;
            $sendSms = filter_var($request->input('send_sms'), FILTER_VALIDATE_BOOLEAN);
            $contracts = Contract::with(['user', 'travelHistory'])
                ->whereIn('id', $rowIds)
                ->get()
                ->keyBy('id');

            $updatedCount = 0;
            $skippedCount = 0;
            $smsSentCount = 0;
            $updatedContracts = [];
            $skippedContracts = [];

            foreach ($rowIds as $rowId) {
                $contract = $contracts->get($rowId);

                if (!$contract) {
                    $skippedCount++;
                    $skippedContracts[] = [
                        'id' => $rowId,
                        'message' => 'العقد غير موجود',
                    ];
                    continue;
                }

                try {
                    $actionContext = $this->resolveBulkActionContext($contract);

                    if (!$actionContext['can_select']) {
                        $skippedCount++;
                        $skippedContracts[] = [
                            'id' => $contract->id,
                            'message' => $actionContext['reason'] ?? 'تعذر تحديث العقد المحدد',
                        ];
                        continue;
                    }

                    if (($actionContext['action_type'] ?? null) !== 'status_update' || empty($actionContext['next_status'])) {
                        $skippedCount++;
                        $skippedContracts[] = [
                            'id' => $contract->id,
                            'message' => 'العقد المحدد لا يدعم التحديث الجماعي من هذه الشاشة',
                        ];
                        continue;
                    }

                    $statusUpdate = $this->updateContractStatusRecord($contract, $actionContext['next_status'], $date);
                    $smsSent = false;

                    if ($sendSms) {
                        $smsSent = $this->sendContractStatusSMS($contract, $actionContext['next_status']);
                        if ($smsSent) {
                            $smsSentCount++;
                        }
                    }

                    $updatedCount++;
                    $updatedContracts[] = array_merge([
                        'id' => $contract->id,
                        'sms_sent' => $smsSent,
                    ], $statusUpdate);
                } catch (\Throwable $contractException) {
                    logger()->error('Error updating contract during bulkUpdateStatus', [
                        'rowId' => $rowId,
                        'message' => $contractException->getMessage(),
                        'trace' => $contractException->getTraceAsString(),
                    ]);

                    $skippedCount++;
                    $skippedContracts[] = [
                        'id' => $rowId,
                        'message' => $contractException instanceof ValidationException
                            ? (collect($contractException->errors())->flatten()->first() ?: $contractException->getMessage())
                            : 'حدث خطأ أثناء تحديث هذا العقد',
                    ];
                }
            }

            $message = $updatedCount > 0
                ? 'تم تحديث ' . $updatedCount . ' عقد بنجاح'
                : 'لم يتم تحديث أي عقد';

            if ($skippedCount > 0) {
                $message .= ' وتعذر تحديث ' . $skippedCount . ' عقد';
            }

            if ($sendSms && $smsSentCount > 0) {
                $message .= ' وتم إرسال ' . $smsSentCount . ' إشعار';
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
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

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'row_id' => 'required|integer|exists:contracts,id',
                'next_status' => 'required|string',
                'date' => 'nullable|date',
            ]);

            $rowId = (int) $request->row_id;
            $nextStatus = (string) $request->next_status;
            $date = $request->date;
            $sendSms = filter_var($request->input('send_sms'), FILTER_VALIDATE_BOOLEAN);
            $contract = Contract::with('user')->findOrFail($rowId);

            if (!$nextStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد الحالة الجديدة للعقد'
                ], 422);
            }

            if ($nextStatus === ContractStatusEnum::Closed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'إغلاق العقد يتم تلقائياً بعد انتهاء فترة الضمان'
                ], 400);
            }

            if ($contract->status === ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد في حالة الانتظار ولا يمكن تغيير حالته'
                ], 400);
            }

            if ($contract->status === $nextStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحالة الجديدة مطابقة للحالة الحالية'
                ], 422);
            }

            if ($contract->status === ContractStatusEnum::Refunded->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد مسترد ومكتمل ولا يمكن تغيير حالته',
                ], 422);
            }

            if ($contract->status === ContractStatusEnum::Completed->value) {
                // العقد المكتمل مرتبط بقيود وفواتير؛ لا يقبل إلا الإرجاع خطوة واحدة إلى الوصول.
                $rollbackService = app(RecruitmentContractStageRollbackService::class);
                $previousStatus = $rollbackService->previousStatus($contract);

                if ($nextStatus !== $previousStatus) {
                    return response()->json([
                        'success' => false,
                        'message' => 'العقد مكتمل ولا يمكن نقله إلا إلى المرحلة السابقة مباشرة (' . getContractStatus($previousStatus) . ') عبر زر الإرجاع',
                    ], 422);
                }

                if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية لإرجاع العقد'
                    ], 403);
                }

                try {
                    $rolledBack = $rollbackService->rollback($contract, Auth::guard('admin')->id(), $date);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => collect($exception->errors())->flatten()->first() ?: $exception->getMessage(),
                        'errors' => $exception->errors(),
                    ], 422);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إرجاع العقد من ' . getContractStatus($contract->status) . ' إلى ' . getContractStatus($rolledBack->status),
                    'contract_id' => $contract->id,
                    'current_status' => $contract->status,
                    'next_status' => $rolledBack->status,
                    'sms_sent' => false,
                ]);
            } else {
                $transitionContext = $this->resolveContractTransitionContext($contract);

                if (!$transitionContext['next_status']) {
                    return response()->json([
                        'success' => false,
                        'message' => $transitionContext['reason']
                    ], $transitionContext['http_status']);
                }

                if ($nextStatus !== $transitionContext['next_status']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المرحلة التالية المحددة لا تطابق المرحلة الحالية للعقد'
                    ], 422);
                }

                if (!$transitionContext['can_update']) {
                    return response()->json([
                        'success' => false,
                        'message' => $transitionContext['reason']
                    ], $transitionContext['http_status']);
                }
            }

            $statusUpdate = $this->updateContractStatusRecord($contract, $nextStatus, $date);

            $smsResult = false;
            if ($sendSms) {
                $smsResult = $this->sendContractStatusSMS($contract, $nextStatus);
            }

            $message = 'تم تحديث الحالة بنجاح من '
                . $statusUpdate['current_status_text']
                . ' إلى '
                . $statusUpdate['next_status_text'];

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
                'contract_id' => $contract->id,
                'current_status' => $statusUpdate['current_status'],
                'next_status' => $statusUpdate['next_status'],
                'sms_sent' => $smsResult,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            logger()->error('Error in updateStatus', [
                'rowId' => $request->input('row_id'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب العاملات المتاحة
     */
    public function releaseMaidAfterCompletion(
        Contract $contract,
        CompletedRecruitmentMaidReleaseService $service
    ) {
        abort_unless(
            @AdminGuardHasPerm(NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION),
            403,
            'ليس لديك صلاحية إتاحة العاملة بعد اكتمال العقد.'
        );

        try {
            $maid = $service->release($contract, auth('admin')->id());

            return redirect()
                ->route('RecruitmentContract.index', ['status' => 'completed_stage'])
                ->with('success', 'تم اكتمال العقد وتسفير العاملة وإتاحة ' . ($maid->name ?: ('العاملة #' . $maid->id)) . ' لعقد استقدام جديد.');
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('RecruitmentContract.index', ['status' => 'completed_stage'])
                ->with('error', $exception->getMessage());
        }
    }

    public function getAvailableMaids(Request $request)
    {
        logger()->info('getAvailableMaids called', ['request' => $request->all()]);

        try {
            if (!$this->canSelectMaidForRecruitmentContracts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لعرض العاملات المتاحات لعقود الاستقدام'
                ], 403);
            }

            $query = Maid::query()
                ->select([
                    'id',
                    'name',
                    'passport_num',
                    'id_number',
                    'nationality_id',
                    'religion_id',
                    'office_id',
                    'salary',
                    'experience_years',
                    'age',
                    'available',
                    'status',
                    'is_show',
                    'available_at',
                ])
                ->with(['nationality', 'religion', 'jobTitles', 'recruitmentOffice'])
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
                    $q->whereNotIn('status', Order::nonBlockingMaidAssignmentStatuses());
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

            if ($request->boolean('select2')) {
                $query
                    ->where('is_show', true)
                    ->where('available_at', '<=', now()->toDateString())
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->whereDoesntHave('contracts', function ($q) {
                        $q->whereNotIn('status', ['back', 'closed', 'completed']);
                    })
                    ->whereDoesntHave('orders', function ($q) {
                        $q->whereIn('status', ['pending', 'approved', 'in_progress']);
                    });

                $page = max((int) $request->input('page', 1), 1);
                $maids = $query->latest()->paginate(10, ['*'], 'page', $page);

                return response()->json([
                    'results' => $maids->getCollection()->map(function ($maid) {
                        $label = collect([$maid->name, $maid->passport_num, $maid->nationality?->sub_title])
                            ->filter()
                            ->implode(' - ');

                        return [
                            'id' => $maid->id,
                            'text' => $label ?: ('عاملة #' . $maid->id),
                            'name' => $maid->name,
                            'passport' => $maid->passport_num,
                            'religion' => $maid->religion?->title,
                            'job' => $maid->jobTitles->first()?->title,
                            'nationality' => $maid->nationality?->sub_title,
                            'office' => $maid->recruitmentOffice?->title,
                            'salary' => $maid->salary,
                            'experience' => $maid->experience_years,
                            'age' => $maid->age,
                        ];
                    })->values(),
                    'pagination' => [
                        'more' => $maids->hasMorePages(),
                    ],
                ]);
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
        if (!$this->canViewRecruitmentContracts()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض خيارات الفلترة'
            ], 403);
        }

        // استخدام cache لتسريع التحميل
        return \Cache::remember('recruitment_contract_filter_options.v3', 300, function () { // 5 دقائق
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
                'marketers' => \App\Models\auth\Admin::query()
                    ->whereIn('id', Contract::query()
                        ->whereIn('type', ['recruitment', 'maid'])
                        ->whereNotNull('marketer_id')
                        ->select('marketer_id'))
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),
            ];
        });
    }

    /**
     * إضافة عاملة للعقد
     */
    public function addMaidToContract(Request $request, $contractId)
    {
        try {
            if (!$this->canSelectMaidForRecruitmentContracts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لاختيار عاملة للعقد'
                ], 403);
            }

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
            if (!$this->canSelectMaidForRecruitmentContracts()) {
                return redirect()->route('RecruitmentContract.index')
                    ->with('error', 'ليس لديك صلاحية لاختيار عاملة للعقد');
            }

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
            if (!$this->canViewRecruitmentContracts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لعرض إحصائيات العقود'
                ], 403);
            }

            $baseQuery = Contract::query()
                ->whereIn('type', ['recruitment', 'maid']);
            $this->excludeShelterContracts($baseQuery);

            // إحصائيات العقود المطابقة لجميع فلاتر الجدول الحالية.
            $this->applyContractFilters($baseQuery, $request);

            $today = \Carbon\Carbon::now()->toDateString();
            $currentStatuses = $this->getCurrentContractStatuses();
            $completedStageStatuses = $this->getCompletedStageStatuses();

            $aggregateBindings = array_merge(
                $currentStatuses,
                $completedStageStatuses,
                [
                    ContractStatusEnum::Back->value,
                    ContractStatusEnum::Refunded->value,
                    ContractStatusEnum::PendingMaidSelection->value,
                    ContractStatusEnum::New->value,
                    ContractStatusEnum::Msaned->value,
                    ContractStatusEnum::Tafwed->value,
                    ContractStatusEnum::Taffez->value,
                    ContractStatusEnum::Tazker->value,
                    ContractStatusEnum::Arrival->value,
                    ContractStatusEnum::Completed->value,
                    ContractStatusEnum::Arrival->value,
                    $today,
                    ContractStatusEnum::Completed->value,
                    $today,
                    ContractStatusEnum::Completed->value,
                ]
            );

            $aggregate = (clone $baseQuery)
                ->selectRaw(
                    '
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN (' . implode(',', array_fill(0, count($currentStatuses), '?')) . ')
                        AND NOT EXISTS (
                            SELECT 1 FROM travel_histories th_current
                            WHERE th_current.order_id = contracts.id
                                AND th_current.status = \'completed\'
                                AND th_current.deleted_at IS NULL
                        ) THEN 1 ELSE 0 END) as current_total,
                    SUM(CASE WHEN status IN (' . implode(',', array_fill(0, count($completedStageStatuses), '?')) . ')
                        OR EXISTS (
                            SELECT 1 FROM travel_histories th_completed
                            WHERE th_completed.order_id = contracts.id
                                AND th_completed.status = \'completed\'
                                AND th_completed.deleted_at IS NULL
                        ) THEN 1 ELSE 0 END) as completed_total,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_back,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_refunded,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_pending_maid_selection,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_new,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_msaned,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_tafwed,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_taffez,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_tazker,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_arrival,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as stage_completed,
                    SUM(CASE WHEN status = ? AND warranty_end_date IS NOT NULL AND DATE(warranty_end_date) >= ? THEN 1 ELSE 0 END) as warranty_active,
                    SUM(CASE WHEN status = ? AND warranty_end_date IS NOT NULL AND DATE(warranty_end_date) < ? THEN 1 ELSE 0 END) as warranty_expired,
                    SUM(CASE WHEN status = ? AND warranty_end_date IS NULL THEN 1 ELSE 0 END) as warranty_not_applicable
                    ',
                    $aggregateBindings
                )
                ->first();

            $counts = [
                'total' => (int) ($aggregate->total ?? 0),
                'current_total' => (int) ($aggregate->current_total ?? 0),
                'completed_total' => (int) ($aggregate->completed_total ?? 0),
                'stage_back' => (int) ($aggregate->stage_back ?? 0),
                'stage_refunded' => (int) ($aggregate->stage_refunded ?? 0),
                'stage_pending_maid_selection' => (int) ($aggregate->stage_pending_maid_selection ?? 0),
                'stage_new' => (int) ($aggregate->stage_new ?? 0),
                'stage_msaned' => (int) ($aggregate->stage_msaned ?? 0),
                'stage_tafwed' => (int) ($aggregate->stage_tafwed ?? 0),
                'stage_taffez' => (int) ($aggregate->stage_taffez ?? 0),
                'stage_tazker' => (int) ($aggregate->stage_tazker ?? 0),
                'stage_arrival' => (int) ($aggregate->stage_arrival ?? 0),
                'stage_completed' => (int) ($aggregate->stage_completed ?? 0),
                'warranty_active' => (int) ($aggregate->warranty_active ?? 0),
                'warranty_expired' => (int) ($aggregate->warranty_expired ?? 0),
                'warranty_not_applicable' => (int) ($aggregate->warranty_not_applicable ?? 0),
            ];

            $arrivalTodayQuery = clone $baseQuery;
            $this->applyStatusFilter($arrivalTodayQuery, 'arrival_today');
            $counts['arrival_today'] = $arrivalTodayQuery->count();

            $delayedArrivalQuery = clone $baseQuery;
            $this->applyStatusFilter($delayedArrivalQuery, 'delayed_arrival');
            $counts['delayed_arrival'] = $delayedArrivalQuery->count();

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

    private function applyDocumentPresenceFilters($query, Request $request): void
    {
        $relations = [
            'has_invoice' => 'invoice',
            'has_musaned_transfer' => 'invoice.musanedTransferItem.transfer',
            'has_external_dispatch' => 'externalOfficeDispatch',
        ];

        foreach ($relations as $filter => $relation) {
            $value = (string) $request->input($filter, '');

            if ($value === '1') {
                $query->whereHas($relation);
            } elseif ($value === '0') {
                $query->whereDoesntHave($relation);
            }
        }
    }

    private function applyContractFilters($query, Request $request, bool $ignoreStatus = false, bool $ignoreWarranty = false): void
    {
        $this->applyRequestBranchFilter($query, $request, 'contracts');

        if ($request->filled('marketer_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('marketer_id', $request->marketer_id)
                    ->orWhereHas('order.details', function ($orderQuery) use ($request) {
                        $orderQuery->where('admin_id', $request->marketer_id);
                    });
            });
        }

        // فلتر الحالة - استخدام query parameter فقط
        if (!$ignoreStatus) {
            $statusFilter = $this->getStatusFilterForRequest($request);
            $this->applyStatusFilter($query, $statusFilter);
        }

        if ($request->boolean('exclude_back')) {
            $query->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', ContractStatusEnum::Back->value);
            });
        }

        $this->applyDocumentPresenceFilters($query, $request);

        // فلتر حالة الضمان
        if (!$ignoreWarranty && !$this->shouldTreatAsCompletedTotalRequest($request) && $request->filled('warranty_status')) {
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
            $query->whereRaw($this->getEffectiveContractDateSql() . ' >= ?', [$request->date_from]);
        }

        // فلتر التاريخ إلى
        if ($request->filled('date_to')) {
            $query->whereRaw($this->getEffectiveContractDateSql() . ' <= ?', [$request->date_to]);
        }

        // فلتر رقم جوال العميل
        if ($request->filled('client_phone')) {
            $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->client_phone . '%');
            });
        }

        // فلتر اسم العميل
        if ($request->filled('client_name')) {
            $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
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
            $this->applyOfficeFilter($query, $request->office_id);
        }

        // فلتر مدينة العميل
        if ($request->filled('city')) {
            $this->applyDisplayedClientFilter($query, function ($q) use ($request) {
                $q->where('city', 'like', '%' . $request->city . '%');
            });
        }

        // فلتر البحث الشامل
        if ($request->filled('global_search')) {
            $this->applyGlobalSearchFilter($query, $request->global_search);
        }
    }

    private function branchesEnabledFor(string $table): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($table)
            && Schema::hasColumn($table, 'branch_id');
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

            // بيانات العميل المعروض في الجدول، مع الحفاظ على الكفيل القديم بعد نقل الخدمات.
            $q->orWhere(function ($clientSearchQuery) use ($likeSearch) {
                $this->applyDisplayedClientFilter($clientSearchQuery, function ($userQuery) use ($likeSearch) {
                    $userQuery->where('name', 'like', $likeSearch)
                        ->orWhere('id', 'like', $likeSearch)
                        ->orWhere('arabia_client_number', 'like', $likeSearch)
                        ->orWhere('phone_code', 'like', $likeSearch)
                        ->orWhere('phone', 'like', $likeSearch)
                        ->orWhere('source_phone', 'like', $likeSearch)
                        ->orWhereRaw("CONCAT(COALESCE(phone_code,''), COALESCE(phone,'')) LIKE ?", [$likeSearch])
                        ->orWhere('phone2', 'like', $likeSearch)
                        ->orWhere('source_phone2', 'like', $likeSearch)
                        ->orWhere('national_num', 'like', $likeSearch)
                        ->orWhere('email', 'like', $likeSearch)
                        ->orWhere('city', 'like', $likeSearch)
                        ->orWhere('address', 'like', $likeSearch)
                        ->orWhere('birthdate', 'like', $likeSearch)
                        ->orWhere('job', 'like', $likeSearch);
                });
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

    private function applyOfficeFilter($query, $officeId): void
    {
        $query->where(function ($officeQuery) use ($officeId) {
            $officeQuery->whereHas('maid', function ($maidQuery) use ($officeId) {
                $maidQuery->where('office_id', $officeId);
            })->orWhereHas('info', function ($infoQuery) use ($officeId) {
                $infoQuery->where('manual_maid_office_id', $officeId);
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
            if (!$this->canUpdateRecruitmentContracts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتعديل العقود'
                ], 403);
            }

            // التحقق من وجود العقد
            $contract = Contract::with(['maid', 'user', 'order'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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
                'reason' => 'required_if:old_maid_status,backout|nullable|string|max:1000'
            ]);

            $newMaidId = $request->new_maid_id;
            $oldMaidStatus = $request->old_maid_status;
            $reason = $request->reason;

            // التحقق من أن العاملة الجديدة متاحة
            $newMaid = Maid::findOrFail($newMaidId);

            if (!$newMaid->available || $newMaid->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة غير متاحة حالياً'
                ], 400);
            }

            // التحقق من أن العاملة الجديدة ليس لها عقود نشطة
            $hasActiveContracts = Contract::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    ContractStatusEnum::PendingMaidSelection->value,
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

            $oldMaid = $contract->maid;
            if (!$oldMaid) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد عاملة حالية مرتبطة بهذا العقد'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // حفظ العاملة القديمة
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
                        'available' => false,
                        'back_out_reason' => $reason ?? 'تم تغيير العاملة من عقد',
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
            if (!$this->canUpdateRecruitmentContracts()) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقود');
            }

            $contract = Contract::with(['maid', 'user'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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
            if (in_array($contract->status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
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
                $previousStatus = $contract->status;

                // تحديث حالة العقد إلى مرتجع
                $contract->update([
                    'status' => ContractStatusEnum::Back->value
                ]);

                // إنشاء سجل حالة العقد
                $statusLogData = [
                    'status' => ContractStatusEnum::Back->value,
                    'date' => now(),
                    'notes' => 'تم إلغاء العقد - السبب: ' . $request->cancel_reason
                ];
                if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                    $statusLogData['previous_status'] = $previousStatus;
                }
                $contract->contract_status_logs()->create($statusLogData);

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

    /**
     * التراجع عن إرجاع العقد وإعادته إلى آخر حالة موثقة قبل الإرجاع.
     */
    public function restoreReturned(
        Request $request,
        Contract $contract,
        RecruitmentContractReturnRestorationService $restorationService
    ) {
        if (!@AdminGuardHasAnyPerm([
            \App\NewPermKeys::REC_CONTRACTS_RETURN,
            \App\NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للتراجع عن إرجاع العقود',
            ], 403);
        }

        $this->assertCanAccessBranchModel($contract, 'contracts');

        try {
            $restoredContract = $restorationService->restore(
                $contract,
                Auth::guard('admin')->id(),
                $request->input('previous_status')
            );

            return response()->json([
                'success' => true,
                'message' => 'تمت إعادة العقد إلى الحالة السابقة: ' . getContractStatus($restoredContract->status),
                'status' => $restoredContract->status,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first() ?: $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            logger()->error('Failed to restore returned recruitment contract', [
                'contract_id' => $contract->id,
                'admin_id' => Auth::guard('admin')->id(),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تعذر التراجع عن إرجاع العقد. لم يتم إجراء أي تغيير.',
            ], 500);
        }
    }

    public function refundPreview(Request $request, RecruitmentContractRefundService $refundService)
    {
        try {
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لاسترداد العقود'
                ], 403);
            }

            $validated = $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'refund_date' => 'required|date',
                'cancelled_by' => 'nullable|in:client,office,external_office',
                'penalty_percent' => 'nullable|numeric|between:0,100',
                'penalty_amount' => 'nullable|numeric|min:0',
            ]);

            $contract = Contract::with([
                'maid',
                'user',
                'invoice.musanedTransferItem.transfer',
                'recruitmentRefund',
                'info',
            ])->findOrFail($validated['contract_id']);

            if (!$this->isRefundableRecruitmentContractStatus($contract->status)) {
                return response()->json([
                    'success' => false,
                    'message' => 'استرداد العقد متاح فقط للعقود السارية'
                ], 422);
            }

            if (!$contract->invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استرداد العقد قبل إضافة فاتورة له'
                ], 422);
            }

            if ($contract->recruitmentRefund) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم تسجيل استرداد سابق لهذا العقد'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'preview' => $refundService->previewDetails(
                    $contract,
                    $validated['refund_date'],
                    $validated['cancelled_by'] ?? null,
                    $validated
                ),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            logger()->error('Error previewing recruitment contract refund:', [
                'contract_id' => $request->contract_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة الاسترداد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refund(Request $request, RecruitmentContractRefundService $refundService)
    {
        try {
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لاسترداد العقود'
                ], 403);
            }

            $validated = $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'refund_date' => 'required|date',
                'cancelled_by' => 'nullable|in:client,office,external_office',
                'penalty_percent' => 'nullable|numeric|between:0,100',
                'penalty_amount' => 'nullable|numeric|min:0',
                'reason' => 'required|string|max:500',
            ]);

            $contract = Contract::with([
                'maid',
                'user',
                'invoice.musanedTransferItem.transfer',
                'recruitmentRefund',
                'info',
            ])->findOrFail($validated['contract_id']);

            if (!$this->isRefundableRecruitmentContractStatus($contract->status)) {
                return response()->json([
                    'success' => false,
                    'message' => 'استرداد العقد متاح فقط للعقود السارية'
                ], 422);
            }

            if (!$contract->invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استرداد العقد قبل إضافة فاتورة له'
                ], 422);
            }

            if ($contract->recruitmentRefund) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم تسجيل استرداد سابق لهذا العقد'
                ], 422);
            }

            $refund = null;

            DB::beginTransaction();

            try {
                $contract = Contract::with([
                    'maid',
                    'user',
                    'invoice.musanedTransferItem.transfer',
                    'invoice.recruitmentCreditNotes',
                    'recruitmentRefund',
                    'info',
                ])->lockForUpdate()->findOrFail($validated['contract_id']);

                if ($contract->recruitmentRefund) {
                    throw new \RuntimeException('تم تسجيل استرداد سابق لهذا العقد.');
                }

                $refund = $refundService->refund($contract, [
                    'refund_date' => $validated['refund_date'],
                    'cancelled_by' => $validated['cancelled_by'] ?? null,
                    'reason' => $validated['reason'],
                    'penalty_percent' => $validated['penalty_percent'] ?? null,
                    'penalty_amount' => $validated['penalty_amount'] ?? null,
                ]);

                $previousStatus = $contract->status;
                $contract->update([
                    'status' => ContractStatusEnum::Back->value,
                ]);

                $statusLogData = [
                    'status' => ContractStatusEnum::Back->value,
                    'date' => $validated['refund_date'],
                    'notes' => 'تم استرداد العقد - السبب: ' . $validated['reason'],
                ];
                if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
                    $statusLogData['previous_status'] = $previousStatus;
                }
                $contract->contract_status_logs()->create($statusLogData);

                if ($contract->maid) {
                    $backoutReason = 'تم تحويل العاملة إلى باك آوت بعد استرداد العقد #' . $contract->id
                        . ' - السبب: ' . $validated['reason'];

                    $contract->maid->forceFill([
                        'status' => 'backout',
                        'available' => false,
                        'back_out_reason' => $backoutReason,
                    ])->save();

                    MaidUnavailableRecord::query()->create([
                        'maid_id' => $contract->maid->id,
                        'reason' => $backoutReason,
                        'contract_id' => $contract->id,
                        'admin_id' => Auth::guard('admin')->id(),
                    ]);
                }

                DB::commit();
            } catch (\Throwable $exception) {
                DB::rollBack();
                throw $exception;
            }

            $partyLabel = match ($refund->cancelled_by) {
                'client' => 'العميل',
                'office' => 'المكتب',
                'external_office' => 'الوكيل/المكتب الخارجي',
                default => 'قبل تحويل مساند',
            };

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل استرداد العقد بنجاح. مبلغ الاسترداد: '
                    . number_format((float) $refund->refund_amount, 2)
                    . '، الغرامة: '
                    . number_format((float) $refund->penalty_amount, 2)
                    . '، الطرف: ' . $partyLabel,
                'refund' => $refund,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            logger()->error('Error refunding recruitment contract:', [
                'contract_id' => $request->contract_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استرداد العقد: ' . $e->getMessage()
            ], 500);
        }
    }
}
