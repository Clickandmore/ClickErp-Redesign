<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class OfficeRequest extends MainRequest
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
            "title:ar"        => "required|string|max:255",
            "title:en"        => "required|string|max:255",
            "phone_office"    => "required|numeric|max:20",
            "license_number"  => "required|numeric|max:50",
            "nationality_id"  => "required|exists:nationalities,id",
            "street"          => "required|string|max:255",
            "manager_name"    => "required|string|max:255",
            "manager_phone"   => "required|numeric|max:20",
            "manager_email"   => "required|email",
            "password"        => "required|string|min:6",
            "bank_address"    => "required|string|max:255",
            "bank_account"    => "required|string|max:255",
            "bank_account_name" => "required|string|max:255",
            "bank_swift_code" => "required|numeric|max:50",
            "bank_iban"       => "required|numeric|max:100",
            "cost_of_dollar"  => "required|numeric",
            "cost_of_other"   => "required|numeric",
            "job_title_id"    => "required|array",
            "job_title_id.*"    => "required|exists:job_types,id",

        ];
    }
}
