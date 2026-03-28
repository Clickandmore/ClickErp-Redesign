<?php

namespace App\Http\Requests\site;

use App\Enums\auth\AdminTypes;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class ImmediateReciptsRequest extends MainRequest
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
            'maid_id' => ['required', 'exists:maids,id'],
            'job_type_id' => ['required', 'exists:job_types,id'],
            'order_type' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
            'details_list' => ['required', 'array'],
            'details_list.admin_id' => [
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
        ];
    }


    public function messages()
    {
        return [
            'maid_id.required'            => __('ingaz.maid_id_required'),
            'maid_id.exists'              => __('ingaz.maid_id_exists'),
            'job_type_id.required'        => __('ingaz.job_type_id_required'),
            'job_type_id.exists'          => __('ingaz.job_type_id_exists'),
            'order_type.required'         => __('ingaz.order_type_required'),
            'payment_method.required'     => __('ingaz.payment_method_required'),
            'details_list.required'       => __('ingaz.details_list_required'),
            'details_list.array'          => __('ingaz.details_list_array'),
            'details_list.admin_id.required' => __('ingaz.details_list_admin_id_required'),
            'details_list.admin_id.exists'   => __('ingaz.details_list_admin_id_exists'),
        ];
    }



}
