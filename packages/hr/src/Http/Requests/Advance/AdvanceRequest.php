<?php

namespace Nami\Hr\Http\Requests\Advance;

use App\Http\Requests\MainRequest;

use Illuminate\Foundation\Http\FormRequest;
use Nami\Hr\Rules\ChronologicalDates;
use Nami\Hr\Rules\SumEqual;

class AdvanceRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => 'required|exists:hr_employee,id',
            'value' => 'required',
            'date' => 'required',
            'notes' => 'nullable',
            'payment_method' => 'required',
            'delivery_method' => 'required',
            'from_date' => 'nullable',
            'to_date' => 'nullable',
            'file' => 'nullable',
            'amount_value' => [ 'array', new SumEqual($this->input('value'),'payment_method')],
            'date_value' => [ 'array',  new ChronologicalDates('payment_method')],
        ];
    }
}
