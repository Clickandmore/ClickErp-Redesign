<?php

namespace Nami\Finance\Http\Requests\Bond;
use App\Http\Requests\MainRequest;
class StoreBondConfirmRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'bond_id' => 'required|exists:fn_bonds,id',
        ];
    }


}
