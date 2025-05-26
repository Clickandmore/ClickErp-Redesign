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
            'client_phone' => 'required_if:client_type,new_client|unique:users,phone,' . ($this->input('order_id') ? Order::find($this->input('order_id'))->user_id : 'NULL') . ',id',
            'client_nationality_id' => 'required_if:client_type,new_client',
            'client_email' => 'nullable|email',
            'client_nationality_num' => 'required_if:client_type,new_client',
            'phone2' => 'nullable',
            'city' => 'nullable',
            'beds_num' => 'nullable',
            'family_num' => 'nullable',
            'children' => 'nullable',
            'job' => 'nullable',
            'address' => 'nullable',
            'maid_id' => 'required',
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
            'client_phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'client_nationality_id.required_if' => 'يرجي اختيار جنسية العميل',
            'client_nationality_num.required_if' => 'يرجي ادخال رقم هوية العميل',
            'maid_id.required' => 'يرجي اختيار العاملة',
        ];
    }
}
