<?php

namespace Nami\AccessControl\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends MainRequest
{
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view()
        };
    }

    protected function store(): array
    {
        return [
            'name' => ['required'],
            'phone' => ['required'],
            'module' => ['required'],
            'role_id' => ['required'],
            'email' => ['required', 'email', 'unique:admins'],
            'permissions' => 'nullable|min:1',
            'password' => 'required|min:6',
            'department_id' => 'nullable',
            'employee_id' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => ['required'],
            'phone' => ['required'],
            'module' => ['required'],
            'role_id' => ['required'],
            'email' => ['required', Rule::unique('admins')->ignore($this->admin)],
            'permissions' => 'nullable|min:1',
            'password' => 'nullable|min:6',
        ];
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }
}
