<?php

namespace Nami\Hr\Http\Requests\Payroll;

use App\Http\Requests\MainRequest;

class PayrollRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'month_year' => 'required|date_format:Y-m',
            'month_days_display_option' => 'required|in:30,28,31,current',
            'employee_id' => 'required|array|min:1',
            'employee_id.*' => 'required|integer|exists:hr_employee,id',
            'maid_id' => 'nullable|array',
            'selected_rows' => 'nullable|array',
            'employee_name' => 'required|array|min:1',
            'employee_name.*' => 'required|string|max:255',
            'nationality_name' => 'required|array|min:1',
            'nationality_name.*' => 'required|string|max:255',
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'required|numeric|min:0',
            'basic_salary' => 'required|array|min:1',
            'basic_salary.*' => 'required|numeric|min:0',
            'incentive' => 'required|array|min:1',
            'incentive.*' => 'nullable|numeric|min:0',
            'other_additions' => 'required|array|min:1',
            'other_additions.*' => 'nullable|numeric|min:0',
            'advance' => 'required|array|min:1',
            'advance.*' => 'nullable|numeric|min:0',
            'penalty' => 'required|array|min:1',
            'penalty.*' => 'nullable|numeric|min:0',
            'other_deductions' => 'required|array|min:1',
            'other_deductions.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
            'payroll_notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages()
    {
        return [
            'month_year.required' => 'يرجى تحديد الشهر.',
            'month_year.date_format' => 'صيغة الشهر غير صحيحة.',
            'employee_id.required' => 'لا توجد عاملات متاحة لإنشاء المسير.',
            'employee_id.*.exists' => 'تم اختيار عاملة غير صالحة.',
        ];
    }
}
