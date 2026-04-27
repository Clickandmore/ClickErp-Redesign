<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmClientRequest extends MainRequest
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
            'name' => 'required',
            'email' => 'required|email|unique:clients',
            'password' => 'required|min:6',
        ];
    }
}
