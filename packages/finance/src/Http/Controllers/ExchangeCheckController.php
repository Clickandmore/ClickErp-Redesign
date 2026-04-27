<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondCheckStatusEnum;
use Nami\Finance\Http\Requests\ExchangeCheckRequest as ObjRequest;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\CostCenterService;
use Nami\Finance\Services\ExchangeCheckService as ObjService;
use Yajra\DataTables\DataTables;

class ExchangeCheckController extends Controller
{
    public string $folderPath = "finance::finance.exchange-checks";
    public string $mainRoute = "finance.exchange-checks";

    public function index(Request $request, ObjService $service, CostCenterService $costCenterService, AccountService $accountService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getExchangeCheckDataTable();
            $filterDataTable = $service->filterChecks($request, $dataTable);
            return DataTables::of($filterDataTable)
                ->addIndexColumn()
                ->addColumn('con_number', function ($row) {
                    return @$row->bond->constraint->code;
                })
                ->addColumn('con_date', function ($row) {
                    return @$row->bond->constraint->date;
                })
                ->editColumn('debit_account_id', function ($row) {
                    return @$row->bond->debitAccount->name;
                })
                ->editColumn('credit_account_id', function ($row) {
                    return @$row->bond->creditAccount->name;
                })
                ->filter(function ($instance) use ($request) {
                    if ($request->get('status')) {
                        if ($request->status == 'today') {
                            $instance->where('check_date', '<=', date('Y-m-d'));
                        } else {
                            $instance->where('check_date', '>', date('Y-m-d'));
                        }
                    }
                })
//                ->editColumn('created_by_id', function ($row) {
//                    return @$row->admin->name;
//                })
//                ->addColumn('statement', function ($row) {
//                    $limitedStatement = Str::limit($row->bond->statement, 30);
//                    return "<span title='{$row->bond->statement}'>{$limitedStatement}</span>";
//                })
                ->addColumn('actions', function ($row) use ($request) {
                    $editButton = '';
                    $refuseButton = '';
                    $showBtn = '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal" model-route="' . route('finance.bonds-sarf.show', $row->bond->id) . '" model-title="تفاصيل سند الصرف"><i class="fa fa-eye"></i></button>';

                    if ($row->status == BondCheckStatusEnum::NewSarf->value && $request->get('status') == 'today') {
                        $editButton = '<button type="button" class="btn btn-success editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                                         model-route="' . route($this->mainRoute . ".show", $row->id) . '" model-title="' . __('finance.collect') . '">
                                         ' . __('finance.collect') . ' <i class="fa fa-check"></i>
                                        </button>';
                    }
                    if ($row->status == BondCheckStatusEnum::NewSarf->value && $request->get('status') == 'today') {
                        $refuseButton = '<button type="button" model-route="' . route($this->mainRoute . ".edit", $row->id) . '" model-title="' . __('finance.refuse') . '" data-bs-toggle="modal" data-bs-target="#createModal" class="btn btn-danger editButton"> ' . __('finance.refuse') . ' <i class="fa fa-times"></i></button>';
                    }
                    return $showBtn . ' ' . $editButton . ' ' . $refuseButton;
                })
                ->escapeColumns([])
                ->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.exchange-checks");
        $data["status"] = $request->status ?? 'today';
        $data["cost_centers"] = $costCenterService->get();
        $data["accounts"] = $accountService->get();
        $data["banks"] = $accountService->getByType(AccountTypesEnum::Bank->value);
        return view($this->folderPath . '.index', $data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request, ObjService $service)
    {
        $service->acceptExchangeCheck($request->id);
        return jsonSuccess();
    }


    public function show(string $id, request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".accept")->with([
                "obj" => $service->find($id),
                'route' => route($this->mainRoute . ".store"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function edit(string $id, ObjService $service, Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".refuse")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(ObjRequest $request, string $id, ObjService $service)
    {
        $data = $request->validated();
        $service->RefuseExchangeCheck($id, $data);
        return jsonSuccess();
    }


    public function destroy(string $id)
    {
        //
    }
}
