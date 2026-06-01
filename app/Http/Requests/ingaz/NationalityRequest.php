<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NationalityRequest extends MainRequest
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
        $updateId = $this->route('nationality');

        $rules = [
            'logo' => [$this->isMethod('post') ? 'required' : 'nullable'],
            'phone_code' => "required",
            'price' => "nullable|numeric|min:0",
            'direct_recruitment_cost' => "nullable|numeric|min:0",
            'external_recruitment_cost' => "nullable|numeric|min:0",
            'government_cost' => "nullable|numeric|min:0",
            'service_transportation_cost' => "nullable|numeric|min:0",
            'rental_cost' => "nullable|numeric|min:0",
            "expected_duration"=> "required|integer|min:45",
            'monthly_salary' => "nullable|numeric|min:0",
            'is_show' => 'nullable|in:0,1,on',
        ];

        foreach (config('translatable.locales') as $locale) {
            $rules["sub_title:$locale"] = ['required', Rule::unique('nationalities_translations', 'sub_title')->where('locale', $locale)->ignore($updateId, 'nationality_id')];
            $rules["description:$locale"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
