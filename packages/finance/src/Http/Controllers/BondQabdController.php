<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Http\Middleware\CheckActiveYear;
use Nami\Finance\Http\Requests\Bond\StoreBondRequest as StoreRequest;
use Nami\Finance\Http\Requests\Bond\UpdateBondRequest as UpdateRequest;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\BondService as ObjService;
use Nami\Finance\Services\BondListExportService;
use Nami\Finance\Services\YearService;
use Yajra\DataTables\DataTables;

class BondQabdController extends Controller
{
    public $folderPath = "finance::finance.bond.qabd";
    public $mainRoute = "finance.bonds-qabd";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create', 'edit']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, ObjService $service, AccountService $accountService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTableQabdData(BondTypes::Qabd->value);
            $filterDataTable = $service->filterBonds($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->order(function ($query) {
                    $query->reorder()
                        ->orderByRaw("CASE WHEN TRIM(bond_book_num) REGEXP '^[0-9]+$' THEN CAST(bond_book_num AS UNSIGNED) ELSE code END DESC")
                        ->orderByDesc('code')
                        ->orderByDesc('date')
                        ->orderByDesc('id');
                })
                ->addIndexColumn()
                ->addColumn('bond_num', function ($row) {
                    $printUrl = route('finance.bonds-print.show', ['bonds_print' => $row->id, 'type' => 'qabd']);
                    $bookNumber = filled(trim((string) $row->bond_book_num))
                        ? '<small>دفتر: ' . e($row->bond_book_num) . '</small>'
                        : '';

                    return '<div class="bond-voucher-number">'
                        . '<a href="' . e($printUrl) . '" target="_blank" rel="noopener">#' . e($row->code) . '</a>'
                        . $bookNumber . '</div>';
                })
                ->editColumn('date', function ($row) {
                    return '<div class="bond-date"><strong dir="ltr">' . e($row->date) . '</strong><small>تاريخ السند</small></div>';
                })
                ->addColumn('credit_account', function ($row) {
                    return '<div class="bond-account"><strong>' . e(optional($row->creditAccount)->name ?: '-') . '</strong>'
                        . '<small title="' . e($row->statement) . '">' . e($row->statement ?: 'بدون بيان') . '</small></div>';
                })
                ->addColumn('debit_account', function ($row) {
                    return '<div class="bond-account"><strong>' . e(optional($row->debitAccount)->name ?: '-') . '</strong>'
                        . '<small>' . e(getEnumValue("bond_payment_type", $row->payment_type)) . '</small></div>';
                })
                ->editColumn('value', function ($row) {
                    return '<strong class="bond-amount" dir="ltr">' . number_format((float) $row->value, 2) . '</strong>';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_confirm
                        ? '<span class="badge bg-success-subtle text-success bond-status"><i class="fa fa-check-circle"></i> معتمد</span>'
                        : '<span class="badge bg-warning-subtle text-warning bond-status"><i class="fa fa-clock"></i> بانتظار الاعتماد</span>';
                })
                ->addColumn('constraint', function ($row) {
                    if (!$row->constraint_id) {
                        return '<span class="text-muted">غير منشأ</span>';
                    }

                    $url = route('finance.add-constraint.show', ['add_constraint' => $row->constraint_id]);
                    return '<a class="btn btn-sm btn-outline-primary bond-constraint-link" href="' . e($url) . '" target="_blank" rel="noopener">'
                        . '<i class="fa fa-book"></i> القيد #' . e($row->constraint_id) . '</a>';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '<div class="bond-actions">';
                    $buttons .= printButton(route("finance.bonds-print.show", $row->id) . '?type=qabd', null, 'finance_bonds_export') . ' ';
                    $buttons .= '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal"'
                        . ' model-route="' . route('finance.bonds-qabd.show', $row->id) . '" model-title="تفاصيل سند القبض" title="التفاصيل">'
                        . '<i class="fa fa-eye"></i></button> ';
                    $buttons .= editButton(route($this->mainRoute . ".edit", $row->id), $row->name) . " ";
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id)) . " ";
                    if ($row->is_confirm == 0) {
                        $buttons .= confirmButton(route("finance.bonds-confirm.store"), $row->id);
                    }
                    return $buttons . '</div>';
                })
                ->rawColumns(['bond_num', 'date', 'credit_account', 'debit_account', 'value', 'status', 'constraint', 'actions'])
                ->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.qabd_bonds");
        $data["addButtonText"] = __("finance::finance.qabd_bond");
        $data["banks_boxes"] = $accountService->getByTypes([AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value]);
        $data["payment_types"] = BondPaymentTypes::cases();
        $data["filterOptionsRoute"] = route($this->mainRoute . '.filter-options');
        $data["archiveSummary"] = $service->getBondArchiveSummary(BondTypes::Qabd->value);
        return view($this->folderPath . '.index', $data)->with('results', $service->getReportBondQabd());
    }

    public function export(Request $request, ObjService $service, BondListExportService $exporter, string $format)
    {
        return $exporter->download($request, $service, BondTypes::Qabd, $format);
    }

    public function filterOptions(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:account,cost_center'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);

        if ($validated['type'] === 'account') {
            $query = Account::query()->select(['id', 'name', 'code'])
                ->whereIn('id', Bond::query()->select('credit_account_id')
                    ->where('type', BondTypes::Qabd->value)->where('general_type', 0));
            if ($search !== '') {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            }
            $paginator = $query->orderBy('name')->paginate(30, ['*'], 'page', $page);
            $results = collect($paginator->items())->map(fn ($account) => [
                'id' => $account->id,
                'text' => trim(($account->code ? $account->code . ' - ' : '') . $account->name),
            ]);
        } else {
            $query = CostCenter::query()->select(['id', 'title', 'code'])
                ->whereIn('id', Bond::query()->select('cost_center_id')
                    ->where('type', BondTypes::Qabd->value)->where('general_type', 0)->whereNotNull('cost_center_id'));
            if ($search !== '') {
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            }
            $paginator = $query->orderBy('title')->paginate(30, ['*'], 'page', $page);
            $results = collect($paginator->items())->map(fn ($costCenter) => [
                'id' => $costCenter->id,
                'text' => trim(($costCenter->code ? $costCenter->code . ' - ' : '') . $costCenter->title),
            ]);
        }

        return response()->json([
            'results' => $results->values(),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "lastId" => $service->lastCode(BondTypes::Qabd->value),
                'storeRoute' => route($this->mainRoute . ".store"),
                "tax_rate_revenue" => $service->getTaxRate(ConfirmSettingTypes::TaxRateRevenue->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterQabdBond->value),
                "tax_settings" => $service->getTaxSetting()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, ObjService $service, YearService $yearService)
    {
        $dataInsert = $request->validated();
        if (!$yearService->checkDateRange($dataInsert['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $data = $service->storeQabdBond($dataInsert);
        $service->checkIsConfirmSetting($data, ConfirmSettingTypes::QabdBond->value);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ObjService $service, $id)
    {
        if ($request->ajax()) {
            $obj = $service->find($id)->loadMissing(['debitAccount', 'creditAccount', 'costCenter', 'createdBy', 'check', 'bondTax', 'constraint']);
            $returnHTML = view($this->folderPath . ".show")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj,
                "edit_payment_type_view" => $service->getEditPaymentTypeView($obj->payment_type, "qabd"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function downloadAttachment(ObjService $service, $bond, string $attachment)
    {
        abort_unless(in_array($attachment, ['file', 'transfer_file'], true), 404);

        $obj = $service->find($bond);
        $path = $obj->{$attachment};

        abort_if(!is_string($path) || $path === '' || !Storage::disk('public')->exists($path), 404);

        $filename = basename($path);

        return Storage::disk('public')->download($path, $filename);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $obj = $service->find($id);
            $returnHTML = view($this->folderPath . ".edit")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj,
                "edit_payment_type_view" => $service->getEditPaymentTypeView($obj->payment_type, "qabd"),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "tax_rate_revenue" => $service->getTaxRate(ConfirmSettingTypes::TaxRateRevenue->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterQabdBond->value),
                "tax_settings" => $service->getTaxSetting()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, $id, ObjService $service, YearService $yearService)
    {
        $dataInsert = $request->validated();
        if (!$yearService->checkDateRange($dataInsert['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $data = $service->updateBond($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $service->deleteBond($id);
        return jsonSuccess();
    }

    private function extractRequestNumber(?string $statement): string
    {
        $statement = trim((string) $statement);
        if ($statement === '') {
            return '-';
        }

        $patterns = [
            '/رقم\s+الطلب\s*[:#-]?\s*(\d+)/u',
            '/رقم\s+العقد\s*[:#-]?\s*(\d+)/u',
            '/العقد\s*#\s*(\d+)/u',
            '/العقد\s+(\d+)/u',
            '/الطلب\s*#\s*(\d+)/u',
            '/الطلب\s+(\d+)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $statement, $matches)) {
                return $matches[1];
            }
        }

        return '-';
    }

    private function formatAccountSummary($row): string
    {
        $accountCode = e((string) optional($row->creditAccount)->code);
        $accountName = e((string) (optional($row->creditAccount)->name ?: '-'));
        $primaryText = trim(($accountCode !== '' ? $accountCode . ' :: ' : '') . $accountName);
        $secondaryText = e((string) (optional($row->debitAccount)->name ?: '-'));

        return '<div class="receipt-account">
                    <strong>' . $primaryText . '</strong>
                    <small>حساب التحصيل: ' . $secondaryText . '</small>
                </div>';
    }

    private function formatCurrency($value): string
    {
        $formattedValue = number_format((float) $value, 2, '.', ',');
        $formattedValue = rtrim(rtrim($formattedValue, '0'), '.');

        return $formattedValue . ' ر.س';
    }
}
