<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalContractRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // معلومات العميل الأساسية
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_nationality_id' => 'required|exists:nationalities,id',
            'client_email' => 'nullable|email|max:255',
            'client_national_num' => 'required|string|max:20',
            'client_birthdate' => 'required|date',
            'client_address' => 'required|string|max:500',
            'client_city' => 'required|string|max:100',
            'family_num' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',

            // معلومات العميل الخاصة بالتأجير
            'profession' => 'required|string|max:255',
            'monthly_income' => 'required|numeric|min:0',

            // معلومات العاملة
            'maid_id' => 'required|exists:maids,id',

            // معلومات المسوق
            'marketer_id' => 'required|exists:admins,id',

            // معلومات العقد
            'contract_date' => 'required|date',
            'working_hours_per_day' => 'required|integer|min:1|max:24',
            'hourly_rate' => 'required|numeric|min:0',
            'monthly_salary' => 'required|numeric|min:0',
            'sponsorship_transfer_amount' => 'required|numeric|min:0',
            'salary_after_sponsorship' => 'required|numeric|min:0',
            'monthly_cost' => 'required|numeric|min:0',
            'contract_duration_months' => 'required|integer|min:1',
            'contract_duration_days' => 'required|integer|min:1',
            'ingaz_from' => 'required|date|after_or_equal:contract_date',

            // معلومات إضافية
            'notes' => 'nullable|string|max:1000',
            'additional_terms' => 'nullable|array',

            // معلومات الطلب (إذا كان موجود)
            'order_id' => 'nullable|exists:orders,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            // رسائل معلومات العميل
            'client_name.required' => 'اسم العميل مطلوب',
            'client_phone.required' => 'رقم جوال العميل مطلوب',
            'client_nationality_id.required' => 'جنسية العميل مطلوبة',
            'client_nationality_id.exists' => 'الجنسية المختارة غير صحيحة',
            'client_email.email' => 'البريد الإلكتروني غير صحيح',
            'client_national_num.required' => 'رقم الهوية مطلوب',
            'client_birthdate.required' => 'تاريخ الميلاد مطلوب',
            'client_address.required' => 'العنوان مطلوب',
            'client_city.required' => 'المدينة مطلوبة',

            // رسائل معلومات العميل الخاصة بالتأجير
            'profession.required' => 'المهنة مطلوبة',
            'monthly_income.required' => 'الدخل الشهري مطلوب',
            'monthly_income.numeric' => 'الدخل الشهري يجب أن يكون رقماً',
            'monthly_income.min' => 'الدخل الشهري يجب أن يكون أكبر من أو يساوي صفر',

            // رسائل معلومات العاملة
            'maid_id.required' => 'يرجى اختيار العاملة',
            'maid_id.exists' => 'العاملة المختارة غير صحيحة',

            // رسائل معلومات المسوق
            'marketer_id.required' => 'يرجى اختيار المسوق',
            'marketer_id.exists' => 'المسوق المختار غير صحيح',

            // رسائل معلومات العقد
            'contract_date.required' => 'تاريخ العقد مطلوب',
            'working_hours_per_day.required' => 'عدد ساعات العمل يومياً مطلوب',
            'working_hours_per_day.integer' => 'عدد ساعات العمل يجب أن يكون رقماً صحيحاً',
            'working_hours_per_day.min' => 'عدد ساعات العمل يجب أن يكون على الأقل ساعة واحدة',
            'working_hours_per_day.max' => 'عدد ساعات العمل لا يمكن أن يتجاوز 24 ساعة',
            'hourly_rate.required' => 'أجرة الساعة مطلوبة',
            'hourly_rate.numeric' => 'أجرة الساعة يجب أن تكون رقماً',
            'monthly_salary.required' => 'الراتب الشهري مطلوب',
            'monthly_salary.numeric' => 'الراتب الشهري يجب أن يكون رقماً',
            'sponsorship_transfer_amount.required' => 'مبلغ نقل الكفالة مطلوب',
            'sponsorship_transfer_amount.numeric' => 'مبلغ نقل الكفالة يجب أن يكون رقماً',
            'salary_after_sponsorship.required' => 'الراتب بعد نقل الكفالة مطلوب',
            'salary_after_sponsorship.numeric' => 'الراتب بعد نقل الكفالة يجب أن يكون رقماً',
            'monthly_cost.required' => 'التكلفة الشهرية مطلوبة',
            'monthly_cost.numeric' => 'التكلفة الشهرية يجب أن تكون رقماً',
            'contract_duration_months.required' => 'مدة العقد بالشهور مطلوبة',
            'contract_duration_months.integer' => 'مدة العقد بالشهور يجب أن تكون رقماً صحيحاً',
            'contract_duration_months.min' => 'مدة العقد يجب أن تكون شهر واحد على الأقل',
            'contract_duration_days.required' => 'مدة العقد بالأيام مطلوبة',
            'contract_duration_days.integer' => 'مدة العقد بالأيام يجب أن تكون رقماً صحيحاً',
            'contract_duration_days.min' => 'مدة العقد يجب أن تكون يوم واحد على الأقل',
            'ingaz_from.required' => 'تاريخ بداية الإنجاز مطلوب',
            'ingaz_from.after_or_equal' => 'تاريخ بداية الإنجاز يجب أن يكون بعد أو يساوي تاريخ العقد',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // التحقق من أن العاملة متاحة للتأجير
            if ($this->maid_id) {
                $maid = \App\Models\ingaz\Maid::find($this->maid_id);
                if ($maid && !$maid->available) {
                    $validator->errors()->add('maid_id', 'العاملة المختارة غير متاحة حالياً');
                }

                // التحقق من أن العاملة من نوع تأجير
                if ($maid && !$maid->jobTypes()->where('job_type_id', 2)->exists()) {
                    $validator->errors()->add('maid_id', 'العاملة المختارة ليست من نوع تأجير');
                }
            }

            // حساب قيمة العقد والتحقق من صحتها
            if ($this->monthly_cost && $this->contract_duration_months) {
                $calculatedValue = $this->monthly_cost * $this->contract_duration_months;
                // يمكن إضافة المزيد من التحققات هنا
            }
        });
    }
}
