<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class DeleteOfficeRequest extends MainRequest
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
    protected function prepareForValidation()
    {
        if ($this->isMethod('delete')) {
            $this->merge([
                'office_id' => $this->route('office'),
            ]);
        }
    }

    public function rules()
    {
        if ($this->isMethod('delete')) {
            $officeId = $this->route('office');

            return [
                'office_id' => ['required', 'exists:recruitment_offices,id'],
                'replacement_office_id' => [
                    'nullable',
                    'integer',
                    'exists:recruitment_offices,id',
                    Rule::notIn([(int) $officeId]),
                ],
            ];
        }

        return [];
    }


}
