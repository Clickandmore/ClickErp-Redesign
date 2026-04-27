<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Http\Requests\ConstraintRequest as ObjRequest;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use Yajra\DataTables\DataTables;

class ArithmeticTransactionsController extends Controller
{
    protected string $folderPath = "finance.arithmetic_transactions";
    protected string $mainRoute = "finance.arithmetic-transactions";

    public function __construct()
    {
        $this->middleware('checkActiveYear')->only(['create','edit']);
    }
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return DataTables::of($service->getIndexDataAll())
                ->addIndexColumn()
                ->editColumn('type', function ($row) {
                   foreach (ConstraintTypiesEnum::cases() as $case){
                       if($case->value == $row->type){
                           return $case->lang();
                       }
                   }
                })
                ->addColumn('debitors_total', function ($row) use ($service) {
                    return $service->calculateTotal($row->constraintDetails, 'debtor');
                })
                ->addColumn('creditors_total', function ($row) use ($service) {
                    return $service->calculateTotal($row->constraintDetails, 'creditor');
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
               ->editColumn('type', function ($row) {
                    return $row->type->lang();
                })
                ->editColumn('date', function ($row) {
                    return '<span title="'.$row->statement.'">' . $row->date . '</span> ';
                })
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('statement', function ($row) {
                    return $row->statement;
                })
                ->filter(function ($instance) use ($request) {
                    if ($request->get('type')) {
                        $instance->where('type', $request->get('type'));
                    }
                     if ($request->get('from_date')) {
                        $instance->where('date','>=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('date','<=', $request->get('to_date'));
                    }
                })
                ->addColumn('actions', function ($row) use ($request) {

                    $showButton = editButton(route($row->type->route_show(), $row->show_id), $row->name, 'eye',true);

                    return $showButton ;
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "bladeTitle" => __("finance.arithmetic-transactions"),
            "addButtonText" => __("finance.arithmetic-transaction"),
            'modalType' => 'modal-fullscreen',
            'request' => $request,
            'types' => $service->getTypes(),
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
}
