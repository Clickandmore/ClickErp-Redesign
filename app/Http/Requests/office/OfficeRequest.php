<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

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
        $rules = [
            "title:ar" => "required|string",
            "title:en" => "required|string",
            "phone_office" => "required|numeric",
            "license_number" => "required|numeric",
            "nationality_id" => "required|exists:nationalities,id",
            "street" => "required|string",
            "manager_name" => "required|string",
            "bank_address" => "required|string",
            "bank_account" => "required|string",
            "bank_account_name" => "required|string",
            "bank_swift_code" => "required|numeric",
            "bank_iban" => "required|numeric",
            "cost_of_dollar" => "required|numeric",
            "cost_of_other" => "required|numeric",
            "job_title_id" => "required|array",
            "job_title_id.*" => "required|exists:job_types,id",
        ];

        if ($this->isMethod('post')) {
            $rules["manager_email"] = [
                "required",
                "email",
                function ($attribute, $value, $fail) {
                    $existsInAdmins = DB::table('admins')->where('email', $value)->exists();
                    $existsInOffices = DB::table('recruitment_offices')->where('manager_email', $value)->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_email_unique'));
                    }
                },
            ];

            $rules["manager_phone"] = [
                "required",
                "numeric",
                function ($attribute, $value, $fail) {
                    $existsInAdmins = DB::table('admins')->where('phone', $value)->exists();
                    $existsInOffices = DB::table('recruitment_offices')->where('manager_phone', $value)->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_phone_unique'));
                    }
                },
            ];

            $rules["password"] = "required|string|min:6";
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $officeId = $this->route('office');
            $rules["manager_email"] = [
                "nullable",
                "email",
                function ($attribute, $value, $fail) use ($officeId) {
                    $admin = DB::table('admins')->where('email', $value)->first();
                    $existsInAdmins = DB::table('admins')
                        ->where('email', $value)
                        ->when($admin, fn($q) => $q->where('id', '<>', $admin->id))
                        ->exists();
                    $existsInOffices = DB::table('recruitment_offices')
                        ->where('manager_email', $value)
                        ->where('id', '<>', $officeId)
                        ->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_email_unique'));
                    }
                },
            ];
            $rules["manager_phone"] = [
                "nullable",
                "numeric",
                function ($attribute, $value, $fail) use ($officeId) {

                    $admin = DB::table('admins')->where('phone', $value)->first();

                    $existsInAdmins = DB::table('admins')
                        ->where('phone', $value)
                        ->when($admin, fn($q) => $q->where('id', '<>', $admin->id))
                        ->exists();

                    $existsInOffices = DB::table('recruitment_offices')
                        ->where('manager_phone', $value)
                        ->where('id', '<>', $officeId)
                        ->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_phone_unique'));
                    }
                },
            ];
            $rules["password"] = "nullable|string|min:6";
        }

        return $rules;
    }

    public function messages()
    {
        return [
            "title:ar.required" => __("ingaz.title_ar_required"),
            "title:ar.string" => __("ingaz.title_ar_string"),

            "title:en.required" => __("ingaz.title_en_required"),
            "title:en.string" => __("ingaz.title_en_string"),

            "phone_office.required" => __("ingaz.phone_office_required"),
            "phone_office.numeric" => __("ingaz.phone_office_numeric"),

            "license_number.required" => __("ingaz.license_number_required"),
            "license_number.numeric" => __("ingaz.license_number_numeric"),

            "nationality_id.required" => __("ingaz.nationality_id_required"),
            "nationality_id.exists" => __("ingaz.nationality_id_exists"),

            "street.required" => __("ingaz.street_required"),
            "street.string" => __("ingaz.street_string"),

            "manager_name.required" => __("ingaz.manager_name_required"),
            "manager_name.string" => __("ingaz.manager_name_string"),

            "manager_email.required" => __("ingaz.manager_email_required"),
            "manager_email.email" => __("ingaz.manager_email_email"),
            "manager_email.unique" => __("ingaz.manager_email_unique"),

            "manager_phone.required" => __("ingaz.manager_phone_required"),
            "manager_phone.numeric" => __("ingaz.manager_phone_numeric"),
            "manager_phone.unique" => __("ingaz.manager_phone_unique"),

            "password.required" => __("ingaz.password_required"),
            "password.string" => __("ingaz.password_string"),
            "password.min" => __("ingaz.password_min"),

            "bank_address.required" => __("ingaz.bank_address_required"),
            "bank_address.string" => __("ingaz.bank_address_string"),

            "bank_account.required" => __("ingaz.bank_account_required"),
            "bank_account.string" => __("ingaz.bank_account_string"),

            "bank_account_name.required" => __("ingaz.bank_account_name_required"),
            "bank_account_name.string" => __("ingaz.bank_account_name_string"),

            "bank_swift_code.required" => __("ingaz.bank_swift_code_required"),
            "bank_swift_code.numeric" => __("ingaz.bank_swift_code_numeric"),

            "bank_iban.required" => __("ingaz.bank_iban_required"),
            "bank_iban.numeric" => __("ingaz.bank_iban_numeric"),

            "cost_of_dollar.required" => __("ingaz.cost_of_dollar_required"),
            "cost_of_dollar.numeric" => __("ingaz.cost_of_dollar_numeric"),

            "cost_of_other.required" => __("ingaz.cost_of_other_required"),
            "cost_of_other.numeric" => __("ingaz.cost_of_other_numeric"),

            "job_title_id.required" => __("ingaz.job_title_id_required"),
            "job_title_id.array" => __("ingaz.job_title_id_array"),

            "job_title_id.*.required" => __("ingaz.job_title_id_each_required"),
            "job_title_id.*.exists" => __("ingaz.job_title_id_each_exists"),
        ];
    }

}
