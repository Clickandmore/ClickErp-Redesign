<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Enums\StockStatusEnum;

class InventoryRequest extends MainRequest
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
            'stock_id' => 'required|exists:fn_stocks,id',
            'date' => 'required|date',
            'responsible' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable',
            'code' => 'required|numeric',
            'total_purchase_price' => 'nullable|numeric|min:0',
            'created_by_id' => 'required|exists:admins,id',
            'items.*.item_id' => 'required|exists:fn_items,id',
            'items.*.current_amount' => 'required|numeric|min:0',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.real_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.difference_amount' => 'nullable|numeric|min:0',
            'items.*.difference_purchase_price' => 'nullable|numeric|min:0',
        ];
    }

    protected function update(): array
    {
        return [
            'stock_id' => 'required|exists:fn_stocks,id',
            'date' => 'required|date',
            'responsible' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable',
            'code' => 'required|numeric',
            'total_purchase_price' => 'nullable|numeric|min:0',
            'items.*.item_id' => 'required|exists:fn_items,id',
            'items.*.current_amount' => 'required|numeric|min:0',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.real_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.difference_amount' => 'nullable|numeric|min:0',
            'items.*.difference_purchase_price' => 'nullable|numeric|min:0',
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
