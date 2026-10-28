<?php
namespace Tests\Feature\Branch;

use App\Models\Permission;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RecruitmentContractArrivalReportingTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    private function viewer()
    {
        $this->setUpBranches();
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        foreach ([\App\NewPermKeys::REC_CONTRACTS_VIEW, \App\NewPermKeys::REC_CONTRACTS_EXPORT] as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(['name'=>$permission,'guard_name'=>'admin'],[
                'display_name'=>$permission,'group_name'=>'recruitment_contracts','section_name'=>'operations_management','is_active'=>true,
            ]);
            $admin->givePermissionTo($permission);
        }
        $this->actingAsAdmin($admin);
        return [$branch,$admin];
    }

    private function contract(int $branch, string $status, ?string $arrival): int
    {
        return DB::table('contracts')->insertGetId(['branch_id'=>$branch,'type'=>'recruitment','status'=>$status,
            'warranty_start_date'=>$arrival,'created_at'=>'2020-01-01 00:00:00','updated_at'=>now()]);
    }

    public function test_arrival_filter_agrees_between_table_statistics_and_export_and_preserves_branch_isolation(): void
    {
        [$branch] = $this->viewer();
        $own = $this->contract($branch->id,'arrival','2026-06-30 23:59:59');
        $this->contract($branch->id,'arrival','2026-07-01 00:00:00');
        $this->contract($this->makeBranch()->id,'arrival','2026-06-15 00:00:00');
        $params=['date_type'=>'arrival','date_from'=>'2026-06-01','date_to'=>'2026-06-30','arrival_scope'=>'arrived'];
        $stats=$this->getJson(route('RecruitmentContract.statistics',$params))->assertOk();
        $stats->assertJsonPath('data.total',1)->assertJsonPath('data.operations_arrived',1);
        $table=$this->getJson(route('RecruitmentContract.index',$params),['X-Requested-With'=>'XMLHttpRequest'])->assertOk();
        $this->assertSame(1,$table->json('recordsFiltered'));
        $this->assertSame($own,(int)$table->json('data.0.id'));
        $method=new \ReflectionMethod(RecruitmentContractController::class,'recruitmentContractExportQuery');$method->setAccessible(true);
        $query=$method->invoke(app(RecruitmentContractController::class),Request::create('/','GET',$params));
        $this->assertEquals([$own],$query->pluck('contracts.id')->all());
        // The same date range means something different when the user chooses creation date.
        $this->getJson(route('RecruitmentContract.statistics',array_merge($params,['date_type'=>'created'])))
            ->assertOk()->assertJsonPath('data.total',0);
    }

    public function test_historical_arrival_is_counted_once_after_return_and_invalid_dates_return_validation_errors(): void
    {
        [$branch]=$this->viewer();
        $id=$this->contract($branch->id,'back',null);
        foreach (['2026-06-10','2026-06-11'] as $date) DB::table('contract_status_logs')->insert([
            'contract_id'=>$id,'branch_id'=>$branch->id,'status'=>'arrival','date'=>$date,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->getJson(route('RecruitmentContract.statistics',['date_type'=>'arrival','date_from'=>'2026-06-01','date_to'=>'2026-06-30']))
            ->assertOk()->assertJsonPath('data.total',1)->assertJsonPath('data.operations_arrived',1);
        $this->getJson(route('RecruitmentContract.statistics',['date_type'=>'invalid']))->assertStatus(422);
        $this->getJson(route('RecruitmentContract.statistics',['date_from'=>'2026-07-01','date_to'=>'2026-06-01']))->assertStatus(422);
    }

    public function test_overdue_scope_excludes_unscheduled_and_cancelled_trips(): void
    {
        [$branch]=$this->viewer();
        foreach (['pending','cancelled',null] as $status) {
            $id=$this->contract($branch->id,'tazker',null);
            if($status) DB::table('travel_histories')->insert(['order_id'=>$id,'branch_id'=>$branch->id,'user_id'=>1,'maid_id'=>1,
                'travel_type'=>'arrival','travel_with'=>'airport','status'=>$status,'arrival_date'=>today()->subDay()->toDateString(),
                'created_at'=>now(),'updated_at'=>now()]);
        }
        $this->getJson(route('RecruitmentContract.statistics',['arrival_scope'=>'delayed']))->assertOk()
            ->assertJsonPath('data.total',1)->assertJsonPath('data.operations_delayed',1);
    }

    public function test_arrivals_report_page_renders_period_and_current_sections(): void
    {
        [$branch]=$this->viewer();
        $this->contract($branch->id,'arrival',now()->startOfMonth()->toDateTimeString());
        foreach (['contracts','offices','employees'] as $view) {
            $this->get(route('reports.arrivals',['view'=>$view]))->assertOk()
                ->assertSee('خلال الفترة المختارة')->assertSee('الوضع الحالي');
        }
        $this->get(route('reports.arrivals',['bucket'=>'delayed']))->assertOk();
    }

    public function test_month_cards_split_this_month_arrivals_from_upcoming_and_unscheduled_trips(): void
    {
        [$branch]=$this->viewer();
        $this->contract($branch->id,'arrival',now()->startOfMonth()->toDateTimeString());
        $this->contract($branch->id,'arrival',now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString());
        $upcoming=$this->contract($branch->id,'tazker',null);
        DB::table('travel_histories')->insert(['order_id'=>$upcoming,'branch_id'=>$branch->id,'user_id'=>1,'maid_id'=>1,
            'travel_type'=>'arrival','travel_with'=>'airport','status'=>'pending','arrival_date'=>now()->endOfMonth()->toDateString(),
            'created_at'=>now(),'updated_at'=>now()]);
        $this->contract($branch->id,'tazker',null);
        $this->getJson(route('RecruitmentContract.statistics'))->assertOk()
            ->assertJsonPath('data.operations_arrived_month',1)->assertJsonPath('data.operations_arrived',2)
            ->assertJsonPath('data.operations_expected_month',1)->assertJsonPath('data.operations_unscheduled',1)
            ->assertJsonPath('data.operations_waiting',2)->assertJsonPath('data.arrival_comparison.previous',1);
        $this->getJson(route('RecruitmentContract.statistics',['arrival_scope'=>'expected_month']))->assertOk()
            ->assertJsonPath('data.total',1);
    }
}
