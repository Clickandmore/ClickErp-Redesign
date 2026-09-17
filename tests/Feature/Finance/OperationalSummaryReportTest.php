<?php

namespace Tests\Feature\Finance;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\OperationalSummaryExportService;
use Nami\Finance\Services\OperationalSummaryService;
use Tests\TestCase;

/**
 * الملخص المالي التشغيلي: يثبّت الأرقام التي يُبنى عليها القرار المالي —
 * المبيعات الشهرية، المبالغ غير المحصّلة، عمولة مساند، واستحقاق المكاتب.
 *
 * كل صف يبنيه الاختبار بنفسه داخل نطاق تواريخ خاص به، فلا يعتمد على أي بيانات
 * موجودة مسبقاً في قاعدة الاختبار.
 */
class OperationalSummaryReportTest extends TestCase
{
    use DatabaseTransactions;

    private const FROM = '2031-01-01';
    private const TO = '2031-03-31';

    private int $userId;
    private int $officeAccountId;
    private int $officeId;
    private int $contractId;
    private int $nationalityId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = DB::table('users')->insertGetId([
            'name' => 'عميل الملخص التشغيلي',
            'phone_code' => '966',
            'phone' => '500000031',
            'password' => bcrypt('secret'),
            'national_num' => '1099887766',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->officeAccountId = DB::table('fn_accounts')->insertGetId([
            'name' => 'مكتب خارجي للاختبار',
            'operation_type' => 1,
            'status' => 'creditor',
            'level' => 4,
            'code' => 999731,
            'type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->nationalityId = $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->officeId = DB::table('recruitment_offices')->insertGetId([
            'nationality_id' => $nationalityId,
            'account_id' => $this->officeAccountId,
            'status' => 'active',
            'office_country' => 'PH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contractId = DB::table('contracts')->insertGetId([
            'user_id' => $this->userId,
            'arabia_order_number' => 3100001,
            'created_at' => self::FROM . ' 10:00:00',
            'updated_at' => self::FROM . ' 10:00:00',
        ]);
    }

    /* -------------------------------------------------------------- */

    private function rentalInvoice(string $date, float $due, float $paid, string $number): void
    {
        $contractId = DB::table('rental_contracts')->insertGetId([
            'user_id' => $this->userId,
            'contract_number' => $number,
            'created_at' => $date . ' 09:00:00',
            'updated_at' => $date . ' 09:00:00',
        ]);

        DB::table('rental_contract_invoices')->insert([
            'rental_contract_id' => $contractId,
            'total_due' => $due,
            'paid_amount' => $paid,
            'remaining_amount' => $due - $paid,
            'is_paid' => $due - $paid <= 0,
            'payment_status' => $due - $paid <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'later'),
            'created_at' => $date . ' 09:00:00',
            'updated_at' => $date . ' 09:00:00',
        ]);
    }

    private function recruitmentInvoice(string $date, float $due, bool $paid): int
    {
        return DB::table('invoices')->insertGetId([
            'contract_id' => $this->contractId,
            'client_id' => $this->userId,
            'total_due' => $due,
            'is_paid' => $paid,
            'created_at' => $date . ' 11:00:00',
            'updated_at' => $date . ' 11:00:00',
        ]);
    }

