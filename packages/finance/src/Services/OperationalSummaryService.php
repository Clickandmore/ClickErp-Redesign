<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;

/**
 * الملخص المالي التشغيلي — يربط الحركة التشغيلية (فوترة التأجير والاستقدام،
 * حوالات مساند، المكاتب الخارجية) بالأرقام المالية التي يُبنى عليها القرار:
 * كم بعنا، كم حصّلنا، كم بقي علينا تحصيله، وكم علينا للمكاتب.
 *
 * مصادر الأرقام مقصودة ومختلفة عن قائمة الدخل المحاسبية:
 *   - المبيعات   = قيمة الفواتير الصادرة فعلياً (تأجير + استقدام + تفاويض + تسويات التأجير).
 *     فواتير التفاويض مستثناة من عمود الاستقدام لأنها تُعرض في عمودها الخاص.
 *   - المحصّل    = paid_amount / is_paid على نفس الفواتير، لا سندات القبض.
 *   - الاستحقاقات للمكاتب = رصيد حساب المكتب في الأستاذ (دائن - مدين)، لأن هذا
 *     هو الرقم الذي تُسدَّد عليه الحوالة فعلياً وليس تكلفة العقد المعلنة.
 */
class OperationalSummaryService extends MainService
{
    /** Cache schema inspection for legacy rental invoice mirrors during this PHP process. */
    private static ?bool $rentalMirrorColumn = null;

    /** حدود شرائح التقادم بالأيام. */
    private const AGING_BUCKETS = [30, 60, 90];

    /** الذمم مقصورة على الفواتير الصادرة داخل الفترة المختارة. */
    public const SCOPE_PERIOD = 'period';

    /** كل رصيد غير محصّل حتى تاريخ النهاية، مهما كان تاريخ إصداره. */
    public const SCOPE_OUTSTANDING = 'outstanding';

    public const DEFAULT_PER_PAGE = 25;

    public const PER_PAGE_OPTIONS = [25, 50, 100];

    public const MUSANED_STATUS_ALL = 'all';

    public const MUSANED_STATUS_TRANSFERRED = 'transferred';

    public const MUSANED_STATUS_NOT_TRANSFERRED = 'not_transferred';

    public const MUSANED_STATUS_OPTIONS = [
        self::MUSANED_STATUS_ALL,
        self::MUSANED_STATUS_TRANSFERRED,
        self::MUSANED_STATUS_NOT_TRANSFERRED,
    ];

    /**
     * @param  array{scope?: string, musaned_status?: string, per_page?: int|null, rental_page?: int, recruitment_page?: int}  $options
     *         scope: 'period' يقصر الذمم على فواتير الفترة، 'outstanding' يعرض كل رصيد قائم حتى $to.
     *         per_page: null تعني بلا ترقيم (يستخدمه التصدير).
     */
    public function report(string $from, string $to, array $options = []): array
    {
        $scope = ($options['scope'] ?? self::SCOPE_PERIOD) === self::SCOPE_OUTSTANDING
            ? self::SCOPE_OUTSTANDING
            : self::SCOPE_PERIOD;
        $perPage = array_key_exists('per_page', $options) ? $options['per_page'] : self::DEFAULT_PER_PAGE;
        $musanedStatus = in_array($options['musaned_status'] ?? null, self::MUSANED_STATUS_OPTIONS, true)
            ? $options['musaned_status']
            : self::MUSANED_STATUS_ALL;
        $receivablesFrom = $scope === self::SCOPE_PERIOD ? $from : null;

        $sales = $this->sales($from, $to, $musanedStatus);
        $rental = $this->receivableSection(
            fn () => $this->rentalReceivableQuery($receivablesFrom, $to),
            'rental_contract_invoices.created_at',
            'rental_contract_invoices.remaining_amount',
            $to,
            (int) ($options['rental_page'] ?? 1),
            $perPage
        );
        $recruitment = $this->receivableSection(
            fn () => $this->recruitmentReceivableQuery($receivablesFrom, $to, $musanedStatus),
            'invoices.created_at',
            'invoices.total_due',
            $to,
            (int) ($options['recruitment_page'] ?? 1),
            $perPage
        );
        $sponsorship = $this->receivableSection(
            fn () => $this->sponsorshipReceivableQuery($receivablesFrom, $to),
            'invoices.created_at', 'GREATEST(invoices.total_due - sponsorship_transfers.paid_amount, 0)', $to,
            (int) ($options['sponsorship_page'] ?? 1), $perPage
        );
        $delegations = $this->delegations($from, $to, $musanedStatus);
        $musaned = $this->musaned($from, $to);
        $offices = $this->externalOffices($from, $to);

        if ($scope === self::SCOPE_PERIOD) {
            $rental['outstanding_all'] = $this->receivableGrandTotal(
                fn () => $this->rentalReceivableQuery(null, $to),
                'rental_contract_invoices.remaining_amount'
            );
            $recruitment['outstanding_all'] = $this->receivableGrandTotal(
                fn () => $this->recruitmentReceivableQuery(null, $to, $musanedStatus),
                'invoices.total_due'
            );
            $sponsorship['outstanding_all'] = $this->receivableGrandTotal(
                fn () => $this->sponsorshipReceivableQuery(null, $to), 'GREATEST(invoices.total_due - sponsorship_transfers.paid_amount, 0)'
            );
        }

        $recruitment['musaned_filter'] = $this->musanedFilterDiagnostics(
            $musanedStatus,
            $receivablesFrom,
            $to,
            (int) $recruitment['totals']['documents']
        );

        return [
            'from' => $from,
            'to' => $to,
            'scope' => $scope,
            'musaned_status' => $musanedStatus,
            'per_page' => $perPage,
            'sales' => $sales,
            'rental_receivables' => $rental,
            'recruitment_receivables' => $recruitment,
            'sponsorship_receivables' => $sponsorship,
            'delegations' => $delegations,
            'musaned' => $musaned,
            'external_offices' => $offices,
            'kpis' => [
                'total_sales' => $sales['totals']['invoiced'],
                'collected' => $sales['totals']['collected'],
                'outstanding_period' => $sales['totals']['outstanding'],
                'collection_rate' => $sales['totals']['invoiced'] > 0
                    ? round($sales['totals']['collected'] / $sales['totals']['invoiced'] * 100, 1)
                    : null,
                'receivables_total' => $rental['totals']['remaining'] + $recruitment['totals']['remaining'] + $sponsorship['totals']['remaining'],
                'rental_receivables' => $rental['totals']['remaining'],
                'recruitment_receivables' => $recruitment['totals']['remaining'],
                'sponsorship_receivables' => $sponsorship['totals']['remaining'],
                'delegation_revenue' => $delegations['totals']['revenue'],
                'delegation_cost' => $delegations['totals']['cost'],
                'delegation_net' => $delegations['totals']['net'],
                'delegation_count' => $delegations['totals']['delegations'],
                'musaned_commission_net' => $musaned['totals']['commission_net'],
                'musaned_commission_gross' => $musaned['totals']['commission'],
                'offices_payable' => $offices['totals']['payable'],
                'offices_prepaid' => $offices['totals']['prepaid'],
            ],
        ];
    }

