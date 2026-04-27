<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Enums\StockStatusEnum;

class ConversionRequest extends MainRequest
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
            'from_stock_id' => 'required|exists:fn_stocks,id',
            'to_stock_id' => 'required|exists:fn_stocks,id',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable',
            'code' => 'required|numeric',
            'created_by_id' => 'required|exists:admins,id',
            'items.*.item_id' => 'required|exists:fn_items,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.available_amount' => 'required|numeric|min:0',
            'items.*.transfer_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
        ];
    }

    protected function update(): array
    {
        return [
            'from_stock_id' => 'required|exists:fn_stocks,id',
            'to_stock_id' => 'required|exists:fn_stocks,id',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
            'code' => 'required|numeric',
            'items.*.item_id' => 'required|exists:fn_items,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.available_amount' => 'required|numeric|min:0',
            'items.*.transfer_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
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
