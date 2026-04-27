<?php

namespace Nami\Hr\Http\Requests\Allowance;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditAllowanceRequest extends MainRequest
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
            'title' => ['required', Rule::unique('hr_allowances')->ignore(request()->input('allowances_id')),],
            'type' => 'required',
            'value' => 'required',
        ];
    }
}
