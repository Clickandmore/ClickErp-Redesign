<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Http\Middleware\CheckActiveYear;
use Nami\Finance\Http\Requests\AccountTransferRequest as ObjRequest;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\AccountTransferService as ObjService;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\CostCenterService;
use Nami\Finance\Services\YearService;
use Yajra\DataTables\DataTables;

class AccountTransferController extends Controller
{
    protected string $folderPath = "finance::finance.account_transfer";
    protected string $mainRoute = "finance.account-transfer";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create', 'edit']);
    }

    public function index(Request $request, ObjService $service, CostCenterService $costCenterService, AccountService $accountService , BondService  $bondService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            $filterDataTable = $bondService->filterBonds($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->addIndexColumn()
                ->editColumn('debit_account', function ($row) {
                    return $row->debitAccount->name;
                })
                ->editColumn('credit_account', function ($row) {
                    return $row->creditAccount->name;
                })
                ->editColumn('date', function ($row) {
                    return '<span title="' . $row->statement . '">' . $row->date . '</span> ';
                })
                ->addColumn('actions', function ($row) {

                    $printButton = '<a href="' . route("finance.account-transfer-print.show", $row->id) . '" target="_blank" class="btn btn-success"><i class="fa fa-print"></i></a>';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $destroyButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $ConfirmButton = '';
                    $showButton = showButton(route($this->mainRoute . ".show", $row->id), $row->name, 'eye', 'dark');
                    if ($row->is_confirm == 0) {
                        $ConfirmButton = confirmButton(route("finance.account-transfer-confirm.show", $row->id), $row->id);
                    }
                    return $printButton . " " . $editButton . " " . $destroyButton . " " . $ConfirmButton . " " . $showButton;
                })
                ->setRowAttr([
                    'data-tooltip' => function ($row) {
                        return $row->statement;
                    },
                ])
                ->rawColumns(["debit_account", "credit_account", "date", 'actions'])->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "bladeTitle" => __("finance::finance.account_transfers"),
            "addButtonText" => __("finance::finance.account_transfers"),
            'request' => $request,
            'cost_centers' => $costCenterService->get(),
            'banks_boxes' => $accountService->getByTypes([AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value]),

        ]);
    }

    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".create")->with([
                    'accounts' => $service->getBoxsAndBanks(),
                    'costCenters' => $service->getCostCenters(),
                    "storeRoute" => route($this->mainRoute . ".store"),
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
        $storeTransfer = $service->storeTransfer($data, $getActiveYear);
        $service->storeFiles($storeTransfer, $data);
        $service->checkIsConfirmSetting($storeTransfer, ConfirmSettingTypes::AccountTransfer->value);
        return jsonSuccess($storeTransfer);
    }

    public function show($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".show")->with([
                    "obj" => $service->find($id),
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
                    'accounts' => $service->getBoxsAndBanks(),
                    'costCenters' => $service->getCostCenters(),
                    "updateRoute" => route($this->mainRoute . ".update", $id),
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
        $updateTransfer = $service->updateTransfer($id, $data, $getActiveYear);
        $transfer = $service->find($id);
        $service->updateTransferConstraint($transfer);
        $service->storeFiles($transfer, $data);
        return jsonSuccess($updateTransfer);
    }

    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
