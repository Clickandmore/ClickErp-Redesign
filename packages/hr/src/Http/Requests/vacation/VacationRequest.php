<?php

namespace Nami\Hr\Http\Requests\vacation;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class VacationRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|unique:hr_vacations,title',
            'number_of_days' => 'required|numeric|min:1|max:365',
            'with_salary' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => $this->isArabic()
                ? 'مسمى الاجازة مطلوب.'
                : 'The Vacation title field is required.',
            'title.unique' => $this->isArabic()
                ? 'مسمى الاجازة مستخدم من قبل، يرجى اختيار مسمى آخر.'
                : 'The Vacation title has already been taken.',
            'number_of_days.required' => $this->isArabic()
                ? 'عدد الأيام في الإجازة مطلوب.'
                : 'The number of days field is required.',
            'number_of_days.numeric' => $this->isArabic()
                ? 'عدد الأيام يجب أن يكون عدد صحيح.'
                : 'The number of days must be a number.',
            'number_of_days.max' => $this->isArabic()
                ? 'عدد الأيام في السنة لا يمكن أن يتجاوز 365.'
                : 'The number of days cannot exceed 365.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
