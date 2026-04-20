<?php

namespace App\Http\Requests\ingaz\API;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SponsorshipTransferRequest extends MainRequest
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
            'contract_id' => 'required|exists:contracts,id',
            'new_sponsor_name' => 'required|string|max:255',
            'new_sponsor_phone' => 'required|string|max:20',
            'new_sponsor_national_id' => 'required|string|max:20',
            'new_sponsor_address' => 'required|string|max:500',
            'trial_days' => 'required|integer|min:1|max:90',
            'trial_start_date' => 'required|date',
            'contract_value' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'contract_id.required' => 'يجب اختيار العقد',
            'contract_id.exists' => 'العقد المختار غير موجود',
            'new_sponsor_name.required' => 'يجب إدخال اسم الكفيل الجديد',
            'new_sponsor_phone.required' => 'يجب إدخال رقم الجوال',
            'new_sponsor_national_id.required' => 'يجب إدخال رقم الهوية',
            'new_sponsor_address.required' => 'يجب إدخال العنوان',
            'trial_days.required' => 'يجب إدخال عدد أيام العمل التجريبي',
            'trial_days.integer' => 'عدد أيام العمل التجريبي يجب أن يكون رقماً صحيحاً',
            'trial_days.min' => 'عدد أيام العمل التجريبي يجب أن يكون 1 يوم على الأقل',
            'trial_days.max' => 'عدد أيام العمل التجريبي يجب أن يكون 90 يوم كحد أقصى',
            'trial_start_date.required' => 'يجب إدخال تاريخ بداية العمل التجريبي',
            'trial_start_date.date' => 'تاريخ بداية العمل التجريبي غير صحيح',
            'contract_value.required' => 'يجب إدخال قيمة العقد',
            'contract_value.numeric' => 'قيمة العقد يجب أن تكون رقماً',
            'contract_value.min' => 'قيمة العقد يجب أن تكون أكبر من صفر',
            'paid_amount.required' => 'يجب إدخال المبلغ المدفوع',
            'paid_amount.numeric' => 'المبلغ المدفوع يجب أن يكون رقماً',
            'paid_amount.min' => 'المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر',
            'notes.max' => 'الملاحظات يجب أن تكون أقل من 1000 حرف',
        ];
    }
}
