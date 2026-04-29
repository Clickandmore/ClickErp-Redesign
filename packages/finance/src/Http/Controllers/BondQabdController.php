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
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\BondService as ObjService;
use Nami\Finance\Services\CostCenterService;
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
    public function index(Request $request, ObjService $service, CostCenterService $costCenterService, AccountService $accountService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTableQabdData(BondTypes::Qabd->value);
            $filterDataTable = $service->filterBonds($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->addColumn('identifier', function ($row) {
                    return '<a href="' . route('finance.bonds-print.show', ['bonds_print' => $row->id, 'type' => 'qabd']) . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">#' . $row->id . '</a>';
                })
                ->addColumn('request_number', function ($row) {
                    return $this->extractRequestNumber($row->statement);
                })
                ->addColumn('account_summary', function ($row) {
                    return $this->formatAccountSummary($row);
                })
                ->addColumn('required_amount', function ($row) {
                    return $this->formatCurrency($row->value);
                })
                ->addColumn('received_amount', function ($row) {
                    return $this->formatCurrency($row->value);
                })
                ->addColumn('financial_movement', function ($row) {
                    return $this->formatCurrency($row->value);
                })
                ->addColumn('created_by_name', function ($row) {
                    return e(optional($row->createdBy)->name ?: '-');
                })
                ->editColumn('date', function ($row) {
                    return '<div class="receipt-date" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($row->statement) . '">
                                <strong dir="ltr">' . e($row->date) . '</strong>
                                <small>' . e(getEnumValue("bond_payment_type", $row->payment_type)) . '</small>
                            </div>';
                })
                ->addColumn('print_action', function ($row) {
                    return printButton(route("finance.bonds-print.show", $row->id) . '?type=qabd', ' طباعة', 'finance.bonds-print-read');
                })
                ->addColumn('actions', function ($row) {
                    $buttons = editButton(route($this->mainRoute . ".edit", $row->id), $row->name) . " ";
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id)) . " ";
                    $buttons .= '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                                model-route="' . route('finance.bonds-qabd.show', $row->id) . '" model-title="تفاصيل سند القبض">
                                <i class="fa fa-eye"></i></button>' . ' ';
                    if ($row->is_confirm == 0) {
                        $buttons .= confirmButton(route("finance.bonds-confirm.store"), $row->id);
                    }
                    return $buttons;
                })
                ->rawColumns(['identifier', 'account_summary', 'date', 'print_action', 'actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.qabd_bonds");
        $data["addButtonText"] = __("finance::finance.qabd_bond");
        $data["accounts"] = $accountService->get();
        $data["cost_centers"] = $costCenterService->get();
        $data["banks_boxes"] = $accountService->getByTypes([AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value]);
        $data["payment_types"] = BondPaymentTypes::cases();
        return view($this->folderPath . '.index', $data)->with('results', $service->getReportBondQabd());
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
            $obj = $service->find($id)->loadMissing(['debitAccount', 'creditAccount', 'costCenter', 'createdBy', 'check', 'bondTax']);
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
