<?php

namespace Nami\Hr\Http\Requests\Tax;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditTaxRequest extends MainRequest
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
            'title' => ['required', Rule::unique('hr_tax')->ignore(request()->input('tax_id')),],
            'value' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => $this->isArabic() ? ' مسمى الشريحة مطلوب.' : 'The Tax title field is required.',
            'title.unique' => $this->isArabic() ? 'مسمى الشريحة مستخدم من قبل، يرجى اختيار مسمى آخر.' : 'The Tax title has already been taken.',
            'value.required' => $this->isArabic() ? 'قيمة التأمين مطلوبة.' : 'The insurance value is required.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
