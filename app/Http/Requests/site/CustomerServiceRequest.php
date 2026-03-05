<?php

namespace App\Http\Requests\site;

use App\Enums\auth\AdminTypes;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

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
            'customer' => [
                'required',
                Rule::exists('admins', 'id')->where(function ($query) {
                    $query
                        ->whereNull('deleted_at')
                        ->where('admin_type', AdminTypes::CustomerService->value)
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('status')->orWhere('status', 'active');
                        });
                }),
            ],
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
