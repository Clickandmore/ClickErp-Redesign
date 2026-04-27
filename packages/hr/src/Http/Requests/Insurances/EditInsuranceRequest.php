<?php

namespace Nami\Hr\Http\Requests\Insurances;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditInsuranceRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required', Rule::unique('hr_insurances')->ignore(request()->input('insurances_id')),],
            'type' => 'required',
            'value' => 'required',
            'emp_value' => 'required',
            'company_value' => 'required',
        ];
    }

    public function messages()
    {
        return [
            // Arabic messages
            'title.required' => $this->isArabic() ? ' مسمى الشريحة مطلوب.' : 'The insurance title field is required.',
            'title.unique' => $this->isArabic() ? 'مسمى الشريحة مستخدم من قبل، يرجى اختيار مسمى آخر.' : 'The insurance title has already been taken.',
            'type.required' => $this->isArabic() ? 'نوع التأمين مطلوب.' : 'The insurance type is required.',
            'value.required' => $this->isArabic() ? 'قيمة التأمين مطلوبة.' : 'The insurance value is required.',
            'emp_value.required' => $this->isArabic() ? 'قيمة مساهمة الموظف مطلوبة.' : 'The employee contribution value is required.',
            'company_value.required' => $this->isArabic() ? 'قيمة مساهمة الشركة مطلوبة.' : 'The company contribution value is required.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
