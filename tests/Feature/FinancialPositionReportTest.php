<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\FinancialPositionService;
use Tests\TestCase;

class FinancialPositionReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('fn_accounts', function (Blueprint $table) {
            $table->integer('id'); $table->integer('parent_id')->nullable();
            $table->string('code'); $table->string('name'); $table->integer('type')->default(1);
        });
        Schema::create('fn_constraints', function (Blueprint $table) {
            $table->integer('id'); $table->date('date'); $table->string('approval_status');
            $table->timestamp('approved_at')->nullable(); $table->integer('approved_by_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('fn_constraint_details', function (Blueprint $table) {
            $table->integer('constraint_id'); $table->integer('account_id'); $table->dateTime('date');
            $table->decimal('debtor_value', 20, 2); $table->decimal('creditor_value', 20, 2); $table->softDeletes();
        });
        $this->resetApprovalCache();
        DB::table('fn_accounts')->insert([
            ['id'=>1,'parent_id'=>null,'code'=>'1','name'=>'Assets'],
            ['id'=>2,'parent_id'=>1,'code'=>'12','name'=>'Current assets'],
            ['id'=>3,'parent_id'=>null,'code'=>'3','name'=>'Equity'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->resetApprovalCache();
        parent::tearDown();
    }

    private function resetApprovalCache(): void
    {
        $property = new \ReflectionProperty(Constraint::class, 'approvalColumnsExist');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function entry(int $id, string $date, string $status = 'approved', bool $deleted = false, bool $detailDeleted = false): void
    {
        DB::table('fn_constraints')->insert(['id'=>$id,'date'=>substr($date,0,10),'approval_status'=>$status,'deleted_at'=>$deleted ? '2026-01-01' : null]);
        DB::table('fn_constraint_details')->insert([
            ['constraint_id'=>$id,'account_id'=>2,'date'=>$date,'debtor_value'=>'100.10','creditor_value'=>'0','deleted_at'=>$detailDeleted ? '2026-01-01' : null],
            ['constraint_id'=>$id,'account_id'=>3,'date'=>$date,'debtor_value'=>'0','creditor_value'=>'100.10','deleted_at'=>$detailDeleted ? '2026-01-01' : null],
        ]);
    }

    public function test_report_includes_history_and_end_of_day_but_excludes_pending_deleted_and_future_entries(): void
    {
        $this->entry(1, '2024-01-01');
        $this->entry(2, '2026-09-05 23:59:59');
        $this->entry(3, '2026-09-06');
        $this->entry(4, '2026-09-01', 'pending');
        $this->entry(5, '2026-09-01', 'approved', true);
        $this->entry(6, '2026-09-01', 'approved', false, true);
        // An orphaned detail must not contribute to the statement.
        DB::table('fn_constraint_details')->insert(['constraint_id'=>999,'account_id'=>2,'date'=>'2026-09-01','debtor_value'=>900,'creditor_value'=>0]);
        $service = app(FinancialPositionService::class);
        $current = $service->report('2026-09-05');
        $prior = $service->report('2024-12-31');
        $this->assertSame(20020, $current['assets']);
        $this->assertSame(10010, $prior['assets']);
        $this->assertTrue($current['balanced']);
        $this->assertSame(1, $current['pending_count']);
    }

    public function test_empty_period_is_not_presented_as_having_transactions(): void
    {
        $report = app(FinancialPositionService::class)->report('2020-01-01');
        $this->assertSame(0, $report['postingCount']);
        $this->assertNull($report['current_ratio']);
    }
}
