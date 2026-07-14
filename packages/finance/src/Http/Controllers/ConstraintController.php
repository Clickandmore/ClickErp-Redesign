<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Invoice;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Http\Requests\ConstraintRequest as ObjRequest;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\DataTables;

class ConstraintController extends Controller
{
    protected string $folderPath = "finance::finance.constraint";
    protected string $mainRoute = "finance.constraints";

    public function __construct()
    {
        $this->middleware('checkActiveYear')->only(['create','edit']);
    }
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return $this->constraintDataTable($service->getIndexData(), $request)->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "exportPdfRoute" => route($this->mainRoute . ".export", ['format' => 'pdf']),
            "exportExcelRoute" => route($this->mainRoute . ".export", ['format' => 'excel']),
            "bladeTitle" => __("finance::finance.constraints"),
            "addButtonText" => __("finance::finance.constraint"),
            'modalType' => 'modal-fullscreen',
            'request' => $request,
            'approvalMode' => 'approved',
            'pendingRoute' => route($this->mainRoute . '.pending'),
            'approvedRoute' => route($this->mainRoute . '.index'),
            'typeOptions' => ConstraintTypiesEnum::cases(),
        ]);
    }

    public function pending(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return $this->constraintDataTable($service->getPendingApprovalData(), $request, true)->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".pending"),
            "exportPdfRoute" => '#',
            "exportExcelRoute" => '#',
            "bladeTitle" => __("finance::finance.pending_constraints_approval"),
            "addButtonText" => __("finance::finance.constraint"),
            'modalType' => 'modal-fullscreen',
            'request' => $request,
            'approvalMode' => 'pending',
            'pendingRoute' => route($this->mainRoute . '.pending'),
            'approvedRoute' => route($this->mainRoute . '.index'),
            'typeOptions' => ConstraintTypiesEnum::cases(),
        ]);
    }

    public function approve(int $id, ObjService $service)
    {
        abort_unless($this->canApproveConstraints(), 403, __('ingaz.permission_denied'));

        $service->approveConstraint($id, auth('admin')->id());

        return jsonSuccess(null, __("finance::finance.constraint_approved_successfully"));
    }

    public function export(Request $request, ObjService $service, string $format)
    {
        $constraints = $this->filteredExportQuery($request, $service)->get();
        $rows = $this->buildExportRows($constraints);
        $totals = [
            'count' => $constraints->count(),
            'value' => $constraints->sum(fn ($constraint) => (float) $constraint->value),
        ];

        if (in_array($format, ['excel', 'xlsx'], true)) {
            return $this->exportExcelFile($rows);
        }

        $pdf = \PDF::loadView($this->folderPath . '.export_pdf', [
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $this->resolveExportFilters($request),
            'title' => __('finance::finance.constraints'),
        ])->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download($this->buildExportFilename('pdf'));
    }

    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".create")->with([
                    'accounts' => $service->getAccounts(),
                    'lastCode' => $service->lastCode(),
                    "cost_centers" => $service->getCostCenters(),
                    "storeRoute" => route($this->mainRoute . ".store"),
                    "cost_center_required" => $service->getConfirmSetting(ConfirmSettingTypes::CostCenter->value),
                ])->render()
            ]);
        }
    }

    public function store(ObjRequest $request, ObjService $service, YearService $yearService)
    {
        $data = $request->validated();
        if (!$yearService->checkDateRange($data['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $getActiveYear = $yearService->getActiveYear();
        $storeConstraint = $service->storeConstraint($data, $getActiveYear);
        $service->storeConstraintDetails($data['account_id'], $storeConstraint->id, $data);
        $service->storeFiles($storeConstraint, $data);
        return jsonSuccess($storeConstraint);
    }

    public function show($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".show")->with([
                    "obj" => $service->find($id),
                    'accounts' => $service->getAccounts(),
                    'lastCode' => $service->lastCode(),
                    "cost_centers" => $service->getCostCenters(),
                ])->render()
            ]);
        }
    }

    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".edit")->with([
                    "obj" => $service->find($id),
                    'accounts' => $service->getAccounts(),
                    'lastCode' => $service->lastCode(),
                    "cost_centers" => $service->getCostCenters(),
                    "updateRoute" => route($this->mainRoute . ".update", $id),
                    "cost_center_required" => $service->getConfirmSetting(ConfirmSettingTypes::CostCenter->value),
                ])->render()
            ]);
        }
    }

    public function update(ObjRequest $request, $id, ObjService $service, YearService $yearService)
    {
        $data = $request->validated();
        if (!$yearService->checkDateRange($data['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }

        $getActiveYear = $yearService->getActiveYear();
        $before = $service->constraintSnapshot($service->find($id));

        $updateConstraint = $service->updateConstraint($id, $data, $request, $getActiveYear);
        $service->updateConstraintDetails($updateConstraint->id, $data['account_id'], $data);
        $service->storeFiles($updateConstraint, $data);
        $service->logConstraintActivity($updateConstraint->refresh(), 'updated', __('finance::finance.constraint_updated_log_description'), [
            'before' => $before,
            'after' => $service->constraintSnapshot($updateConstraint),
        ]);
        return jsonSuccess();
    }

    public function destroy($id, ObjService $service)
    {
        $service->deleteConstraint($id);
        return jsonSuccess();
    }

    private function constraintDataTable($query, Request $request, bool $pending = false)
    {
        $requestedLength = (int) $request->input('length', 10);
        if ($requestedLength < 1 || $requestedLength > 100) {
            $request->merge(['length' => 100]);
        }

        $dataTable = DataTables::of($query)
            ->addColumn('code_link', function ($row) {
                return $this->renderConstraintCodeLink($row);
            })
            ->addColumn('statement_preview', function ($row) {
                return $this->renderStatementPreview($row);
            })
            ->addColumn('debitors_total', function ($row) {
                return $this->renderDetailsSummary($row, 'debtor');
            })
            ->addColumn('creditors_total', function ($row) {
                return $this->renderDetailsSummary($row, 'creditor');
            })
            ->editColumn('value', function ($row) {
                return number_format((float) $row->value, 2, '.', ',');
            })
            ->editColumn('date', function ($row) {
                return '<span title="' . e($row->statement) . '">' . e($row->date) . '</span>';
            })
            ->addColumn('print_action', function ($row) {
                return $this->renderPrintAction($row);
            })
            ->addColumn('document_action', function ($row) {
                return $this->renderDocumentAction($row);
            })
            ->addColumn('actions', function ($row) {
                return $this->renderRowActions($row);
            })
            ->filter(function ($instance) use ($request) {
                $this->applyConstraintFilters($instance, $request);
            });

        if ($pending) {
            $dataTable
                ->addColumn('approval_status', function ($row) {
                    return $this->renderApprovalStatus($row);
                })
                ->addColumn('approval_action', function ($row) {
                    return $this->renderApprovalAction($row);
                });
        }

        $rawColumns = [
            'code_link',
            'statement_preview',
            'debitors_total',
            'creditors_total',
            'date',
            'print_action',
            'document_action',
            'actions',
        ];

        if ($pending) {
            $rawColumns[] = 'approval_status';
            $rawColumns[] = 'approval_action';
        }

        return $dataTable->rawColumns($rawColumns);
    }

    private function renderApprovalStatus(Constraint $constraint): string
    {
        $createdBy = $constraint->created_by?->name ?: '-';

        return '<div class="constraint-approval-cell">'
            . '<span class="badge bg-warning text-dark">' . e(__('finance::finance.pending_approval')) . '</span>'
            . '<small>' . e(__('finance::finance.created_by')) . ': ' . e($createdBy) . '</small>'
            . '</div>';
    }

    private function renderApprovalAction(Constraint $constraint): string
    {
        if (!$this->canApproveConstraints()) {
            return '<span class="text-muted">-</span>';
        }

        return '<button type="button" class="btn btn-sm btn-success approveConstraintButton" data-action="'
            . e(route($this->mainRoute . '.approve', $constraint->id)) . '">'
            . '<i class="fa fa-check"></i> '
            . e(__('finance::finance.approve_constraint'))
            . '</button>';
    }

    private function canApproveConstraints(): bool
    {
        $admin = auth('admin')->user();

        return $admin
            && (
                $admin->admin_type == \App\Enums\AdminTypeisEnum::Developer->value
                || $admin->can(Constraint::APPROVE_PERMISSION)
            );
    }

    private function renderConstraintCodeLink(Constraint $constraint): string
    {
        $label = '#' . ($constraint->code ?: $constraint->id);
        $url = $this->resolveConstraintPrintUrl($constraint);

        return '<a href="' . $url . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">'
            . e($label) .
            '</a>';
    }

    private function renderStatementPreview(Constraint $constraint): string
    {
        $type = $this->resolveConstraintType($constraint);
        $typeLabel = $type?->lang() ?? __('finance::finance.constraint');
        $statement = $constraint->statement ?: '-';

        return '<div class="constraint-statement-cell">'
            . '<strong>' . e($statement) . '</strong>'
            . '<small>' . e($typeLabel) . '</small>'
            . '</div>';
    }

    private function renderDetailsSummary(Constraint $constraint, string $type): string
    {
        $details = $constraint->constraintDetails->where('type', $type);
        $visibleDetailsLimit = 3;

        if ($details->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }

        $fullTitle = $details->map(function ($detail) use ($type) {
            $value = $type === 'debtor'
                ? (float) $detail->debtor_value
                : (float) $detail->creditor_value;

            return ($detail->account->name ?? '-') . ' - ' . number_format($value, 2, '.', ',');
        })->implode("\n");

        $visibleDetails = $details->take($visibleDetailsLimit)->values();
        $items = $visibleDetails->map(function ($detail, $index) use ($type, $visibleDetails) {
            $value = $type === 'debtor'
                ? (float) $detail->debtor_value
                : (float) $detail->creditor_value;
            $isLastVisible = $index === $visibleDetails->count() - 1;

            return '<span class="constraint-account-line' . ($isLastVisible ? ' constraint-account-line--last' : '') . '">'
                . '<span class="constraint-account-name" title="' . e($detail->account->name ?? '-') . '">'
                . e($detail->account->name ?? '-')
                . '</span>'
                . '<strong>' . e(number_format($value, 2, '.', ',')) . '</strong>'
                . '</span>';
        })->implode('');

        $remainingCount = $details->count() - $visibleDetailsLimit;
        $more = $remainingCount > 0
            ? '<span class="constraint-account-more">+' . e($remainingCount) . ' بنود أخرى</span>'
            : '';

        return '<div class="constraint-account-list" title="' . e($fullTitle) . '">' . $items . $more . '</div>';
    }

    private function renderPrintAction(Constraint $constraint): string
    {
        return '<a href="' . $this->resolveConstraintPrintUrl($constraint) . '" target="_blank" rel="noopener" class="btn btn-sm btn-primary">'
            . '<i class="fa fa-print"></i> '
            . e(__('finance::finance.print'))
            . '</a>';
    }

    private function renderDocumentAction(Constraint $constraint): string
    {
        $document = $this->resolveConstraintDocument($constraint);

        return '<a href="' . $document['url'] . '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">'
            . e($document['label'])
            . '</a>';
    }

    private function renderRowActions(Constraint $constraint): string
    {
        $editUrl = route('finance.add-constraint.edit', $constraint->id);
        $deleteUrl = route($this->mainRoute . '.destroy', $constraint->id);
        $printUrl = $this->resolveConstraintPrintUrl($constraint);
        $document = $this->resolveConstraintDocument($constraint);

        return '<div class="dropdown constraint-actions-dropdown">'
            . '<button class="btn btn-sm btn-light border constraint-actions-toggle" type="button" aria-expanded="false">'
            . '<i class="fa fa-ellipsis-v"></i> ' . e(__('finance::finance.actions'))
            . '</button>'
            . '<ul class="dropdown-menu dropdown-menu-end shadow border-0 constraint-actions-menu">'
            . '<li><a class="dropdown-item" href="' . e($printUrl) . '" target="_blank" rel="noopener">'
            . '<i class="fa fa-print"></i> ' . e(__('finance::finance.print')) . '</a></li>'
            . '<li><a class="dropdown-item" href="' . e($document['url']) . '" target="_blank" rel="noopener">'
            . '<i class="fa fa-file-text-o"></i> ' . e($document['label']) . '</a></li>'
            . '<li><hr class="dropdown-divider"></li>'
            . '<li><a class="dropdown-item" href="' . e($editUrl) . '">'
            . '<i class="fa fa-pencil"></i> ' . e(__('finance::finance.edit')) . '</a></li>'
            . '<li><button type="button" class="dropdown-item text-danger deleteConstraintButton" data-action="' . e($deleteUrl) . '">'
            . '<i class="fa fa-trash"></i> ' . e(__('finance::finance.Delete')) . '</button></li>'
            . '</ul>'
            . '</div>';
    }

    private function resolveConstraintDocument(Constraint $constraint): array
    {
        $type = $this->resolveConstraintType($constraint);

        if ($constraint->bond) {
            $bondType = $constraint->bond->type === 'sarf' ? 'sarf' : 'qabd';
            $bondLabel = $bondType === 'sarf' ? 'سند الصرف' : 'سند القبض';

            return [
                'label' => $bondLabel . ' #' . ($constraint->bond->code ?: $constraint->bond->id),
                'url' => route('finance.bonds-print.show', [
                    'bonds_print' => $constraint->bond->id,
                    'type' => $bondType,
                ]),
            ];
        }

        $rentalInvoice = $constraint->rentalInvoiceConstraint
            ?: ($constraint->legacy_invoice_type === 'rental' ? $constraint->rentalInvoice : null);
        if ($rentalInvoice) {
            return [
                'label' => 'فاتورة التأجير #' . ($rentalInvoice->legacy_invoice_id ?: $rentalInvoice->id),
                'url' => route('rental.invoice.download', [
                    'id' => $rentalInvoice->id,
                    'autoprint' => 1,
                ]),
            ];
        }

        if ($constraint->rentalReceiptInvoice) {
            return [
                'label' => 'فاتورة التأجير #' . ($constraint->rentalReceiptInvoice->legacy_invoice_id ?: $constraint->rentalReceiptInvoice->id),
                'url' => route('rental.invoice.download', [
                    'id' => $constraint->rentalReceiptInvoice->id,
                    'autoprint' => 1,
                ]),
            ];
        }

        if ($type === ConstraintTypiesEnum::TransferEntry && $constraint->transfer_entry) {
            return [
                'label' => 'مستند التحويل #' . $constraint->transfer_entry->id,
                'url' => route('finance.account-transfer-print.show', [
                    'account_transfer_print' => $constraint->transfer_entry->id,
                ]),
            ];
        }

        $recruitmentInvoice = $constraint->arrivalRevenueInvoice
            ?: $constraint->externalOfficeCostInvoice
            ?: $constraint->ingazDelegationCostInvoice
            ?: ($constraint->legacy_invoice_type === 'recruitment' ? $constraint->recruitmentInvoice : null);
        if ($recruitmentInvoice) {
            return [
                'label' => 'الفاتورة #' . ($recruitmentInvoice->legacy_invoice_number ?: $recruitmentInvoice->id),
                'url' => route('invoice.download', [
                    'id' => $recruitmentInvoice->id,
                    'autoprint' => 1,
                ]),
            ];
        }

        $invoiceId = $this->extractInvoiceIdFromStatement((string) $constraint->statement);
        if ($invoiceId) {
            return [
                'label' => 'الفاتورة #' . $invoiceId,
                'url' => route('invoice.download', [
                    'id' => $invoiceId,
                    'autoprint' => 1,
                ]),
            ];
        }

        return [
            'label' => 'القيد نفسه',
            'url' => $this->resolveConstraintPrintUrl($constraint),
        ];
    }

    private function resolveConstraintPrintUrl(Constraint $constraint): string
    {
        return route('finance.add-constraint.show', [
            'add_constraint' => $constraint->id,
        ]);
    }

    private function resolveConstraintType(Constraint $constraint): ?ConstraintTypiesEnum
    {
        $rawType = $constraint->getRawOriginal('type');

        if ($rawType instanceof ConstraintTypiesEnum) {
            return $rawType;
        }

        if ($rawType === null) {
            $rawType = $constraint->getAttributes()['type'] ?? null;
        }

        if ($rawType instanceof ConstraintTypiesEnum) {
            return $rawType;
        }

        if (!is_numeric($rawType)) {
            return null;
        }

        return ConstraintTypiesEnum::tryFrom((int) $rawType);
    }

    private function filteredExportQuery(Request $request, ObjService $service)
    {
        $query = $service->getIndexData();

        return $this->applyConstraintFilters($query, $request);
    }

    private function applyConstraintFilters($query, Request $request)
    {
        $query
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->where('fn_constraints.date', '>=', $request->input('from_date'));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->where('fn_constraints.date', '<=', $request->input('to_date'));
            })
            ->when($request->filled('type_filter'), function ($query) use ($request) {
                $query->where('fn_constraints.type', $request->input('type_filter'));
            });

        $keyword = $this->resolveSearchKeyword($request);
        if ($keyword === '') {
            return $query;
        }

        $numericKeyword = ltrim($keyword, '#');

        return $query->where(function ($query) use ($keyword, $numericKeyword) {
            if ($numericKeyword !== '' && ctype_digit($numericKeyword)) {
                $number = (int) $numericKeyword;

                return $query->where('fn_constraints.code', $number)
                    ->orWhere(function ($query) use ($number) {
                        $query->whereNull('fn_constraints.code')
                            ->where('fn_constraints.id', $number);
                    })
                    ->orWhere('fn_constraints.legacy_document_number', $number)
                    ->orWhere('fn_constraints.legacy_invoice_number', $number);
            }

            $query->where('fn_constraints.statement', 'like', '%' . $keyword . '%')
                ->orWhere('fn_constraints.value', 'like', '%' . $keyword . '%')
                ->orWhere('fn_constraints.date', 'like', '%' . $keyword . '%')
                ->orWhereHas('constraintDetails.account', function ($accountQuery) use ($keyword) {
                    $accountQuery->where('fn_accounts.name', 'like', '%' . $keyword . '%');
                });
        });
    }

    private function resolveSearchKeyword(Request $request): string
    {
        $keyword = $request->input('keyword');

        if ($keyword === null) {
            $keyword = $request->input('search.value', $request->input('sSearch', ''));
        }

        return trim(strtr((string) $keyword, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]));
    }

    private function buildExportRows($constraints): array
    {
        return $constraints->map(function (Constraint $constraint) {
            $document = $this->resolveConstraintDocument($constraint);

            return [
                'code' => $constraint->code ?: $constraint->id,
                'statement' => $constraint->statement ?: '-',
                'type' => $this->resolveConstraintType($constraint)?->lang() ?? __('finance::finance.constraint'),
                'value' => number_format((float) $constraint->value, 2, '.', ','),
                'date' => (string) $constraint->date,
                'debtors' => $this->formatDetailsForExport($constraint, 'debtor'),
                'creditors' => $this->formatDetailsForExport($constraint, 'creditor'),
                'document' => $document['label'],
            ];
        })->values()->all();
    }

    private function formatDetailsForExport(Constraint $constraint, string $type): string
    {
        return $constraint->constraintDetails
            ->where('type', $type)
            ->map(function ($detail) use ($type) {
                $value = $type === 'debtor'
                    ? (float) $detail->debtor_value
                    : (float) $detail->creditor_value;

                return ($detail->account->name ?? '-') . ' - ' . number_format($value, 2, '.', ',');
            })
            ->implode("\n");
    }

    private function resolveExportFilters(Request $request): array
    {
        $type = ConstraintTypiesEnum::tryFrom((int) $request->get('type_filter'));

        return [
            'keyword' => $this->resolveSearchKeyword($request) ?: 'الكل',
            'from_date' => $request->get('from_date') ?: 'الكل',
            'to_date' => $request->get('to_date') ?: 'الكل',
            'type' => $type?->lang() ?: 'الكل',
        ];
    }

    private function exportExcelFile(array $rows)
    {
        $headers = $this->exportHeaders();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('constraints');
        $sheet->setRightToLeft(app()->getLocale() === 'ar');
        $sheet->fromArray($headers, null, 'A1');

        $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'EEF2F7',
                ],
            ],
        ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['code'],
                $row['statement'],
                $row['type'],
                $row['value'],
                $row['date'],
                $row['debtors'],
                $row['creditors'],
                $row['document'],
            ], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $lastRow = max($rowNumber - 1, 1);
        $sheet->getStyle('A1:H' . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:H' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach (range(1, count($headers)) as $columnIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'constraints_');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                $this->buildExportFilename('xlsx'),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    private function exportHeaders(): array
    {
        return [
            'رقم القيد',
            __('finance::finance.statement'),
            'نوع القيد',
            __('finance::finance.value'),
            __('finance::finance.date'),
            'مدينون',
            'دائنون',
            'المستند',
        ];
    }

    private function buildExportFilename(string $extension): string
    {
        return 'finance-constraints-' . now()->format('Ymd_His') . '.' . $extension;
    }

    private function extractInvoiceIdFromStatement(string $statement): ?int
    {
        if (!preg_match('/طلب\s+استقدام|مساند/u', $statement)) {
            return null;
        }

        foreach ([
            '/الفاتورة\s*#\s*(\d+)/u',
            '/فاتورة\s+رقم\s*[:#-]?\s*(\d+)/u',
        ] as $pattern) {
            if (preg_match($pattern, $statement, $matches)) {
                $invoiceId = (int) ($matches[1] ?? 0);
                if ($invoiceId > 0 && Invoice::query()->whereKey($invoiceId)->exists()) {
                    return $invoiceId;
                }
            }
        }

        return null;
    }
}
