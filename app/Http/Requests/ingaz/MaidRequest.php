<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaidRequest extends MainRequest
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
            'name:en' => 'required|string|max:255',
            'cv_file' => 'nullable',
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => [
                'required',
                Rule::unique('maids')->ignore($this->route('maid')),
            ],
            'price' => 'nullable|numeric',
            'salary' => 'nullable|numeric',
            'experience_years' => 'nullable',
            'religion_id' => 'nullable|exists:religions,id',
            'age' => 'nullable|numeric',
            'birth_date' => 'nullable|date',
            'job_id' => 'required|array',
            'job_id.*' => 'required|exists:job_titles,id',
            'type_id' => 'required|exists:job_types,id',
            'office_id' => 'nullable|exists:recruitment_offices,id',
            'languages' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'name:en.required' => 'اسم العاملة مطلوب',
            'name:en.string' => 'اسم العاملة يجب أن يكون نص',
            'name:en.max' => 'اسم العاملة يجب أن لا يتجاوز 255 حرف',
            'cv_file.required' => 'CV file is required',
            'nationality_id.required' => 'Nationality is required',
            'nationality_id.exists' => 'Invalid nationality',
            'passport_num.required' => 'Passport number is required',
            'passport_num.min' => 'Passport number must be at least 6 characters',
            'passport_num.unique' => 'رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر',
            'price.numeric' => 'Price must be numeric',
            'salary.numeric' => 'Salary must be numeric',
            'religion_id.required' => 'Religion is required',
            'religion_id.exists' => 'Invalid religion',
            'age.numeric' => 'Age must be numeric',
            'job_id.required' => 'Job is required',
            'job_id.array' => 'Job must be an array',
            'job_id.*.required' => 'Each job is required',
            'job_id.*.exists' => 'Invalid job',
            'type_id.required' => 'Type is required',
            'type_id.exists' => 'Invalid type',
            'office_id.exists' => 'Office does not exist',
        ];
    }
}
