<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use App\Rules\DeleteOfficeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SendEmailResetPasswordRequest extends MainRequest
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
            'office_id' => 'required|exists:recruitment_offices,id',
            'email' => 'required|exists:admins,email'
            ];
    }

    public function messages()
    {
        return [
            'office_id.required' => __('ingaz.office_required'),
            'office_id.exists' => __('ingaz.office_exists'),

            'email.required' => __('ingaz.email_required'),
            'email.exists' => __('ingaz.email_exists'),
        ];
    }


}
