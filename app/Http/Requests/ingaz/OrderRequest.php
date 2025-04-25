<?php

namespace App\Http\Requests\ingaz;

use App\Enums\ingaz\PaymentMethodEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class OrderRequest extends MainRequest
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

    public function withValidator(Validator $validator)
    {
        $validator->sometimes('details_list.admin_id', 'required|exists:admins,id', function ($input) {
            return $input->job_type_id == 1;
        });

        $validator->sometimes('details_list.package_id', 'required|exists:packages,id', function ($input) {
            return $input->job_type_id == 2;
        });

        $validator->sometimes('details_list.address_id', 'required|exists:user_addresses,id', function ($input) {
            return $input->job_type_id == 2;
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
//       dd( request()->details_list);
        return [
            'payment_method' => ['required', new Enum(PaymentMethodEnum::class)],
            'maid_id' => 'required|exists:maids,id',
            'job_type_id' => 'required|exists:job_types,id',
            'details_list' => 'required_if:job_type_id,1,2',
        ];
    }
}
