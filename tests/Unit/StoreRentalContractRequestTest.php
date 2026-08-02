<?php

namespace Tests\Unit;

use App\Http\Requests\StoreRentalContractRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRentalContractRequestTest extends TestCase
{
    public function test_rental_client_fields_match_the_recruitment_client_requirements(): void
    {
        $request = StoreRentalContractRequest::create('/admin/RentContract', 'POST');
        $rules = $request->rules();

        $personValidator = Validator::make(
            ['account_client_type' => 'person'],
            [
                'account_client_type' => $rules['account_client_type'],
                'new_client_birthdate' => $rules['new_client_birthdate'],
                'new_client_phone2' => $rules['new_client_phone2'],
            ],
            $request->messages()
        );

        $this->assertTrue($personValidator->errors()->has('new_client_birthdate'));
        $this->assertTrue($personValidator->errors()->has('new_client_phone2'));

        $companyValidator = Validator::make(
            [
                'account_client_type' => 'company',
                'new_client_phone2' => '0500000000',
            ],
            [
                'account_client_type' => $rules['account_client_type'],
                'new_client_birthdate' => $rules['new_client_birthdate'],
                'new_client_phone2' => $rules['new_client_phone2'],
            ],
            $request->messages()
        );

        $this->assertFalse($companyValidator->errors()->has('new_client_birthdate'));
        $this->assertFalse($companyValidator->errors()->has('new_client_phone2'));
    }

    public function test_rental_create_page_supports_direct_client_entry_like_recruitment(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/admin/ingaz/RentContract/create.blade.php'
        );

        $this->assertStringContainsString('id="use-direct-client-data"', $view);
        $this->assertStringContainsString('عميل جديد من البيانات', $view);
        $this->assertStringContainsString('data-placeholder="ابحث بالاسم أو الجوال أو الهوية"', $view);
        $this->assertStringContainsString("minimumInputLength: 0", $view);
    }

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
