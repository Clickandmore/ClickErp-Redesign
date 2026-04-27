<?php

namespace Nami\Hr\Http\Requests\permissionSetting;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class PermissionSettingRequest extends MainRequest
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
            'number_of_hours' => 'required',
            'per_month' => 'required',
            'shifts_ids' => 'array|required',
            'shifts_ids.*' => 'integer|exists:hr_shifts,id',
        ];
    }
}
