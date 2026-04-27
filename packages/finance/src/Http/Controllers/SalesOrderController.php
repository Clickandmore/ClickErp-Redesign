<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\SalesOrderRequest as ObjRequest;
use Nami\Finance\Services\AdditionItemService;
use Nami\Finance\Services\SalesOrderService as ObjService;
use Yajra\DataTables\DataTables;

class SalesOrderController extends Controller
{
    public string $folderPath = "finance::finance.sales_order";
    public string $mainRoute = "finance.sales-order";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $fromDate = $request->from_date;
                $toDate = $request->to_date;
                $dataTable = $dataTable->whereBetween('date', [$fromDate, $toDate]);
            }

            if ($request->filled('code')) {
                $code = $request->code;
                $dataTable = $dataTable->where('code', $code);
            }

            if ($request->filled('client_id')) {
                $stock = $request->client_id;
                $dataTable = $dataTable->where('client_id', $stock);
            }


            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('salesOrderItems', function ($query) use ($item) {
                    $query->where('item_id', $item);
                });
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('client_id', function ($row) {
                    return $row->client->name ?? "";
                })
                ->editColumn('final_total', function ($row) {
                    return number_format($row->final_total + @$row->additionItems->sum('final_price'), 2);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-dark">
                                        <i class="fa fa-cogs"></i> الإجراءات
                                    </button>
                                    <button type="button" class="btn btn-sm btn-dark dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark border-0 shadow py-3 px-2" style="min-width: 220px;">';

                    if ($row->status == "new") {
                        $actions .= '<li>
                                        <button type="button" class="dropdown-item py-2 rounded d-flex align-items-center gap-2 editButton"
                                            data-bs-toggle="modal"
                                            data-bs-target="#createModal"
                                            model-route="' . route($this->mainRoute . ".edit", $row->id) . '"
                                            model-title="' . $row->name . '">
                                            <i class="fa fa-edit text-primary"></i> تعديل
                                        </button>
                                    </li>';
                        $actions .= '<li>
                                        <a href="' . route("finance.sales-order-print-withTax", ['id' => $row->id, 'withTax' => 0]) . '"
                                           class="dropdown-item py-2 rounded d-flex align-items-center gap-2"
                                           target="_blank">
                                            <i class="fa fa-print text-success"></i> طباعة <span class="text-muted">(بدون الضريبة)</span>
                                        </a>
                                    </li>';
                        $actions .= '<li>
                                        <a href="' . route("finance.sales-order-print-withTax", ['id' => $row->id, 'withTax' => 1]) . '"
                                           class="dropdown-item py-2 rounded d-flex align-items-center gap-2"
                                           target="_blank">
                                            <i class="fa fa-file-invoice-dollar text-primary"></i> طباعة <span class="text-muted">(مع الضريبة)</span>
                                        </a>
                                    </li>';
                        $actions .= '<li>
                                        <button type="button" class="dropdown-item py-2 rounded text-danger d-flex align-items-center gap-2 deleteButton"
                                            delete-route="' . route($this->mainRoute . ".destroy", $row->id) . '">
                                            <i class="fa fa-trash"></i> حذف
                                        </button>
                                    </li>';
                        if (count($row->salesOrderItems)) {
                            $actions .= '<li>
                                            <a href="' . route('finance.transfer-sales-order.edit', $row->id) . '"
                                               class="dropdown-item py-2 rounded d-flex align-items-center gap-2 transferButton"
                                               title="تحويل الى فاتورة مبيعات">
                                               <i class="fa fa-file-invoice text-warning"></i> تحويل إلى فاتورة
                                            </a>
                                        </li>';
                        } else {
                            $actions .= '<li>
                                            <button type="button" class="dropdown-item py-2 rounded disabled d-flex align-items-center gap-2"
                                                    style="background-color:#9a6e18;border-color:#9a6e18"
                                                    title="لا يوجد منتجات مكوده في عرض السعر !">
                                                <i class="fa fa-ban text-muted"></i> لا يوجد منتجات مكوده
                                            </button>
                                        </li>';
                        }
                    }
                    $actions .= '<li>
                                    <button type="button" class="dropdown-item py-2 rounded d-flex align-items-center gap-2 editButton"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createModal"
                                        model-route="' . route($this->mainRoute . ".show", $row->id) . '"
                                        model-title="' . $row->name . '">
                                        <i class="fa fa-eye text-info"></i> التفاصيل
                                    </button>
                                </li>';
                    $actions .= '</ul></div>';
                    return $actions;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.sales-order");
        $data["addButtonText"] = __("finance::finance.sales-order");
        $data["clients"] = $service->getClients();
        $data["items"] = $service->getItems();
        $data["modalType"] = "modal-fullscreen";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'clients' => $service->getClients(),
                'code' => $service->getCode(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service, AdditionItemService $additionItemService)
    {
        $dataInsert = $request->validated();
        $obj = $service->storeSalesOrder($dataInsert);
        $additionItemService->storeAdditionItems($dataInsert, $obj->id);
        return jsonSuccess($obj);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
                'clients' => $service->getClients(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                'clients' => $service->getClients(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service, AdditionItemService $additionItemService)
    {
        $dataInsert = $request->validated();
        $data = $service->updateSalesOrder($id, $dataInsert);
        $additionItemService->storeAdditionItems($dataInsert, $id);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();

    }
}
