<?php

namespace Nami\Crm\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class FollowClientRequest extends MainRequest
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
            'client_id' => 'required|exists:crm_clients,id',
            'follow_clients_category_id' => 'required|exists:crm_follow_clients_categories,id',
            'date' => 'required',
            'content' => 'required',
            'notes' => 'required',
            'result' => 'nullable',
        ];
    }
}
