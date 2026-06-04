<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class OfficeRequest extends MainRequest
{
    protected function officeId(): mixed
    {
        return $this->route('office');
    }

    protected function linkedAdminId(mixed $officeId): mixed
    {
        $officeId = is_object($officeId) && isset($officeId->id) ? $officeId->id : $officeId;

        return DB::table('admin_offices')
            ->where('office_id', $officeId)
            ->value('admin_id');
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            'GET' => $this->view(),
            default => [],
        };
    }

    protected function store(): array
    {
        return [
            "title:ar" => "required|string",
            "title:en" => "required|string",
            "phone_office" => "nullable|numeric",
            "license_number" => "nullable|numeric",
            "nationality_id" => "required|exists:nationalities,id",
            "street" => "nullable|string",
            "manager_name" => "nullable|string",
            "manager_email" => [
                "nullable",
                function ($attribute, $value, $fail) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail(__('ingaz.manager_email_email'));
                        return;
                    }

                    $existsInAdmins = DB::table('admins')->where('email', $value)->exists();
                    $existsInOffices = DB::table('recruitment_offices')->where('manager_email', $value)->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_email_unique'));
                    }
                },
            ],
            "manager_phone" => [
                "nullable",
                "numeric",
                function ($attribute, $value, $fail) {
                    $existsInAdmins = DB::table('admins')->where('phone', $value)->exists();
                    $existsInOffices = DB::table('recruitment_offices')->where('manager_phone', $value)->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_phone_unique'));
                    }
                },
            ],
            "password" => "nullable|string|min:6",
            "file" => "nullable|file",
            "file_name" => "nullable|string",
            "bank_address" => "nullable|string",
            "bank_account" => "nullable|string",
            "bank_account_name" => "nullable|string",
            "bank_swift_code" => "nullable|string",
            "bank_iban" => "nullable|string",
            "cost_of_dollar" => "nullable|numeric",
            "cost_of_other" => "nullable|numeric",
            "job_title_id" => "nullable|array",
            "job_title_id.*" => "nullable|exists:job_types,id",
        ];
    }


    protected function update(): array
    {
        $officeId = $this->officeId();
        $linkedAdminId = $this->linkedAdminId($officeId);

        return [
            "title:ar" => "required|string",
            "title:en" => "required|string",
            "phone_office" => "nullable|numeric",
            "license_number" => "nullable|numeric",
            "nationality_id" => "required|exists:nationalities,id",
            "street" => "nullable|string",
            "manager_name" => "nullable|string",
            "manager_email" => [
                "nullable",
                function ($attribute, $value, $fail) use ($officeId, $linkedAdminId) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail(__('ingaz.manager_email_email'));
                        return;
                    }

                    $existsInAdmins = DB::table('admins')
                        ->where('email', $value)
                        ->when($linkedAdminId, fn($q) => $q->where('id', '<>', $linkedAdminId))
                        ->exists();
                    $existsInOffices = DB::table('recruitment_offices')
                        ->where('manager_email', $value)
                        ->where('id', '<>', $officeId)
                        ->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_email_unique'));
                    }
                },
            ],
            "manager_phone" => [
                "nullable",
                "numeric",
                function ($attribute, $value, $fail) use ($officeId, $linkedAdminId) {
                    $existsInAdmins = DB::table('admins')
                        ->where('phone', $value)
                        ->when($linkedAdminId, fn($q) => $q->where('id', '<>', $linkedAdminId))
                        ->exists();

                    $existsInOffices = DB::table('recruitment_offices')
                        ->where('manager_phone', $value)
                        ->where('id', '<>', $officeId)
                        ->exists();

                    if ($existsInAdmins || $existsInOffices) {
                        $fail(__('ingaz.manager_phone_unique'));
                    }
                },
            ],
            "password" => "nullable|string|min:6",
            "file" => "nullable|file",
            "file_name" => "nullable|string",
            "bank_address" => "nullable|string",
            "bank_account" => "nullable|string",
            "bank_account_name" => "nullable|string",
            "bank_swift_code" => "nullable|string",
            "bank_iban" => "nullable|string",
            "cost_of_dollar" => "nullable|numeric",
            "cost_of_other" => "nullable|numeric",
            "job_title_id" => "nullable|array",
            "job_title_id.*" => "nullable|exists:job_types,id",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */


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
