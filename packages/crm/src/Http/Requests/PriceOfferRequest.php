<?php

namespace Nami\Crm\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class PriceOfferRequest extends MainRequest
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
            'typeSelect' => 'required|in:client,customer',
            'client_id' => 'required_if:typeSelect,client|exists:crm_clients,id|nullable',
            'admin_id' => 'required_if:typeSelect,customer|exists:admins,id|nullable',
            'date' => 'required',
            'discount' => 'nullable',
            'description' => 'nullable',
            'terms' => 'nullable',
            'notes' => 'nullable',
            'desc' => 'nullable',
            'file' => 'nullable',
            'status' => 'nullable',
        ];
    }
}
