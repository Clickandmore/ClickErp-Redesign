<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreContractRequest extends MainRequest
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
        $currentAdmin = Auth::guard('admin')->user();
        $canSelectAnyMarketer = $currentAdmin && (
            $currentAdmin->admin_type === 'super_admin'
            || $currentAdmin->hasAnyRole(['super_admin', 'admin', 'Administrator'])
        );

        $marketerRules = ['required', 'exists:admins,id'];
        if ($currentAdmin && !$canSelectAnyMarketer) {
            $marketerRules[] = Rule::in([$currentAdmin->id]);
        }

        return [
            'contract_date' => 'required',
            'msaned_number' => 'required',
            'tafwed_number' => 'nullable',
            'visa_number' => 'required',
            'visa_milad_date' => 'nullable',
            'visa_hijry_date' => 'nullable',
            'contract_duration' => 'required',
            'order_id' => 'required_if:client_type,ex_client',
            'client_type' => 'required|in:new_client,ex_client',
            'client_id' => 'nullable|exists:users,id',
            'client_name' => 'required_if:client_type,new_client',
            'client_phone' => 'required_if:client_type,new_client',
            'client_nationality_id' => 'required_if:client_type,new_client',
            'client_email' => 'nullable|email',
            'new_client_national_num' => 'nullable',
            'client_phone2' => 'nullable',
            'client_city' => 'nullable',
            'client_beds_num' => 'nullable',
            'client_family_num' => 'nullable',
            'client_children' => 'nullable',
            'client_job' => 'nullable',
            'client_address' => 'nullable',
            'client_birthdate' => 'nullable',
            'new_client_email' => 'nullable|email',
            'new_client_phone2' => 'nullable',
            'new_client_address' => 'nullable',
            'new_client_city' => 'nullable',
            'new_client_beds_num' => 'nullable',
            'new_client_family_num' => 'nullable',
            'new_client_children' => 'nullable',
            'new_client_job' => 'nullable',
            'new_client_birthdate' => 'nullable',
            'maid_id' => 'nullable',
            'maid_selection_mode' => 'nullable|in:existing,manual,postponed',
            'maid_selection_postponed' => 'nullable|boolean',
            'manual_maid_name' => 'required_if:maid_selection_mode,manual|nullable|string|max:255',
            'manual_maid_passport_num' => 'required_if:maid_selection_mode,manual|nullable|string|max:100',
            'manual_maid_nationality_id' => 'required_if:maid_selection_mode,manual|nullable|exists:nationalities,id',
            'manual_maid_office_id' => 'required_if:maid_selection_mode,manual|nullable|exists:recruitment_offices,id',
            'manual_maid_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            'marketer_id' => $marketerRules,
            'required_maid_nationality' => 'nullable|exists:nationalities,id',
            'contract_note' => 'nullable|string|max:2000',
        ];
    }

    public function messages(){
        return [
            'contract_date.required' => 'يرجي ادخال تاريخ العقد',
            'msaned_number.required' => 'يرجي ادخال رقم مساند',
            'tafwed_number.required' => 'يرجي ادخال رقم التفويض',
            'visa_number.required' => 'يرجي ادخال رقم التأشيرة',
            'contract_duration.required' => 'يرجي ادخال مدة العقد',
            'order_id.required_if' => 'يرجي اختيار الطلب',
            'client_type.required' => 'يرجي اختيار نوع العميل',
            'client_type.in' => 'نوع العميل غير صحيح',
            'client_id.exists' => 'العميل المختار غير موجود',
            'client_name.required_if' => 'يرجي ادخال اسم العميل',
            'client_phone.required_if' => 'يرجي ادخال رقم هاتف العميل',

            'client_nationality_id.required_if' => 'يرجي اختيار جنسية العميل',
            'new_client_national_num.required' => 'يرجي ادخال رقم هوية العميل',
            'client_phone2.required' => 'يرجي ادخال رقم الهاتف الإضافي',
            'client_city.required' => 'يرجي ادخال المدينة',
            'client_address.required' => 'يرجي ادخال العنوان',
            'client_birthdate.required' => 'يرجي ادخال تاريخ الميلاد',
            'maid_selection_mode.in' => 'طريقة إضافة العاملة غير صحيحة',
            'manual_maid_name.required_if' => 'يرجى إدخال اسم العاملة المختصر',
            'manual_maid_passport_num.required_if' => 'يرجى إدخال رقم جواز العاملة المختصر',
            'manual_maid_nationality_id.required_if' => 'يرجى اختيار جنسية العاملة المختصرة',
            'manual_maid_nationality_id.exists' => 'جنسية العاملة المختصرة غير موجودة',
            'manual_maid_office_id.required_if' => 'يرجى اختيار المكتب الخارجي للعاملة المختصرة',
            'manual_maid_office_id.exists' => 'المكتب الخارجي المختار غير موجود',
            'manual_maid_attachment.file' => 'مرفق العاملة المختصرة غير صالح',
            'manual_maid_attachment.max' => 'حجم مرفق العاملة المختصرة يجب ألا يتجاوز 5 ميجابايت',
            'manual_maid_attachment.mimes' => 'صيغة مرفق العاملة المختصرة غير مدعومة',
            'marketer_id.required' => 'يرجي اختيار المسوق',
            'marketer_id.exists' => 'المسوق المختار غير موجود',
            'required_maid_nationality.exists' => 'الجنسية المختارة غير موجودة',
        ];
    }
}
