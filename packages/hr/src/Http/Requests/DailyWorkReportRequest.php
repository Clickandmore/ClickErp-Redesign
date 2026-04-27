<?php

namespace Nami\Hr\Http\Requests;

use App\Http\Requests\MainRequest;

class DailyWorkReportRequest extends MainRequest
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
            "employee_id" => "required|exists:hr_employee,id",
            "title" => "required",
            "description" =>  "required",
            "date" => "required",
            "file" => "nullable"
        ];
    }

    protected function update(): array
    {
        return [
            "employee_id" => "required|exists:hr_employee,id",
            "title" => "required",
            "description" =>  "required",
            "date" => "required",
            "file" => "nullable"
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
