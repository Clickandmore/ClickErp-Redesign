<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;

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


    public function messages(): array
    {
        return [
            'client_name.required'               => __('ingaz.client_name_required'),
            'client_name.string'                 => __('ingaz.client_name_string'),

            'client_phone.required'              => __('ingaz.client_phone_required'),
            'client_phone.string'                => __('ingaz.client_phone_string'),

            'client_address.required'            => __('ingaz.client_address_required'),
            'client_address.string'              => __('ingaz.client_address_string'),

            'city.required'                      => __('ingaz.city_required'),
            'city.exists'                        => __('ingaz.city_exists'),

            'worker_name.required'               => __('ingaz.worker_name_required'),
            'worker_name.string'                 => __('ingaz.worker_name_string'),

            'worker_phone.required'              => __('ingaz.worker_phone_required'),
            'worker_phone.string'                => __('ingaz.worker_phone_string'),

            'worker_address.required'            => __('ingaz.worker_address_required'),
            'worker_address.string'              => __('ingaz.worker_address_string'),

            'passport_number.required'           => __('ingaz.passport_number_required'),
            'passport_number.string'             => __('ingaz.passport_number_string'),

            'name_of_relative.required'          => __('ingaz.name_of_relative_required'),
            'name_of_relative.string'            => __('ingaz.name_of_relative_string'),

            'address_of_relative.required'       => __('ingaz.address_of_relative_required'),
            'address_of_relative.string'         => __('ingaz.address_of_relative_string'),

            'phone_of_relative.required'         => __('ingaz.phone_of_relative_required'),
            'phone_of_relative.string'           => __('ingaz.phone_of_relative_string'),

            'nationality_id.required'            => __('ingaz.nationality_id_required'),
            'nationality_id.exists'              => __('ingaz.nationality_id_exists'),

            'worker_new_salary.required'         => __('ingaz.worker_new_salary_required'),
            'worker_new_salary.string'           => __('ingaz.worker_new_salary_string'),

            'worker_old_salary.required'         => __('ingaz.worker_old_salary_required'),
            'worker_old_salary.string'           => __('ingaz.worker_old_salary_string'),

            'worker_brought_from.required'       => __('ingaz.worker_brought_from_required'),
            'worker_brought_from.string'         => __('ingaz.worker_brought_from_string'),

            'worker_marital_status.required'    => __('ingaz.worker_marital_status_required'),
            'worker_marital_status.string'      => __('ingaz.worker_marital_status_string'),

            'sponsor_card.required'              => __('ingaz.sponsor_card_required'),
            'sponsor_card.string'                => __('ingaz.sponsor_card_string'),

            'worker_residence.required'          => __('ingaz.worker_residence_required'),
            'worker_residence.string'            => __('ingaz.worker_residence_string'),

            'first_page_of_the_worker_passport.required' => __('ingaz.first_page_of_the_worker_passport_required'),
            'first_page_of_the_worker_passport.string'   => __('ingaz.first_page_of_the_worker_passport_string'),

            'travel_tickets.required'            => __('ingaz.travel_tickets_required'),
            'travel_tickets.string'              => __('ingaz.travel_tickets_string'),

            'visa_printed_in_the_worker_passport.required' => __('ingaz.visa_printed_in_the_worker_passport_required'),
            'visa_printed_in_the_worker_passport.string'   => __('ingaz.visa_printed_in_the_worker_passport_string'),

            'exit_and_re_entry_visa.required'    => __('ingaz.exit_and_re_entry_visa_required'),
            'exit_and_re_entry_visa.string'      => __('ingaz.exit_and_re_entry_visa_string'),

            'customer_national_address.required' => __('ingaz.customer_national_address_required'),
            'customer_national_address.string'   => __('ingaz.customer_national_address_string'),

            'worker_information.required'        => __('ingaz.worker_information_required'),
            'worker_information.string'          => __('ingaz.worker_information_string'),

            'image_of_bank_transfer.required'    => __('ingaz.image_of_bank_transfer_required'),
            'image_of_bank_transfer.string'      => __('ingaz.image_of_bank_transfer_string'),
        ];
    }
}
