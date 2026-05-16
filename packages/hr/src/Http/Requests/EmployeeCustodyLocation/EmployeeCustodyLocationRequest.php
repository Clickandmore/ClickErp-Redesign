<?php

namespace Nami\Hr\Http\Requests\EmployeeCustodyLocation;

use App\Http\Requests\MainRequest;

class EmployeeCustodyLocationRequest extends MainRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