    private function markAsTransferredFromMusaned(int $invoiceId, string $date): void
    {
        $transferId = DB::table('musaned_transfers')->insertGetId([
            'transfer_date' => $date,
            'contracts_count' => 1,
            'customer_paid_total' => 12000,
            'transferred_total' => 11640,
            'commission_total' => 360,
            'expense_tax_total' => 54,
            'commission_net_total' => 306,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('musaned_transfer_items')->insert([
            'musaned_transfer_id' => $transferId,
            'contract_id' => $this->contractId,
            'invoice_id' => $invoiceId,
            'customer_paid_amount' => 12000,
            'transferred_amount' => 11640,
            'commission_amount' => 360,
            'expense_tax_amount' => 54,
            'commission_net_amount' => 306,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * تفويض إنجاز بفاتورته وقيد تكلفته. $costApproval يحدد إن كان القيد معتمداً.
     */
    private function delegation(string $date, float $revenue, float $cost, string $profession, string $costApproval = Constraint::APPROVAL_APPROVED): array
    {
        $jobTitleId = DB::table('job_titles')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        DB::table('job_titles_translations')->insert([
            'job_title_id' => $jobTitleId,
            'title' => $profession,
            'locale' => app()->getLocale(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $delegationId = DB::table('ingaz_delegations')->insertGetId([
            'delegation_date_gregorian' => $date,
            'nationality_id' => $this->nationalityId,
            'profession_id' => $jobTitleId,
            'visa_number' => 'V-' . $date . '-' . $jobTitleId,
            'created_at' => $date . ' 12:00:00',
            'updated_at' => $date . ' 12:00:00',
        ]);

        $costConstraintId = DB::table('fn_constraints')->insertGetId([
            'type' => 1,
            'code' => random_int(700000, 799999),
            'value' => $cost,
            'date' => $date,
            'statement' => 'تكلفة تفويض',
            'approval_status' => $costApproval,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'ingaz_delegation_id' => $delegationId,
            'ingaz_delegation_cost_constraint_id' => $costConstraintId,
            'client_id' => $this->userId,
            'total_due' => $revenue,
            'is_paid' => 1,
            'created_at' => $date . ' 12:30:00',
            'updated_at' => $date . ' 12:30:00',
        ]);

        return ['delegation_id' => $delegationId, 'invoice_id' => $invoiceId];
    }

    private function markDelegationAsTransferredFromMusaned(array $delegation, string $date): void
    {
        $transferId = DB::table('musaned_transfers')->insertGetId([
            'transfer_date' => $date,
            'contracts_count' => 1,
            'customer_paid_total' => 1600,
            'transferred_total' => 1552,
            'commission_total' => 48,
            'expense_tax_total' => 7.2,
            'commission_net_total' => 40.8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('musaned_transfer_items')->insert([
            'musaned_transfer_id' => $transferId,
            'contract_id' => null,
            'ingaz_delegation_id' => $delegation['delegation_id'],
            'invoice_id' => $delegation['invoice_id'],
            'customer_paid_amount' => 1600,
            'transferred_amount' => 1552,
            'commission_amount' => 48,
            'expense_tax_amount' => 7.2,
            'commission_net_amount' => 40.8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sponsorshipTransferInvoice(string $date, float $due, float $paid): int
    {
        $transferId = DB::table('sponsorship_transfers')->insertGetId([
            'contract_id' => $this->contractId,
            'old_sponsor_id' => $this->userId,
            'new_sponsor_id' => $this->userId,
            'trial_days' => 30,
            'trial_start_date' => $date,
            'contract_value' => $due,
            'paid_amount' => $paid,
            'remaining_amount' => $due - $paid,
            'status' => 'approved',
            'created_at' => $date . ' 10:00:00',
            'updated_at' => $date . ' 10:00:00',
        ]);

        DB::table('invoices')->insert([
            'sponsorship_transfer_id' => $transferId,
            'client_id' => $this->userId,
            'total_due' => $due,
            'is_paid' => $paid >= $due,
            'created_at' => $date . ' 11:00:00',
            'updated_at' => $date . ' 11:00:00',
        ]);

        return $transferId;
    }

    private function ledgerEntry(string $date, float $debtor, float $creditor, string $approval = Constraint::APPROVAL_APPROVED): void
    {
        $constraintId = DB::table('fn_constraints')->insertGetId([
            'type' => 1,
            'code' => random_int(900000, 999999),
            'value' => max($debtor, $creditor),
            'date' => $date,
            'statement' => 'قيد اختبار',
            'approval_status' => $approval,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fn_constraint_details')->insert([
            'constraint_id' => $constraintId,
            'account_id' => $this->officeAccountId,
            'date' => $date,
            'type' => $creditor > 0 ? 'creditor' : 'debtor',
            'debtor_value' => $debtor,
            'creditor_value' => $creditor,
            'statement' => 'قيد اختبار',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function report(array $options = []): array
    {
        return app(OperationalSummaryService::class)->report(self::FROM, self::TO, $options);
    }

    /* -------------------------------------------------------------- */

    public function test_monthly_sales_combine_rental_and_recruitment_billing_with_collections(): void
    {
        $this->rentalInvoice('2031-01-10', 10000, 10000, 'RC-31-01');
        $this->rentalInvoice('2031-02-10', 8000, 3000, 'RC-31-02');
        $this->recruitmentInvoice('2031-01-20', 25000, true);
        $this->recruitmentInvoice('2031-03-05', 12000, false);

        $sales = $this->report()['sales'];
        $months = collect($sales['months'])->keyBy('key');

        $this->assertCount(3, $sales['months'], 'الفترة ثلاثة أشهر فيجب أن تظهر ثلاثة صفوف حتى الفارغ منها.');
        $this->assertSame(35000.0, $months['2031-01']['invoiced']);
        $this->assertSame(35000.0, $months['2031-01']['collected']);
        $this->assertSame(8000.0, $months['2031-02']['invoiced']);
        $this->assertSame(3000.0, $months['2031-02']['collected']);
        $this->assertSame(12000.0, $months['2031-03']['outstanding']);

        $this->assertSame(55000.0, $sales['totals']['invoiced']);
        $this->assertSame(38000.0, $sales['totals']['collected']);
        $this->assertSame(17000.0, $sales['totals']['outstanding']);
        $this->assertSame(18000.0, $sales['totals']['rental']);
        $this->assertSame(37000.0, $sales['totals']['recruitment']);
    }

    public function test_extensions_are_included_in_sales_receivables_aging_and_headline(): void
    {
        $this->rentalInvoice('2031-01-10', 3300, 0, 'RC-EXT');
        $contractId = DB::table('rental_contracts')->where('contract_number', 'RC-EXT')->value('id');
        foreach ([
            ['2031-02-10', 3300, null, 'later'],
            ['2031-03-20', 1000, 400, 'partial'],
            ['2031-03-21', 500, null, 'paid'],
            ['2031-04-01', 900, null, 'later'],
            ['2030-12-01', 700, null, 'later'],
            ['2031-03-22', null, null, 'later'],
        ] as [$date, $value, $paid, $status]) {
            DB::table('rental_contract_extensions')->insert([
                'rental_contract_id' => $contractId,
                'extension_value' => $value,
                'payment_amount' => $paid,
                'payment_status' => $status,
                'created_at' => $date . ' 10:00:00',
                'updated_at' => $date . ' 10:00:00',
            ]);
        }

        $report = $this->report(['per_page' => 1]);
        $section = $report['rental_receivables'];
        $this->assertSame(8100.0, $report['sales']['totals']['rental']);
        $this->assertSame(4, $report['sales']['totals']['rental_count']);
        $this->assertSame(900.0, $report['sales']['totals']['collected']);
        $this->assertSame(3, $section['totals']['documents']);
        $this->assertSame(7200.0, $section['totals']['remaining']);
        $this->assertSame(7900.0, $section['outstanding_all']);
        $this->assertEquals(7200, array_sum($section['aging']));
        $this->assertCount(1, $section['rows']);

        $all = $this->report(['per_page' => null])['rental_receivables']['rows'];
        $this->assertCount(2, $all->where('document_type', 'extension'));
        $this->assertEquals(600, $all->where('payment_status', 'partial')->first()->remaining_amount);
        $headline = app(OperationalSummaryService::class)->headline(self::FROM, self::TO);
        $this->assertSame(8100.0, $headline['sales_invoiced']);
        $this->assertSame(7900.0, $headline['rental_receivables']);
    }

    public function test_rental_receivables_list_only_unpaid_invoices_with_client_and_aging(): void
    {
        $this->rentalInvoice('2031-01-10', 10000, 10000, 'RC-31-PAID');
        $this->rentalInvoice('2031-02-10', 8000, 3000, 'RC-31-PARTIAL');
        $this->rentalInvoice('2031-03-20', 4000, 0, 'RC-31-LATER');

        $section = $this->report()['rental_receivables'];
        $numbers = $section['rows']->pluck('contract_number')->all();

        $this->assertSame(['RC-31-PARTIAL', 'RC-31-LATER'], $numbers, 'الفاتورة المسددة بالكامل لا تظهر، والترتيب بالأعلى متبقياً.');
        $this->assertSame(9000.0, $section['totals']['remaining']);
        $this->assertSame(2, $section['totals']['documents']);
        $this->assertSame(1, $section['totals']['clients']);

        $partial = $section['rows']->firstWhere('contract_number', 'RC-31-PARTIAL');
        $this->assertSame('عميل الملخص التشغيلي', $partial->client_name);
        $this->assertSame(5000.0, $partial->remaining_amount);
        $this->assertSame(49, $partial->age_days, '10 فبراير حتى 31 مارس = 49 يوماً.');
        $this->assertSame('d60', $partial->aging_bucket);

        $later = $section['rows']->firstWhere('contract_number', 'RC-31-LATER');
        $this->assertSame('d30', $later->aging_bucket);
        $this->assertSame(9000.0, $section['aging']['d30'] + $section['aging']['d60']);
        $this->assertSame(0.0, $section['aging']['over']);
    }

    public function test_recruitment_receivables_cover_only_invoices_not_marked_paid(): void
    {
        $this->recruitmentInvoice('2031-01-20', 25000, true);
        $this->recruitmentInvoice('2031-03-05', 12000, false);

        $section = $this->report()['recruitment_receivables'];

        $this->assertSame(1, $section['totals']['documents']);
        $this->assertSame(12000.0, $section['totals']['remaining']);
        $this->assertSame(12000.0, $section['rows']->first()->total_due, 'لا سداد جزئي هنا، فالمتبقي كامل الفاتورة.');
        $this->assertSame('3100001', (string) $section['rows']->first()->contract_number);
    }

    public function test_recruitment_receivables_can_be_filtered_by_musaned_transfer_status(): void
    {
        $transferredInvoiceId = $this->recruitmentInvoice('2031-02-05', 12000, false);
        $this->recruitmentInvoice('2031-02-10', 8000, false);
        $this->markAsTransferredFromMusaned($transferredInvoiceId, '2031-02-15');

        $transferred = $this->report(['musaned_status' => OperationalSummaryService::MUSANED_STATUS_TRANSFERRED]);
        $notTransferred = $this->report(['musaned_status' => OperationalSummaryService::MUSANED_STATUS_NOT_TRANSFERRED]);

        $this->assertSame(OperationalSummaryService::MUSANED_STATUS_TRANSFERRED, $transferred['musaned_status']);
        $this->assertSame([$transferredInvoiceId], $transferred['recruitment_receivables']['rows']->pluck('id')->all());
        $this->assertSame(12000.0, $transferred['recruitment_receivables']['totals']['remaining']);
        $this->assertSame(1, $notTransferred['recruitment_receivables']['totals']['documents']);
        $this->assertSame(8000.0, $notTransferred['recruitment_receivables']['totals']['remaining']);
    }

    /**
     * فلتر مساند يبدو بلا أثر لسببين مختلفين، والواجهة تحتاج التمييز بينهما:
     * إما لا توجد ذمم استقدام في النطاق أصلاً، أو توجد والفلتر استبعدها.
     */
    public function test_musaned_filter_reports_how_many_documents_it_excluded(): void
    {
        $transferredInvoiceId = $this->recruitmentInvoice('2031-02-05', 12000, false);
        $this->recruitmentInvoice('2031-02-10', 8000, false);
        $this->markAsTransferredFromMusaned($transferredInvoiceId, '2031-02-15');

        $all = $this->report()['recruitment_receivables']['musaned_filter'];
        $this->assertFalse($all['active'], 'الفلتر على «الكل» غير مفعّل، فلا يُحسب أي استبعاد.');
        $this->assertSame(0, $all['excluded_documents']);

        $notTransferred = $this->report([
            'musaned_status' => OperationalSummaryService::MUSANED_STATUS_NOT_TRANSFERRED,
        ])['recruitment_receivables']['musaned_filter'];

        $this->assertTrue($notTransferred['active']);
        $this->assertSame(2, $notTransferred['unfiltered_documents']);
        $this->assertSame(1, $notTransferred['filtered_documents']);
        $this->assertSame(1, $notTransferred['excluded_documents']);
        $this->assertSame(12000.0, $notTransferred['excluded_remaining']);
    }

    public function test_musaned_filter_distinguishes_an_empty_section_from_a_filtered_out_one(): void
    {
        // لا فواتير استقدام غير محصّلة في النطاق — الفلتر لا شيء ليعمل عليه.
        $emptyBase = $this->report([
            'musaned_status' => OperationalSummaryService::MUSANED_STATUS_TRANSFERRED,
        ])['recruitment_receivables']['musaned_filter'];

        $this->assertTrue($emptyBase['active']);
        $this->assertSame(0, $emptyBase['unfiltered_documents'], 'صفر يعني: لا ذمم استقدام أصلاً.');
        $this->assertSame(0, $emptyBase['excluded_documents']);

        // الآن توجد ذمة واحدة غير محوّلة، وفلتر «محوّل لها» يستبعدها كلها.
        $this->recruitmentInvoice('2031-02-10', 8000, false);

        $filteredOut = $this->report([
            'musaned_status' => OperationalSummaryService::MUSANED_STATUS_TRANSFERRED,
        ])['recruitment_receivables']['musaned_filter'];

        $this->assertSame(0, $filteredOut['filtered_documents']);
        $this->assertSame(1, $filteredOut['unfiltered_documents'], 'وجود ذمم مع نتيجة فارغة يعني أن الفلتر أخفاها.');
        $this->assertSame(1, $filteredOut['excluded_documents']);
        $this->assertSame(8000.0, $filteredOut['excluded_remaining']);
    }

    public function test_musaned_commissions_aggregate_per_month_with_net_and_rate(): void
    {
        DB::table('musaned_transfers')->insert([
            [
                'transfer_date' => '2031-01-15', 'contracts_count' => 4,
                'customer_paid_total' => 100000, 'transferred_total' => 97000,
                'commission_total' => 3000, 'expense_tax_total' => 450, 'commission_net_total' => 2550,
                'bank_transfer_constraint_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'transfer_date' => '2031-02-15', 'contracts_count' => 2,
                'customer_paid_total' => 50000, 'transferred_total' => 48500,
                'commission_total' => 1500, 'expense_tax_total' => 225, 'commission_net_total' => 1275,
                'bank_transfer_constraint_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $musaned = $this->report()['musaned'];
        $months = collect($musaned['months'])->keyBy('key');

        $this->assertSame(2550.0, $months['2031-01']['commission_net']);
        $this->assertSame(3.0, $months['2031-01']['commission_rate']);
        $this->assertSame(0, $months['2031-03']['transfers']);

        $this->assertSame(2, $musaned['totals']['transfers']);
        $this->assertSame(6, $musaned['totals']['contracts']);
        $this->assertSame(4500.0, $musaned['totals']['commission']);
        $this->assertSame(3825.0, $musaned['totals']['commission_net']);
        $this->assertSame(3.0, $musaned['totals']['commission_rate']);
        $this->assertSame(2, $musaned['unposted']['transfers'], 'حوالة بلا قيد بنكي مرحّل تُعد نقداً في الطريق.');
        $this->assertSame(145500.0, $musaned['unposted']['amount']);
    }

    public function test_sponsorship_transfer_invoices_have_separate_sales_and_receivables(): void
    {
        $this->recruitmentInvoice('2031-02-05', 8000, false);
        $this->sponsorshipTransferInvoice('2031-02-10', 12000, 5000);

        $report = $this->report();

        $this->assertSame(8000.0, $report['sales']['totals']['recruitment']);
        $this->assertSame(12000.0, $report['sales']['totals']['sponsorship']);
        $this->assertSame(1, $report['sponsorship_receivables']['totals']['documents']);
        $this->assertSame(5000.0, $report['sponsorship_receivables']['totals']['paid']);
        $this->assertSame(7000.0, $report['sponsorship_receivables']['totals']['remaining']);
        $this->assertSame(15000.0, $report['kpis']['receivables_total']);
    }

    public function test_musaned_status_filter_also_covers_ingaz_delegations(): void
    {
        $transferredDelegation = $this->delegation('2031-02-12', 1600, 140, 'سائق خاص');
        $this->delegation('2031-02-18', 1200, 100, 'عامل منزلي');
        $this->markDelegationAsTransferredFromMusaned($transferredDelegation, '2031-02-20');

        $transferred = $this->report([
            'musaned_status' => OperationalSummaryService::MUSANED_STATUS_TRANSFERRED,
        ]);
        $notTransferred = $this->report([
            'musaned_status' => OperationalSummaryService::MUSANED_STATUS_NOT_TRANSFERRED,
        ]);

        $this->assertSame(1, $transferred['delegations']['totals']['delegations']);
        $this->assertSame(1600.0, $transferred['delegations']['totals']['revenue']);
        $this->assertSame(1600.0, $transferred['sales']['totals']['delegations']);
        $this->assertSame(1, $notTransferred['delegations']['totals']['delegations']);
        $this->assertSame(1200.0, $notTransferred['delegations']['totals']['revenue']);
        $this->assertSame(1200.0, $notTransferred['sales']['totals']['delegations']);
    }

    public function test_external_office_dues_come_from_approved_ledger_balance_only(): void
    {
        $this->ledgerEntry('2031-01-20', 0, 40000);
        $this->ledgerEntry('2031-02-20', 15000, 0);
        $this->ledgerEntry('2031-02-25', 0, 9000, Constraint::APPROVAL_PENDING);
        $this->ledgerEntry('2031-06-01', 0, 7000);

        DB::table('contract_external_office_dispatches')->insert([
            'contract_id' => $this->contractId,
            'recruitment_office_id' => $this->officeId,
            'dispatch_date' => '2031-02-05',
            'office_contract_cost_usd' => 1800,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $offices = $this->report()['external_offices'];
        $row = $offices['rows']->firstWhere('id', $this->officeId);

        $this->assertNotNull($row, 'المكتب صاحب الرصيد يجب أن يظهر في التقرير.');
        $this->assertSame(25000.0, $row->payable, 'دائن 40000 ناقص مدين 15000؛ القيد المعلّق والقيد اللاحق للفترة مستبعدان.');
        $this->assertSame(0.0, $row->prepaid);
        $this->assertSame(1, $row->period_contracts);
        $this->assertSame(1800.0, $row->period_cost_usd);
        $this->assertSame(0, $offices['unlinked_offices']);
    }

    public function test_headline_matches_the_full_report_for_the_shared_figures(): void
    {
        $this->rentalInvoice('2031-02-10', 8000, 3000, 'RC-31-PARTIAL');
        $this->recruitmentInvoice('2031-01-20', 25000, true);
        $this->recruitmentInvoice('2031-03-05', 12000, false);
        $this->ledgerEntry('2031-01-20', 0, 40000);

        $report = $this->report();
        $headline = app(OperationalSummaryService::class)->headline(self::FROM, self::TO);

        $this->assertSame($report['kpis']['receivables_total'], $headline['receivables_total']);
        $this->assertSame($report['kpis']['rental_receivables'], $headline['rental_receivables']);
        $this->assertSame($report['kpis']['recruitment_receivables'], $headline['recruitment_receivables']);
        $this->assertSame($report['kpis']['offices_payable'], $headline['offices_payable']);
        $this->assertSame($report['kpis']['musaned_commission_net'], $headline['musaned_commission_net']);
        $this->assertSame(2, $headline['receivables_documents'], 'فاتورة تأجير جزئية + فاتورة استقدام غير مسددة.');
    }

    public function test_period_scope_excludes_invoices_issued_before_the_range(): void
    {
        $this->rentalInvoice('2030-11-05', 6000, 0, 'RC-30-OLD');
        $this->rentalInvoice('2031-02-10', 8000, 3000, 'RC-31-PARTIAL');

        $period = $this->report()['rental_receivables'];
        $this->assertSame(['RC-31-PARTIAL'], $period['rows']->pluck('contract_number')->all(), 'النطاق الافتراضي هو فواتير الفترة، فالفاتورة الأقدم خارجها.');
        $this->assertSame(5000.0, $period['totals']['remaining']);
        $this->assertSame(11000.0, $period['outstanding_all'], 'الرصيد القائم كاملاً يظل معروضاً حتى لا يختفي الدين القديم.');

        $all = $this->report(['scope' => OperationalSummaryService::SCOPE_OUTSTANDING])['rental_receivables'];
        $this->assertSame(['RC-30-OLD', 'RC-31-PARTIAL'], $all['rows']->pluck('contract_number')->sort()->values()->all());
        $this->assertSame(11000.0, $all['totals']['remaining']);
        $this->assertArrayNotHasKey('outstanding_all', $all, 'لا معنى لمقارنة إضافية حين يكون النطاق هو كل الرصيد أصلاً.');
    }

    public function test_receivables_paginate_while_totals_and_aging_cover_the_whole_set(): void
    {
        foreach (range(1, 7) as $i) {
            $this->rentalInvoice('2031-02-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 1000 * $i, 0, 'RC-31-P' . $i);
        }

        $first = $this->report(['per_page' => 3, 'rental_page' => 1])['rental_receivables'];
        $second = $this->report(['per_page' => 3, 'rental_page' => 2])['rental_receivables'];
        $unpaged = $this->report(['per_page' => null])['rental_receivables'];

        $this->assertCount(3, $first['rows']);
        $this->assertCount(3, $second['rows']);
        $this->assertCount(7, $unpaged['rows']);
        $this->assertNull($unpaged['paginator'], 'التصدير يطلب كل الصفوف بلا ترقيم.');

        $this->assertSame(7, $first['paginator']->total());
        $this->assertSame(3, $first['paginator']->lastPage());
        $this->assertSame(['RC-31-P7', 'RC-31-P6', 'RC-31-P5'], $first['rows']->pluck('contract_number')->all(), 'الترتيب بالأعلى متبقياً عبر الصفحات.');
        $this->assertSame(['RC-31-P4', 'RC-31-P3', 'RC-31-P2'], $second['rows']->pluck('contract_number')->all());

        $expected = 28000.0;
        $this->assertSame($expected, $first['totals']['remaining'], 'المجاميع تُحسب على كل الصفوف لا على صفحة واحدة.');
        $this->assertSame($expected, $second['totals']['remaining']);
        $this->assertSame($expected, array_sum($first['aging']), 'التقادم كذلك يغطي كل الصفوف.');
        $this->assertSame(7, $first['totals']['documents']);
    }

    public function test_export_writes_every_row_with_money_formatting_and_a_totals_line(): void
    {
        $this->rentalInvoice('2031-02-10', 8000, 3000, 'RC-31-PARTIAL');
        $this->rentalInvoice('2031-03-20', 4000, 0, 'RC-31-LATER');

        // per_page: null هو ما يمرره المتحكم عند التصدير والطباعة.
        $report = $this->report(['per_page' => null]);
        $book = app(OperationalSummaryExportService::class)->workbook($report);

        $this->assertSame(
            ['المبيعات الشهرية', 'ذمم التأجير', 'ذمم الاستقدام', 'ذمم نقل الكفالة', 'التفاويض الرجالية', 'عمولات مساند', 'المكاتب الخارجية'],
            $book->getSheetNames()
        );

        $sheet = $book->getSheetByName('ذمم التأجير');

        $this->assertSame('رقم العقد', $sheet->getCell('A3')->getValue(), 'رأس الجدول في السطر الثالث بعد العنوان وسطر النطاق.');
        $this->assertSame('RC-31-PARTIAL / فاتورة أساسية #' . $report['rental_receivables']['rows'][0]->id, $sheet->getCell('A4')->getValue());
        $this->assertSame('RC-31-LATER / فاتورة أساسية #' . $report['rental_receivables']['rows'][1]->id, $sheet->getCell('A5')->getValue());
        $this->assertSame('الإجمالي', $sheet->getCell('A6')->getValue(), 'سطر الإجمالي يلي آخر صف بيانات مباشرة.');
        $this->assertSame(9000.0, $sheet->getCell('I6')->getValue());

        $this->assertSame('4,000.00', $sheet->getCell('I5')->getFormattedValue(), 'أعمدة المبالغ بفواصل آلاف وخانتين عشريتين.');
        $this->assertTrue($sheet->getStyle('A6')->getFont()->getBold());
        $this->assertSame('A3:J3', (string) $sheet->getAutoFilter()->getRange());

        $book->disconnectWorksheets();
    }

    public function test_delegation_revenue_is_reported_separately_and_not_double_counted_in_recruitment(): void
    {
        $this->recruitmentInvoice('2031-01-20', 25000, true);
        $this->delegation('2031-02-12', 1600, 140, 'سائق خاص');
        $this->delegation('2031-02-18', 1600, 140, 'سائق خاص');
        $this->delegation('2031-03-04', 1600, 140, 'عامل منزلي');

        $report = $this->report();
        $sales = $report['sales']['totals'];
        $delegations = $report['delegations'];

        $this->assertSame(4800.0, $sales['delegations'], 'فواتير التفاويض لها عمودها الخاص.');
        $this->assertSame(3, $sales['delegations_count']);
        $this->assertSame(25000.0, $sales['recruitment'], 'وهي مستثناة من عمود الاستقدام حتى لا تُحتسب مرتين.');
        $this->assertSame(1, $sales['recruitment_count']);
        $this->assertSame(29800.0, $sales['invoiced'], 'الإجمالي يساوي الاستقدام زائد التفاويض دون تكرار.');

        $months = collect($delegations['months'])->keyBy('key');
        $this->assertSame(2, $months['2031-02']['delegations']);
        $this->assertSame(3200.0, $months['2031-02']['revenue']);
        $this->assertSame(280.0, $months['2031-02']['cost']);
        $this->assertSame(2920.0, $months['2031-02']['net']);
        $this->assertSame(1600.0, $months['2031-02']['average_revenue']);

        $t = $delegations['totals'];
        $this->assertSame(3, $t['delegations']);
        $this->assertSame(4800.0, $t['revenue']);
        $this->assertSame(420.0, $t['cost']);
        $this->assertSame(4380.0, $t['net']);
        $this->assertSame(91.3, $t['margin_rate']);

        $professions = $delegations['professions']->keyBy('profession');
        $this->assertSame(2, $professions['سائق خاص']->delegations);
        $this->assertSame(3200.0, $professions['سائق خاص']->revenue);
        $this->assertSame(1600.0, $professions['عامل منزلي']->revenue);
    }

    public function test_unapproved_delegation_cost_is_excluded_from_net_but_reported_on_its_own(): void
    {
        $this->delegation('2031-02-12', 1600, 140, 'سائق خاص');
        $this->delegation('2031-02-18', 1600, 140, 'سائق خاص', Constraint::APPROVAL_PENDING);

        $delegations = $this->report()['delegations'];

        $this->assertSame(140.0, $delegations['totals']['cost'], 'الصافي يحتسب التكلفة المعتمدة وحدها، كبقية التقرير.');
        $this->assertSame(3060.0, $delegations['totals']['net']);
        $this->assertSame(1, $delegations['pending_cost']['entries'], 'والتكلفة المعلّقة تُعرض صراحة حتى لا يبدو الهامش أكبر من حقيقته.');
        $this->assertSame(140.0, $delegations['pending_cost']['amount']);
    }

    public function test_headline_reports_delegation_revenue_for_the_finance_dashboard(): void
    {
        $this->delegation('2031-02-12', 1600, 140, 'سائق خاص');

        $headline = app(OperationalSummaryService::class)->headline(self::FROM, self::TO);

        $this->assertSame(1, $headline['delegation_count']);
        $this->assertSame(1600.0, $headline['delegation_revenue']);
        $this->assertSame(1460.0, $headline['delegation_net']);
    }

    public function test_rental_invoices_mirrored_into_the_invoices_table_are_counted_once(): void
    {
        // فاتورة تأجير مسدّدة جزئياً، لها نسخة في جدول invoices كما يفعل الترحيل
        // القديم عبر legacy_invoice_id — النسخة لا تحمل المدفوع ولا المتبقّي.
        $this->rentalInvoice('2031-02-10', 8000, 6000, 'RC-31-MIRROR');
        $rentalInvoiceId = DB::table('rental_contract_invoices')->max('id');

        $mirrorId = DB::table('invoices')->insertGetId([
            'client_id' => $this->userId,
            'total_due' => 8000,
            'is_paid' => 0,
            'created_at' => '2031-02-10 09:00:00',
            'updated_at' => '2031-02-10 09:00:00',
        ]);
        DB::table('rental_contract_invoices')->where('id', $rentalInvoiceId)
            ->update(['legacy_invoice_id' => $mirrorId]);

        $this->recruitmentInvoice('2031-03-05', 12000, false);

        $report = $this->report();
        $sales = $report['sales']['totals'];

        $this->assertSame(8000.0, $sales['rental'], 'فاتورة التأجير تُحتسب في عمودها وحده.');
        $this->assertSame(12000.0, $sales['recruitment'], 'ولا تتكرّر في عمود الاستقدام.');
        $this->assertSame(1, $sales['recruitment_count']);
        $this->assertSame(20000.0, $sales['invoiced'], 'الإجمالي لا يحتسب الفاتورة مرّتين.');

        $rentalDue = $report['rental_receivables'];
        $this->assertSame(2000.0, $rentalDue['totals']['remaining'], 'المتبقّي من الجانب التأجيري لأنه وحده يحمل المدفوع.');

        $recruitmentDue = $report['recruitment_receivables'];
        $this->assertSame([12000.0], $recruitmentDue['rows']->pluck('remaining_amount')->all(), 'النسخة المرآة لا تظهر كذمّة بكامل قيمتها.');
        $this->assertSame(12000.0, $recruitmentDue['totals']['remaining']);

        $headline = app(OperationalSummaryService::class)->headline(self::FROM, self::TO);
        $this->assertSame(14000.0, $headline['receivables_total'], 'ولوحة المالية تقرأ نفس الرقم.');
    }
}
