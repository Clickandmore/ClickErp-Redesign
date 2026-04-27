<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;
use Nami\Finance\Enums\StockStatusEnum;

class StockRequest extends MainRequest
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
            'name' => 'required|unique:fn_stocks,name',
            'phone' => 'required|string|unique:fn_stocks,phone',
            'address' => 'nullable|string',
            'status' => ['required',   new Enum(StockStatusEnum::class)],
            'storekeeper' => 'nullable|string',
            'notes' => 'nullable|string',
            'code' => 'required',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => 'required|unique:fn_stocks,name,' . $this->stock,
            'phone' => 'required|string|unique:fn_stocks,phone,' . $this->stock,
            'address' => 'nullable|string',
            'status' => ['required',   new Enum(StockStatusEnum::class)],
            'storekeeper' => 'nullable|string',
            'code' => 'required',
            'notes' => 'nullable|string',
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
