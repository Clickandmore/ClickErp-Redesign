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

        $dispatchFilter = $request->get('dispatch_filter', 'sent');
        $currentStatuses = $this->currentRecruitmentStatuses();

        $query = Contract::query()
            ->with([
                'user',
                'maid.religion',
                'maid.office',
                'maid.recruitmentOffice',
                'info',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
            ])
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($inner) use ($currentStatuses) {
                $inner->whereIn('status', $currentStatuses)
                    ->orWhereHas('externalOfficeDispatch');
            });

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
                ->addColumn('client', function (Contract $contract) {
                    $name = e($contract->user?->name ?? 'غير محدد');
                    $identity = e($contract->user?->national_num ?? '-');

                    return '<div class="fw-semibold">' . $name . '</div><small class="text-muted">هوية: ' . $identity . '</small>';
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
                    'contract_number',
                    'client',
                    'worker',
                    'office',
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
        ]);
    }

    public function edit(Contract $contract)
    {
        if (!$this->canViewRecruitmentContracts()) {
            abort(403, 'ليس لديك صلاحية لعرض إرسالية المكتب الخارجي.');
        }

        $contract->load([
            'user',
            'maid.religion',
            'maid.office',
            'maid.recruitmentOffice',
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
        ]);
    }

    public function store(Request $request, Contract $contract)
    {
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

        $officeContractCostUsd = number_format((float) $validated['office_contract_cost_usd'], 2, '.', '');
        $sourceSnapshot = $contract->buildExternalOfficeDispatchSourceData($officeContractCostUsd);
        $adminId = auth('admin')->id() ?? auth()->id();

        DB::transaction(function () use ($contract, $validated, $sourceSnapshot, $adminId, $officeContractCostUsd) {
            $existingDispatch = $contract->externalOfficeDispatch;

            $payload = array_merge($sourceSnapshot, [
                'dispatch_date' => $validated['dispatch_date'],
                'arrival_station' => trim((string) $validated['arrival_station']),
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

            $travelHistory = $contract->travelHistory()->latest('id')->first();
            if ($travelHistory && $travelHistory->arrival_airport !== $payload['arrival_station']) {
                $travelHistory->update([
                    'arrival_airport' => $payload['arrival_station'],
                ]);
            }
        });

        return redirect()
            ->route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id)
            ->with('success', 'تم حفظ إرسالية المكتب الخارجي بنجاح.');
    }

    public function print(Contract $contract, Request $request)
    {
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

        $pdf = PDF::loadView('admin.ingaz.RecruitmentContract.external_office_dispatch_print', $viewData)
            ->setPaper('a4', 'portrait');

        $pdf->setWarnings(false);
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
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
}
