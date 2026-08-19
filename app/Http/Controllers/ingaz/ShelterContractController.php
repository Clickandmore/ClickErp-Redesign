<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\ShelterContactStore;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\RentalContract;
use App\Models\ShelterLivingExpense;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use App\Services\ingaz\ShelterLivingExpenseAccountingService;
use App\Services\ManagedFileStorage;
use App\Support\AutomationSettings;
use App\Support\CvOptionsSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Enums\AccountTypesEnum;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class ShelterContractController extends Controller
{
    public $mainRoute = "ShelterContract";
    public $folderPath = "admin.ingaz.ShelterContract";
    protected ?int $instantJobTypeId = null;

    public function __construct()
    {
        $this->middleware($this->permissionMiddleware($this->shelterInsideViewPermissions()), ['only' => ['index', 'getData', 'outsideShelter']]);
        $this->middleware($this->permissionMiddleware($this->shelterAvailableForTransferViewPermissions()), ['only' => ['availableForTransfer']]);
        $this->middleware($this->permissionMiddleware($this->shelterDeportationStageViewPermissions()), ['only' => ['deportationStage']]);
        $this->middleware($this->permissionMiddleware($this->shelterDeportedViewPermissions()), ['only' => ['deported']]);
        $this->middleware($this->permissionMiddleware($this->shelterContractDetailViewPermissions()), [
            'only' => ['show', 'generalShelter', 'export', 'deliveryShelterContractDownload'],
        ]);
        $this->middleware($this->permissionMiddleware($this->shelterDeportationProceduresViewPermissions()), [
            'only' => ['showDeportationProcedures', 'getActivityLog'],
        ]);
        $this->middleware($this->permissionMiddleware(array_merge(
            $this->shelterAdditionCreatePermissions(),
            $this->shelterContractEditPermissions()
        )), [
            'only' => ['searchByPassport', 'getContractDetails', 'getNationalities', 'getShelterPlaces'],
        ]);
        $this->middleware($this->permissionMiddleware($this->shelterAdditionCreatePermissions()), ['only' => ['create', 'store']]);
        $this->middleware($this->permissionMiddleware($this->shelterContractEditPermissions()), ['only' => ['edit', 'update', 'storeLivingExpense', 'livingExpenses', 'reportEscape']]);
        $this->middleware($this->permissionMiddleware($this->shelterContractDetailViewPermissions()), ['only' => ['downloadEscapeReportAttachment']]);
        $this->middleware($this->permissionMiddleware($this->shelterRemoveFromShelterPermissions()), ['only' => ['back']]);
        $this->middleware($this->permissionMiddleware($this->shelterQuickTagsEditPermissions()), ['only' => ['updateQuickTags']]);
        $this->middleware($this->permissionMiddleware($this->shelterDeportationProceduresEditPermissions()), [
            'only' => ['startDeportation', 'updateDeportationProcedures'],
        ]);
        $this->middleware($this->permissionMiddleware($this->shelterWorkDesireEditPermissions()), ['only' => ['changeWorkDesire']]);
        $this->middleware($this->permissionMiddleware(array_merge(
            $this->shelterInstantReceiptEditPermissions(),
            $this->shelterRentalInstantReceiptEditPermissions()
        )), ['only' => ['toggleInstantReceipt']]);
        $this->middleware($this->permissionMiddleware([PermKeys::SHELTER_CONTRACT_DELETE, PermKeys::SHELTER_CONTRACT_CONTROL]), ['only' => ['destroy']]);
    }

    private function permissionMiddleware(array $permissions): string
    {
        return 'permission:' . implode('|', array_values(array_unique($permissions))) . ',admin';
    }

    private function adminCanAny(array $permissions): bool
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return false;
        }

        foreach (array_values(array_unique($permissions)) as $permission) {
            if ($admin->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function shelterAdditionCreatePermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_ADDITION_CREATE,
            PermKeys::SHELTER_CONTRACT_CREATE,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterInsideViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterAvailableForTransferViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterDeportationStageViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterDeportedViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterContractDetailViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
            PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW,
            PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW,
            PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW,
            PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterDeportationProceduresViewPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_VIEW,
            PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW,
            PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW,
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterContractEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterDeportationProceduresEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_EDIT,
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterWorkDesireEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_WORK_DESIRE_EDIT,
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterQuickTagsEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_QUICK_TAGS_EDIT,
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterInstantReceiptEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_INSTANT_RECEIPT_EDIT,
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterRemoveFromShelterPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_REMOVE_FROM_SHELTER_EDIT,
            PermKeys::SHELTER_CONTRACT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    private function shelterRentalInstantReceiptEditPermissions(): array
    {
        return [
            PermKeys::SHELTER_CONTRACT_RENTAL_INSTANT_RECEIPT_EDIT,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ];
    }

    public function index(Request $request)
    {
        $statsQuery = Contract::insideShelter()
            ->with([
                'maid.jobTypes',
                'maid.job_types.job_type.translations',
                'info',
                'latestShelterLog',
            ]);
        $this->applyRequestBranchFilter($statsQuery, $request, 'contracts');
        $statsContracts = $statsQuery->get();

        $totalContracts = $statsContracts->count();
        $wantsToWork = 0;
        $inDeportation = 0;
        $undecided = 0;
        $withinGuarantee = 0;
        $expiredGuarantee = 0;
        $guaranteeUnknown = 0;
        $availableForInstantReceipt = 0;
        $serviceCategoryStats = [
            'recruitment' => 0,
            'rental' => 0,
            'transfer' => 0,
        ];
        $rentalWorkerStats = [
            'total' => 0,
            'ready' => 0,
            'instant' => 0,
            'deportation' => 0,
            'undecided' => 0,
            'within_guarantee' => 0,
            'expired_guarantee' => 0,
            'unknown_guarantee' => 0,
        ];
        $quickTagCounts = [];

        foreach ($statsContracts as $contract) {
            $info = $contract->info;
            $workWillingness = $info?->wants_to_work;
            $serviceCategory = $this->determineShelterServiceCategory($contract->maid);
            $serviceCategoryStats[$serviceCategory] = ($serviceCategoryStats[$serviceCategory] ?? 0) + 1;
            $isRentalWorker = $serviceCategory === 'rental';

            if ($workWillingness === 'yes') {
                $wantsToWork++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['ready']++;
                }
                if ($info?->is_available_for_instant) {
                    $availableForInstantReceipt++;
                    if ($isRentalWorker) {
                        $rentalWorkerStats['instant']++;
                    }
                }
            } elseif ($workWillingness === 'no') {
                $inDeportation++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['deportation']++;
                }
            } else {
                $undecided++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['undecided']++;
                }
            }

            if ($isRentalWorker) {
                $rentalWorkerStats['total']++;
            }

            $guaranteeData = $this->resolveGuaranteeData($contract);
            if ($guaranteeData['status'] === 'within') {
                $withinGuarantee++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['within_guarantee']++;
                }
            } elseif ($guaranteeData['status'] === 'expired') {
                $expiredGuarantee++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['expired_guarantee']++;
                }
            } else {
                $guaranteeUnknown++;
                if ($isRentalWorker) {
                    $rentalWorkerStats['unknown_guarantee']++;
                }
            }

            foreach ($this->extractQuickTags($info?->shelter_quick_tags) as $tag) {
                $quickTagCounts[$tag] = ($quickTagCounts[$tag] ?? 0) + 1;
            }
        }

        $quickTagStats = collect($quickTagCounts)
            ->sortDesc()
            ->map(function ($count, $name) {
                return [
                    'name' => $name,
                    'count' => $count,
                ];
            })
            ->values();

        // Get filter options
        $nationalities = Nationality::with('translations')->get();
        $jobTypes = JobType::with('translations')->get();
        $shelters = ShelterPlaces::with('translations')->get();
        $quickTagSuggestions = $this->getQuickTagSuggestions();
        $livingExpenseAccounts = Account::query()
            ->whereDoesntHave('children')
            ->where('status', 'debtor')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $paymentAccounts = Account::query()
            ->whereDoesntHave('children')
            ->whereIn('type', [AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
        $custodyAccountIds = collect();
        if (Schema::hasTable('hr_employee_custodies')) {
            $custodyAccountIds = DB::table('hr_employee_custodies')
                ->whereNotNull('account_id')
                ->where('status', 'active')
                ->distinct()
                ->pluck('account_id');
        }
        $custodyAccounts = Account::query()
            ->whereDoesntHave('children')
            ->where(function ($query) use ($custodyAccountIds) {
                $query->whereHas('parent', function ($parentQuery) {
                    $parentQuery
                        ->where('type', AccountTypesEnum::Custody->value)
                        ->orWhere('code', 126);
                });

                if ($custodyAccountIds->isNotEmpty()) {
                    $query->orWhereIn('id', $custodyAccountIds);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
        $livingExpenseWorkerOptionsQuery = Contract::insideShelter()
            ->with(['maid:id,name,passport_num', 'info.shelter.translations'])
            ->whereNotNull('maid_id')
            ->whereHas('maid')
            ->orderBy('id');
        $this->applyRequestBranchFilter($livingExpenseWorkerOptionsQuery, $request, 'contracts');
        $livingExpenseWorkerOptions = $livingExpenseWorkerOptionsQuery->get()
            ->map(function (Contract $contract) {
                $shelterTitle = $contract->info?->shelter?->translations?->where('locale', app()->getLocale())->first()?->title
                    ?: $contract->info?->shelter?->translations?->first()?->title
                    ?: 'بدون إيواء محدد';
                $maidName = trim((string) ($contract->maid?->name ?? 'عاملة غير محددة'));
                $passport = trim((string) ($contract->maid?->passport_num ?? ''));

                return [
                    'contract_id' => $contract->id,
                    'shelter_id' => $contract->info?->shelter_id,
                    'label' => '#' . $contract->id . ' - ' . $maidName . ($passport !== '' ? ' - ' . $passport : ''),
                    'shelter' => $shelterTitle,
                ];
            });
        $defaultLivingExpenseAccountId = app(ShelterLivingExpenseAccountingService::class)->defaultExpenseAccountId();
        $defaultPaymentAccountId = (int) (ConfirmSetting::query()
            ->where('title', 'bond_sarf_account')
            ->latest('updated_at')
            ->value('value') ?? 0);
        $branchSelector = $this->branchSelectorData($request);

        return view('admin.ingaz.ShelterContract.index', compact(
            'totalContracts',
            'wantsToWork',
            'inDeportation',
            'undecided',
            'withinGuarantee',
            'expiredGuarantee',
            'guaranteeUnknown',
            'availableForInstantReceipt',
            'serviceCategoryStats',
            'rentalWorkerStats',
            'quickTagStats',
            'quickTagSuggestions',
            'nationalities',
            'jobTypes',
            'shelters',
            'livingExpenseAccounts',
            'paymentAccounts',
            'custodyAccounts',
            'livingExpenseWorkerOptions',
            'defaultLivingExpenseAccountId',
            'defaultPaymentAccountId',
            'branchSelector'
        ));
    }

    public function storeLivingExpense(Request $request, ShelterLivingExpenseAccountingService $service)
    {
        $data = $request->validate([
            'shelter_id' => ['nullable', 'integer', 'exists:shelter_places,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'expense_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'expense_account_id' => ['required', 'integer', 'exists:fn_accounts,id'],
            'payment_source_type' => ['required', 'in:bank,custody'],
            'payment_account_id' => ['required', 'integer', 'exists:fn_accounts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!empty($data['contract_id'])) {
            $this->assertCanAccessBranchModel(Contract::findOrFail($data['contract_id']), 'contracts');
        }

        try {
            $expense = $service->create($data);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل مصروف الإعاشة وتوزيعه على العاملات بنجاح.',
                'constraint_url' => $expense->constraint_id
                    ? route('finance.add-constraint.show', ['add_constraint' => $expense->constraint_id, 'autoprint' => 1])
                    : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function livingExpenses()
    {
        $expenses = ShelterLivingExpense::query()
            ->with(['shelter.translations', 'expenseAccount', 'paymentAccount', 'constraint', 'allocations.maid'])
            ->latest('expense_date')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(function (ShelterLivingExpense $expense) {
                $shelterTitle = $expense->shelter?->translations?->where('locale', app()->getLocale())->first()?->title
                    ?: $expense->shelter?->translations?->first()?->title
                    ?: 'كل الإيواء';

                return [
                    'id' => $expense->id,
                    'expense_date' => optional($expense->expense_date)->format('Y-m-d'),
                    'title' => $expense->title,
                    'shelter' => $shelterTitle,
                    'target' => $expense->worker_count === 1
                        ? ($expense->allocations->first()?->maid?->name ?: 'عاملة واحدة')
                        : 'توزيع على العاملات',
                    'total_amount' => number_format((float) $expense->total_amount, 2),
                    'worker_count' => $expense->worker_count,
                    'amount_per_worker' => number_format((float) $expense->amount_per_worker, 2),
                    'expense_account' => trim(($expense->expenseAccount?->code ? $expense->expenseAccount->code . ' / ' : '') . ($expense->expenseAccount?->name ?? '')),
                    'payment_source_type' => $expense->payment_source_type ?: 'bank',
                    'payment_account' => trim(($expense->paymentAccount?->code ? $expense->paymentAccount->code . ' / ' : '') . ($expense->paymentAccount?->name ?? '')),
                    'constraint_url' => $expense->constraint_id
                        ? route('finance.add-constraint.show', ['add_constraint' => $expense->constraint_id, 'autoprint' => 1])
                        : null,
                ];
            })
            ->values();

        return response()->json(['data' => $expenses]);
    }

    /**
     * Export filtered data
     */
    private function exportData($contracts, string $format = 'csv')
    {
        $format = strtolower($format);
        $rows = $this->buildExportRows(collect($contracts));

        return match ($format) {
            'xlsx', 'excel' => $this->exportExcelFile($rows),
            'pdf' => $this->exportPdfFile($rows),
            default => $this->exportCsvFile($rows),
        };
    }

    private function exportCsvFile(array $rows)
    {
        $headers = $this->exportHeadings();
        $filename = $this->buildExportFilename('csv');

        return response()->streamDownload(function () use ($rows, $headers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, array_values($row));
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportExcelFile(array $rows)
    {
        $headers = $this->exportHeadings();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->fromArray($headers, null, 'A1');

        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->applyFromArray([
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
            ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'shelter_contracts_xlsx_');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                $this->buildExportFilename('xlsx'),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    private function exportPdfFile(array $rows)
    {
        // الاسم المستعار PDF مرتبط بحزمة mPDF لأنها وحدها تشكّل الحروف العربية بشكل صحيح،
        // وهي لا تعرف setPaper/setOptions/setWarnings الخاصة بـ Dompdf. إعداد الصفحة يمرَّر
        // إلى loadView مباشرة: A4-L أي A4 عرضية.
        $pdf = PDF::loadView('admin.ingaz.ShelterContract.export_pdf', [
            'rows' => $rows,
            'headings' => $this->exportHeadings(),
            'exportedAt' => now()->format('Y-m-d H:i'),
        ], [], [
            'format' => 'A4-L',
            'mode' => 'utf-8',
        ]);

        return $pdf->download($this->buildExportFilename('pdf'));
    }

    private function buildExportFilename(string $extension): string
    {
        return 'shelter_contracts_' . now()->format('Y_m_d_H_i_s') . '.' . $extension;
    }

    private function exportHeadings(): array
    {
        return [
            'رقم العقد',
            'العميل',
            'العاملة',
            'رقم الجواز',
            'تصنيف الخدمة',
            'الجنسية',
            'مكان الإيواء',
            'تاريخ الوصول للمملكة',
            'تاريخ إدخال الإيواء',
            'مدة داخل المملكة',
            'مدة داخل الإيواء',
            'مصدر الاستلام',
            'حالة الضمان',
            'رغبة العمل',
            'التصنيفات السريعة',
            'حالة الاستلام الفوري / نقل الخدمات',
        ];
    }

    private function buildExportRows($contracts): array
    {
        return $contracts->map(function (Contract $contract) {
            $maid = $contract->maid;
            $arrivalDate = $this->resolveArrivalDate($contract);
            $shelterEntryDate = $this->resolveShelterEntryDate($contract);
            $guaranteeData = $this->resolveGuaranteeData($contract);
            $quickTags = $this->extractQuickTags(optional($contract->info)->shelter_quick_tags);
            $workWillingness = optional($contract->info)->wants_to_work;

            return [
                'رقم العقد' => $contract->id,
                'العميل' => $contract->user?->name ?? 'غير محدد',
                'العاملة' => $maid?->name ?: ('عاملة رقم: ' . ($maid?->id ?? '-')),
                'رقم الجواز' => $maid?->passport_num ?? 'غير محدد',
                'تصنيف الخدمة' => $this->serviceCategoryLabel($this->determineShelterServiceCategory($maid)),
                'الجنسية' => $this->resolveNationalityLabel($contract),
                'مكان الإيواء' => $this->resolveShelterLabel($contract),
                'تاريخ الوصول للمملكة' => $arrivalDate?->format('Y-m-d') ?? 'غير محدد',
                'تاريخ إدخال الإيواء' => $shelterEntryDate?->format('Y-m-d') ?? 'غير محدد',
                'مدة داخل المملكة' => $this->formatHumanDuration($arrivalDate),
                'مدة داخل الإيواء' => $this->formatHumanDuration($shelterEntryDate),
                'مصدر الاستلام' => $this->resolveReceiptSourceLabel(optional($contract->info)->receipt_source),
                'حالة الضمان' => $guaranteeData['label'],
                'رغبة العمل' => $this->formatWorkWillingnessLabel($workWillingness),
                'التصنيفات السريعة' => $quickTags ? implode('، ', $quickTags) : 'بدون تصنيف',
                'حالة الاستلام الفوري / نقل الخدمات' => $workWillingness === 'yes'
                    ? (optional($contract->info)->is_available_for_instant
                        ? ($this->determineShelterServiceCategory($maid) === 'rental'
                            ? 'متاحة للاستلام الفوري في الإيجار فقط'
                            : 'متاحة لنقل الخدمات')
                        : 'غير متاحة')
                    : 'غير مطبق',
            ];
        })->all();
    }

    private function supportsQuickTags(): bool
    {
        return Schema::hasColumn('contract_infos', 'shelter_quick_tags');
    }

    private function getQuickTagSuggestions(): array
    {
        if (!$this->supportsQuickTags()) {
            return [];
        }

        return ContractInfo::query()
            ->whereNotNull('shelter_quick_tags')
            ->pluck('shelter_quick_tags')
            ->flatMap(function ($value) {
                return $this->extractQuickTags($value);
            })
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeQuickTags($input): array
    {
        if (is_array($input)) {
            $tags = $input;
        } else {
            $tags = preg_split('/[\r\n,،|]+/u', (string) $input) ?: [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || in_array($tag, $normalized, true)) {
                continue;
            }

            $normalized[] = $tag;
        }

        return $normalized;
    }

    private function extractQuickTags($value): array
    {
        if (!$this->supportsQuickTags()) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }

        return $this->normalizeQuickTags(is_array($value) ? $value : (string) $value);
    }

    private function determineShelterServiceCategory(?Maid $maid): string
    {
        if (!$maid) {
            return 'recruitment';
        }

        $jobTypes = $maid->relationLoaded('jobTypes')
            ? $maid->jobTypes
            : $maid->jobTypes()->get();

        $title = $this->normalizeServiceCategoryTitle(
            $jobTypes->pluck('title')->filter()->implode(' ')
        );

        if ($title === '') {
            return 'recruitment';
        }

        if (str_contains($title, 'ايجار') || str_contains($title, 'تاجير') || str_contains($title, 'rent')) {
            return 'rental';
        }

        if (str_contains($title, 'استلام') || str_contains($title, 'instant') || str_contains($title, 'transfer') || str_contains($title, 'نقل')) {
            return 'transfer';
        }

        return 'recruitment';
    }

    /**
     * حقل "الاستلام الفوري" متاح دائمًا لغير عاملات الإيجار، أما عاملات الإيجار
     * فيتوقف على إعداد "إتاحة سيرة عاملة الإيجار لنقل الخدمات" في إعدادات السير الذاتية.
     */
    /**
     * @return array<int, string> تصنيف الخدمة لكل عقد معروض في قائمة الاختيار.
     */
    private function mapContractServiceCategories($contracts): array
    {
        return collect($contracts)
            ->mapWithKeys(fn (Contract $contract) => [
                $contract->id => $this->determineShelterServiceCategory($contract->maid),
            ])
            ->all();
    }

    private function instantReceiptFieldEnabledFor(?Maid $maid): bool
    {
        if ($this->determineShelterServiceCategory($maid) !== 'rental') {
            return true;
        }

        return CvOptionsSettings::rentalTransferEnabled();
    }

    private function normalizeServiceCategoryTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        $normalized = mb_strtolower(trim($title));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);

        return preg_replace('/\s+/', ' ', $normalized);
    }

    private function serviceCategoryLabel(string $category): string
    {
        return match ($category) {
            'rental' => 'تأجير',
            'transfer' => 'نقل خدمات',
            default => 'استقدام',
        };
    }

    private function getRentalServiceKeywords(): array
    {
        return ['ايجار', 'إيجار', 'تاجير', 'تأجير', 'rent', 'rental'];
    }

    private function getTransferServiceKeywords(): array
    {
        return ['استلام', 'إستلام', 'instant', 'transfer', 'نقل'];
    }

    private function applyJobTypeKeywordQuery($query, array $keywords): void
    {
        $query->where(function ($inner) use ($keywords) {
            foreach (array_values(array_unique($keywords)) as $index => $keyword) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $inner->{$method}('title', 'like', '%' . $keyword . '%');
            }
        });
    }

    private function resolveArrivalDate(Contract $contract): ?Carbon
    {
        $travelHistoryArrivalDate = $contract->relationLoaded('travelHistory')
            ? optional($contract->travelHistory)->arrival_date
            : null;

        $candidate = optional($contract->info)->arrival_date
            ?? $travelHistoryArrivalDate
            ?? optional($contract->info)->visa_milad_date
            ?? optional($contract->info)->contract_date
            ?? optional($contract->info)->ingaz_from;

        if (!$candidate) {
            return null;
        }

        try {
            return Carbon::parse($candidate);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveShelterEntryDate(Contract $contract): ?Carbon
    {
        // التاريخ المُدخل يدوياً في شاشة التعديل يتقدّم على طابع زمن سجل الحالة،
        // وإلا بقيت القائمة تعرض لحظة نقل العقد إلى الإيواء ولا تعكس أي تعديل لاحق.
        $candidate = optional($contract->info)->maid_date_back
            ?? optional($contract->latestShelterLog)->created_at
            ?? optional($contract->info)->created_at
            ?? $contract->created_at;

        if (!$candidate) {
            return null;
        }

        try {
            return Carbon::parse($candidate);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveGuaranteeData(Contract $contract): array
    {
        $guaranteePeriodDays = AutomationSettings::warrantyPeriodDays();
        $arrivalDate = $this->resolveArrivalDate($contract);
        if (!$arrivalDate) {
            return [
                'status' => 'unknown',
                'label' => 'غير محدد',
                'badge_class' => 'bg-secondary',
            ];
        }

        $guaranteeEndDate = $arrivalDate->copy()->addDays($guaranteePeriodDays);
        $today = now();

        if ($today->lte($guaranteeEndDate)) {
            return [
                'status' => 'within',
                'label' => 'ضمن الضمان (' . $today->diffInDays($guaranteeEndDate) . ' يوم)',
                'badge_class' => 'bg-success',
            ];
        }

        return [
            'status' => 'expired',
            'label' => 'منتهي الضمان (' . $guaranteeEndDate->diffInDays($today) . ' يوم)',
            'badge_class' => 'bg-danger',
        ];
    }

    private function formatHumanDuration(?Carbon $startDate, ?Carbon $endDate = null): string
    {
        if (!$startDate) {
            return 'غير محدد';
        }

        $endDate = $endDate ?: now();
        if ($startDate->isFuture()) {
            return '0 يوم';
        }

        $cursor = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        if ($cursor->equalTo($end)) {
            return 'اليوم';
        }

        $years = $cursor->diffInYears($end);
        $cursor->addYears($years);
        $months = $cursor->diffInMonths($end);
        $cursor->addMonths($months);
        $days = $cursor->diffInDays($end);

        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' سنة';
        }
        if ($months > 0) {
            $parts[] = $months . ' شهر';
        }
        if ($days > 0) {
            $parts[] = $days . ' يوم';
        }

        return $parts ? implode(' و ', array_slice($parts, 0, 3)) : '0 يوم';
    }

    private function formatWorkWillingnessLabel(?string $value): string
    {
        return match ($value) {
            'yes' => 'ترغب بالعمل',
            'no' => 'لا ترغب بالعمل',
            default => 'غير محدد',
        };
    }

    private function resolveReceiptSourceLabel(?string $value): string
    {
        if (!$value) {
            return 'غير محدد';
        }

        $translationKey = 'shelter_worker_statuses.received_by.' . $value;
        $translated = __($translationKey);

        return $translated === $translationKey ? $value : $translated;
    }

    private function resolveNationalityLabel(Contract $contract): string
    {
        $nationality = $contract->maid?->nationality;
        if (!$nationality) {
            return 'غير محدد';
        }

        $translation = $nationality->translations->where('locale', app()->getLocale())->first()
            ?? $nationality->translations->first();

        return $translation?->sub_title ?: ($translation?->title ?: 'غير محدد');
    }

    private function resolveShelterLabel(Contract $contract): string
    {
        $shelter = $contract->info?->shelter;
        if (!$shelter) {
            return 'غير محدد';
        }

        $translation = $shelter->translations->where('locale', app()->getLocale())->first()
            ?? $shelter->translations->first();

        return $translation?->title ?: 'غير محدد';
    }

    private function shelterSelectableContractsQuery()
    {
        $query = Contract::query()
            ->whereIn('status', $this->shelterEligibleContractStatuses())
            ->where($this->shelterArrivedContractConstraint())
            ->with([
                'maid.nationality.translations',
                'info',
                'user',
                'travelHistory',
                'complaints' => function ($query) {
                    $query->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                        ->latest();
                },
            ]);

        $this->applyRequestBranchFilter($query, request(), 'contracts');

        return $query->latest();
    }

    private function shelterEligibleContractStatuses(): array
    {
        return [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::UnderWarranty->value,
            ContractStatusEnum::Closed->value,
        ];
    }

    private function shelterArrivedContractConstraint(): \Closure
    {
        $today = now()->toDateString();

        return function ($query) use ($today) {
            $query->whereHas('info', function ($infoQuery) use ($today) {
                $infoQuery->whereNotNull('arrival_date')
                    ->whereDate('arrival_date', '<=', $today);
            })
                ->orWhereHas('maid', function ($maidQuery) use ($today) {
                    $maidQuery->whereNotNull('arrival_date')
                        ->whereDate('arrival_date', '<=', $today);
                })
                ->orWhereHas('travelHistory', function ($travelQuery) use ($today) {
                    $travelQuery->whereNotNull('arrival_date')
                        ->whereDate('arrival_date', '<=', $today);
                });
        };
    }

    private function resolveExplicitShelterArrivalDate(Contract $contract): ?Carbon
    {
        $candidate = optional($contract->info)->arrival_date
            ?: optional($contract->maid)->arrival_date
            ?: optional($contract->travelHistory)->arrival_date;

        if (!$candidate) {
            return null;
        }

        return $candidate instanceof \Carbon\CarbonInterface
            ? Carbon::instance($candidate)->startOfDay()
            : Carbon::parse($candidate)->startOfDay();
    }

    private function shelterIneligibilityDetails(?Contract $contract): ?array
    {
        if (!$contract) {
            return null;
        }

        $currentStatus = getContractStatus($contract->status);

        if (!in_array($contract->status, $this->shelterEligibleContractStatuses(), true)) {
            return [
                'message' => 'العقد موجود لكنه غير مؤهل للإيواء لأن حالته الحالية هي: ' . $currentStatus . '. الحالات المقبولة: وصول / مكتمل / تحت الضمان / مغلق.',
                'status' => $contract->status,
                'status_label' => $currentStatus,
            ];
        }

        $arrivalDate = $this->resolveExplicitShelterArrivalDate($contract);

        if (!$arrivalDate) {
            return [
                'message' => 'العقد موجود وحالته مناسبة، لكنه لا يحتوي على تاريخ وصول فعلي مسجل في بيانات العقد أو العاملة أو رحلة الوصول.',
                'status' => $contract->status,
                'status_label' => $currentStatus,
            ];
        }

        if ($arrivalDate->gt(now()->endOfDay())) {
            return [
                'message' => 'العقد موجود وحالته مناسبة، لكن تاريخ الوصول المسجل مستقبلي: ' . $arrivalDate->format('Y-m-d') . '. لا يمكن إضافته للإيواء قبل تاريخ الوصول.',
                'status' => $contract->status,
                'status_label' => $currentStatus,
                'arrival_date' => $arrivalDate->format('Y-m-d'),
            ];
        }

        return null;
    }

    private function contractCanBeAddedToShelter(Contract $contract): bool
    {
        if (!in_array($contract->status, $this->shelterEligibleContractStatuses(), true)) {
            return false;
        }

        $arrivalDate = $this->resolveExplicitShelterArrivalDate($contract);

        return $arrivalDate && $arrivalDate->lte(now()->endOfDay());
    }

    private function formatShelterComplaintOption(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'label' => '#' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($complaint->subject ?: 'بدون عنوان'),
        ];
    }

    private function formatShelterSelectableContract(Contract $contract): array
    {
        $maidName = optional($contract->info)->manual_maid_name ?: ($contract->maid->name ?? '');
        $passportNumber = optional($contract->info)->manual_maid_passport_num ?: ($contract->maid->passport_num ?? '');
        $nationality = $contract->maid && $contract->maid->nationality
            ? ($contract->maid->nationality->translations->where('locale', app()->getLocale())->first()?->title
                ?? $contract->maid->nationality->translations->first()?->title
                ?? '')
            : '';
        $clientName = $contract->user->name ?? '';
        $clientNationalId = $contract->user?->documentNumber() ?? '';
        $arrivalDate = optional($contract->info)->arrival_date
            ?: optional($contract->maid)->arrival_date
            ?: optional($contract->travelHistory)->arrival_date;
        if ($arrivalDate instanceof \Carbon\CarbonInterface) {
            $arrivalDate = $arrivalDate->format('Y-m-d');
        }

        $statusLabel = match ($contract->status) {
            ContractStatusEnum::Arrival->value => ' (مرحلة الوصول)',
            ContractStatusEnum::Completed->value, ContractStatusEnum::Closed->value => ' (مكتمل)',
            ContractStatusEnum::UnderWarranty->value => ' (تحت الضمان)',
            default => '',
        };

        $searchParts = array_filter([
            $contract->desc . $statusLabel,
            $maidName,
            $passportNumber,
            $clientName,
            $clientNationalId,
        ]);

        return [
            'id' => $contract->id,
            'desc' => implode(' - ', $searchParts),
            'passport_number' => $passportNumber,
            'maid_name' => $maidName,
            'maid_name_snapshot' => $maidName,
            'maid_nationality' => $nationality,
            'maid_nationality_snapshot' => $contract->maid?->nationality_id ?? '',
            'passport_snapshot' => $passportNumber,
            'client_name' => $clientName,
            'client_national_id' => $clientNationalId,
            'sponsorship_price' => optional($contract->info)->sponsorship_price ?? '',
            'receipt_date' => optional($contract->info)->receipt_form_date ?? $arrivalDate ?? ($contract->contract_date ?? ''),
            'receipt_text' => optional($contract->info)->receipt_form_text ?? '',
            'has_residency' => optional($contract->info)->workers_have_residency ?? '',
            'medical_exam' => optional($contract->info)->performing_medical_examination ?? '',
            'service_type' => optional($contract->info)->worker_service_type ?? '',
            'service_category' => $this->determineShelterServiceCategory($contract->maid),
            'arrival_date' => $arrivalDate,
            'status' => $contract->status,
            'shelter_complaints' => $contract->complaints
                ? $contract->complaints->map(fn (Complaint $complaint) => $this->formatShelterComplaintOption($complaint))->values()
                : [],
        ];
    }

    public function create()
    {
        $shelters = \App\Models\ShelterPlaces::latest()->get();

        $contracts = collect();

        // Debug: Log contracts data


        // إذا تم تمرير contract_id في الطلب، نختار العقد المحدد
        $selectedContractId = old('contract_id', request('contract_id'));

        if ($selectedContractId) {
            $selectedContract = $this->shelterSelectableContractsQuery()
                ->whereKey($selectedContractId)
                ->first();

            if ($selectedContract) {
                $contracts = collect([$selectedContract]);
            }
        }

        $nationalities = $this->shelterContractNationalities();
        $quickTagSuggestions = $this->getQuickTagSuggestions();
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $rentalInstantReceiptEnabled = CvOptionsSettings::rentalTransferEnabled();
        $contractServiceCategories = $this->mapContractServiceCategories($contracts);

        return view($this->folderPath . '.create', compact('shelters', 'contracts', 'selectedContractId', 'nationalities', 'quickTagSuggestions', 'complaintTypes', 'rentalInstantReceiptEnabled', 'contractServiceCategories'));
    }

    /**
     * Search contracts available for shelter by contract, client, worker, identity, or passport.
     */
    public function searchByPassport(Request $request)
    {
        $contractNumber = trim((string) $request->input('contract_number'));
        $searchTerm = trim((string) ($contractNumber ?: ($request->input('passport_number') ?: $request->input('search'))));
        $searchType = trim((string) $request->input('search_type'));

        if (!in_array($searchType, $this->shelterSearchTypes(), true)) {
            $searchType = '';
        }

        $query = $this->shelterSelectableContractsQuery();

        if ($contractNumber !== '') {
            $query->whereKey($contractNumber);
        } elseif ($searchType !== '' && $searchTerm !== '') {
            $this->applyShelterSearchType($query, $searchType, $searchTerm);
        } elseif ($searchTerm !== '') {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('id', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('national_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tax_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('license_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('phone', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($searchTerm) {
                        $maidQuery->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('passport_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('id_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('border_number', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('info', function ($infoQuery) use ($searchTerm) {
                        $infoQuery->where('manual_maid_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('manual_maid_passport_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('visa_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('msaned_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tafwed_number', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        $contracts = $query
            ->limit(50)
            ->get()
            ->map(fn ($contract) => $this->formatShelterSelectableContract($contract));

        $ineligibility = null;
        if ($contracts->isEmpty() && $searchTerm !== '') {
            $candidate = $this->findShelterSearchCandidate($contractNumber, $searchTerm, $searchType);
            $ineligibility = $this->shelterIneligibilityDetails($candidate);
        }

        return response()->json([
            'contracts' => $contracts,
            'ineligibility' => $ineligibility,
        ]);
    }

    private function shelterSearchTypes(): array
    {
        return ['contract_number', 'client', 'maid', 'identity', 'passport', 'musaned'];
    }

    private function applyShelterSearchType($query, string $searchType, string $searchTerm): void
    {
        $likeSearch = '%' . $searchTerm . '%';

        switch ($searchType) {
            case 'contract_number':
                $query->whereKey($searchTerm);
                break;

            case 'client':
                $query->whereHas('user', function ($userQuery) use ($searchTerm, $likeSearch) {
                    $userQuery->where(function ($userQuery) use ($searchTerm, $likeSearch) {
                        $userQuery->where('id', $searchTerm)
                            ->orWhere('name', 'like', $likeSearch)
                            ->orWhere('phone', 'like', $likeSearch);
                    });
                });
                break;

            case 'maid':
                $query->where(function ($query) use ($searchTerm, $likeSearch) {
                    $query->whereHas('maid', function ($maidQuery) use ($searchTerm, $likeSearch) {
                        $maidQuery->where(function ($maidQuery) use ($searchTerm, $likeSearch) {
                            $maidQuery->where('id', $searchTerm)
                                ->orWhere('name', 'like', $likeSearch)
                                ->orWhere('arabia_worker_number', 'like', $likeSearch);
                        });
                    })->orWhereHas('info', function ($infoQuery) use ($likeSearch) {
                        $infoQuery->where('manual_maid_name', 'like', $likeSearch);
                    });
                });
                break;

            case 'identity':
                $query->where(function ($query) use ($likeSearch) {
                    $query->whereHas('user', function ($userQuery) use ($likeSearch) {
                        $userQuery->where(function ($userQuery) use ($likeSearch) {
                            $userQuery->where('national_num', 'like', $likeSearch)
                                ->orWhere('tax_number', 'like', $likeSearch)
                                ->orWhere('license_number', 'like', $likeSearch);
                        });
                    })->orWhereHas('maid', function ($maidQuery) use ($likeSearch) {
                        $maidQuery->where(function ($maidQuery) use ($likeSearch) {
                            $maidQuery->where('id_number', 'like', $likeSearch)
                                ->orWhere('border_number', 'like', $likeSearch);
                        });
                    });
                });
                break;

            case 'passport':
                $query->where(function ($query) use ($likeSearch) {
                    $query->whereHas('maid', function ($maidQuery) use ($likeSearch) {
                        $maidQuery->where('passport_num', 'like', $likeSearch);
                    })->orWhereHas('info', function ($infoQuery) use ($likeSearch) {
                        $infoQuery->where('manual_maid_passport_num', 'like', $likeSearch);
                    });
                });
                break;

            case 'musaned':
                $query->where(function ($query) use ($likeSearch) {
                    if (Schema::hasColumn('contracts', 'msaned_number')) {
                        $query->where('msaned_number', 'like', $likeSearch)
                            ->orWhereHas('info', function ($infoQuery) use ($likeSearch) {
                                $infoQuery->where('msaned_number', 'like', $likeSearch);
                            });
                    } else {
                        $query->whereHas('info', function ($infoQuery) use ($likeSearch) {
                            $infoQuery->where('msaned_number', 'like', $likeSearch);
                        });
                    }
                });
                break;
        }
    }

    private function findShelterSearchCandidate(string $contractNumber, string $searchTerm, string $searchType = ''): ?Contract
    {
        $query = Contract::query()
            ->with(['maid.nationality.translations', 'info', 'user', 'travelHistory']);
        $this->applyRequestBranchFilter($query, request(), 'contracts');

        if ($contractNumber !== '') {
            $contract = (clone $query)->whereKey((int) ($contractNumber ?: $searchTerm))->first();
            if ($contract) {
                return $contract;
            }
        }

        if ($searchType !== '') {
            $this->applyShelterSearchType($query, $searchType, $searchTerm);

            return $query->first();
        }

        if (ctype_digit($searchTerm)) {
            $contract = (clone $query)->whereKey((int) $searchTerm)->first();
            if ($contract) {
                return $contract;
            }
        }

        return $query
            ->where(function ($query) use ($searchTerm) {
                $query->where('id', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('national_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tax_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('license_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('phone', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($searchTerm) {
                        $maidQuery->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('passport_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('id_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('border_number', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('info', function ($infoQuery) use ($searchTerm) {
                        $infoQuery->where('manual_maid_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('manual_maid_passport_num', 'like', '%' . $searchTerm . '%')
                            ->orWhere('visa_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('msaned_number', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tafwed_number', 'like', '%' . $searchTerm . '%');
                    });
            })
            ->first();
    }

    public function store(ShelterContactStore $request)
    {
        $contract = Contract::with(['maid.nationality', 'user', 'info', 'travelHistory'])->find($request->contract_id);

        if (
            !$contract ||
            !$this->contractCanBeAddedToShelter($contract)
        ) {
            return jsonError('لا يمكن إضافة هذا العقد للإيواء. يجب أن يكون العقد في مرحلة وصول/ضمان/مكتمل وأن تكون العاملة وصلت فعليًا.', 422);
        }

        $this->assertCanAccessBranchModel($contract, 'contracts');

        $wantsToWork = $request->input('wants_to_work') ?: 'unknown';
        $displayOnSite = $wantsToWork === 'yes'
            && $request->boolean('is_available_for_instant')
            && $this->instantReceiptFieldEnabledFor($contract->maid);
        $existingAttachments = $contract->info ? ($contract->info->shelter_attachments ?? []) : [];
        $attachments = $this->prepareShelterAttachments($request, $existingAttachments);

        $infoPayload = [
            'shelter_id' => $request->shelter_id,
            'receipt_source' => $request->receipt_source,
            'maid_date_back' => $request->maid_date_back,
            'salary_final_status' => $request->salary_final_status,
            'duration_from_enter' => $request->duration_from_enter,
            'wants_to_work' => $wantsToWork,
            'back_reasons' => $request->back_reasons,
            'arrival_date' => $request->arrival_date,
            'is_available_for_instant' => $displayOnSite,
            'shelter_attachments' => $attachments,
            'workers_have_residency' => $request->workers_have_residency,
            'performing_medical_examination' => $request->performing_medical_examination,
            'worker_service_type' => $request->worker_service_type,
            // 'client_name_snapshot' => $request->client_name_snapshot ?? optional($contract->user)->name,
            // 'client_national_id' => $request->client_national_id ?? optional($contract->user)->national_num,
            // 'worker_name_snapshot' => $request->maid_name_snapshot ?? optional($contract->maid)->name,
            // 'worker_nationality_snapshot' => $request->maid_nationality_snapshot ?? optional(optional($contract->maid)->nationality)->name_ar,
            // 'passport_number_snapshot' => $request->passport_number_snapshot ?? optional($contract->maid)->passport_num,
            // 'sponsorship_price' => $request->sponsorship_price ?? optional($contract->info)->sponsorship_price,
            // 'receipt_form_date' => $request->receipt_form_date,
            'receipt_form_text' => $request->receipt_form_text,
        ];

        if ($this->supportsQuickTags()) {
            $infoPayload['shelter_quick_tags'] = $this->normalizeQuickTags($request->input('shelter_quick_tags'));
        }

        DB::transaction(function () use ($contract, $infoPayload, $request) {
            if ($contract->info) {
                $contract->info()->update($infoPayload);
            } else {
                $contract->info()->create(array_merge(['contract_id' => $contract->id], $infoPayload));
            }

            $contract->status = ContractStatusEnum::Shelter->value;
            $contract->save();

            $contract->contract_status_logs()->create([
                'contract_id' => $contract->id,
                'status' => ContractStatusEnum::Shelter->value,
            ]);

            $this->syncShelterComplaint($contract, $request);
        });

        // dd($contract->maid->id);
        // $maid = Maid::find($contract->maid->id);

        $this->syncMaidInstantDisplay($contract, $displayOnSite);

        return jsonSuccess(['success' => true], __('ingaz.shelter_contract_stored'), 200);
    }

    private function syncShelterComplaint(Contract $contract, ShelterContactStore $request): void
    {
        if (!Schema::hasColumn('complaints', 'contract_id')) {
            return;
        }

        $action = $request->input('complaint_action', 'none');

        if ($action === 'link' && $request->filled('shelter_complaint_id')) {
            Complaint::whereKey($request->input('shelter_complaint_id'))->update([
                'contract_id' => $contract->id,
                'maid_passport' => $request->input('passport_number_snapshot') ?: optional($contract->maid)->passport_num,
                'maid_nationality_id' => $request->input('maid_nationality_snapshot') ?: optional($contract->maid)->nationality_id,
            ]);

            return;
        }

        if ($action !== 'create') {
            return;
        }

        $user = $contract->user;
        if (!$user) {
            return;
        }

        $details = trim((string) $request->input('shelter_complaint_details'));
        if ($details === '') {
            $details = trim((string) $request->input('back_reasons')) ?: 'تم إنشاء الشكوى تلقائياً من إدخال العاملة للإيواء.';
        }
        $complaintTypeId = $request->input('shelter_complaint_type_id')
            ?: ComplaintType::query()->orderBy('id')->value('id');

        Complaint::create([
            'contract_id' => $contract->id,
            'user_id' => $user->id,
            'address' => 'إيواء العاملة',
            'message' => mb_substr($details, 0, 1000),
            'details' => $details,
            'subject' => $request->input('shelter_complaint_subject') ?: 'شكوى مرتبطة بإدخال العاملة للإيواء',
            'complaint_type_id' => $complaintTypeId,
            'priority' => $request->input('shelter_complaint_priority', 'medium'),
            'status' => 'pending',
            'complainant_name' => $request->input('client_name_snapshot') ?: $user->name,
            'complainant_phone' => ltrim((string) ($user->phone ?? ''), '0'),
            'maid_passport' => $request->input('passport_number_snapshot') ?: optional($contract->maid)->passport_num,
            'maid_nationality_id' => $request->input('maid_nationality_snapshot') ?: optional($contract->maid)->nationality_id,
        ]);
    }


    public function show($id)
    {
        $contract = Contract::with([
            'user',
            'maid.nationality',
            'maid.religion',
            'maid.job_types.job_type',
            'info.shelter'
        ])->findOrFail($id);

        $this->assertCanAccessBranchModel($contract, 'contracts');

        // حساب حالة الضمان
        $warrantyStatus = 'غير محدد';
        $warrantyDays = 0;
        $guaranteeData = $this->resolveGuaranteeData($contract);
        if ($guaranteeData['status'] === 'within') {
            $warrantyStatus = 'ضمن الضمان';
            preg_match('/\((\d+) يوم\)/u', $guaranteeData['label'], $matches);
            $warrantyDays = isset($matches[1]) ? (int) $matches[1] : 0;
        } elseif ($guaranteeData['status'] === 'expired') {
            $warrantyStatus = 'منتهي الضمان';
            preg_match('/\((\d+) يوم\)/u', $guaranteeData['label'], $matches);
            $warrantyDays = isset($matches[1]) ? (int) $matches[1] : 0;
        }

        return view('admin.ingaz.ShelterContract.show', compact('contract', 'warrantyStatus', 'warrantyDays'));
    }


    public function edit($id)
    {
        $contract = Contract::with(['info', 'maid.nationality', 'user'])->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');
        $shelters = \App\Models\ShelterPlaces::latest()->get();
        $contracts = collect([$contract]);
        $selectedContractId = $contract->id;
        $nationalities = $this->shelterContractNationalities();
        $quickTagSuggestions = $this->getQuickTagSuggestions();
        $isEditMode = true;
        $editingContract = $contract;
        $rentalInstantReceiptEnabled = CvOptionsSettings::rentalTransferEnabled();
        $contractServiceCategories = $this->mapContractServiceCategories($contracts);

        return view(
            $this->folderPath . '.create',
            compact(
                'shelters',
                'contracts',
                'selectedContractId',
                'nationalities',
                'quickTagSuggestions',
                'isEditMode',
                'editingContract',
                'rentalInstantReceiptEnabled',
                'contractServiceCategories'
            )
        );
    }

    private function shelterContractNationalities()
    {
        return Nationality::with('translations')
            ->get()
            ->sortBy(fn (Nationality $nationality) => $nationality->display_name)
            ->values();
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'shelter_id' => 'required|exists:shelter_places,id',
            'receipt_source' => 'required|string',
            'maid_date_back' => 'required|date',
            'salary_final_status' => 'nullable|in:received,not_received',
            'duration_from_enter' => 'required',
            'wants_to_work' => 'nullable|in:yes,no,unknown',
            'is_available_for_instant' => 'nullable|boolean',
            'back_reasons' => 'nullable|string',
            'arrival_date' => 'required|date',
            'workers_have_residency' => 'nullable|in:yes,no',
            'performing_medical_examination' => 'nullable|in:yes,no',
            'worker_service_type' => 'nullable|in:deportation,transfer',
            'receipt_form_text' => 'nullable|string',
            'shelter_quick_tags' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $contract = Contract::with('maid')->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');
        $wantsToWork = $request->input('wants_to_work') ?: 'unknown';
        $displayOnSite = $wantsToWork === 'yes'
            && $request->boolean('is_available_for_instant')
            && $this->instantReceiptFieldEnabledFor($contract->maid);
        // $existingAttachments = $contract->info ? ($contract->info->shelter_attachments ?? []) : [];
        $existingAttachments = $contract->info && $contract->info->shelter_attachments ?
            (
                is_array($contract->info->shelter_attachments) ?
                $contract->info->shelter_attachments :
                json_decode($contract->info->shelter_attachments, true)
            ) : [];

        $attachments = $this->prepareShelterAttachments($request, $existingAttachments);

        $infoPayload = [
            'shelter_id' => $request->shelter_id,
            'receipt_source' => $request->receipt_source,
            'maid_date_back' => $request->maid_date_back,
            'salary_final_status' => $request->salary_final_status,
            'duration_from_enter' => $request->duration_from_enter,
            'wants_to_work' => $wantsToWork,
            'is_available_for_instant' => $displayOnSite,
            'back_reasons' => $request->back_reasons,
            'arrival_date' => $request->arrival_date,
            'workers_have_residency' => $request->workers_have_residency,
            'performing_medical_examination' => $request->performing_medical_examination,
            'worker_service_type' => $request->worker_service_type,
            'receipt_form_text' => $request->receipt_form_text,
            'shelter_attachments' => $attachments,
        ];

        if ($this->supportsQuickTags()) {
            $infoPayload['shelter_quick_tags'] = $this->normalizeQuickTags($request->input('shelter_quick_tags'));
        }

        $contract->info()->update($infoPayload);

        $this->syncMaidInstantDisplay($contract, $displayOnSite);

        return jsonSuccess(['success' => true], __('ingaz.shelter_contract_updated'), 200);
    }


    public function destroy($id)
    {
        //
    }

    private function prepareShelterAttachments(Request $request, array $existing = []): array
    {
        $attachments = $existing;

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = [
                    'path' => app(ManagedFileStorage::class)->uploadFile(
                        $file,
                        'shelter-contracts/attachments'
                    ),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        return $attachments;
    }

    private function syncMaidInstantDisplay(Contract $contract, bool $displayOnSite): void
    {
        $maid = $contract->maid;
        if (!$maid) {
            return;
        }

        $instantJobTypeId = $this->getInstantJobTypeId();

        if (!$instantJobTypeId) {
            return;
        }

        $isRentalWorker = $this->determineShelterServiceCategory($maid) === 'rental';

        if ($displayOnSite) {
            if ($isRentalWorker) {
                // Availability is metadata for rental workers. Keep their public
                // service exclusively rental and remove any legacy transfer tag.
                $maid->jobTypes()->detach($instantJobTypeId);
            } else {
                $maid->jobTypes()->syncWithoutDetaching([$instantJobTypeId]);
            }
            $maid->is_show = true;
            // $maid->status = 'in_accommodation';
            $maid->maid_accommodation_type = 2;
            $maid->available = 0;
            $maid->save();
        } else {
            $maid->jobTypes()->detach($instantJobTypeId);
            if ($maid->jobTypes()->count() === 0) {
                $maid->is_show = false;
                // if ($maid->status === 'in_accommodation') {
                //     $maid->status = 'available';
                // }
                if ($maid->maid_accommodation_type === 2) {
                    $maid->status = 'available';
                }
                $maid->available = 1;
            }
            $maid->save();
        }
    }

    private function getInstantJobTypeId(): ?int
    {
        if ($this->instantJobTypeId !== null) {
            return $this->instantJobTypeId;
        }

        $jobType = JobType::whereHas('translations', function ($query) {
            $query->where('title', 'LIKE', '%استلام%')
                ->orWhere('title', 'LIKE', '%إستلام%')
                ->orWhere('title', 'LIKE', '%instant%')
                ->orWhere('title', 'LIKE', '%Instant%');
        })->first();

        if (!$jobType) {
            $jobType = JobType::find(3);
        }

        $this->instantJobTypeId = $jobType?->id;

        return $this->instantJobTypeId;
    }


    /**
     * Get shelter places
     */
    public function getShelterPlaces()
    {
        $shelterPlaces = \App\Models\ShelterPlaces::with('translations')->get();
        return response()->json($shelterPlaces);
    }

    /**
     * Get contract details for duration calculation
     */
    public function getContractDetails(Request $request)
    {
        try {
            $contractId = $request->input('contract_id');
            $contract = Contract::with(['maid', 'user', 'info'])->find($contractId);

            if (!$contract) {
                return response()->json(['error' => 'العقد غير موجود'], 404);
            }

            $this->assertCanAccessBranchModel($contract, 'contracts');

            $data = [
                'contract_id' => $contract->id,
                'maid_name' => $contract->maid ? $contract->maid->name : null,
                'maid_passport' => $contract->maid ? $contract->maid->passport_num : null,
                'maid_nationality' => $contract->maid && $contract->maid->nationality ?
                    $contract->maid->nationality->translations->where('locale', app()->getLocale())->first()->sub_title ??
                    $contract->maid->nationality->translations->first()->sub_title : null,
                'sponsor_name' => $contract->user ? $contract->user->name : null,
                'sponsor_id' => $contract->user ? $contract->user->id_number : null,
                'sponsor_phone' => $contract->user ? $contract->user->phone : null,
                'sponsor_city' => $contract->user ? $contract->user->city : null,
            ];

            return response()->json($data);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error in getContractDetails: ' . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ في جلب بيانات العقد'], 500);
        }
    }

    /**
     * Get nationalities for filter dropdown
     */
    public function getNationalities()
    {
        $nationalities = \App\Models\ingaz\Nationality::with('translations')->get();
        return response()->json($nationalities);
    }

    /**
     * Show contracts available for transfer (work_willingness = 'yes')
     */
    public function availableForTransfer(Request $request)
    {
        $filterStatus = Request('s');
        if ($request->ajax()) {
            try {
                $rows = Contract::insideShelter()
                    ->with([
                        'maid',
                        'maid.jobTypes',
                        'user',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations',
                        'latestShelterLog'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->where(function ($q) {
                            $q->where('wants_to_work', 'yes')
                                ->orWhere('wants_to_work', 1);
                        });
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->orderByDesc($this->getShelterOrderExpression());

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get(), $request->input('export_format', 'csv'));
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);



                return $dataTable;
            } catch (\Exception $e) {

                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }



        $data["dataTableRoute"] = route($this->mainRoute . ".availableForTransfer");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.available-for-transfer', $data);
    }

    /**
     * Show contracts in deportation stage (work_willingness = 'no')
     */
    public function deportationStage(Request $request)
    {
        $filterStatus = Request('s');

        // حساب الإحصائيات لمرحلة الترحيل
        $deportationContracts = Contract::insideShelter()
            ->whereHas('info', function ($query) {
                $query->where('wants_to_work', 'no');
            });
        $this->applyRequestBranchFilter($deportationContracts, $request, 'contracts');

        // إجمالي العقود في مرحلة الترحيل
        $totalDeportation = $deportationContracts->count();

        // العقود التي لم تبدأ إجراءات الترحيل بعد
        $notStarted = $deportationContracts->clone()
            ->whereDoesntHave('deportationProcedure')
            ->count();

        // العقود قيد التنفيذ (لديها إجراءات ترحيل ولكن لم تكتمل)
        $inProgress = $deportationContracts->clone()
            ->whereHas('deportationProcedure', function ($query) {
                $query->where('status', 'pending');
            })
            ->count();

        // العقود التي تم حجز التذاكر لها
        $ticketsBooked = $deportationContracts->clone()
            ->whereHas('deportationProcedure', function ($query) {
                $query->where('has_final_exit', true)
                    ->whereNotNull('final_exit_date');
            })
            ->count();

        // العقود التي تم ترحيلها (مكتملة)
        $completed = $deportationContracts->clone()
            ->whereHas('deportationProcedure', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();

        if ($request->ajax()) {
            // إذا كان الطلب للإحصائيات فقط
            if ($request->has('statistics') && $request->input('statistics') == 'true') {
                return response()->json([
                    'statistics' => [
                        'total' => $totalDeportation,
                        'not_started' => $notStarted,
                        'in_progress' => $inProgress,
                        'tickets_booked' => $ticketsBooked,
                        'completed' => $completed
                    ]
                ]);
            }

            try {
                $rows = Contract::insideShelter()
                    ->with([
                        'maid',
                        'maid.jobTypes',
                        'user',
                        'info',
                        'deportationProcedure',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations',
                        'latestShelterLog'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->where('wants_to_work', 'no');
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->orderByDesc($this->getShelterOrderExpression());

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get(), $request->input('export_format', 'csv'));
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);

                \Log::info('DataTable response created successfully');
                \Log::info('DataTable response: ' . json_encode($dataTable->getData()));

                return $dataTable;
            } catch (\Exception $e) {
                \Log::error('Error in deportationStage: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('deportationStage returning view');

        $data["dataTableRoute"] = route($this->mainRoute . ".deportationStage");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();

        // إضافة الإحصائيات للبيانات المرسلة للعرض
        $data["statistics"] = [
            'total' => $totalDeportation,
            'not_started' => $notStarted,
            'in_progress' => $inProgress,
            'tickets_booked' => $ticketsBooked,
            'completed' => $completed
        ];

        return view($this->folderPath . '.deportation-stage', $data);
    }

    /**
     * Show general shelter contracts (work_willingness = 'unknown' or null)
     */
    public function generalShelter(Request $request)
    {
        \Log::info('generalShelter method called');
        \Log::info('Request data: ' . json_encode($request->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        $filterStatus = Request('s');

        if ($request->ajax()) {
            \Log::info('generalShelter AJAX request');
            try {
                $rows = Contract::insideShelter()
                    ->with([
                        'maid',
                        'maid.jobTypes',
                        'user',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->where(function ($inner) {
                            $inner->where('wants_to_work', 'unknown')
                                ->orWhereNull('wants_to_work');
                        });
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->orderByDesc($this->getShelterOrderExpression());

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get(), $request->input('export_format', 'csv'));
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);

                \Log::info('DataTable response created successfully');
                \Log::info('DataTable response: ' . json_encode($dataTable->getData()));

                return $dataTable;
            } catch (\Exception $e) {
                \Log::error('Error in generalShelter: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('generalShelter returning view');
        $data["dataTableRoute"] = route($this->mainRoute . ".generalShelter");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.general-shelter', $data);
    }

    public function outsideShelter(Request $request)
    {
        if ($request->ajax()) {
            try {
                $rows = Contract::outsideShelter()
                    ->with([
                        'maid',
                        'maid.jobTypes',
                        'user',
                        'info.shelter.translations',
                        'info.outsideShelterBy',
                        'info.outsideReference',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'maid.rentalContracts',
                        'sponsorshipTransfers',
                    ]);

                $this->applyFilters($rows, $request);

                $rows = $rows->orderByDesc($this->getShelterOrderExpression());

                return DataTables::of($rows)
                    ->addIndexColumn()
                    ->addColumn('contract_number', function ($row) {
                        return 'عقد رقم: ' . $row->id;
                    })
                    ->addColumn('maid_data', function ($row) {
                        if (!$row->maid) {
                            return '<span class="text-muted">غير محدد</span>';
                        }

                        $maid = $row->maid;
                        $name = $maid->name ?: 'عاملة رقم: ' . $maid->id;
                        $passport = $maid->passport_num ?: 'غير محدد';

                        return '<div class="maid-info">'
                            . '<strong>' . e($name) . '</strong><br>'
                            . '<small class="text-muted">جواز: ' . e($passport) . '</small>'
                            . '</div>';
                    })
                    ->addColumn('outside_reason', function ($row) {
                        $reason = $this->resolveOutsideShelterReason($row);

                        return match ($reason) {
                            'rental_delivery' => '<span class="badge bg-primary">تسليم عقد إيجار</span>',
                            'transfer_delivery' => '<span class="badge bg-success">تسليم نقل خدمات</span>',
                            'escape_report' => '<span class="badge bg-danger"><i class="fas fa-running me-1"></i>بلاغ هروب</span>',
                            default => '<span class="badge bg-secondary">غير محدد</span>',
                        };
                    })
                    ->addColumn('outside_details', function ($row) {
                        if (!$row->hasEscapeReport()) {
                            return '<span class="text-muted">—</span>';
                        }

                        $note = trim((string) $row->info?->escape_report_note);
                        $reporter = $row->info?->outsideShelterBy?->name;
                        $html = $note !== ''
                            ? '<div class="small">' . nl2br(e($note)) . '</div>'
                            : '<span class="text-muted">بدون ملاحظة</span>';

                        if ($reporter) {
                            $html .= '<div class="text-muted small mt-1"><i class="fas fa-user me-1"></i>' . e($reporter) . '</div>';
                        }

                        return $html;
                    })
                    ->addColumn('outside_at', function ($row) {
                        $timestamp = $this->resolveOutsideShelterAt($row);

                        if ($timestamp instanceof \Carbon\CarbonInterface) {
                            return $timestamp->format('Y-m-d H:i');
                        }

                        if ($timestamp) {
                            try {
                                return \Carbon\Carbon::parse($timestamp)->format('Y-m-d H:i');
                            } catch (\Throwable) {
                            }
                        }

                        return 'غير محدد';
                    })
                    ->addColumn('reference_link', function ($row) {
                        if ($row->hasEscapeReport()) {
                            $attachment = $row->info?->escape_report_attachment;

                            if (!is_array($attachment) || empty($attachment['path'])) {
                                return '<span class="text-muted">لا يوجد ملف</span>';
                            }

                            return '<a href="' . e(route('ShelterContract.escapeReport.attachment', $row->id)) . '" class="btn btn-sm btn-outline-danger">'
                                . '<i class="fas fa-paperclip"></i> ' . e($attachment['name'] ?? 'تحميل مرفق البلاغ')
                                . '</a>';
                        }

                        $reference = $this->resolveOutsideShelterReference($row);

                        if (!$reference) {
                            return '<span class="text-muted">لا يوجد</span>';
                        }

                        $label = 'عرض المرجع';
                        $url = null;

                        switch (get_class($reference)) {
                            case RentalContract::class:
                                $url = route('RentContract.show', $reference->id);
                                $label = 'عرض عقد الإيجار';
                                break;
                            case SponsorshipTransfer::class:
                                $url = route('SponsorshipTransfer.show', $reference->id);
                                $label = 'عرض طلب نقل الكفالة';
                                break;
                        }

                        if (!$url) {
                            return '<span class="text-muted">لا يوجد</span>';
                        }

                        return '<a href="' . e($url) . '" class="btn btn-sm btn-outline-primary" target="_blank">'
                            . '<i class="fas fa-external-link-alt"></i> ' . e($label)
                            . '</a>';
                    })
                    ->addColumn('actions', function ($row) {
                        $actions = '<div class="dropdown actions-dropdown">'
                            . '<button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
                            . '<i class="fas fa-cogs me-1"></i> الإجراءات'
                            . '</button>'
                            . '<ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                        $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.show', $row->id) . '">
                                        <i class="fas fa-eye"></i> عرض تفاصيل العقد
                                    </a></li>';

                        $reference = $this->resolveOutsideShelterReference($row);

                        if ($reference instanceof RentalContract) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('RentContract.show', $reference->id) . '">
                                            <i class="fas fa-file-contract"></i> عرض عقد الإيجار
                                        </a></li>';
                        }

                        if ($reference instanceof SponsorshipTransfer) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.show', $reference->id) . '">
                                            <i class="fas fa-people-carry"></i> عرض طلب نقل الكفالة
                                        </a></li>';
                        }

                        $actions .= '</ul></div>';

                        return $actions;
                    })
                    ->rawColumns(['maid_data', 'outside_reason', 'outside_details', 'reference_link', 'actions'])
                    ->make(true);
            } catch (\Exception $e) {
                \Log::error('Error in outsideShelter: ' . $e->getMessage());
                return response()->json([
                    'error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()
                ], 500);
            }
        }

        $outsideContracts = Contract::outsideShelter();
        $this->applyRequestBranchFilter($outsideContracts, $request, 'contracts');
        $outsideTotal = $outsideContracts->clone()->count();
        $outsideRental = $this->applyOutsideReasonFilter($outsideContracts->clone(), 'rental_delivery')->count();
        $outsideTransfer = $this->applyOutsideReasonFilter($outsideContracts->clone(), 'transfer_delivery')->count();
        $outsideEscape = $this->applyOutsideReasonFilter($outsideContracts->clone(), 'escape_report')->count();
        $outsideOther = max(0, $outsideTotal - $outsideRental - $outsideTransfer - $outsideEscape);

        $data["dataTableRoute"] = route($this->mainRoute . '.outside');
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        $data['statistics'] = [
            'total' => $outsideTotal,
            'rental' => $outsideRental,
            'transfer' => $outsideTransfer,
            'escape' => $outsideEscape,
            'other' => $outsideOther,
        ];

        return view($this->folderPath . '.outside', $data);
    }

    private function resolveOutsideShelterReason(Contract $contract): ?string
    {
        if ($contract->info?->outside_shelter_reason) {
            return $contract->info->outside_shelter_reason;
        }

        if ($this->resolveActiveTransferReference($contract)) {
            return 'transfer_delivery';
        }

        if ($this->resolveActiveRentalReference($contract)) {
            return 'rental_delivery';
        }

        return null;
    }

    private function resolveOutsideShelterAt(Contract $contract)
    {
        if ($contract->info?->outside_shelter_at) {
            return $contract->info->outside_shelter_at;
        }

        $reference = $this->resolveOutsideShelterReference($contract);

        return $reference?->maid_delivered_at
            ?? $reference?->trial_started_at
            ?? $reference?->transferred_at
            ?? null;
    }

    private function resolveOutsideShelterReference(Contract $contract): RentalContract|SponsorshipTransfer|null
    {
        $info = $contract->info;

        if ($info && $info->outside_shelter_reference_type && $info->outside_shelter_reference_id) {
            if ($info->relationLoaded('outsideReference') && $info->outsideReference) {
                return $info->outsideReference;
            }

            if ($info->outside_shelter_reference_type === RentalContract::class) {
                return RentalContract::find($info->outside_shelter_reference_id);
            }

            if ($info->outside_shelter_reference_type === SponsorshipTransfer::class) {
                return SponsorshipTransfer::find($info->outside_shelter_reference_id);
            }
        }

        return $this->resolveActiveTransferReference($contract)
            ?: $this->resolveActiveRentalReference($contract);
    }

    private function resolveActiveRentalReference(Contract $contract): ?RentalContract
    {
        if ($contract->maid && $contract->maid->relationLoaded('rentalContracts')) {
            return $contract->maid->rentalContracts
                ->whereIn('status', ['maid_delivered', 'active'])
                ->sortByDesc('id')
                ->first();
        }

        return $contract->maid?->rentalContracts()
            ->whereIn('status', ['maid_delivered', 'active'])
            ->latest('id')
            ->first();
    }

    private function resolveActiveTransferReference(Contract $contract): ?SponsorshipTransfer
    {
        if ($contract->relationLoaded('sponsorshipTransfers')) {
            return $contract->sponsorshipTransfers
                ->whereIn('status', ['maid_delivered', 'trial', 'trial_started'])
                ->sortByDesc('id')
                ->first();
        }

        return $contract->sponsorshipTransfers()
            ->whereIn('status', ['maid_delivered', 'trial', 'trial_started'])
            ->latest('id')
            ->first();
    }

    private function applyOutsideReasonFilter($rows, string $outsideReason)
    {
        return $rows->where(function ($query) use ($outsideReason) {
            $query->whereHas('info', function ($infoQuery) use ($outsideReason) {
                $infoQuery->where('outside_shelter_reason', $outsideReason);
            });

            if ($outsideReason === 'rental_delivery') {
                $query->orWhere(function ($rentalQuery) {
                    $rentalQuery->where('status', ContractStatusEnum::Shelter->value)
                        ->whereHas('maid.rentalContracts', function ($contractQuery) {
                            $contractQuery->whereIn('status', ['maid_delivered', 'active']);
                        });
                });
            }

            if ($outsideReason === 'transfer_delivery') {
                $query->orWhere(function ($transferQuery) {
                    $transferQuery->whereIn('status', [
                            ContractStatusEnum::Shelter->value,
                            ContractStatusEnum::Trial->value,
                        ])
                        ->whereHas('sponsorshipTransfers', function ($sponsorshipQuery) {
                            $sponsorshipQuery->whereIn('status', ['maid_delivered', 'trial', 'trial_started']);
                        });
                });
            }
        });
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($rows, $request)
    {
        $this->applyRequestBranchFilter($rows, $request, 'contracts');

        // Apply filters
        if ($request->filled('contract_number')) {
            $contractNumber = $request->input('contract_number');
            $rows->where('id', 'like', '%' . $contractNumber . '%');
        }

        if ($request->filled('maid_name')) {
            $maidName = trim((string) $request->input('maid_name'));
            $rows->whereHas('maid', function ($query) use ($maidName) {
                $query->where('name', 'like', '%' . $maidName . '%')
                    ->orWhere('id', 'like', '%' . $maidName . '%');
            });
        }

        if ($request->filled('passport_num')) {
            $passportNum = trim((string) $request->input('passport_num'));
            $rows->whereHas('maid', function ($query) use ($passportNum) {
                $query->where('passport_num', 'like', '%' . $passportNum . '%');
            });
        }

        if ($request->filled('maid_type')) {
            $maidType = $request->input('maid_type');
            $rows->whereHas('maid.jobTypes', function ($query) use ($maidType) {
                $query->where('job_types.id', $maidType);
            });
        }

        if ($request->filled('nationality')) {
            $nationality = $request->input('nationality');
            $rows->whereHas('maid.nationality', function ($query) use ($nationality) {
                $query->where('id', $nationality);
            });
        }

        if ($request->filled('shelter')) {
            $shelter = $request->input('shelter');
            $rows->whereHas('info', function ($query) use ($shelter) {
                $query->where('shelter_id', $shelter);
            });
        }

        if ($request->filled('receipt_source')) {
            $receiptSource = $request->input('receipt_source');
            $rows->whereHas('info', function ($query) use ($receiptSource) {
                $query->where('receipt_source', $receiptSource);
            });
        }

        if ($request->filled('status')) {
            $guaranteeStatus = $request->input('status');
            $today = now();
            $guaranteePeriodDays = AutomationSettings::warrantyPeriodDays();

            if ($guaranteeStatus === 'within_guarantee') {
                // Within guarantee period
                $rows->whereHas('info', function ($query) use ($today, $guaranteePeriodDays) {
                    $query->where(function ($q) use ($today, $guaranteePeriodDays) {
                        $q->where(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('arrival_date')
                                ->whereDate('arrival_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('visa_milad_date')
                                ->whereDate('visa_milad_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('contract_date')
                                ->whereDate('contract_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('ingaz_from')
                                ->whereDate('ingaz_from', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        });
                    });
                });
            } elseif ($guaranteeStatus === 'expired_guarantee') {
                // Expired guarantee period
                $rows->whereHas('info', function ($query) use ($today, $guaranteePeriodDays) {
                    $query->where(function ($q) use ($today, $guaranteePeriodDays) {
                        $q->where(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('arrival_date')
                                ->whereDate('arrival_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('visa_milad_date')
                                ->whereDate('visa_milad_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('contract_date')
                                ->whereDate('contract_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($subQ) use ($today, $guaranteePeriodDays) {
                            $subQ->whereNotNull('ingaz_from')
                                ->whereDate('ingaz_from', '<', $today->copy()->subDays($guaranteePeriodDays));
                        });
                    });
                });
            }
        }

        if ($request->filled('work_willingness')) {
            $workWillingness = $request->input('work_willingness');
            $rows->whereHas('info', function ($query) use ($workWillingness) {
                $query->where('wants_to_work', $workWillingness);
            });
        }

        if ($request->filled('arrival_date')) {
            $arrivalDate = $request->input('arrival_date');
            $rows->whereHas('info', function ($query) use ($arrivalDate) {
                $query->where(function ($q) use ($arrivalDate) {
                    $q->whereDate('visa_milad_date', $arrivalDate)
                        ->orWhereDate('contract_date', $arrivalDate)
                        ->orWhereDate('ingaz_from', $arrivalDate);
                });
            });
        }

        if ($request->filled('return_date')) {
            $returnDate = $request->input('return_date');
            $rows->whereHas('info', function ($query) use ($returnDate) {
                $query->whereDate('maid_date_back', $returnDate);
            });
        }

        if ($request->filled('outside_reason')) {
            $this->applyOutsideReasonFilter($rows, $request->input('outside_reason'));
        }

        // فلتر الاستلام الفوري (العاملات التي ترغب بالعمل)
        if ($request->filled('instant_receipt')) {
            $instantReceipt = $request->input('instant_receipt');
            if ($instantReceipt === 'yes') {
                $rows->whereHas('info', function ($query) {
                    $query->where('wants_to_work', 'yes')
                        ->where('is_available_for_instant', true);
                });
            } elseif ($instantReceipt === 'no') {
                $rows->whereHas('info', function ($query) {
                    $query->where(function ($inner) {
                        $inner->where('wants_to_work', '!=', 'yes')
                            ->orWhereNull('wants_to_work')
                            ->orWhere('is_available_for_instant', false)
                            ->orWhereNull('is_available_for_instant');
                    });
                });
            }
        }

        if ($request->filled('quick_tag') && $this->supportsQuickTags()) {
            $quickTag = trim((string) $request->input('quick_tag'));
            $rows->whereHas('info', function ($query) use ($quickTag) {
                $query->whereJsonContains('shelter_quick_tags', $quickTag);
            });
        }

        if ($request->filled('service_category')) {
            $serviceCategory = $request->input('service_category');

            if ($serviceCategory === 'rental') {
                $rows->whereHas('maid.jobTypes.translations', function ($query) {
                    $this->applyJobTypeKeywordQuery($query, $this->getRentalServiceKeywords());
                });
            } elseif ($serviceCategory === 'transfer') {
                $rows->whereHas('maid.jobTypes.translations', function ($query) {
                    $this->applyJobTypeKeywordQuery($query, $this->getTransferServiceKeywords());
                })->whereDoesntHave('maid.jobTypes.translations', function ($query) {
                    $this->applyJobTypeKeywordQuery($query, $this->getRentalServiceKeywords());
                });
            } elseif ($serviceCategory === 'recruitment') {
                $rows->where(function ($query) {
                    $query->whereDoesntHave('maid.jobTypes')
                        ->orWhereDoesntHave('maid.jobTypes.translations', function ($translationQuery) {
                            $translationQuery->where(function ($inner) {
                                $this->applyJobTypeKeywordQuery($inner, [
                                    ...$this->getRentalServiceKeywords(),
                                    ...$this->getTransferServiceKeywords(),
                                ]);
                            });
                        });
                });
            }
        }

        if ($request->filled('request_type')) {
            $rows->whereHas('maid', function ($query) use ($request) {
                $query->ofRequestType($request->request_type);
            });
        }

        // فلتر حالة الإجراءات لمرحلة الترحيل
        if ($request->filled('procedure_status')) {
            $procedureStatus = $request->input('procedure_status');

            if ($procedureStatus === 'not_started') {
                // العقود التي لم تبدأ إجراءات الترحيل بعد
                $rows->whereDoesntHave('deportationProcedure');
            } elseif ($procedureStatus === 'in_progress') {
                // العقود قيد التنفيذ (لديها إجراءات ترحيل ولكن لم تكتمل)
                $rows->whereHas('deportationProcedure', function ($query) {
                    $query->where('status', 'pending');
                });
            } elseif ($procedureStatus === 'tickets_booked') {
                // العقود التي تم حجز التذاكر لها
                $rows->whereHas('deportationProcedure', function ($query) {
                    $query->where('has_final_exit', true)
                        ->whereNotNull('final_exit_date');
                });
            } elseif ($procedureStatus === 'completed') {
                // العقود التي تم ترحيلها (مكتملة)
                $rows->whereHas('deportationProcedure', function ($query) {
                    $query->where('status', 'completed');
                });
            }
        }

        if ($request->filled('missing_stage')) {
            $missingStage = $request->input('missing_stage');
            $rows->whereHas('deportationProcedure', function ($query) use ($missingStage) {
                switch ($missingStage) {
                    case 'salary':
                        $query->where(function ($q) {
                            $q->whereNull('salary_received')
                                ->orWhere('salary_received', '!=', 1);
                        });
                        break;
                    case 'passport':
                        $query->where(function ($q) {
                            $q->whereNull('has_passport')
                                ->orWhere('has_passport', '!=', 1);
                        });
                        break;
                    case 'letter':
                        $query->where(function ($q) {
                            $q->whereNull('has_deportation_letter')
                                ->orWhere('has_deportation_letter', '!=', 1);
                        });
                        break;
                    case 'settlement':
                        $query->where(function ($q) {
                            $q->whereNull('has_settlement_session')
                                ->orWhere('has_settlement_session', '!=', 1);
                        });
                        break;
                    case 'final_exit':
                        $query->where(function ($q) {
                            $q->whereNull('has_final_exit')
                                ->orWhere('has_final_exit', '!=', 1)
                                ->orWhereNull('final_exit_date');
                        });
                        break;
                }
            });
        }

        return $rows;
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        \Log::info('getData method called');
        \Log::info('Request URL: ' . $request->fullUrl());
        \Log::info('Request headers: ' . json_encode($request->headers->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        try {
            $rows = Contract::insideShelter()
                ->with([
                    'maid' => function ($query) {
                        $query->with([
                            'jobTypes',
                            'rentalContracts' => function ($rentalQuery) {
                                $rentalQuery->select(['id', 'maid_id', 'status']);
                            },
                            'nationality' => function ($q) {
                                $q->with('translations');
                            },
                            'job_types' => function ($q) {
                                $q->with(['job_type' => function ($jt) {
                                    $jt->with('translations');
                                }]);
                            }
                        ]);
                    },
                    'user',
                    'info' => function ($query) {
                        $query->with(['shelter' => function ($q) {
                            $q->with('translations');
                        }]);
                    },
                    'latestShelterLog'
                ]);

            // Apply filters
            $this->applyFilters($rows, $request);

            $rows = $rows->orderByDesc($this->getShelterOrderExpression());

            // Handle export request
            if ($request->has('export') && $request->input('export') == '1') {
                return $this->exportData($rows->get(), $request->input('export_format', 'csv'));
            }

            $dataTable = $this->buildDataTable($rows);

            \Log::info('getData response created successfully');

            return $dataTable;
        } catch (\Exception $e) {
            \Log::error('Error in getData: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request, string $format)
    {
        $rows = Contract::insideShelter()
            ->with([
                'maid.jobTypes',
                'maid.job_types.job_type.translations',
                'maid.nationality.translations',
                'user',
                'info.shelter.translations',
                'latestShelterLog',
            ]);

        $this->applyFilters($rows, $request);
        $rows = $rows->orderByDesc($this->getShelterOrderExpression())->get();

        return $this->exportData($rows, $format);
    }

    /**
     * إرجاع العقد
     */
    public function back($id)
    {
        $this->assertCanAccessBranchModel(Contract::findOrFail($id), 'contracts');

        try {
            $contract = Contract::findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            // إرجاع العقد إلى حالة "مكتمل" لأن العقود في الإيواء هي عقود مكتملة أصلاً
            $contract->status = ContractStatusEnum::Completed->value;

            // إزالة معلومات الإيواء
            if ($contract->info) {
                $infoResetPayload = [
                    'shelter_id' => null,
                    'wants_to_work' => null,
                    'maid_date_back' => null,
                    'receipt_source' => null,
                    'is_outside_shelter' => false,
                    'outside_shelter_at' => null,
                    'outside_shelter_reason' => null,
                    'outside_shelter_reference_type' => null,
                    'outside_shelter_reference_id' => null,
                    'outside_shelter_by' => null,
                ];

                if ($this->supportsQuickTags()) {
                    $infoResetPayload['shelter_quick_tags'] = null;
                }

                $contract->info->update($infoResetPayload);
            }

            $contract->save();

            // تسجيل النشاط
            activity()
                ->performedOn($contract)
                ->causedBy(auth('admin')->user())
                ->log('تم حذف العقد من الإيواء - العقد الآن في حالة مكتمل');

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العقد من الإيواء بنجاح - العقد الآن في حالة مكتمل'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العقد من الإيواء: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reportEscape(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:3000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240', 'required_without:note'],
        ], [
            'note.required_without' => 'يجب كتابة ملاحظة أو إرفاق ملف للبلاغ.',
            'attachment.required_without' => 'يجب إرفاق ملف أو كتابة ملاحظة للبلاغ.',
            'attachment.mimes' => 'صيغة المرفق يجب أن تكون صورة أو PDF أو Word.',
            'attachment.max' => 'حجم المرفق يجب ألا يتجاوز 10 ميجابايت.',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 3000 حرف.',
        ]);

        $contract = Contract::with('info')->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');

        if ($contract->status !== ContractStatusEnum::Shelter->value || $contract->info?->is_outside_shelter) {
            throw ValidationException::withMessages([
                'contract_id' => 'لا يمكن تسجيل بلاغ هروب لأن العاملة ليست داخل الإيواء.',
            ]);
        }

        $attachment = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachment = [
                'path' => app(ManagedFileStorage::class)->uploadFile(
                    $file,
                    'shelter-contracts/escape-reports'
                ),
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        try {
            DB::transaction(function () use ($contract, $validated, $attachment) {
                $lockedContract = Contract::with('info')->lockForUpdate()->findOrFail($contract->id);

                if ($lockedContract->status !== ContractStatusEnum::Shelter->value || $lockedContract->info?->is_outside_shelter) {
                    throw ValidationException::withMessages([
                        'contract_id' => 'تم تغيير حالة العاملة ولم تعد داخل الإيواء.',
                    ]);
                }

                $lockedContract->info()->updateOrCreate(
                    ['contract_id' => $lockedContract->id],
                    [
                        'is_outside_shelter' => true,
                        'outside_shelter_at' => now(),
                        'outside_shelter_reason' => 'escape_report',
                        'outside_shelter_reference_type' => null,
                        'outside_shelter_reference_id' => null,
                        'outside_shelter_by' => auth('admin')->id(),
                        'escape_report_note' => $validated['note'] ?? null,
                        'escape_report_attachment' => $attachment,
                        'is_available_for_instant' => false,
                    ]
                );

                $this->syncMaidInstantDisplay($lockedContract, false);

                activity()
                    ->performedOn($lockedContract)
                    ->causedBy(auth('admin')->user())
                    ->withProperties([
                        'note' => $validated['note'] ?? null,
                        'attachment' => $attachment['name'] ?? null,
                    ])
                    ->log('تم تسجيل بلاغ هروب وإخراج العاملة من الإيواء');
            });
        } catch (\Throwable $e) {
            if ($attachment) {
                app(ManagedFileStorage::class)->delete($attachment['path']);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل بلاغ الهروب ونقل العاملة إلى خارج الإيواء.',
            'redirect' => route('ShelterContract.outside'),
        ]);
    }

    public function downloadEscapeReportAttachment($id)
    {
        $contract = Contract::with('info')->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');

        $attachment = $contract->info?->escape_report_attachment;
        $path = is_array($attachment) ? ($attachment['path'] ?? null) : null;

        $storage = app(ManagedFileStorage::class);
        abort_unless($contract->hasEscapeReport() && $path && $storage->exists($path), 404);

        return $storage->downloadResponse(
            $path,
            $attachment['name'] ?? basename($path),
            $attachment['mime_type'] ?? $storage->mimeType($path)
        );
    }

    /**
     * Build DataTable response
     */
    private function buildDataTable($rows)
    {
        \Log::info('buildDataTable called with rows count: ' . $rows->count());

        try {
            \Log::info('Starting DataTables::of');

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('contract_number', function ($row) {
                    return 'عقد رقم: ' . $row->id;
                })
                ->addColumn('maid_data', function ($row) {
                    \Log::info('Processing maid_data for contract: ' . $row->id);

                    if (!$row->maid) {
                        \Log::warning('No maid found for contract: ' . $row->id);
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $maidName = $row->maid->name ?: 'عاملة رقم: ' . $row->maid->id;
                    $passport = $row->maid->passport_num ?: 'غير محدد';

                    // Get job type
                    $jobType = 'غير محدد';
                    if ($row->maid->job_types && !$row->maid->job_types->isEmpty()) {
                        $jobTypeModel = $row->maid->job_types->first();
                        if ($jobTypeModel && $jobTypeModel->job_type) {
                            $locale = app()->getLocale();
                            $translation = $jobTypeModel->job_type->translations->where('locale', $locale)->first();
                            if (!$translation) {
                                $translation = $jobTypeModel->job_type->translations->first();
                            }
                            $jobType = $translation ? $translation->title : 'غير محدد';
                        }
                    }

                    $html = '<div class="table-person-cell">';
                    $html .= '<div class="table-person-title">' . e($maidName) . '</div>';
                    $html .= '<div class="table-person-meta"><i class="fas fa-passport"></i><span>جواز: ' . e($passport) . '</span></div>';
                    $html .= '<div class="table-person-meta"><i class="fas fa-clipboard-list"></i><span>نوع الطلب: ' . e($row->maid->request_type_label) . '</span></div>';
                    $html .= '<div class="table-person-meta"><i class="fas fa-briefcase"></i><span>نوع العمل: ' . e($jobType) . '</span></div>';
                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('client', function ($row) {
                    if (!$row->user) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $clientName = $row->user->name ?: 'عميل رقم: ' . $row->user->id;
                    $clientPhone = $row->user->phone ?: 'غير محدد';

                    return '<div class="table-person-cell">
                                <div class="table-person-title">' . e($clientName) . '</div>
                                <div class="table-person-meta"><i class="fas fa-phone-alt"></i><span>' . e($clientPhone) . '</span></div>
                            </div>';
                })
                ->addColumn('nationality', function ($row) {
                    \Log::info('Processing nationality for contract: ' . $row->id);

                    if (!$row->maid) {
                        \Log::warning('No maid found for nationality - contract: ' . $row->id);
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    if (!$row->maid->nationality) {
                        \Log::warning('No nationality found for maid: ' . $row->maid->id);
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Try to get nationality from translation first
                    $locale = app()->getLocale();
                    $translation = $row->maid->nationality->translations->where('locale', $locale)->first();

                    if (!$translation) {
                        $translation = $row->maid->nationality->translations->first();
                    }

                    $nationality = 'غير محدد';
                    if ($translation && !empty($translation->sub_title)) {
                        $nationality = $translation->sub_title;
                    } elseif (!empty($row->maid->nationality->sub_title)) {
                        // Fallback to direct model attribute
                        $nationality = $row->maid->nationality->sub_title;
                    } elseif ($translation && !empty($translation->title)) {
                        $nationality = $translation->title;
                    } elseif (!empty($row->maid->nationality->title)) {
                        $nationality = $row->maid->nationality->title;
                    }

                    \Log::info('Nationality for contract ' . $row->id . ': ' . $nationality);

                    return '<span class="badge bg-info">' . htmlspecialchars($nationality) . '</span>';
                })
                ->addColumn('shelter', function ($row) {
                    if (!$row->info || !$row->info->shelter) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $locale = app()->getLocale();
                    $translation = $row->info->shelter->translations->where('locale', $locale)->first();
                    $shelter = $translation ? $translation->title : 'غير محدد';

                    return $shelter;
                })
                ->addColumn('added_to_shelter_at', function ($row) {
                    $addedAt = $this->resolveShelterEntryDate($row);

                    if (!$addedAt) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span data-order="' . $addedAt->timestamp . '">' . $addedAt->format('Y-m-d H:i') . '</span>';
                })
                ->addColumn('shelter_entry_date', function ($row) {
                    $shelterEntryDate = $this->resolveShelterEntryDate($row);

                    if (!$shelterEntryDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span data-order="' . $shelterEntryDate->timestamp . '">' . $shelterEntryDate->format('Y-m-d') . '</span>';
                })
                ->addColumn('shelter_duration', function ($row) {
                    $shelterEntryDate = $this->resolveShelterEntryDate($row);

                    if (!$shelterEntryDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span data-order="' . $shelterEntryDate->timestamp . '">' . e($this->formatHumanDuration($shelterEntryDate)) . '</span>';
                })
                ->addColumn('kingdom_duration', function ($row) {
                    $arrivalDate = $this->resolveArrivalDate($row);

                    if (!$arrivalDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span data-order="' . $arrivalDate->timestamp . '">' . e($this->formatHumanDuration($arrivalDate)) . '</span>';
                })
                ->editColumn('arrival_date', function ($row) {
                    $dateToDisplay = $this->resolveArrivalDate($row);

                    if (!$dateToDisplay) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span data-order="' . $dateToDisplay->timestamp . '">' . $dateToDisplay->format('Y-m-d') . '</span>';
                })
                ->editColumn('return_date', function ($row) {
                    $returnDate = $this->resolveShelterEntryDate($row)?->format('Y-m-d');

                    return $returnDate ?: '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('receipt_source', function ($row) {
                    if (!$row->info || !$row->info->receipt_source) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return '<span class="badge bg-secondary">' . htmlspecialchars($this->resolveReceiptSourceLabel($row->info->receipt_source)) . '</span>';
                })
                ->addColumn('guarantee_status', function ($row) {
                    $guaranteeData = $this->resolveGuaranteeData($row);

                    return '<span class="badge ' . e($guaranteeData['badge_class']) . '">' . e($guaranteeData['label']) . '</span>';
                })
                ->addColumn('work_willingness', function ($row) {
                    $workWillingness = $row->info ? $row->info->wants_to_work : null;

                    if ($workWillingness === 'yes') {
                        return '<span class="badge bg-success">ترغب بالعمل</span>';
                    } elseif ($workWillingness === 'no') {
                        return '<span class="badge bg-warning">لا ترغب بالعمل</span>';
                    } else {
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    }
                })
                ->addColumn('status_summary', function ($row) {
                    $serviceCategory = $this->determineShelterServiceCategory($row->maid);
                    $serviceLabel = match ($serviceCategory) {
                        'rental' => 'إيجار',
                        'transfer' => 'نقل خدمات',
                        default => 'استقدام',
                    };

                    $receiptSource = $row->info && $row->info->receipt_source
                        ? e($this->resolveReceiptSourceLabel($row->info->receipt_source))
                        : 'غير محدد';
                    $guaranteeData = $this->resolveGuaranteeData($row);
                    $workWillingness = $row->info ? $row->info->wants_to_work : null;
                    $workLabel = match ($workWillingness) {
                        'yes' => 'ترغب بالعمل',
                        'no' => 'لا ترغب بالعمل',
                        default => 'رغبة العمل غير محددة',
                    };
                    $workClass = match ($workWillingness) {
                        'yes' => 'status-pill-work',
                        'no' => 'status-pill-work-warning',
                        default => 'status-pill-work-muted',
                    };

                    return '<div class="shelter-status-cell">'
                        . '<div class="status-top">'
                        . '<span class="status-pill status-pill-service">' . e($serviceLabel) . '</span>'
                        . '<span class="status-pill ' . $workClass . '">' . e($workLabel) . '</span>'
                        . '</div>'
                        . '<div class="status-line"><i class="fas fa-truck-loading"></i><strong>مصدر الاستلام</strong><span>' . $receiptSource . '</span></div>'
                        . '<div class="status-line"><i class="fas fa-shield-alt"></i><strong>الضمان</strong><span class="badge ' . e($guaranteeData['badge_class']) . '">' . e($guaranteeData['label']) . '</span></div>'
                        . '</div>';
                })
                ->addColumn('instant_receipt_availability', function ($row) {
                    $isAvailable = $row->info ? $row->info->is_available_for_instant : false;
                    $workWillingness = $row->info ? $row->info->wants_to_work : null;
                    $isRentalWorker = $this->determineShelterServiceCategory($row->maid) === 'rental';

                    if ($workWillingness === 'yes' && $isAvailable) {
                        $label = $isRentalWorker
                            ? 'استلام فوري - ظاهرة في نقل الخدمات'
                            : 'متاحة لنقل الخدمات';

                        return '<span class="badge bg-success"><i class="fas fa-eye me-1"></i>' . $label . '</span>';
                    } elseif ($workWillingness === 'yes' && !$isAvailable) {
                        return '<span class="badge bg-warning"><i class="fas fa-eye-slash me-1"></i>غير متاحة للاستلام الفوري / نقل الخدمات</span>';
                    } elseif ($workWillingness === 'no') {
                        return '<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>لا ترغب بالعمل</span>';
                    } else {
                        return '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i>غير محدد</span>';
                    }
                })
                ->addColumn('quick_tags', function ($row) {
                    $tags = $this->extractQuickTags(optional($row->info)->shelter_quick_tags);

                    if (empty($tags)) {
                        return '<span class="text-muted">بدون تصنيف</span>';
                    }

                    return collect($tags)->map(function ($tag) {
                        return '<span class="badge bg-light text-dark border me-1 mb-1">' . e($tag) . '</span>';
                    })->implode('');
                })
                ->addColumn('deportation_status', function ($row) {
                    $procedure = \App\Models\DeportationProcedure::where('contract_id', $row->id)->first();

                    $statusData = $this->determineDeportationStage($procedure);

                    $html = '<div class="deportation-status">';
                    $html .= '<div class="progress mb-1" style="height: 20px;">
                                <div class="progress-bar ' . $statusData['progress_class'] . '" role="progressbar" style="width: ' . $statusData['percentage'] . '%">
                                    ' . $statusData['percentage'] . '%
                                </div>
                              </div>';
                    $html .= '<span class="badge ' . $statusData['badge_class'] . '">' . $statusData['label'] . '</span>';

                    if (!empty($statusData['description'])) {
                        $html .= '<div class="mt-1 text-muted" style="font-size: 12px;">' . e($statusData['description']) . '</div>';
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('show_site_status', function ($row) {
                    $html = $row->info->is_available_for_instant == 0 ? '<span class="badge text-bg-dark">مخفي</span>' : '<span class="badge text-bg-success">ظاهر</span>';

                    return $html;
                })
                ->addColumn('actions', function ($row) {
                    $isRentalWorker = $this->determineShelterServiceCategory($row->maid) === 'rental';
                    $canViewDetails = $this->adminCanAny($this->shelterContractDetailViewPermissions());
                    $canEditContract = $this->adminCanAny($this->shelterContractEditPermissions());
                    $canChangeWorkDesire = $this->adminCanAny($this->shelterWorkDesireEditPermissions());
                    $canEditQuickTags = $this->adminCanAny($this->shelterQuickTagsEditPermissions());
                    $canToggleInstantReceipt = $isRentalWorker
                        ? $this->adminCanAny($this->shelterRentalInstantReceiptEditPermissions())
                        : $this->adminCanAny($this->shelterInstantReceiptEditPermissions());
                    $canRemoveFromShelter = $this->adminCanAny($this->shelterRemoveFromShelterPermissions());
                    $canViewDeportationProcedures = $this->adminCanAny($this->shelterDeportationProceduresViewPermissions());
                    $canManageDeportationProcedures = $this->adminCanAny($this->shelterDeportationProceduresEditPermissions());
                    $canCreateTransferRequest = $this->adminCanAny([
                        PermKeys::SPONSOR_TRANSFER_CREATE,
                        PermKeys::SPONSOR_TRANSFER_CONTROL,
                    ]);
                    $canCreateRentalContract = $this->adminCanAny([
                        PermKeys::RENT_CONTRACT_CREATE,
                    ]);

                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    if ($canViewDetails) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.show', $row->id) . '">
                                        <i class="fas fa-eye"></i> عرض التفاصيل
                                    </a></li>';
                        $actions .= '<li><a class="dropdown-item" href="' . route('shelter_contract_form.download', $row->id) . '" target="_blank">
                                        <i class="fas fa-eye"></i> عرض نموذج الاستلام
                                    </a></li>';
                    }

                    if ($canEditContract) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.edit', $row->id) . '">
                                        <i class="fas fa-edit"></i> تحرير
                                    </a></li>';
                    }

                    if ($canChangeWorkDesire) {
                        $actions .= '<li><a class="dropdown-item change-work-desire" href="#" data-id="' . $row->id . '" data-current="' . ($row->info ? $row->info->wants_to_work : 'unknown') . '">
                                        <i class="fas fa-exchange-alt"></i> تغيير رغبة العمل
                                    </a></li>';
                    }

                    if ($canEditQuickTags && $this->supportsQuickTags()) {
                        $quickTags = htmlspecialchars(implode(', ', $this->extractQuickTags(optional($row->info)->shelter_quick_tags)), ENT_QUOTES, 'UTF-8');
                        $actions .= '<li><a class="dropdown-item edit-quick-tags" href="#" data-id="' . $row->id . '" data-tags="' . $quickTags . '">
                                        <i class="fas fa-tags"></i> تعديل التصنيفات السريعة
                                    </a></li>';
                    }

                    if ($canToggleInstantReceipt
                        && $row->info
                        && $row->info->wants_to_work === 'yes'
                        && $this->instantReceiptFieldEnabledFor($row->maid)) {
                        $availableStatus = $isRentalWorker
                            ? 'متاحة للاستلام الفوري وتظهر في نقل الخدمات'
                            : 'متاحة لنقل الخدمات';
                        $currentStatus = $row->info->is_available_for_instant ? $availableStatus : 'غير متاحة';
                        $newStatus = $isRentalWorker
                            ? ($row->info->is_available_for_instant
                                ? 'جعلها غير متاحة للاستلام الفوري'
                                : 'جعلها متاحة للاستلام الفوري')
                            : ($row->info->is_available_for_instant ? 'إخفاء من الموقع' : 'إظهار على الموقع');
                        $icon = $row->info->is_available_for_instant ? 'fa-eye-slash' : 'fa-eye';
                        $badgeClass = $row->info->is_available_for_instant ? 'text-success' : 'text-warning';

                        $actions .= '<li><a class="dropdown-item toggle-instant-receipt ' . $badgeClass . '" href="#" data-id="' . $row->id . '" data-current="' . ($row->info->is_available_for_instant ? '1' : '0') . '" data-text="تغيير حالة الاستلام الفوري / نقل الخدمات من ' . $currentStatus . ' إلى ' . $newStatus . '">
                                        <i class="fas ' . $icon . ' me-1"></i> ' . $newStatus . '
                                    </a></li>';
                    }

                    if ($canRemoveFromShelter) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="back" data-id="' . $row->id . '" data-text="هل أنت متأكد من حذف العقد من الإيواء؟ سيعود العقد إلى العقود المكتملة">
                                        <i class="fas fa-undo"></i> حذف من الإيواء
                                    </a></li>';
                    }

                    if ($canEditContract) {
                        $actions .= '<li><a class="dropdown-item text-danger report-escape" href="#" data-id="' . $row->id . '">
                                        <i class="fas fa-running"></i> إضافة بلاغ هروب
                                    </a></li>';
                    }

                    $maidRentalContracts = $row->maid?->rentalContracts;
                    $hasActiveRentalContract = $maidRentalContracts
                        ? $maidRentalContracts->whereIn('status', [
                            'new',
                            'waiting_contract_send',
                            'contract_sent',
                            'contract_signed',
                            'maid_delivered',
                            'active',
                            'transferred',
                            'suspended',
                            'frozen',
                        ])
                            ->isNotEmpty()
                        : false;
                    $canRentWillingMaid = $canCreateRentalContract
                        && $row->maid
                        && $this->determineShelterServiceCategory($row->maid) === 'rental'
                        && $row->info?->wants_to_work === 'yes'
                        && !$hasActiveRentalContract;

                    if ($canRentWillingMaid) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item text-success" href="' . route('RentContract.create', ['maid_id' => $row->maid_id]) . '">
                                        <i class="fas fa-file-signature"></i> إضافة عقد إيجار
                                    </a></li>';
                    }

                    if ($canCreateTransferRequest
                        && $row->info
                        && $row->info->wants_to_work === 'yes'
                        && $this->determineShelterServiceCategory($row->maid) !== 'rental') {
                        $siteAvailabilityLabel = 'متاحة لنقل الخدمات';
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="transfer" data-id="' . $row->id . '" data-text="سيتم فتح صفحة إضافة طلب نقل خدمات">
                                        <i class="fas fa-exchange-alt"></i> إضافة طلب نقل خدمات
                                    </a></li>';

                        // إضافة معلومات إضافية
                        $actions .= '<li><div class="dropdown-item-text text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>الحالة الحالية:</strong> ' . ($row->info->is_available_for_instant ? $siteAvailabilityLabel : 'غير متاحة') . '
                                    </div></li>';
                    }

                    if ($row->info && $row->info->wants_to_work === 'no') {
                        $actions .= '<li><hr class="dropdown-divider"></li>';

                        $deportationProcedure = $row->deportationProcedure;

                        if ($deportationProcedure) {
                            if ($canViewDeportationProcedures) {
                                $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.deportation.procedures', $row->id) . '">
                                                <i class="fas fa-clipboard-check"></i> عرض إجراءات الترحيل
                                            </a></li>';
                            }

                            $canConfirmProcedures = !$deportationProcedure->procedures_confirmed
                                && $deportationProcedure->status !== 'completed'
                                && $deportationProcedure->areAllQuestionsAnswered();

                            if ($canManageDeportationProcedures && $canConfirmProcedures) {
                                $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="confirm-procedures" data-id="' . $deportationProcedure->id . '" data-text="هل تريد تأكيد اكتمال جميع إجراءات الترحيل؟">
                                                <i class="fas fa-check-double"></i> تأكيد اكتمال الإجراءات
                                            </a></li>';
                            }

                            $canCompleteDeportation = $deportationProcedure->status !== 'completed'
                                && $deportationProcedure->trip_scheduled
                                && $deportationProcedure->procedures_confirmed;

                            if ($canManageDeportationProcedures && $canCompleteDeportation) {
                                $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="complete-deportation" data-id="' . $deportationProcedure->id . '" data-text="بعد إتمام الترحيل سيتم نقل العقد إلى قائمة تم الترحيل. هل تريد المتابعة؟">
                                                <i class="fas fa-plane-arrival"></i> إكمال الترحيل
                                            </a></li>';
                            }
                        } elseif ($canManageDeportationProcedures) {
                            $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="start-deportation" data-id="' . $row->id . '" data-text="هل تريد بدء إجراءات الترحيل للعاملة؟">
                                            <i class="fas fa-plane-departure"></i> بدء إجراءات الترحيل
                                        </a></li>';
                        }
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->orderColumn('added_to_shelter_at', function ($query, $order) {
                    $query->orderBy($this->getShelterOrderExpression(), $order);
                })
                ->rawColumns(['maid_data', 'client', 'nationality', 'shelter', 'added_to_shelter_at', 'shelter_entry_date', 'shelter_duration', 'kingdom_duration', 'receipt_source', 'arrival_date', 'return_date', 'guarantee_status', 'work_willingness', 'status_summary', 'quick_tags', 'instant_receipt_availability', 'deportation_status', 'show_site_status', 'actions'])
                ->toJson();

            \Log::info('DataTables processing completed');
            return $dataTable;
        } catch (\Exception $e) {
            \Log::error('Error in buildDataTable: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function getShelterOrderExpression()
    {
        $status = addslashes(ContractStatusEnum::Shelter->value);

        return DB::raw("COALESCE(
            (SELECT MAX(created_at)
             FROM contract_status_logs
             WHERE contract_status_logs.contract_id = contracts.id
               AND contract_status_logs.status = '{$status}'),
            (SELECT MAX(created_at)
             FROM contract_infos
             WHERE contract_infos.contract_id = contracts.id),
            contracts.updated_at,
            contracts.created_at
        )");
    }

    private function determineDeportationStage($procedure): array
    {
        if (!$procedure) {
            return [
                'label' => 'لم تبدأ الإجراءات',
                'badge_class' => 'bg-secondary',
                'progress_class' => 'bg-secondary',
                'percentage' => 0,
                'description' => ''
            ];
        }

        if ($procedure->status === 'completed') {
            return [
                'label' => 'تم الترحيل',
                'badge_class' => 'bg-success',
                'progress_class' => 'bg-success',
                'percentage' => 100,
                'description' => ''
            ];
        }

        $percentage = $procedure->completion_percentage ?? 0;
        $progressClass = $percentage < 50 ? 'bg-danger' : ($percentage < 100 ? 'bg-warning' : 'bg-success');

        $stages = [
            [
                'field' => 'salary_received',
                'date' => 'salary_received_date',
                'label' => 'استلام الراتب',
                'description' => 'بانتظار تأكيد استلام الراتب'
            ],
            [
                'field' => 'has_passport',
                'date' => 'passport_date',
                'label' => 'استلام الجواز',
                'description' => 'بانتظار استلام الجواز أو وثيقة السفر'
            ],
            [
                'field' => 'has_deportation_letter',
                'date' => 'deportation_letter_date',
                'label' => 'خطاب الترحيل',
                'description' => 'بانتظار استلام خطاب الترحيل'
            ],
            [
                'field' => 'has_settlement_session',
                'date' => 'settlement_date',
                'label' => 'جلسة التسوية',
                'description' => 'بانتظار جلسة التسوية'
            ],
            [
                'field' => 'has_final_exit',
                'date' => 'final_exit_date',
                'label' => 'الخروج النهائي',
                'description' => 'بانتظار إصدار خروج نهائي'
            ],
        ];

        foreach ($stages as $stage) {
            $value = $procedure->{$stage['field']};
            $dateField = $stage['date'] ?? null;
            $dateValue = $dateField ? $procedure->{$dateField} : null;

            if ($value === null || ($value === true && $dateField && empty($dateValue))) {
                return [
                    'label' => $stage['label'],
                    'badge_class' => 'bg-warning',
                    'progress_class' => $progressClass,
                    'percentage' => $percentage,
                    'description' => $stage['description']
                ];
            }
        }

        if (!$procedure->procedures_confirmed) {
            return [
                'label' => 'بانتظار التأكيد النهائي',
                'badge_class' => 'bg-primary',
                'progress_class' => $progressClass,
                'percentage' => $percentage,
                'description' => 'يرجى تأكيد اكتمال الإجراءات للانتقال للخطوة التالية'
            ];
        }

        if (!$procedure->trip_scheduled) {
            return [
                'label' => 'بانتظار جدولة الرحلة',
                'badge_class' => 'bg-info',
                'progress_class' => $progressClass,
                'percentage' => $percentage,
                'description' => 'يرجى جدولة رحلة للعاملة'
            ];
        }

        if (!$procedure->has_final_exit || !$procedure->final_exit_date) {
            return [
                'label' => 'بانتظار إصدار الخروج النهائي',
                'badge_class' => 'bg-warning',
                'progress_class' => $progressClass,
                'percentage' => $percentage,
                'description' => 'الرحلة مجدولة، يرجى استكمال إجراءات الخروج النهائي'
            ];
        }

        return [
            'label' => 'قيد التنفيذ',
            'badge_class' => 'bg-warning',
            'progress_class' => $progressClass,
            'percentage' => $percentage,
            'description' => ''
        ];
    }

    /**
     * بدء إجراءات الترحيل
     */
    public function startDeportation($id)
    {
        $this->assertCanAccessBranchModel(Contract::findOrFail($id), 'contracts');

        try {
            $contract = Contract::with(['maid', 'info'])->findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            if ($contract->hasEscapeReport() || $contract->info?->is_outside_shelter) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن بدء الترحيل لوجود بلاغ هروب على العاملة'
                ], 422);
            }

            // التحقق من أن العاملة لا ترغب بالعمل
            if ($contract->info && $contract->info->wants_to_work === 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة ترغب بالعمل - يمكن نقل الكفالة بدلاً من الترحيل'
                ], 400);
            }

            // التحقق من عدم وجود إجراءات ترحيل سابقة
            $existingProcedure = \App\Models\DeportationProcedure::where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->first();

            if ($existingProcedure) {
                return response()->json([
                    'success' => false,
                    'message' => 'توجد إجراءات ترحيل قيد التنفيذ بالفعل'
                ], 400);
            }

            // إنشاء إجراءات ترحيل جديدة
            $procedure = \App\Models\DeportationProcedure::create([
                'contract_id' => $contract->id,
                'maid_id' => $contract->maid_id,
                'status' => 'pending',
                'started_at' => now(),
                'created_by' => auth('admin')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء إجراءات الترحيل بنجاح',
                'procedure_id' => $procedure->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صفحة إجراءات الترحيل
     */
    public function showDeportationProcedures($id)
    {
        $contract = Contract::with(['maid', 'user', 'info'])->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');

        $procedure = \App\Models\DeportationProcedure::where('contract_id', $contract->id)
            ->with(['createdBy', 'completedBy'])
            ->first();

        if (!$procedure) {
            return redirect()->route('ShelterContract.show', $id)
                ->with('error', 'لا توجد إجراءات ترحيل لهذا العقد');
        }

        return view('admin.ingaz.ShelterContract.deportation-procedures', compact('contract', 'procedure'));
    }

    /**
     * تحديث إجراءات الترحيل
     */
    public function updateDeportationProcedures(Request $request, $id)
    {
        \Log::info('updateDeportationProcedures called', [
            'id' => $id,
            'request_data' => $request->all()
        ]);

        $procedureContract = Contract::with('info')->findOrFail(
            \App\Models\DeportationProcedure::findOrFail($id)->contract_id
        );
        $this->assertCanAccessBranchModel($procedureContract, 'contracts');

        try {
            if ($procedureContract->hasEscapeReport()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن متابعة إجراءات الترحيل لوجود بلاغ هروب على العاملة',
                ], 422);
            }

            $procedure = \App\Models\DeportationProcedure::findOrFail($id);

            // التحقق من أن الإجراءات غير مكتملة
            if ($procedure->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل إجراءات مكتملة'
                ], 400);
            }

            // التحقق من حفظ التقدم الجزئي
            $isPartialSave = $request->has('partial_save') && $request->partial_save == '1';

            // التحقق من التواريخ المستقبلية
            $today = now()->format('Y-m-d');
            $dateFields = [
                'salary_received_date' => $request->salary_received_date,
                'passport_date' => $request->passport_date,
                'deportation_letter_date' => $request->deportation_letter_date,
                'settlement_date' => $request->settlement_date,
                'final_exit_date' => $request->final_exit_date
            ];

            foreach ($dateFields as $field => $date) {
                if ($date && $date > $today) {
                    return response()->json([
                        'success' => false,
                        'message' => "التاريخ في حقل {$field} لا يمكن أن يكون مستقبلياً"
                    ], 400);
                }
            }

            // التحقق من طلب تأكيد الإجراءات
            if ($request->has('confirm_procedures') && $request->confirm_procedures) {
                if (!$procedure->areAllQuestionsAnswered()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب الإجابة على جميع الأسئلة أولاً'
                    ], 400);
                }

                $oldStatus = $procedure->procedures_confirmed;
                $procedure->update([
                    'procedures_confirmed' => true,
                    'confirmed_at' => now(),
                    'confirmed_by' => auth('admin')->id()
                ]);

                // تسجيل التحرك
                \App\Models\DeportationProcedureLog::logConfirmation(
                    $procedure->id,
                    auth('admin')->id(),
                    'تم تأكيد اكتمال الإجراءات'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'تم تأكيد اكتمال الإجراءات بنجاح'
                ]);
            }

            // التحقق من طلب إكمال الترحيل
            if ($request->has('complete_deportation') && $request->complete_deportation) {
                // التحقق من اكتمال جميع الإجراءات
                if (!$procedure->isAllProceduresCompleted()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب إكمال جميع الإجراءات أولاً'
                    ], 400);
                }

                // التحقق من وجود رحلة مجدولة
                if (!$procedure->trip_scheduled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب جدولة رحلة أولاً'
                    ], 400);
                }

                $oldStatus = $procedure->status;
                // إكمال الترحيل
                $procedure->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => auth('admin')->id()
                ]);

                // تسجيل التحرك
                \App\Models\DeportationProcedureLog::logCompletion(
                    $procedure->id,
                    auth('admin')->id(),
                    'تم إكمال إجراءات الترحيل'
                );

                // تحديث حالة العقد إلى "تم الترحيل"
                $procedure->contract->update([
                    'status' => ContractStatusEnum::Back->value
                ]);

                // إزالة معلومات الإيواء
                if ($procedure->contract->info) {
                    $infoResetPayload = [
                        'shelter_id' => null,
                        'wants_to_work' => null,
                        'maid_date_back' => now(),
                        'receipt_source' => null,
                    ];

                    if ($this->supportsQuickTags()) {
                        $infoResetPayload['shelter_quick_tags'] = null;
                    }

                    $procedure->contract->info->update($infoResetPayload);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إكمال إجراءات الترحيل بنجاح',
                    'redirect' => route('ShelterContract.deported')
                ]);
            }

            // حفظ القيم القديمة لتسجيل التحركات
            $oldValues = $procedure->only([
                'salary_received',
                'salary_received_date',
                'has_passport',
                'passport_date',
                'has_deportation_letter',
                'deportation_letter_date',
                'has_settlement_session',
                'settlement_date',
                'has_final_exit',
                'final_exit_date',
                'notes'
            ]);

            $defaultDate = now()->format('Y-m-d');
            $salaryReceived = $request->input('salary_received');
            $hasPassport = $request->input('has_passport');
            $hasDeportationLetter = $request->input('has_deportation_letter');
            $hasSettlementSession = $request->input('has_settlement_session');
            $hasFinalExit = $request->input('has_final_exit');

            // Update procedure data
            $updateData = [
                'salary_received' => $salaryReceived !== null ? ($salaryReceived == '1' ? true : false) : null,
                'salary_received_date' => $salaryReceived == '1' ? ($request->salary_received_date ?: $defaultDate) : null,
                'has_passport' => $hasPassport !== null ? ($hasPassport == '1' ? true : false) : null,
                'passport_date' => $hasPassport == '1' ? ($request->passport_date ?: $defaultDate) : null,
                'has_deportation_letter' => $hasDeportationLetter !== null ? ($hasDeportationLetter == '1' ? true : false) : null,
                'deportation_letter_date' => $hasDeportationLetter == '1' ? ($request->deportation_letter_date ?: $defaultDate) : null,
                'has_settlement_session' => $hasSettlementSession !== null ? ($hasSettlementSession == '1' ? true : false) : null,
                'settlement_date' => $hasSettlementSession !== null ? ($request->settlement_date ?: $defaultDate) : null,
                'has_final_exit' => $hasFinalExit !== null ? ($hasFinalExit == '1' ? true : false) : null,
                'final_exit_date' => $hasFinalExit == '1' ? ($request->final_exit_date ?: $defaultDate) : null,
                'notes' => $request->notes
            ];

            // إذا كان حفظ جزئي، لا نغير الحالة إلى in_progress
            if (!$isPartialSave) {
                $updateData['status'] = 'in_progress';
            }

            $procedure->update($updateData);

            // تسجيل التحركات للتغييرات
            $newValues = $procedure->only([
                'salary_received',
                'salary_received_date',
                'has_passport',
                'passport_date',
                'has_deportation_letter',
                'deportation_letter_date',
                'has_settlement_session',
                'settlement_date',
                'has_final_exit',
                'final_exit_date',
                'notes'
            ]);

            foreach ($newValues as $field => $newValue) {
                $oldValue = $oldValues[$field];

                // في حالة الحفظ الجزئي، نتجاهل التغييرات من null إلى null
                if ($isPartialSave && $oldValue === null && $newValue === null) {
                    continue;
                }

                if ($oldValue !== $newValue) {
                    $description = $isPartialSave ? "تم تحديث جزئي {$this->getFieldDisplayName($field)}" : "تم تحديث {$this->getFieldDisplayName($field)}";
                    \App\Models\DeportationProcedureLog::logUpdate(
                        $procedure->id,
                        auth('admin')->id(),
                        $field,
                        $oldValue,
                        $newValue,
                        $description
                    );
                }
            }

            // معالجة المرفقات لكل مرحلة
            $allAttachments = [];

            // مرفقات استلام الراتب
            if ($request->hasFile('salary_attachments')) {
                foreach ($request->file('salary_attachments') as $file) {
                    $path = app(ManagedFileStorage::class)->uploadFile($file, 'deportation-attachments/salary');
                    $allAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => 'salary',
                        'uploaded_at' => now()
                    ];

                    \App\Models\DeportationProcedureLog::logAttachmentAdded(
                        $procedure->id,
                        auth('admin')->id(),
                        $file->getClientOriginalName(),
                        "تم إضافة مرفق استلام الراتب: {$file->getClientOriginalName()}"
                    );
                }
            }

            // مرفقات الجواز
            if ($request->hasFile('passport_attachments')) {
                \Log::info('Passport attachments found', [
                    'count' => count($request->file('passport_attachments')),
                    'files' => array_map(function ($file) {
                        return $file->getClientOriginalName();
                    }, $request->file('passport_attachments'))
                ]);

                foreach ($request->file('passport_attachments') as $file) {
                    $path = app(ManagedFileStorage::class)->uploadFile($file, 'deportation-attachments/passport');
                    $allAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => 'passport',
                        'uploaded_at' => now()
                    ];

                    \App\Models\DeportationProcedureLog::logAttachmentAdded(
                        $procedure->id,
                        auth('admin')->id(),
                        $file->getClientOriginalName(),
                        "تم إضافة مرفق الجواز: {$file->getClientOriginalName()}"
                    );
                }
            } else {
                \Log::info('No passport attachments found');
            }

            // مرفقات خطاب الترحيل
            if ($request->hasFile('letter_attachments')) {
                foreach ($request->file('letter_attachments') as $file) {
                    $path = app(ManagedFileStorage::class)->uploadFile($file, 'deportation-attachments/letter');
                    $allAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => 'letter',
                        'uploaded_at' => now()
                    ];

                    \App\Models\DeportationProcedureLog::logAttachmentAdded(
                        $procedure->id,
                        auth('admin')->id(),
                        $file->getClientOriginalName(),
                        "تم إضافة مرفق خطاب الترحيل: {$file->getClientOriginalName()}"
                    );
                }
            }

            // مرفقات جلسة التسوية
            if ($request->hasFile('settlement_attachments')) {
                foreach ($request->file('settlement_attachments') as $file) {
                    $path = app(ManagedFileStorage::class)->uploadFile($file, 'deportation-attachments/settlement');
                    $allAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => 'settlement',
                        'uploaded_at' => now()
                    ];

                    \App\Models\DeportationProcedureLog::logAttachmentAdded(
                        $procedure->id,
                        auth('admin')->id(),
                        $file->getClientOriginalName(),
                        "تم إضافة مرفق جلسة التسوية: {$file->getClientOriginalName()}"
                    );
                }
            }

            // مرفقات الخروج النهائي
            if ($request->hasFile('exit_attachments')) {
                foreach ($request->file('exit_attachments') as $file) {
                    $path = app(ManagedFileStorage::class)->uploadFile($file, 'deportation-attachments/exit');
                    $allAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => 'exit',
                        'uploaded_at' => now()
                    ];

                    \App\Models\DeportationProcedureLog::logAttachmentAdded(
                        $procedure->id,
                        auth('admin')->id(),
                        $file->getClientOriginalName(),
                        "تم إضافة مرفق الخروج النهائي: {$file->getClientOriginalName()}"
                    );
                }
            }

            // حفظ جميع المرفقات
            if (!empty($allAttachments)) {
                $existingAttachments = $procedure->attachments ?? [];

                // تجنب التكرار عن طريق التحقق من وجود المرفق بنفس الاسم والنوع
                $filteredAttachments = [];
                foreach ($allAttachments as $newAttachment) {
                    $exists = false;
                    foreach ($existingAttachments as $existing) {
                        if (
                            is_array($existing) &&
                            isset($existing['name']) && isset($newAttachment['name']) &&
                            $existing['name'] === $newAttachment['name'] &&
                            isset($existing['type']) && isset($newAttachment['type']) &&
                            $existing['type'] === $newAttachment['type']
                        ) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $filteredAttachments[] = $newAttachment;
                    }
                }

                $procedure->attachments = array_merge($existingAttachments, $filteredAttachments);
                $procedure->save();
            }

            $message = $isPartialSave ?
                'تم حفظ التقدم الجزئي بنجاح' :
                'تم تحديث إجراءات الترحيل بنجاح';

            return response()->json([
                'success' => true,
                'message' => $message,
                'completion_percentage' => $procedure->completion_percentage,
                'partial_save' => $isPartialSave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض العقود التي تم ترحيلها
     */
    public function deported(Request $request)
    {
        if ($request->ajax()) {
            $query = \App\Models\DeportationProcedure::with([
                'contract',
                'contract.maid',
                'contract.maid.nationality.translations',
                'contract.user',
                'completedBy'
            ])->where('status', 'completed');
            $query->whereHas('contract', function ($contractQuery) use ($request) {
                $this->applyRequestBranchFilter($contractQuery, $request, 'contracts');
            });

            // تطبيق الفلاتر
            if ($request->filled('contract_number')) {
                $query->whereHas('contract', function ($q) use ($request) {
                    $q->where('id', 'like', '%' . $request->contract_number . '%');
                });
            }

            if ($request->filled('maid_name')) {
                $query->whereHas('contract.maid', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            if ($request->filled('deportation_date')) {
                $query->whereDate('completed_at', $request->deportation_date);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('contract_number', function ($row) {
                    return 'عقد رقم: ' . $row->contract_id;
                })
                ->addColumn('maid_data', function ($row) {
                    if (!$row->contract || !$row->contract->maid) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $maid = $row->contract->maid;
                    $maidName = $maid->name ?: 'عاملة رقم: ' . $maid->id;
                    $passport = $maid->passport_num ?: 'غير محدد';

                    return '<div class="maid-info">
                                <strong>' . $maidName . '</strong><br>
                                <small class="text-muted">جواز: ' . $passport . '</small>
                            </div>';
                })
                ->addColumn('client', function ($row) {
                    if (!$row->contract || !$row->contract->user) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $user = $row->contract->user;
                    $userName = $user->name ?: 'عميل رقم: ' . $user->id;
                    $userPhone = $user->phone ?: 'غير محدد';

                    return '<div class="user-info">
                                <strong>' . $userName . '</strong><br>
                                <small class="text-muted">' . $userPhone . '</small>
                            </div>';
                })
                ->addColumn('nationality', function ($row) {
                    if (!$row->contract || !$row->contract->maid || !$row->contract->maid->nationality) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $locale = app()->getLocale();
                    $translation = $row->contract->maid->nationality->translations->where('locale', $locale)->first();
                    $nationality = $translation ? $translation->sub_title : 'غير محدد';

                    return '<span class="badge bg-info">' . $nationality . '</span>';
                })
                ->addColumn('deportation_date', function ($row) {
                    return $row->completed_at ? $row->completed_at->format('Y-m-d H:i') : 'غير محدد';
                })
                ->addColumn('completed_by', function ($row) {
                    return $row->completedBy ? $row->completedBy->name : 'غير محدد';
                })
                ->addColumn('status', function ($row) {
                    return '<span class="badge text-bg-danger">تم الترحيل</span>';
                })
                ->addColumn('actions', function ($row) {
                    $canViewDetails = $this->adminCanAny($this->shelterContractDetailViewPermissions());
                    $canViewDeportationProcedures = $this->adminCanAny($this->shelterDeportationProceduresViewPermissions());

                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    if ($canViewDeportationProcedures) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.deportation.procedures', $row->contract_id) . '">
                                        <i class="fas fa-clipboard-check"></i> عرض إجراءات الترحيل
                                    </a></li>';
                    }

                    if ($canViewDetails) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.show', $row->contract_id) . '">
                                        <i class="fas fa-eye"></i> عرض تفاصيل العقد
                                    </a></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['maid_data', 'client', 'nationality', 'status', 'actions'])
                ->toJson();
        }

        return view('admin.ingaz.ShelterContract.deported');
    }

    /**
     * تغيير رغبة العمل للعاملة
     */
    public function changeWorkDesire(Request $request, $id)
    {
        $this->assertCanAccessBranchModel(Contract::findOrFail($id), 'contracts');

        try {
            $request->validate([
                'wants_to_work' => 'required|in:yes,no,unknown',
                'reason' => 'nullable|string|max:500'
            ]);

            $contract = Contract::with(['maid', 'info'])->findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            $oldDesire = $contract->info ? $contract->info->wants_to_work : 'unknown';
            $newDesire = $request->wants_to_work;

            // التحقق من أن القيمة الجديدة مختلفة
            if ($oldDesire === $newDesire) {
                return response()->json([
                    'success' => false,
                    'message' => 'رغبة العمل لم تتغير'
                ], 400);
            }

            // تحديث رغبة العمل
            if ($contract->info) {
                $contract->info->update([
                    'wants_to_work' => $newDesire
                ]);
            } else {
                $contract->info()->create([
                    'contract_id' => $contract->id,
                    'wants_to_work' => $newDesire
                ]);
            }

            // تسجيل التغيير في اللوج
            \Log::info('Work desire changed for contract', [
                'contract_id' => $contract->id,
                'maid_name' => $contract->maid->name,
                'old_desire' => $oldDesire,
                'new_desire' => $newDesire,
                'reason' => $request->reason,
                'changed_by' => auth('admin')->id()
            ]);

            // تحديد رسالة النجاح
            $desireText = [
                'yes' => 'ترغب بالعمل',
                'no' => 'لا ترغب بالعمل',
                'unknown' => 'غير محدد'
            ];

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير رغبة العمل إلى: ' . $desireText[$newDesire],
                'data' => [
                    'old_desire' => $oldDesire,
                    'new_desire' => $newDesire,
                    'maid_name' => $contract->maid->name
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error changing work desire: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير رغبة العمل'
            ], 500);
        }
    }

    public function updateQuickTags(Request $request, $id)
    {
        if (!$this->supportsQuickTags()) {
            return response()->json([
                'success' => false,
                'message' => 'حقل التصنيفات السريعة غير متاح قبل تنفيذ التحديثات المطلوبة.'
            ], 422);
        }

        $this->assertCanAccessBranchModel(Contract::findOrFail($id), 'contracts');

        try {
            $request->validate([
                'shelter_quick_tags' => 'nullable|string|max:1000',
            ]);

            $contract = Contract::with('info')->findOrFail($id);
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس داخل الإيواء'
                ], 400);
            }

            $tags = $this->normalizeQuickTags($request->input('shelter_quick_tags'));

            if ($contract->info) {
                $contract->info->update([
                    'shelter_quick_tags' => $tags,
                ]);
            } else {
                $contract->info()->create([
                    'contract_id' => $contract->id,
                    'shelter_quick_tags' => $tags,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التصنيفات السريعة بنجاح',
                'data' => [
                    'tags' => $tags,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating shelter quick tags: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التصنيفات السريعة'
            ], 500);
        }
    }

    private function getFieldDisplayName($field)
    {
        $fields = [
            "salary_received" => "استلام الراتب",
            "salary_received_date" => "تاريخ استلام الراتب",
            "has_passport" => "وجود الجواز",
            "passport_date" => "تاريخ الجواز",
            "has_deportation_letter" => "خطاب الترحيل",
            "deportation_letter_date" => "تاريخ خطاب الترحيل",
            "has_settlement_session" => "جلسة التسوية",
            "settlement_date" => "تاريخ جلسة التسوية",
            "has_final_exit" => "الخروج النهائي",
            "final_exit_date" => "تاريخ الخروج النهائي",
            "notes" => "الملاحظات"
        ];

        return $fields[$field] ?? $field;
    }
    public function getActivityLog($id)
    {
        $procedureContract = Contract::findOrFail(
            \App\Models\DeportationProcedure::findOrFail($id)->contract_id
        );
        $this->assertCanAccessBranchModel($procedureContract, 'contracts');

        try {
            $procedure = \App\Models\DeportationProcedure::findOrFail($id);
            $logs = $procedure->logs()->with("admin")->orderBy('created_at', 'desc')->get();

            $html = view("admin.ingaz.ShelterContract.partials.activity-log", compact("logs"))->render();

            return response()->json([
                "success" => true,
                "html" => $html,
                "logs_count" => $logs->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "حدث خطأ في تحميل سجل التحركات",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تغيير حالة الاستلام الفوري
     */
    public function toggleInstantReceipt(Request $request, $id)
    {
        $authorizationContract = Contract::with('maid.jobTypes')->findOrFail($id);
        $this->assertCanAccessBranchModel($authorizationContract, 'contracts');
        $isRentalWorker = $this->determineShelterServiceCategory($authorizationContract->maid) === 'rental';
        $requiredPermissions = $isRentalWorker
            ? $this->shelterRentalInstantReceiptEditPermissions()
            : $this->shelterInstantReceiptEditPermissions();
        abort_unless($this->adminCanAny($requiredPermissions), 403);

        try {
            $contract = Contract::with(['maid', 'info'])->findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            // التحقق من أن العاملة ترغب بالعمل
            if (!$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة لا ترغب بالعمل - لا يمكن إتاحتها للاستلام الفوري / نقل الخدمات'
                ], 400);
            }

            $oldStatus = $contract->info->is_available_for_instant;
            $newStatus = !$oldStatus;

            // إتاحة عاملة الإيجار لنقل الخدمات تتطلب تفعيل الإعداد في إعدادات السير الذاتية.
            if ($newStatus && !$this->instantReceiptFieldEnabledFor($contract->maid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'إتاحة سيرة عاملة الإيجار لنقل الخدمات غير مفعّلة في إعدادات السير الذاتية'
                ], 400);
            }

            // تحديث حالة الاستلام الفوري
            $contract->info->update([
                'is_available_for_instant' => $newStatus
            ]);

            $this->syncMaidInstantDisplay($contract, $newStatus);

            // تسجيل التغيير في اللوج
            \Log::info('Instant receipt availability changed for contract', [
                'contract_id' => $contract->id,
                'maid_name' => $contract->maid->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => auth('admin')->id()
            ]);

            $availableStatus = $this->determineShelterServiceCategory($contract->maid) === 'rental'
                ? 'متاحة للاستلام الفوري وتظهر في نقل الخدمات'
                : 'متاحة لنقل الخدمات';
            $statusText = $newStatus ? $availableStatus : 'غير متاحة';
            $actionText = $newStatus ? 'إتاحة' : 'إلغاء إتاحة';

            return response()->json([
                'success' => true,
                'message' => 'تم ' . $actionText . ' العاملة للاستلام الفوري بنجاح',
                'data' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'maid_name' => $contract->maid->name,
                    'status_text' => $statusText
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling instant receipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الاستلام الفوري / نقل الخدمات'
            ], 500);
        }
    }

    public function deliveryShelterContractDownload($id)
    {
        $contract = Contract::with(['user', 'maid.nationality', 'info'])->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');
        $documentBranding = documentBranding();
        $clientName = $contract->user->name ?? '';
        $clientNationalNumber = $contract->user?->documentNumber() ?? '';

        $maid = $contract->maid;
        $passportId = $maid->passport_num ?? '';
        $nationalityTitle = $maid?->nationality?->title ?? $maid?->nationality?->sub_title ?? '';
        $arriveDate = filled($maid?->available_at) ? Carbon::parse($maid->available_at)->format('Y/m/d') : '--';
        $dateReceipt = filled($contract->contract_date) ? Carbon::parse($contract->contract_date)->format('Y/m/d') : '--';
        $maidName = $maid->name ?? '';

        $textReceipt = $contract->info?->receipt_form_text ?? '';
        $printedBy = auth('admin')->user()?->name ?? 'النظام';
        $printedAt = Carbon::now()->format('Y/m/d H:i:s');
        $contractSignedAt = optional($contract->created_at)->format('Y/m/d H:i');
        $printUrl = route('shelter_contract_form.download', [
            'id' => $contract->id,
            'print' => 1,
            'autoprint' => 1,
        ]);
        $viewData = [
            'contract' => $contract,
            'document_company_name' => $documentBranding['company_name'],
            'document_office_name' => $documentBranding['office_name'],
            'document_footer_note' => $documentBranding['footer_note'],
            'client_name' => $clientName,
            'national_identification_number' => $clientNationalNumber,
            'passport_id' => $passportId,
            'nationality_id' => $nationalityTitle,
            'arrive_date' => $arriveDate,
            'date_receipt' => $dateReceipt,
            'cvs_name' => $maidName,
            'text_receipt' => $textReceipt,
            'printed_by' => $printedBy,
            'printed_at' => $printedAt,
            'contract_signed_at' => $contractSignedAt ?: '--',
            'print_url' => $printUrl,
        ];

        // dompdf does not reliably shape Arabic text, so browser print is the default.
        if (request()->query('driver') !== 'dompdf') {
            if (request()->boolean('print')) {
                return response()->view('admin.ingaz.ShelterContract.receipt_form_printable', $viewData, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);
            }

            return response()->view('admin.ingaz.ShelterContract.receipt_form_print', $viewData, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $pdfFilename = 'contract-' . $contract->id . '.pdf';
        $previousErrorReporting = error_reporting();

        error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $pdf = PDF::loadView('admin.ingaz.ShelterContract.receipt_form', $viewData, [], [
                'format' => 'A4',
                'mode' => 'utf-8',
            ]);

            if (request()->boolean('download') || request()->query('type') === 'download') {
                return $pdf->download($pdfFilename);
            }

            return $pdf->stream($pdfFilename);
        } finally {
            error_reporting($previousErrorReporting);
        }
    }
}
