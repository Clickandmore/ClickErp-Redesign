<?php

namespace Tests\Feature\Branch;

use App\Http\Controllers\admin\BranchController;
use App\Models\auth\Admin;
use App\Models\Branch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class BranchReportScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function report(Admin $admin, array $query = [])
    {
        $this->actingAsAdmin($admin);

        return app(BranchController::class)->report(
            Request::create('/admin/ingaz/branches/report', 'GET', $query)
        );
    }

    /** @test */
    public function a_restricted_employee_cannot_switch_the_branch_report_from_the_url(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($own)->assignRole('Administrator'));

        $view = $this->report($admin, ['branch_id' => $other->id]);

        $this->assertSame($own->id, $view->getData()['selectedBranchId']);
        $this->assertEquals([$own->id], $view->getData()['rows']->pluck('branch.id')->all());
        $this->assertEquals([$own->id], $view->getData()['branches']->pluck('id')->all());
    }

    /** @test */
    public function a_branch_employee_with_view_all_can_report_on_another_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $admin->assignRole('Administrator');

        $view = $this->report($admin, ['branch_id' => $other->id]);

        $this->assertSame($other->id, $view->getData()['selectedBranchId']);
        $this->assertContains($own->id, $view->getData()['branches']->pluck('id'));
        $this->assertContains($other->id, $view->getData()['branches']->pluck('id'));
        $this->assertEquals([$other->id], $view->getData()['rows']->pluck('branch.id')->all());
    }

    /** @test */
    public function an_invalid_branch_filter_falls_back_to_the_full_authorized_report(): void
    {
        $first = $this->makeBranch();
        $second = $this->makeBranch();
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');

        $view = $this->report($admin, ['branch_id' => Branch::query()->max('id') + 5000]);
        $rowIds = $view->getData()['rows']->pluck('branch.id');

        $this->assertNull($view->getData()['selectedBranchId']);
        $this->assertContains($first->id, $rowIds);
        $this->assertContains($second->id, $rowIds);
    }
}
