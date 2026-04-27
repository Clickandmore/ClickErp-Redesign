<?php

namespace Nami\Hr\Http\Requests\Sanction;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SanctionRequest extends MainRequest
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
            'title' => 'required|unique:hr_sanctions,title',
            'type' => 'required',
            'value' => 'required',
        ];
    }
}
