<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\UniqueItemStockPair;
use Nami\Finance\Enums\StockStatusEnum;

class SalesRequest extends MainRequest
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
        $itemIds = $this->input('item_id',[]);
        $stockIds = $this->input('stock_id',[]);

        return [
            'code' => 'required|numeric',
            'date' => 'required|date',
            'notes' => 'nullable',
            'main_stock_id' => 'required_without:stock_id',
            'payment_type' => 'required',
            'client_id' => 'required|exists:fn_clients,id',
            'total_item' => 'required|numeric',
            'item_id.*' => 'required|exists:fn_items,id',
            'stock_id.*' => 'required_without:main_stock_id|exists:fn_stocks,id',
            'item_id' => [
                'required',
                'array',
                new UniqueItemStockPair($itemIds, $stockIds)
            ],
            'stock_id' => [
                'nullable',
                'array',
            ],
            'amount' => 'required|array',
            'amount.*' => 'required|numeric|min:0',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'purchase_price' => 'required|array',
            'purchase_price.*' => 'required|numeric|min:0',
            'sale_price' => 'required|array',
            'sale_price.*' => 'required|numeric|min:0',
            'tax_per' => 'required|array',
            'tax_per.*' => 'required|numeric|min:0',
            'tax_value' => 'required|array',
            'tax_value.*' => 'required|numeric|min:0',
            'discount_per' => 'required|array',
            'discount_per.*' => 'required|numeric|min:0',
            'discount_value' => 'required|array',
            'discount_value.*' => 'required|numeric|min:0',
            'total_sales_price' => 'sometimes|array',
            'total_sales_price.*' => 'sometimes|numeric',
            'final_total' => 'sometimes|numeric',
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
            'total_price' => 'required|numeric',
            'total_discount_value' => 'required|numeric',
            'final_total' => 'required|numeric',
            'items.*.item_id' => 'required|exists:fn_items,id',
            'items.*.sales_price' => 'required|numeric|min:0',
            'items.*.sales_amount' => 'required|numeric|min:0',
            'items.*.discount_per' => 'required|numeric|min:0',
            'items.*.discount_value' => 'required|numeric',
            'items.*.total_sales_price' => 'required|numeric',
            'items.*.final_total' => 'required|numeric',
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

    public function messages()
    {
        return [
            'main_stock_id.required_without' => __('finance.you should select a stock !'),
            'stock_id.*.required_without' => __('finance.you should select a stock for all products !'),
            'client_id.required' => __('finance.you should select the client !'),
            'payment_type.required' => __('finance.you should select the payment type !'),
            'total_item.required' => __('finance.the total price is required !'),
            'item_id.*.required' => __('finance.the item is required !'),
            'amount.*.required' => __('finance.the amount is required !'),
            'price.*.required' => __('finance.the price is required !'),
            'purchase_price.*.required' => __('finance.the price is required !'),
            'sale_price.*.required' => __('finance.the price is required !'),
        ];
    }
}
