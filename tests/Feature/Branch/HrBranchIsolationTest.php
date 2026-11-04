<?php

namespace Tests\Feature\Branch;

use App\Support\BranchQueryContext;
use App\Models\Branch;
use App\Models\ingaz\Maid;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\HrTask;
use Nami\Hr\Models\Payroll;
use Nami\Hr\Services\EmployeeService;
use Nami\Hr\Services\TaskService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class HrBranchIsolationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule(true);
    }

    /** @test */
    public function employee_category_is_the_canonical_listing_type_even_when_source_maid_is_retained(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $maid = Maid::query()->create([
            'name' => 'عاملة محولة لموظفة',
            'passport_num' => 'HR-' . uniqid(),
            'age' => 30,
            'status' => 'available',
            'available' => true,
            'branch_id' => $branch->id,
        ]);
        $row = $this->makeEmployee($branch, Employee::CATEGORY_EMPLOYEE, $maid->id);

        $this->assertTrue(Employee::query()->onlyEmployees()->whereKey($row)->exists());
        $this->assertFalse(Employee::query()->onlyMaids()->whereKey($row)->exists());
    }

    /** @test */
    public function employee_and_employed_maid_listings_are_separate_and_respect_filters(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $otherBranch = $this->makeBranch();
        $maid = Maid::query()->create([
            'name' => 'Listing test maid',
            'passport_num' => 'HR-' . uniqid(),
            'age' => 30,
            'status' => 'available',
            'available' => true,
            'branch_id' => $branch->id,
        ]);
        $employeeId = $this->makeEmployee($branch);
        $employedMaidId = $this->makeEmployee($branch, Employee::CATEGORY_EMPLOYEE, $maid->id);
        $this->makeEmployee($otherBranch);
        $service = app(EmployeeService::class);
        $request = Request::create('/hr/employees', 'GET', ['branch_id' => $branch->id]);

        $this->assertSame([$employeeId], $service->GetFilterEmployees($request)->pluck('id')->all());
        $this->assertSame([$employedMaidId], $service->GetFilterEmployees($request, Employee::CATEGORY_EMPLOYEE, true)->pluck('id')->all());

        $departmentId = DB::table('hr_employee')->where('id', $employeeId)->value('deparment_id');
        $filtered = Request::create('/hr/employed-maids', 'GET', ['department_id' => $departmentId]);
        $this->assertCount(0, $service->GetFilterEmployees($filtered, Employee::CATEGORY_EMPLOYEE, true));

        DB::table('hr_employee')->where('id', $employedMaidId)->update(['employee_category' => Employee::CATEGORY_MAID]);
        $this->assertCount(0, $service->GetFilterEmployees($request, Employee::CATEGORY_EMPLOYEE, true));
    }

    /** @test */
    public function employee_filters_support_inactive_status_nationality_dates_and_contract_boundaries(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $inactiveId = $this->makeEmployee($branch);
        $activeId = $this->makeEmployee($branch);
        $laterId = $this->makeEmployee($branch);
        DB::table('hr_employee')->where('id', $inactiveId)->update([
            'status' => 0, 'hired_date' => '2026-02-15',
            'contract_end_date' => today()->addDays(30)->toDateString(),
        ]);
        DB::table('hr_employee')->where('id', $activeId)->update([
            'hired_date' => '2026-03-01', 'contract_end_date' => today()->subDay()->toDateString(),
        ]);
        DB::table('hr_employee')->where('id', $laterId)->update([
            'hired_date' => '2026-04-01', 'contract_end_date' => today()->addDays(31)->toDateString(),
        ]);
        $countryId = DB::table('hr_employee')->where('id', $inactiveId)->value('country_id');
        $ids = function (array $filters) use ($branch) {
            return app(EmployeeService::class)->GetFilterEmployees(Request::create('/hr/employees', 'GET',
                ['branch_id' => $branch->id] + $filters))->pluck('id')->sort()->values()->all();
        };

        $this->assertSame([$inactiveId], $ids(['status' => '0']));
        $this->assertSame([$activeId, $laterId], $ids(['status' => '1']));
        $this->assertSame([$inactiveId], $ids(['country_id' => $countryId]));
        $this->assertSame([$inactiveId], $ids(['hired_from' => '2026-02-15', 'hired_to' => '2026-02-15']));
        $this->assertSame([$activeId], $ids(['contract_status' => 'expired']));
        $this->assertSame([$inactiveId], $ids(['contract_status' => 'expiring']));
        $this->assertSame([$inactiveId, $laterId], $ids(['contract_status' => 'valid']));
        $this->assertSame([$inactiveId], $ids([
            'status' => '0', 'country_id' => $countryId, 'hired_from' => '2026-02-15',
            'hired_to' => '2026-02-15', 'contract_status' => 'expiring',
        ]));
        DB::table('hr_employee')->where('id', $inactiveId)->update(['contract_end_date' => today()->toDateString()]);
        $this->assertSame([$inactiveId], $ids(['contract_status' => 'expiring']));
        $this->assertSame([$activeId], $ids(['contract_status' => 'expired']));
    }

    /** @test */
    public function employee_name_search_matches_partial_names_and_combines_with_other_filters(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $first = $this->makeEmployee($branch);
        $second = $this->makeEmployee($branch);
        DB::table('hr_employee')->where('id', $first)->update(['name' => 'أحمد محمد', 'status' => 0]);
        DB::table('hr_employee')->where('id', $second)->update(['name' => 'محمد علي', 'status' => 1]);
        $service = app(EmployeeService::class);
        $request = Request::create('/hr/employees', 'GET', ['employee_name' => '  محمد  ', 'branch_id' => $branch->id]);
        $this->assertEqualsCanonicalizing([$first, $second], $service->GetFilterEmployees($request)->pluck('id')->all());
        $request->merge(['status' => '0']);
        $this->assertSame([$first], $service->GetFilterEmployees($request)->pluck('id')->all());
        $request->merge(['employee_name' => 'لا يوجد']);
        $this->assertCount(0, $service->GetFilterEmployees($request));
    }

    /** @test */
    public function employee_filters_reject_reversed_hiring_dates(): void
    {
        $this->expectException(ValidationException::class);
        app(EmployeeService::class)->GetFilterEmployees(Request::create('/hr/employees', 'GET', [
            'hired_from' => '2026-03-01', 'hired_to' => '2026-02-01',
        ]));
    }

    /** @test */
    public function employed_maids_page_uses_its_own_title_and_data_source(): void
    {
        $request = Request::create('/hr/employed-maids');
        $view = app(\Nami\Hr\Http\Controllers\EmployeeController::class)
            ->employedMaids($request, app(EmployeeService::class));

        $this->assertSame(__('hr::hr.employed_maids'), $view->getData()['bladeTitle']);
        $this->assertSame(route('hr.maids.employees'), $view->getData()['dataTableRoute']);
        $this->assertNull($view->getData()['createRoute']);
        $this->assertStringContainsString(__('hr::hr.employed_maids'), view('admin.layout.inc.hr_sidebar_links')->render());
    }

    /** @test */
    public function employee_filter_uses_hr_employee_branch_as_the_single_source_of_truth(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $mainEmployeeId = $this->makeEmployee($main);
        $otherEmployeeId = $this->makeEmployee($other);

        $request = Request::create('/hr/employees', 'GET', ['branch_id' => $other->id]);
        $visibleIds = app(EmployeeService::class)
            ->GetFilterEmployees($request, Employee::CATEGORY_EMPLOYEE)
            ->pluck('id');

        $this->assertTrue($visibleIds->contains($otherEmployeeId));
        $this->assertFalse($visibleIds->contains($mainEmployeeId));
    }

    /** @test */
    public function payroll_headers_are_isolated_and_the_same_month_is_allowed_per_branch(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $month = '2099-12';

        $first = Payroll::query()->withoutBranchScope()->create($this->payrollData($month, $main->id));
        $second = Payroll::query()->withoutBranchScope()->create($this->payrollData($month, $other->id));

        $admin = $this->branchAdmin($main);
        $this->actingAsAdmin($admin);
        BranchQueryContext::flush();

        $visibleIds = Payroll::query()->pluck('id');

        $this->assertTrue($visibleIds->contains($first->id));
        $this->assertFalse($visibleIds->contains($second->id));
    }

    /** @test */
    public function a_task_cannot_mix_employees_from_different_branches(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $mainEmployeeId = $this->makeEmployee($main);
        $otherEmployeeId = $this->makeEmployee($other);

        $this->expectException(ValidationException::class);

        app(TaskService::class)->store([
            'title' => 'مهمة متعددة الفروع',
            'description' => 'يجب رفضها',
            'employee_ids' => [$mainEmployeeId, $otherEmployeeId],
        ]);
    }

    /** @test */
    public function task_and_assignments_inherit_the_employee_branch(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $employeeId = $this->makeEmployee($branch);

        $task = app(TaskService::class)->store([
            'title' => 'مهمة فرع واحد',
            'description' => 'اختبار التوريث',
            'employee_ids' => [$employeeId],
        ]);

        $this->assertSame($branch->id, (int) $task->branch_id);
        $this->assertDatabaseHas('hr_employee_tasks', [
            'task_id' => $task->id,
            'employee_id' => $employeeId,
            'branch_id' => $branch->id,
        ]);
        $this->assertTrue(HrTask::query()->withoutBranchScope()->whereKey($task->id)->exists());
    }

    private function payrollData(string $month, int $branchId): array
    {
        return [
            'branch_id' => $branchId,
            'month_year' => $month,
            'month_days' => 30,
            'workers_count' => 0,
            'total_gross_salary' => 0,
            'total_deductions' => 0,
            'total_net_salary' => 0,
        ];
    }

    private function makeEmployee(
        Branch $branch,
        string $category = Employee::CATEGORY_EMPLOYEE,
        ?int $maidId = null
    ): int {
        $suffix = str_replace('.', '', uniqid('', true));
        $departmentId = DB::table('hr_department')->insertGetId([
            'title' => 'قسم اختبار ' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobId = DB::table('hr_jobs')->insertGetId([
            'title' => 'وظيفة اختبار ' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        do {
            $countryCode = chr(random_int(65, 90)) . chr(random_int(65, 90));
        } while (DB::table('countries')->where('code', $countryCode)->exists());

        $countryId = DB::table('countries')->insertGetId([
            'name_ar' => 'دولة اختبار',
            'name_en' => 'Test Country',
            'code' => $countryCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('hr_employee')->insertGetId([
            'name' => 'موظف اختبار ' . $suffix,
            'image' => 'default.png',
            'code' => 'EMP-' . $suffix,
            'phone' => '5' . substr($suffix, -8),
            'birthday' => '1990-01-01',
            'email' => $suffix . '@example.test',
            'national_num' => $suffix,
            'address' => 'الرياض',
            'hired_date' => '2026-01-01',
            'contract_end_date' => '2027-01-01',
            'salary' => 1000,
            'net_salary' => 1000,
            'status' => 1,
            'deparment_id' => $departmentId,
            'job_id' => $jobId,
            'country_id' => $countryId,
            'employee_category' => $category,
            'maid_id' => $maidId,
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
