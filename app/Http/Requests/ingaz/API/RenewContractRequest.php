<?php

namespace App\Http\Requests\ingaz\API;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class RenewContractRequest extends MainRequest
{
    /**
     * مرفقات تجديد العقد تُرفع من نموذج عام بلا تسجيل دخول وتُخزَّن على قرص
     * public المخدوم عبر /storage — لذلك يجب حصرها في صور ومستندات PDF فقط
     * (لا php/html/svg) مع حدّ للحجم.
     */
    public const DOCUMENT_FILE_RULES = ['file', 'mimes:jpg,jpeg,png,webp,gif,bmp,heic,heif,tif,tiff,pdf,doc,docx', 'max:20480'];

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
            'sponsor_card' => ['required', ...self::DOCUMENT_FILE_RULES],
            'worker_residence' => ['required', ...self::DOCUMENT_FILE_RULES],
            'first_page_of_the_worker_passport' => ['required', ...self::DOCUMENT_FILE_RULES],
            'travel_tickets' => ['required', ...self::DOCUMENT_FILE_RULES],
            'visa_printed_in_the_worker_passport' => ['required', ...self::DOCUMENT_FILE_RULES],
            'exit_and_re_entry_visa' => ['required', ...self::DOCUMENT_FILE_RULES],
            'customer_national_address' => ['required', ...self::DOCUMENT_FILE_RULES],
            'worker_information' => ['required', ...self::DOCUMENT_FILE_RULES],
            'image_of_bank_transfer' => ['required', ...self::DOCUMENT_FILE_RULES],
            'g-recaptcha-response' => 'required',

        ];
    }
}
