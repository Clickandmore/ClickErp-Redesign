<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConversionStatus;
use Nami\Finance\Services\ConversionService as ObjService;
use Yajra\DataTables\DataTables;

class AddConversionOrderController extends Controller
{
    public string $folderPath = "finance::finance.add_conversion_orders";
    public string $mainRoute = "finance.add-conversion-orders";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getConversionWithStatus([ConversionStatus::MakeSarf->value]);
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $fromDate = $request->from_date;
                $toDate = $request->to_date;
                $dataTable = $dataTable->whereBetween('date', [$fromDate, $toDate]);
            }

            if ($request->filled('code')) {
                $code = $request->code;
                $dataTable = $dataTable->where('code', $code);
            }

            if ($request->filled('from_stock_id')) {
                $stock = $request->from_stock_id;
                $dataTable = $dataTable->where('from_stock_id', $stock);
            }

            if ($request->filled('to_stock_id')) {
                $stock = $request->to_stock_id;
                $dataTable = $dataTable->where('to_stock_id', $stock);
            }

            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('conversionItems', function ($query) use ($item) {
                    $query->where('item_id', $item);
                });
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('from_stock_id', function ($row) {
                    return $row->fromStock->name ?? "";
                })
                ->editColumn('to_stock_id', function ($row) {
                    return $row->toStock->name ?? "";
                })
                ->addColumn('conversion_details', function ($row) {
                    $conversionDetailsButton = showButton(route("finance.conversions.show", $row->id), __("finance.conversion_details"));
                    return $conversionDetailsButton;
                })

                ->addColumn('sarf_movement_details', function ($row) {
                    $sarfMovementDetailsButton = showButton(route("finance.sarf-movement.show", $row->stock_movement_id), __("finance.sarf-movement-details"), "eye", "danger");
                    return $sarfMovementDetailsButton;
                })

                ->addColumn('transfer', function ($row) {
                    $transferButton = '<button type="button" class="btn btn-outline-warning transferButton"
                                            model-route="' . route("finance.transfer-to-stock-movement.show", $row->id) . '"
                                            model-title="اصدار اذن اضافة"
                                            title="اصدار اذن اضافة">
                                        <i class="fa fa-reply"></i>
                                    </button>';
                    return $transferButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.add-conversion-orders");
        $data["addButtonText"] = __("finance::finance.add-conversion-orders");
        $data["stocks"] = $service->getStocks();
        $data["items"] = $service->getItems();
        $data["modalType"] = "modal-xl";
        return view($this->folderPath . '.index', $data);
    }

}
