<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Setting;
use Illuminate\Http\Request;
use Nami\Finance\Models\CostCenter;
use PDF;
use Yajra\DataTables\Facades\DataTables;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use Nami\Finance\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
                    $pdfUrl = route('invoice.download', ['id' => $row->id, 'autoprint' => 1]);

                    return '<div class="d-flex justify-content-end gap-2">
                                <a href="' . $viewUrl . '" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="' . $pdfUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-print"></i>
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
    public function store(Request $request, ObjService $service, YearService $yearService)
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

        try {
            $invoice = DB::transaction(function () use ($request, $service, $yearService) {
                $contract = Contract::with(['user', 'maid.nationality', 'requiredMaidNationality'])->findOrFail($request->contract_id);

                // جلب حساب العميل المسجل في الشجرة المحاسبية
                $clientAccountId = $contract->user?->account_id;

                // جلب حساب الجنسية المسجلة في الشجرة المحاسبية، أو محاولة إيجاد حساب بناءً على اسم الجنسية
                // التزامات عقود الاستقدام + اسم الجنسية
                $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
                $nationalityAccountId = $nationality?->account_id;

                if (!$nationalityAccountId && $nationality) {
                    $nationalityName = $nationality->sub_title ?? $nationality->title ?? null;
                    if ($nationalityName) {
                        $nationalityAccountId = $this->resolveNationalityAccountId($nationalityName);
                    }
                }

                // dd( $contract, $clientAccountId, $nationality, $nationalityAccountId);

                if (!$clientAccountId) {
                    // throw new \RuntimeException('Cannot create entry: client account is missing');
                    return response()->json([
                        'success' => false,
                        'message' => "لا يوجد حساب لهذا العميل , الرجاء اضافة حساب له اولا.",
                    ], 422);
                }

                if (!$nationalityAccountId) {
                    // throw new \RuntimeException('Cannot create entry: nationality account is missing');
                    return response()->json([
                        'success' => false,
                        'message' => "لا يوجد حساب لهذه الجنسية : $nationalityName , الرجاء اضافة حساب اولا.",
                    ], 422);
                }

                //1- انشاء الفاتورة وربطها بالعقد
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

                //2- انشاء قيد الفاتورة في النظام المحاسبي بين العميل-مدين والتزامات عقود الجنسية-دائن
                $statement = 'Invoice entry #' . $invoice->id . ' for contract #' . $invoice->contract->id;

                // بعد قيد الفاتورة يتم عمل مركز تكلفة لهذه الجنسية - مركز تكلفة طلبات الاستقدام لدولة+اسم الدولة
                $costCenterName = 'مركز تكلفة طلبات استقدام دولة ' . ($nationality->sub_title ?? $nationality->title ?? null);
                $nationality_cost_center = CostCenter::where('title', 'like', '%' . $costCenterName . '%')->first();
                if (!$nationality_cost_center) {
                    throw new \RuntimeException("لا يوجد مركز تكلفة باسم '$costCenterName'");
                }

                $cost_center_ids = [$nationality_cost_center->id, null];

                $this->storeConstraintForInvoice($invoice, $service, $yearService, $nationalityAccountId, $clientAccountId, $statement, $cost_center_ids);


                //3- انشاء قيد سند قبض بين العميل-دائن و حساب مساند-مدين 
                $musandName = 'عمولات مساند';
                $musandAccountId = Account::query()->where('name', $musandName)->value('id');

                if (!$musandAccountId) {
                    throw new \RuntimeException("لا يوجد حساب باسم '$musandName' في الشجرة المحاسبية");
                }
                $statement = "Musand receipt for invoice #{$invoice->id}";

                $this->storeConstraintForInvoice($invoice, $service, $yearService, $clientAccountId, $musandAccountId, $statement);

                return $invoice;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'data' => $invoice,
            'redirect' => route('invoices.show', $invoice->id),
        ]);
    }

    public function storeConstraintForInvoice(Invoice $invoice, ObjService $service, YearService $yearService, $creditAccountId, $debitAccountId, $statement = null, $costCenterIds = null)
    {
        $date = $invoice->created_at ? $invoice->created_at->toDateString() : now()->toDateString();
        $activeYear = $yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة للتاريخ المحدد');
        }

        $statement = 'Invoice entry #' . $invoice->id . ' for contract #' . $invoice->contract->id;
        $totalDue = (float) ($invoice->total_due ?? 0);

        $data = [
            'code' => $service->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => [$creditAccountId, $debitAccountId],
            'account_type' => ['creditor', 'debtor'],
            'debtor_value' => [0, $totalDue],
            'creditor_value' => [$totalDue, 0],
            'statement_account' => [$statement, $statement],
            'cost_center_required' => 'off',
            'cost_center_id' => $costCenterIds ?? [null, null],
            'value' => $totalDue,

        ];

        $storeConstraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $storeConstraint->id, $data);
        $service->storeFiles($storeConstraint, $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $viewData = $this->prepareInvoiceViewData((int) $id);

        return view('admin.ingaz.invoices.show', $viewData);
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
        $viewData = $this->prepareInvoiceViewData((int) $id);
        $invoice = $viewData['invoice'];

        // dompdf does not shape Arabic text well in many cases, so browser print is the default.
        if (request()->query('driver') !== 'dompdf') {
            return response()->view('admin.ingaz.invoices.print', $viewData, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $pdf = PDF::loadView('admin.ingaz.invoices.invoice', $viewData)->setPaper('a4', 'portrait');

        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        if (request()->boolean('download')) {
            return $pdf->download('invoice-' . $invoice->id . '.pdf');
        }

        return $pdf->stream('invoice-' . $invoice->id . '.pdf');
    }

    private function prepareInvoiceViewData(int $id): array
    {
        $invoice = Invoice::with([
            'contract.user',
            'contract.maid.nationality',
            'contract.maid.religion',
            'contract.requiredMaidNationality',
            'contract.requiredMaidReligion',
            'contract.requiredMaidJobTitle',
            'contract.info',
        ])->findOrFail($id);

        $settings = Setting::query()->first();
        $settingsLogo = ($settings && $settings->logo_header) ? showFile($settings->logo_header) : null;
        $settingsZatcaLogo = ($settings && $settings->zatch_logo) ? showFile($settings->zatch_logo) : null;
        $settingsQr = ($settings && $settings->qr_image) ? showFile($settings->qr_image) : null;
        $settingsSecondQr = ($settings && $settings->second_qr_image) ? showFile($settings->second_qr_image) : null;

        $supplierName = (string) ($settings?->website_name ?: 'شركة بلاتنيوم الشرقية للاستقدام');
        $supplierAddress = (string) ($settings?->location ?: 'الرياض، المملكة العربية السعودية');
        $supplierPhone = (string) ($settings?->phone ?: '-');
        $supplierOtherPhone = (string) ($settings?->other_phone ?: '-');
        $supplierEmail = (string) ($settings?->email ?: '-');
        $supplierTaxNumber = (string) ($settings?->tax_number ?: '314232024200003');
        $supplierLogo = $settingsLogo ?: asset('admin/assets/img/company_logo.png');
        $zatcaLogo = $settingsZatcaLogo ?: asset('site/assets/images/zatca-logo.png');
        $qrImage = $settingsQr ?: ($settingsSecondQr ?: asset('site/assets/images/qr1.png'));

        $serviceDesc = 'طلب تعاقد';
        $serviceLongDescription = $this->buildServiceDescription($invoice);
        $items = $this->buildInvoiceItems($invoice);
        $invoiceStatus = $invoice->total_due <= 0 ? 'مدفوعة' : 'غير مدفوعة';
        $printedBy = optional(Auth::guard('admin')->user())->name
            ?? optional(Auth::user())->name
            ?? 'النظام';
        $printedAt = now();

        $client = $invoice->contract?->user;
        $customerName = $client?->name ?: '---';
        $customerNationalId = $client?->national_num ?: '---';
        $customerPhone = $this->formatPhone((string) ($client?->phone_code ?? ''), (string) ($client?->phone ?? ''));
        $customerAddress = $client?->address ?: '---';
        $accountNumber = $client?->account_id ?: ($client?->id ?: $invoice->contract_id);

        $contractInfo = $invoice->contract?->info;
        $matchedHijriDate = $contractInfo?->visa_hijry_date
            ? date('Y-m-d', strtotime((string) $contractInfo->visa_hijry_date))
            : '-';

        $invoiceDateTime = optional($invoice->created_at)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $invoiceDate = optional($invoice->created_at)->format('Y-m-d') ?? now()->format('Y-m-d');

        $subtotalBeforeDiscount = (float) $invoice->subtotal;
        $discountTotal = (float) $invoice->discount;
        $netSubtotal = $subtotalBeforeDiscount - $discountTotal;
        $vatAmount = (float) $invoice->vat_amount;
        $grandTotal = (float) $invoice->total_due;

        return [
            'invoice' => $invoice,
            'settings' => $settings,
            'supplierName' => $supplierName,
            'supplierAddress' => $supplierAddress,
            'supplierPhone' => $supplierPhone,
            'supplierOtherPhone' => $supplierOtherPhone,
            'supplierEmail' => $supplierEmail,
            'supplierTaxNumber' => $supplierTaxNumber,
            'supplierLogo' => $supplierLogo,
            'zatcaLogo' => $zatcaLogo,
            'qrImage' => $qrImage,
            'serviceDesc' => $serviceDesc,
            'serviceLongDescription' => $serviceLongDescription,
            'items' => $items,
            'invoiceStatus' => $invoiceStatus,
            'printedBy' => $printedBy,
            'printedAt' => $printedAt,
            'customerName' => $customerName,
            'customerNationalId' => $customerNationalId,
            'customerPhone' => $customerPhone,
            'customerAddress' => $customerAddress,
            'accountNumber' => $accountNumber,
            'matchedHijriDate' => $matchedHijriDate,
            'invoiceDate' => $invoiceDate,
            'invoiceDateTime' => $invoiceDateTime,
            'invoiceUrl' => url('/admin/invoices/download/' . $invoice->id),
            'subtotalBeforeDiscount' => $subtotalBeforeDiscount,
            'discountTotal' => $discountTotal,
            'netSubtotal' => $netSubtotal,
            'vatAmount' => $vatAmount,
            'grandTotal' => $grandTotal,
        ];
    }

    private function buildInvoiceItems(Invoice $invoice): array
    {
        $rawItems = [
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

        $items = array_values(array_filter($rawItems, function ($item) {
            return $item['qty'] > 0 || $item['unit'] > 0 || $item['vat_amount'] > 0;
        }));

        if (empty($items)) {
            $items = $rawItems;
        }

        foreach ($items as &$item) {
            $item['line_subtotal'] = $item['qty'] * $item['unit'];
            $item['line_discount'] = 0;
            $item['line_total'] = $item['line_subtotal'] - $item['line_discount'];
            $item['line_grand_total'] = $item['line_total'] + $item['vat_amount'];
        }
        unset($item);

        return $items;
    }

    private function buildServiceDescription(Invoice $invoice): string
    {
        $contract = $invoice->contract;
        if (!$contract) {
            return 'طلب استقدام';
        }

        $clientName = $contract->user?->name ?: 'غير محدد';
        $jobTitle = $contract->requiredMaidJobTitle?->title ?: 'عاملة منزلية';
        $nationality = $contract->requiredMaidNationality?->title
            ?: $contract->maid?->nationality?->title
            ?: $contract->maid?->nationality?->sub_title
            ?: 'غير محدد';
        $religion = $contract->requiredMaidReligion?->title
            ?: $contract->maid?->religion?->title
            ?: 'غير محدد';
        $experience = $contract->required_maid_experience_min
            ? 'خبرة لا تقل عن ' . $contract->required_maid_experience_min . ' سنة'
            : (($contract->maid?->experience_status === 'has_experience')
                ? 'سبق لها العمل'
                : (($contract->maid?->experience_status === 'no_experience') ? 'غير سبق لها العمل' : 'غير محدد'));
        $arrivalPlace = $contract->user?->address ?: 'غير محدد';
        $visaType = $contract->contract_visa_type ?: 'مساند';
        $msanedNo = $contract->info?->msaned_number ?: ($contract->msaned_number ?: '-');
        $visaNo = $contract->info?->visa_number ?: ($contract->visa_number ?: '-');

        return 'طلب استقدام - العميل: ' . $clientName
            . ' - رقم العقد: ' . $contract->id
            . ' - المهنة: ' . $jobTitle
            . ' - الجنسية: ' . $nationality
            . ' - الديانة: ' . $religion
            . ' - الخبرة: ' . $experience
            . ' - محل الوصول: ' . $arrivalPlace
            . ' - نوع التأشيرة: ' . $visaType
            . ' - رقم التأشيرة: ' . $visaNo
            . ' - رقم مساند: ' . $msanedNo;
    }

    private function formatPhone(string $code, string $phone): string
    {
        $code = trim($code);
        $phone = trim($phone);

        if ($code === '' && $phone === '') {
            return '---';
        }

        if ($code === '') {
            return $phone;
        }

        if ($phone === '') {
            return $code;
        }

        return $code . $phone;
    }

    private function resolveNationalityAccountId(string $nationalityName): ?int
    {
        $name = trim($nationalityName);
        if ($name === '') {
            return null;
        }

        $accountId = Account::query()->where('name', $name)->value('id');
        if ($accountId) {
            return (int) $accountId;
        }

        $prefixes = [
            'التزامات عقود ',
            'التزامات عقود الاستقدام ',
        ];

        foreach ($prefixes as $prefix) {
            $accountId = Account::query()->where('name', $prefix . $name)->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        $parentId = Account::query()
            ->where('name', 'التزامات عقود الاستقدام')
            ->value('id');

        if (!$parentId) {
            $parentId = Account::query()
                ->where('name', 'التزامات عقود')
                ->value('id');
        }

        if ($parentId) {
            $accountId = Account::query()
                ->where('parent_id', $parentId)
                ->where('name', 'like', '%' . $name . '%')
                ->orderBy('id')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        $accountId = Account::query()
            ->where('name', 'like', '%' . $name . '%')
            ->orderBy('id')
            ->value('id');

        return $accountId ? (int) $accountId : null;
    }
}
