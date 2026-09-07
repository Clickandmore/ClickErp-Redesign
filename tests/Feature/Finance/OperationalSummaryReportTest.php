<?php

namespace Tests\Feature\Finance;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
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

        $nationalityId = DB::table('nationalities')->insertGetId([
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

    private function recruitmentInvoice(string $date, float $due, bool $paid): void
    {
        DB::table('invoices')->insert([
            'contract_id' => $this->contractId,
            'client_id' => $this->userId,
            'total_due' => $due,
            'is_paid' => $paid,
            'created_at' => $date . ' 11:00:00',
            'updated_at' => $date . ' 11:00:00',
        ]);
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
}
