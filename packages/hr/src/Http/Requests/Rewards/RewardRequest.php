<?php

namespace Nami\Hr\Http\Requests\Rewards;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class RewardRequest extends MainRequest
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
            'title' => 'required|unique:hr_rewards,title',
            'type' => 'required',
            'value' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => $this->isArabic() ? ' مسمى المكافأة مطلوب.' : 'The Rewards title field is required.',
            'title.unique' => $this->isArabic() ? 'مسمى المكافأة مستخدم من قبل، يرجى اختيار مسمى آخر.' : 'The Rewards title has already been taken.',
            'type.required' => $this->isArabic() ? ' طريقة الحساب مطلوب.' : 'The Rewards type is required.',
            'value.required' => $this->isArabic() ? 'قيمة مطلوبة.' : 'The Rewards value is required.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
