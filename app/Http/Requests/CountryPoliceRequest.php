<?php

namespace App\Http\Requests;

class CountryPoliceRequest extends MainRequest
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

        $rules = [
            'name:ar' => 'string',
            'name:en' => 'string',
            'text_file_1:ar' => 'string',
            'text_file_1:en' => 'string',
            'text_file_2:ar' => 'string',
            'text_file_2:en' => 'string',
            'flag' => [$this->isMethod('post') ? 'nullable' : ''],
            'file_1' => [$this->isMethod('post') ? 'nullable' : ''],
            'file_2' => [$this->isMethod('post') ? 'nullable' : ''],
        ];


        return $rules;
    }
}
