<?php

namespace App\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreRapidCv extends MainRequest
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
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => [
                'required',
                Rule::unique('maids')->ignore($this->route('maid')),
            ],
            'type_id' => 'required|exists:job_types,id',
            'office_id' => 'nullable|exists:recruitment_offices,id',
        ];
    }

    public function messages()
    {
        return [
            'name:en.required' => 'اسم العاملة مطلوب',
            'name:en.string' => 'اسم العاملة يجب أن يكون نص',
            'name:en.max' => 'اسم العاملة يجب أن لا يتجاوز 255 حرف',
            'nationality_id.required' => helperTrans('web.nationality_id_is_required'),
            'nationality_id.exists' => helperTrans('web.invalid_nationality_id'),
            'passport_num.required' => helperTrans('web.passport_num_is_required'),
            'passport_num.min' => helperTrans('web.passport_num_min_length'),
            'passport_num.unique' => 'رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر',
            'type_id.required' => helperTrans('web.type_id_is_required'),
            'type_id.exists' => helperTrans('web.invalid_type_id'),
            'office_id.exists' => helperTrans('web.office_id not exist'),
        ];
    }
}
