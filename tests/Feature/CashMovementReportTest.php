<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\CashMovementReportService;
use Nami\Finance\Services\CashMovementExportService;
use Tests\TestCase;

class CashMovementReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $this->resetApprovalCache();
        Schema::create('fn_accounts', function (Blueprint $t) {
            $t->integer('id'); $t->integer('parent_id')->nullable(); $t->string('code'); $t->string('name'); $t->integer('type')->default(1);
        });
        Schema::create('fn_constraints', function (Blueprint $t) {
            $t->integer('id'); $t->string('code')->nullable(); $t->integer('type')->default(1); $t->text('statement')->nullable();
            $t->date('date'); $t->string('approval_status'); $t->timestamp('approved_at')->nullable(); $t->integer('approved_by_id')->nullable(); $t->softDeletes();
        });
        Schema::create('fn_constraint_details', function (Blueprint $t) {
            $t->increments('id'); $t->integer('constraint_id'); $t->integer('account_id'); $t->dateTime('date'); $t->text('statement')->nullable();
            $t->decimal('debtor_value',20,2); $t->decimal('creditor_value',20,2); $t->softDeletes();
        });
        DB::table('fn_accounts')->insert([
            ['id'=>1,'parent_id'=>null,'code'=>'121','name'=>'Cash parent','type'=>16],
            ['id'=>2,'parent_id'=>1,'code'=>'1211','name'=>'=unsafe name','type'=>1],
            ['id'=>3,'parent_id'=>null,'code'=>'122','name'=>'Bank','type'=>14],
            ['id'=>4,'parent_id'=>null,'code'=>'5','name'=>'Expense','type'=>44],
        ]);
    }

    protected function tearDown(): void
    {
        $this->resetApprovalCache();
        parent::tearDown();
    }

    private function resetApprovalCache(): void
    {
        $p = new \ReflectionProperty(Constraint::class, 'approvalColumnsExist');
        $p->setAccessible(true); $p->setValue(null,null);
    }

    private function entry(int $id, string $date, int $debitAccount, int $creditAccount, string $amount, string $status='approved'): void
    {
        DB::table('fn_constraints')->insert(['id'=>$id,'date'=>substr($date,0,10),'approval_status'=>$status]);
        DB::table('fn_constraint_details')->insert([
            ['constraint_id'=>$id,'account_id'=>$debitAccount,'date'=>$date,'debtor_value'=>$amount,'creditor_value'=>0],
            ['constraint_id'=>$id,'account_id'=>$creditAccount,'date'=>$date,'debtor_value'=>0,'creditor_value'=>$amount],
        ]);
    }

    public function test_history_parent_postings_transfers_and_running_balances_reconcile(): void
    {
        $this->entry(1,'2024-01-01',2,4,'100.10');
        $this->entry(2,'2026-09-01',1,4,'10.20');
        $this->entry(3,'2026-09-01',3,2,'30.10');
        $this->entry(4,'2026-09-02 23:59:59',4,2,'5.05');
        $this->entry(5,'2026-09-03',2,4,'100');
        $s = app(CashMovementReportService::class);
        $r = $s->report($s->accounts(),'2026-09-01','2026-09-02');
        $this->assertCount(3,$s->accounts());
        $this->assertSame(10010,$r['opening']);
        $this->assertSame(4030,$r['receipts']);
        $this->assertSame(3515,$r['payments']);
        $this->assertSame(10525,$r['closing']);
        $this->assertSame(6495,$r['accounts'][2]['closing']);
        $this->assertSame(11030,$r['days'][1]['opening']);
        $this->assertSame($r['closing'],end($r['movements'])['balance']);
        $this->assertSame($r['closing'],array_sum(array_column($r['accounts'],'closing')));
    }

    public function test_pending_and_deleted_entries_are_excluded_and_empty_period_keeps_opening(): void
    {
        $this->entry(1,'2024-01-01',2,4,'0.29');
        $this->entry(2,'2026-09-01',2,4,'500','pending');
        $this->entry(3,'2026-09-01',2,4,'600');
        $this->entry(4,'2026-09-01',2,4,'700');
        DB::table('fn_constraints')->where('id',3)->update(['deleted_at'=>'2026-09-01']);
        DB::table('fn_constraint_details')->where('constraint_id',4)->update(['deleted_at'=>'2026-09-01']);
        $s=app(CashMovementReportService::class);
        $r=$s->report($s->accounts(),'2026-09-01','2026-09-02');
        $this->assertSame(29,$r['opening']);
        $this->assertSame(29,$r['closing']);
        $this->assertSame(0,$r['count']);
        $this->assertSame(1,$r['pending']);
    }

    public function test_pagination_preserves_global_totals_and_export_contains_every_row_as_safe_cells(): void
    {
        for($i=1;$i<=105;$i++) $this->entry($i,'2026-09-01',2,4,'1.01');
        $s=app(CashMovementReportService::class);
        $r=$s->report($s->accounts(),'2026-09-01','2026-09-02',2);
        $this->assertSame(105,$r['count']);
        $this->assertCount(5,$r['movements']);
        $this->assertSame(10605,$r['closing']);
        $this->assertSame(10201,$r['movements'][0]['balance']);
        $export=$s->report($s->accounts(),'2026-09-01','2026-09-02',1,false);
        $book=app(CashMovementExportService::class)->workbook($export);
        $this->assertSame(107,$book->getSheetByName('تفاصيل القيود')->getHighestRow());
        $this->assertSame('s',$book->getSheetByName('تفاصيل القيود')->getCell('E3')->getDataType());
        $this->assertSame('=unsafe name',$book->getSheetByName('تفاصيل القيود')->getCell('E3')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_selecting_one_account_keeps_its_transfers_and_negative_balance(): void
    {
        $this->entry(1,'2026-09-01',3,2,'20');
        $s=app(CashMovementReportService::class);
        $r=$s->report([2=>$s->accounts()[2]],'2026-09-01','2026-09-02');
        $this->assertSame(-2000,$r['closing']);
        $this->assertSame(1,$r['negative_accounts']);
        $this->assertSame(['122 — Bank'],$r['movements'][0]['counterparts']);
    }
    public function test_reference_report_uses_exact_reference_buckets_and_signed_net_balances(): void
    {
        DB::table('fn_accounts')->insert([
            ['id'=>5,'parent_id'=>4,'code'=>'5211','name'=>'Rent','type'=>1],
            ['id'=>6,'parent_id'=>null,'code'=>'1252','name'=>'Advance','type'=>1],
            ['id'=>7,'parent_id'=>5,'code'=>'999','name'=>'Rent child','type'=>1],
        ]);
        $this->entry(1,'2024-01-01',2,4,'100');
        $this->entry(2,'2026-09-01',5,2,'20');
        $this->entry(3,'2026-09-01',7,2,'5');
        $this->entry(4,'2026-09-01',2,6,'3');
        $this->entry(5,'2026-09-01',5,2,'10','pending');
        $s=app(\Nami\Finance\Services\DailyCashReferenceService::class);
        $r=$s->report(null,'2026-09-01');
        $this->assertCount(20,$r['groups']['receipts']['rows']);
        $this->assertCount(116,$r['groups']['expenses']['rows']);
        $this->assertSame(7800,$r['groups']['receipts']['total']);
        $this->assertSame(2200,$r['groups']['expenses']['total']);
        $this->assertSame(2500,$r['groups']['expenses']['rows'][0]['amount']);
        $this->assertSame(1,$r['pending']);
        $this->assertNotEmpty($r['issues']);
        $this->assertTrue($r['groups']['receipts']['rows'][1]['missing']);
        $all=$s->report(null,'2026-09-01','all');
        $this->assertSame(6800,$all['groups']['receipts']['total']);
        $this->assertSame(3200,$all['groups']['expenses']['total']);
        $period=$s->report('2026-09-01','2026-09-01');
        $this->assertSame(-2200,$period['groups']['receipts']['total']);
        $book=app(CashMovementExportService::class)->referenceWorkbook($r);
        $this->assertSame(7800/100,$book->getActiveSheet()->getCell('C122')->getValue());
        $this->assertSame('s',$book->getActiveSheet()->getCell('B6')->getDataType());
        $this->assertSame('غير مربوط',$book->getActiveSheet()->getCell('C7')->getValue());
        $book->disconnectWorksheets();
    }

    private function createReferenceLinesTable(): void
    {
        Schema::create('fn_daily_cash_reference_lines', function (Blueprint $t) {
            $t->increments('id'); $t->string('group_key'); $t->integer('sort_order'); $t->integer('account_id')->nullable();
            $t->string('reference_code'); $t->string('label'); $t->timestamps();
        });
    }

    public function test_reference_report_without_linked_lines_keeps_config_codes(): void
    {
        $this->createReferenceLinesTable();
        $r=app(\Nami\Finance\Services\DailyCashReferenceService::class)->report(null,'2026-09-01');
        $this->assertCount(20,$r['groups']['receipts']['rows']);
        $this->assertSame('1211',$r['groups']['receipts']['rows'][0]['code']);
        $this->assertFalse($r['groups']['receipts']['rows'][0]['missing']);
    }

    public function test_linked_reference_lines_follow_their_account_after_code_change(): void
    {
        $this->createReferenceLinesTable();
        DB::table('fn_accounts')->insert(['id'=>5,'parent_id'=>4,'code'=>'5211','name'=>'Rent','type'=>1]);
        DB::table('fn_daily_cash_reference_lines')->insert([
            ['group_key'=>'receipts','sort_order'=>1,'account_id'=>2,'reference_code'=>'1211','label'=>'صندوق'],
            ['group_key'=>'expenses','sort_order'=>1,'account_id'=>5,'reference_code'=>'5211','label'=>'الايجارات'],
            ['group_key'=>'expenses','sort_order'=>2,'account_id'=>99,'reference_code'=>'5999','label'=>'محذوف'],
        ]);
        $this->entry(1,'2026-09-01',5,2,'20');
        // إعادة ترقيم الحسابين وإعطاء كوديهما القديمين لحسابين آخرين لا يغيّر البنود
        DB::table('fn_accounts')->where('id',5)->update(['code'=>'50001']);
        DB::table('fn_accounts')->where('id',2)->update(['code'=>'121000']);
        DB::table('fn_accounts')->insert([['id'=>8,'parent_id'=>null,'code'=>'5211','name'=>'Other','type'=>1],['id'=>9,'parent_id'=>null,'code'=>'1211','name'=>'Other cash','type'=>1]]);
        $this->entry(2,'2026-09-01',8,9,'7');
        $r=app(\Nami\Finance\Services\DailyCashReferenceService::class)->report(null,'2026-09-01');
        $this->assertSame(['receipts','expenses'],array_keys($r['groups']));
        $this->assertSame('121000',$r['groups']['receipts']['rows'][0]['code']);
        $this->assertSame(-2000,$r['groups']['receipts']['total']);
        $this->assertSame('50001',$r['groups']['expenses']['rows'][0]['code']);
        $this->assertSame('Rent',$r['groups']['expenses']['rows'][0]['name']);
        $this->assertSame(2000,$r['groups']['expenses']['rows'][0]['amount']);
        $this->assertTrue($r['groups']['expenses']['rows'][1]['missing']);
        $this->assertContains('البند «محذوف»: الحساب المربوط به لم يعد موجوداً',$r['issues']);
    }

    public function test_link_command_resolves_codes_as_of_before_a_restructure(): void
    {
        $this->createReferenceLinesTable();
        Schema::table('fn_accounts', function (Blueprint $t) { $t->timestamp('created_at')->nullable(); });
        Schema::create('fn_account_restructure_logs', function (Blueprint $t) {
            $t->increments('id'); $t->string('action'); $t->integer('account_id'); $t->string('account_code')->nullable();
            $t->integer('target_account_id')->nullable(); $t->timestamp('reversed_at')->nullable(); $t->timestamps();
        });
        // بعد الترتيب: 1211 (id 2) نُقل وصار 121000، والكود 1211 أُعطي لحساب جديد؛ 1212 دُمج في 1213
        DB::table('fn_accounts')->where('id',2)->update(['code'=>'121000','created_at'=>'2026-01-01']);
        DB::table('fn_accounts')->insert([
            ['id'=>10,'parent_id'=>1,'code'=>'1211','name'=>'New holder','type'=>1,'created_at'=>'2026-10-28 18:00:00'],
            ['id'=>11,'parent_id'=>1,'code'=>'1213','name'=>'Custody','type'=>1,'created_at'=>'2026-01-01'],
        ]);
        DB::table('fn_account_restructure_logs')->insert([
            ['action'=>'move','account_id'=>2,'account_code'=>'1211','target_account_id'=>null,'created_at'=>'2026-10-28 18:00:00'],
            ['action'=>'merge','account_id'=>12,'account_code'=>'1212','target_account_id'=>11,'created_at'=>'2026-10-28 18:00:00'],
        ]);
        $this->artisan('finance:link-daily-cash-reference',['--as-of'=>'2026-10-28 17:00:00'])->assertExitCode(0);
        $line=fn ($code)=>DB::table('fn_daily_cash_reference_lines')->where('reference_code',$code)->value('account_id');
        $this->assertSame(2,(int)$line('1211'));
        $this->assertSame(11,(int)$line('1212'));
        $this->assertSame(11,(int)$line('1213'));
        $this->assertNull($line('1222'));
        $this->artisan('finance:link-daily-cash-reference')->assertExitCode(1);
    }
}
