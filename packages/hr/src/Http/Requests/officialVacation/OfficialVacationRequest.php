<?php

namespace Nami\Hr\Http\Requests\officialVacation;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class OfficialVacationRequest extends MainRequest
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
            'name' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
        ];
    }
}
