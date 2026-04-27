<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class StockMovementRequest extends MainRequest
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
            'code'         => 'required',
            'stock_id'     => 'required|exists:fn_stocks,id',
            'date'         => 'required|date',
            'note'         => 'nullable',
            'item_id'      => 'required|array',
            'item_id.*' => 'required|exists:fn_items,id',
            'price'        => 'required|array',
            'price.*'      => 'required',
            'one_price'    => 'required|array',
            'one_price.*'  => 'required',
            'amount'       => 'required|array',
            'amount.*'     => 'required',
        ];
    }

    protected function update(): array
    {
        return [
            'code'         => 'required',
            'stock_id'     => 'required|exists:fn_stocks,id',
            'date'         => 'required|date',
            'note'         => 'nullable',
            'item_id'      => 'required|array',
            'price'        => 'required|array',
            'amount'       => 'required|array',
            'one_price'    => 'required|array',
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
            'item_id.*'     =>  __('finance.the item is required !'),
            'price.*'   => __('finance.the price is required !'),
            'one_price.*'   => __('finance.the price is required !'),
            'amount.*'      => __('finance.the amount is required !'),
        ];
    }
}
