<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends MainRequest
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
        // Extract the role ID from the URL
        $roleId = $this->route('role');

        return [
            'name' => 'required|unique:roles,name,' . $roleId,
            'description' => 'required',
            'display_name' => 'required|unique:roles,display_name,' . $roleId,
            'permission' => 'required',
        ];
    }


    public function messages()
    {
        return [
            'name.required' => 'The name is required',
            'name.unique' => 'You entered this role before',
            'description.required' => 'The description is required',
            'display_name.required' => 'The display name is required',
            'display_name.unique' => 'You entered this display name before',
            'permission.required' => 'Please select the permissions',
        ];
    }
}
