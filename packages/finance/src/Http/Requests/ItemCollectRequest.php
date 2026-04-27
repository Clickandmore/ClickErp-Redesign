<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ItemCollectRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
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
            'code' => 'required',
            'to_stock_id' => 'required|exists:fn_stocks,id',
            'item_id' => 'required|exists:fn_items,id',
            'date' => 'required',
            'amount' => 'required|numeric',
            'attachment' => 'nullable',
            'notes' => 'nullable',
            'status' => 'nullable',
            'details.*' => 'required',
        ];
    }

    protected function update(): array
    {
        return [];
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
