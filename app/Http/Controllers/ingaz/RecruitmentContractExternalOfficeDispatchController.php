<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\TravelHistory;
use App\NewPermKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentContractExternalOfficeDispatchController extends Controller
{
    private const USD_TO_SAR_RATE = 3.75;

    private function canViewRecruitmentContracts(): bool
    {
        return @AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_VIEW,
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

    public function index(Request $request)
    {
        if (!$this->canViewRecruitmentContracts()) {
            abort(403, 'ليس لديك صلاحية لعرض إرسالات المكتب الخارجي.');
        }

        $dispatchFilter = (string) $request->get('dispatch_filter', 'not_sent');
        if (!in_array($dispatchFilter, ['all', 'sent', 'not_sent'], true)) {
            $dispatchFilter = 'not_sent';
        }
        $currentStatuses = $this->currentRecruitmentStatuses();

        $requestedContractIds = collect((array) $request->old('contract_ids', $request->query('contract_ids', [])))
            ->push($request->query('contract_id'))
            ->filter(fn ($value) => is_scalar($value) && filter_var($value, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        $selectedContractIds = collect();
        $selectedContractGroups = collect();
        if ($requestedContractIds->isNotEmpty()) {
            $selectedContractsQuery = Contract::query()
                ->with([
                    'maid.nationality',
                    'maid.office',
                    'maid.recruitmentOffice',
                    'requiredMaidNationality',
                    'info.manualMaidNationality',
                    'info.manualMaidOffice',
                ])
                ->whereIn('id', $requestedContractIds)
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch');
            $this->applyRequestBranchFilter($selectedContractsQuery, $request, 'contracts');
            $selectedContracts = $selectedContractsQuery->get()
                ->filter(function (Contract $contract) {
                    $group = $this->buildBulkSelectionGroupData($contract);

                    return $contract->canManageExternalOfficeDispatch()
                        && !empty($group['office_id'])
                        && !empty($group['nationality_id'])
                        && empty($contract->externalOfficeDispatchSourceValidationErrors('1.00'));
                });
            $selectedContractIds = $selectedContracts->pluck('id');
            $selectedContractGroups = $selectedContracts->mapWithKeys(fn (Contract $contract) => [
                (string) $contract->id => $this->buildBulkSelectionGroupData($contract),
            ]);
        }

        $query = Contract::query()
            ->with([
                'user',
                'maid.religion',
                'maid.nationality',
                'maid.office',
                'maid.recruitmentOffice',
                'requiredMaidNationality',
                'info',
                'info.manualMaidNationality',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
            ])
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($inner) use ($currentStatuses) {
                $inner->whereIn('status', $currentStatuses)
                    ->orWhereHas('externalOfficeDispatch');
            });
        $this->applyRequestBranchFilter($query, $request, 'contracts');

        if ($request->ajax()) {
            $this->applyDispatchFilter($query, $dispatchFilter, $currentStatuses);

            if ($request->filled('global_search')) {
                $search = trim((string) $request->global_search);
                $query->where(function ($inner) use ($search) {
                    $inner->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('info', function ($infoQuery) use ($search) {
                            $infoQuery->where('visa_number', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('national_num', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('maid', function ($maidQuery) use ($search) {
                            $maidQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('passport_num', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($request->filled('contract_status')) {
                $query->where('status', $request->contract_status);
            }

            return DataTables::eloquent($query->latest('id'))
                ->addColumn('contract_number', function (Contract $contract) {
                    return '<a href="' . e(route('contracts.details', $contract->id)) . '" class="fw-semibold text-decoration-none">#' . e($contract->id) . '</a>';
                })
                ->addColumn('selection', function (Contract $contract) {
                    $group = $this->buildBulkSelectionGroupData($contract);
                    $sourceErrors = $contract->externalOfficeDispatchSourceValidationErrors('1.00');
                    $isSelectable = $contract->canManageExternalOfficeDispatch()
                        && !$contract->externalOfficeDispatch
                        && !empty($group['office_id'])
                        && !empty($group['nationality_id'])
                        && empty($sourceErrors);
                    $disabled = $isSelectable
                        ? ''
                        : ' disabled';
                    $disabledReason = $sourceErrors
                        ? implode(' ', $sourceErrors)
                        : (!$group['office_id'] || !$group['nationality_id']
                            ? 'يجب تحديد المكتب الخارجي والجنسية أولًا.'
                            : ($contract->externalOfficeDispatch ? 'توجد إرسالية مسجلة مسبقًا.' : 'العقد غير متاح للإرسال.'));

                    $checkbox = '<input type="checkbox" class="form-check-input dispatch-contract-checkbox"'
                        . ' value="' . e($contract->id) . '"'
                        . ' data-office-id="' . e($group['office_id']) . '"'
                        . ' data-nationality-id="' . e($group['nationality_id']) . '"'
                        . ' data-office-name="' . e($group['office_name']) . '"'
                        . ' data-nationality-name="' . e($group['nationality_name']) . '"'
                        . $disabled . '>';

                    return $isSelectable
                        ? $checkbox
                        : '<span title="' . e($disabledReason) . '">' . $checkbox . '</span>';
                })
                ->addColumn('client', function (Contract $contract) {
                    $name = e($contract->user?->name ?? 'غير محدد');
                    $identity = e($contract->user?->documentNumber() ?? '-');
                    $identityLabel = e($contract->user?->documentNumberLabel() ?? 'هوية');

                    return '<div class="fw-semibold">' . $name . '</div><small class="text-muted">' . $identityLabel . ': ' . $identity . '</small>';
                })
                ->addColumn('worker', function (Contract $contract) {
                    $workerName = e($contract->maid?->name ?? $contract->info?->manual_maid_name ?? 'غير محدد');
                    $passport = e($contract->maid?->passport_num ?? $contract->info?->manual_maid_passport_num ?? '-');

                    return '<div class="fw-semibold">' . $workerName . '</div><small class="text-muted">جواز: ' . $passport . '</small>';
                })
                ->addColumn('office', function (Contract $contract) {
                    $office = $contract->resolveExternalOfficeDispatchOffice();
                    $officeName = e($office?->title ?? 'غير محدد');
                    $usdCost = filled($office?->cost_of_dollar)
                        ? number_format((float) str_replace(',', '', (string) $office->cost_of_dollar), 2) . ' USD'
                        : 'غير محدد';

                    return '<div class="fw-semibold">' . $officeName . '</div><small class="text-muted">التكلفة: ' . e($usdCost) . '</small>';
                })
                ->addColumn('nationality', function (Contract $contract) {
                    $group = $this->buildBulkSelectionGroupData($contract);

                    return e($group['nationality_name']);
                })
                ->addColumn('contract_status', function (Contract $contract) {
                    return '<span class="badge bg-' . e(getContractStatusColor($contract->status)) . '">' . e(getContractStatus($contract->status)) . '</span>';
                })
                ->addColumn('dispatch_status', function (Contract $contract) {
                    $state = $contract->getExternalOfficeDispatchState();

                    $badgeClass = match ($state['code']) {
                        'completed' => 'success',
                        'outdated' => 'warning text-dark',
                        'incomplete' => 'danger',
                        default => 'secondary',
                    };

                    return '<span class="badge bg-' . e($badgeClass) . '" title="' . e($state['message']) . '">' . e($state['label']) . '</span>';
                })
                ->addColumn('dispatch_date', function (Contract $contract) {
                    return optional($contract->externalOfficeDispatch?->dispatch_date)->format('Y-m-d') ?: '<span class="text-muted">-</span>';
                })
                ->addColumn('arrival_station', function (Contract $contract) {
                    $station = $contract->resolveExternalOfficeDispatchArrivalStation();

                    return $station
                        ? e($station)
                        : '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('actions', function (Contract $contract) {
                    $actions = '<div class="btn-group btn-group-sm" role="group">';
                    $actions .= '<a href="' . e(route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id)) . '" class="btn btn-outline-primary">الإرسالية</a>';
                    $actions .= '<a href="' . e(route('contracts.details', $contract->id)) . '" class="btn btn-outline-secondary">العقد</a>';

                    if ($contract->externalOfficeDispatch) {
                        $actions .= '<a href="' . e(route('RecruitmentContract.externalOfficeDispatch.print', $contract->id)) . '" target="_blank" rel="noopener" class="btn btn-outline-dark">طباعة</a>';
                    }

                    $actions .= '</div>';

                    return $actions;
                })
                ->rawColumns([
                    'selection',
                    'contract_number',
                    'client',
                    'worker',
                    'office',
                    'nationality',
                    'contract_status',
                    'dispatch_status',
                    'dispatch_date',
                    'arrival_station',
                    'actions',
                ])
                ->make(true);
        }

        $statsBaseQuery = Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($inner) use ($currentStatuses) {
                $inner->whereIn('status', $currentStatuses)
                    ->orWhereHas('externalOfficeDispatch');
            });
        $this->applyRequestBranchFilter($statsBaseQuery, $request, 'contracts');

        $stats = [
            'all' => (clone $statsBaseQuery)->count(),
            'sent' => (clone $statsBaseQuery)->whereHas('externalOfficeDispatch')->count(),
            'not_sent' => (clone $statsBaseQuery)
                ->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch')
                ->count(),
        ];

        return view('admin.ingaz.RecruitmentContract.external_office_dispatch_index', [
            'dispatchFilter' => $dispatchFilter,
            'stats' => $stats,
            'selectedContractIds' => $selectedContractIds,
            'selectedContractGroups' => $selectedContractGroups,
        ]);
    }

    public function edit(Contract $contract, Request $request)
    {
        $this->assertCanAccessBranchModel($contract);

        if (!$this->canViewRecruitmentContracts()) {
            abort(403, 'ليس لديك صلاحية لعرض إرسالية المكتب الخارجي.');
        }

        $contract->load([
            'user',
            'maid.religion',
            'maid.nationality',
            'maid.office',
            'maid.recruitmentOffice',
            'requiredMaidNationality',
            'info.manualMaidNationality',
            'info.manualMaidOffice',
            'externalOfficeDispatch',
            'travelHistory',
        ]);

        $dispatch = $contract->externalOfficeDispatch;
        $dispatchState = $contract->getExternalOfficeDispatchState();
        $sourceSnapshot = $contract->buildExternalOfficeDispatchSourceData($dispatch?->office_contract_cost_usd);
        $sourceErrors = $contract->externalOfficeDispatchSourceValidationErrors();
        $isEditable = $contract->canManageExternalOfficeDispatch() && $this->canUpdateRecruitmentContracts();
        $initialOfficeContractCostUsd = old(
            'office_contract_cost_usd',
            $dispatch?->office_contract_cost_usd ?? $sourceSnapshot['office_contract_cost_usd']
        );
        $initialOfficeContractCostSar = filled($initialOfficeContractCostUsd)
            ? number_format((float) str_replace(',', '', (string) $initialOfficeContractCostUsd) * self::USD_TO_SAR_RATE, 2, '.', '')
            : null;
        $bulkGroup = $this->buildBulkSelectionGroupData($contract);
        $bulkCompatibleContracts = collect();

        if ($contract->canManageExternalOfficeDispatch() && $bulkGroup['office_id'] && $bulkGroup['nationality_id']) {
            $bulkContractsQuery = Contract::query()
                ->with([
                    'user',
                    'maid.religion',
                    'maid.nationality',
                    'maid.office',
                    'maid.recruitmentOffice',
                    'requiredMaidNationality',
                    'info.manualMaidNationality',
                    'info.manualMaidOffice',
                    'externalOfficeDispatch',
                    'travelHistory',
                ])
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereIn('status', $this->currentRecruitmentStatuses())
                ->whereDoesntHave('externalOfficeDispatch')
                ->where(function ($query) use ($bulkGroup) {
                    $query->whereHas('maid', function ($maidQuery) use ($bulkGroup) {
                        $maidQuery
                            ->where('office_id', $bulkGroup['office_id'])
                            ->where('nationality_id', $bulkGroup['nationality_id']);
                    })->orWhereHas('info', function ($infoQuery) use ($bulkGroup) {
                        $infoQuery
                            ->where('manual_maid_office_id', $bulkGroup['office_id'])
                            ->where('manual_maid_nationality_id', $bulkGroup['nationality_id']);
                    });
                });
            $this->applyRequestBranchFilter($bulkContractsQuery, $request, 'contracts');

            $bulkCompatibleContracts = $bulkContractsQuery
                ->orderByDesc('id')
                ->get()
                ->filter(function (Contract $candidate) use ($bulkGroup) {
                    return $this->buildBulkSelectionGroupData($candidate)['group_key'] === $bulkGroup['group_key']
                        && empty($candidate->externalOfficeDispatchSourceValidationErrors('1.00'));
                })
                ->values();
        }

        $currentContractSelectionEligible = in_array($contract->type, ['recruitment', 'maid'], true)
            && $contract->canManageExternalOfficeDispatch()
            && !$dispatch
            && !empty($bulkGroup['office_id'])
            && !empty($bulkGroup['nationality_id'])
            && empty($sourceErrors);

        // Keep the current contract available whenever it satisfies the actual
        // business rules, even if a relation-based query did not return it.
        if ($currentContractSelectionEligible
            && !$bulkCompatibleContracts->contains(fn (Contract $candidate) => (int) $candidate->id === (int) $contract->id)) {
            $bulkCompatibleContracts = $bulkCompatibleContracts
                ->prepend($contract)
                ->sortByDesc('id')
                ->values();
        }

        $selectionEligibilityChecks = [
            [
                'label' => 'نوع العقد استقدام أو عقد عاملة',
                'passed' => in_array($contract->type, ['recruitment', 'maid'], true),
                'failure' => 'نوع العقد الحالي لا يدعم إرسالية المكتب الخارجي.',
            ],
            [
                'label' => 'العقد ضمن الحالات الجارية المسموح بها',
                'passed' => $contract->canManageExternalOfficeDispatch(),
                'failure' => 'حالة العقد الحالية (' . getContractStatus($contract->status) . ') لا تسمح بإنشاء أو تعديل الإرسالية.',
            ],
            [
                'key' => 'no_existing_dispatch',
                'label' => 'لا توجد إرسالية مسجلة مسبقًا',
                'passed' => !$dispatch,
                'failure' => 'توجد إرسالية مسجلة لهذا العقد بالفعل؛ يمكن تعديلها بدل إنشاء إرسالية جديدة.',
            ],
            [
                'label' => 'العقد مرتبط بعاملة فعلية',
                'passed' => (bool) $contract->maid,
                'failure' => 'يجب ربط عاملة فعلية بالعقد.',
            ],
            [
                'label' => 'المكتب الخارجي محدد',
                'passed' => !empty($bulkGroup['office_id']),
                'failure' => 'المكتب الخارجي غير محدد على بيانات العاملة.',
            ],
            [
                'label' => 'جنسية العاملة محددة',
                'passed' => !empty($bulkGroup['nationality_id']),
                'failure' => 'جنسية العاملة غير محددة.',
            ],
            [
                'label' => 'اسم العميل ورقم هويته مكتملان',
                'passed' => filled($sourceSnapshot['sponsor_name']) && filled($sourceSnapshot['sponsor_identity_number']),
                'failure' => 'يجب استكمال اسم العميل ورقم هويته.',
            ],
            [
                'label' => 'اسم العاملة ورقم جوازها مكتملان',
                'passed' => filled($sourceSnapshot['worker_name']) && filled($sourceSnapshot['passport_number']),
                'failure' => 'يجب استكمال اسم العاملة ورقم جوازها.',
            ],
            [
                'label' => 'خبرة العاملة وديانتها مكتملتان',
                'passed' => filled($sourceSnapshot['worker_experience']) && filled($sourceSnapshot['worker_religion']),
                'failure' => 'يجب استكمال خبرة العاملة وديانتها.',
            ],
            [
                'label' => 'لديك صلاحية تحديث عقود الاستقدام',
                'passed' => $this->canUpdateRecruitmentContracts(),
                'failure' => 'لا توجد لديك صلاحية تحديث عقود الاستقدام أو إنشاء الإرسالية.',
            ],
        ];

        $selectionUnavailableReasons = collect($selectionEligibilityChecks)
            ->where('passed', false)
            ->pluck('failure')
            ->merge($sourceErrors)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // A contract that already carries a dispatch is not "unavailable" — it simply
        // moves from create mode to edit mode. Keep that out of the blocking reasons so
        // the page stops reporting a normal, finished dispatch as a failure.
        $selectionBlockingReasons = collect($selectionEligibilityChecks)
            ->where('passed', false)
            ->reject(fn (array $check) => ($check['key'] ?? null) === 'no_existing_dispatch')
            ->pluck('failure')
            ->merge($sourceErrors)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $oldBulkContractIds = collect((array) old('contract_ids', [$contract->id]))
            ->map(fn ($id) => (int) $id)
            ->intersect($bulkCompatibleContracts->pluck('id'))
            ->values()
            ->all();

        return view('admin.ingaz.RecruitmentContract.external_office_dispatch', [
            'contract' => $contract,
            'dispatch' => $dispatch,
            'dispatchState' => $dispatchState,
            'sourceSnapshot' => $sourceSnapshot,
            'sourceErrors' => $sourceErrors,
            'isEditable' => $isEditable,
            'usdToSarRate' => self::USD_TO_SAR_RATE,
            'initialOfficeContractCostUsd' => $initialOfficeContractCostUsd,
            'initialOfficeContractCostSar' => $initialOfficeContractCostSar,
            'initialArrivalStation' => old('arrival_station', $dispatch?->arrival_station ?? $contract->resolveExternalOfficeDispatchArrivalStation()),
            'initialDispatchDate' => old(
                'dispatch_date',
                optional($dispatch?->dispatch_date)->format('Y-m-d') ?? now()->toDateString()
            ),
            'bulkGroup' => $bulkGroup,
            'bulkCompatibleContracts' => $bulkCompatibleContracts,
            'oldBulkContractIds' => $oldBulkContractIds,
            'selectionEligibilityChecks' => $selectionEligibilityChecks,
            'selectionUnavailableReasons' => $selectionUnavailableReasons,
            'selectionBlockingReasons' => $selectionBlockingReasons,
        ]);
    }

    public function store(Request $request, Contract $contract)
    {
        $this->assertCanAccessBranchModel($contract);

        if (!$this->canUpdateRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتحديث إرسالية المكتب الخارجي.');
        }

        if (!$contract->canManageExternalOfficeDispatch()) {
            return redirect()->back()->with('error', 'يمكن إنشاء أو تحديث إرسالية المكتب الخارجي للعقود الجارية فقط.');
        }

        $validated = $request->validate([
            'dispatch_date' => 'required|date',
            'arrival_station' => 'required|string|max:255',
            'office_contract_cost_usd' => 'required|numeric|min:0.01|max:99999999.99',
        ], [
            'dispatch_date.required' => 'تاريخ الإرسالية مطلوب.',
            'dispatch_date.date' => 'تاريخ الإرسالية غير صحيح.',
            'arrival_station.required' => 'محطة الوصول مطلوبة.',
            'arrival_station.max' => 'محطة الوصول طويلة جداً.',
            'office_contract_cost_usd.required' => 'تكلفة العقد بالدولار مطلوبة.',
            'office_contract_cost_usd.numeric' => 'تكلفة العقد بالدولار يجب أن تكون رقماً صحيحاً.',
            'office_contract_cost_usd.min' => 'تكلفة العقد بالدولار يجب أن تكون أكبر من صفر.',
            'office_contract_cost_usd.max' => 'تكلفة العقد بالدولار كبيرة جداً.',
        ]);

        $contract->loadMissing([
            'user',
            'maid.religion',
            'maid.office',
            'maid.recruitmentOffice',
            'externalOfficeDispatch',
            'travelHistory',
        ]);

        $sourceErrors = $contract->externalOfficeDispatchSourceValidationErrors();
        if (!empty($sourceErrors)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', implode(' ', $sourceErrors));
        }

        $adminId = auth('admin')->id() ?? auth()->id();

        $this->persistExternalOfficeDispatch(
            $contract,
            $validated['dispatch_date'],
            trim((string) $validated['arrival_station']),
            number_format((float) $validated['office_contract_cost_usd'], 2, '.', ''),
            $adminId
        );

        return redirect()
            ->route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id)
            ->with('success', 'تم حفظ إرسالية المكتب الخارجي بنجاح.');
    }

    public function bulkStore(Request $request)
    {
        if (!$this->canUpdateRecruitmentContracts()) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتحديث إرساليات المكتب الخارجي.');
        }

        $validated = $request->validate([
            'contract_ids' => 'required|array|min:1',
            'contract_ids.*' => 'integer|distinct|exists:contracts,id',
            'dispatch_date' => 'required|date',
            'arrival_station' => 'nullable|string|max:255',
            'office_contract_cost_usd' => 'required|numeric|min:0.01|max:99999999.99',
            'return_contract_id' => 'nullable|integer|exists:contracts,id',
        ], [
            'contract_ids.required' => 'يجب تحديد عقد واحد على الأقل.',
            'contract_ids.min' => 'يجب تحديد عقد واحد على الأقل.',
            'dispatch_date.required' => 'تاريخ الإرسالية مطلوب.',
            'dispatch_date.date' => 'تاريخ الإرسالية غير صحيح.',
            'arrival_station.max' => 'محطة الوصول طويلة جداً.',
            'office_contract_cost_usd.required' => 'تكلفة العقد بالدولار مطلوبة للإرسال المجمع.',
            'office_contract_cost_usd.numeric' => 'تكلفة العقد بالدولار يجب أن تكون رقمًا صحيحًا.',
            'office_contract_cost_usd.min' => 'تكلفة العقد بالدولار يجب أن تكون أكبر من صفر.',
            'office_contract_cost_usd.max' => 'تكلفة العقد بالدولار كبيرة جداً.',
        ]);

        $contractsQuery = Contract::query()
            ->with([
                'user',
                'maid.religion',
                'maid.nationality',
                'maid.office',
                'maid.recruitmentOffice',
                'requiredMaidNationality',
                'info.manualMaidNationality',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
                'travelHistory',
            ])
            ->whereIn('id', $validated['contract_ids']);
        $this->applyRequestBranchFilter($contractsQuery, $request, 'contracts');
        $contracts = $contractsQuery->get();

        $adminId = auth('admin')->id() ?? auth()->id();
        $errors = [];
        $arrivalStations = [];
        $commonArrivalStation = trim((string) ($validated['arrival_station'] ?? ''));
        $commonOfficeContractCostUsd = number_format(
            (float) $validated['office_contract_cost_usd'],
            2,
            '.',
            ''
        );

        if ($contracts->count() !== count($validated['contract_ids'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'تعذر الوصول إلى عقد أو أكثر من العقود المحددة.');
        }

        $groupData = $contracts->map(fn (Contract $contract) => $this->buildBulkSelectionGroupData($contract));
        $missingGroupContract = $contracts->first(function (Contract $contract) {
            $group = $this->buildBulkSelectionGroupData($contract);

            return !$group['office_id'] || !$group['nationality_id'];
        });

        if ($missingGroupContract) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'العقد #' . $missingGroupContract->id . ': يجب تحديد المكتب الخارجي والجنسية قبل إنشاء الإرسالية.');
        }

        if ($groupData->pluck('group_key')->unique()->count() !== 1) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'يجب أن تكون جميع العقود المحددة من نفس الجنسية ونفس المكتب الخارجي.');
        }

        foreach ($contracts as $contract) {
            if (!$contract->canManageExternalOfficeDispatch()) {
                $errors[] = 'العقد #' . $contract->id . ': يمكن إنشاء الإرسالية للعقود الجارية فقط.';
                continue;
            }

            if ($contract->externalOfficeDispatch) {
                $errors[] = 'العقد #' . $contract->id . ': توجد إرسالية مسجلة مسبقًا.';
                continue;
            }

            $sourceErrors = $contract->externalOfficeDispatchSourceValidationErrors($commonOfficeContractCostUsd);
            if (!empty($sourceErrors)) {
                $errors[] = 'العقد #' . $contract->id . ': ' . implode(' ', $sourceErrors);
                continue;
            }

            $arrivalStation = $commonArrivalStation !== ''
                ? $commonArrivalStation
                : ($contract->resolveExternalOfficeDispatchArrivalStation() ?: '');

            if ($arrivalStation === '') {
                $errors[] = 'العقد #' . $contract->id . ': محطة الوصول غير محددة.';
                continue;
            }

            $arrivalStations[$contract->id] = $arrivalStation;
        }

        if (!empty($errors)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'لم يتم حفظ أي إرسالية. يرجى معالجة الملاحظات ثم المحاولة مرة أخرى.')
                ->with('bulk_dispatch_errors', $errors);
        }

        DB::transaction(function () use (
            $contracts,
            $validated,
            $arrivalStations,
            $commonOfficeContractCostUsd,
            $adminId
        ) {
            foreach ($contracts as $contract) {
                $this->persistExternalOfficeDispatch(
                    $contract,
                    $validated['dispatch_date'],
                    $arrivalStations[$contract->id],
                    $commonOfficeContractCostUsd,
                    $adminId
                );
            }
        });

        $createdCount = $contracts->count();

        $message = $createdCount > 0
            ? 'تم حفظ ' . $createdCount . ' إرسالية مكتب خارجي بنجاح.'
            : 'لم يتم حفظ أي إرسالية.';

        $redirectRoute = !empty($validated['return_contract_id'])
            ? route('RecruitmentContract.externalOfficeDispatch.edit', $validated['return_contract_id'])
            : route('RecruitmentContract.externalOfficeDispatch.index', ['dispatch_filter' => 'not_sent']);

        return redirect()
            ->to($redirectRoute)
            ->with($createdCount > 0 ? 'success' : 'error', $message)
            ->with('bulk_dispatch_errors', $errors);
    }

    public function print(Contract $contract, Request $request)
    {
        $this->assertCanAccessBranchModel($contract);

        if (!$this->canViewRecruitmentContracts()) {
            abort(403, 'ليس لديك صلاحية لعرض إرسالية المكتب الخارجي.');
        }

        $contract->load([
            'user',
            'maid.religion',
            'maid.office',
            'externalOfficeDispatch',
        ]);

        $dispatch = $contract->externalOfficeDispatch;
        if (!$dispatch) {
            abort(404, 'إرسالية المكتب الخارجي غير موجودة لهذا العقد.');
        }

        $viewData = [
            'contract' => $contract,
            'dispatch' => $dispatch,
            'dispatchState' => $contract->getExternalOfficeDispatchState(),
            'usdToSarRate' => self::USD_TO_SAR_RATE,
            'previewUrl' => route('RecruitmentContract.externalOfficeDispatch.print', $contract->id),
            'pdfUrl' => route('RecruitmentContract.externalOfficeDispatch.print', [
                'contract' => $contract->id,
                'driver' => 'dompdf',
                'download' => 1,
            ]),
        ];

        if ($request->query('driver') !== 'dompdf') {
            return response()->view('admin.ingaz.RecruitmentContract.external_office_dispatch_print', $viewData, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $pdf = PDF::loadView('admin.ingaz.RecruitmentContract.external_office_dispatch_print', $viewData, [], [
            'format' => 'A4',
            'mode' => 'utf-8',
        ]);

        $fileName = 'external-office-dispatch-contract-' . $contract->id . '.pdf';

        if ($request->boolean('download') || $request->query('type') === 'download') {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public static function ensureDispatchCompleted(Contract $contract): ?string
    {
        $contract->loadMissing([
            'user',
            'maid.religion',
            'maid.office',
            'maid.recruitmentOffice',
            'externalOfficeDispatch',
        ]);

        $dispatchState = $contract->getExternalOfficeDispatchState();
        if ($dispatchState['is_completed']) {
            return null;
        }

        if ($dispatchState['code'] === 'missing') {
            return 'لا يمكن إكمال الوصول أو إغلاق العقد قبل إتمام إرسالية المكتب الخارجي.';
        }

        return 'إرسالية المكتب الخارجي غير مكتملة أو تحتاج تحديث: ' . $dispatchState['message'];
    }

    public static function syncArrivalStationFromDispatch(Contract $contract, TravelHistory $travelHistory): void
    {
        $contract->loadMissing('externalOfficeDispatch');

        if (blank($travelHistory->arrival_airport) && filled($contract->externalOfficeDispatch?->arrival_station)) {
            $travelHistory->arrival_airport = $contract->externalOfficeDispatch->arrival_station;
        }
    }

    private function currentRecruitmentStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ];
    }

    private function applyDispatchFilter($query, string $dispatchFilter, array $currentStatuses): void
    {
        if ($dispatchFilter === 'not_sent') {
            $query->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch');

            return;
        }

        if ($dispatchFilter === 'all') {
            return;
        }

        $query->whereHas('externalOfficeDispatch');
    }

    private function buildBulkSelectionGroupData(Contract $contract): array
    {
        $office = $contract->resolveExternalOfficeDispatchOffice();
        $nationality = $contract->maid?->nationality
            ?? $contract->info?->manualMaidNationality
            ?? $contract->requiredMaidNationality;
        $nationalityId = $contract->maid?->nationality_id
            ?? $contract->info?->manual_maid_nationality_id
            ?? $contract->required_maid_nationality_id;
        $officeId = $office?->id;

        return [
            'office_id' => $officeId ? (string) $officeId : '',
            'nationality_id' => $nationalityId ? (string) $nationalityId : '',
            'office_name' => $office?->title ?: 'غير محدد',
            'nationality_name' => $nationality?->display_name ?: 'غير محددة',
            'group_key' => ($officeId ?: 'missing') . ':' . ($nationalityId ?: 'missing'),
        ];
    }

    private function persistExternalOfficeDispatch(
        Contract $contract,
        string $dispatchDate,
        string $arrivalStation,
        string $officeContractCostUsd,
        $adminId
    ): void {
        $officeContractCostUsd = number_format((float) str_replace(',', '', $officeContractCostUsd), 2, '.', '');
        $sourceSnapshot = $contract->buildExternalOfficeDispatchSourceData($officeContractCostUsd);

        DB::transaction(function () use ($contract, $dispatchDate, $arrivalStation, $sourceSnapshot, $adminId, $officeContractCostUsd) {
            Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            $contract->unsetRelation('externalOfficeDispatch');
            $existingDispatch = $contract->externalOfficeDispatch;
            $invoice = $contract->invoice()->first();
            if ($existingDispatch && $invoice?->external_office_cost_constraint_id
                && ((string) $existingDispatch->office_contract_cost_usd !== $officeContractCostUsd
                    || (int) $existingDispatch->recruitment_office_id !== (int) ($sourceSnapshot['recruitment_office_id'] ?? 0))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'office_contract_cost_usd' => 'تم إثبات تكلفة المورد لهذا العقد؛ يجب تسوية القيد المحاسبي قبل تغيير التكلفة أو المكتب الخارجي.',
                ]);
            }

            $payload = array_merge($sourceSnapshot, [
                'dispatch_date' => $dispatchDate,
                'arrival_station' => $arrivalStation,
                'source_hash' => $contract->buildExternalOfficeDispatchSourceHash($officeContractCostUsd),
                'updated_by_admin_id' => $adminId,
            ]);

            if (!$existingDispatch) {
                $payload['created_by_admin_id'] = $adminId;
            }

            $contract->externalOfficeDispatch()->updateOrCreate(
                ['contract_id' => $contract->id],
                $payload
            );

            try {
                app(\App\Services\ingaz\RecruitmentTripAccountingService::class)->recordDispatchCostEntries($contract);
            } catch (\RuntimeException $exception) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'office_contract_cost_usd' => $exception->getMessage(),
                ]);
            }

            $travelHistory = $contract->travelHistory()->latest('id')->first();
            if ($travelHistory && $travelHistory->arrival_airport !== $payload['arrival_station']) {
                $travelHistory->update([
                    'arrival_airport' => $payload['arrival_station'],
                ]);
            }
        });
    }
}
