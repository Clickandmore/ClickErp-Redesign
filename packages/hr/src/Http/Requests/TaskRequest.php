<?php

namespace Nami\Hr\Http\Requests;


use App\Http\Requests\MainRequest;

class TaskRequest extends MainRequest
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
            'title' => 'required',
            'description' => 'required',
            'end_date' => 'nullable',
            'end_time' => 'nullable',
            'file' => 'nullable',
            'status' => 'nullable',
            'employee_ids' => 'required|array|min:1',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'required',
            'description' => 'required',
            'end_date' => 'nullable',
            'end_time' => 'nullable',
            'file' => 'nullable',
            'status' => 'nullable',
            'employee_ids' => 'required|array|min:1',
        ];
    }

    protected function destroy(): array
    {
        return [
            // Add your validation rules for deleting resources here
        ];
    }

    protected function view(): array
    {
        return [
            // Add your validation rules for viewing resources here
        ];
    }

}
