<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Enums\StockStatusEnum;

class SalesOrderRequest extends MainRequest
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
            'client_id'                 => 'required|exists:fn_clients,id',
            'date'                      => 'required|date',
            'notes'                     => 'nullable|string|max:1000',
            'file'                      => 'nullable',
            'code'                      => 'required|numeric',
            'total_price'               => 'nullable|numeric|min:1',
            'total_discount_value'      => 'nullable|numeric',
            'total_tax_value'      => 'nullable|numeric',
            'final_total'               => 'nullable|numeric|min:0',
            'created_by_id'             => 'nullable|exists:admins,id',
            'items.*.item_id'           => 'nullable|exists:fn_items,id',
            'items.*.sales_price'       => 'nullable|numeric|min:0',
            'items.*.sales_amount'      => 'nullable|numeric|min:0',
            'items.*.discount_per'      => 'nullable|numeric|min:0|max:100',
            'items.*.discount_value'    => 'nullable|numeric',
            'items.*.tax_per'           => 'nullable|numeric|min:0|max:100',
            'items.*.tax_value'         => 'nullable|numeric',
            'items.*.total_sales_price' => 'nullable|numeric',
            'items.*.final_total'       => 'nullable|numeric',
            'addition_items.*'          => 'nullable|array',
        ];
    }

    protected function update(): array
    {
        return [
            'client_id' => 'required|exists:fn_clients,id',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable',
            'code' => 'required|numeric',
            'total_price' => 'nullable|numeric',
            'total_discount_value' => 'nullable|numeric',
            'total_tax_value'      => 'nullable|numeric',
            'final_total' => 'nullable|numeric',
            'items.*.item_id' => 'nullable|exists:fn_items,id',
            'items.*.sales_price' => 'nullable|numeric|min:0',
            'items.*.sales_amount' => 'nullable|numeric|min:0',
            'items.*.discount_per' => 'nullable|numeric|min:0',
            'items.*.discount_value' => 'nullable|numeric',
            'items.*.tax_per'           => 'nullable|numeric|min:0|max:100',
            'items.*.tax_value'         => 'nullable|numeric',
            'items.*.total_sales_price' => 'nullable|numeric',
            'items.*.final_total' => 'nullable|numeric',
            'addition_items.*' => 'nullable|array',
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
