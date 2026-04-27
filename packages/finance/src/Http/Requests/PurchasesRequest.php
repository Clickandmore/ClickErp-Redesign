<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\UniqueItemStockPair;
use Nami\Finance\Enums\StockStatusEnum;

class PurchasesRequest extends MainRequest
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
        $itemIds = $this->input('item_ids', []);
        $stockIds = $this->input('stock_ids', []);

        return [
            'code' => 'required|numeric',
            'supplier_bill_number' => 'nullable',
            'main_stock_id' => 'required_without:stock_id',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:fn_suppliers,id',
            'notes' => 'nullable',
            'purchase_id' => 'nullable',
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
            'ItemNotes' => 'nullable|array',
            'ItemNotes.*' => 'nullable',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'purchase_price' => 'required|array',
            'purchase_price.*' => 'required|numeric|min:0',
            'discount_per.*' => 'required',
            'tax_per.*' => 'required',
            'tax_value.*' => 'required',
            'discount_value' => 'required',
            'file' => 'nullable',
        ];
    }

    protected function update(): array
    {
        $itemIds = $this->input('item_ids', []);
        $stockIds = $this->input('stock_ids', []);

        return [
            'supplier_bill_number' => 'nullable',
            'main_stock_id' => 'required_without:stock_id',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:fn_suppliers,id',
            'notes' => 'nullable',
            'purchase_id' => 'nullable',
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
            'ItemNotes' => 'nullable|array',
            'ItemNotes.*' => 'nullable',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'purchase_price' => 'required|array',
            'purchase_price.*' => 'required|numeric|min:0',
            'discount_per.*' => 'required',
            'tax_per.*' => 'required',
            'tax_value.*' => 'required',
            'discount_value' => 'required',
            'file' => 'nullable',
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
            'supplier_id.required' => __('finance.you should select the supplier !'),
            'total_item.required' => __('finance.the total price is required !'),
            'item_id.*.required' => __('finance.the item is required !'),
            'amount.*.required' => __('finance.the amount is required !'),
            'price.*.required' => __('finance.the price is required !'),
            'purchase_price.*.required' => __('finance.the price is required !'),
        ];
    }
}
