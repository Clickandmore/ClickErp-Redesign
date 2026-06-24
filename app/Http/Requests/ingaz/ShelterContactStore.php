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
            'salary_final_status' => 'nullable|in:received,not_received',
            'duration_from_enter' => 'required',
            'wants_to_work' => 'nullable|in:yes,no,unknown',
            'back_reasons' => 'nullable',
            'workers_have_residency' => 'nullable|in:yes,no',
            'performing_medical_examination' => 'nullable|in:yes,no',
            'worker_service_type' => 'nullable|in:deportation,transfer',
            'client_name_snapshot' => 'nullable|string|max:255',
            'client_national_id' => 'nullable|string|max:255',
            'maid_name_snapshot' => 'nullable|string|max:255',
            'maid_nationality_snapshot' => 'nullable|string|max:255',
            'passport_number_snapshot' => 'nullable|string|max:255',
            'sponsorship_price' => 'nullable|numeric',
            'receipt_form_date' => 'nullable|date',
            'receipt_form_text' => 'nullable|string',
            'shelter_quick_tags' => 'nullable|string|max:1000',
            'complaint_action' => 'nullable|in:none,link,create',
            'shelter_complaint_id' => 'required_if:complaint_action,link|nullable|exists:complaints,id',
            'shelter_complaint_type_id' => 'nullable|exists:complaint_types,id',
            'shelter_complaint_priority' => 'nullable|in:low,medium,high,urgent',
            'shelter_complaint_subject' => 'nullable|string|max:255',
            'shelter_complaint_details' => 'nullable|string|max:5000',
        ];
    }

}
