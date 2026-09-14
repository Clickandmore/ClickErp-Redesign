<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HrCompanySponsoredMaidFilterTest extends TestCase
{
    public function test_hr_maid_listing_and_payroll_use_current_company_sponsorship_scope(): void
    {
        $employeeService = file_get_contents(__DIR__ . '/../../packages/hr/src/Services/EmployeeService.php');
        $payrollController = file_get_contents(__DIR__ . '/../../packages/hr/src/Http/Controllers/PayrollController.php');
        $employeeModel = file_get_contents(__DIR__ . '/../../packages/hr/src/Models/Employee.php');
        $employeeListing = file_get_contents(__DIR__ . '/../../packages/hr/resources/views/hr/employees/index.blade.php');
        $payrollCreate = file_get_contents(__DIR__ . '/../../packages/hr/resources/views/hr/payrolls/create.blade.php');

        $this->assertStringContainsString('currentlyCompanySponsoredMaids()', $employeeService);
        $this->assertStringContainsString('currentlyCompanySponsoredMaids()', $payrollController);
        $this->assertStringContainsString("where('status', 'transferred')", $employeeModel);
        $this->assertStringContainsString("orWhereNotNull('transferred_at')", $employeeModel);
        $this->assertStringContainsString("orWhere('status', '!=', 'deported')", $employeeModel);
        $this->assertStringContainsString('من تظهر في هذه الصفحة؟', $employeeListing);
        $this->assertStringContainsString('العاملات المشمولات:', $payrollCreate);
    }
}
