<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\RentalContractInvoice;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Setting;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\InvoiceCalculationService;
use App\Support\InvoiceQrCode;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Category;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\Item;
use Nami\Finance\Models\Sale;
use Nami\Finance\Models\SaleItem;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Models\Unit;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    private const SETTING_DELEGATION_REVENUE_ACCOUNT = 'ingaz_delegation_revenue_account';
    private const SETTING_DELEGATION_INGAZ_ACCOUNT = 'ingaz_delegation_ingaz_account';
    private const SETTING_DELEGATION_COST_ACCOUNT = 'ingaz_delegation_cost_account';
    private const SETTING_DELEGATION_FIXED_COST = 'ingaz_delegation_fixed_cost';
    private const DEFAULT_DELEGATION_FIXED_COST = 137.0;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $invoiceBranchesEnabled = $this->branchesEnabledFor('invoices');
            $rentalBranchesEnabled = $this->branchesEnabledFor('rental_contracts');

            $invoiceRelations = ['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'];
            if ($invoiceBranchesEnabled) {
                $invoiceRelations[] = 'branch';
            }

            $recruitmentInvoicesQuery = Invoice::with($invoiceRelations);
            $this->applyRequestBranchFilter($recruitmentInvoicesQuery, $request, 'invoices');

            $recruitmentInvoices = $recruitmentInvoicesQuery
                ->latest()
                ->get()
                ->map(fn (Invoice $invoice) => $this->formatInvoiceIndexRow($invoice, $invoiceBranchesEnabled));

            $rentalRelations = ['rentalContract.user', 'rentalContract.maid', 'invoiceConstraint'];
            if ($rentalBranchesEnabled) {
                $rentalRelations[] = 'rentalContract.branch';
            }

            $rentalInvoicesQuery = RentalContractInvoice::with($rentalRelations);
            $this->applyRequestBranchFilter($rentalInvoicesQuery, $request, 'rental_contract_invoices');

            $rentalInvoices = $rentalInvoicesQuery
                ->latest()
                ->get()
                ->map(fn (RentalContractInvoice $invoice) => $this->formatRentalInvoiceIndexRow($invoice, $rentalBranchesEnabled));

            $query = $recruitmentInvoices
                ->concat($rentalInvoices)
                ->sortByDesc('created_at_sort')
                ->values();

            return DataTables::of($query)
                ->addIndexColumn()
                ->rawColumns(['invoice_number', 'invoice_source', 'status', 'zatca_status', 'actions'])
                ->make(true);
        }

        $branchesEnabled = $this->branchesEnabledFor('invoices') || $this->branchesEnabledFor('rental_contracts');
        $branches = $branchesEnabled
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();

        return view('admin.ingaz.invoices.index', compact('branchesEnabled', 'branches'));
    }

    private function branchesEnabledFor(string $table): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($table)
            && Schema::hasColumn($table, 'branch_id');
    }

    private function formatInvoiceIndexRow(Invoice $invoice, bool $branchesEnabled = false): array
    {
        $paid = (bool) $invoice->is_paid || (float) $invoice->total_due <= 0;
        $queueButton = (!$invoice->finance_sale_id)
            ? '<form method="post" action="' . route('invoices.queue-zatca-sale', $invoice->id) . '" class="d-inline queue-zatca-form">
                    ' . csrf_field() . '
                    <button type="submit" class="btn btn-sm btn-outline-success" title="إدراج في فواتير الزكاة">
                        <i class="fas fa-receipt"></i>
                    </button>
                </form>'
            : '<a href="' . route('finance.bill-sales.show', $invoice->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="فاتورة المبيعات">
                    <i class="fas fa-link"></i>
                </a>';

        return [
            'id' => $invoice->id,
            'invoice_number' => '<a href="' . route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]) . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">#' . $invoice->id . '</a>',
            'invoice_source' => $this->invoiceSourceBadge($invoice),
            'client_name' => $invoice->contract?->user?->name ?: ($invoice->ingazDelegation?->user?->name ?: ($invoice->client?->name ?: '-')),
            'branch' => $branchesEnabled ? ($invoice->branch?->name ?: '-') : '-',
            'subtotal' => number_format((float) $invoice->subtotal, 2),
            'vat_amount' => number_format((float) $invoice->vat_amount, 2),
            'discount' => number_format((float) $invoice->discount, 2),
            'total_due' => number_format((float) $invoice->total_due, 2),
            'status' => '<span class="badge ' . ($paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning') . '">' . ($paid ? 'مدفوعة' : 'غير مدفوعة') . '</span>',
            'zatca_status' => $this->invoiceZatcaBadge($invoice),
            'created_at' => $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i') : '-',
            'created_at_sort' => optional($invoice->created_at)->timestamp ?? 0,
            'actions' => '<div class="d-flex justify-content-end gap-2">
                            ' . $queueButton . '
                            <a href="' . route('invoices.show', $invoice->id) . '" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="' . route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]) . '" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>',
        ];
    }

    private function formatRentalInvoiceIndexRow(RentalContractInvoice $invoice, bool $branchesEnabled = false): array
    {
        $contract = $invoice->rentalContract;
        $paid = (bool) $invoice->is_paid || (float) $invoice->total_due <= 0;
        $contractUrl = $contract ? route('RentContract.show', $contract->id) : '#';
        $printUrl = route('rental.invoice.download', ['id' => $invoice->id, 'autoprint' => 1]);

        return [
            'id' => 'rental-' . $invoice->id,
            'invoice_number' => '<a href="' . $printUrl . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">إيجار #' . $invoice->id . '</a>',
            'invoice_source' => '<span class="badge bg-success-subtle text-success border">عقد تأجير #' . ($contract?->contract_number ?: $invoice->rental_contract_id) . '</span>',
            'client_name' => $contract?->user?->name ?: '-',
            'branch' => $branchesEnabled ? ($contract?->branch?->name ?: '-') : '-',
            'subtotal' => number_format((float) $invoice->subtotal, 2),
            'vat_amount' => number_format((float) $invoice->vat_amount, 2),
            'discount' => number_format((float) $invoice->discount, 2),
            'total_due' => number_format((float) $invoice->total_due, 2),
            'status' => '<span class="badge ' . ($paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning') . '">' . ($paid ? 'مدفوعة' : 'غير مدفوعة') . '</span>',
            'zatca_status' => '<span class="badge bg-secondary-subtle text-secondary border">غير مطلوب</span>',
            'created_at' => $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i') : '-',
            'created_at_sort' => optional($invoice->created_at)->timestamp ?? 0,
            'actions' => '<div class="d-flex justify-content-end gap-2">
                            <a href="' . $contractUrl . '" class="btn btn-sm btn-outline-primary" title="عرض عقد التأجير">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" title="طباعة الفاتورة">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>',
        ];
    }

    private function invoiceSourceBadge(Invoice $invoice): string
    {
        if ($invoice->contract_id) {
            return '<span class="badge bg-info-subtle text-info border">عقد استقدام #' . $invoice->contract_id . '</span>';
        }

        if ($invoice->ingaz_delegation_id) {
            return '<span class="badge bg-primary-subtle text-primary border">تفويض إنجاز #' . $invoice->ingaz_delegation_id . '</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary border">فاتورة عامة</span>';
    }

    private function invoiceZatcaBadge(Invoice $invoice): string
    {
        if (!$invoice->send_to_zatca) {
            return '<span class="badge bg-secondary-subtle text-secondary border">غير مطلوب</span>';
        }

        if (!$invoice->finance_sale_id) {
            return '<span class="badge bg-warning-subtle text-warning border">لم يدرج</span>';
        }

        return match ($invoice->financeSale?->zatca?->zatca_status) {
            'REPORTED' => '<span class="badge bg-success">مرحل للهيئة</span>',
            'CLEARED' => '<span class="badge bg-primary">معتمد من الهيئة</span>',
            'NOT_REPORTED', 'NOT_CLEARED' => '<span class="badge bg-danger">مرفوض من الهيئة</span>',
            default => '<span class="badge bg-warning-subtle text-warning border">معلق في المبيعات</span>',
        };
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
        $ingazDelegationId = $request->query('ingaz_delegation_id');
        $ingazDelegationId = is_numeric($ingazDelegationId) ? (int) $ingazDelegationId : null;
        $clientId = $request->query('client_id');
        $clientId = is_numeric($clientId) ? (int) $clientId : null;

        $contracts = collect();

        if (!$contractId && !$ingazDelegationId && !$clientId) {
            $contracts = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality'])
                ->whereDoesntHave('invoice')
                ->latest('id')
                ->get();
        }

        $selectedContract = null;
        $selectedIngazDelegation = null;
        $selectedClient = null;

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

        if ($ingazDelegationId) {
            $selectedIngazDelegation = IngazDelegation::query()
                ->with(['user', 'country', 'nationality', 'profession', 'invoice'])
                ->findOrFail($ingazDelegationId);

            if ($selectedIngazDelegation->invoice) {
                return redirect()
                    ->route('invoices.show', $selectedIngazDelegation->invoice->id)
                    ->with('success', 'تم إصدار فاتورة لهذا التفويض مسبقاً.');
            }
        }

        if ($clientId && !$contractId && !$ingazDelegationId) {
            $selectedClient = ClientUser::query()->findOrFail($clientId);
        }

        return view('admin.ingaz.invoices.create', compact(
            'contractId',
            'ingazDelegationId',
            'clientId',
            'contracts',
            'selectedContract',
            'selectedIngazDelegation',
            'selectedClient'
        ));
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
            'contract_id' => 'nullable|required_without_all:ingaz_delegation_id,client_id|exists:contracts,id',
            'ingaz_delegation_id' => 'nullable|required_without_all:contract_id,client_id|exists:ingaz_delegations,id',
            'client_id' => 'nullable|required_without_all:contract_id,ingaz_delegation_id|exists:users,id',
            'general_description' => 'nullable|string|max:2000',
            'general_direct_label' => 'nullable|string|max:255',
            'general_external_label' => 'nullable|string|max:255',
            'general_government_label' => 'nullable|string|max:255',
            'general_ticket_label' => 'nullable|string|max:255',
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
            'send_to_zatca' => 'nullable|boolean',
        ]);

        $referenceCount = collect([
            $request->filled('contract_id'),
            $request->filled('ingaz_delegation_id'),
            $request->filled('client_id'),
        ])->filter()->count();

        if ($referenceCount !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'يجب ربط الفاتورة بعقد أو تفويض إنجاز أو عميل فقط.'
            ], 422);
        }

        $invoiceExists = false;
        if ($request->filled('contract_id')) {
            $invoiceExists = Invoice::where('contract_id', $request->contract_id)->exists();
        } elseif ($request->filled('ingaz_delegation_id')) {
            $invoiceExists = Invoice::where('ingaz_delegation_id', $request->ingaz_delegation_id)->exists();
        }

        if ($invoiceExists) {
            return response()->json([
                'success' => false,
                'message' => $request->filled('contract_id') ? 'هذا العقد لديه فاتورة سابقة' : 'هذا التفويض لديه فاتورة سابقة'
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $service, $yearService, $bondService) {
                $invoicePayload = $this->buildInvoicePayload($request);
                $sendToZatca = $request->has('send_to_zatca') ? $request->boolean('send_to_zatca') : true;

                if ($request->filled('ingaz_delegation_id')) {
                    return $this->storeIngazDelegationInvoice(
                        $request,
                        $invoicePayload,
                        $sendToZatca,
                        $service,
                        $yearService,
                        $bondService
                    );
                }

                if ($request->filled('client_id')) {
                    return $this->storeGeneralClientInvoice(
                        $request,
                        $invoicePayload,
                        $sendToZatca,
                        $service,
                        $yearService,
                        $bondService
                    );
                }

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
                $this->persistMissingNationalityInvoiceCosts($nationality, $invoicePayload);

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
                    'client_id' => null,
                    'insurance_company' => $request->insurance_company ?? null,
                    'general_description' => $request->general_description ?? null,
                    'direct_recruitment_cost' => $invoicePayload['direct_recruitment_cost'],
                    'external_recruitment_cost' => $invoicePayload['external_recruitment_cost'],
                    'government_cost' => $invoicePayload['government_cost'],
                    'internal_ticket_cost' => $invoicePayload['internal_ticket_cost'],
                    'visa_recruited_count' => $invoicePayload['visa_recruited_count'],
                    'direct_recruitment_count' => $invoicePayload['direct_recruitment_count'],
                    'external_recruitment_count' => $invoicePayload['external_recruitment_count'],
                    'insured_count' => $invoicePayload['insured_count'],
                    'government_cost_count' => $invoicePayload['government_cost_count'],
                    'internal_ticket_count' => $invoicePayload['internal_ticket_count'],
                    'subtotal' => $invoicePayload['subtotal'],
                    'discount' => $invoicePayload['discount'],
                    'vat_amount' => $invoicePayload['vat_amount'],
                    'total_due' => $invoicePayload['total_due'],
                    'is_paid' => true,
                    'paid_at' => now(),
                    'send_to_zatca' => $sendToZatca,
                ]);

                $invoice->setRelation('contract', $contract);

                $invoiceConstraint = null;
                $receiptBond = null;

                if ((float) $invoice->total_due > 0) {
                    //2- انشاء قيد الفاتورة التفصيلي وربطه بمركز تكلفة العقد
                    $contractCostCenter = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName);

                    $invoiceConstraint = $this->storeInvoiceConstraintForInvoice(
                        $invoice,
                        $service,
                        $yearService,
                        $nationalityAccountId,
                        $clientAccountId,
                        $contractCostCenter->id
                    );

                    //3- انشاء سند قبض فعلي من حساب العميل إلى حساب مساند.
                    // إذا كان هناك حساب مساند مخصص لإجمالي التحصيلات يُستخدم أولاً، وإلا يتم
                    // الرجوع للحساب الوسيط الحالي حفاظاً على التوافق مع القيود السابقة.
                    $musandAccount = $this->resolveMusanedReceiptAccount();

                    $receiptBond = $this->storeReceiptBondForInvoice(
                        $invoice,
                        $bondService,
                        $yearService,
                        $clientAccountId,
                        (int) $musandAccount->id
                    );
                }

                if ($invoice->send_to_zatca) {
                    $this->queueInvoiceForZatcaSale($invoice);
                }

                return [
                    'invoice' => $invoice,
                    'invoice_constraint' => $invoiceConstraint,
                    'receipt_bond' => $receiptBond,
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        /** @var \App\Models\ingaz\Invoice $invoice */
        $invoice = $result['invoice'];
        /** @var \Nami\Finance\Models\Constraint|null $invoiceConstraint */
        $invoiceConstraint = $result['invoice_constraint'];
        /** @var \Nami\Finance\Models\Bond|null $receiptBond */
        $receiptBond = $result['receipt_bond'];
        /** @var \Nami\Finance\Models\Constraint|null $delegationCostConstraint */
        $delegationCostConstraint = $result['delegation_cost_constraint'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار الفاتورة بنجاح',
            'data' => $invoice,
            'redirect' => route('invoices.show', $invoice->id),
            'view_url' => route('invoices.show', $invoice->id),
            'print_url' => route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]),
            'invoice_constraint_print_url' => $invoiceConstraint ? $this->buildConstraintPrintUrl($invoiceConstraint->id) : null,
            'receipt_bond_print_url' => $receiptBond ? $this->buildReceiptBondPrintUrl($receiptBond->id) : null,
            'receipt_constraint_print_url' => ($receiptBond && $receiptBond->constraint_id) ? $this->buildConstraintPrintUrl((int) $receiptBond->constraint_id) : null,
            'delegation_cost_constraint_print_url' => $delegationCostConstraint ? $this->buildConstraintPrintUrl($delegationCostConstraint->id) : null,
            'index_url' => route('invoices.index'),
            'create_url' => route('invoices.create'),
        ]);
    }

    public function queueZatcaSale(int $id)
    {
        try {
            $invoice = Invoice::query()
                ->with(['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'])
                ->findOrFail($id);

            DB::transaction(function () use ($invoice) {
                $invoice->update(['send_to_zatca' => true]);
                $this->queueInvoiceForZatcaSale($invoice->fresh(['contract.user', 'ingazDelegation.user', 'client']));
            });

            return back()->with('success', 'تم إدراج الفاتورة ضمن فواتير المبيعات المعلقة لهيئة الزكاة.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function searchClients(Request $request)
    {
        $search = trim((string) $request->input('term', $request->input('q', '')));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 20;

        $clients = ClientUser::query()
            ->select(['id', 'name', 'phone', 'phone_code', 'national_num', 'client_type', 'tax_number'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_num', 'like', "%{$search}%")
                        ->orWhere('tax_number', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $clients->getCollection()->map(function ($client) {
                $phone = trim((string) (($client->phone_code ?? '') . ($client->phone ?? '')));
                $documentNumber = $client->searchableDocumentNumber();
                $label = collect([$client->name, $phone, $documentNumber])
                    ->filter()
                    ->implode(' - ');

                return [
                    'id' => $client->id,
                    'text' => $label ?: ('عميل #' . $client->id),
                    'name' => $client->name,
                    'phone' => $phone,
                    'national_num' => $documentNumber,
                    'identity' => $documentNumber,
                    'identity_label' => $client->documentNumberLabel(),
                ];
            })->values(),
            'pagination' => [
                'more' => $clients->hasMorePages(),
            ],
        ]);
    }

    private function queueInvoiceForZatcaSale(Invoice $invoice): Sale
    {
        if ($invoice->finance_sale_id) {
            $sale = Sale::query()->find($invoice->finance_sale_id);
            if ($sale) {
                return $sale;
            }

            $invoice->update(['finance_sale_id' => null]);
        }

        $invoice->loadMissing(['contract.user', 'ingazDelegation.user', 'client']);
        $clientUser = $invoice->contract?->user ?: ($invoice->ingazDelegation?->user ?: $invoice->client);

        if (!$clientUser) {
            throw new \RuntimeException('الفاتورة غير مرتبطة بعميل، لا يمكن إدراجها في فواتير الزكاة.');
        }

        $accountId = $this->ensureClientUserAccountId($clientUser);
        $financeClient = $this->ensureFinanceClientForInvoice($clientUser, $accountId);
        $financeItem = $this->ensureIngazZatcaServiceItem();

        $netTotal = $this->roundMoney(max(0, (float) $invoice->subtotal - (float) $invoice->discount));
        $vatAmount = $this->roundMoney((float) $invoice->vat_amount);
        $finalTotal = $this->roundMoney((float) $invoice->total_due);

        $sale = Sale::query()->create([
            'code' => $this->nextFinanceSaleCode(),
            'client_id' => $financeClient->id,
            'debit_client_id' => $accountId,
            'date' => optional($invoice->created_at)->toDateString() ?: now()->toDateString(),
            'notes' => 'فاتورة إنجاز #' . $invoice->id . ' - ' . $this->buildInvoiceStatement($invoice),
            'total_item' => $netTotal,
            'discount' => 0,
            'final_total' => $finalTotal,
            'total_tax_value' => $vatAmount,
            'type' => SalesTypeEnum::Sales->value,
            'file' => 'no_file',
            'payment_type' => 'cash',
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'item_id' => $financeItem->id,
            'price' => $netTotal,
            'discount_per' => 0,
            'discount_value' => 0,
            'tax_per' => $netTotal > 0 && $vatAmount > 0 ? InvoiceCalculationService::VAT_RATE * 100 : 0,
            'tax_value' => $vatAmount,
            'total_price_before' => $netTotal,
            'final_total' => $finalTotal,
            'purchase_price' => 0,
            'amount' => 1,
            'stock_id' => null,
            'status' => 'new',
        ]);

        $invoice->update([
            'send_to_zatca' => true,
            'finance_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    private function ensureFinanceClientForInvoice(ClientUser $user, int $accountId): Client
    {
        $client = Client::query()
            ->where('account_id', $accountId)
            ->first();

        if ($client) {
            $client->update([
                'is_company' => $user->isCompanyClient() ? 1 : 0,
                'tax_number' => $user->documentNumber() ?? $client->tax_number,
            ]);

            return $client;
        }

        $categoryId = DB::table('fn_client_categories')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('fn_client_categories')->insertGetId([
                'name' => 'عملاء إنجاز',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $phone = trim((string) (($user->phone_code ?? '') . ($user->phone ?? '')));

        return Client::query()->create([
            'name' => $user->name ?: ('عميل #' . $user->id),
            'code' => (string) $user->id,
            'address' => $user->address ?? ' ',
            'phone' => $phone !== '' ? $phone : ' ',
            'email' => $user->email ?? ' ',
            'city' => $user->city ?? ' ',
            'postal_zone' => $user->postal_zone ?? ' ',
            'is_company' => $user->isCompanyClient() ? 1 : 0,
            'tax_number' => $user->documentNumber() ?? ' ',
            'days_credit_period' => 0,
            'credit_limit' => 0,
            'client_category_id' => $categoryId,
            'account_id' => $accountId,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $invoices = $this->exportInvoicesQuery($request)->get();
        $filename = 'ingaz-invoices-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'رقم الفاتورة',
            'المرجع',
            'العميل',
            'الإجمالي',
            'الضريبة',
            'الخصم',
            'المطلوب',
            'حالة السداد',
            'حالة الزكاة',
            'تاريخ الإنشاء',
        ]);

        foreach ($invoices as $invoice) {
            fputcsv($stream, [
                $invoice->id,
                $invoice->contract_id
                    ? ('عقد #' . $invoice->contract_id)
                    : ($invoice->ingaz_delegation_id ? ('تفويض #' . $invoice->ingaz_delegation_id) : 'فاتورة عامة'),
                $invoice->contract?->user?->name ?: ($invoice->ingazDelegation?->user?->name ?: ($invoice->client?->name ?: '-')),
                number_format((float) $invoice->subtotal, 2, '.', ''),
                number_format((float) $invoice->vat_amount, 2, '.', ''),
                number_format((float) $invoice->discount, 2, '.', ''),
                number_format((float) $invoice->total_due, 2, '.', ''),
                ((bool) $invoice->is_paid || (float) $invoice->total_due <= 0) ? 'مدفوعة' : 'غير مدفوعة',
                $this->exportZatcaStatusLabel($invoice),
                optional($invoice->created_at)->format('Y-m-d H:i'),
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

    public function exportPdf(Request $request)
    {
        $invoices = $this->exportInvoicesQuery($request)->get();
        $pdf = PDF::loadView('admin.ingaz.invoices.export-pdf', [
            'invoices' => $invoices,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download('ingaz-invoices-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function exportInvoicesQuery(Request $request)
    {
        $query = Invoice::query()
            ->with(['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'])
            ->latest('id');

        if ($this->branchesEnabledFor('invoices') && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return $query;
    }

    private function exportZatcaStatusLabel(Invoice $invoice): string
    {
        if (!$invoice->send_to_zatca) {
            return 'غير مطلوب';
        }

        if (!$invoice->finance_sale_id) {
            return 'لم يدرج';
        }

        return match ($invoice->financeSale?->zatca?->zatca_status) {
            'REPORTED' => 'مرحل للهيئة',
            'CLEARED' => 'معتمد من الهيئة',
            'NOT_REPORTED', 'NOT_CLEARED' => 'مرفوض من الهيئة',
            default => 'معلق في المبيعات',
        };
    }

    private function ensureIngazZatcaServiceItem(): Item
    {
        $item = Item::query()
            ->where('code_string', 'INGAZ-ZATCA-INVOICE')
            ->first();

        if ($item) {
            return $item;
        }

        $categoryId = $this->ensureZatcaServiceCategoryId();
        $unitId = $this->ensureZatcaServiceUnitId();
        $accountId = $this->ensureZatcaServiceSalesAccountId();

        return Item::query()->create([
            'name' => 'خدمة فاتورة إنجاز',
            'main_category_id' => $categoryId,
            'sub_category_id' => null,
            'type' => CategoryTypes::Service->value,
            'code_string' => 'INGAZ-ZATCA-INVOICE',
            'code' => ((int) Item::query()->max('code')) + 1,
            'min_order' => 0,
            'sale_unit_id' => $unitId,
            'sale_price' => 0,
            'purchase_unit_id' => $unitId,
            'purchase_price' => 0,
            'details' => 'صنف خدمي تلقائي لفواتير إنجاز المرحلة إلى هيئة الزكاة.',
            'sale_account_id' => $accountId,
            'stock_account_id' => $accountId,
            'stock_tracking' => 0,
            'show_pos' => 0,
            'show_website' => 0,
        ]);
    }

    private function ensureZatcaServiceCategoryId(): int
    {
        if (Schema::hasColumn('fn_categories', 'code')) {
            $category = Category::query()
                ->where('code', 'INGAZ-ZATCA-SERVICE')
                ->orderBy('id')
                ->first();

            if (!$category) {
                $category = Category::query()
                    ->where('title', 'خدمات إنجاز')
                    ->orderBy('id')
                    ->first();
            }
        } else {
            $category = Category::query()
                ->where('title', 'خدمات إنجاز')
                ->orderBy('id')
                ->first();
        }

        if (!$category) {
            $category = new Category();
            $categoryData = [
                'title' => 'خدمات إنجاز',
                'parent_id' => null,
            ];

            if (Schema::hasColumn('fn_categories', 'code')) {
                $categoryData['code'] = 'INGAZ-ZATCA-SERVICE';
            }

            $category->forceFill($categoryData);
            $category->save();
        } elseif (Schema::hasColumn('fn_categories', 'code') && !$category->code) {
            $category->forceFill(['code' => 'INGAZ-ZATCA-SERVICE'])->save();
        }

        return (int) $category->id;
    }

    private function ensureZatcaServiceUnitId(): int
    {
        $unit = Unit::query()->orderBy('id')->first();

        if (!$unit) {
            $unit = Unit::query()->create([
                'title' => 'خدمة',
                'parent_id' => null,
                'value' => 1,
            ]);
        }

        return (int) $unit->id;
    }

    private function ensureZatcaServiceSalesAccountId(): int
    {
        $account = Account::query()
            ->where('type', AccountTypesEnum::Sales->value)
            ->orderBy('id')
            ->first();

        if (!$account) {
            $parent = Account::query()
                ->whereIn('type', [AccountTypesEnum::GeneralIncome->value, AccountTypesEnum::CompanyIncome->value])
                ->orderBy('id')
                ->first();

            $account = Account::query()->create([
                'name' => 'إيرادات خدمات إنجاز',
                'status' => AccountStatusEnum::Creditor->value,
                'level' => $parent ? ((int) $parent->level + 1) : 1,
                'code' => $this->nextAccountCode($parent),
                'type' => AccountTypesEnum::Sales->value,
                'parent_id' => $parent?->id,
            ]);
        }

        return (int) $account->id;
    }

    private function nextAccountCode(?Account $parent): int
    {
        if ($parent) {
            $maxChildCode = Account::query()
                ->where('parent_id', $parent->id)
                ->max('code');

            return $maxChildCode ? ((int) $maxChildCode + 1) : (((int) $parent->code * 10) + 1);
        }

        return ((int) Account::query()->max('code')) + 1;
    }

    private function nextFinanceSaleCode(): int
    {
        return ((int) Sale::query()
            ->where('type', SalesTypeEnum::Sales->value)
            ->max('code')) + 1;
    }

    private function storeInvoiceConstraintForInvoice(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $nationalityAccountId,
        int $clientAccountId,
        int $costCenterId
    ): ?Constraint
    {
        $amounts = $this->buildInvoiceFinanceBreakdown($invoice);
        if ($amounts['total_due'] <= 0) {
            return null;
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
            return null;
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

        return $storeConstraint;
    }

    private function storeReceiptBondForInvoice(
        Invoice $invoice,
        BondService $bondService,
        YearService $yearService,
        int $clientAccountId,
        int $musanedAccountId
    ): Bond
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

        $bond = $bond->fresh();

        // سند القبض الناتج من إصدار الفاتورة يجب أن يولد قيده مباشرة،
        // بغض النظر عن إعدادات تأكيد سندات القبض اليدوية.
        if ((int) $bond->is_confirm !== 1 || !$bond->constraint_id) {
            $bondService->confirmBond($bond->id);
        }

        return $bond->fresh();
    }

    private function storeIngazDelegationInvoice(
        Request $request,
        array $invoicePayload,
        bool $sendToZatca,
        ObjService $service,
        YearService $yearService,
        BondService $bondService
    ): array
    {
        $delegation = IngazDelegation::query()
            ->with(['user', 'country', 'nationality', 'profession'])
            ->lockForUpdate()
            ->findOrFail($request->ingaz_delegation_id);

        if ($delegation->invoice()->exists()) {
            throw new \RuntimeException('هذا التفويض لديه فاتورة سابقة');
        }

        if (!$delegation->user) {
            throw new \RuntimeException('التفويض غير مرتبط بعميل، لا يمكن إصدار الفاتورة.');
        }

        $clientAccountId = $this->ensureClientUserAccountId($delegation->user);
        $this->persistMissingNationalityInvoiceCosts($delegation->nationality, $invoicePayload);

        $invoice = Invoice::create([
            'contract_id' => null,
            'ingaz_delegation_id' => $delegation->id,
            'client_id' => null,
            'insurance_company' => $request->insurance_company ?? null,
            'general_description' => $request->general_description ?? null,
            'direct_recruitment_cost' => $invoicePayload['direct_recruitment_cost'],
            'external_recruitment_cost' => $invoicePayload['external_recruitment_cost'],
            'government_cost' => $invoicePayload['government_cost'],
            'internal_ticket_cost' => $invoicePayload['internal_ticket_cost'],
            'visa_recruited_count' => $invoicePayload['visa_recruited_count'],
            'direct_recruitment_count' => $invoicePayload['direct_recruitment_count'],
            'external_recruitment_count' => $invoicePayload['external_recruitment_count'],
            'insured_count' => $invoicePayload['insured_count'],
            'government_cost_count' => $invoicePayload['government_cost_count'],
            'internal_ticket_count' => $invoicePayload['internal_ticket_count'],
            'subtotal' => $invoicePayload['subtotal'],
            'discount' => $invoicePayload['discount'],
            'vat_amount' => $invoicePayload['vat_amount'],
            'total_due' => $invoicePayload['total_due'],
            'is_paid' => true,
            'paid_at' => now(),
            'send_to_zatca' => $sendToZatca,
        ]);

        $invoice->setRelation('ingazDelegation', $delegation);

        $invoiceConstraint = null;
        $receiptBond = null;
        $delegationCostConstraint = null;

        if ((float) $invoice->total_due > 0) {
            $invoiceConstraint = $this->storeInvoiceConstraintForIngazDelegation(
                $invoice,
                $service,
                $yearService,
                $clientAccountId
            );

            $receiptBond = $this->storeReceiptBondForInvoice(
                $invoice,
                $bondService,
                $yearService,
                $clientAccountId,
                (int) $this->resolveMusanedReceiptAccount()->id
            );

            $delegationCostConstraint = $this->storeIngazDelegationFixedCostConstraint(
                $invoice,
                $service,
                $yearService
            );
        }

        if ($delegationCostConstraint) {
            $invoice->update([
                'ingaz_delegation_cost_constraint_id' => $delegationCostConstraint->id,
            ]);
        }

        if ($invoice->send_to_zatca) {
            $this->queueInvoiceForZatcaSale($invoice);
        }

        return [
            'invoice' => $invoice,
            'invoice_constraint' => $invoiceConstraint,
            'receipt_bond' => $receiptBond,
            'delegation_cost_constraint' => $delegationCostConstraint,
        ];
    }

    private function storeGeneralClientInvoice(
        Request $request,
        array $invoicePayload,
        bool $sendToZatca,
        ObjService $service,
        YearService $yearService,
        BondService $bondService
    ): array
    {
        $client = ClientUser::query()
            ->lockForUpdate()
            ->findOrFail($request->client_id);

        $clientAccountId = $this->ensureClientUserAccountId($client);

        $invoice = Invoice::create([
            'contract_id' => null,
            'ingaz_delegation_id' => null,
            'client_id' => $client->id,
            'insurance_company' => $request->insurance_company ?? null,
            'general_description' => $request->general_description ?? null,
            'general_direct_label' => $this->normalizeGeneralItemLabel($request->general_direct_label, 'بند خدمة خاضع للضريبة'),
            'general_external_label' => $this->normalizeGeneralItemLabel($request->general_external_label, 'بند خدمة غير خاضع للضريبة'),
            'general_government_label' => $this->normalizeGeneralItemLabel($request->general_government_label, 'رسوم حكومية'),
            'general_ticket_label' => $this->normalizeGeneralItemLabel($request->general_ticket_label, 'بند إضافي خاضع للضريبة'),
            'direct_recruitment_cost' => $invoicePayload['direct_recruitment_cost'],
            'external_recruitment_cost' => $invoicePayload['external_recruitment_cost'],
            'government_cost' => $invoicePayload['government_cost'],
            'internal_ticket_cost' => $invoicePayload['internal_ticket_cost'],
            'visa_recruited_count' => $invoicePayload['visa_recruited_count'],
            'direct_recruitment_count' => $invoicePayload['direct_recruitment_count'],
            'external_recruitment_count' => $invoicePayload['external_recruitment_count'],
            'insured_count' => $invoicePayload['insured_count'],
            'government_cost_count' => $invoicePayload['government_cost_count'],
            'internal_ticket_count' => $invoicePayload['internal_ticket_count'],
            'subtotal' => $invoicePayload['subtotal'],
            'discount' => $invoicePayload['discount'],
            'vat_amount' => $invoicePayload['vat_amount'],
            'total_due' => $invoicePayload['total_due'],
            'is_paid' => true,
            'paid_at' => now(),
            'send_to_zatca' => $sendToZatca,
        ]);

        $invoice->setRelation('client', $client);

        $invoiceConstraint = null;
        $receiptBond = null;

        if ((float) $invoice->total_due > 0) {
            $invoiceConstraint = $this->storeInvoiceConstraintForGeneralInvoice(
                $invoice,
                $service,
                $yearService,
                $clientAccountId
            );

            $receiptBond = $this->storeReceiptBondForInvoice(
                $invoice,
                $bondService,
                $yearService,
                $clientAccountId,
                (int) $this->resolveGeneralInvoiceReceiptAccount()->id
            );
        }

        if ($invoice->send_to_zatca) {
            $this->queueInvoiceForZatcaSale($invoice);
        }

        return [
            'invoice' => $invoice,
            'invoice_constraint' => $invoiceConstraint,
            'receipt_bond' => $receiptBond,
        ];
    }

    private function storeInvoiceConstraintForIngazDelegation(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $clientAccountId
    ): ?Constraint
    {
        $amounts = $this->buildInvoiceFinanceBreakdown($invoice);
        if ($amounts['total_due'] <= 0) {
            return null;
        }

        $revenueAccountId = $this->resolveSettingAccountId(
            self::SETTING_DELEGATION_REVENUE_ACCOUNT,
            'حساب إيراد تفاويض إنجاز غير محدد. الرجاء تحديده من صفحة إعدادات الحسابات أولاً.',
            'creditor'
        );
        $statement = $this->buildInvoiceStatement($invoice);
        $lines = [];

        $netRevenueAmount = $this->roundMoney($amounts['direct_amount'] + $amounts['other_amount']);

        if ($netRevenueAmount > 0) {
            $this->appendConstraintPair(
                $lines,
                $revenueAccountId,
                $clientAccountId,
                $netRevenueAmount,
                $statement
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

        return $this->storeConstraintFromLines($service, $yearService, $invoice, $statement, $lines, $amounts['total_due']);
    }

    private function storeInvoiceConstraintForGeneralInvoice(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $clientAccountId
    ): ?Constraint
    {
        $amounts = $this->buildInvoiceFinanceBreakdown($invoice);
        if ($amounts['total_due'] <= 0) {
            return null;
        }

        $revenueAccountId = $this->resolveSettingAccountId(
            self::SETTING_DELEGATION_REVENUE_ACCOUNT,
            'حساب إيراد الفواتير العامة غير محدد. الرجاء تحديد حساب إيراد تفاويض إنجاز من صفحة إعدادات الحسابات أولاً.',
            'creditor'
        );
        $statement = $this->buildInvoiceStatement($invoice);
        $lines = [];
        $netRevenueAmount = $this->roundMoney($amounts['direct_amount'] + $amounts['other_amount']);

        if ($netRevenueAmount > 0) {
            $this->appendConstraintPair(
                $lines,
                $revenueAccountId,
                $clientAccountId,
                $netRevenueAmount,
                $statement
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

        return $this->storeConstraintFromLines($service, $yearService, $invoice, $statement, $lines, $amounts['total_due']);
    }

    private function storeIngazDelegationFixedCostConstraint(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService
    ): ?Constraint
    {
        $amount = $this->resolveDelegationFixedCostAmount();
        if ($amount <= 0) {
            return null;
        }

        $ingazAccountId = $this->resolveSettingAccountId(
            self::SETTING_DELEGATION_INGAZ_ACCOUNT,
            'حساب إنجاز غير محدد. الرجاء تحديده من صفحة إعدادات الحسابات أولاً.',
            'creditor'
        );
        $costAccountId = $this->resolveSettingAccountId(
            self::SETTING_DELEGATION_COST_ACCOUNT,
            'حساب تكلفة تفاويض إنجاز غير محدد. الرجاء تحديده من صفحة إعدادات الحسابات أولاً.',
            'debtor'
        );
        $statement = $this->buildDelegationFixedCostStatement($invoice);
        $lines = [];

        $this->appendConstraintPair(
            $lines,
            $ingazAccountId,
            $costAccountId,
            $amount,
            $statement
        );

        return $this->storeConstraintFromLines($service, $yearService, $invoice, $statement, $lines, $amount);
    }

    private function storeConstraintFromLines(
        ObjService $service,
        YearService $yearService,
        Invoice $invoice,
        string $statement,
        array $lines,
        float $value
    ): ?Constraint
    {
        if (empty($lines)) {
            return null;
        }

        $date = $invoice->created_at ? $invoice->created_at->toDateString() : now()->toDateString();
        $activeYear = $yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة للتاريخ المحدد');
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
            'value' => $this->roundMoney($value),
        ];

        $storeConstraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $storeConstraint->id, $data);
        $service->storeFiles($storeConstraint, $data);

        return $storeConstraint;
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
            'ingazDelegation.user',
            'ingazDelegation.country',
            'ingazDelegation.nationality',
            'ingazDelegation.profession',
            'client',
            'musanedTransferItem.transfer',
            'financeSale.zatca',
        ])->findOrFail($id);
        $this->assertCanAccessBranchModel($invoice, 'invoices');

        $settings = Setting::query()->first();
        $documentBranding = documentBranding();
        $settingsLogo = documentLogoUrl();
        $settingsZatcaLogo = ($settings && $settings->zatch_logo) ? showFile($settings->zatch_logo) : null;

        $supplierName = (string) documentSetting('document_company_name', $settings?->website_name ?: config('app.name'));
        $supplierAddress = (string) documentSetting('location', 'الرياض، المملكة العربية السعودية');
        $supplierPhone = $this->printableSetting('phone', '-');
        $supplierOtherPhone = $this->printableSetting('other_phone', '-');
        $supplierEmail = $this->printableSetting('email', '-');
        $supplierTaxNumber = $this->printableSetting('tax_number', '314232024200003');
        $supplierLogo = $settingsLogo ?: $documentBranding['logo_url'];
        $zatcaLogo = $settingsZatcaLogo ?: asset('site/assets/images/zatca-logo.png');
        $invoiceTitle = (string) documentSetting('invoice_title', 'فاتورة ضريبية مبسطة');
        $invoiceSubtitle = (string) documentSetting('invoice_subtitle', 'Simplified Tax Invoice');
        $invoiceFooterText = (string) replaceDocumentPlaceholders(documentSetting('invoice_footer_text', ''));

        $serviceDesc = $invoice->ingaz_delegation_id ? 'تفويض إنجاز' : ($invoice->client_id ? 'فاتورة عامة' : 'طلب تعاقد');
        $serviceLongDescription = $this->buildServiceDescription($invoice);
        $items = $this->buildInvoiceItems($invoice);
        $invoiceStatus = ((bool) $invoice->is_paid || (float) $invoice->total_due <= 0) ? 'مدفوعة' : 'غير مدفوعة';
        $printedBy = optional(Auth::guard('admin')->user())->name
            ?? optional(Auth::user())->name
            ?? 'النظام';
        $printedAt = now();

        $client = $invoice->contract?->user ?: ($invoice->ingazDelegation?->user ?: $invoice->client);
        $customerName = $client?->name ?: '---';
        $customerDocumentLabel = $client?->documentNumberLabel() ?: __('ingaz.national_num');
        $customerNationalId = $client?->documentNumber() ?: '---';
        $customerPhone = $this->formatPhone((string) ($client?->phone_code ?? ''), (string) ($client?->phone ?? ''));
        $customerAddress = $client?->address ?: '---';
        $accountNumber = $client?->account_id ?: ($client?->id ?: ($invoice->contract_id ?: $invoice->ingaz_delegation_id));

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
        $invoiceBarcodeValue = $this->buildInvoiceQrSerial(
            $supplierName,
            $supplierTaxNumber,
            $invoiceDateTime,
            $grandTotal,
            $vatAmount
        );
        $qrImage = InvoiceQrCode::render($invoiceBarcodeValue);

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
            'invoiceTitle' => $invoiceTitle,
            'invoiceSubtitle' => $invoiceSubtitle,
            'invoiceFooterText' => $invoiceFooterText,
            'zatcaLogo' => $zatcaLogo,
            'qrImage' => $qrImage,
            'invoiceBarcodeValue' => $invoiceBarcodeValue,
            'serviceDesc' => $serviceDesc,
            'serviceLongDescription' => $serviceLongDescription,
            'items' => $items,
            'invoiceStatus' => $invoiceStatus,
            'printedBy' => $printedBy,
            'printedAt' => $printedAt,
            'customerName' => $customerName,
            'customerDocumentLabel' => $customerDocumentLabel,
            'customerNationalId' => $customerNationalId,
            'customerPhone' => $customerPhone,
            'customerAddress' => $customerAddress,
            'accountNumber' => $accountNumber,
            'matchedHijriDate' => $matchedHijriDate,
            'invoiceDate' => $invoiceDate,
            'invoiceDateTime' => $invoiceDateTime,
            'invoiceUrl' => route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]),
            'subtotalBeforeDiscount' => $subtotalBeforeDiscount,
            'discountTotal' => $discountTotal,
            'netSubtotal' => $netSubtotal,
            'vatAmount' => $vatAmount,
            'grandTotal' => $grandTotal,
        ];
    }

    private function buildInvoiceQrSerial(
        string $sellerName,
        string $vatNumber,
        string $invoiceDateTime,
        float $totalWithVat,
        float $vatAmount
    ): string
    {
        $fields = [
            1 => $sellerName,
            2 => $vatNumber,
            3 => date('Y-m-d\TH:i:s\Z', strtotime($invoiceDateTime)),
            4 => number_format($totalWithVat, 2, '.', ''),
            5 => number_format($vatAmount, 2, '.', ''),
        ];

        $tlv = '';
        foreach ($fields as $tag => $value) {
            $value = (string) $value;
            $tlv .= chr($tag) . chr(strlen($value)) . $value;
        }

        return base64_encode($tlv);
    }

    private function buildInvoiceItems(Invoice $invoice): array
    {
        $isGeneralInvoice = $invoice->client_id && !$invoice->contract_id && !$invoice->ingaz_delegation_id;
        $generalLabels = $this->generalInvoiceItemLabels($invoice);
        $items = [
            [
                'key' => 'direct',
                'label' => $isGeneralInvoice ? $generalLabels['direct'] : 'تكاليف الاستقدام المباشر',
                'qty' => (int) $invoice->direct_recruitment_count,
                'unit' => (float) $invoice->direct_recruitment_cost,
            ],
            [
                'key' => 'external',
                'label' => $isGeneralInvoice ? $generalLabels['external'] : 'تكاليف الاستقدام الخارجية',
                'qty' => (int) $invoice->external_recruitment_count,
                'unit' => (float) $invoice->external_recruitment_cost,
            ],
            [
                'key' => 'government',
                'label' => $isGeneralInvoice ? $generalLabels['government'] : 'تكاليف حكومية',
                'qty' => (int) $invoice->government_cost_count,
                'unit' => (float) $invoice->government_cost,
            ],
            [
                'key' => 'ticket',
                'label' => $isGeneralInvoice ? $generalLabels['ticket'] : 'تكلفة التذكرة الداخلية',
                'qty' => (int) $invoice->internal_ticket_count,
                'unit' => (float) $invoice->internal_ticket_cost,
            ],
        ];

        foreach ($items as &$item) {
            $item['line_subtotal'] = $this->roundMoney($item['qty'] * $item['unit']);
            $item['line_discount'] = 0.0;
            $item['line_total'] = $this->roundMoney($item['line_subtotal'] - $item['line_discount']);
        }
        unset($item);

        $vatDistribution = $this->invoiceCalculationService()->distributeStoredVat(
            (float) $invoice->vat_amount,
            [
                'direct' => $items[0]['line_total'],
                'ticket' => $items[3]['line_total'],
            ]
        );

        foreach ($items as &$item) {
            $item['vat_amount'] = $vatDistribution[$item['key']] ?? 0.0;
            $item['vat_rate'] = $item['vat_amount'] > 0 ? InvoiceCalculationService::VAT_RATE : 0.0;
            $item['line_grand_total'] = $this->roundMoney($item['line_total'] + $item['vat_amount']);
        }
        unset($item);

        return array_values(array_filter($items, function ($item) {
            return $item['line_grand_total'] > 0;
        }));
    }

    private function generalInvoiceItemLabels(Invoice $invoice): array
    {
        return [
            'direct' => $this->normalizeGeneralItemLabel($invoice->general_direct_label ?? null, 'بند خدمة خاضع للضريبة'),
            'external' => $this->normalizeGeneralItemLabel($invoice->general_external_label ?? null, 'بند خدمة غير خاضع للضريبة'),
            'government' => $this->normalizeGeneralItemLabel($invoice->general_government_label ?? null, 'رسوم حكومية'),
            'ticket' => $this->normalizeGeneralItemLabel($invoice->general_ticket_label ?? null, 'بند إضافي خاضع للضريبة'),
        ];
    }

    private function normalizeGeneralItemLabel(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function buildInvoicePayload(Request $request): array
    {
        $calculator = $this->invoiceCalculationService();

        $payload = [
            'direct_recruitment_cost' => $calculator->normalizeMoney($request->direct_recruitment_cost ?? 0),
            'external_recruitment_cost' => $calculator->normalizeMoney($request->external_recruitment_cost ?? 0),
            'government_cost' => $calculator->normalizeMoney($request->government_cost ?? 0),
            'internal_ticket_cost' => $calculator->normalizeMoney($request->internal_ticket_cost ?? 0),
            'visa_recruited_count' => $calculator->normalizeCount($request->visa_recruited_count ?? 0),
            'direct_recruitment_count' => $calculator->normalizeCount($request->direct_recruitment_count ?? 0),
            'external_recruitment_count' => $calculator->normalizeCount($request->external_recruitment_count ?? 0),
            'insured_count' => $calculator->normalizeCount($request->insured_count ?? 0),
            'government_cost_count' => $calculator->normalizeCount($request->government_cost_count ?? 0),
            'internal_ticket_count' => $calculator->normalizeCount($request->internal_ticket_count ?? 0),
            'discount' => $calculator->normalizeMoney($request->discount ?? 0),
        ];

        $totals = $calculator->calculateTotals($payload);

        return array_merge($payload, [
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'vat_amount' => $totals['vat_amount'],
            'total_due' => $totals['total_due'],
        ]);
    }

    private function persistMissingNationalityInvoiceCosts(?Nationality $nationality, array $invoicePayload): void
    {
        if (!$nationality || !Schema::hasTable('nationalities')) {
            return;
        }

        $columns = [
            'direct_recruitment_cost',
            'external_recruitment_cost',
            'government_cost',
        ];

        $updates = [];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('nationalities', $column)) {
                continue;
            }

            $currentValue = $nationality->{$column};
            $incomingValue = (float) ($invoicePayload[$column] ?? 0);

            if (($currentValue === null || (float) $currentValue <= 0) && $incomingValue > 0) {
                $updates[$column] = $incomingValue;
            }
        }

        if ($updates !== []) {
            $nationality->forceFill($updates)->save();
        }
    }

    private function buildServiceDescription(Invoice $invoice): string
    {
        $contract = $invoice->contract;
        if (!$contract) {
            $delegation = $invoice->ingazDelegation;
            if (!$delegation) {
                $clientName = $invoice->client?->name ?: 'غير محدد';
                $description = trim((string) ($invoice->general_description ?? ''));

                return 'فاتورة عامة - العميل: ' . $clientName
                    . ' - رقم الفاتورة: ' . $invoice->id
                    . ($description !== '' ? ' - التفاصيل: ' . $description : '');
            }

            $clientName = $delegation->user?->name ?: 'غير محدد';
            $jobTitle = $delegation->profession?->title ?: 'غير محدد';
            $country = $delegation->country?->title
                ?: ($delegation->country?->name_ar
                    ?: ($delegation->nationality?->sub_title ?: ($delegation->nationality?->title ?: 'غير محدد')));

            return 'تفويض إنجاز - العميل: ' . $clientName
                . ' - رقم التفويض: ' . $delegation->id
                . ' - المهنة: ' . $jobTitle
                . ' - الدولة: ' . $country
                . ' - نوع التأشيرة: ' . ($delegation->visa_type ?: 'غير محدد')
                . ' - رقم التأشيرة: ' . ($delegation->visa_number ?: '-')
                . ' - رقم إنجاز: ' . ($delegation->ingaz_number ?: '-')
                . ' - رقم مساند: ' . ($delegation->msaned_contract_number ?: '-');
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
        if ($invoice->ingaz_delegation_id) {
            $delegation = $invoice->ingazDelegation;
            $ingazNumber = $delegation?->ingaz_number ?: '-';
            $msanedNumber = $delegation?->msaned_contract_number ?: '-';

            return "قيد فاتورة تفويض إنجاز #{$invoice->ingaz_delegation_id} / الفاتورة #{$invoice->id} / إنجاز {$ingazNumber} / مساند {$msanedNumber}";
        }

        if ($invoice->client_id) {
            $clientName = $invoice->client?->name ?: 'غير محدد';
            $description = trim((string) ($invoice->general_description ?? ''));
            $itemsSummary = $this->buildInvoiceItemsSummary($invoice);

            return "قيد فاتورة عامة #{$invoice->id} / العميل: {$clientName}"
                . ($description !== '' ? " / التفاصيل: {$description}" : '')
                . ($itemsSummary !== '' ? " / البنود: {$itemsSummary}" : '');
        }

        $contractId = $invoice->contract_id;
        $msanedNumber = $invoice->contract?->info?->msaned_number
            ?? $invoice->contract?->msaned_number
            ?? '-';

        return "قيد فاتورة طلب استقدام للعقد #{$contractId} / الفاتورة #{$invoice->id} / مساند {$msanedNumber}";
    }

    private function buildInvoiceReceiptStatement(Invoice $invoice): string
    {
        if ($invoice->ingaz_delegation_id) {
            $delegation = $invoice->ingazDelegation;
            $ingazNumber = $delegation?->ingaz_number ?: '-';
            $msanedNumber = $delegation?->msaned_contract_number ?: '-';

            return "سند قبض سداد فاتورة تفويض إنجاز #{$invoice->ingaz_delegation_id} / الفاتورة #{$invoice->id} / إلى حساب مساند / إنجاز {$ingazNumber} / مساند {$msanedNumber}";
        }

        if ($invoice->client_id) {
            $clientName = $invoice->client?->name ?: 'غير محدد';
            $description = trim((string) ($invoice->general_description ?? ''));
            $itemsSummary = $this->buildInvoiceItemsSummary($invoice);

            return "سند قبض فاتورة عامة #{$invoice->id} / العميل: {$clientName}"
                . ($description !== '' ? " / التفاصيل: {$description}" : '')
                . ($itemsSummary !== '' ? " / البنود: {$itemsSummary}" : '');
        }

        $contractId = $invoice->contract_id;
        $msanedNumber = $invoice->contract?->info?->msaned_number
            ?? $invoice->contract?->msaned_number
            ?? '-';

        return "سند قبض سداد فاتورة طلب استقدام للعقد #{$contractId} / الفاتورة #{$invoice->id} / إلى حساب مساند الوسيط / مساند {$msanedNumber}";
    }

    private function buildDelegationFixedCostStatement(Invoice $invoice): string
    {
        $delegation = $invoice->ingazDelegation;
        $ingazNumber = $delegation?->ingaz_number ?: '-';

        return "قيد تكلفة تفويض إنجاز #{$invoice->ingaz_delegation_id} / الفاتورة #{$invoice->id} / إنجاز {$ingazNumber}";
    }

    private function buildInvoiceItemsSummary(Invoice $invoice): string
    {
        return collect($this->buildInvoiceItems($invoice))
            ->map(function (array $item) {
                return $item['label'] . ' (' . $item['qty'] . ' × ' . number_format((float) $item['unit'], 2) . ')';
            })
            ->implode('، ');
    }

    private function buildConstraintPrintUrl(int $constraintId): string
    {
        return route('finance.add-constraint.show', [
            'add_constraint' => $constraintId,
            'autoprint' => 1,
        ]);
    }

    private function buildReceiptBondPrintUrl(int $bondId): string
    {
        return route('finance.bonds-print.show', [
            'bonds_print' => $bondId,
            'type' => 'qabd',
        ]);
    }

    private function resolveGeneralInvoiceReceiptAccount(): Account
    {
        $accountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::BondQabdAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($accountId) {
            $account = Account::query()
                ->whereKey((int) $accountId)
                ->where('status', AccountStatusEnum::Debtor->value)
                ->first();

            if ($account) {
                return $account;
            }
        }

        $account = Account::query()
            ->whereIn('type', [AccountTypesEnum::Box->value, AccountTypesEnum::Bank->value])
            ->where('status', AccountStatusEnum::Debtor->value)
            ->orderByRaw('type = ? desc', [AccountTypesEnum::Box->value])
            ->orderBy('code')
            ->first();

        if ($account) {
            return $account;
        }

        throw new \RuntimeException(
            'لا يوجد حساب قبض عام صالح للفواتير العامة. الرجاء تحديد حساب قبض عام أو إنشاء صندوق/بنك في الحسابات.'
        );
    }

    private function resolveMusanedReceiptAccount(): Account
    {
        $directAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($directAccountId) {
            $account = $this->findValidMusanedAccount((int) $directAccountId);
            if ($account) {
                return $account;
            }

            throw new \RuntimeException(
                'حساب مساند المحدد غير صحيح. يجب اختيار حساب تابع للبنوك أو لأرصدة مدينة أخرى من إعدادات النظام المحاسبي.'
            );
        }

        $intermediateAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedIntermediateAccount->value)
            ->latest('updated_at')
            ->value('value');

        if (!$intermediateAccountId) {
            throw new \RuntimeException(
                'لا يوجد حساب مساند أو حساب وسيط مساند محدد. الرجاء تحديده من صفحة إعدادات النظام المحاسبي.'
            );
        }

        $account = $this->findValidMusanedAccount((int) $intermediateAccountId);

        if (!$account) {
            throw new \RuntimeException(
                'الحساب الوسيط لمساند المحدد غير صحيح. يجب اختيار حساب تابع للبنوك أو لأرصدة مدينة أخرى من إعدادات النظام المحاسبي.'
            );
        }

        return $account;
    }

    private function findValidMusanedAccount(int $accountId): ?Account
    {
        return Account::query()
            ->whereKey($accountId)
            ->whereIn('parent_id', [122, 156])
            ->first();
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

        $accountId = $this->ensureClientUserAccountId($user);

        if ($contract->relationLoaded('user') && $contract->user) {
            $contract->user->account_id = $accountId;
        }

        return $accountId;
    }

    private function ensureClientUserAccountId(ClientUser $user): int
    {
        $user = ClientUser::query()
            ->lockForUpdate()
            ->find($user->id);

        if (!$user) {
            throw new \RuntimeException('العميل غير موجود.');
        }

        if ($user->account_id) {
            $existingAccount = Account::query()
                ->lockForUpdate()
                ->find($user->account_id);

            if ($existingAccount) {
                return (int) $existingAccount->id;
            }

            $user->update([
                'account_id' => null,
            ]);
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

    private function invoiceCalculationService(): InvoiceCalculationService
    {
        return app(InvoiceCalculationService::class);
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

    private function resolveSettingAccountId(string $settingTitle, string $missingMessage, ?string $status = null): int
    {
        $accountId = ConfirmSetting::query()
            ->where('title', $settingTitle)
            ->latest('updated_at')
            ->value('value');

        if (!$accountId) {
            throw new \RuntimeException($missingMessage);
        }

        $query = Account::query()->whereKey((int) $accountId);

        if ($status) {
            $query->where('status', $status);
        }

        $account = $query->first();
        if (!$account) {
            throw new \RuntimeException($missingMessage);
        }

        return (int) $account->id;
    }

    private function resolveDelegationFixedCostAmount(): float
    {
        $amount = ConfirmSetting::query()
            ->where('title', self::SETTING_DELEGATION_FIXED_COST)
            ->latest('updated_at')
            ->value('value');

        if ($amount === null || $amount === '') {
            return self::DEFAULT_DELEGATION_FIXED_COST;
        }

        return $this->roundMoney((float) $amount);
    }

    private function printableSetting(string $key, string $default = '-'): string
    {
        $value = trim((string) documentSetting($key, ''));

        return $value === '' || $value === '#' ? $default : $value;
    }
}
