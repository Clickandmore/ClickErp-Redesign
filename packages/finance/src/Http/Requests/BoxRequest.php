<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\HasConstraintDetails;

class BoxRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function prepareForValidation()
    {
        if ($this->method() === 'DELETE') {
            $this->merge([
                'box' => request()->route()->parameters()['box'],
            ]);
        }
    }
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
            'title' => 'required|unique:fn_boxes,title',
            'account_id' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'required',
            'account_id' => 'nullable',
        ];
    }

    protected function destroy(): array
    {
        return [
            'box' => [
                new  HasConstraintDetails(box_id:request()->box )
            ]
        ];
    }

    protected function view(): array
    {
        return [];
    }
}
