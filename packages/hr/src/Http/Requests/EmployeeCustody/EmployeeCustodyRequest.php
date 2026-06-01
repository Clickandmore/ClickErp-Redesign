<?php

namespace Nami\Hr\Http\Requests\EmployeeCustody;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;
use Nami\Hr\Models\EmployeeCustody;

class EmployeeCustodyRequest extends MainRequest
{
    public function rules()
    {
        return [
            'employee_id' => 'required|exists:hr_employee,id',
            'location_id' => 'required|exists:hr_custody_locations,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'received_at' => 'nullable|date',
            'returned_at' => 'nullable|date|after_or_equal:received_at',
            'status' => ['required', Rule::in(array_keys(EmployeeCustody::statuses()))],
            'file' => 'nullable|file',
            'notes' => 'nullable|string',
        ];
    }
}
