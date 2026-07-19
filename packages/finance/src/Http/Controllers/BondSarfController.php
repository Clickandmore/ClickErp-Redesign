<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Http\Middleware\CheckActiveYear;
use Nami\Finance\Http\Requests\Bond\StoreBondRequest as StoreRequest;
use Nami\Finance\Http\Requests\Bond\UpdateBondRequest as UpdateRequest;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\CostCenter;
use Yajra\DataTables\DataTables;

class BondSarfController extends Controller
{
    public $folderPath = "finance::finance.bond.sarf";
    public $mainRoute = "finance.bonds-sarf";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create', 'edit']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, BondService $service, AccountService $accountService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTableData(BondTypes::Sarf->value);
            $filterDataTable = $service->filterBonds($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->order(function ($query) {
                    $query->reorder()
                        ->orderByDesc('date')
                        ->orderByRaw("CASE WHEN TRIM(bond_book_num) REGEXP '^[0-9]+$' THEN CAST(bond_book_num AS UNSIGNED) ELSE code END DESC")
                        ->orderByDesc('code')
                        ->orderByDesc('id');
                })
                ->addIndexColumn()
                ->editColumn('bond_num', function ($row) {
                    $printUrl = route('finance.bonds-print.show', ['bonds_print' => $row->id, 'type' => 'sarf']);
                    $bookNumber = filled(trim((string) $row->bond_book_num))
                        ? '<small>دفتر: ' . e($row->bond_book_num) . '</small>'
                        : '';

                    return '<div class="sarf-voucher-number">'
                        . '<a href="' . e($printUrl) . '" target="_blank" rel="noopener">#' . e($row->code) . '</a>'
                        . $bookNumber
                        . '</div>';
                })
                ->editColumn('date', function ($row) {
                    return '<div class="sarf-date"><strong dir="ltr">' . e($row->date) . '</strong><small>تاريخ السند</small></div>';
                })
                ->addColumn('debit_account', function ($row) {
                    return '<div class="sarf-account"><strong>' . e(optional($row->debitAccount)->name ?: '-') . '</strong>'
                        . '<small title="' . e($row->statement) . '">' . e($row->statement ?: 'بدون بيان') . '</small></div>';
                })
                ->addColumn('credit_account', function ($row) {
                    return '<div class="sarf-account"><strong>' . e(optional($row->creditAccount)->name ?: '-') . '</strong>'
                        . '<small>' . e(getEnumValue("bond_payment_type", $row->payment_type)) . '</small></div>';
                })
                ->editColumn('value', function ($row) {
                    return '<strong class="sarf-amount" dir="ltr">' . number_format((float) $row->value, 2) . '</strong>';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_confirm
                        ? '<span class="badge bg-success-subtle text-success sarf-status"><i class="fa fa-check-circle"></i> معتمد</span>'
                        : '<span class="badge bg-warning-subtle text-warning sarf-status"><i class="fa fa-clock"></i> بانتظار الاعتماد</span>';
                })
                ->addColumn('constraint', function ($row) {
                    if (!$row->constraint_id) {
                        return '<span class="text-muted">غير منشأ</span>';
                    }

                    $url = route('finance.add-constraint.show', ['add_constraint' => $row->constraint_id]);
                    return '<a class="btn btn-sm btn-outline-primary sarf-constraint-link" href="' . e($url) . '" target="_blank" rel="noopener">'
                        . '<i class="fa fa-book"></i> القيد #' . e($row->constraint_id) . '</a>';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '<div class="sarf-actions">';
                    $buttons .= printButton(route("finance.bonds-print.show", $row->id) . '?type=sarf', null, 'finance.bonds-print-read') . ' ';
                    $buttons .= '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal"'
                        . ' model-route="' . route('finance.bonds-sarf.show', $row->id) . '" model-title="تفاصيل سند الصرف" title="التفاصيل">'
                        . '<i class="fa fa-eye"></i></button> ';
                    $buttons .= editButton(route($this->mainRoute . ".edit", $row->id), $row->name) . " ";
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id)) . " ";
                    if ($row->is_confirm == 0) {
                        $buttons .= confirmButton(route("finance.bonds-confirm.store"), $row->id);
                    }

                    return $buttons . '</div>';
                })
                ->rawColumns(["bond_num", "date", "debit_account", "credit_account", "value", "status", "constraint", 'actions'])
                ->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.sarf_bonds");
        $data["addButtonText"] = __("finance::finance.sarf_bond");
        $data["banks_boxes"] = $accountService->getByTypes([AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value]);
        $data["payment_types"] = BondPaymentTypes::cases();
        $data["filterOptionsRoute"] = route($this->mainRoute . '.filter-options');
        return view($this->folderPath . '.index', $data)->with('results', $service->getReportBondSarf());
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
            $query = Account::query()
                ->select(['id', 'name', 'code'])
                ->whereIn('id', Bond::query()
                    ->select('debit_account_id')
                    ->where('type', BondTypes::Sarf->value)
                    ->where('general_type', 0));

            if ($search !== '') {
                $query->where(function ($accountQuery) use ($search) {
                    $accountQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $paginator = $query->orderBy('name')->paginate(30, ['*'], 'page', $page);
            $results = collect($paginator->items())->map(fn ($account) => [
                'id' => $account->id,
                'text' => trim(($account->code ? $account->code . ' - ' : '') . $account->name),
            ]);
        } else {
            $query = CostCenter::query()
                ->select(['id', 'title', 'code'])
                ->whereIn('id', Bond::query()
                    ->select('cost_center_id')
                    ->where('type', BondTypes::Sarf->value)
                    ->where('general_type', 0)
                    ->whereNotNull('cost_center_id'));

            if ($search !== '') {
                $query->where(function ($costCenterQuery) use ($search) {
                    $costCenterQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
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
    public function create(Request $request, BondService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "lastId" => $service->lastCode(BondTypes::Sarf->value),
                'storeRoute' => route($this->mainRoute . ".store"),
                "tax_rate_expenditure" => $service->getTaxRate(ConfirmSettingTypes::TaxRateExpenditure->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterSarfBond->value),
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
    public function store(StoreRequest $request, BondService $service, YearService $yearService)
    {
        $dataInsert = $request->validated();
        if (!$yearService->checkDateRange($dataInsert['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $data = $service->storeSarfBond($dataInsert);
        $service->checkIsConfirmSetting($data, ConfirmSettingTypes::SarfBond->value);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, BondService $service, $id)
    {
        if ($request->ajax()) {
            $obj = $service->find($id)->loadMissing([
                'debitAccount', 'creditAccount', 'costCenter', 'createdBy', 'bondTax', 'constraint', 'check',
            ]);
            $returnHTML = view($this->folderPath . ".show")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj,
                "edit_payment_type_view" => $service->getEditPaymentTypeView($obj->payment_type, "sarf"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, BondService $service)
    {
        if ($request->ajax()) {
            $obj = $service->find($id);
            $returnHTML = view($this->folderPath . ".edit")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj,
                "edit_payment_type_view" => $service->getEditPaymentTypeView($obj->payment_type, "sarf"),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "tax_rate_expenditure" => $service->getTaxRate(ConfirmSettingTypes::TaxRateExpenditure->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterSarfBond->value),
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
    public function update(UpdateRequest $request, $id, BondService $service, YearService $yearService)
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
    public function destroy($id, BondService $service)
    {
        $service->deleteBond($id);
        return jsonSuccess();
    }

    public function print($id, BondService $service)
    {
        $data['bond'] = $service->find($id);
        return view($this->folderPath . '.print', $data);
    }
}
