<?php

namespace Nami\Hr\Http\Requests\Tax;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class TaxRequest extends MainRequest
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
            'title' => 'required|unique:hr_tax,title',
            'value' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => $this->isArabic() ? ' مسمى الشريحة مطلوب.' : 'The Tax title field is required.',
            'title.unique' => $this->isArabic() ? 'مسمى الشريحة مستخدم من قبل، يرجى اختيار مسمى آخر.' : 'The Tax title has already been taken.',
            'value.required' => $this->isArabic() ? 'قيمة الضريبة مطلوبة.' : 'The Tax value is required.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
