<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ingaz\Order;

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
            'client_name' => 'required_if:client_type,new_client',
            'client_phone' => 'required_if:client_type,new_client',
            'client_nationality_id' => 'required_if:client_type,new_client',
            'client_email' => 'nullable|email',
            'client_nationality_num' => 'required_if:client_type,new_client',
            'client_phone2' => 'required_if:client_type,new_client',
            'client_city' => 'required_if:client_type,new_client',
            'client_beds_num' => 'nullable',
            'client_family_num' => 'nullable',
            'client_children' => 'nullable',
            'client_job' => 'nullable',
            'client_address' => 'required_if:client_type,new_client',
            'client_birthdate' => 'required_if:client_type,new_client',
            'maid_id' => 'nullable',
            'maid_selection_postponed' => 'nullable|boolean',
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
            'client_name.required_if' => 'يرجي ادخال اسم العميل',
            'client_phone.required_if' => 'يرجي ادخال رقم هاتف العميل',

            'client_nationality_id.required_if' => 'يرجي اختيار جنسية العميل',
            'client_nationality_num.required_if' => 'يرجي ادخال رقم هوية العميل',
            'client_phone2.required_if' => 'يرجي ادخال رقم الهاتف الإضافي',
            'client_city.required_if' => 'يرجي ادخال المدينة',
            'client_address.required_if' => 'يرجي ادخال العنوان',
            'client_birthdate.required_if' => 'يرجي ادخال تاريخ الميلاد',
        ];
    }
}
