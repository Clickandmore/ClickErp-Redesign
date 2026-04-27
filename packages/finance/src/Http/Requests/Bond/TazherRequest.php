<?php

namespace Nami\Finance\Http\Requests\Bond;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;
use Nami\Finance\Enums\BondPaymentTypes;

class TazherRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'nullable',
            'payment_type' => ['required', new Enum(BondPaymentTypes::class)],
            'bond_book_num' => 'nullable',
            'debit_account_id' => 'required|exists:fn_accounts,id',
            'credit_account_id' => 'required|exists:fn_accounts,id',
            'value' => 'required|numeric|min:1',
            'net' => 'required|nullable',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'required|max:500',
            'file' => 'nullable|file',
            'code' => 'required',
//            'transfer_number' => 'required',
            'transfer_file' => "file|nullable",
        ];
    }

}
