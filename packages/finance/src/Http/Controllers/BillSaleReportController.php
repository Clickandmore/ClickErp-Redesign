<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\BillSalesService as ObjService;

class BillSaleReportController extends Controller
{
    public string $folderPath = "finance::finance.bill_sales_report";
    public $postData = ['code', 'client_id', 'from_date', 'to_date', 'date', 'notes', 'total_item', 'payment_type', 'main_stock_id'];
    public string $mainRoute = "finance.bill-sales-report";

public function index(Request $request, ObjService $service)
{
    $data['dataTableRoute'] = route($this->mainRoute . ".index");
    $data["bladeTitle"] = __("finance::finance.bill-sales-report");
    $data["addButtonText"] = __("finance::finance.bill-sales-report");
    $data["clients"] = $service->getClients();
    $data["items"] = $service->getItems();
    $data["modalType"] = "modal-xl";

    // get base query
    $query = $service->getDataTableOfBill($request);

    // apply filters
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('date', [$request->from_date, $request->to_date]);
    } elseif ($request->filled('from_date')) {
        $query->where('date', '>=', $request->from_date);
    } elseif ($request->filled('to_date')) {
        $query->where('date', '<=', $request->to_date);
    }

    if ($request->filled('code')) {
        $query->where('code', $request->code);
    }

    if ($request->filled('client_id')) {
        $query->where('client_id', $request->client_id);
    }

    if ($request->filled('item_id')) {
        $query->whereHas('items', function ($q) use ($request) {
            $q->where('item_id', $request->item_id);
        });
    }

    $data['result'] = $query->get();

    if ($request->ajax()) {
        $returnHTML = view($this->folderPath . ".result", $data)->render();
        return jsonSuccess(["html" => $returnHTML]);
    }

    // لو مش AJAX أول مرة: رجع الفيو بالبيانات
    return view($this->folderPath . '.index', $data);
}

}
