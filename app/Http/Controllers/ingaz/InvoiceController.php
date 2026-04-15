<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Setting;
use App\Models\ingaz\User as ClientUser;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\BondService;
use PDF;
use Yajra\DataTables\Facades\DataTables;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
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
                    $paid = (bool) $row->is_paid || (float) $row->total_due <= 0;
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
        $contractId = is_numeric($contractId) ? (int) $contractId : null;

        $contracts = Contract::query()
            ->with(['user', 'maid.nationality', 'requiredMaidNationality'])
            ->whereDoesntHave('invoice')
            ->latest('id')
            ->get();

        $selectedContract = null;

        if ($contractId) {
            $selectedContract = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality', 'invoice'])
                ->findOrFail($contractId);

            if ($selectedContract->invoice) {
                return redirect()
                    ->route('invoices.show', $selectedContract->invoice->id)
                    ->with('success', 'تم إصدار فاتورة لهذا العقد مسبقاً.');
            }
        }

        return view('admin.ingaz.invoices.create', compact('contractId', 'contracts', 'selectedContract'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ObjService $service, YearService $yearService, BondService $bondService)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'insurance_company' => 'nullable|string|max:255',
            'direct_recruitment_cost' => 'nullable|numeric|min:0',
            'external_recruitment_cost' => 'nullable|numeric|min:0',
            'government_cost' => 'nullable|numeric|min:0',
            'internal_ticket_cost' => 'nullable|numeric|min:0',
            'visa_recruited_count' => 'nullable|integer|min:0',
            'direct_recruitment_count' => 'nullable|integer|min:0',
            'external_recruitment_count' => 'nullable|integer|min:0',
            'insured_count' => 'nullable|integer|min:0',
            'government_cost_count' => 'nullable|integer|min:0',
            'internal_ticket_count' => 'nullable|integer|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'total_due' => 'nullable|numeric|min:0',
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
            $invoice = DB::transaction(function () use ($request, $service, $yearService, $bondService) {
                $contract = Contract::with(['user', 'maid.nationality', 'requiredMaidNationality'])
                    ->findOrFail($request->contract_id);

                if ($contract->invoice()->exists()) {
                    throw new \RuntimeException('هذا العقد لديه فاتورة سابقة');
                }

                // التأكد من وجود حساب للعميل أو إنشاؤه تلقائياً عند أول إصدار للفاتورة
                $clientAccountId = $this->ensureClientAccountId($contract);

                // جلب حساب الجنسية المسجلة في الشجرة المحاسبية، أو محاولة إيجاد حساب بناءً على اسم الجنسية
                // التزامات عقود الاستقدام + اسم الجنسية
                $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
                $nationalityAccountId = $nationality?->account_id;

                $nationalityName = $nationality?->sub_title ?? $nationality?->title ?? null;

                if ($nationalityAccountId && !$this->isRecruitmentLiabilityAccount((int) $nationalityAccountId)) {
                    $nationalityAccountId = null;
                }

                if (!$nationalityAccountId && $nationality) {
                    if ($nationalityName) {
                        $nationalityAccountId = $this->resolveNationalityAccountId($nationalityName);
                    }
                }

                // dd( $contract, $clientAccountId, $nationality, $nationalityAccountId);

                if (!$nationalityAccountId) {
                    throw new \RuntimeException(
                        'لا يوجد حساب لهذه الجنسية'
                        . ($nationalityName ? " : {$nationalityName}" : '')
                        . '، الرجاء إضافة حساب أولاً.'
                    );
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
                    'is_paid' => true,
                    'paid_at' => now(),
                ]);

                $invoice->setRelation('contract', $contract);

                if ((float) $invoice->total_due > 0) {
                    //2- انشاء قيد الفاتورة التفصيلي وربطه بمركز تكلفة العقد
                    $contractCostCenter = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName);

                    $this->storeInvoiceConstraintForInvoice(
                        $invoice,
                        $service,
                        $yearService,
                        $nationalityAccountId,
                        $clientAccountId,
                        $contractCostCenter->id
                    );

                    //3- انشاء سند قبض فعلي من حساب العميل الى الحساب الوسيط لمساند
                    $musandAccountId = ConfirmSetting::query()
                        ->where('title', ConfirmSettingTypes::MusanedIntermediateAccount->value)
                        ->latest('updated_at')
                        ->value('value');

                    if (!$musandAccountId) {
                        throw new \RuntimeException("لا يوجد حساب وسيط مساند محدد. الرجاء تحديده من صفحة إعدادات النظام المحاسبي.");
                    }

                    $musandAccount = Account::query()
                        ->whereKey($musandAccountId)
                        ->whereIn('parent_id', [122, 156])
                        ->first();

                    if (!$musandAccount) {
                        throw new \RuntimeException("حساب مساند المحدد غير صحيح. يجب اختيار حساب تابع للبنوك أو لأرصدة مدينة أخرى من إعدادات النظام المحاسبي.");
                    }

                    $this->storeReceiptBondForInvoice(
                        $invoice,
                        $bondService,
                        $yearService,
                        $clientAccountId,
                        (int) $musandAccount->id
                    );
                }

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
            'message' => 'تم إصدار الفاتورة بنجاح',
            'data' => $invoice,
            'redirect' => route('invoices.show', $invoice->id),
            'view_url' => route('invoices.show', $invoice->id),
            'print_url' => route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]),
            'index_url' => route('invoices.index'),
            'create_url' => route('invoices.create'),
        ]);
    }

    private function storeInvoiceConstraintForInvoice(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $nationalityAccountId,
        int $clientAccountId,
        int $costCenterId
    ): void
    {
        $amounts = $this->buildInvoiceFinanceBreakdown($invoice);
        if ($amounts['total_due'] <= 0) {
            return;
        }

        $date = $invoice->created_at ? $invoice->created_at->toDateString() : now()->toDateString();
        $activeYear = $yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة للتاريخ المحدد');
        }

        $statement = $this->buildInvoiceStatement($invoice);
        $lines = [];

        if ($amounts['direct_amount'] > 0) {
            $this->appendConstraintPair(
                $lines,
                $nationalityAccountId,
                $clientAccountId,
                $amounts['direct_amount'],
                $statement,
                $costCenterId
            );
        }

        if ($amounts['other_amount'] > 0) {
            $this->appendConstraintPair(
                $lines,
                $nationalityAccountId,
                $clientAccountId,
                $amounts['other_amount'],
                $statement,
                $costCenterId
            );
        }

        if ($amounts['vat_amount'] > 0) {
            $taxAccountId = $this->resolveRevenueTaxAccountId();

            if (!$taxAccountId) {
                throw new \RuntimeException(
                    'لا توجد ضريبة إيرادات معرفة في إعدادات الحسابات. الرجاء تحديدها من صفحة إعدادات الحسابات أولاً.'
                );
            }

            $this->appendConstraintPair(
                $lines,
                $taxAccountId,
                $clientAccountId,
                $amounts['vat_amount'],
                $statement
            );
        }

        if (empty($lines)) {
            return;
        }

        $data = [
            'code' => $service->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $amounts['total_due'],
        ];

        $storeConstraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $storeConstraint->id, $data);
        $service->storeFiles($storeConstraint, $data);
    }

    private function storeReceiptBondForInvoice(
        Invoice $invoice,
        BondService $bondService,
        YearService $yearService,
        int $clientAccountId,
        int $musanedAccountId
    ): void
    {
        $date = $invoice->created_at ? $invoice->created_at->toDateString() : now()->toDateString();
        if (!$yearService->checkDateRange($date)) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة للتاريخ المحدد');
        }

        $bond = $bondService->storeQabdBond([
            'payment_type' => BondPaymentTypes::Cash->value,
            'bond_book_num' => ' ',
            'debit_account_id' => $musanedAccountId,
            'credit_account_id' => $clientAccountId,
            'value' => $this->roundMoney((float) $invoice->total_due),
            'date' => $date,
            'statement' => $this->buildInvoiceReceiptStatement($invoice),
            'code' => $bondService->lastCode(BondTypes::Qabd->value),
            'is_tax' => 0,
            'general_type' => 0,
        ]);

        $bondService->checkIsConfirmSetting($bond, ConfirmSettingTypes::QabdBond->value);
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
        $invoiceStatus = ((bool) $invoice->is_paid || (float) $invoice->total_due <= 0) ? 'مدفوعة' : 'غير مدفوعة';
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

    private function buildInvoiceFinanceBreakdown(Invoice $invoice): array
    {
        $directGross = $this->roundMoney((float) $invoice->direct_recruitment_cost * (int) $invoice->direct_recruitment_count);
        $otherGross = $this->roundMoney(
            ((float) $invoice->external_recruitment_cost * (int) $invoice->external_recruitment_count)
            + ((float) $invoice->government_cost * (int) $invoice->government_cost_count)
            + ((float) $invoice->internal_ticket_cost * (int) $invoice->internal_ticket_count)
        );
        $vatAmount = $this->roundMoney((float) $invoice->vat_amount);
        $totalDue = $this->roundMoney((float) $invoice->total_due);
        $serviceGross = $this->roundMoney($directGross + $otherGross);
        $serviceNet = max(0, $this->roundMoney($totalDue - $vatAmount));

        if ($serviceGross <= 0) {
            return [
                'direct_amount' => 0.0,
                'other_amount' => 0.0,
                'vat_amount' => $vatAmount,
                'total_due' => $totalDue,
            ];
        }

        if ($directGross <= 0) {
            return [
                'direct_amount' => 0.0,
                'other_amount' => $serviceNet,
                'vat_amount' => $vatAmount,
                'total_due' => $totalDue,
            ];
        }

        if ($otherGross <= 0) {
            return [
                'direct_amount' => $serviceNet,
                'other_amount' => 0.0,
                'vat_amount' => $vatAmount,
                'total_due' => $totalDue,
            ];
        }

        $directAmount = $this->roundMoney($serviceNet * ($directGross / $serviceGross));
        $otherAmount = $this->roundMoney($serviceNet - $directAmount);

        return [
            'direct_amount' => $directAmount,
            'other_amount' => $otherAmount,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
        ];
    }

    private function appendConstraintPair(
        array &$lines,
        int $creditAccountId,
        int $debitAccountId,
        float $amount,
        string $statement,
        ?int $costCenterId = null
    ): void
    {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $creditAccountId,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => $amount,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];

        $lines[] = [
            'account_id' => $debitAccountId,
            'account_type' => 'debtor',
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => $statement,
            'cost_center_id' => null,
        ];
    }

    private function buildInvoiceStatement(Invoice $invoice): string
    {
        $contractId = $invoice->contract_id;
        $msanedNumber = $invoice->contract?->info?->msaned_number
            ?? $invoice->contract?->msaned_number
            ?? '-';

        return "قيد فاتورة العقد #{$contractId} / الفاتورة #{$invoice->id} / مساند {$msanedNumber}";
    }

    private function buildInvoiceReceiptStatement(Invoice $invoice): string
    {
        $contractId = $invoice->contract_id;
        $msanedNumber = $invoice->contract?->info?->msaned_number
            ?? $invoice->contract?->msaned_number
            ?? '-';

        return "سند قبض فاتورة العقد #{$contractId} / الفاتورة #{$invoice->id} / مساند {$msanedNumber}";
    }

    private function resolveRevenueTaxAccountId(): ?int
    {
        $directAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::RevenueTaxAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($directAccountId) {
            $account = Account::query()
                ->whereKey($directAccountId)
                ->where('status', 'creditor')
                ->first();

            if ($account) {
                return (int) $account->id;
            }
        }

        $taxSettingId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::TaxRateRevenue->value)
            ->latest('updated_at')
            ->value('value');

        if ($taxSettingId) {
            $taxAccountId = TaxSetting::query()
                ->whereKey($taxSettingId)
                ->value('account_id');

            if ($taxAccountId) {
                return (int) $taxAccountId;
            }
        }

        $taxAccountId = Account::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%ضريبة%إيراد%')
                    ->orWhere('name', 'like', '%ضريبة%إيرادات%')
                    ->orWhere('name', 'like', '%ضريبة%قيمة%مضافة%');
            })
            ->where('status', 'creditor')
            ->orderBy('code')
            ->value('id');

        return $taxAccountId ? (int) $taxAccountId : null;
    }

    private function ensureClientAccountId(Contract $contract): int
    {
        if (!$contract->user_id) {
            throw new \RuntimeException('العقد غير مرتبط بعميل، لا يمكن إنشاء الحساب المحاسبي.');
        }

        $user = ClientUser::query()
            ->lockForUpdate()
            ->find($contract->user_id);

        if (!$user) {
            throw new \RuntimeException('العميل المرتبط بالعقد غير موجود.');
        }

        if ($user->account_id) {
            return (int) $user->account_id;
        }

        $parentAccount = Account::query()
            ->where('type', AccountTypesEnum::Clients->value)
            ->lockForUpdate()
            ->first();

        if (!$parentAccount) {
            throw new \RuntimeException(
                'لا يوجد حساب رئيسي للعملاء في جدول الحسابات. يرجى إنشاء حساب من نوع Clients أولاً.'
            );
        }

        $lastCode = Account::query()
            ->where('parent_id', $parentAccount->id)
            ->max('code');

        if (!$lastCode) {
            $lastCode = (int) ($parentAccount->code . '00000');
        }

        $account = Account::query()->create([
            'name' => $user->name ?: ('عميل #' . $user->id),
            'operation_type' => $parentAccount->operation_type,
            'status' => $parentAccount->status,
            'statement_type' => $parentAccount->statement_type,
            'level' => $parentAccount->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $parentAccount->id,
            'code' => ((int) $lastCode) + 1,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        $user->update([
            'account_id' => $account->id,
        ]);

        if ($contract->relationLoaded('user') && $contract->user) {
            $contract->user->account_id = $account->id;
        }

        return (int) $account->id;
    }

    private function ensureRecruitmentContractCostCenter(Contract $contract, ?string $nationalityName): CostCenter
    {
        $rootCostCenter = $this->ensureRecruitmentContractsRootCostCenter();

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $rootCostCenter->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contract->id)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $title = 'عقد استقدام #' . $contract->id;
        $nationalityName = trim((string) $nationalityName);
        if ($nationalityName !== '') {
            $title .= ' - ' . $nationalityName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $rootCostCenter->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $contract->id,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureRecruitmentContractsRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عقود الاستقدام')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عقود الاستقدام',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function isRecruitmentLiabilityAccount(int $accountId): bool
    {
        $account = Account::query()->find($accountId);

        while ($account) {
            if (in_array($account->name, ['التزامات عقود الاستقدام', 'التزامات عقود'], true)) {
                return true;
            }

            $account = $account->parent_id
                ? Account::query()->find($account->parent_id)
                : null;
        }

        return false;
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
            $rootAccount = Account::query()
                ->with('childrenRecursive')
                ->find($parentId);

            $relatedAccountIds = $rootAccount
                ? array_merge([$rootAccount->id], $rootAccount->getAllChildrenIds())
                : [$parentId];

            $accountId = Account::query()
                ->whereIn('id', $relatedAccountIds)
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
