<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ExchangeCheckRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'PUT', 'PATCH' => $this->update()
        };

    }

    protected function update(): array
    {
        return [
            'refuse_reason' => 'required',
            'date' => 'required',
        ];
    }

}
