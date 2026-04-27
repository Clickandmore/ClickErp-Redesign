<?php

namespace Nami\Crm\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends MainRequest
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
            'name' => ['required'],
            'price' => 'required',
            'details' => 'nullable',
            'photo' => [ ($this->isMethod('post'))?'required' :'nullable'],
        ];
    }
}
