<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ProductNumSettingRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'code' => 'required_if:type,0|max:255',
            'code_string' => 'required_if:type,0|max:255',
            'mainCategories.*.code' => 'required_if:type,1|max:255',
            'mainCategories.*.code_string' => 'required_if:type,1|max:255',
            'subCategories.*.code' => 'required_if:type,2|max:255',
            'subCategories.*.code_string' => 'required_if:type,2|max:255',
        ];

    }

}
