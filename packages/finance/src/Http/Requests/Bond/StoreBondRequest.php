<?php

namespace Nami\Finance\Http\Requests\Bond;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Nami\Finance\Enums\BondPaymentTypes;

class StoreBondRequest extends MainRequest
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
            'type' => 'nullable',
            'payment_type' => ['required',new Enum(BondPaymentTypes::class)],
            'bond_book_num' => 'nullable',
            'cost_center_required' => 'nullable',
            'debit_account_id' => 'required|exists:fn_accounts,id',
            'credit_account_id' => 'required|exists:fn_accounts,id',
            'cost_center_id' => 'nullable|required_if:cost_center_required,on|exists:fn_cost_centers,id',
            'value' => 'required|numeric|min:1',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'required|max:500',
            'file' => 'nullable|file',
            'created_by_id' => 'nullable',
            'year_id' => 'nullable',
            'account_text' => 'nullable',
            'general_type' => 'nullable',
            'is_tax' => 'required|boolean',
            'net' => 'nullable|required_if:is_tax,1|numeric',
            'code' => 'required',
            'transfer_number' => ['nullable'],
            'transfer_file' => ["file",Rule::requiredIf(fn () => (in_array(request()->payment_type,$transferArray )))],
            'check_num' =>[ Rule::requiredIf(fn () => ( request()->payment_type == BondPaymentTypes::Check->value))],
            'check_date' => ['date_format:Y-m-d',Rule::requiredIf(fn () => ( request()->payment_type == BondPaymentTypes::Check->value))],
            'tax_rate' => 'nullable|required_if:is_tax,1|numeric',
            'tax_amount' => 'nullable|required_if:is_tax,1|numeric',
            'tax_setting_id' => 'nullable|exists:fn_tax_settings,id',
            'tax_account_id' => 'nullable|exists:fn_accounts,id',
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

    public function messages()
    {
        return [
            'credit_account_id.required' => __('finance.credit_account_id.required'),
            'debit_account_id.required' => __('finance.debit_account_id.required'),
        ];
    }



}
