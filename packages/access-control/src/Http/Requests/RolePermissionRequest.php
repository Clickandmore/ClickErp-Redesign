<?php

namespace Nami\AccessControl\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class RolePermissionRequest extends MainRequest
{
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view(),
        };
    }

    protected function store(): array
    {
        return [
            'name' => ['required', Rule::unique('roles', 'name')],
            'permissions' => 'required|min:1',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => ['required', 'unique:roles,name,' . $this->role_permission],
            'permissions' => 'required|min:1',
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
