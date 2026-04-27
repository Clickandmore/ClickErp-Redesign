<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class StockSettingRequest extends MainRequest
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
            'show_price_in_additional_permit' => 'required|boolean',
            'show_price_in_conversions' => 'required|boolean',
            'create_additional_permit_with_confirmed_purchase_bill' => 'required|boolean',
            'create_additional_permit_with_purchase_bill' => 'required|boolean',
            'create_additional_permit_with_back_purchase_bill' => 'required|boolean',
            'more_than_additional_permit_for_bill' => 'required|boolean',
            'create_additional_permit_with_return_bill' => 'required|boolean',
            'sync_with_zatca' => 'required|boolean',
            'multiple_stocks_in_bill_sales' => 'required|boolean',
            'multiple_stocks_in_bill_purchase' => 'required|boolean',
            'auto_create_stock_movement_with_bill_sale' => 'required|boolean',
            'show_purchase_price_in_first_stocks' => 'required|boolean',
        ];
    }

    protected function update(): array
    {
        return [
            'show_price_in_additional_permit' => 'required|boolean',
            'show_price_in_conversions' => 'required|boolean',
            'create_additional_permit_with_purchase_bill' => 'required|boolean',
            'create_additional_permit_with_confirmed_purchase_bill' => 'required|boolean',
            'create_additional_permit_with_back_purchase_bill' => 'required|boolean',
            'more_than_additional_permit_for_bill' => 'required|boolean',
            'create_additional_permit_with_return_bill' => 'required|boolean',
            'sync_with_zatca' => 'required|boolean',
            'multiple_stocks_in_bill_sales' => 'required|boolean',
            'multiple_stocks_in_bill_purchase' => 'required|boolean',
            'auto_create_stock_movement_with_bill_sale' => 'required|boolean',
            'show_purchase_price_in_first_stocks' => 'required|boolean',
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
