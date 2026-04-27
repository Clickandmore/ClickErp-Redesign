<?php

namespace Nami\Finance\Http\Requests\Bond;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Nami\Finance\Enums\BondPaymentTypes;

class UpdateBondConfirmRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $transferArray = $this->getTransferNumberArray();
        return [
            'payment_type' => ['required',new Enum(BondPaymentTypes::class)],
            'bond_book_num' => 'nullable',
            'debit_account_id' => 'required|exists:fn_accounts,id',
            'credit_account_id' => 'required|exists:fn_accounts,id',
            'value' => 'required|numeric|min:1',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'required|max:500',
            'file' => 'nullable|file',
            'transfer_number' => [Rule::requiredIf(fn () => (in_array(request()->payment_type,$transferArray )))],
            'transfer_file' => ["file",Rule::requiredIf(fn () => (in_array(request()->payment_type,$transferArray )))],
            'check_num' =>[ Rule::requiredIf(fn () => ( request()->payment_type == BondPaymentTypes::Check->value))],
            'check_date' => ['date_format:Y-m-d',Rule::requiredIf(fn () => ( request()->payment_type == BondPaymentTypes::Check->value))],
        ];
    }

    private function getTransferNumberArray(){
        return [
            BondPaymentTypes::BankTransfer->value ,
            BondPaymentTypes::BankVisa->value ,
            BondPaymentTypes::ElectronicWallet->value ,
            BondPaymentTypes::Network->value ,
        ];
    }




}