    /**
     * نسخة مجمّعة خفيفة لبطاقة لوحة المالية — بلا قوائم صفوف ولا تفصيل شهري،
     * حتى لا تدفع الصفحة الرئيسية ثمن تقرير كامل.
     */
    public function headline(string $from, string $to): array
    {
        $rentalSales = $this->rentalBillingQuery()
            ->whereBetween(DB::raw('DATE(rental_contract_invoices.created_at)'), [$from, $to])
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as collected')
            ->first();

        // التفاويض تبقى محتسبة هنا لأن هذا الرقم هو إجمالي المبيعات للوحة المالية.
        $recruitmentSales = $this->withoutRentalMirrors($this->scoped('invoices'))
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to])
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_paid = 1 THEN total_due ELSE 0 END), 0) as collected')
            ->first();

        $rentalOutstanding = DB::query()->fromSub($this->rentalReceivableBillingQuery($to), 'rental_contract_invoices')
            ->whereDate('rental_contract_invoices.created_at', '<=', $to)
            ->where('remaining_amount', '>', 0)
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(remaining_amount), 0) as remaining')
            ->first();

        $recruitmentOutstanding = $this->withoutRentalMirrors($this->scoped('invoices'))
            ->whereDate('invoices.created_at', '<=', $to)
            ->where('is_paid', 0)
            ->where('total_due', '>', 0)
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(total_due), 0) as remaining')
            ->first();

        $musaned = $this->scoped('musaned_transfers')
            ->whereBetween('transfer_date', [$from, $to])
            ->selectRaw('COUNT(*) as transfers')
            ->selectRaw('COALESCE(SUM(commission_total), 0) as commission')
            ->selectRaw('COALESCE(SUM(commission_net_total), 0) as commission_net')
            ->first();

        $delegations = $this->delegationQuery($from, $to)
            ->selectRaw('COUNT(*) as delegations')
            ->selectRaw('COALESCE(SUM(invoices.total_due), 0) as revenue')
            ->selectRaw('COALESCE(SUM(cost.value), 0) as cost')
            ->first();

        $officeAccountIds = DB::table('recruitment_offices')->whereNotNull('account_id')->pluck('account_id')->all();
        $officesPayable = $this->officeAccountBalances($officeAccountIds, $to)
            ->reduce(function (float $carry, $row) {
                return $carry + max((float) $row->creditor - (float) $row->debtor, 0.0);
            }, 0.0);

        $invoiced = (float) $rentalSales->invoiced + (float) $recruitmentSales->invoiced;
        $collected = (float) $rentalSales->collected + (float) $recruitmentSales->collected;

        return [
            'from' => $from,
            'to' => $to,
            'sales_invoiced' => $invoiced,
            'sales_collected' => $collected,
            'sales_outstanding' => $invoiced - $collected,
            'collection_rate' => $invoiced > 0 ? round($collected / $invoiced * 100, 1) : null,
            'receivables_total' => (float) $rentalOutstanding->remaining + (float) $recruitmentOutstanding->remaining,
            'receivables_documents' => (int) $rentalOutstanding->documents + (int) $recruitmentOutstanding->documents,
            'rental_receivables' => (float) $rentalOutstanding->remaining,
            'recruitment_receivables' => (float) $recruitmentOutstanding->remaining,
            'musaned_transfers' => (int) $musaned->transfers,
            'musaned_commission' => (float) $musaned->commission,
            'musaned_commission_net' => (float) $musaned->commission_net,
            'delegation_count' => (int) $delegations->delegations,
            'delegation_revenue' => (float) $delegations->revenue,
            'delegation_net' => (float) $delegations->revenue - (float) $delegations->cost,
            'offices_payable' => $officesPayable,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* المبيعات                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * إجمالي المبيعات الشهرية موزعة على مصادرها الثلاثة، مع المحصّل من كل شهر.
     */
    private function sales(string $from, string $to, string $musanedStatus = self::MUSANED_STATUS_ALL): array
    {
        $rental = $this->monthlyRentalSales($from, $to);
        $recruitment = $this->monthlyRecruitmentSales($from, $to, $musanedStatus);
        $delegations = $this->monthlyDelegationSales($from, $to, $musanedStatus);
        $sponsorship = $this->monthlySponsorshipSales($from, $to);
        $adjustments = $this->monthlyRentalAdjustments($from, $to);

        $months = [];
        foreach ($this->monthKeys($from, $to) as $key) {
            $rentalRow = $rental[$key] ?? null;
            $recruitmentRow = $recruitment[$key] ?? null;
            $delegationRow = $delegations[$key] ?? null;
            $sponsorshipRow = $sponsorship[$key] ?? null;
            $adjustmentRow = $adjustments[$key] ?? null;

            $invoiced = (float) ($rentalRow->invoiced ?? 0)
                + (float) ($recruitmentRow->invoiced ?? 0)
                + (float) ($delegationRow->invoiced ?? 0)
                + (float) ($sponsorshipRow->invoiced ?? 0)
                + (float) ($adjustmentRow->invoiced ?? 0);
            $collected = (float) ($rentalRow->collected ?? 0)
                + (float) ($recruitmentRow->collected ?? 0)
                + (float) ($delegationRow->collected ?? 0)
                + (float) ($sponsorshipRow->collected ?? 0)
                + (float) ($adjustmentRow->collected ?? 0);

            $months[] = [
                'key' => $key,
                'label' => Carbon::createFromFormat('Y-m-d', $key . '-01')->translatedFormat('M Y'),
                'rental' => (float) ($rentalRow->invoiced ?? 0),
                'rental_count' => (int) ($rentalRow->documents ?? 0),
                'recruitment' => (float) ($recruitmentRow->invoiced ?? 0),
                'recruitment_count' => (int) ($recruitmentRow->documents ?? 0),
                'delegations' => (float) ($delegationRow->invoiced ?? 0),
                'delegations_count' => (int) ($delegationRow->documents ?? 0),
                'sponsorship' => (float) ($sponsorshipRow->invoiced ?? 0),
                'sponsorship_count' => (int) ($sponsorshipRow->documents ?? 0),
                'adjustments' => (float) ($adjustmentRow->invoiced ?? 0),
                'adjustments_count' => (int) ($adjustmentRow->documents ?? 0),
                'invoiced' => $invoiced,
                'collected' => $collected,
                'outstanding' => $invoiced - $collected,
            ];
        }

        $sum = fn (string $column) => array_sum(array_column($months, $column));

        return [
            'months' => $months,
            'totals' => [
                'rental' => $sum('rental'),
                'rental_count' => $sum('rental_count'),
                'recruitment' => $sum('recruitment'),
                'recruitment_count' => $sum('recruitment_count'),
                'delegations' => $sum('delegations'),
                'delegations_count' => $sum('delegations_count'),
                'sponsorship' => $sum('sponsorship'),
                'sponsorship_count' => $sum('sponsorship_count'),
                'adjustments' => $sum('adjustments'),
                'adjustments_count' => $sum('adjustments_count'),
                'invoiced' => $sum('invoiced'),
                'collected' => $sum('collected'),
                'outstanding' => $sum('outstanding'),
            ],
        ];
    }

    /** Base invoices and separately billed extensions share one reporting source. */
    private function rentalBillingQuery(): Builder
    {
        $invoices = $this->scoped('rental_contract_invoices')->select([
            'id', 'rental_contract_id', 'total_due', 'paid_amount', 'remaining_amount',
            'payment_type', 'payment_status', 'created_at',
        ])->selectRaw("'invoice' as document_type");

        // Older extensions without a stored value are not issued financial documents.
        // A missing payment amount on a fully paid legacy extension means full payment.
        $paid = "COALESCE(payment_amount, CASE WHEN payment_status = 'paid' THEN extension_value ELSE 0 END)";
        $extensions = $this->scoped('rental_contract_extensions')
            ->whereNotNull('extension_value')
            ->select(['id', 'rental_contract_id'])
            ->selectRaw("extension_value as total_due, {$paid} as paid_amount")
            ->selectRaw("GREATEST(extension_value - {$paid}, 0) as remaining_amount")
            ->addSelect(['payment_type', 'payment_status', 'created_at'])
            ->selectRaw("'extension' as document_type");

        return DB::query()->fromSub($invoices->unionAll($extensions), 'rental_contract_invoices');
    }

    /** Apply contract credits once, oldest outstanding document first, before period filtering. */
    public function rentalReceivableBillingQuery(string $to): Builder
    {
        $credits = $this->scoped('rental_financial_adjustments')
            ->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction'])
            ->whereDate(DB::raw('COALESCE(issue_date, created_at)'), '<=', $to)
            ->select('rental_contract_id')
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount WHEN type = 'income' THEN -amount ELSE 0 END) as credit")
            ->groupBy('rental_contract_id');

        $documents = $this->rentalBillingQuery()
            ->whereDate('created_at', '<=', $to);
        $ordered = DB::query()->fromSub($documents, 'documents')
            ->select('documents.*')
            ->selectRaw('COALESCE(SUM(remaining_amount) OVER (PARTITION BY rental_contract_id ORDER BY created_at, document_type, id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) as earlier_remaining')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY rental_contract_id ORDER BY created_at, document_type, id) as document_position');
        $allocated = DB::query()->fromSub($ordered, 'documents')
            ->leftJoinSub($credits, 'credits', 'credits.rental_contract_id', '=', 'documents.rental_contract_id')
            ->select('documents.*')
            ->selectRaw('CASE WHEN COALESCE(credits.credit, 0) >= 0 THEN LEAST(documents.remaining_amount, GREATEST(COALESCE(credits.credit, 0) - documents.earlier_remaining, 0)) WHEN documents.document_position = 1 THEN credits.credit ELSE 0 END as adjustment_amount');

        return DB::query()->fromSub($allocated, 'documents')->select([
            'id', 'rental_contract_id', 'total_due', 'paid_amount',
            'payment_type', 'created_at', 'document_type', 'adjustment_amount',
        ])->selectRaw('ROUND(remaining_amount - adjustment_amount, 2) as remaining_amount')
            ->selectRaw("CASE WHEN ROUND(remaining_amount - adjustment_amount, 2) <= 0 THEN 'paid' WHEN paid_amount > 0 THEN 'partial' ELSE payment_status END as payment_status");
    }

    private function monthlyRentalSales(string $from, string $to): Collection
    {
        return $this->rentalBillingQuery()
            ->whereBetween(DB::raw('DATE(rental_contract_invoices.created_at)'), [$from, $to])
            ->selectRaw("DATE_FORMAT(rental_contract_invoices.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as collected')
            ->groupBy('period')
            ->get()
            ->keyBy('period');
    }

    private function monthlyRecruitmentSales(string $from, string $to, string $musanedStatus): Collection
    {
        $query = $this->withoutRentalMirrors($this->scoped('invoices'))
            ->whereNull('invoices.ingaz_delegation_id')
            ->whereNull('invoices.sponsorship_transfer_id')
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to]);

        return $this->applyMusanedStatus($query, $musanedStatus)
            ->selectRaw("DATE_FORMAT(invoices.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_paid = 1 THEN total_due ELSE 0 END), 0) as collected')
            ->groupBy('period')
            ->get()
            ->keyBy('period');
    }

    /**
     * فواتير تفاويض الإنجاز — تُعرض في عمود مستقل عن الاستقدام لا فوقه.
     */
    private function monthlyDelegationSales(string $from, string $to, string $musanedStatus): Collection
    {
        $query = $this->scoped('invoices')
            ->whereNotNull('invoices.ingaz_delegation_id')
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to]);

        return $this->applyMusanedStatus($query, $musanedStatus)
            ->selectRaw("DATE_FORMAT(invoices.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_paid = 1 THEN total_due ELSE 0 END), 0) as collected')
            ->groupBy('period')
            ->get()
            ->keyBy('period');
    }

    private function monthlySponsorshipSales(string $from, string $to): Collection
    {
        return $this->scoped('invoices')
            ->whereNotNull('invoices.sponsorship_transfer_id')
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to])
            ->selectRaw("DATE_FORMAT(invoices.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(total_due), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_paid = 1 THEN total_due ELSE 0 END), 0) as collected')
            ->groupBy('period')->get()->keyBy('period');
    }

    /**
     * تسويات التأجير من نوع إيراد (إغلاق/نقل الخدمات) — فوترة حقيقية خارج فاتورة الباقة.
     */
    private function monthlyRentalAdjustments(string $from, string $to): Collection
    {
        return $this->scoped('rental_financial_adjustments')
            ->where('type', 'income')
            ->whereBetween(DB::raw('DATE(COALESCE(issue_date, rental_financial_adjustments.created_at))'), [$from, $to])
            ->selectRaw("DATE_FORMAT(COALESCE(issue_date, rental_financial_adjustments.created_at), '%Y-%m') as period")
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(amount), 0) as invoiced')
            ->selectRaw('COALESCE(SUM(CASE WHEN settled_at IS NOT NULL THEN amount ELSE 0 END), 0) as collected')
            ->groupBy('period')
            ->get()
            ->keyBy('period');
    }

    /* ------------------------------------------------------------------ */
    /* الذمم — عملاء مفوترون لم يسددوا                                     */
    /* ------------------------------------------------------------------ */

    /**
     * عملاء عقود التأجير الذين صدرت لهم فاتورة ولم تُسدَّد بالكامل حتى $to.
     * تمرير $from يقصر النتيجة على الفواتير الصادرة داخل الفترة.
     */
    private function rentalReceivableQuery(?string $from, string $to): Builder
    {
        $query = DB::query()->fromSub($this->rentalReceivableBillingQuery($to), 'rental_contract_invoices')
            ->join('rental_contracts', 'rental_contracts.id', '=', 'rental_contract_invoices.rental_contract_id')
            ->leftJoin('users', 'users.id', '=', 'rental_contracts.user_id')
            ->whereDate('rental_contract_invoices.created_at', '<=', $to)
            ->where('rental_contract_invoices.remaining_amount', '>', 0);

        if ($from !== null) {
            $query->whereDate('rental_contract_invoices.created_at', '>=', $from);
        }

        return $query;
    }

    /**
     * فواتير الاستقدام غير المحصّلة — لا يوجد سداد جزئي على هذا الجدول،
     * فالمتبقي هو كامل قيمة الفاتورة.
     */
    private function recruitmentReceivableQuery(?string $from, string $to, string $musanedStatus = self::MUSANED_STATUS_ALL): Builder
    {
        $query = $this->withoutRentalMirrors($this->scoped('invoices'))
            ->leftJoin('users', 'users.id', '=', 'invoices.client_id')
            ->leftJoin('contracts', 'contracts.id', '=', 'invoices.contract_id')
            ->leftJoin('transfer_service_contracts', 'transfer_service_contracts.sponsorship_transfer_id', '=', 'invoices.sponsorship_transfer_id')
            ->leftJoin('sponsorship_transfers', 'sponsorship_transfers.id', '=', 'invoices.sponsorship_transfer_id')
            ->whereNull('invoices.sponsorship_transfer_id')
            ->whereDate('invoices.created_at', '<=', $to)
            ->where('invoices.is_paid', 0)
            ->where('invoices.total_due', '>', 0);

        $this->applyMusanedStatus($query, $musanedStatus);

        if ($from !== null) {
            $query->whereDate('invoices.created_at', '>=', $from);
        }

        return $query;
    }

    private function sponsorshipReceivableQuery(?string $from, string $to): Builder
    {
        $query = $this->scoped('invoices')
            ->join('sponsorship_transfers', 'sponsorship_transfers.id', '=', 'invoices.sponsorship_transfer_id')
            ->leftJoin('transfer_service_contracts', 'transfer_service_contracts.sponsorship_transfer_id', '=', 'sponsorship_transfers.id')
            ->leftJoin('contracts', 'contracts.id', '=', 'sponsorship_transfers.contract_id')
            ->leftJoin('users', 'users.id', '=', 'sponsorship_transfers.new_sponsor_id')
            ->whereDate('invoices.created_at', '<=', $to)
            ->whereRaw('GREATEST(invoices.total_due - sponsorship_transfers.paid_amount, 0) > 0');

        if ($from !== null) {
            $query->whereDate('invoices.created_at', '>=', $from);
        }

        return $query;
    }

    private function applyMusanedStatus(Builder $query, string $musanedStatus): Builder
    {
        if ($musanedStatus === self::MUSANED_STATUS_ALL) {
            return $query;
        }

        $method = $musanedStatus === self::MUSANED_STATUS_TRANSFERRED ? 'whereExists' : 'whereNotExists';
        $query->{$method}(function (Builder $subQuery) {
            $subQuery->selectRaw('1')
                ->from('musaned_transfer_items')
                ->where(function (Builder $relation) {
                    $relation->whereColumn('musaned_transfer_items.invoice_id', 'invoices.id')
                        ->orWhere(function (Builder $delegation) {
                            $delegation->whereNotNull('invoices.ingaz_delegation_id')
                                ->whereColumn('musaned_transfer_items.ingaz_delegation_id', 'invoices.ingaz_delegation_id');
                        });
                });
        });

        return $query;
    }

    /**
     * أعمدة العرض لكل مصدر — مفصولة عن الاستعلام الأساسي حتى تبقى استعلامات
     * المجاميع والتقادم بلا SELECT ثقيل.
     *
     * @return array<int, mixed>
     */
    private function receivableColumns(string $dateColumn): array
    {
        if ($dateColumn === 'invoices.created_at') {
            return [
                'invoices.id',
                'invoices.contract_id',
                'invoices.total_due',
                DB::raw('COALESCE(sponsorship_transfers.paid_amount, 0) as paid_amount'),
                DB::raw('GREATEST(invoices.total_due - COALESCE(sponsorship_transfers.paid_amount, 0), 0) as remaining_amount'),
                'invoices.created_at as invoiced_at',
                DB::raw('COALESCE(transfer_service_contracts.contract_number, contracts.arabia_order_number, invoices.legacy_invoice_number, invoices.id) as contract_number'),
                'users.id as client_id',
                'users.name as client_name',
                'users.phone as client_phone',
                'users.national_num as client_identity',
                DB::raw("'unpaid' as payment_status"),
            ];
        }

        return [
            'rental_contract_invoices.id',
            'rental_contract_invoices.document_type',
            'rental_contract_invoices.rental_contract_id',
            'rental_contract_invoices.total_due',
            'rental_contract_invoices.paid_amount',
            'rental_contract_invoices.remaining_amount',
            'rental_contract_invoices.adjustment_amount',
            'rental_contract_invoices.payment_type',
            'rental_contract_invoices.payment_status',
            'rental_contract_invoices.created_at as invoiced_at',
            'rental_contracts.contract_number',
            'users.id as client_id',
            'users.account_id as client_account_id',
            'users.name as client_name',
            'users.phone as client_phone',
            'users.national_num as client_identity',
        ];
    }

    /**
     * قسم ذمم كامل: مجاميع وتقادم محسوبان على كل الصفوف المطابقة، وصفحة واحدة
     * فقط من الصفوف. المجاميع لا تتبع الصفحة — وإلا قرأ المستخدم إجمالي صفحة
     * على أنه إجمالي الذمم.
     *
     * @param  callable(): Builder  $query  مصنع استعلام؛ كل استخدام يبدأ من نسخة نظيفة.
     */
    private function receivableSection(
        callable $query,
        string $dateColumn,
        string $remainingColumn,
        string $to,
        int $page,
        ?int $perPage
    ): array {
        $totalColumn = $dateColumn === 'invoices.created_at'
            ? 'invoices.total_due'
            : 'rental_contract_invoices.total_due';

        $totalsQuery = $query()
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COUNT(DISTINCT users.id) as clients')
            ->selectRaw("COALESCE(SUM({$remainingColumn}), 0) as remaining")
            ->selectRaw("COALESCE(SUM({$totalColumn}), 0) as invoiced")
            ->selectRaw("COALESCE(MAX(DATEDIFF(?, DATE({$dateColumn}))), 0) as oldest_days", [$to]);

        if ($dateColumn !== 'invoices.created_at') {
            $totalsQuery->selectRaw('COALESCE(SUM(rental_contract_invoices.paid_amount), 0) as actual_paid')
                ->selectRaw('COALESCE(SUM(rental_contract_invoices.adjustment_amount), 0) as adjustments');
        }
        $totals = $totalsQuery->first();

        $paid = isset($totals->actual_paid) ? (float) $totals->actual_paid : (float) $totals->invoiced - (float) $totals->remaining;
        $aging = $this->receivableAging($query, $dateColumn, $remainingColumn, $to);

        $rowsQuery = $query()->select($this->receivableColumns($dateColumn));
        str_contains($remainingColumn, '(')
            ? $rowsQuery->orderByRaw("{$remainingColumn} DESC")
            : $rowsQuery->orderByDesc($remainingColumn);
        $rowsQuery->orderBy($dateColumn);

        $total = (int) $totals->documents;

        if ($perPage === null) {
            $rows = $rowsQuery->get();
            $paginator = null;
        } else {
            $perPage = max(1, $perPage);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min(max(1, $page), $lastPage);
            $rows = $rowsQuery->forPage($page, $perPage)->get();
            $paginator = new LengthAwarePaginator($rows, $total, $perPage, $page);
        }

        $rows = $rows->map(fn ($row) => $this->decorateAge($row, $to));

        if ($paginator) {
            $paginator->setCollection($rows);
        }

        return [
            'rows' => $rows,
            'paginator' => $paginator,
            'aging' => $aging,
            'totals' => [
                'documents' => $total,
                'clients' => (int) $totals->clients,
                'invoiced' => (float) $totals->invoiced,
                'paid' => $paid,
                'adjustments' => (float) ($totals->adjustments ?? 0),
                'remaining' => (float) $totals->remaining,
                'oldest_days' => (int) $totals->oldest_days,
            ],
        ];
    }

    /**
     * شرائح التقادم بمجموع واحد على قاعدة البيانات، لا بجلب كل الصفوف.
     *
     * @param  callable(): Builder  $query
     * @return array<string, float>
     */
    private function receivableAging(callable $query, string $dateColumn, string $remainingColumn, string $to): array
    {
        $age = "DATEDIFF(?, DATE({$dateColumn}))";
        $select = $query();

        foreach (self::AGING_BUCKETS as $index => $limit) {
            $lower = $index === 0 ? null : self::AGING_BUCKETS[$index - 1];
            $condition = $lower === null
                ? "{$age} <= {$limit}"
                : "{$age} > {$lower} AND {$age} <= {$limit}";
            $select->selectRaw(
                "COALESCE(SUM(CASE WHEN {$condition} THEN {$remainingColumn} ELSE 0 END), 0) as d{$limit}",
                $lower === null ? [$to] : [$to, $to]
            );
        }

        $buckets = self::AGING_BUCKETS;
        $last = (int) end($buckets);
        $select->selectRaw(
            "COALESCE(SUM(CASE WHEN {$age} > {$last} THEN {$remainingColumn} ELSE 0 END), 0) as over_due",
            [$to]
        );
        $select->selectRaw(
            "COALESCE(SUM(CASE WHEN {$dateColumn} IS NULL THEN {$remainingColumn} ELSE 0 END), 0) as unknown_due"
        );

        $row = $select->first();
        $aging = [];

        foreach (self::AGING_BUCKETS as $limit) {
            $aging['d' . $limit] = (float) $row->{'d' . $limit};
        }

        $aging['over'] = (float) $row->over_due;
        $aging['unknown'] = (float) $row->unknown_due;

        return $aging;
    }

    /**
     * إجمالي الرصيد غير المحصّل بلا قيد فترة — يُعرض بجانب إجمالي الفترة حتى لا
     * يختفي الدين القديم عن الأنظار عند تضييق التواريخ.
     *
     * @param  callable(): Builder  $query
     */
    private function receivableGrandTotal(callable $query, string $remainingColumn): float
    {
        return (float) $query()->sum(DB::raw($remainingColumn));
    }

    /**
     * يفسّر أثر فلتر «حالة التحويل من مساند» على قسم ذمم الاستقدام وحده.
     *
     * الفلتر يشمل أقساماً أخرى (المبيعات والتفاويض)، لكن قسم الذمم هو الوحيد
     * الذي يَظهر فيه الفراغ التام لأنه مقيّد أصلاً بالفواتير غير المحصّلة، فقد
     * يبدو بلا أثر لسببين مختلفين تماماً: إما لا توجد ذمم استقدام في الفترة
     * أصلاً، أو توجد والفلتر استبعد جزءاً منها. نحسب هنا العدد بلا فلتر حتى
     * تستطيع الواجهة قول أي السببين هو الجاري، بدل رسالة فراغ صامتة.
     *
     * لا يُنفَّذ أي استعلام إضافي عندما يكون الفلتر على «الكل».
     *
     * @return array{status: string, active: bool, filtered_documents: int, unfiltered_documents: int, excluded_documents: int, excluded_remaining: float}
     */
    private function musanedFilterDiagnostics(
        string $musanedStatus,
        ?string $from,
        string $to,
        int $filteredDocuments
    ): array {
        if ($musanedStatus === self::MUSANED_STATUS_ALL) {
            return [
                'status' => $musanedStatus,
                'active' => false,
                'filtered_documents' => $filteredDocuments,
                'unfiltered_documents' => $filteredDocuments,
                'excluded_documents' => 0,
                'excluded_remaining' => 0.0,
            ];
        }

        $unfiltered = $this->recruitmentReceivableQuery($from, $to)
            ->selectRaw('COUNT(*) as documents')
            ->selectRaw('COALESCE(SUM(invoices.total_due), 0) as remaining')
            ->first();

        $unfilteredDocuments = (int) $unfiltered->documents;
        $excludedRemaining = (float) $unfiltered->remaining - (float) $this->receivableGrandTotal(
            fn () => $this->recruitmentReceivableQuery($from, $to, $musanedStatus),
            'invoices.total_due'
        );

        return [
            'status' => $musanedStatus,
            'active' => true,
            'filtered_documents' => $filteredDocuments,
            'unfiltered_documents' => $unfilteredDocuments,
            'excluded_documents' => max(0, $unfilteredDocuments - $filteredDocuments),
            'excluded_remaining' => round(max(0, $excludedRemaining), 2),
        ];
    }

    /**
     * يضيف عمر الفاتورة وشريحة التقادم إلى السطر.
     */
    private function decorateAge(object $row, string $to): object
    {
        $invoicedAt = $row->invoiced_at ? Carbon::parse($row->invoiced_at) : null;
        $row->age_days = $invoicedAt ? max(0, $invoicedAt->startOfDay()->diffInDays(Carbon::parse($to)->startOfDay(), false)) : null;
        $row->aging_bucket = $this->agingBucket($row->age_days);
        $row->remaining_amount = (float) $row->remaining_amount;
        $row->paid_amount = (float) $row->paid_amount;
        $row->total_due = (float) $row->total_due;

        return $row;
    }

    private function agingBucket(?int $days): string
    {
        if ($days === null) {
            return 'unknown';
        }

        foreach (self::AGING_BUCKETS as $limit) {
            if ($days <= $limit) {
                return 'd' . $limit;
            }
        }

        return 'over';
    }

    /* ------------------------------------------------------------------ */
    /* إيرادات التفاويض الرجالية (تفاويض الإنجاز)                          */
    /* ------------------------------------------------------------------ */

    /**
     * إيرادات تفاويض الإنجاز — التفويض الإلكتروني للعمالة الرجالية (سائق خاص،
     * عامل منزلي، مزارع منزلي).
     *
     * الإيراد من فاتورة التفويض نفسها، والتكلفة من قيد تكلفة التفويض المرتبط
     * بها؛ فالصافي هنا هامش الخدمة لا ربح القائمة المحاسبية. وهذه الفواتير
     * مستثناة من عمود «فواتير الاستقدام» حتى لا يُحتسب الإيراد مرتين.
     */
    private function delegations(string $from, string $to, string $musanedStatus = self::MUSANED_STATUS_ALL): array
    {
        $monthly = $this->delegationQuery($from, $to, $musanedStatus)
            ->selectRaw("DATE_FORMAT(invoices.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as delegations')
            ->selectRaw('COALESCE(SUM(invoices.total_due), 0) as revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN invoices.is_paid = 1 THEN invoices.total_due ELSE 0 END), 0) as collected')
            ->selectRaw('COALESCE(SUM(cost.value), 0) as cost')
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $months = [];
        foreach ($this->monthKeys($from, $to) as $key) {
            $row = $monthly[$key] ?? null;
            $revenue = (float) ($row->revenue ?? 0);
            $cost = (float) ($row->cost ?? 0);
            $count = (int) ($row->delegations ?? 0);

            $months[] = [
                'key' => $key,
                'label' => Carbon::createFromFormat('Y-m-d', $key . '-01')->translatedFormat('M Y'),
                'delegations' => $count,
                'revenue' => $revenue,
                'collected' => (float) ($row->collected ?? 0),
                'outstanding' => $revenue - (float) ($row->collected ?? 0),
                'cost' => $cost,
                'net' => $revenue - $cost,
                'average_revenue' => $count > 0 ? round($revenue / $count, 2) : 0.0,
            ];
        }

        $sum = fn (string $column) => array_sum(array_column($months, $column));
        $revenue = $sum('revenue');
        $cost = $sum('cost');
        $count = $sum('delegations');

        return [
            'months' => $months,
            'professions' => $this->delegationsByProfession($from, $to, $musanedStatus),
            'pending_cost' => $this->delegationPendingCost($from, $to, $musanedStatus),
            'totals' => [
                'delegations' => $count,
                'revenue' => $revenue,
                'collected' => $sum('collected'),
                'outstanding' => $sum('outstanding'),
                'cost' => $cost,
                'net' => $revenue - $cost,
                'average_revenue' => $count > 0 ? round($revenue / $count, 2) : 0.0,
                'margin_rate' => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : null,
            ],
        ];
    }

    /**
     * قيود تكلفة تفاويض لم تُعتمد بعد. الصافي أعلاه يحتسب المعتمد وحده — كما في
     * بقية التقرير — فلو بقيت هذه معلّقة بدا الهامش أكبر من حقيقته، ولذلك تُعرض
     * صراحة بدل أن تُدمج بصمت.
     *
     * @return array{entries: int, amount: float}
     */
    private function delegationPendingCost(string $from, string $to, string $musanedStatus): array
    {
        if (!Constraint::hasApprovalColumns()) {
            return ['entries' => 0, 'amount' => 0.0];
        }

        $query = $this->scoped('invoices')
            ->join('fn_constraints as cost', 'cost.id', '=', 'invoices.ingaz_delegation_cost_constraint_id')
            ->whereNotNull('invoices.ingaz_delegation_id')
            ->whereNull('cost.deleted_at')
            ->where('cost.approval_status', '!=', Constraint::APPROVAL_APPROVED)
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to]);

        $row = $this->applyMusanedStatus($query, $musanedStatus)
            ->selectRaw('COUNT(*) as entries')
            ->selectRaw('COALESCE(SUM(cost.value), 0) as amount')
            ->first();

        return ['entries' => (int) $row->entries, 'amount' => (float) $row->amount];
    }

    /**
     * توزيع التفاويض على المهن — يقرأ أين يتولّد الإيراد فعلاً.
     */
    private function delegationsByProfession(string $from, string $to, string $musanedStatus): Collection
    {
        return $this->delegationQuery($from, $to, $musanedStatus)
            ->leftJoin('job_titles_translations as profession', function ($join) {
                $join->on('profession.job_title_id', '=', 'delegation.profession_id')
                    ->where('profession.locale', '=', app()->getLocale());
            })
            ->selectRaw('COALESCE(profession.title, ?) as profession', [__('finance::finance.ops_unspecified')])
            ->selectRaw('COUNT(*) as delegations')
            ->selectRaw('COALESCE(SUM(invoices.total_due), 0) as revenue')
            ->selectRaw('COALESCE(SUM(cost.value), 0) as cost')
            ->groupBy('profession')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->revenue = (float) $row->revenue;
                $row->cost = (float) $row->cost;
                $row->delegations = (int) $row->delegations;
                $row->net = $row->revenue - $row->cost;
                $row->average_revenue = $row->delegations > 0 ? round($row->revenue / $row->delegations, 2) : 0.0;

                return $row;
            });
    }

    /**
     * فواتير التفاويض داخل الفترة، مع قيد التكلفة المعتمد المرتبط بكل فاتورة.
     */
    private function delegationQuery(string $from, string $to, string $musanedStatus = self::MUSANED_STATUS_ALL): Builder
    {
        $query = $this->scoped('invoices')
            ->join('ingaz_delegations as delegation', 'delegation.id', '=', 'invoices.ingaz_delegation_id')
            ->leftJoin('fn_constraints as cost', function ($join) {
                $join->on('cost.id', '=', 'invoices.ingaz_delegation_cost_constraint_id')
                    ->whereNull('cost.deleted_at');

                if (Constraint::hasApprovalColumns()) {
                    $join->where('cost.approval_status', '=', Constraint::APPROVAL_APPROVED);
                }
            })
            ->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to]);

        return $this->applyMusanedStatus($query, $musanedStatus);
    }

    /* ------------------------------------------------------------------ */
    /* عمولات مساند                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * حوالات مساند خلال الفترة: ما دفعه العميل، ما وصلنا، والعمولة المحتجزة
     * وصافيها بعد ضريبة المصروف.
     */
    private function musaned(string $from, string $to): array
    {
        $monthly = $this->scoped('musaned_transfers')
            ->whereBetween('transfer_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(transfer_date, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as transfers')
            ->selectRaw('COALESCE(SUM(contracts_count), 0) as contracts')
            ->selectRaw('COALESCE(SUM(customer_paid_total), 0) as customer_paid')
            ->selectRaw('COALESCE(SUM(transferred_total), 0) as transferred')
            ->selectRaw('COALESCE(SUM(commission_total), 0) as commission')
            ->selectRaw('COALESCE(SUM(expense_tax_total), 0) as expense_tax')
            ->selectRaw('COALESCE(SUM(commission_net_total), 0) as commission_net')
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $months = [];
        foreach ($this->monthKeys($from, $to) as $key) {
            $row = $monthly[$key] ?? null;
            $customerPaid = (float) ($row->customer_paid ?? 0);
            $commission = (float) ($row->commission ?? 0);

            $months[] = [
                'key' => $key,
                'label' => Carbon::createFromFormat('Y-m-d', $key . '-01')->translatedFormat('M Y'),
                'transfers' => (int) ($row->transfers ?? 0),
                'contracts' => (int) ($row->contracts ?? 0),
                'customer_paid' => $customerPaid,
                'transferred' => (float) ($row->transferred ?? 0),
                'commission' => $commission,
                'expense_tax' => (float) ($row->expense_tax ?? 0),
                'commission_net' => (float) ($row->commission_net ?? 0),
                'commission_rate' => $customerPaid > 0 ? round($commission / $customerPaid * 100, 2) : null,
            ];
        }

        $sum = fn (string $column) => array_sum(array_column($months, $column));
        $customerPaid = $sum('customer_paid');
        $commission = $sum('commission');

        /** الحوالات التي لم يُرحَّل قيدها البنكي بعد — نقد منتظر في الطريق. */
        $unsettled = $this->scoped('musaned_transfers')
            ->whereBetween('transfer_date', [$from, $to])
            ->whereNull('bank_transfer_constraint_id')
            ->selectRaw('COUNT(*) as transfers')
            ->selectRaw('COALESCE(SUM(transferred_total), 0) as amount')
            ->first();

        return [
            'months' => $months,
            'totals' => [
                'transfers' => $sum('transfers'),
                'contracts' => $sum('contracts'),
                'customer_paid' => $customerPaid,
                'transferred' => $sum('transferred'),
                'commission' => $commission,
                'expense_tax' => $sum('expense_tax'),
                'commission_net' => $sum('commission_net'),
                'commission_rate' => $customerPaid > 0 ? round($commission / $customerPaid * 100, 2) : null,
            ],
            'unposted' => [
                'transfers' => (int) ($unsettled->transfers ?? 0),
                'amount' => (float) ($unsettled->amount ?? 0),
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* استحقاقات المكاتب الخارجية                                          */
    /* ------------------------------------------------------------------ */

    /**
     * رصيد كل مكتب خارجي في الأستاذ حتى $to، مع نشاط الفترة (عدد وتكلفة
     * العقود المُرسَلة) لقراءة الاستحقاق في سياق حجم التعامل.
     */
    private function externalOffices(string $from, string $to): array
    {
        $offices = DB::table('recruitment_offices as office')
            ->leftJoin('fn_accounts as account', 'account.id', '=', 'office.account_id')
            ->select([
                'office.id',
                'office.status',
                'office.office_country',
                'office.account_id',
                'account.code as account_code',
                'account.name as account_name',
            ])
            ->get()
            ->keyBy('id');

        $balances = $this->officeAccountBalances($offices->pluck('account_id')->filter()->all(), $to);
        $activity = $this->officeDispatchActivity($from, $to);

        $rows = $offices->map(function ($office) use ($balances, $activity) {
            $balance = $balances[(int) $office->account_id] ?? null;
            $signed = (float) ($balance->creditor ?? 0) - (float) ($balance->debtor ?? 0);
            $period = $activity[(int) $office->id] ?? null;

            return (object) [
                'id' => (int) $office->id,
                'name' => $office->account_name ?: ($period->office_name ?? ('#' . $office->id)),
                'account_code' => $office->account_code,
                'account_id' => $office->account_id ? (int) $office->account_id : null,
                'country' => $office->office_country,
                'status' => $office->status,
                'payable' => max($signed, 0.0),
                'prepaid' => max(-$signed, 0.0),
                'signed_balance' => $signed,
                'debtor' => (float) ($balance->debtor ?? 0),
                'creditor' => (float) ($balance->creditor ?? 0),
                'period_contracts' => (int) ($period->contracts ?? 0),
                'period_cost_usd' => (float) ($period->cost_usd ?? 0),
                'last_dispatch_at' => $period->last_dispatch ?? null,
            ];
        })
            ->filter(fn ($row) => abs($row->signed_balance) > .005 || $row->period_contracts > 0)
            ->sortByDesc('payable')
            ->values();

        $unlinked = $offices->filter(fn ($office) => $office->account_id === null)->count();

        return [
            'rows' => $rows,
            'unlinked_offices' => $unlinked,
            'totals' => [
                'offices' => $rows->count(),
                'payable' => (float) $rows->sum('payable'),
                'prepaid' => (float) $rows->sum('prepaid'),
                'period_contracts' => (int) $rows->sum('period_contracts'),
                'period_cost_usd' => (float) $rows->sum('period_cost_usd'),
            ],
        ];
    }

    /**
     * @param  array<int, int|string>  $accountIds
     */
    private function officeAccountBalances(array $accountIds, string $to): Collection
    {
        if ($accountIds === []) {
            return collect();
        }

        return $this->ledgerQuery()
            ->whereIn('detail.account_id', $accountIds)
            ->whereDate('detail.date', '<=', $to)
            ->selectRaw('detail.account_id')
            ->selectRaw('COALESCE(SUM(detail.debtor_value), 0) as debtor')
            ->selectRaw('COALESCE(SUM(detail.creditor_value), 0) as creditor')
            ->groupBy('detail.account_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->account_id);
    }

    private function officeDispatchActivity(string $from, string $to): Collection
    {
        return $this->scoped('contract_external_office_dispatches', 'dispatch')
            ->whereNotNull('dispatch.recruitment_office_id')
            ->whereBetween(DB::raw('DATE(COALESCE(dispatch.dispatch_date, dispatch.created_at))'), [$from, $to])
            ->selectRaw('dispatch.recruitment_office_id')
            ->selectRaw('MAX(dispatch.office_name) as office_name')
            ->selectRaw('COUNT(*) as contracts')
            ->selectRaw('COALESCE(SUM(dispatch.office_contract_cost_usd), 0) as cost_usd')
            ->selectRaw('MAX(COALESCE(dispatch.dispatch_date, dispatch.created_at)) as last_dispatch')
            ->groupBy('dispatch.recruitment_office_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->recruitment_office_id);
    }

    /* ------------------------------------------------------------------ */
    /* أدوات مشتركة                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * قيود الأستاذ المعتمدة فقط، بنفس دلالة الفرع المستخدمة في لوحة المالية.
     */
    private function ledgerQuery(): Builder
    {
        $query = BranchReportFilter::apply(DB::table('fn_constraint_details as detail'), 'detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at');

        if (Constraint::hasApprovalColumns()) {
            $query->where('constraint.approval_status', Constraint::APPROVAL_APPROVED);
        }

        return $query;
    }

    /**
     * فواتير عقود التأجير مُرحَّلة أيضاً إلى جدول `invoices` بمعرّفها القديم
     * (`rental_contract_invoices.legacy_invoice_id`). فبدون استبعادها من جانب
     * الاستقدام تُحتسب الفاتورة الواحدة مرّتين: مرّة في عمود التأجير ومرّة في
     * عمود الاستقدام — ويظهر كامل مبلغها غير محصّل لأن نسخة `invoices` لا تحمل
     * المدفوع ولا المتبقّي. الجانب التأجيري هو المرجع لأنه وحده يحملهما.
     */
    private function withoutRentalMirrors(Builder $query): Builder
    {
        if (!$this->rentalMirrorColumnExists()) {
            return $query;
        }

        return $query->whereNotExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('rental_contract_invoices as rental_mirror')
                ->whereColumn('rental_mirror.legacy_invoice_id', 'invoices.id');
        });
    }

    private function rentalMirrorColumnExists(): bool
    {
        if (self::$rentalMirrorColumn === null) {
            self::$rentalMirrorColumn = DB::getSchemaBuilder()
                ->hasColumn('rental_contract_invoices', 'legacy_invoice_id');
        }

        return self::$rentalMirrorColumn;
    }

    /**
     * جدول مُقيَّد بالفرع النشط — نفس دلالة BranchScope على موديلات Eloquent.
     */
    private function scoped(string $table, ?string $alias = null): Builder
    {
        $query = DB::table($alias ? "{$table} as {$alias}" : $table);

        return BranchReportFilter::apply($query, $alias ?: $table);
    }

    /**
     * @return array<int, string> مفاتيح الأشهر (Y-m) داخل المدى، بالترتيب.
     */
    private function monthKeys(string $from, string $to): array
    {
        $cursor = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->startOfMonth();
        $keys = [];

        while ($cursor->lte($end)) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $keys;
    }
}
