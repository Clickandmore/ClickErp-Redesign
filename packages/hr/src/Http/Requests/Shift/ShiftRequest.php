<?php

namespace Nami\Hr\Http\Requests\Shift;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ShiftRequest extends MainRequest
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
            'name' => 'required|unique:hr_shifts,name',
            'from_hour' => 'required|date_format:H:i|before:to_hour',
            'to_hour' => 'required|date_format:H:i|after:from_hour',
            'month_days' => 'required|numeric|min:1|max:30',
            'absence_sanctions_id' => 'required|exists:hr_sanctions,id',
            'days' => 'required',
            'minutes' => 'required',
            'sanctions_id' => 'required|exists:hr_sanctions,id',
        ];
    }

    public function messages()
    {
        return [
            // Name field
            'name.required' => $this->isArabic() ? 'مسمى الشفت مطلوب.' : 'The Shift title field is required.',
            'name.unique' => $this->isArabic() ? 'مسمى الشفت مستخدم من قبل، يرجى اختيار مسمى آخر.' : 'The Shift title has already been taken.',

            // From hour field
            'from_hour.required' => $this->isArabic() ? 'وقت البداية مطلوب.' : 'The start time is required.',
            'from_hour.date_format' => $this->isArabic() ? 'يجب أن يكون وقت البداية بالتنسيق HH:MM.' : 'The start time must be in the format HH:MM.',
            'from_hour.before' => $this->isArabic() ? 'يجب أن يكون وقت البداية قبل وقت النهاية.' : 'The start time must be before the end time.',

            // To hour field
            'to_hour.required' => $this->isArabic() ? 'وقت النهاية مطلوب.' : 'The end time is required.',
            'to_hour.date_format' => $this->isArabic() ? 'يجب أن يكون وقت النهاية بالتنسيق HH:MM.' : 'The end time must be in the format HH:MM.',
            'to_hour.after' => $this->isArabic() ? 'يجب أن يكون وقت النهاية بعد وقت البداية.' : 'The end time must be after the start time.',

            // Month days field
            'month_days.required' => $this->isArabic()
                ? 'حقل عدد الأيام في الشهر مطلوب.'
                : 'The month days field is required.',
            'month_days.numeric' => $this->isArabic()
                ? 'حقل عدد الأيام في الشهر يجب أن يكون عدداً.'
                : 'The month days field must be a number.',
            'month_days.max' => $this->isArabic()
                ? 'عدد الأيام في الشهر لا يمكن أن يتجاوز 30.'
                : 'The month days cannot exceed 30.',

            // Absence sanctions ID field
            'absence_sanctions_id.required' => $this->isArabic() ? 'معرف عقوبات الغياب مطلوب.' : 'The absence sanctions ID is required.',
            'absence_sanctions_id.exists' => $this->isArabic() ? 'العقوبة المحددة غير موجودة.' : 'The selected sanctions ID does not exist.',

            // Days field
            'days.required' => $this->isArabic() ? 'عدد الأيام مطلوب.' : 'The number of days is required.',

            // Minutes field
            'minutes.required' => $this->isArabic() ? 'عدد الدقائق مطلوب.' : 'The number of minutes is required.',

            // Sanctions ID field
            'sanctions_id.required' => $this->isArabic() ? 'معرف العقوبات مطلوب.' : 'The sanctions ID is required.',
            'sanctions_id.exists' => $this->isArabic() ? 'العقوبة المحددة غير موجودة.' : 'The selected sanctions ID does not exist.',
        ];
    }

    private function isArabic()
    {
        return app()->getLocale() === 'ar';
    }
}
