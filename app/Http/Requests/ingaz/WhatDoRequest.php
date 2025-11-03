<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class WhatDoRequest extends MainRequest
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
            'title:ar' => 'string',
            'title:en' => 'string',
            'description:ar' => 'string',
            'description:en' => 'string',
            'icon' => 'string',
            'titles' => 'array',
            'image' => [$this->isMethod('post') ? 'nullable' : ''],
        ];


        return $rules;
    }
}
