<?php

namespace Nami\Crm\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends MainRequest
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
        // 966 50 80 91 74 0
        return [
            'name' => 'required',
            'phone' => 'required',
            'whatsapp' => ['required', 'regex:/^((9665\d{8})|(20[1,2,0]\d{8}))$/'],
            'notes' => 'nullable',
            'code' => 'nullable',
            'email' => 'required|email|unique:crm_clients,email,'.$this->client,
            'status' => 'nullable',
            'address' => 'nullable',
            'vat_number' => 'nullable|unique:crm_clients,vat_number,'.$this->client,
            'social_contact_id' => 'nullable|exists:crm_social_contacts,id',
            'city_id' => 'nullable|exists:crm_areas,id',
            'area_id' => 'nullable|exists:crm_areas,id',
            'admin_id' => 'nullable|exists:admins,id',
            'attachments.*.attachment_id' => 'nullable',
            'attachments.*.title' => 'nullable',
            'attachments.*.file' => 'required_if:attachment.*.attachment_id,!=,null|file',
        ];
    }

    public function messages()
    {
        return [
            'whatsapp.regex' => __("crm.The phone number is invalid. It must be a Saudi number starting with 9665 or an Egyptian number starting with 20."),
        ];
    }
}
