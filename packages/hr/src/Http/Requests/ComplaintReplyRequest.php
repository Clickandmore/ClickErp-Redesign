<?php

namespace Nami\Hr\Http\Requests;

use App\Http\Requests\MainRequest;

class ComplaintReplyRequest extends MainRequest
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
            'complaint_id' => 'required|exists:complaints,id',
            'date' => 'required|date',
            'text' => 'required|string|max:1000',
            'admin_id' => 'nullable|exists:admins,id',
        ];
    }

    protected function update(): array
    {
        return [
            'complaint_id' => 'required|exists:complaints,id',
            'date' => 'required|date',
            'text' => 'required|string|max:1000',
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
