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
            return DataTables::of($service->getIndexData())
                ->addColumn('code_link', function ($row) {
                    return $this->renderConstraintCodeLink($row);
                })
                ->addColumn('statement_preview', function ($row) {
                    return $this->renderStatementPreview($row);
                })
                ->addColumn('debitors_total', function ($row) use ($service) {
                    return $service->calculateTotal($row->constraintDetails, 'debtor');
                })
                ->addColumn('creditors_total', function ($row) use ($service) {
                    return $service->calculateTotal($row->constraintDetails, 'creditor');
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
                ->filter(function ($instance) use ($request) {
                    if ($request->get('from_date')) {
                        $instance->where('date','>=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('date','<=', $request->get('to_date'));
                    }
                })
                ->rawColumns([
                    'code_link',
                    'statement_preview',
                    'debitors_total',
                    'creditors_total',
                    'date',
                    'print_action',
                    'document_action',
                ])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "bladeTitle" => __("finance::finance.constraints"),
            "addButtonText" => __("finance::finance.constraint"),
            'modalType' => 'modal-fullscreen',
            'request' => $request
        ]);
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

        $updateConstraint = $service->updateConstraint($id, $data, $request, $getActiveYear);
        $service->updateConstraintDetails($updateConstraint->id, $data['account_id'], $data);
        $service->storeFiles($updateConstraint, $data);
        return jsonSuccess();
    }

    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
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

    private function resolveConstraintDocument(Constraint $constraint): array
    {
        $type = $this->resolveConstraintType($constraint);

        if ($type === ConstraintTypiesEnum::QapdBond && $constraint->bond) {
            return [
                'label' => 'سند القبض #' . $constraint->bond->id,
                'url' => route('finance.bonds-print.show', [
                    'bonds_print' => $constraint->bond->id,
                    'type' => 'qabd',
                ]),
            ];
        }

        if ($type === ConstraintTypiesEnum::SarfBond && $constraint->bond) {
            return [
                'label' => 'سند الصرف #' . $constraint->bond->id,
                'url' => route('finance.bonds-print.show', [
                    'bonds_print' => $constraint->bond->id,
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
            'autoprint' => 1,
        ]);
    }

    private function resolveConstraintType(Constraint $constraint): ?ConstraintTypiesEnum
    {
        if ($constraint->type instanceof ConstraintTypiesEnum) {
            return $constraint->type;
        }

        return ConstraintTypiesEnum::tryFrom((int) $constraint->type);
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
