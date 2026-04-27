<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SarfMovementCollectRequest extends MainRequest
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
        return [];
    }

    protected function update(): array
    {

        return [
            'code'         => 'required',
            'stock_id'     => 'required|exists:fn_stocks,id',
            'to_stock_id'     => 'nullable|exists:fn_stocks,id',
            'date'         => 'required|date',
            'note'         => 'nullable',
            'item_id'      => 'required|array',
            'total_price'      => 'nullable|array',
            'purchase_price'      => 'nullable|array',
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
}
