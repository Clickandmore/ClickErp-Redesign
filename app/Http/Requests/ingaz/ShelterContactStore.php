<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ShelterContactStore extends MainRequest
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
            'contract_id' => 'required|exists:contracts,id',
            'shelter_id'  => 'required|exists:shelter_places,id',
            'receipt_source' => 'required|string',
            'maid_date_back' => 'required',
            'salary_final_status' => 'required',
            'duration_from_enter' => 'required',
            'wants_to_work' => 'required|in:yes,no,unknown',
            'back_reasons' => 'nullable',
        ];
    }

}
