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
use Nami\Finance\Services\BondService as ObjService;
use Nami\Finance\Services\CostCenterService;
use Nami\Finance\Services\YearService;
use Yajra\DataTables\DataTables;

class GeneralBondQabdController extends Controller
{
    public $folderPath = "finance::finance.general_bond.qabd";
    public $mainRoute = "finance.general-bonds-qabd";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create','edit']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, ObjService $service , CostCenterService $costCenterService, AccountService $accountService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTableQabdData(BondTypes::Qabd->value, 1);
            $filterDataTable = $service->filterBonds($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->addIndexColumn()
                ->editColumn('bond_num', function ($row) {
                    return $row->code;
                })
                ->editColumn('date', function ($row) {
                    return  '<span  data-bs-toggle="tooltip" data-bs-placement="top" title="'.$row->statement.'">'.$row->date.'</span>';
                })
                ->editColumn('debit_account', function ($row) {
                    return $row->debitAccount->name;
                })
                ->editColumn('credit_account', function ($row) {
                    return $row->creditAccount->name;
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
                ->editColumn('payment_type_name', function ($row) {
                    return getEnumValue("bond_payment_type", $row->payment_type);
                })
                ->addColumn('actions', function ($row) {
                    $buttons = printButton(route("finance.bonds-print.show", $row->id) . '?type=qabd', null, 'finance_bonds_export') . ' ';
                    $buttons .= editButton(route($this->mainRoute . ".edit", $row->id), $row->name) . " " ;
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id))." " ;
                    $buttons .= '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                                model-route="' . route('finance.general-bonds-qabd.show',$row->id). '" model-title="تفاصيل سند القبض">
                                <i class="fa fa-eye"></i></button>'.' ';
                    if($row->is_confirm == 0){
                        $buttons .= confirmButton( route("finance.bonds-confirm.store"),$row->id);
                    }
                    return $buttons;
                })
                ->rawColumns(["bond_num", "date", "debit_account", "credit_account", "value", "payment_type_name", 'actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.qabd_bonds");
        $data["addButtonText"] = __("finance::finance.qabd_bond");
        $data["accounts"] = $accountService->get();
        $data["cost_centers"] = $costCenterService->get();
        $data["banks_boxes"] = $accountService->getByTypes([AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value]);
        $data["payment_types"] = BondPaymentTypes::cases();
        return view($this->folderPath . '.index', $data)->with('results',$service->getReportBondQabd());
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
                "bond_qabd_account" => $service->getTaxRate(ConfirmSettingTypes::BondQabdAccount->value),
                "tax_rate_revenue" => $service->getTaxRate(ConfirmSettingTypes::TaxRateRevenue->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterGeneralQabdBond->value),
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
    public function show(Request $request, ObjService $service , $id)
    {
        if ($request->ajax()) {
            $obj = $service->find($id) ;
            $returnHTML = view($this->folderPath . ".show")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj ,
                "edit_payment_type_view"=>$service->getEditPaymentTypeView($obj->payment_type,"sarf"),
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
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $obj = $service->find($id) ;
            $returnHTML = view($this->folderPath . ".edit")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "costCenters" => $service->getCostCenters(),
                "obj" => $obj ,
                "edit_payment_type_view"=>$service->getEditPaymentTypeView($obj->payment_type,"qabd"),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "bond_qabd_account" => $service->getTaxRate(ConfirmSettingTypes::BondQabdAccount->value),
                "tax_rate_revenue" => $service->getTaxRate(ConfirmSettingTypes::TaxRateRevenue->value),
                "cost_center_required" => $service->getTaxRate(ConfirmSettingTypes::CostCenterGeneralQabdBond->value),
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
}
