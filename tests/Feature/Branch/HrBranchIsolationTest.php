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
        $countryId = DB::table('countries')->insertGetId([
            'name_ar' => 'دولة اختبار',
            'name_en' => 'Test Country',
            'code' => substr(strtoupper($suffix), -2),
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
