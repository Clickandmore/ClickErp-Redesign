<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use Illuminate\Http\Request;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Invoice::with(['contract.user'])->select('invoices.*')->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('invoice_number', function ($row) {
                    return '#' . $row->id;
                })
                ->addColumn('contract_number', function ($row) {
                    return '#' . $row->contract_id;
                })
                ->addColumn('client_name', function ($row) {
                    return $row->contract && $row->contract->user ? $row->contract->user->name : '-';
                })
                ->editColumn('subtotal', function ($row) {
                    return number_format($row->subtotal, 2);
                })
                ->editColumn('vat_amount', function ($row) {
                    return number_format($row->vat_amount, 2);
                })
                ->editColumn('discount', function ($row) {
                    return number_format($row->discount, 2);
                })
                ->editColumn('total_due', function ($row) {
                    return number_format($row->total_due, 2);
                })
                ->addColumn('status', function ($row) {
                    $paid = $row->total_due <= 0;
                    $class = $paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
                    $text = $paid ? 'مدفوعة' : 'غير مدفوعة';
                    return '<span class="badge ' . $class . '">' . $text . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d H:i') : '-';
                })
                ->addColumn('actions', function ($row) {
                    $viewUrl = route('invoices.show', $row->id);
                    $pdfUrl = route('invoice.download', $row->id);

                    return '<div class="d-flex justify-content-end gap-2">
                                <a href="' . $viewUrl . '" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="' . $pdfUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('admin.ingaz.invoices.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $contractId = $request->query('contract_id');
        if ($request->$contractId) {
            return view('admin.ingaz.invoices.create', compact('contractId'));
        } else {
            $contracts = Contract::get();
            return view('admin.ingaz.invoices.create', compact('contracts'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
        ]);

        // منع تكرار الفاتورة لنفس العقد
        $invoiceExists = Invoice::where('contract_id', $request->contract_id)->exists();

        if ($invoiceExists) {
            return response()->json([
                'success' => false,
                'message' => 'هذا العقد لديه فاتورة سابقة'
            ], 422);
        }

        $invoice = Invoice::create([
            'contract_id' => $request->contract_id,
            'insurance_company' => $request->insurance_company ?? null,
            'direct_recruitment_cost' => $request->direct_recruitment_cost ?? 0,
            'external_recruitment_cost' => $request->external_recruitment_cost ?? 0,
            'government_cost' => $request->government_cost ?? 0,
            'internal_ticket_cost' => $request->internal_ticket_cost ?? 0,
            'visa_recruited_count' => $request->visa_recruited_count ?? 0,
            'direct_recruitment_count' => $request->direct_recruitment_count ?? 0,
            'external_recruitment_count' => $request->external_recruitment_count ?? 0,
            'insured_count' => $request->insured_count ?? 0,
            'government_cost_count' => $request->government_cost_count ?? 0,
            'internal_ticket_count' => $request->internal_ticket_count ?? 0,
            'subtotal' => $request->subtotal ?? 0,
            'discount' => $request->discount ?? 0,
            'vat_amount' => $request->vat_amount ?? 0,
            'total_due' => $request->total_due ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'data' => $invoice,
            'redirect' => route('invoices.show', $invoice->id),
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invoice = Invoice::with(['contract.user', 'contract.maid', 'contract.info'])->findOrFail($id);

        $supplierName = 'شركة بلاتنيوم الشرقية للاستقدام';
        $supplierAddress = 'الرياض، المملكة العربية السعودية';
        $serviceDesc = 'طلب تعاقد';

        $items = [
            [
                'label' => 'تكاليف الاستقدام المباشر',
                'qty' => (int) $invoice->direct_recruitment_count,
                'unit' => (float) $invoice->direct_recruitment_cost,
                'vat_rate' => 0.15,
                'vat_amount' => (float) $invoice->vat_amount,
            ],
            [
                'label' => 'تكاليف الاستقدام الخارجية',
                'qty' => (int) $invoice->external_recruitment_count,
                'unit' => (float) $invoice->external_recruitment_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
            [
                'label' => 'تكاليف حكومية',
                'qty' => (int) $invoice->government_cost_count,
                'unit' => (float) $invoice->government_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
            [
                'label' => 'تكلفة التذكرة الداخلية',
                'qty' => (int) $invoice->internal_ticket_count,
                'unit' => (float) $invoice->internal_ticket_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
        ];

        foreach ($items as &$item) {
            $item['line_total'] = $item['qty'] * $item['unit'];
        }
        unset($item);

        $invoiceStatus = $invoice->total_due <= 0 ? 'مدفوعة' : 'غير مدفوعة';

        return view('admin.ingaz.invoices.show', compact(
            'invoice',
            'supplierName',
            'supplierAddress',
            'serviceDesc',
            'items',
            'invoiceStatus'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function downloadInvoice($id)
    {
        $invoice = Invoice::with(['contract.user', 'contract.maid', 'contract.info'])->findOrFail($id);

        $supplierName = 'شركة بلاتنيوم الشرقية للاستقدام';
        $supplierAddress = 'الرياض، المملكة العربية السعودية';
        $serviceDesc = 'طلب تعاقد';

        $items = [
            [
                'label' => 'تكاليف الاستقدام المباشر',
                'qty' => (int) $invoice->direct_recruitment_count,
                'unit' => (float) $invoice->direct_recruitment_cost,
                'vat_rate' => 0.15,
                'vat_amount' => (float) $invoice->vat_amount,
            ],
            [
                'label' => 'تكاليف الاستقدام الخارجية',
                'qty' => (int) $invoice->external_recruitment_count,
                'unit' => (float) $invoice->external_recruitment_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
            [
                'label' => 'تكاليف حكومية',
                'qty' => (int) $invoice->government_cost_count,
                'unit' => (float) $invoice->government_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
            [
                'label' => 'تكلفة التذكرة الداخلية',
                'qty' => (int) $invoice->internal_ticket_count,
                'unit' => (float) $invoice->internal_ticket_cost,
                'vat_rate' => 0,
                'vat_amount' => 0,
            ],
        ];

        foreach ($items as &$item) {
            $item['line_total'] = $item['qty'] * $item['unit'];
        }
        unset($item);

        $invoiceStatus = $invoice->total_due <= 0 ? 'مدفوعة' : 'غير مدفوعة';

        $pdf = PDF::loadView('admin.ingaz.invoices.invoice', [
            'invoice' => $invoice,
            'supplierName' => $supplierName,
            'supplierAddress' => $supplierAddress,
            'serviceDesc' => $serviceDesc,
            'items' => $items,
            'invoiceStatus' => $invoiceStatus
        ], [], [
            'title' => 'Contract Form',
            'format' => 'A4-P',
            'orientation' => 'P'
        ]);

        // return $pdf->download('invoice-' . $invoice->id . '.pdf');
        return $pdf->stream('invoice-' . $invoice->id . '.pdf');
    }
}
