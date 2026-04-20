<?php

namespace App\Http\Requests\ingaz\API;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class RenewContractRequest extends MainRequest
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
            'client_name' => ['required',],
            'client_phone' => ['required'],
            'client_address' => ['required',],
            'city' => ['required'],
            'worker_name' => ['required'],
            'worker_phone' => ['required'],
            'worker_address' => ['required'],
            'passport_number' => ['required'],
            'name_of_relative' => ['required'],
            'address_of_relative' => ['required'],
            'phone_of_relative' => ['required'],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'worker_new_salary' => ['required'],
            'worker_old_salary' => ['required'],
            'worker_brought_from' => ['required'],
            'worker_marital_status' => ['required'],
            'sponsor_card' => ['required'],
            'worker_residence' => ['required'],
            'first_page_of_the_worker_passport' => ['required'],
            'travel_tickets' => ['required'],
            'visa_printed_in_the_worker_passport' => ['required'],
            'exit_and_re_entry_visa' => ['required'],
            'customer_national_address' => ['required'],
            'worker_information' => ['required'],
            'image_of_bank_transfer' => ['required'],
            'g-recaptcha-response' => 'required',

        ];
    }
}
