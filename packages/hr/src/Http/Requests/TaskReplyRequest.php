<?php

namespace Nami\Hr\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class TaskReplyRequest extends MainRequest
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
            'description' =>  'nullable' ,
            'file' => 'nullable',
            'task_id' => 'nullable',
            'status' => 'nullable',
            'employee_id' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'title' =>  'nullable' ,
            'description' => 'nullable' ,
            'file' => 'nullable',
            'task_id' => 'nullable',
            'status' => 'nullable',
            'employee_id' => 'nullable',
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
