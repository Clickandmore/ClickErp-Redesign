<?php

namespace Tests\Unit;

use App\Http\Requests\StoreRentalContractRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRentalContractRequestTest extends TestCase
{
    public function test_monthly_salary_is_required_and_must_be_greater_than_zero(): void
    {
        $request = StoreRentalContractRequest::create('/admin/RentContract', 'POST');
        $salaryRules = $request->rules()['monthly_salary'];

        $missingSalary = Validator::make([], ['monthly_salary' => $salaryRules], $request->messages());
        $zeroSalary = Validator::make(['monthly_salary' => 0], ['monthly_salary' => $salaryRules], $request->messages());
        $validSalary = Validator::make(['monthly_salary' => 1000], ['monthly_salary' => $salaryRules], $request->messages());

        $this->assertTrue($missingSalary->fails());
        $this->assertSame(
            'يجب تحديد الراتب الشهري لأنه معفى من الضريبة',
            $missingSalary->errors()->first('monthly_salary')
        );
        $this->assertTrue($zeroSalary->fails());
        $this->assertSame(
            'يجب أن يكون الراتب الشهري أكبر من صفر حتى لا تُحتسب الضريبة على كامل قيمة العقد',
            $zeroSalary->errors()->first('monthly_salary')
        );
        $this->assertFalse($validSalary->fails());
    }
}
