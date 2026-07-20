<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Invoice;
use Illuminate\Http\Request;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Http\Requests\SalesOrderRequest as ObjRequest;
use Nami\Finance\Http\Requests\SalesRequest;
use Nami\Finance\Services\BillSalesService as ObjService;
use Nami\Finance\Services\SalesOrderService;
use PDF;
use Yajra\DataTables\DataTables;

class BillSalesController extends Controller
{
    public string $folderPath = "finance::finance.bill_sales";
    public $postData = ['code', 'client_id', 'date', 'notes', 'total_item', 'payment_type', 'main_stock_id'];
    public string $mainRoute = "finance.bill-sales";


    public function index(Request $request, ObjService $service)
    {
        $dataTable = $this->filteredBillSalesQuery($request, $service);
        if ($request->ajax()) {
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('code', function ($row) {
                    $invoice = $row->sourceInvoice;
                    if (!$invoice) {
                        return '<span class="fw-bold">#' . e((string) $row->code) . '</span>'
                            . '<div class="small text-muted">فاتورة مبيعات يدوية</div>';
                    }

                    $invoiceNumber = $invoice->legacy_invoice_number ?: $invoice->id;

                    return '<a href="' . route('invoices.show', $invoice->id) . '" class="fw-bold text-decoration-none">'
                        . '#' . e((string) $invoiceNumber)
                        . '</a><div class="small text-muted">رقم المبيعات: ' . e((string) $row->code) . '</div>';
                })
                ->addColumn('invoice_source', function ($row) {
                    $invoice = $row->sourceInvoice;
                    if (!$invoice) {
                        return '<span class="badge bg-secondary-subtle text-secondary border">فاتورة مالية</span>';
                    }

                    if ($invoice->contract_id) {
                        return '<a href="' . route('invoices.show', $invoice->id) . '" class="badge bg-info-subtle text-info border text-decoration-none">'
                            . 'عقد استقدام #' . e((string) $invoice->contract_id)
                            . '</a>';
                    }

                    if ($invoice->ingaz_delegation_id) {
                        return '<a href="' . route('invoices.show', $invoice->id) . '" class="badge bg-primary-subtle text-primary border text-decoration-none">'
                            . 'تفويض إنجاز #' . e((string) $invoice->ingaz_delegation_id)
                            . '</a>';
                    }

                    if ($invoice->legacy_order_number) {
                        return '<a href="' . route('invoices.show', $invoice->id) . '" class="badge bg-info-subtle text-info border text-decoration-none">'
                            . 'طلب رقم ' . e((string) $invoice->legacy_order_number)
                            . '</a>';
                    }

                    return '<a href="' . route('invoices.show', $invoice->id) . '" class="badge bg-light text-dark border text-decoration-none">فاتورة النظام</a>';
                })
                ->editColumn('client_id', function ($row) {
                    return $row->client->name ?? "";
                })
                ->addColumn('the_stock', function ($row) {
                    return $row->stocksNames() ?? ""; // Call the method stocksNames()
                })
                ->addColumn('profit_margin', function ($row) {
                    return @$row->profit_with_margin;
                })
                ->editColumn('zatca_status', function ($row) {
                    if ($row->zatca) {
                        return match ($row->zatca->zatca_status) {
                            'REPORTED' => '<span class="badge bg-success"><i class="fa fa-check-circle m-lg-1"></i> REPORTED </span>',
                            'CLEARED' => '<span class="badge bg-primary"><i class="fa fa-check-circle m-lg-1"></i> CLEARED</span>',
                            'NOT_REPORTED' => '<span title="' . $row->error_message . '" class="badge bg-danger">NOT_REPORTED</span>',
                            'NOT_CLEARED' => '<span title="' . $row->error_message . '" class="badge bg-danger">NOT_CLEARED</span>',
                            default => '<span class="badge bg-warning-subtle text-warning border">معلقة للهيئة</span>',
                        };
                    }
                    return '<span class="badge bg-warning-subtle text-warning border">معلقة للهيئة</span>';
                })
                ->addColumn('actions', function ($row) {
                    $imageButton = showButton(route("finance.bill-sales-attachments.edit", $row->id), $row->name,"image", 'warning');
                    $printButton = printButton(route("finance.bill-sales-print.show", $row->id), null, 'finance.bill-sales-print-read');
                    $detailsButton = editHyperButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye", true);
                    $shareButton = (!$row->zatca || ($row->zatca->zatca_status !== "REPORTED" && $row->zatca->zatca_status !== "CLEARED"))
                        ? shareZatcaBtn($row->id)
                        : '';
                    $downloadXMLButton = ($row->zatca && ($row->zatca->xml_file !== null))
                        ? downloadFileBtn(showFile($row->zatca->xml_file), "{$row->id}فاتورة_رقم_")
                        : '';
                    $confirmButton = (!$row->is_confirmed) ? confirmBtn(route('finance.confirm-bill-sales.update',$row->id)) : '';



                    return implode(' ', [$downloadXMLButton,$confirmButton, $imageButton, $printButton, $shareButton, $detailsButton]);
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index", request()->query());
        $data["bladeTitle"] = __("finance::finance.bill-sales");
        $data["addButtonText"] = __("finance::finance.bill-sale");
        $data["clients"] = $service->getClients();
        $data["items"] = $service->getItems();
        $data["modalType"] = "modal-xl";
        return view($this->folderPath . '.index', $data);
    }

    public function exportExcel(Request $request, ObjService $service)
    {
        $sales = $this->filteredBillSalesQuery($request, $service)
            ->with(['client', 'zatca'])
            ->latest('id')
            ->get();
        $filename = 'bill-sales-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            __('finance::finance.Invoice Number'),
            'رقم المبيعات',
            'مرتبطة بـ',
            __('finance::finance.date'),
            __('finance::finance.client'),
            __('finance::finance.Total before discount'),
            __('finance::finance.Total discount'),
            __('finance::finance.the_tax_value'),
            __('finance::finance.total_after_all'),
            __('finance::finance.share'),
        ]);

        foreach ($sales as $sale) {
            fputcsv($stream, [
                $this->sourceInvoiceNumber($sale),
                $sale->code,
                $this->sourceInvoiceLabel($sale),
                $sale->date,
                $sale->client?->name ?: '',
                number_format((float) $sale->total_item, 2, '.', ''),
                number_format((float) $sale->discount, 2, '.', ''),
                number_format((float) $sale->total_tax_value, 2, '.', ''),
                number_format((float) $sale->final_total, 2, '.', ''),
                $this->zatcaStatusLabel($sale),
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request, ObjService $service)
    {
        $sales = $this->filteredBillSalesQuery($request, $service)
            ->with(['client', 'zatca'])
            ->latest('id')
            ->get();

        $pdf = PDF::loadView($this->folderPath . '.export-pdf', [
            'sales' => $sales,
            'printedAt' => now(),
            'bladeTitle' => __("finance::finance.bill-sales"),
        ])->setPaper('a4', 'landscape');

        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download('bill-sales-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function filteredBillSalesQuery(Request $request, ObjService $service)
    {
        $dataTable = $service->getDataTableOfBill($request)
            ->with([
                'client',
                'zatca',
                'sourceInvoice:id,legacy_invoice_number,legacy_order_number,contract_id,ingaz_delegation_id,finance_sale_id',
            ]);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $dataTable->whereBetween('date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $dataTable->where('date', '>=', $fromDate);
        } elseif ($toDate) {
            $dataTable->where('date', '<=', $toDate);
        }

        if ($request->filled('code')) {
            $invoiceNumber = trim((string) $request->code);
            $dataTable->where(function ($query) use ($invoiceNumber) {
                $query->where('code', $invoiceNumber)
                    ->orWhereHas('sourceInvoice', function ($invoiceQuery) use ($invoiceNumber) {
                        $invoiceQuery->where('id', $invoiceNumber)
                            ->orWhere('legacy_invoice_number', $invoiceNumber);
                    });
            });
        }

        if ($request->filled('client_id')) {
            $dataTable->where('client_id', $request->client_id);
        }

        if ($request->filled('item_id')) {
            $item = $request->item_id;
            $dataTable->whereHas('items', function ($query) use ($item) {
                $query->where('item_id', $item);
            });
        }

        // Keep the ZATCA queue in the same newest-first sequence used by the
        // operational invoices screen. Manual financial sales are ordered next
        // by their own id and remain visible.
        return $dataTable
            ->orderByDesc(
                Invoice::query()
                    ->select('id')
                    ->whereColumn('finance_sale_id', 'fn_sales.id')
                    ->limit(1)
            )
            ->orderByDesc('fn_sales.id');
    }

    private function zatcaStatusLabel($sale): string
    {
        return match ($sale->zatca?->zatca_status) {
            'REPORTED' => 'REPORTED',
            'CLEARED' => 'CLEARED',
            'NOT_REPORTED' => 'NOT_REPORTED',
            'NOT_CLEARED' => 'NOT_CLEARED',
            default => 'معلقة للهيئة',
        };
    }

    private function sourceInvoiceNumber($sale): string
    {
        $invoice = $sale->sourceInvoice;

        return (string) ($invoice?->legacy_invoice_number ?: ($invoice?->id ?: $sale->code));
    }

    private function sourceInvoiceLabel($sale): string
    {
        $invoice = $sale->sourceInvoice;
        if (!$invoice) {
            return 'فاتورة مبيعات يدوية';
        }

        if ($invoice->contract_id) {
            return 'عقد استقدام #' . $invoice->contract_id;
        }

        if ($invoice->ingaz_delegation_id) {
            return 'تفويض إنجاز #' . $invoice->ingaz_delegation_id;
        }

        if ($invoice->legacy_order_number) {
            return 'طلب رقم ' . $invoice->legacy_order_number;
        }

        return 'فاتورة النظام';
    }

    public function create(Request $request, ObjService $service)
    {
        $data['storeRoute'] = route($this->mainRoute . ".store");
        $data['clients'] = $service->getClients();
        $data['lastCode'] = $service->getLastCodeOfType(SalesTypeEnum::Sales->value);
        $data['items'] = $service->getItems();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data["bladeTitle"] = __("finance::finance.bill-sale");
        return view($this->folderPath . '.create', $data);
    }


    public function store(SalesRequest $request, ObjService $service,SalesOrderService $salesOrderService)
    {
        $salesData = $request->only($this->postData);
        if (isset($request->sales_order)){
            $salesOrderService->transferSaleOrderToBillSale($request->sales_order);
        }
        $sale = $service->storeSales($salesData, $request);
//        $service->checkIsConfirmSetting($sale, ConfirmSettingTypes::BillSales->value);
        return jsonSuccess(null, null, 201);
    }


    public function show(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.show')->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        } else {
            $obj = $service->find($id);
            $bladeTitle = __('finance::finance.bill-sales');
            return view($this->folderPath . ".show", compact('obj', 'bladeTitle'));
        }
    }

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


    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->updateSalesOrder($id, $dataInsert);
        return jsonSuccess($data);
    }


    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();

    }
}
