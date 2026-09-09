<?php

namespace App\Http\Controllers\ingaz;

use App\Support\BranchReportFilter;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\admin\Controller;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\RentalContractInvoice;
use App\Models\RentalFinancialAdjustment;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Setting;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\InvoiceCalculationService;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\AutomaticJournalEntries;
use App\Support\RecruitmentRevenueRecognition;
use App\Support\InvoiceQrCode;
use App\Support\IngazDelegationTimeline;
use App\Support\InvoiceAuthorization;
use App\Support\RentalContractPricing;
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
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\Item;
use Nami\Finance\Models\Sale;
use Nami\Finance\Models\SaleItem;
use Nami\Finance\Models\Unit;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    private const SETTING_GENERAL_INVOICE_REVENUE_ACCOUNT = 'general_invoice_revenue_account';
    private const SETTING_DELEGATION_REVENUE_ACCOUNT = 'ingaz_delegation_revenue_account';
    private const SETTING_DELEGATION_INGAZ_ACCOUNT = 'ingaz_delegation_ingaz_account';
    private const SETTING_DELEGATION_COST_ACCOUNT = 'ingaz_delegation_cost_account';
    private const SETTING_DELEGATION_FIXED_COST = 'ingaz_delegation_fixed_cost';
    private const DEFAULT_DELEGATION_FIXED_COST = 137.0;

    public function __construct()
    {
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::viewPermissions()), ['only' => ['index', 'show']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::createPermissions()), ['only' => ['create', 'createSponsorshipTransfer', 'store', 'searchClients']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::exportPermissions()), ['only' => ['exportExcel', 'exportPdf']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::printPermissions()), ['only' => ['downloadInvoice', 'legacySourceFile']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::controlPermissions()), ['only' => ['edit', 'update', 'destroy', 'queueZatcaSale', 'queueRentalZatcaSale', 'queueRentalAdjustmentZatcaSale']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::creditNoteCreatePermissions()), ['only' => ['createRecruitmentCreditNote', 'storeRecruitmentCreditNote']]);
        $this->middleware($this->permissionMiddleware(InvoiceAuthorization::creditNotePrintPermissions()), ['only' => ['downloadRecruitmentCreditNote']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // The index is a unified operational screen: imported invoices and
        // application-generated rental invoices must remain visible together.
        $legacyMode = false;

        if ($request->ajax()) {
            if (!$legacyMode) {
                return $this->standardInvoiceIndexData($request);
            }

            $invoiceBranchesEnabled = $this->branchesEnabledFor('invoices');

            $invoiceRelations = ['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'];
            if ($invoiceBranchesEnabled) {
                $invoiceRelations[] = 'branch';
            }

            $query = Invoice::query()
                ->with($invoiceRelations)
                ->whereNotNull('legacy_invoice_number');
            $this->applyRequestBranchFilter($query, $request, 'invoices');
            $this->applyInvoiceIndexFilters($query, $request, false);

            return DataTables::eloquent($query)
                ->filter(fn ($query) => $this->applyInvoiceIndexSearch($query, $request))
                ->addIndexColumn()
                ->addColumn('invoice_number', fn (Invoice $invoice) => $this->invoiceNumberLink($invoice))
                ->addColumn('invoice_source', fn (Invoice $invoice) => $this->invoiceSourceBadge($invoice))
                ->addColumn('client_name', fn (Invoice $invoice) => e($this->invoiceClientName($invoice)))
                ->addColumn('branch', fn (Invoice $invoice) => $invoiceBranchesEnabled ? e($invoice->branch?->name ?: '-') : '-')
                ->editColumn('subtotal', fn (Invoice $invoice) => number_format((float) $invoice->subtotal, 2))
                ->editColumn('vat_amount', fn (Invoice $invoice) => number_format((float) $invoice->vat_amount, 2))
                ->editColumn('discount', fn (Invoice $invoice) => number_format((float) $invoice->discount, 2))
                ->editColumn('total_due', fn (Invoice $invoice) => number_format((float) $invoice->total_due, 2))
                ->addColumn('status', fn (Invoice $invoice) => $this->invoicePaymentStatusBadge($invoice))
                ->addColumn('zatca_status', fn (Invoice $invoice) => $this->invoiceZatcaBadge($invoice))
                ->editColumn('created_at', fn (Invoice $invoice) => $invoice->created_at?->format('Y-m-d H:i') ?: '-')
                ->addColumn('actions', fn (Invoice $invoice) => $this->invoiceIndexActions($invoice))
                ->rawColumns(['invoice_number', 'invoice_source', 'status', 'zatca_status', 'actions'])
                ->make(true);
        }

        $branchesEnabled = $this->branchesEnabledFor('invoices')
            || (!$legacyMode && $this->branchesEnabledFor('rental_contracts'));
        $branches = $branchesEnabled
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();
        $invoiceTypes = $legacyMode ? Invoice::query()
            ->whereNotNull('legacy_invoice_number')
            ->whereNotNull('legacy_invoice_type')
            ->distinct()
            ->orderBy('legacy_invoice_type')
            ->pluck('legacy_invoice_type') : collect();
        $documentTypes = $legacyMode ? Invoice::query()
            ->whereNotNull('legacy_invoice_number')
            ->whereNotNull('legacy_document_type')
            ->distinct()
            ->orderBy('legacy_document_type')
            ->pluck('legacy_document_type') : collect();

        return view('admin.ingaz.invoices.index', compact(
            'branchesEnabled',
            'branches',
            'invoiceTypes',
            'documentTypes',
            'legacyMode'
        ));
    }

    private function usesLegacyInvoiceIndex(): bool
    {
        return Schema::hasColumn('invoices', 'legacy_invoice_number')
            && Invoice::query()->whereNotNull('legacy_invoice_number')->exists();
    }

    private function standardInvoiceIndexData(Request $request)
    {
        $invoiceBranchesEnabled = $this->branchesEnabledFor('invoices');
        $rentalBranchesEnabled = $this->branchesEnabledFor('rental_contracts');

        $invoiceQuery = DB::table('invoices as invoice')
            ->leftJoin('contracts as recruitment_contract', 'recruitment_contract.id', '=', 'invoice.contract_id')
            ->leftJoin('users as recruitment_client', 'recruitment_client.id', '=', 'recruitment_contract.user_id')
            ->leftJoin('ingaz_delegations as delegation', 'delegation.id', '=', 'invoice.ingaz_delegation_id')
            ->leftJoin('users as delegation_client', 'delegation_client.id', '=', 'delegation.user_id')
            ->leftJoin('users as direct_client', 'direct_client.id', '=', 'invoice.client_id')
            ->leftJoin('fn_sales as finance_sale', 'finance_sale.id', '=', 'invoice.finance_sale_id')
            ->leftJoin('zatca_invoices as zatca_invoice', 'zatca_invoice.id', '=', 'finance_sale.zatca_invoice_id');

        if ($invoiceBranchesEnabled) {
            $invoiceQuery->leftJoin('branches as invoice_branch', 'invoice_branch.id', '=', 'invoice.branch_id');
        }

        $this->applyRequestBranchFilter($invoiceQuery, $request, 'invoices', 'invoice.branch_id');
        $invoiceQuery->select([
            DB::raw("'invoice' as row_type"),
            'invoice.id',
            DB::raw('COALESCE(invoice.legacy_invoice_number, invoice.id) as invoice_sort_number'),
            'invoice.legacy_invoice_number',
            'invoice.legacy_order_number',
            'invoice.contract_id',
            'invoice.ingaz_delegation_id',
            'invoice.sponsorship_transfer_id',
            DB::raw('NULL as rental_contract_id'),
            DB::raw('NULL as rental_contract_number'),
            DB::raw('NULL as adjustment_type'),
            DB::raw('NULL as adjustment_constraint_id'),
            DB::raw('NULL as base_rental_invoice_id'),
            DB::raw('invoice.id as base_invoice_id'),
            DB::raw('COALESCE(recruitment_client.name, delegation_client.name, direct_client.name, "-") as client_name'),
            DB::raw('COALESCE(recruitment_client.phone, delegation_client.phone, direct_client.phone, "") as client_phone'),
            $invoiceBranchesEnabled
                ? DB::raw('COALESCE(invoice_branch.name_ar, "-") as branch')
                : DB::raw('"-" as branch'),
            'invoice.subtotal',
            'invoice.vat_amount',
            'invoice.discount',
            'invoice.total_due',
            'invoice.is_paid',
            'invoice.send_to_zatca',
            'invoice.finance_sale_id',
            'invoice.legacy_zatca_shared',
            'zatca_invoice.zatca_status',
            'invoice.created_at',
        ]);

        $rentalQuery = DB::table('rental_contract_invoices as rental_invoice')
            ->leftJoin('rental_contracts as rental_contract', 'rental_contract.id', '=', 'rental_invoice.rental_contract_id')
            ->leftJoin('users as rental_client', 'rental_client.id', '=', 'rental_contract.user_id')
            ->leftJoin('fn_sales as rental_finance_sale', 'rental_finance_sale.id', '=', 'rental_invoice.finance_sale_id')
            ->leftJoin('zatca_invoices as rental_zatca_invoice', 'rental_zatca_invoice.id', '=', 'rental_finance_sale.zatca_invoice_id');

        if ($rentalBranchesEnabled) {
            $rentalQuery->leftJoin('branches as rental_branch', 'rental_branch.id', '=', 'rental_contract.branch_id');
        }

        $this->applyRequestBranchFilter($rentalQuery, $request, 'rental_contracts', 'rental_contract.branch_id');
        $rentalQuery->select([
            DB::raw("'rental' as row_type"),
            'rental_invoice.id',
            DB::raw('rental_invoice.id as invoice_sort_number'),
            DB::raw('NULL as legacy_invoice_number'),
            DB::raw('NULL as legacy_order_number'),
            DB::raw('NULL as contract_id'),
            DB::raw('NULL as ingaz_delegation_id'),
            DB::raw('NULL as sponsorship_transfer_id'),
            'rental_invoice.rental_contract_id',
            'rental_contract.contract_number as rental_contract_number',
            DB::raw('NULL as adjustment_type'),
            DB::raw('NULL as adjustment_constraint_id'),
            DB::raw('rental_invoice.id as base_rental_invoice_id'),
            DB::raw('NULL as base_invoice_id'),
            DB::raw('COALESCE(rental_client.name, "-") as client_name'),
            DB::raw('COALESCE(rental_client.phone, "") as client_phone'),
            $rentalBranchesEnabled
                ? DB::raw('COALESCE(rental_branch.name_ar, "-") as branch')
                : DB::raw('"-" as branch'),
            'rental_invoice.subtotal',
            'rental_invoice.vat_amount',
            'rental_invoice.discount',
            'rental_invoice.total_due',
            'rental_invoice.is_paid',
            'rental_invoice.send_to_zatca',
            'rental_invoice.finance_sale_id',
            DB::raw('NULL as legacy_zatca_shared'),
            'rental_zatca_invoice.zatca_status',
            'rental_invoice.created_at',
        ]);

        $adjustmentVatExpression = 'ROUND(LEAST(1, closure_adjustment.amount / NULLIF(base_rental_invoice.total_due, 0)) * base_rental_invoice.vat_amount, 2)';
        $closureAdjustmentQuery = DB::table('rental_financial_adjustments as closure_adjustment')
            ->join('rental_contracts as adjustment_contract', 'adjustment_contract.id', '=', 'closure_adjustment.rental_contract_id')
            ->join('rental_contract_invoices as base_rental_invoice', 'base_rental_invoice.rental_contract_id', '=', 'adjustment_contract.id')
            ->leftJoin('users as adjustment_client', 'adjustment_client.id', '=', 'adjustment_contract.user_id')
            ->leftJoin('fn_sales as adjustment_sale', 'adjustment_sale.id', '=', 'closure_adjustment.finance_sale_id')
            ->leftJoin('zatca_invoices as adjustment_zatca', 'adjustment_zatca.id', '=', 'adjustment_sale.zatca_invoice_id')
            ->whereIn('closure_adjustment.context', ['closure', 'manual_credit_note', 'closure_correction'])
            ->where('closure_adjustment.amount', '>', 0);

        if ($rentalBranchesEnabled) {
            $closureAdjustmentQuery->leftJoin('branches as adjustment_branch', 'adjustment_branch.id', '=', 'adjustment_contract.branch_id');
        }

        $this->applyRequestBranchFilter(
            $closureAdjustmentQuery,
            $request,
            'rental_contracts',
            'adjustment_contract.branch_id'
        );
        $closureAdjustmentQuery->select([
            DB::raw("'rental_adjustment' as row_type"),
            'closure_adjustment.id',
            DB::raw('closure_adjustment.id as invoice_sort_number'),
            DB::raw('NULL as legacy_invoice_number'),
            DB::raw('NULL as legacy_order_number'),
            DB::raw('NULL as contract_id'),
            DB::raw('NULL as ingaz_delegation_id'),
            DB::raw('NULL as sponsorship_transfer_id'),
            'closure_adjustment.rental_contract_id',
            'adjustment_contract.contract_number as rental_contract_number',
            'closure_adjustment.type as adjustment_type',
            'closure_adjustment.constraint_id as adjustment_constraint_id',
            'base_rental_invoice.id as base_rental_invoice_id',
            DB::raw('NULL as base_invoice_id'),
            DB::raw('COALESCE(adjustment_client.name, "-") as client_name'),
            DB::raw('COALESCE(adjustment_client.phone, "") as client_phone'),
            $rentalBranchesEnabled
                ? DB::raw('COALESCE(adjustment_branch.name_ar, "-") as branch')
                : DB::raw('"-" as branch'),
            DB::raw('COALESCE(closure_adjustment.taxable_amount + closure_adjustment.non_taxable_amount, closure_adjustment.amount - ' . $adjustmentVatExpression . ') as subtotal'),
            DB::raw('COALESCE(closure_adjustment.vat_amount, ' . $adjustmentVatExpression . ') as vat_amount'),
            DB::raw('0 as discount'),
            'closure_adjustment.amount as total_due',
            DB::raw("CASE WHEN closure_adjustment.type = 'expense' THEN 1 ELSE 0 END as is_paid"),
            'closure_adjustment.send_to_zatca',
            'closure_adjustment.finance_sale_id',
            DB::raw('NULL as legacy_zatca_shared'),
            'adjustment_zatca.zatca_status',
            'closure_adjustment.created_at',
        ]);

        $creditNoteQuery = DB::table('recruitment_invoice_credit_notes as credit_note')
            ->join('invoices as original_invoice', 'original_invoice.id', '=', 'credit_note.invoice_id')
            ->leftJoin('contracts as credit_contract', 'credit_contract.id', '=', 'credit_note.contract_id')
            ->leftJoin('ingaz_delegations as credit_delegation', 'credit_delegation.id', '=', 'credit_note.ingaz_delegation_id')
            ->leftJoin('users as credit_client', 'credit_client.id', '=', 'credit_contract.user_id')
            ->leftJoin('users as credit_delegation_client', 'credit_delegation_client.id', '=', 'credit_delegation.user_id')
            ->leftJoin('users as credit_general_client', 'credit_general_client.id', '=', 'original_invoice.client_id')
            ->leftJoin('fn_sales as credit_sale', 'credit_sale.id', '=', 'credit_note.finance_sale_id')
            ->leftJoin('zatca_invoices as credit_zatca', 'credit_zatca.id', '=', 'credit_sale.zatca_invoice_id');

        if (!$this->canViewInvoiceCreditNote()) {
            $creditNoteQuery->whereRaw('1 = 0');
        }

        if ($invoiceBranchesEnabled) {
            $creditNoteQuery->leftJoin('branches as credit_branch', 'credit_branch.id', '=', 'credit_note.branch_id');
        }
        $this->applyRequestBranchFilter($creditNoteQuery, $request, 'invoices', 'credit_note.branch_id');
        $creditNoteQuery->select([
            DB::raw("'recruitment_credit_note' as row_type"),
            'credit_note.id',
            DB::raw('credit_note.id as invoice_sort_number'),
            DB::raw('NULL as legacy_invoice_number'),
            DB::raw('NULL as legacy_order_number'),
            'credit_note.contract_id',
            'credit_note.ingaz_delegation_id',
            DB::raw('NULL as sponsorship_transfer_id'),
            DB::raw('NULL as rental_contract_id'),
            DB::raw('NULL as rental_contract_number'),
            DB::raw("'credit_note' as adjustment_type"),
            'credit_note.constraint_id as adjustment_constraint_id',
            DB::raw('NULL as base_rental_invoice_id'),
            'credit_note.invoice_id as base_invoice_id',
            DB::raw('COALESCE(credit_client.name, credit_delegation_client.name, credit_general_client.name, "-") as client_name'),
            DB::raw('COALESCE(credit_client.phone, credit_delegation_client.phone, credit_general_client.phone, "") as client_phone'),
            $invoiceBranchesEnabled
                ? DB::raw('COALESCE(credit_branch.name_ar, "-") as branch')
                : DB::raw('"-" as branch'),
            'credit_note.subtotal',
            'credit_note.vat_amount',
            DB::raw('0 as discount'),
            'credit_note.total as total_due',
            DB::raw('1 as is_paid'),
            'credit_note.send_to_zatca',
            'credit_note.finance_sale_id',
            DB::raw('NULL as legacy_zatca_shared'),
            'credit_zatca.zatca_status',
            'credit_note.created_at',
        ]);

        $query = DB::query()->fromSub(
            $invoiceQuery->unionAll($rentalQuery)->unionAll($closureAdjustmentQuery)->unionAll($creditNoteQuery),
            'invoice_rows'
        );

        return DataTables::query($query)
            ->filter(function ($query) use ($request) {
                $search = trim((string) data_get($request->input('search'), 'value'));
                if ($search === '') {
                    return;
                }

                $query->where(function ($query) use ($search) {
                    $query->where('invoice_sort_number', 'like', "%{$search}%")
                        ->orWhere('legacy_order_number', 'like', "%{$search}%")
                        ->orWhere('contract_id', 'like', "%{$search}%")
                        ->orWhere('rental_contract_number', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('client_phone', 'like', "%{$search}%");
                });
            })
            ->addIndexColumn()
            ->addColumn('invoice_number', fn ($row) => $this->standardInvoiceNumberLink($row))
            ->addColumn('invoice_source', fn ($row) => $this->standardInvoiceSourceBadge($row))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->editColumn('vat_amount', fn ($row) => number_format((float) $row->vat_amount, 2))
            ->editColumn('discount', fn ($row) => number_format((float) $row->discount, 2))
            ->editColumn('total_due', fn ($row) => number_format((float) $row->total_due, 2))
            ->addColumn('status', fn ($row) => $this->standardInvoicePaymentBadge($row))
            ->editColumn('zatca_status', fn ($row) => $this->standardInvoiceZatcaBadge($row))
            ->editColumn('created_at', fn ($row) => $row->created_at ? substr((string) $row->created_at, 0, 16) : '-')
            ->addColumn('actions', fn ($row) => $this->standardInvoiceActions($row))
            ->removeColumn(
                'row_type',
                'id',
                'invoice_sort_number',
                'legacy_invoice_number',
                'legacy_order_number',
                'contract_id',
                'ingaz_delegation_id',
                'sponsorship_transfer_id',
                'rental_contract_id',
                'rental_contract_number',
                'adjustment_type',
                'adjustment_constraint_id',
                'base_rental_invoice_id',
                'base_invoice_id',
                'client_phone',
                'is_paid',
                'send_to_zatca',
                'finance_sale_id',
                'legacy_zatca_shared'
            )
            ->rawColumns(['invoice_number', 'invoice_source', 'status', 'zatca_status', 'actions'])
            ->make(true);
    }

    private function standardInvoiceNumberLink($row): string
    {
        if ($row->row_type === 'recruitment_credit_note') {
            $label = 'إشعار دائن #' . e((string) $row->id);
            if (!InvoiceAuthorization::canPrintCreditNotes()) {
                return '<span class="fw-bold text-danger">' . $label . '</span>';
            }
            $url = route('recruitment-credit-notes.download', ['id' => $row->id, 'autoprint' => 1]);

            return '<a href="' . $url . '" target="_blank" rel="noopener" class="fw-bold text-danger text-decoration-none">' . $label . '</a>';
        }

        if ($row->row_type === 'rental_adjustment') {
            $label = $row->adjustment_type === 'expense' ? 'إشعار دائن' : 'إشعار مدين';
            $label .= ' #' . e((string) $row->id);
            if (!InvoiceAuthorization::canPrintCreditNotes()) {
                return '<span class="fw-bold">' . $label . '</span>';
            }
            $url = route('rental.credit-note.download', ['id' => $row->id, 'autoprint' => 1]);

            return '<a href="' . $url . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">' . $label . '</a>';
        }

        if ($row->row_type === 'rental') {
            $label = 'إيجار #' . e((string) $row->id);
            if (!InvoiceAuthorization::canPrint()) {
                return '<span class="fw-bold">' . $label . '</span>';
            }
            $url = route('rental.invoice.download', ['id' => $row->id, 'autoprint' => 1]);

            return '<a href="' . $url . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">' . $label . '</a>';
        }

        $label = '#' . e((string) $row->invoice_sort_number);
        if (!InvoiceAuthorization::canPrint()) {
            return '<span class="fw-bold">' . $label . '</span>';
        }
        $url = route('invoice.download', ['id' => $row->id, 'autoprint' => 1]);

        return '<a href="' . $url . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">' . $label . '</a>';
    }

    private function standardInvoiceSourceBadge($row): string
    {
        if ($row->row_type === 'recruitment_credit_note') {
            $source = $row->contract_id
                ? 'عقد استقدام #' . e((string) $row->contract_id)
                : ($row->ingaz_delegation_id
                    ? 'تفويض إنجاز #' . e((string) $row->ingaz_delegation_id)
                    : 'فاتورة عامة');

            return '<span class="badge bg-danger-subtle text-danger border">فاتورة #'
                . e((string) $row->base_invoice_id) . ' / ' . $source . '</span>';
        }

        if (in_array($row->row_type, ['rental', 'rental_adjustment'], true)) {
            $contractNumber = $row->rental_contract_number ?: $row->rental_contract_id;
            $class = $row->row_type === 'rental_adjustment'
                ? 'bg-warning-subtle text-warning'
                : 'bg-success-subtle text-success';

            return '<span class="badge ' . $class . ' border">عقد تأجير #' . e((string) $contractNumber) . '</span>';
        }

        if ($row->legacy_invoice_number) {
            return $row->legacy_order_number
                ? '<span class="badge bg-info-subtle text-info border">طلب رقم ' . e((string) $row->legacy_order_number) . '</span>'
                : '<span class="text-muted">بدون طلب مرتبط</span>';
        }

        if ($row->contract_id) {
            return '<span class="badge bg-info-subtle text-info border">عقد استقدام #' . e((string) $row->contract_id) . '</span>';
        }

        if ($row->sponsorship_transfer_id) {
            return '<span class="badge bg-success-subtle text-success border">نقل الخدمات #' . e((string) $row->sponsorship_transfer_id) . '</span>';
        }

        if ($row->ingaz_delegation_id) {
            return '<span class="badge bg-primary-subtle text-primary border">تفويض إنجاز #' . e((string) $row->ingaz_delegation_id) . '</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary border">فاتورة عامة</span>';
    }

    private function standardInvoicePaymentBadge($row): string
    {
        if ($row->row_type === 'recruitment_credit_note') {
            return '<span class="badge bg-info-subtle text-info border">رصيد لصالح العميل</span>';
        }

        if ($row->row_type === 'rental_adjustment') {
            return $row->adjustment_type === 'expense'
                ? '<span class="badge bg-info-subtle text-info border">رصيد لصالح العميل</span>'
                : '<span class="badge bg-danger-subtle text-danger border">مستحق على العميل</span>';
        }

        $paid = (bool) $row->is_paid || (float) $row->total_due <= 0;
        $class = $paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';

        return '<span class="badge ' . $class . '">' . ($paid ? 'مدفوعة' : 'غير مدفوعة') . '</span>';
    }

    private function standardInvoiceZatcaBadge($row): string
    {
        if ($row->row_type === 'rental_adjustment') {
            if (!(bool) $row->send_to_zatca) {
                return '<span class="badge bg-secondary-subtle text-secondary border">غير مطلوب</span>';
            }
            if (!$row->finance_sale_id) {
                return '<span class="badge bg-warning-subtle text-warning border">لم يدرج</span>';
            }
            return match ($row->zatca_status) {
                'REPORTED' => '<span class="badge bg-success">مرحل للهيئة</span>',
                'CLEARED' => '<span class="badge bg-primary">معتمد من الهيئة</span>',
                'NOT_REPORTED', 'NOT_CLEARED' => '<span class="badge bg-danger">مرفوض من الهيئة</span>',
                default => '<span class="badge bg-warning-subtle text-warning border">معلق في مرتجعات المبيعات</span>',
            };
        }

        if ($row->row_type === 'rental') {
            if (!$row->finance_sale_id) {
                return '<span class="badge bg-info-subtle text-info border">جاهزة للمشاركة</span>';
            }

            return match ($row->zatca_status) {
                'REPORTED' => '<span class="badge bg-success">مرحل للهيئة</span>',
                'CLEARED' => '<span class="badge bg-primary">معتمد من الهيئة</span>',
                'NOT_REPORTED', 'NOT_CLEARED' => '<span class="badge bg-danger">مرفوض من الهيئة</span>',
                default => '<span class="badge bg-warning-subtle text-warning border">معلق في المبيعات</span>',
            };
        }

        if ((int) $row->legacy_zatca_shared === 1) {
            return '<span class="badge bg-success">مشارك سابقاً مع الهيئة</span>';
        }

        if ($row->legacy_invoice_number && $row->legacy_zatca_shared !== null) {
            return '<span class="badge bg-secondary-subtle text-secondary border">غير مشارك في المصدر</span>';
        }

        if (!(bool) $row->send_to_zatca) {
            return '<span class="badge bg-secondary-subtle text-secondary border">غير مطلوب</span>';
        }

        if (!$row->finance_sale_id) {
            return '<span class="badge bg-warning-subtle text-warning border">لم يدرج</span>';
        }

        return match ($row->zatca_status) {
            'REPORTED' => '<span class="badge bg-success">مرحل للهيئة</span>',
            'CLEARED' => '<span class="badge bg-primary">معتمد من الهيئة</span>',
            'NOT_REPORTED', 'NOT_CLEARED' => '<span class="badge bg-danger">مرفوض من الهيئة</span>',
            default => '<span class="badge bg-warning-subtle text-warning border">معلق في المبيعات</span>',
        };
    }

    private function standardInvoiceActions($row): string
    {
        if ($row->row_type === 'recruitment_credit_note') {
            $printUrl = route('recruitment-credit-notes.download', ['id' => $row->id, 'autoprint' => 1]);
            $printButton = InvoiceAuthorization::canPrintCreditNotes()
                ? '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="طباعة إشعار الدائن"><i class="fas fa-print"></i></a>'
                : '';
            $constraintButton = $row->adjustment_constraint_id
                ? '<a href="' . route('finance.add-constraint.show', ['add_constraint' => $row->adjustment_constraint_id])
                    . '" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" title="عرض قيد إشعار الدائن"><i class="fas fa-book-open"></i></a>'
                : '';
            $saleButton = ($row->finance_sale_id ?? null)
                ? '<a href="' . route('finance.bill-sales.show', $row->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="مرتجع المبيعات"><i class="fas fa-link"></i></a>'
                : '';

            return '<div class="d-flex justify-content-end gap-2">'
                . $constraintButton . $saleButton
                . $printButton
                . '</div>';
        }

        if ($row->row_type === 'rental_adjustment') {
            $contractUrl = route('RentContract.show', $row->rental_contract_id);
            $printUrl = route('rental.credit-note.download', ['id' => $row->id, 'autoprint' => 1]);
            $constraintButton = $row->adjustment_constraint_id
                ? '<a href="' . route('finance.add-constraint.show', ['add_constraint' => $row->adjustment_constraint_id])
                    . '" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" title="عرض قيد التسوية"><i class="fas fa-book-open"></i></a>'
                : '';
            $saleButton = ($row->finance_sale_id ?? null)
                ? '<a href="' . route('finance.bill-sales.show', $row->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="مرتجع المبيعات"><i class="fas fa-link"></i></a>'
                : (InvoiceAuthorization::canControl() ? '<form method="post" action="' . route('rental-adjustments.queue-zatca-sale', $row->id) . '" class="d-inline queue-zatca-form">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-sm btn-outline-success" title="إدراج الإشعار في مستندات الزكاة"><i class="fas fa-receipt"></i></button></form>' : '');
            $printButton = InvoiceAuthorization::canPrintCreditNotes()
                ? '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="طباعة مستند الإغلاق"><i class="fas fa-print"></i></a>'
                : '';

            return '<div class="d-flex justify-content-end gap-2">
                        <a href="' . $contractUrl . '" class="btn btn-sm btn-outline-primary" title="عرض عقد التأجير"><i class="fas fa-eye"></i></a>
                        ' . $constraintButton . $saleButton . '
                        ' . $printButton . '
                    </div>';
        }

        if ($row->row_type === 'rental') {
            $contractUrl = $row->rental_contract_id ? route('RentContract.show', $row->rental_contract_id) : '#';
            $printUrl = route('rental.invoice.download', ['id' => $row->id, 'autoprint' => 1]);
            $creditNoteButton = $this->canCreateInvoiceCreditNote()
                ? '<a href="' . route('rental-invoices.credit-notes.create', $row->id) . '" class="btn btn-sm btn-outline-danger" title="إنشاء إشعار دائن دون إنهاء عقد التأجير"><i class="fas fa-file-invoice-dollar"></i></a>'
                : '';
            $queueButton = !$row->finance_sale_id
                ? (InvoiceAuthorization::canControl() ? '<form method="post" action="' . route('rental-invoices.queue-zatca-sale', $row->id) . '" class="d-inline queue-zatca-form">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-sm btn-outline-success" title="إدراج فاتورة التأجير في فواتير الزكاة"><i class="fas fa-receipt"></i></button></form>' : '')
                : '<a href="' . route('finance.bill-sales.show', $row->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="فاتورة المبيعات المرسلة للهيئة"><i class="fas fa-link"></i></a>';
            $printButton = InvoiceAuthorization::canPrint()
                ? '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="طباعة الفاتورة"><i class="fas fa-print"></i></a>'
                : '';

            return '<div class="d-flex justify-content-end gap-2">
                        ' . $queueButton . '
                        ' . $creditNoteButton . '
                        <a href="' . $contractUrl . '" class="btn btn-sm btn-outline-primary" title="عرض عقد التأجير"><i class="fas fa-eye"></i></a>
                        ' . $printButton . '
                    </div>';
        }

        $queueButton = (int) $row->legacy_zatca_shared === 1
            ? '<span class="btn btn-sm btn-outline-success disabled" title="مشاركة سابقة محفوظة من نظام الرافدين"><i class="fas fa-check-circle"></i></span>'
            : (!$row->finance_sale_id
                ? (InvoiceAuthorization::canControl() ? '<form method="post" action="' . route('invoices.queue-zatca-sale', $row->id) . '" class="d-inline queue-zatca-form">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-sm btn-outline-success" title="إدراج في فواتير الزكاة"><i class="fas fa-receipt"></i></button></form>' : '')
                : '<a href="' . route('finance.bill-sales.show', $row->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="فاتورة المبيعات"><i class="fas fa-link"></i></a>');
        $showUrl = route('invoices.show', $row->id);
        $printUrl = route('invoice.download', ['id' => $row->id, 'autoprint' => 1]);
        $creditNoteButton = ($row->contract_id || $row->ingaz_delegation_id
                || (!$row->sponsorship_transfer_id && $row->base_invoice_id))
            && !$row->legacy_invoice_number
            && $this->canCreateInvoiceCreditNote()
            ? '<a href="' . route('invoices.credit-notes.create', $row->id) . '" class="btn btn-sm btn-outline-danger" title="إنشاء إشعار دائن دون تغيير العقد أو التفويض أو الفاتورة الأصلية"><i class="fas fa-file-invoice-dollar"></i></a>'
            : '';
        $printButton = InvoiceAuthorization::canPrint()
            ? '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="طباعة الفاتورة"><i class="fas fa-print"></i></a>'
            : '';

        return '<div class="d-flex justify-content-end gap-2">
                    ' . $queueButton . '
                    ' . $creditNoteButton . '
                    <a href="' . $showUrl . '" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل"><i class="fas fa-eye"></i></a>
                    ' . $printButton . '
                </div>';
    }

    private function applyInvoiceIndexFilters($query, Request $request, bool $includeSearch = true): void
    {
        if ($request->filled('invoice_number')) {
            $query->where('legacy_invoice_number', (int) $request->input('invoice_number'));
        }

        if ($request->filled('invoice_type')) {
            $query->where('legacy_invoice_type', (string) $request->input('invoice_type'));
        }

        if ($request->filled('document_type')) {
            $query->where('legacy_document_type', (string) $request->input('document_type'));
        }

        if ($request->filled('zatca_shared')) {
            $query->where('legacy_zatca_shared', $request->input('zatca_shared') === '1');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($includeSearch) {
            $this->applyInvoiceIndexSearch($query, $request);
        }
    }

    private function applyInvoiceIndexSearch($query, Request $request): void
    {
        $search = trim((string) data_get($request->input('search'), 'value'));
        if ($search === '') {
            return;
        }

        $query->where(function ($query) use ($search) {
            $query->where('legacy_invoice_number', 'like', "%{$search}%")
                ->orWhere('legacy_order_number', 'like', "%{$search}%")
                ->orWhere('legacy_client_number', 'like', "%{$search}%")
                ->orWhere('legacy_invoice_type', 'like', "%{$search}%")
                ->orWhere('legacy_document_type', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($client) => $client
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('contract.user', fn ($client) => $client
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
        });
    }

    private function branchesEnabledFor(string $table): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($table)
            && Schema::hasColumn($table, 'branch_id');
    }

    private function invoiceNumberLink(Invoice $invoice): string
    {
        return '<a href="' . $this->invoicePrintUrl($invoice) . '" target="_blank" rel="noopener" class="fs-6 fw-bold text-decoration-none">'
            . e((string) ($invoice->legacy_invoice_number ?: $invoice->id))
            . '</a>';
    }

    private function invoiceClientName(Invoice $invoice): string
    {
        return $invoice->contract?->user?->name
            ?: ($invoice->ingazDelegation?->user?->name
                ?: ($invoice->client?->name ?: '-'));
    }

    private function invoicePaymentStatusBadge(Invoice $invoice): string
    {
        $paid = (bool) $invoice->is_paid || (float) $invoice->total_due <= 0;
        $class = $paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';

        return '<span class="badge ' . $class . '">' . e($this->invoicePaymentStatusLabel($invoice)) . '</span>';
    }

    private function invoicePrintUrl(Invoice $invoice): string
    {
        return route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]);
    }

    private function invoiceIndexActions(Invoice $invoice): string
    {
        $originalFileButton = $invoice->legacy_source_file
            ? '<a href="' . route('invoices.legacy-source-file', $invoice->id) . '" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener" title="عرض ملف PDF الأصلي">
                    <i class="fas fa-file-pdf"></i>
                </a>'
            : '';

        return '<div class="d-flex justify-content-end gap-2">
                    <a href="' . route('invoices.show', $invoice->id) . '" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="' . $this->invoicePrintUrl($invoice) . '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="طباعة الفاتورة">
                        <i class="fas fa-print"></i>
                    </a>
                    ' . $originalFileButton . '
                </div>';
    }

    private function formatInvoiceIndexRow(Invoice $invoice, bool $branchesEnabled = false): array
    {
        $paid = (bool) $invoice->is_paid || (float) $invoice->total_due <= 0;
        $paymentStatus = $this->invoicePaymentStatusLabel($invoice);
        $statusClass = $paid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
        $queueButton = $invoice->legacy_zatca_shared
            ? '<span class="btn btn-sm btn-outline-success disabled" title="مشاركة سابقة محفوظة من نظام الرافدين"><i class="fas fa-check-circle"></i></span>'
            : ((!$invoice->finance_sale_id)
            ? '<form method="post" action="' . route('invoices.queue-zatca-sale', $invoice->id) . '" class="d-inline queue-zatca-form">
                    ' . csrf_field() . '
                    <button type="submit" class="btn btn-sm btn-outline-success" title="إدراج في فواتير الزكاة">
                        <i class="fas fa-receipt"></i>
                    </button>
                </form>'
            : '<a href="' . route('finance.bill-sales.show', $invoice->finance_sale_id) . '" class="btn btn-sm btn-outline-success" title="فاتورة المبيعات">
                    <i class="fas fa-link"></i>
                </a>');

        return [
            'id' => $invoice->id,
            'invoice_number' => '<a href="' . route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]) . '" target="_blank" rel="noopener" class="fw-bold text-decoration-none">#' . ($invoice->legacy_invoice_number ?: $invoice->id) . '</a>',
            'invoice_source' => $this->invoiceSourceBadge($invoice),
            'client_name' => $invoice->contract?->user?->name ?: ($invoice->ingazDelegation?->user?->name ?: ($invoice->client?->name ?: '-')),
            'branch' => $branchesEnabled ? ($invoice->branch?->name ?: '-') : '-',
            'subtotal' => number_format((float) $invoice->subtotal, 2),
            'vat_amount' => number_format((float) $invoice->vat_amount, 2),
            'discount' => number_format((float) $invoice->discount, 2),
            'total_due' => number_format((float) $invoice->total_due, 2),
            'status' => '<span class="badge ' . $statusClass . '">' . e($paymentStatus) . '</span>',
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
        if ($invoice->legacy_invoice_number) {
            if ($invoice->legacy_order_number) {
                return '<span class="badge bg-info-subtle text-info border">طلب رقم ' . e($invoice->legacy_order_number) . '</span>';
            }

            return '<span class="text-muted">بدون طلب مرتبط</span>';
        }

        if ($invoice->contract_id) {
            return '<span class="badge bg-info-subtle text-info border">عقد استقدام #' . $invoice->contract_id . '</span>';
        }

        if ($invoice->sponsorship_transfer_id) {
            return '<span class="badge bg-success-subtle text-success border">نقل الخدمات #' . $invoice->sponsorship_transfer_id . '</span>';
        }

        if ($invoice->ingaz_delegation_id) {
            return '<span class="badge bg-primary-subtle text-primary border">تفويض إنجاز #' . $invoice->ingaz_delegation_id . '</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary border">فاتورة عامة</span>';
    }

    private function invoiceZatcaBadge(Invoice $invoice): string
    {
        if ($invoice->legacy_zatca_shared) {
            return '<span class="badge bg-success">مشارك سابقاً مع الهيئة</span>';
        }

        if ($invoice->legacy_invoice_number && $invoice->legacy_zatca_shared === false) {
            return '<span class="badge bg-secondary-subtle text-secondary border">غير مشارك في المصدر</span>';
        }

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
        $contractSearch = trim((string) $request->query('contract_search', ''));
        $contractSearchAttempted = $contractSearch !== '';
        $contractSearchMessage = null;

        $contracts = collect();

        if (!$contractId && !$ingazDelegationId && !$clientId && $contractSearchAttempted) {
            $searchedContract = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality'])
                ->whereDoesntHave('invoice')
                ->whereIn('status', Contract::invoiceEligibleStatuses())
                ->where(function ($query) use ($contractSearch) {
                    if (is_numeric($contractSearch)) {
                        $query->where('id', (int) $contractSearch);
                    } else {
                        $query->whereRaw('1 = 0');
                    }

                    if (Schema::hasColumn('contracts', 'contract_number')) {
                        $query->orWhere('contract_number', $contractSearch);
                    }
                })
                ->first();

            if ($searchedContract) {
                $contractId = (int) $searchedContract->id;
            } else {
                $contractSearchMessage = 'لم يتم العثور على عقد حالي بهذا الرقم، أو أن العقد لديه فاتورة مسبقة.';
            }
        }

        $selectedContract = null;
        $selectedIngazDelegation = null;
        $selectedClient = null;

        if ($contractId) {
            $selectedContract = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality', 'invoice'])
                ->findOrFail($contractId);

            if ($selectedContract->invoice) {
                if (!InvoiceAuthorization::canView()) {
                    return redirect()->route('invoices.create')
                        ->with('warning', 'تم إصدار فاتورة لهذا العقد مسبقاً، وليست لديك صلاحية عرض الفواتير.');
                }
                return redirect()
                    ->route('invoices.show', $selectedContract->invoice->id)
                    ->with('success', 'تم إصدار فاتورة لهذا العقد مسبقاً.');
            }
        }

        if ($selectedContract && !$selectedContract->isInvoiceEligible()) {
            $selectedContract = null;
            $contractId = null;
            $contractSearchMessage = 'لا يمكن إصدار فاتورة إلا لعقد من العقود الحالية.';
        }

        if ($ingazDelegationId) {
            $selectedIngazDelegation = IngazDelegation::query()
                ->with(['user', 'country', 'nationality', 'profession', 'invoice'])
                ->findOrFail($ingazDelegationId);

            if ($selectedIngazDelegation->invoice) {
                if (!InvoiceAuthorization::canView()) {
                    return redirect()->route('invoices.create')
                        ->with('warning', 'تم إصدار فاتورة لهذا التفويض مسبقاً، وليست لديك صلاحية عرض الفواتير.');
                }
                return redirect()
                    ->route('invoices.show', $selectedIngazDelegation->invoice->id)
                    ->with('success', 'تم إصدار فاتورة لهذا التفويض مسبقاً.');
            }
        }

        if ($clientId && !$contractId && !$ingazDelegationId) {
            $selectedClient = ClientUser::query()->findOrFail($clientId);
        }

        // The public invoice number currently follows the auto-incrementing invoice ID.
        $nextInvoiceNumber = ((int) Invoice::query()->max('id')) + 1;
        $invoiceIssuedAt = now();
        $invoiceEligibleStatusLabels = collect(Contract::invoiceEligibleStatuses())
            ->map(fn (string $status) => ContractStatusEnum::from($status)->lang())
            ->values();

        return view('admin.ingaz.invoices.create', compact(
            'contractId',
            'ingazDelegationId',
            'clientId',
            'contracts',
            'selectedContract',
            'selectedIngazDelegation',
            'selectedClient',
            'contractSearch',
            'contractSearchAttempted',
            'contractSearchMessage',
            'invoiceEligibleStatusLabels',
            'nextInvoiceNumber',
            'invoiceIssuedAt'
        ));
    }

    public function createSponsorshipTransfer(int $id)
    {
        $transfer = SponsorshipTransfer::query()
            ->with([
                'invoice',
                'newSponsor',
                'contract.maid.nationality',
                'receiptBond.constraint',
            ])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer, 'sponsorship_transfers');

        if ($transfer->invoice) {
            if (!InvoiceAuthorization::canView()) {
                return redirect()->route('invoices.create')
                    ->with('warning', 'تم إصدار فاتورة لطلب نقل الخدمات مسبقاً، وليست لديك صلاحية عرض الفواتير.');
            }
            return redirect()
                ->route('invoices.show', $transfer->invoice->id)
                ->with('success', 'تم إصدار فاتورة لطلب نقل الخدمات مسبقاً.');
        }

        $grossContractValue = round((float) $transfer->contract_value, 2);
        $defaultLicenseeCost = round($grossContractValue / (1 + InvoiceCalculationService::VAT_RATE), 2);
        $invoiceIssuedAt = now();
        $nextInvoiceNumber = ((int) Invoice::query()->max('id')) + 1;

        return view('admin.ingaz.SponsorshipTransfer.invoice-create', compact(
            'transfer',
            'grossContractValue',
            'defaultLicenseeCost',
            'invoiceIssuedAt',
            'nextInvoiceNumber'
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
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'contract_id' => 'nullable|required_without_all:ingaz_delegation_id,client_id,sponsorship_transfer_id|exists:contracts,id',
            'ingaz_delegation_id' => 'nullable|required_without_all:contract_id,client_id,sponsorship_transfer_id|exists:ingaz_delegations,id',
            'client_id' => 'nullable|required_without_all:contract_id,ingaz_delegation_id,sponsorship_transfer_id|exists:users,id',
            'sponsorship_transfer_id' => 'nullable|exists:sponsorship_transfers,id',
            'line_items' => 'nullable|required_with:sponsorship_transfer_id|array|min:1|max:50',
            'line_items.*.key' => 'nullable|string|max:50',
            'line_items.*.label' => 'required_with:sponsorship_transfer_id|string|max:255',
            'line_items.*.quantity' => 'required_with:sponsorship_transfer_id|numeric|min:0.01|max:999999',
            'line_items.*.unit_price' => 'required_with:sponsorship_transfer_id|numeric|min:0|max:999999999.99',
            'line_items.*.taxable' => 'nullable|boolean',
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
            $request->filled('sponsorship_transfer_id'),
        ])->filter()->count();

        if ($referenceCount !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'يجب ربط الفاتورة بعقد أو تفويض إنجاز أو عميل أو طلب نقل الخدمات واحد فقط.'
            ], 422);
        }

        $invoiceExists = false;
        if ($request->filled('contract_id')) {
            $invoiceExists = Invoice::where('contract_id', $request->contract_id)->exists();
        } elseif ($request->filled('ingaz_delegation_id')) {
            $invoiceExists = Invoice::where('ingaz_delegation_id', $request->ingaz_delegation_id)->exists();
        } elseif ($request->filled('sponsorship_transfer_id')) {
            $invoiceExists = Invoice::where('sponsorship_transfer_id', $request->sponsorship_transfer_id)->exists();
        }

        if ($invoiceExists) {
            return response()->json([
                'success' => false,
                'message' => $request->filled('contract_id')
                    ? 'هذا العقد لديه فاتورة سابقة'
                    : ($request->filled('sponsorship_transfer_id')
                        ? 'طلب نقل الخدمات لديه فاتورة سابقة'
                        : 'هذا التفويض لديه فاتورة سابقة')
            ], 422);
        }

        if ($request->filled('contract_id')) {
            $contractIsCurrent = Contract::query()
                ->whereKey($request->contract_id)
                ->whereIn('status', Contract::invoiceEligibleStatuses())
                ->exists();

            if (!$contractIsCurrent) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إصدار فاتورة إلا لعقد من العقود الحالية.'
                ], 422);
            }
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

                if ($request->filled('sponsorship_transfer_id')) {
                    return $this->storeSponsorshipTransferInvoice(
                        $request,
                        $sendToZatca,
                        $service,
                        $yearService
                    );
                }

                $invoicePayload['insurance_company'] = $request->insurance_company ?? null;
                $invoicePayload['general_description'] = $request->general_description ?? null;

                return $this->createRecruitmentContractInvoice(
                    (int) $request->contract_id,
                    $invoicePayload,
                    $this->resolveInvoiceDateTime($request),
                    $sendToZatca,
                    $service,
                    $yearService,
                    $bondService
                );
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

        if ($invoice->ingaz_delegation_id) {
            $delegation = $invoice->relationLoaded('ingazDelegation')
                ? $invoice->ingazDelegation
                : IngazDelegation::query()->find($invoice->ingaz_delegation_id);

            if ($delegation) {
                IngazDelegationTimeline::record($delegation, 'invoice_created', [
                    'summary' => 'تم إصدار فاتورة للتفويض.',
                    'invoice_id' => $invoice->id,
                    'total' => (float) $invoice->total_due,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار الفاتورة بنجاح',
            'data' => $invoice,
            'invoice_number' => $invoice->id,
            'issued_at' => optional($invoice->updated_at)->format('d/m/Y - H:i') ?: now()->format('d/m/Y - H:i'),
            'redirect' => InvoiceAuthorization::canView()
                ? route('invoices.show', $invoice->id)
                : route('invoices.create'),
            'view_url' => InvoiceAuthorization::canView() ? route('invoices.show', $invoice->id) : null,
            'print_url' => InvoiceAuthorization::canPrint()
                ? route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1])
                : null,
            'invoice_constraint_print_url' => $invoiceConstraint ? $this->buildConstraintPrintUrl($invoiceConstraint->id) : null,
            'receipt_bond_print_url' => $receiptBond ? $this->buildReceiptBondPrintUrl($receiptBond->id) : null,
            'receipt_constraint_print_url' => ($receiptBond && $receiptBond->constraint_id) ? $this->buildConstraintPrintUrl((int) $receiptBond->constraint_id) : null,
            'delegation_cost_constraint_print_url' => $delegationCostConstraint ? $this->buildConstraintPrintUrl($delegationCostConstraint->id) : null,
            'index_url' => InvoiceAuthorization::canView() ? route('invoices.index') : null,
            'create_url' => route('invoices.create'),
        ]);
    }

    public function createRecruitmentContractInvoice(
        int $contractId,
        array $invoicePayload,
        \Carbon\Carbon $invoiceDate,
        bool $sendToZatca,
        ObjService $service,
        YearService $yearService,
        BondService $bondService,
        bool $requireAccountingEntries = false
    ): array {
        return DB::transaction(function () use (
            $contractId,
            $invoicePayload,
            $invoiceDate,
            $sendToZatca,
            $service,
            $yearService,
            $bondService,
            $requireAccountingEntries
        ) {
            $contract = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality'])
                ->lockForUpdate()
                ->findOrFail($contractId);

            if ($contract->invoice()->exists()) {
                throw new \RuntimeException('هذا العقد لديه فاتورة سابقة');
            }

            $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
            if ($requireAccountingEntries && !$automaticEntriesEnabled) {
                throw new \RuntimeException('إنشاء القيود المحاسبية التلقائية غير مفعل.');
            }

            $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
            $nationalityName = $nationality?->sub_title ?? $nationality?->title ?? null;
            $this->persistMissingNationalityInvoiceCosts($nationality, $invoicePayload);

            $clientAccountId = null;
            $nationalityAccountId = null;
            // يُثبَّت وضع إثبات الإيراد على الفاتورة وقت إنشائها حتى لا يتأثر إقفالها بتغيير الإعداد لاحقاً.
            $revenueMode = RecruitmentRevenueRecognition::mode();

            if ($automaticEntriesEnabled) {
                $clientAccountId = $this->ensureClientAccountId($contract);
                $nationalityAccountId = $this->resolveRecruitmentInvoiceCreditAccountId($nationality, $revenueMode);

                if (!$nationalityAccountId) {
                    throw new \RuntimeException(
                        $this->recruitmentInvoiceCreditAccountMissingMessage($revenueMode, $nationalityName)
                    );
                }
            }

            $invoice = Invoice::query()->create([
                'contract_id' => $contract->id,
                ...(RecruitmentRevenueRecognition::invoiceColumnExists()
                    ? [RecruitmentRevenueRecognition::INVOICE_COLUMN => $revenueMode]
                    : []),
                'client_id' => null,
                'insurance_company' => $invoicePayload['insurance_company'] ?? null,
                'general_description' => $invoicePayload['general_description'] ?? null,
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
                'created_at' => $invoiceDate,
            ]);
            $invoice->setRelation('contract', $contract);

            $invoiceConstraint = null;
            $receiptBond = null;

            if ($automaticEntriesEnabled && (float) $invoice->total_due > 0) {
                $contractCostCenter = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName);
                $invoiceConstraint = $this->storeInvoiceConstraintForInvoice(
                    $invoice,
                    $service,
                    $yearService,
                    $nationalityAccountId,
                    $clientAccountId,
                    $contractCostCenter->id
                );
                $invoice->update(['invoice_constraint_id' => $invoiceConstraint?->id]);

                $musandAccount = $this->resolveMusanedReceiptAccount();
                $receiptBond = $this->storeReceiptBondForInvoice(
                    $invoice,
                    $bondService,
                    $yearService,
                    $clientAccountId,
                    (int) $musandAccount->id
                );
                if (Schema::hasColumn('fn_bonds', 'recruitment_contract_id')) {
                    $receiptBond->update(['recruitment_contract_id' => $contract->id]);
                }
            }

            if ($requireAccountingEntries) {
                if ((float) $invoice->total_due <= 0) {
                    throw new \RuntimeException('قيمة الفاتورة صفر؛ لن يتم إنشاء فاتورة بلا قيد محاسبي.');
                }
                if (!$invoiceConstraint) {
                    throw new \RuntimeException('لم يتم إنشاء قيد الفاتورة.');
                }
                if (!$receiptBond || !$receiptBond->constraint_id) {
                    throw new \RuntimeException('لم يتم إنشاء سند القبض وقيده المحاسبي.');
                }
            }

            if ($invoice->send_to_zatca) {
                $this->queueInvoiceForZatcaSale($invoice);
            }

            return [
                'invoice' => $invoice->fresh(),
                'invoice_constraint' => $invoiceConstraint,
                'receipt_bond' => $receiptBond,
            ];
        });
    }

    /**
     * الحساب الدائن في قيد فاتورة الاستقدام حسب وضع إثبات الإيراد:
     * التزامات عقود الجنسية (عند الوصول) أو مبيعات الجنسية (عند الفاتورة).
     */
    private function resolveRecruitmentInvoiceCreditAccountId(?Nationality $nationality, string $revenueMode): ?int
    {
        if ($revenueMode === RecruitmentRevenueRecognition::MODE_ON_INVOICE) {
            return $this->resolveContractNationalitySalesAccountId($nationality);
        }

        return $this->resolveContractNationalityLiabilityAccountId($nationality);
    }

    private function recruitmentInvoiceCreditAccountMissingMessage(string $revenueMode, ?string $nationalityName): string
    {
        $suffix = $nationalityName ? " : {$nationalityName}" : '';

        if ($revenueMode === RecruitmentRevenueRecognition::MODE_ON_INVOICE) {
            return 'لا يوجد حساب مبيعات صالح لهذه الجنسية' . $suffix
                . '، الرجاء ربط حساب المبيعات من إعدادات حسابات الجنسيات أولاً.';
        }

        return 'لا يوجد حساب التزامات عقود صالح لهذه الجنسية' . $suffix
            . '، الرجاء ربط حساب الالتزام من إعدادات حسابات الجنسيات أولاً.';
    }

    public function resolveContractNationalitySalesAccountId(?Nationality $nationality): ?int
    {
        if (!$nationality) {
            return null;
        }

        $nationalityName = trim((string) ($nationality->sub_title ?: $nationality->title));

        return app(RecruitmentTripAccountingService::class)
            ->resolveSalesAccountId($nationalityName, (int) $nationality->id);
    }

    public function reconcileRecruitmentContractInvoice(
        int $contractId,
        array $invoicePayload,
        \Carbon\Carbon $invoiceDate,
        string $sourceFile,
        int $sourceRow,
        ObjService $service,
        YearService $yearService,
        BondService $bondService
    ): array {
        return DB::transaction(function () use (
            $contractId,
            $invoicePayload,
            $invoiceDate,
            $sourceFile,
            $sourceRow,
            $service,
            $yearService,
            $bondService
        ): array {
            $contract = Contract::query()
                ->with(['user', 'maid.nationality', 'requiredMaidNationality', 'invoice'])
                ->lockForUpdate()
                ->findOrFail($contractId);

            if (!$contract->invoice) {
                $result = $this->createRecruitmentContractInvoice(
                    $contract->id,
                    $invoicePayload,
                    $invoiceDate,
                    false,
                    $service,
                    $yearService,
                    $bondService,
                    true
                );
                $result['invoice']->update([
                    'legacy_source_file' => $sourceFile,
                    'legacy_source_row' => $sourceRow,
                    'legacy_source_payload' => $invoicePayload,
                ]);
                $result['action'] = 'created';

                return $result;
            }

            $invoice = Invoice::query()
                ->with(['financeSale.zatca', 'musanedTransferItem'])
                ->lockForUpdate()
                ->findOrFail($contract->invoice->id);
            $amountChanged = collect([
                [(float) $invoice->direct_recruitment_cost, (float) $invoicePayload['direct_recruitment_cost']],
                [(float) $invoice->external_recruitment_cost, (float) $invoicePayload['external_recruitment_cost']],
                [(float) $invoice->government_cost, (float) $invoicePayload['government_cost']],
                [(float) $invoice->internal_ticket_cost, (float) $invoicePayload['internal_ticket_cost']],
                [(float) $invoice->vat_amount, (float) $invoicePayload['vat_amount']],
                [(float) $invoice->total_due, (float) $invoicePayload['total_due']],
            ])->contains(fn (array $values) => abs($values[0] - $values[1]) >= 0.01);

            if ($invoice->financeSale?->zatca_invoice_id || $invoice->financeSale?->zatca) {
                throw new \RuntimeException('الفاتورة مرتبطة بفاتورة زكاة مرسلة ولا يجوز تعديلها مباشرة.');
            }
            if ($amountChanged && $invoice->musanedTransferItem) {
                throw new \RuntimeException('الفاتورة مرتبطة بتحويل مساند؛ يجب تسوية التحويل قبل تعديل مبالغها.');
            }
            if ($amountChanged && (
                $invoice->arrival_revenue_constraint_id
                || $invoice->external_office_cost_constraint_id
                || $invoice->external_office_purchase_id
            )) {
                throw new \RuntimeException('الفاتورة مرتبطة بقيود وصول أو تكلفة مكتب خارجي؛ لا يمكن تعديل مبالغها تلقائياً.');
            }

            $bonds = Bond::query()
                ->where('recruitment_contract_id', $contract->id)
                ->lockForUpdate()
                ->get();
            if ($bonds->count() > 1) {
                throw new \RuntimeException('يوجد أكثر من سند مرتبط بالعقد؛ يلزم فحص محاسبي يدوي.');
            }
            $oldBond = $bonds->first();
            if ($oldBond?->check || $oldBond?->bondTax) {
                throw new \RuntimeException('سند العقد يحتوي شيكاً أو ضريبة ولا يمكن استبداله تلقائياً.');
            }

            $oldInvoiceConstraintId = $invoice->invoice_constraint_id
                ? (int) $invoice->invoice_constraint_id
                : null;
            $oldBondConstraintId = $oldBond?->constraint_id
                ? (int) $oldBond->constraint_id
                : null;

            $invoice->update(['invoice_constraint_id' => null]);
            if ($oldBond) {
                $oldBond->update(['constraint_id' => null, 'is_confirm' => 0]);
                $oldBond->delete();
            }
            foreach (array_unique(array_filter([$oldInvoiceConstraintId, $oldBondConstraintId])) as $constraintId) {
                $constraint = Constraint::query()->lockForUpdate()->find($constraintId);
                if ($constraint) {
                    $constraint->constraintDetails()->delete();
                    $constraint->delete();
                }
            }

            $invoice->update([
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
                'created_at' => $invoiceDate,
                'paid_at' => $invoiceDate,
                'legacy_source_file' => $sourceFile,
                'legacy_source_row' => $sourceRow,
                'legacy_source_payload' => $invoicePayload,
            ]);
            $invoice = $invoice->fresh(['contract.user', 'financeSale.items']);

            if ($invoice->financeSale) {
                if ($invoice->financeSale->items->count() !== 1) {
                    throw new \RuntimeException('فاتورة المبيعات المحاسبية تحتوي أكثر من بند ولا يمكن مزامنتها تلقائياً.');
                }
                $invoice->financeSale->update([
                    'date' => $invoiceDate->toDateString(),
                    'total_item' => $invoicePayload['subtotal'],
                    'final_total' => $invoicePayload['total_due'],
                    'total_tax_value' => $invoicePayload['vat_amount'],
                ]);
                $invoice->financeSale->items->first()->update([
                    'price' => $invoicePayload['subtotal'],
                    'tax_per' => $invoicePayload['subtotal'] > 0 && $invoicePayload['vat_amount'] > 0
                        ? InvoiceCalculationService::VAT_RATE * 100
                        : 0,
                    'tax_value' => $invoicePayload['vat_amount'],
                    'total_price_before' => $invoicePayload['subtotal'],
                    'final_total' => $invoicePayload['total_due'],
                    'amount' => 1,
                ]);
            }

            $this->assertRecruitmentInvoiceAccountingConfiguration($invoiceDate, $yearService);
            $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
            $nationalityName = $nationality?->sub_title ?? $nationality?->title ?? null;
            $revenueMode = RecruitmentRevenueRecognition::modeForInvoice($invoice);
            $nationalityAccountId = $this->resolveRecruitmentInvoiceCreditAccountId($nationality, $revenueMode);
            if (!$nationalityAccountId) {
                throw new \RuntimeException(
                    $revenueMode === RecruitmentRevenueRecognition::MODE_ON_INVOICE
                        ? 'حساب مبيعات الجنسية غير مربوط أو غير صالح.'
                        : 'حساب التزامات عقود الجنسية غير مربوط أو غير صالح.'
                );
            }
            $clientAccountId = $this->ensureClientAccountId($contract);
            $contractCostCenter = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName);
            $invoiceConstraint = $this->storeInvoiceConstraintForInvoice(
                $invoice,
                $service,
                $yearService,
                $nationalityAccountId,
                $clientAccountId,
                $contractCostCenter->id
            );
            $invoice->update(['invoice_constraint_id' => $invoiceConstraint?->id]);

            $receiptBond = $this->storeReceiptBondForInvoice(
                $invoice,
                $bondService,
                $yearService,
                $clientAccountId,
                (int) $this->resolveMusanedReceiptAccount()->id
            );
            $receiptBond->update(['recruitment_contract_id' => $contract->id]);

            if (!$invoiceConstraint || !$receiptBond->constraint_id) {
                throw new \RuntimeException('لم تكتمل إعادة إنشاء القيود المحاسبية.');
            }

            return [
                'invoice' => $invoice->fresh(),
                'invoice_constraint' => $invoiceConstraint,
                'receipt_bond' => $receiptBond->fresh(),
                'action' => $amountChanged ? 'corrected' : 'accounting_rebuilt',
                'replaced_invoice_constraint_id' => $oldInvoiceConstraintId,
                'replaced_receipt_bond_id' => $oldBond?->id,
                'replaced_receipt_constraint_id' => $oldBondConstraintId,
            ];
        });
    }

    public function queueZatcaSale(int $id)
    {
        try {
            $invoice = Invoice::query()
                ->with(['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'])
                ->findOrFail($id);

            if ($invoice->legacy_zatca_shared) {
                throw new \RuntimeException('هذه الفاتورة مسجلة كمشاركة سابقاً مع هيئة الزكاة، ولن يعاد إرسالها تفادياً للتكرار.');
            }

            DB::transaction(function () use ($invoice) {
                $invoice->update(['send_to_zatca' => true]);
                $this->queueInvoiceForZatcaSale($invoice->fresh(['contract.user', 'ingazDelegation.user', 'client']));
            });

            return back()->with('success', 'تم إدراج الفاتورة ضمن فواتير المبيعات المعلقة لهيئة الزكاة.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function queueRentalZatcaSale(int $id)
    {
        try {
            $invoice = RentalContractInvoice::query()
                ->with(['rentalContract.user', 'rentalContract.maid', 'rentalContract.info.package', 'financeSale.zatca'])
                ->findOrFail($id);

            DB::transaction(function () use ($invoice) {
                $this->queueRentalInvoiceForZatcaSale($invoice);
            });

            return back()->with('success', 'تم إدراج فاتورة التأجير كاملة ضمن فواتير المبيعات، مع إظهار راتب العاملة كبند غير خاضع للضريبة.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function queueRentalAdjustmentZatcaSale(int $id)
    {
        try {
            $adjustment = RentalFinancialAdjustment::query()
                ->with(['rentalContract.financeInvoice.financeSale'])
                ->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction'])
                ->findOrFail($id);
            $this->assertCanAccessBranchModel($adjustment, 'rental_financial_adjustments');
            $invoice = $adjustment->rentalContract?->financeInvoice;

            if (!$invoice) {
                throw new \RuntimeException('لا توجد فاتورة أصلية مرتبطة بهذا الإشعار.');
            }

            DB::transaction(function () use ($adjustment, $invoice) {
                $this->queueRentalCreditNoteForZatcaSale($adjustment, $invoice);
            });

            return back()->with('success', 'تم إدراج إشعار التأجير ضمن مستندات المبيعات المعلقة لهيئة الزكاة.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function legacySourceFile(int $id)
    {
        $invoice = Invoice::query()->findOrFail($id);
        $this->assertCanAccessBranchModel($invoice, 'invoices');

        abort_if(!$invoice->legacy_source_file, 404);

        $directory = base_path('migration_system/data/alrafedin/finance/invoices');
        $path = $directory . DIRECTORY_SEPARATOR . basename((string) $invoice->legacy_source_file);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . ($invoice->legacy_invoice_number ?: $invoice->id) . '.pdf"',
        ]);
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

        $invoice->loadMissing(['contract.user', 'ingazDelegation.user', 'client', 'sponsorshipTransfer.newSponsor']);
        $clientUser = $invoice->sponsorshipTransfer?->newSponsor
            ?: ($invoice->contract?->user ?: ($invoice->ingazDelegation?->user ?: $invoice->client));

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

        if ($invoice->sponsorship_transfer_id && is_array($invoice->line_items)) {
            $remainingDiscount = $this->roundMoney((float) $invoice->discount);
            $saleLineItems = array_filter(
                $invoice->line_items,
                fn (array $item) => (float) ($item['line_subtotal'] ?? 0) > 0
            );
            $lastIndex = array_key_last($saleLineItems);

            foreach ($saleLineItems as $index => $lineItem) {
                $quantity = max(0.001, (float) ($lineItem['quantity'] ?? 1));
                $lineSubtotal = $this->roundMoney((float) ($lineItem['line_subtotal'] ?? 0));
                $lineVat = $this->roundMoney((float) ($lineItem['vat_amount'] ?? 0));
                $allocatedDiscount = $index === $lastIndex
                    ? $remainingDiscount
                    : $this->roundMoney(
                        (float) $invoice->discount * ($invoice->subtotal > 0 ? $lineSubtotal / (float) $invoice->subtotal : 0)
                    );
                $allocatedDiscount = min($allocatedDiscount, $lineSubtotal + $lineVat);
                $remainingDiscount = $this->roundMoney($remainingDiscount - $allocatedDiscount);

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'item_id' => $financeItem->id,
                    'price' => $this->roundMoney((float) ($lineItem['unit_price'] ?? 0)),
                    'discount_per' => 0,
                    'discount_value' => $allocatedDiscount,
                    'tax_per' => (bool) ($lineItem['taxable'] ?? false) ? InvoiceCalculationService::VAT_RATE * 100 : 0,
                    'tax_value' => $lineVat,
                    'total_price_before' => $lineSubtotal,
                    'final_total' => $this->roundMoney($lineSubtotal + $lineVat - $allocatedDiscount),
                    'purchase_price' => 0,
                    'amount' => $quantity,
                    'stock_id' => null,
                    'status' => 'new',
                ]);
            }
        } else {
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
        }

        $invoice->update([
            'send_to_zatca' => true,
            'finance_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    private function queueRentalInvoiceForZatcaSale(RentalContractInvoice $invoice): Sale
    {
        if ($invoice->finance_sale_id) {
            $sale = Sale::query()->find($invoice->finance_sale_id);
            if ($sale) {
                return $sale;
            }

            $invoice->update(['finance_sale_id' => null]);
        }

        $invoice->loadMissing([
            'rentalContract.user',
            'rentalContract.maid',
            'rentalContract.info.package',
        ]);
        $contract = $invoice->rentalContract;
        $clientUser = $contract?->user;

        if (!$contract || !$clientUser) {
            throw new \RuntimeException('فاتورة التأجير غير مرتبطة بعقد وعميل صالحين للمشاركة مع هيئة الزكاة.');
        }

        $monthlySalary = (float) ($contract->info?->monthly_salary ?? 0);
        if ($monthlySalary <= 0) {
            $monthlySalary = (float) ($contract->maid?->resolved_salary ?? $contract->maid?->salary ?? 0);
        }
        $durationDays = RentalContractPricing::durationDays(
            (int) ($contract->info?->contract_duration_days ?? 0),
            (int) ($contract->info?->contract_duration_months ?? 0)
        );
        $grossTotal = $this->roundMoney((float) $invoice->total_due);
        $postedTotal = 0.0;
        if ($invoice->invoice_constraint_id && $clientUser->account_id) {
            $postedTotal = $this->roundMoney((float) BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
                ->where('constraint_id', $invoice->invoice_constraint_id)
                ->where('account_id', $clientUser->account_id)
                ->whereNull('deleted_at')
                ->sum('debtor_value'));
        }
        if ($postedTotal > 0) {
            $grossTotal = $postedTotal;
        }
        if ($grossTotal <= 0) {
            $grossTotal = $this->roundMoney(
                max(0, (float) $invoice->subtotal - (float) $invoice->discount + (float) $invoice->vat_amount)
            );
        }
        if ($postedTotal > 0) {
            $postedBreakdown = app(RentalContractFinanceService::class)
                ->closureAdjustmentAmounts($invoice, $contract, $grossTotal);
            $salaryExemptAmount = (float) $postedBreakdown['salary_exempt_amount'];
            $taxableSubtotal = (float) $postedBreakdown['taxable_subtotal'];
            $vatAmount = (float) $postedBreakdown['vat_amount'];
            $invoiceSubtotal = $this->roundMoney($salaryExemptAmount + $taxableSubtotal);
            $zatcaTotal = $this->roundMoney($invoiceSubtotal + $vatAmount);

            // سطر الفاتورة القديمة قد يخالف قيدها. قبل تكوين حمولة الهيئة نجعل
            // السطر مرآة للقيد المرحّل، من دون المساس بالقيد أو رصيد العميل.
            $storedDiscount = $this->roundMoney((float) $invoice->discount);
            $invoice->update([
                'subtotal' => $this->roundMoney($invoiceSubtotal + $storedDiscount),
                'vat_amount' => $vatAmount,
                'total_due' => $zatcaTotal,
            ]);
        } else {
            $zatcaAmounts = $this->rentalZatcaAmounts(RentalContractPricing::fromGrossPrice(
                $grossTotal,
                $monthlySalary,
                $durationDays,
                (int) ($contract->info?->contract_duration_months ?? 0)
            ));
            $salaryExemptAmount = $zatcaAmounts['salary_exempt_amount'];
            $taxableSubtotal = $zatcaAmounts['taxable_subtotal'];
            $vatAmount = $zatcaAmounts['vat_amount'];
            $invoiceSubtotal = $zatcaAmounts['invoice_subtotal'];
            $zatcaTotal = $zatcaAmounts['total'];
        }

        $accountId = $this->ensureClientUserAccountId($clientUser);
        $financeClient = $this->ensureFinanceClientForInvoice($clientUser, $accountId);
        $financeItem = $taxableSubtotal > 0 ? $this->ensureRentalZatcaServiceItem() : null;
        $salaryItem = $salaryExemptAmount > 0 ? $this->ensureRentalSalaryZatcaItem() : null;

        $sale = Sale::query()->create([
            'code' => $this->nextFinanceSaleCode(),
            'client_id' => $financeClient->id,
            'debit_client_id' => $accountId,
            'date' => optional($contract->contract_date)->toDateString()
                ?: optional($invoice->created_at)->toDateString()
                ?: now()->toDateString(),
            'notes' => 'فاتورة تأجير #' . $invoice->id
                . ' / العقد #' . ($contract->contract_number ?: $contract->id)
                . ' / راتب العاملة غير خاضع للضريبة '
                . number_format($salaryExemptAmount, 2) . ' ريال',
            'total_item' => $invoiceSubtotal,
            'discount' => 0,
            'final_total' => $zatcaTotal,
            'total_tax_value' => $vatAmount,
            'type' => SalesTypeEnum::Sales->value,
            'file' => 'no_file',
            'payment_type' => $invoice->payment_type ?: 'cash',
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        if ($financeItem) {
            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'item_id' => $financeItem->id,
                'price' => $taxableSubtotal,
                'discount_per' => 0,
                'discount_value' => 0,
                'tax_per' => InvoiceCalculationService::VAT_RATE * 100,
                'tax_value' => $vatAmount,
                'total_price_before' => $taxableSubtotal,
                'final_total' => $this->roundMoney($taxableSubtotal + $vatAmount),
                'purchase_price' => 0,
                'amount' => 1,
                'stock_id' => null,
                'status' => 'new',
            ]);
        }

        if ($salaryItem) {
            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'item_id' => $salaryItem->id,
                'price' => $salaryExemptAmount,
                'discount_per' => 0,
                'discount_value' => 0,
                'tax_per' => 0,
                'tax_value' => 0,
                'total_price_before' => $salaryExemptAmount,
                'final_total' => $salaryExemptAmount,
                'purchase_price' => 0,
                'amount' => 1,
                'stock_id' => null,
                'status' => 'new',
            ]);
        }

        $invoice->update([
            'send_to_zatca' => true,
            'finance_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    public function queueRentalCreditNoteForZatcaSale(
        RentalFinancialAdjustment $adjustment,
        RentalContractInvoice $invoice
    ): Sale {
        if ($adjustment->finance_sale_id) {
            $existing = Sale::query()->find($adjustment->finance_sale_id);
            if ($existing) {
                return $existing;
            }
        }

        $originalSale = $invoice->financeSale ?: $this->queueRentalInvoiceForZatcaSale($invoice);
        $originalSale->loadMissing('items');
        $taxableItemId = (int) ($originalSale->items->first(fn ($item) => (float) $item->tax_per > 0)?->item_id
            ?: $this->ensureRentalZatcaServiceItem()->id);
        $exemptItemId = (int) ($originalSale->items->first(fn ($item) => (float) $item->tax_per <= 0)?->item_id
            ?: $this->ensureRentalSalaryZatcaItem()->id);
        $taxable = $this->roundMoney((float) $adjustment->taxable_amount);
        $nonTaxable = $this->roundMoney((float) $adjustment->non_taxable_amount);
        $vat = $this->roundMoney((float) $adjustment->vat_amount);
        $isCreditNote = $adjustment->type === 'expense';

        $sale = Sale::query()->create([
            'code' => $this->nextFinanceSaleCode(),
            'client_id' => $originalSale->client_id,
            'debit_client_id' => $originalSale->debit_client_id,
            'date' => optional($adjustment->issue_date)->toDateString()
                ?: optional($adjustment->created_at)->toDateString()
                ?: now()->toDateString(),
            'notes' => ($isCreditNote ? 'إشعار دائن' : 'إشعار مدين')
                . ' تأجير #' . $adjustment->id . ' للفاتورة #' . $invoice->id,
            'adjustment_reason' => $adjustment->notes,
            'original_sale_id' => $originalSale->id,
            'total_item' => $this->roundMoney($taxable + $nonTaxable),
            'discount' => 0,
            'final_total' => (float) $adjustment->amount,
            'total_tax_value' => $vat,
            'type' => $isCreditNote ? SalesTypeEnum::BackSales->value : SalesTypeEnum::Sales->value,
            'file' => 'no_file',
            'payment_type' => $invoice->payment_type ?: 'cash',
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        foreach ([
            [$taxableItemId, ($isCreditNote ? 'عكس' : 'إضافة') . ' خدمات التأجير', $taxable, $vat, 15],
            [$exemptItemId, ($isCreditNote ? 'عكس' : 'إضافة') . ' راتب العاملة المعفى من الضريبة', $nonTaxable, 0, 0],
        ] as [$itemId, $label, $net, $itemVat, $taxRate]) {
            if ($net <= 0) {
                continue;
            }
            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'item_id' => $itemId,
                'line_description' => $label,
                'price' => $net,
                'discount_per' => 0,
                'discount_value' => 0,
                'tax_per' => $taxRate,
                'tax_value' => $itemVat,
                'total_price_before' => $net,
                'final_total' => $this->roundMoney($net + $itemVat),
                'purchase_price' => 0,
                'amount' => 1,
                'stock_id' => null,
                'status' => 'new',
            ]);
        }

        $adjustment->update(['finance_sale_id' => $sale->id, 'send_to_zatca' => true]);

        return $sale;
    }

    private function rentalZatcaAmounts(array $pricing): array
    {
        $salaryExemptAmount = $this->roundMoney(max(0, (float) ($pricing['salary_exempt_amount'] ?? 0)));
        $taxableSubtotal = $this->roundMoney(max(0, (float) ($pricing['taxable_subtotal'] ?? 0)));
        $vatAmount = $this->roundMoney(max(0, (float) ($pricing['vat_amount'] ?? 0)));
        $invoiceSubtotal = $this->roundMoney($salaryExemptAmount + $taxableSubtotal);
        $serviceTotal = $this->roundMoney($taxableSubtotal + $vatAmount);
        $zatcaTotal = $this->roundMoney($invoiceSubtotal + $vatAmount);

        if ($invoiceSubtotal <= 0) {
            throw new \RuntimeException('لا يوجد مبلغ صالح لإدراجه في فاتورة التأجير المرسلة إلى هيئة الزكاة.');
        }

        return [
            'salary_exempt_amount' => $salaryExemptAmount,
            'taxable_subtotal' => $taxableSubtotal,
            'vat_amount' => $vatAmount,
            'invoice_subtotal' => $invoiceSubtotal,
            'service_total' => $serviceTotal,
            'total' => $zatcaTotal,
        ];
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
                $invoice->legacy_invoice_number ?: $invoice->id,
                $invoice->contract_id
                    ? ('عقد #' . $invoice->contract_id)
                    : ($invoice->ingaz_delegation_id ? ('تفويض #' . $invoice->ingaz_delegation_id) : 'فاتورة عامة'),
                $invoice->contract?->user?->name ?: ($invoice->ingazDelegation?->user?->name ?: ($invoice->client?->name ?: '-')),
                number_format((float) $invoice->subtotal, 2, '.', ''),
                number_format((float) $invoice->vat_amount, 2, '.', ''),
                number_format((float) $invoice->discount, 2, '.', ''),
                number_format((float) $invoice->total_due, 2, '.', ''),
                $this->invoicePaymentStatusLabel($invoice),
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
        ], [], [
            'format' => 'A4-L',
            'mode' => 'utf-8',
        ]);

        return $pdf->download('ingaz-invoices-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function exportInvoicesQuery(Request $request)
    {
        $query = Invoice::query()
            ->with(['contract.user', 'ingazDelegation.user', 'client', 'financeSale.zatca'])
            ->latest('id');

        if ($this->usesLegacyInvoiceIndex()) {
            $query->whereNotNull('legacy_invoice_number');
            $this->applyInvoiceIndexFilters($query, $request);
        }

        if ($this->branchesEnabledFor('invoices') && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return $query;
    }

    private function exportZatcaStatusLabel(Invoice $invoice): string
    {
        if ($invoice->legacy_zatca_shared) {
            return 'مشارك سابقاً مع الهيئة';
        }

        if ($invoice->legacy_invoice_number && $invoice->legacy_zatca_shared === false) {
            return 'غير مشارك في المصدر';
        }

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

    private function invoicePaymentStatusLabel(Invoice $invoice): string
    {
        return ((bool) $invoice->is_paid || (float) $invoice->total_due <= 0)
            ? 'مدفوعة'
            : 'غير مدفوعة';
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

    private function ensureRentalZatcaServiceItem(): Item
    {
        $item = Item::query()
            ->where('code_string', 'RENTAL-ZATCA-INVOICE')
            ->first();

        if ($item) {
            return $item;
        }

        $categoryId = $this->ensureZatcaServiceCategoryId();
        $unitId = $this->ensureZatcaServiceUnitId();
        $accountId = $this->ensureZatcaServiceSalesAccountId();

        return Item::query()->create([
            'name' => 'خدمة تأجير عاملة منزلية',
            'main_category_id' => $categoryId,
            'sub_category_id' => null,
            'type' => CategoryTypes::Service->value,
            'code_string' => 'RENTAL-ZATCA-INVOICE',
            'code' => ((int) Item::query()->max('code')) + 1,
            'min_order' => 0,
            'sale_unit_id' => $unitId,
            'sale_price' => 0,
            'purchase_unit_id' => $unitId,
            'purchase_price' => 0,
            'details' => 'صنف خدمي تلقائي لفواتير التأجير المرسلة إلى هيئة الزكاة دون راتب العاملة.',
            'sale_account_id' => $accountId,
            'stock_account_id' => $accountId,
            'stock_tracking' => 0,
            'show_pos' => 0,
            'show_website' => 0,
        ]);
    }

    private function ensureRentalSalaryZatcaItem(): Item
    {
        $item = Item::query()
            ->where('code_string', 'RENTAL-WORKER-SALARY-OOS')
            ->first();

        if ($item) {
            return $item;
        }

        $categoryId = $this->ensureZatcaServiceCategoryId();
        $unitId = $this->ensureZatcaServiceUnitId();
        $accountId = $this->ensureZatcaServiceSalesAccountId();

        return Item::query()->create([
            'name' => 'راتب العاملة - غير خاضع للضريبة',
            'main_category_id' => $categoryId,
            'sub_category_id' => null,
            'type' => CategoryTypes::Service->value,
            'code_string' => 'RENTAL-WORKER-SALARY-OOS',
            'code' => ((int) Item::query()->max('code')) + 1,
            'min_order' => 0,
            'sale_unit_id' => $unitId,
            'sale_price' => 0,
            'purchase_unit_id' => $unitId,
            'purchase_price' => 0,
            'details' => 'بند راتب العاملة ضمن فاتورة التأجير، غير خاضع لضريبة القيمة المضافة.',
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

        $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
        $clientAccountId = $automaticEntriesEnabled
            ? $this->ensureClientUserAccountId($delegation->user)
            : null;
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
            'created_at' => $this->resolveInvoiceDateTime($request),
        ]);

        $invoice->setRelation('ingazDelegation', $delegation);

        $invoiceConstraint = null;
        $receiptBond = null;
        $delegationCostConstraint = null;

        if ($automaticEntriesEnabled && (float) $invoice->total_due > 0) {
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

        $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
        $clientAccountId = $automaticEntriesEnabled
            ? $this->ensureClientUserAccountId($client)
            : null;

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
            'created_at' => $this->resolveInvoiceDateTime($request),
        ]);

        $invoice->setRelation('client', $client);

        $invoiceConstraint = null;
        $receiptBond = null;

        if ($automaticEntriesEnabled && (float) $invoice->total_due > 0) {
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

    private function storeSponsorshipTransferInvoice(
        Request $request,
        bool $sendToZatca,
        ObjService $service,
        YearService $yearService
    ): array {
        $transfer = SponsorshipTransfer::query()
            ->with(['newSponsor', 'contract.maid'])
            ->lockForUpdate()
            ->findOrFail($request->sponsorship_transfer_id);
        $this->assertCanAccessBranchModel($transfer, 'sponsorship_transfers');

        if ($transfer->invoice()->exists()) {
            throw new \RuntimeException('طلب نقل الخدمات لديه فاتورة سابقة');
        }

        if (!$transfer->newSponsor) {
            throw new \RuntimeException('طلب نقل الخدمات غير مرتبط بالعميل الجديد، لا يمكن إصدار الفاتورة.');
        }

        $items = $this->normalizeSponsorshipTransferItems((array) $request->input('line_items', []));
        $subtotal = $this->roundMoney(array_sum(array_column($items, 'line_subtotal')));
        $vatAmount = $this->roundMoney(array_sum(array_column($items, 'vat_amount')));
        $discount = min($this->roundMoney((float) $request->input('discount', 0)), $subtotal);
        $totalDue = $this->roundMoney(max(0, $subtotal + $vatAmount - $discount));
        $isPaid = (float) $transfer->paid_amount + 0.009 >= $totalDue;

        $invoice = Invoice::query()->create([
            'contract_id' => null,
            'ingaz_delegation_id' => null,
            'client_id' => $transfer->new_sponsor_id,
            'sponsorship_transfer_id' => $transfer->id,
            'general_description' => trim((string) $request->input('general_description'))
                ?: 'فاتورة عملية نقل خدمة رقم ' . $transfer->id,
            'line_items' => $items,
            // تُحفظ القيمة الصافية أيضاً في الحقول القديمة حتى تظل التقارير والقيود
            // الموحدة متوافقة مع الفواتير السابقة.
            'direct_recruitment_cost' => $subtotal,
            'direct_recruitment_count' => $subtotal > 0 ? 1 : 0,
            'external_recruitment_cost' => 0,
            'external_recruitment_count' => 0,
            'government_cost' => 0,
            'government_cost_count' => 0,
            'internal_ticket_cost' => 0,
            'internal_ticket_count' => 0,
            'visa_recruited_count' => 0,
            'insured_count' => 0,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? ($transfer->payment_confirmed_at ?? now()) : null,
            'send_to_zatca' => $sendToZatca,
            'created_at' => $this->resolveInvoiceDateTime($request),
        ]);
        $invoice->setRelation('sponsorshipTransfer', $transfer);
        $invoice->setRelation('client', $transfer->newSponsor);

        $invoiceConstraint = null;
        if (AutomaticJournalEntries::enabled() && $totalDue > 0) {
            $clientAccountId = $this->ensureClientUserAccountId($transfer->newSponsor);
            $invoiceConstraint = $this->storeInvoiceConstraintForSponsorshipTransfer(
                $invoice,
                $service,
                $yearService,
                $clientAccountId
            );
            $invoice->update(['invoice_constraint_id' => $invoiceConstraint?->id]);
        }

        if ($invoice->send_to_zatca) {
            $this->queueInvoiceForZatcaSale($invoice);
        }

        return [
            'invoice' => $invoice,
            'invoice_constraint' => $invoiceConstraint,
            // التحصيل تم على طلب النقل نفسه؛ لا ننشئ سند قبض ثانياً.
            'receipt_bond' => null,
        ];
    }

    private function storeInvoiceConstraintForSponsorshipTransfer(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $clientAccountId
    ): ?Constraint {
        if ((float) $invoice->total_due <= 0) {
            return null;
        }

        $revenueAccountId = $this->resolveSettingAccountId(
            self::SETTING_DELEGATION_REVENUE_ACCOUNT,
            'حساب إيراد فواتير نقل الخدمات غير محدد. الرجاء تحديد حساب إيراد تفاويض إنجاز من صفحة إعدادات الحسابات أولاً.',
            'creditor'
        );
        $statement = $this->buildInvoiceStatement($invoice);
        $lines = [];
        $remainingDiscount = $this->roundMoney((float) $invoice->discount);
        $items = array_filter(
            (array) $invoice->line_items,
            fn (array $item) => (float) ($item['line_subtotal'] ?? 0) > 0
        );
        $lastIndex = array_key_last($items);

        foreach ($items as $index => $item) {
            $lineSubtotal = $this->roundMoney((float) ($item['line_subtotal'] ?? 0));
            $allocatedDiscount = $index === $lastIndex
                ? $remainingDiscount
                : $this->roundMoney(
                    (float) $invoice->discount * ($invoice->subtotal > 0 ? $lineSubtotal / (float) $invoice->subtotal : 0)
                );
            $allocatedDiscount = min($allocatedDiscount, $lineSubtotal);
            $remainingDiscount = $this->roundMoney($remainingDiscount - $allocatedDiscount);
            $netAmount = $this->roundMoney($lineSubtotal - $allocatedDiscount);

            if ($netAmount > 0) {
                $this->appendConstraintPair(
                    $lines,
                    $revenueAccountId,
                    $clientAccountId,
                    $netAmount,
                    $statement . ' / ' . ($item['label'] ?? 'بند فاتورة')
                );
            }
        }

        if ((float) $invoice->vat_amount > 0) {
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
                (float) $invoice->vat_amount,
                $statement . ' / ضريبة القيمة المضافة'
            );
        }

        return $this->storeConstraintFromLines(
            $service,
            $yearService,
            $invoice,
            $statement,
            $lines,
            (float) $invoice->total_due
        );
    }

    private function normalizeSponsorshipTransferItems(array $submittedItems): array
    {
        $coreTaxRules = [
            'licensee_cost' => true,
            'external_recruitment' => false,
            'government_cost' => false,
            'worker_salary' => false,
        ];
        $seenCoreKeys = [];
        $items = [];

        foreach ($submittedItems as $index => $submittedItem) {
            $key = trim((string) ($submittedItem['key'] ?? ''));
            $label = trim((string) ($submittedItem['label'] ?? ''));
            $quantity = round((float) ($submittedItem['quantity'] ?? 0), 3);
            $unitPrice = $this->roundMoney((float) ($submittedItem['unit_price'] ?? 0));

            if ($label === '' || $quantity <= 0 || $unitPrice < 0) {
                continue;
            }

            if (array_key_exists($key, $coreTaxRules)) {
                if (isset($seenCoreKeys[$key])) {
                    throw new \RuntimeException('لا يمكن تكرار أحد البنود الأساسية في فاتورة نقل الخدمات.');
                }
                $seenCoreKeys[$key] = true;
                $taxable = $coreTaxRules[$key];
            } else {
                $key = 'additional_' . ($index + 1);
                $taxable = filter_var($submittedItem['taxable'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }

            $lineSubtotal = $this->roundMoney($quantity * $unitPrice);
            $lineVat = $taxable
                ? $this->roundMoney($lineSubtotal * InvoiceCalculationService::VAT_RATE)
                : 0.0;

            $items[] = [
                'key' => $key,
                'label' => $label,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'taxable' => $taxable,
                'tax_rate' => $taxable ? InvoiceCalculationService::VAT_RATE : 0.0,
                'line_subtotal' => $lineSubtotal,
                'vat_amount' => $lineVat,
                'line_total' => $this->roundMoney($lineSubtotal + $lineVat),
            ];
        }

        if (!isset($seenCoreKeys['licensee_cost'])) {
            throw new \RuntimeException('بند تكاليف المرخص له مطلوب في فاتورة نقل الخدمات.');
        }

        if ($items === [] || array_sum(array_column($items, 'line_total')) <= 0) {
            throw new \RuntimeException('يجب أن تحتوي الفاتورة على بند واحد بقيمة أكبر من صفر.');
        }

        return $items;
    }

    private function storeInvoiceConstraintForIngazDelegation(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService,
        int $clientAccountId
    ): ?Constraint
    {
        return app(\App\Services\ingaz\DelegationInvoiceJournalService::class)->ensure(
            $invoice,
            fn () => $this->createInvoiceConstraintForIngazDelegation($invoice, $service, $yearService, $clientAccountId)
        );
    }

    private function createInvoiceConstraintForIngazDelegation(
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
            self::SETTING_GENERAL_INVOICE_REVENUE_ACCOUNT,
            'حساب إيراد الفواتير العامة غير محدد. الرجاء تحديده من صفحة إعدادات الحسابات أولاً.',
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

        $pdf = PDF::loadView('admin.ingaz.invoices.invoice', $viewData, [], [
            'format' => 'A4',
            'mode' => 'utf-8',
        ]);

        if (request()->boolean('download')) {
            return $pdf->download('invoice-' . $invoice->id . '.pdf');
        }

        return $pdf->stream('invoice-' . $invoice->id . '.pdf');
    }

    public function createRecruitmentCreditNote(int $id)
    {
        abort_unless($this->canCreateInvoiceCreditNote(), 403);
        $invoice = Invoice::query()
            ->with(['contract.user', 'contract.info', 'ingazDelegation.user', 'client', 'recruitmentCreditNotes'])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($invoice, 'invoices');

        abort_unless(
            ($invoice->contract_id && $invoice->contract)
                || ($invoice->ingaz_delegation_id && $invoice->ingazDelegation)
                || ($invoice->client_id && $invoice->client && !$invoice->sponsorship_transfer_id),
            422,
            'إشعار الدائن متاح لفواتير الاستقدام والتفاويض والفواتير العامة فقط.'
        );

        $remaining = $this->recruitmentCreditNoteRemaining($invoice);

        return view('admin.ingaz.invoices.credit-note-create', compact('invoice', 'remaining'));
    }

    public function storeRecruitmentCreditNote(
        Request $request,
        int $id,
        ObjService $service,
        YearService $yearService,
        \App\Services\commissions\DelegationCommissionService $delegationCommissionService
    ) {
        abort_unless($this->canCreateInvoiceCreditNote(), 403);
        $validated = $request->validate([
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
            'taxable_amount' => ['required', 'numeric', 'min:0'],
            'non_taxable_amount' => ['required', 'numeric', 'min:0'],
            'send_to_zatca' => ['nullable', 'boolean'],
        ]);

        try {
            $creditNote = DB::transaction(function () use ($validated, $id, $service, $yearService, $delegationCommissionService) {
                $invoice = Invoice::query()
                    ->with(['contract.user', 'ingazDelegation.user', 'client', 'financeSale.items', 'recruitmentCreditNotes'])
                    ->lockForUpdate()
                    ->findOrFail($id);
                $this->assertCanAccessBranchModel($invoice, 'invoices');

                if ((!$invoice->contract_id || !$invoice->contract)
                    && (!$invoice->ingaz_delegation_id || !$invoice->ingazDelegation)
                    && (!$invoice->client_id || !$invoice->client || $invoice->sponsorship_transfer_id)) {
                    throw new \RuntimeException('إشعار الدائن متاح لفواتير الاستقدام والتفاويض والفواتير العامة فقط.');
                }

                if ($invoice->ingaz_delegation_id && !$invoice->invoice_constraint_id) {
                    $clientAccountId = $this->ensureClientUserAccountId($invoice->ingazDelegation->user);
                    $sourceConstraint = $this->storeInvoiceConstraintForIngazDelegation(
                        $invoice,
                        $service,
                        $yearService,
                        $clientAccountId
                    );

                    if (!$sourceConstraint) {
                        throw new \RuntimeException('تعذر إنشاء قيد الفاتورة الأصلي لتفويض الإنجاز.');
                    }

                    $invoice->update(['invoice_constraint_id' => $sourceConstraint->id]);
                    $invoice->setRelation('invoiceConstraint', $sourceConstraint);
                }

                if ($invoice->contract_id
                    && !$invoice->invoice_constraint_id
                    && !$invoice->arrival_revenue_constraint_id) {
                    $sourceConstraint = $this->ensureRecruitmentInvoiceConstraintForCreditNote(
                        $invoice,
                        $service,
                        $yearService
                    );
                    $invoice->update(['invoice_constraint_id' => $sourceConstraint->id]);
                    $invoice->setRelation('invoiceConstraint', $sourceConstraint);
                }

                if (!$invoice->contract_id && !$invoice->ingaz_delegation_id
                    && $invoice->client_id && !$invoice->invoice_constraint_id) {
                    $clientAccountId = $this->ensureClientUserAccountId($invoice->client);
                    $sourceConstraint = $this->storeInvoiceConstraintForGeneralInvoice(
                        $invoice,
                        $service,
                        $yearService,
                        $clientAccountId
                    );

                    if (!$sourceConstraint) {
                        throw new \RuntimeException('تعذر إنشاء قيد الفاتورة العامة الأصلي.');
                    }

                    $invoice->update(['invoice_constraint_id' => $sourceConstraint->id]);
                    $invoice->setRelation('invoiceConstraint', $sourceConstraint);
                }

                if (!$invoice->invoice_constraint_id && !$invoice->arrival_revenue_constraint_id) {
                    throw new \RuntimeException('لا يمكن إنشاء إشعار دائن قبل وجود قيد الفاتورة أو قيد إيراد الوصول.');
                }

                $issueDate = \Carbon\Carbon::parse($validated['issue_date'])->startOfDay();
                $invoiceDate = optional($invoice->created_at)->copy()?->startOfDay();
                if ($invoiceDate && $issueDate->lt($invoiceDate)) {
                    throw new \RuntimeException('تاريخ إشعار الدائن لا يمكن أن يسبق تاريخ الفاتورة الأصلية.');
                }

                $remaining = $this->recruitmentCreditNoteRemaining($invoice);
                $taxable = $this->roundMoney((float) $validated['taxable_amount']);
                $nonTaxable = $this->roundMoney((float) $validated['non_taxable_amount']);
                $vat = $this->roundMoney($taxable * InvoiceCalculationService::VAT_RATE);
                $subtotal = $this->roundMoney($taxable + $nonTaxable);
                $total = $this->roundMoney($subtotal + $vat);

                if ($total <= 0) {
                    throw new \RuntimeException('يجب أن تكون قيمة إشعار الدائن أكبر من صفر.');
                }
                if ($taxable > $remaining['taxable_amount'] + 0.01
                    || $nonTaxable > $remaining['non_taxable_amount'] + 0.01
                    || $vat > $remaining['vat_amount'] + 0.01
                    || $total > $remaining['total'] + 0.01) {
                    throw new \RuntimeException('قيمة إشعار الدائن تتجاوز الرصيد المتبقي من الفاتورة الأصلية.');
                }

                $lineItems = $this->buildRecruitmentCreditNoteItems($invoice, $taxable, $nonTaxable);

                $creditNote = RecruitmentInvoiceCreditNote::query()->create([
                    'invoice_id' => $invoice->id,
                    'contract_id' => $invoice->contract_id,
                    'ingaz_delegation_id' => $invoice->ingaz_delegation_id,
                    'issue_date' => $issueDate->toDateString(),
                    'reason' => trim($validated['reason']),
                    'taxable_amount' => $taxable,
                    'non_taxable_amount' => $nonTaxable,
                    'subtotal' => $subtotal,
                    'vat_amount' => $vat,
                    'total' => $total,
                    'line_items' => $lineItems,
                    'send_to_zatca' => (bool) ($validated['send_to_zatca'] ?? true),
                    'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
                ]);

                $constraint = $this->storeRecruitmentCreditNoteConstraint(
                    $creditNote,
                    $invoice,
                    $service,
                    $yearService
                );
                $sale = $this->storeRecruitmentCreditNoteSale($creditNote, $invoice);

                $creditNote->update([
                    'constraint_id' => $constraint->id,
                    'finance_sale_id' => $sale->id,
                ]);

                if ($invoice->ingazDelegation) {
                    $delegationCommissionService->cancelAfterFullCreditNote(
                        $invoice->ingazDelegation,
                        Auth::guard('admin')->id() ?: Auth::id()
                    );
                }

                return $creditNote->fresh(['invoice', 'contract', 'constraint', 'financeSale']);
            });

            if ($creditNote->ingaz_delegation_id) {
                $delegation = IngazDelegation::query()->find($creditNote->ingaz_delegation_id);
                if ($delegation) {
                    IngazDelegationTimeline::record($delegation, 'credit_note_created', [
                        'summary' => 'تم إنشاء إشعار دائن مرتبط بفاتورة التفويض.',
                        'credit_note_id' => $creditNote->id,
                        'invoice_id' => $creditNote->invoice_id,
                        'total' => (float) $creditNote->total,
                        'reason' => $creditNote->reason,
                    ]);
                }
            }

            $redirect = InvoiceAuthorization::canPrintCreditNotes()
                ? redirect()->route('recruitment-credit-notes.download', ['id' => $creditNote->id, 'autoprint' => 1])
                : redirect()->route('invoices.credit-notes.create', $id);

            return $redirect->with('success', 'تم إنشاء إشعار الدائن وقيده المالي، ويُلغى التفويض تلقائيًا عند عكس كامل قيمة فاتورته.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function downloadRecruitmentCreditNote(int $id)
    {
        abort_unless($this->canViewInvoiceCreditNote(), 403);
        $creditNote = RecruitmentInvoiceCreditNote::query()
                ->with(['invoice.contract.user', 'invoice.ingazDelegation.user', 'invoice.client', 'financeSale.zatca', 'constraint'])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($creditNote->invoice, 'invoices');

        $viewData = $this->prepareInvoiceViewData((int) $creditNote->invoice_id);
        $viewData['creditNote'] = $creditNote;
        $viewData['invoiceTitle'] = 'إشعار دائن ضريبي';
        $viewData['invoiceSubtitle'] = 'Tax Credit Note';
        $viewData['documentNumberLabel'] = 'رقم إشعار الدائن';
        $viewData['documentNumber'] = $creditNote->id;
        $viewData['originalInvoiceNumber'] = $creditNote->invoice->legacy_invoice_number ?: $creditNote->invoice_id;
        $viewData['creditNoteReason'] = $creditNote->reason;
        $viewData['invoiceDate'] = $creditNote->issue_date->format('Y-m-d');
        $viewData['invoiceDateTime'] = $creditNote->issue_date->format('Y-m-d') . ' 00:00:00';
        $viewData['invoiceStatus'] = 'رصيد دائن للعميل';
        $sourceLabel = $creditNote->contract_id
            ? 'عقد الاستقدام'
            : ($creditNote->ingaz_delegation_id ? 'تفويض الإنجاز' : 'فاتورة عامة');
        $viewData['serviceLongDescription'] = 'إشعار دائن مرتبط بفاتورة ' . $sourceLabel . ' رقم '
            . $viewData['originalInvoiceNumber'] . ' - السبب: ' . $creditNote->reason;
        $viewData['items'] = collect($creditNote->line_items)->map(fn (array $item) => [
            'label' => $item['label'],
            'unit' => (float) $item['unit_price'],
            'qty' => (float) $item['quantity'],
            'line_subtotal' => (float) ($item['line_subtotal'] ?? ((float) $item['unit_price'] * (float) $item['quantity'])),
            'line_discount' => 0,
            'line_total' => (float) ($item['line_subtotal'] ?? ((float) $item['unit_price'] * (float) $item['quantity'])),
            'vat_rate' => ((float) $item['tax_rate']) / 100,
            'vat_amount' => (float) $item['vat_amount'],
            'line_grand_total' => (float) $item['line_total'],
        ])->all();
        $viewData['subtotalBeforeDiscount'] = (float) $creditNote->subtotal;
        $viewData['discountTotal'] = 0.0;
        $viewData['netSubtotal'] = (float) $creditNote->subtotal;
        $viewData['vatAmount'] = (float) $creditNote->vat_amount;
        $viewData['grandTotal'] = (float) $creditNote->total;
        $viewData['invoiceUrl'] = route('recruitment-credit-notes.download', $creditNote->id);
        $qrValue = $creditNote->financeSale?->zatca?->qr ?: $this->buildInvoiceQrSerial(
            $viewData['supplierName'],
            $viewData['supplierTaxNumber'],
            $viewData['invoiceDateTime'],
            (float) $creditNote->total,
            (float) $creditNote->vat_amount
        );
        $viewData['qrImage'] = InvoiceQrCode::render($qrValue);

        return response()->view('admin.ingaz.invoices.print', $viewData, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function createSystemCreditNoteForRecruitmentRefund(
        Invoice $invoice,
        string $issueDate,
        string $reason,
        ObjService $service,
        YearService $yearService
    ): ?RecruitmentInvoiceCreditNote {
        $invoice->loadMissing([
            'contract.user',
            'financeSale.items',
            'recruitmentCreditNotes',
            'invoiceConstraint.constraintDetails',
            'arrivalRevenueConstraint.constraintDetails',
        ]);

        if (!$invoice->contract_id || !$invoice->contract) {
            throw new \RuntimeException('الاسترداد التلقائي متاح لفاتورة عقد استقدام فقط.');
        }

        $remaining = $this->recruitmentCreditNoteRemaining($invoice);
        if ((float) $remaining['total'] <= 0.01) {
            return null;
        }

        if (!$invoice->invoice_constraint_id && !$invoice->arrival_revenue_constraint_id) {
            $sourceConstraint = $this->ensureRecruitmentInvoiceConstraintForCreditNote(
                $invoice,
                $service,
                $yearService
            );
            $invoice->update(['invoice_constraint_id' => $sourceConstraint->id]);
            $invoice->setRelation('invoiceConstraint', $sourceConstraint);
        }

        $creditNote = RecruitmentInvoiceCreditNote::query()->create([
            'invoice_id' => $invoice->id,
            'contract_id' => $invoice->contract_id,
            'ingaz_delegation_id' => null,
            'issue_date' => $issueDate,
            'reason' => trim($reason),
            'taxable_amount' => $remaining['taxable_amount'],
            'non_taxable_amount' => $remaining['non_taxable_amount'],
            'subtotal' => $remaining['subtotal'],
            'vat_amount' => $remaining['vat_amount'],
            'total' => $remaining['total'],
            'line_items' => $this->buildRecruitmentCreditNoteItems(
                $invoice,
                (float) $remaining['taxable_amount'],
                (float) $remaining['non_taxable_amount']
            ),
            'send_to_zatca' => true,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
        ]);

        $constraint = $this->storeRecruitmentCreditNoteConstraint($creditNote, $invoice, $service, $yearService);
        $sale = $this->storeRecruitmentCreditNoteSale($creditNote, $invoice);
        $creditNote->update([
            'constraint_id' => $constraint->id,
            'finance_sale_id' => $sale->id,
        ]);

        return $creditNote->fresh(['constraint', 'financeSale']);
    }

    private function ensureRecruitmentInvoiceConstraintForCreditNote(
        Invoice $invoice,
        ObjService $service,
        YearService $yearService
    ): Constraint {
        $invoice->loadMissing([
            'contract.user',
            'contract.maid.nationality',
            'contract.requiredMaidNationality',
        ]);
        $contract = $invoice->contract;
        if (!$contract) {
            throw new \RuntimeException('الفاتورة غير مرتبطة بعقد استقدام صالح.');
        }

        $invoiceDate = optional($invoice->created_at)?->copy() ?: now();
        $this->assertRecruitmentInvoiceAccountingConfiguration($invoiceDate, $yearService);
        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
        $nationalityName = $nationality?->sub_title ?? $nationality?->title ?? null;
        $revenueMode = RecruitmentRevenueRecognition::modeForInvoice($invoice);
        $nationalityAccountId = $this->resolveRecruitmentInvoiceCreditAccountId($nationality, $revenueMode);
        if (!$nationalityAccountId) {
            throw new \RuntimeException(
                $revenueMode === RecruitmentRevenueRecognition::MODE_ON_INVOICE
                    ? 'حساب مبيعات الجنسية غير مربوط أو غير صالح.'
                    : 'حساب التزامات عقود الجنسية غير مربوط أو غير صالح.'
            );
        }

        $constraint = $this->storeInvoiceConstraintForInvoice(
            $invoice,
            $service,
            $yearService,
            $nationalityAccountId,
            $this->ensureClientAccountId($contract),
            $this->ensureRecruitmentContractCostCenter($contract, $nationalityName)->id
        );
        if (!$constraint) {
            throw new \RuntimeException('تعذر إنشاء قيد الفاتورة الأصلي قبل إشعار الدائن.');
        }

        return $constraint;
    }

    public function prepareInvoiceViewData(int $id): array
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
            'sponsorshipTransfer.newSponsor',
            'sponsorshipTransfer.contract.maid',
            'sponsorshipTransfer.receiptBond.constraint',
            'invoiceConstraint',
            'musanedTransferItem.transfer',
            'financeSale.zatca',
            'recruitmentCreditNotes.constraint',
            'recruitmentCreditNotes.financeSale',
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

        $serviceDesc = $invoice->sponsorship_transfer_id
            ? 'نقل الخدمات'
            : ($invoice->ingaz_delegation_id ? 'تفويض إنجاز' : ($invoice->client_id ? 'فاتورة عامة' : 'طلب تعاقد'));
        $serviceLongDescription = $this->buildServiceDescription($invoice);
        $items = $this->buildInvoiceItems($invoice);
        $invoiceStatus = $this->invoicePaymentStatusLabel($invoice);
        $printedBy = optional(Auth::guard('admin')->user())->name
            ?? optional(Auth::user())->name
            ?? 'النظام';
        $printedAt = now();

        $client = $invoice->sponsorshipTransfer?->newSponsor
            ?: ($invoice->contract?->user ?: ($invoice->ingazDelegation?->user ?: $invoice->client));
        $customerName = $client?->name ?: '---';
        $customerDocumentLabel = $client?->documentNumberLabel() ?: __('ingaz.national_num');
        $customerNationalId = $client?->documentNumber() ?: '---';
        $customerPhone = $this->formatPhone((string) ($client?->phone_code ?? ''), (string) ($client?->phone ?? ''));
        $customerAddress = $client?->address ?: '---';
        $accountNumber = $client?->account_id
            ?: ($client?->id ?: ($invoice->sponsorship_transfer_id ?: ($invoice->contract_id ?: $invoice->ingaz_delegation_id)));

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
        if ($invoice->sponsorship_transfer_id && is_array($invoice->line_items)) {
            return collect($invoice->line_items)
                ->map(function (array $item) {
                    $quantity = (float) ($item['quantity'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $lineSubtotal = (float) ($item['line_subtotal'] ?? ($quantity * $unitPrice));
                    $vatAmount = (float) ($item['vat_amount'] ?? 0);

                    return [
                        'key' => (string) ($item['key'] ?? ''),
                        'label' => (string) ($item['label'] ?? 'بند'),
                        'qty' => $quantity,
                        'unit' => $unitPrice,
                        'line_subtotal' => $lineSubtotal,
                        'line_discount' => 0.0,
                        'line_total' => $lineSubtotal,
                        'vat_rate' => (float) ($item['tax_rate'] ?? ((bool) ($item['taxable'] ?? false) ? InvoiceCalculationService::VAT_RATE : 0)),
                        'vat_amount' => $vatAmount,
                        'line_grand_total' => (float) ($item['line_total'] ?? ($lineSubtotal + $vatAmount)),
                    ];
                })
                ->filter(fn (array $item) => $item['line_grand_total'] > 0)
                ->values()
                ->all();
        }

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

    private function resolveInvoiceDateTime(Request $request): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $request->input('invoice_date') . ' ' . now()->format('H:i:s'),
            config('app.timezone')
        );
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
        if ($invoice->sponsorship_transfer_id) {
            $transfer = $invoice->sponsorshipTransfer;
            $clientName = $transfer?->newSponsor?->name ?: $invoice->client?->name ?: 'غير محدد';
            $maidName = $transfer?->contract?->maid?->name ?: 'غير محدد';
            $description = trim((string) ($invoice->general_description ?? ''));

            return 'عملية نقل الخدمات - رقم الطلب: ' . $invoice->sponsorship_transfer_id
                . ' - العميل: ' . $clientName
                . ' - العاملة: ' . $maidName
                . ($description !== '' ? ' - التفاصيل: ' . $description : '');
        }

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

    private function recruitmentCreditNoteRemaining(Invoice $invoice): array
    {
        $invoice->loadMissing('recruitmentCreditNotes');
        $netTotal = $this->roundMoney(max(0, (float) $invoice->total_due - (float) $invoice->vat_amount));
        $taxableTotal = $this->roundMoney(min(
            $netTotal,
            (float) $invoice->vat_amount > 0
                ? (float) $invoice->vat_amount / InvoiceCalculationService::VAT_RATE
                : 0
        ));
        $nonTaxableTotal = $this->roundMoney(max(0, $netTotal - $taxableTotal));

        return [
            'taxable_amount' => $this->roundMoney(max(0, $taxableTotal - (float) $invoice->recruitmentCreditNotes->sum('taxable_amount'))),
            'non_taxable_amount' => $this->roundMoney(max(0, $nonTaxableTotal - (float) $invoice->recruitmentCreditNotes->sum('non_taxable_amount'))),
            'vat_amount' => $this->roundMoney(max(0, (float) $invoice->vat_amount - (float) $invoice->recruitmentCreditNotes->sum('vat_amount'))),
            'subtotal' => $this->roundMoney(max(0, $netTotal - (float) $invoice->recruitmentCreditNotes->sum('subtotal'))),
            'total' => $this->roundMoney(max(0, (float) $invoice->total_due - (float) $invoice->recruitmentCreditNotes->sum('total'))),
        ];
    }

    private function buildRecruitmentCreditNoteItems(
        Invoice $invoice,
        float $taxableAmount,
        float $nonTaxableAmount
    ): array {
        $invoiceItems = collect($this->buildInvoiceItems($invoice));
        $result = collect();

        foreach ([true => $taxableAmount, false => $nonTaxableAmount] as $isTaxable => $requestedAmount) {
            $requestedAmount = $this->roundMoney((float) $requestedAmount);
            if ($requestedAmount <= 0) {
                continue;
            }

            $matchingItems = $invoiceItems
                ->filter(fn (array $item) => ((float) ($item['vat_rate'] ?? 0) > 0) === (bool) $isTaxable)
                ->values();
            $sourceTotal = $this->roundMoney((float) $matchingItems->sum('line_total'));
            if ($matchingItems->isEmpty() || $sourceTotal <= 0) {
                throw new \RuntimeException('تعذر مطابقة مبلغ إشعار الدائن مع بنود الفاتورة الأصلية.');
            }

            $remaining = $requestedAmount;
            $matchingItems->each(function (array $item, int $index) use (
                $matchingItems,
                $requestedAmount,
                $sourceTotal,
                $isTaxable,
                &$remaining,
                $result
            ) {
                $allocatedNet = $index === $matchingItems->count() - 1
                    ? $remaining
                    : $this->roundMoney(
                        $requestedAmount * ((float) $item['line_total'] / $sourceTotal)
                    );
                $remaining = $this->roundMoney($remaining - $allocatedNet);
                if ($allocatedNet <= 0) {
                    return;
                }

                $quantity = max(1, (float) ($item['qty'] ?? 1));
                $vatAmount = $isTaxable
                    ? $this->roundMoney($allocatedNet * InvoiceCalculationService::VAT_RATE)
                    : 0.0;

                $result->push([
                    'key' => (string) ($item['key'] ?? ''),
                    'label' => (string) ($item['label'] ?? 'بند'),
                    'quantity' => $quantity,
                    'unit_price' => $this->roundMoney($allocatedNet / $quantity),
                    'line_subtotal' => $allocatedNet,
                    'tax_rate' => $isTaxable ? 15 : 0,
                    'vat_amount' => $vatAmount,
                    'line_total' => $this->roundMoney($allocatedNet + $vatAmount),
                ]);
            });
        }

        return $result->values()->all();
    }

    private function storeRecruitmentCreditNoteConstraint(
        RecruitmentInvoiceCreditNote $creditNote,
        Invoice $invoice,
        ObjService $service,
        YearService $yearService
    ): Constraint {
        $activeYear = $yearService->checkDateRange($creditNote->issue_date->format('Y-m-d'));
        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ إشعار الدائن.');
        }

        $invoice->loadMissing(['contract.user', 'ingazDelegation.user', 'client', 'invoiceConstraint.constraintDetails', 'arrivalRevenueConstraint.constraintDetails']);
        $clientAccountId = (int) ($invoice->contract?->user?->account_id
            ?: $invoice->ingazDelegation?->user?->account_id
            ?: $invoice->client?->account_id
            ?: 0);
        if (!$clientAccountId) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للعميل المرتبط بالعقد.');
        }

        $sourceDetails = $invoice->invoice_constraint_id
            ? ConstraintDetails::query()->where('constraint_id', $invoice->invoice_constraint_id)->orderBy('id')->get()
            : collect();
        $arrivalDetails = $invoice->arrival_revenue_constraint_id
            ? ConstraintDetails::query()->where('constraint_id', $invoice->arrival_revenue_constraint_id)->orderBy('id')->get()
            : collect();
        if ($sourceDetails->isEmpty() && $arrivalDetails->isEmpty()) {
            throw new \RuntimeException('لا يوجد قيد فاتورة أو إيراد وصول يحتوي تفاصيل يمكن عكسها.');
        }

        $taxAccountId = $this->resolveRevenueTaxAccountId();
        $netCreditDetails = $sourceDetails
            ->filter(fn ($detail) => (float) $detail->creditor_value > 0 && (int) $detail->account_id !== (int) $taxAccountId)
            ->values();
        $netSourceColumn = 'creditor_value';
        if ($netCreditDetails->isEmpty() && $arrivalDetails->isNotEmpty()) {
            $netCreditDetails = $arrivalDetails
                ->filter(fn ($detail) => (float) $detail->debtor_value > 0)
                ->values();
            $netSourceColumn = 'debtor_value';
        }
        if ($netCreditDetails->isEmpty() && (float) $creditNote->subtotal > 0) {
            throw new \RuntimeException('تعذر تحديد حساب الالتزام أو المبيعات في قيد الفاتورة الأصلي.');
        }

        $sourceName = $invoice->contract_id
            ? 'استقدام'
            : ($invoice->ingaz_delegation_id ? 'تفويض إنجاز' : 'فاتورة عامة');
        $statement = 'إشعار دائن ' . $sourceName . ' #' . $creditNote->id
            . ' للفاتورة #' . ($invoice->legacy_invoice_number ?: $invoice->id)
            . ' - ' . $creditNote->reason;
        $lines = [];

        foreach ($this->recruitmentCreditNoteNetAllocations(
            $creditNote,
            $invoice,
            $netCreditDetails,
            $netSourceColumn
        ) as $allocation) {
            $this->appendCreditNoteLine($lines, (int) $allocation['account_id'], 'debtor', $allocation['amount'], $statement, $allocation['cost_center_id']);
            $this->appendCreditNoteLine($lines, $clientAccountId, 'creditor', $allocation['amount'], $statement);
        }
        if ((float) $creditNote->vat_amount > 0) {
            if (!$taxAccountId) {
                throw new \RuntimeException('حساب ضريبة المخرجات غير معرف.');
            }
            $this->appendCreditNoteLine($lines, $taxAccountId, 'debtor', (float) $creditNote->vat_amount, $statement);
            $this->appendCreditNoteLine($lines, $clientAccountId, 'creditor', (float) $creditNote->vat_amount, $statement);
        }

        if ($invoice->arrival_revenue_constraint_id) {
            $arrivalDebit = $arrivalDetails->filter(fn ($detail) => (float) $detail->debtor_value > 0)->values();
            $arrivalCredit = $arrivalDetails->filter(fn ($detail) => (float) $detail->creditor_value > 0)->values();

            foreach ($this->allocateCreditNoteAmount($arrivalCredit, (float) $creditNote->subtotal, 'creditor_value') as $allocation) {
                $this->appendCreditNoteLine($lines, (int) $allocation['account_id'], 'debtor', $allocation['amount'], $statement, $allocation['cost_center_id']);
            }
            foreach ($this->allocateCreditNoteAmount($arrivalDebit, (float) $creditNote->subtotal, 'debtor_value') as $allocation) {
                $this->appendCreditNoteLine($lines, (int) $allocation['account_id'], 'creditor', $allocation['amount'], $statement, $allocation['cost_center_id']);
            }
        }

        $debit = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $credit = $this->roundMoney(array_sum(array_column($lines, 'creditor_value')));
        if (abs($debit - $credit) > 0.01) {
            throw new \RuntimeException(
                'قيد إشعار الدائن غير متوازن: المدين '
                . number_format($debit, 2) . ' والدائن ' . number_format($credit, 2) . '.'
            );
        }

        $data = [
            'code' => $service->lastCode(),
            'date' => $creditNote->issue_date->format('Y-m-d'),
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $debit,
        ];

        $constraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $service->storeFiles($constraint, $data);

        return $constraint;
    }

    private function recruitmentCreditNoteNetAllocations(
        RecruitmentInvoiceCreditNote $creditNote,
        Invoice $invoice,
        $sourceDetails,
        string $valueColumn
    ): array {
        $breakdown = $this->buildInvoiceFinanceBreakdown($invoice);
        if ($sourceDetails->count() < 2
            || $breakdown['direct_amount'] <= 0
            || $breakdown['other_amount'] <= 0) {
            return $this->allocateCreditNoteAmount(
                $sourceDetails,
                (float) $creditNote->subtotal,
                $valueColumn
            );
        }

        $creditItems = collect($creditNote->line_items);
        $directAmount = $this->roundMoney((float) $creditItems
            ->where('key', 'direct')
            ->sum(fn (array $item) => (float) ($item['line_subtotal'] ?? 0)));
        $otherAmount = $this->roundMoney(max(0, (float) $creditNote->subtotal - $directAmount));
        $allocations = [];

        if ($directAmount > 0) {
            $detail = $sourceDetails->first();
            $allocations[] = [
                'account_id' => (int) $detail->account_id,
                'cost_center_id' => $detail->cost_center_id,
                'amount' => $directAmount,
            ];
        }
        if ($otherAmount > 0) {
            $otherDetails = $sourceDetails->slice(1)->values();
            $allocations = array_merge(
                $allocations,
                $this->allocateCreditNoteAmount($otherDetails, $otherAmount, $valueColumn)
            );
        }

        return $allocations;
    }

    private function storeRecruitmentCreditNoteSale(
        RecruitmentInvoiceCreditNote $creditNote,
        Invoice $invoice
    ): Sale {
        $originalSale = $invoice->financeSale;
        if (!$originalSale) {
            $originalSale = $this->queueInvoiceForZatcaSale($invoice);
        }
        $originalSale->loadMissing('items');
        $itemId = (int) ($originalSale->items->first()?->item_id ?: $this->ensureIngazZatcaServiceItem()->id);

        $sale = Sale::query()->create([
            // ترقيم فواتير المبيعات يبقى متصلاً على مستوى الشركة رغم عزل الفروع.
            'code' => ((int) Sale::query()->acrossBranches()->max('code')) + 1,
            'client_id' => $originalSale->client_id,
            'debit_client_id' => $originalSale->debit_client_id,
            'date' => $creditNote->issue_date->format('Y-m-d'),
            'notes' => 'إشعار دائن ' . ($invoice->contract_id
                ? 'استقدام'
                : ($invoice->ingaz_delegation_id ? 'تفويض إنجاز' : 'فاتورة عامة'))
                . ' #' . $creditNote->id . ' للفاتورة #' . $invoice->id,
            'adjustment_reason' => $creditNote->reason,
            'original_sale_id' => $originalSale->id,
            'total_item' => (float) $creditNote->subtotal,
            'discount' => 0,
            'final_total' => (float) $creditNote->total,
            'total_tax_value' => (float) $creditNote->vat_amount,
            'type' => SalesTypeEnum::BackSales->value,
            'file' => 'no_file',
            'payment_type' => 'cash',
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        foreach ($creditNote->line_items as $item) {
            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'item_id' => $itemId,
                'line_description' => (string) $item['label'],
                'price' => (float) $item['unit_price'],
                'discount_per' => 0,
                'discount_value' => 0,
                'tax_per' => (float) $item['tax_rate'],
                'tax_value' => (float) $item['vat_amount'],
                'total_price_before' => (float) ($item['line_subtotal'] ?? $item['unit_price']),
                'final_total' => (float) $item['line_total'],
                'purchase_price' => 0,
                'amount' => (float) ($item['quantity'] ?? 1),
                'stock_id' => null,
                'status' => 'new',
            ]);
        }

        return $sale;
    }

    private function canCreateInvoiceCreditNote(): bool
    {
        return InvoiceAuthorization::canCreateCreditNotes();
    }

    private function canViewInvoiceCreditNote(): bool
    {
        return InvoiceAuthorization::canViewCreditNotes()
            || InvoiceAuthorization::canPrintCreditNotes();
    }

    private function permissionMiddleware(array $permissions): string
    {
        return 'permission:' . implode('|', array_values(array_unique($permissions))) . ',admin';
    }

    private function allocateCreditNoteAmount($details, float $amount, string $valueColumn): array
    {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0 || $details->isEmpty()) {
            return [];
        }

        $sourceTotal = (float) $details->sum($valueColumn);
        if ($sourceTotal <= 0) {
            return [];
        }

        $remaining = $amount;
        return $details->values()->map(function ($detail, int $index) use ($details, $amount, $sourceTotal, $valueColumn, &$remaining) {
            $allocated = $index === $details->count() - 1
                ? $remaining
                : $this->roundMoney($amount * ((float) $detail->getAttribute($valueColumn) / $sourceTotal));
            $remaining = $this->roundMoney($remaining - $allocated);

            return [
                'account_id' => (int) $detail->account_id,
                'cost_center_id' => $detail->cost_center_id,
                'amount' => $allocated,
            ];
        })->all();
    }

    private function appendCreditNoteLine(
        array &$lines,
        int $accountId,
        string $type,
        float $amount,
        string $statement,
        ?int $costCenterId = null
    ): void {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'account_type' => $type,
            'debtor_value' => $type === 'debtor' ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
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
        if ($invoice->sponsorship_transfer_id) {
            $clientName = $invoice->sponsorshipTransfer?->newSponsor?->name
                ?: $invoice->client?->name
                ?: 'غير محدد';
            $itemsSummary = $this->buildInvoiceItemsSummary($invoice);

            return "قيد فاتورة نقل الخدمات #{$invoice->sponsorship_transfer_id} / الفاتورة #{$invoice->id} / العميل: {$clientName}"
                . ($itemsSummary !== '' ? " / البنود: {$itemsSummary}" : '');
        }

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
            ->musanedAccountCandidate()
            ->first();
    }

    private function resolveRevenueTaxAccountId(): ?int
    {
        return app(\Nami\Finance\Services\TaxAccountResolver::class)->resolve('revenue_tax_account')?->id;
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

    public function resolveContractNationalityLiabilityAccountId(?Nationality $nationality): ?int
    {
        if (!$nationality) {
            return null;
        }

        $configuredAccountId = ConfirmSetting::query()
            ->where('title', 'nationality_account_' . $nationality->id . '_contract_liability')
            ->latest('updated_at')
            ->value('value');

        $candidates = array_filter([
            $configuredAccountId ? (int) $configuredAccountId : null,
            $nationality->account_id ? (int) $nationality->account_id : null,
        ]);

        $nationalityName = trim((string) ($nationality->sub_title ?: $nationality->title));
        if ($nationalityName !== '') {
            $resolvedAccountId = $this->resolveNationalityAccountId($nationalityName);
            if ($resolvedAccountId) {
                $candidates[] = $resolvedAccountId;
            }
        }

        foreach (array_unique($candidates) as $accountId) {
            if ($this->isRecruitmentLiabilityAccount((int) $accountId)) {
                return (int) $accountId;
            }
        }

        return null;
    }

    public function assertRecruitmentInvoiceAccountingConfiguration(
        \Carbon\Carbon $invoiceDate,
        YearService $yearService
    ): void {
        if (!AutomaticJournalEntries::enabled()) {
            throw new \RuntimeException('إنشاء القيود المحاسبية التلقائية غير مفعل.');
        }

        if (!$yearService->checkDateRange($invoiceDate->toDateString())) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ الفاتورة المحدد.');
        }

        $this->resolveMusanedReceiptAccount();

        if (!$this->resolveRevenueTaxAccountId()) {
            throw new \RuntimeException('لا يوجد حساب ضريبة إيرادات صالح.');
        }

        if (!Account::query()->where('type', AccountTypesEnum::Clients->value)->exists()) {
            throw new \RuntimeException('لا يوجد حساب رئيسي للعملاء من نوع Clients.');
        }
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
