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
            'new_client_name' => 'required|string|max:255',
            'new_client_phone' => 'required|string|max:20',
            'client_nationality_id' => 'required|exists:countries,id',
            'new_client_email' => 'nullable|email|max:255',
            'new_client_national_num' => 'required|string|max:20',
            'new_client_birthdate' => 'nullable|date',
            'new_client_address' => 'nullable|string|max:500',
            'new_client_city' => 'required|string|max:100',
            'new_client_phone2' => 'nullable|string|max:20',
            'new_client_beds_num' => 'nullable|string|max:10',
            'new_client_family_num' => 'nullable|integer|min:1',
            'new_client_children' => 'nullable|integer|min:0',
            'new_client_job' => 'nullable|string|max:255',

            // معلومات العميل الخاصة بالتأجير
            'monthly_income' => 'nullable|numeric|min:0',

            // معلومات العاملة
            'maid_id' => 'required|exists:maids,id',

            // معلومات المسوق
            'marketer_id' => 'required|exists:admins,id',

            // معلومات العقد
            'contract_date' => 'required|date',
            'working_hours' => 'nullable|integer|min:1|max:24',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'sponsorship_transfer' => 'nullable|string|in:yes,no',
            'monthly_cost' => 'required|numeric|min:0',
            'contract_duration_months' => 'required|integer|min:1',
            'contract_duration_days' => 'nullable|integer|min:1',
            'total_contract_value' => 'nullable|numeric|min:0',
            'ingaz_from' => 'required|date',
            'ingaz_to' => 'nullable|date',
            'rental_type' => 'nullable|string|in:daily,weekly,monthly,yearly',

            // معلومات إضافية
            'notes' => 'nullable|string|max:1000',

            // معلومات الطلب (إذا كان موجود)
            'order_id' => 'nullable|exists:orders,id',
            'new_existing_client_id' => 'nullable|exists:users,id',
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
            'new_client_name.required' => 'اسم العميل مطلوب',
            'new_client_phone.required' => 'رقم جوال العميل مطلوب',
            'client_nationality_id.required' => 'جنسية العميل مطلوبة',
            'client_nationality_id.exists' => 'الجنسية المختارة غير صحيحة',
            'new_client_email.email' => 'البريد الإلكتروني غير صحيح',
            'new_client_national_num.required' => 'رقم الهوية مطلوب',
            'new_client_city.required' => 'المدينة مطلوبة',

            // رسائل معلومات العميل الخاصة بالتأجير
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
            'working_hours.integer' => 'عدد ساعات العمل يجب أن يكون رقماً صحيحاً',
            'working_hours.min' => 'عدد ساعات العمل يجب أن يكون على الأقل ساعة واحدة',
            'working_hours.max' => 'عدد ساعات العمل لا يمكن أن يتجاوز 24 ساعة',
            'hourly_rate.numeric' => 'أجرة الساعة يجب أن تكون رقماً',
            'monthly_salary.numeric' => 'الراتب الشهري يجب أن يكون رقماً',
            'monthly_cost.required' => 'التكلفة الشهرية مطلوبة',
            'monthly_cost.numeric' => 'التكلفة الشهرية يجب أن تكون رقماً',
            'contract_duration_months.required' => 'مدة العقد بالشهور مطلوبة',
            'contract_duration_months.integer' => 'مدة العقد بالشهور يجب أن تكون رقماً صحيحاً',
            'contract_duration_months.min' => 'مدة العقد يجب أن تكون شهر واحد على الأقل',
            'contract_duration_days.integer' => 'مدة العقد بالأيام يجب أن تكون رقماً صحيحاً',
            'contract_duration_days.min' => 'مدة العقد يجب أن تكون يوم واحد على الأقل',
            'ingaz_from.required' => 'تاريخ بداية الإنجاز مطلوب',
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
