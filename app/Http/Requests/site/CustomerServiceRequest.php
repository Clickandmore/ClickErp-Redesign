<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;

class CustomerServiceRequest extends MainRequest
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

    public function rules()
    {
        return [
            'customer' => ['required' , 'exists:admins,id'],
            // 'maid_id' => ['required' , 'exists:maids,id'],
        ];
    }


    public function messages(): array
    {
        return [
            'customer.required' => __('ingaz.customer_is_required'),
            'customer.exists' => __('ingaz.customer_is_not_exists'),
            'maid_id.required' => __('ingaz.maid_id_is_required'),
            'maid_id.exists' => __('ingaz.maid_id_is_not_exists'),
        ];
    }



}
