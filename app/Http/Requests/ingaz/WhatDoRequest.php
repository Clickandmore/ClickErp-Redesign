<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class WhatDoRequest extends MainRequest
{
    protected function prepareForValidation()
    {
        $titles = $this->input('titles', []);

        if (is_array($titles)) {
            // Ignore unused repeater rows while still validating entered items.
            $titles = array_filter($titles, function ($item) {
                if (!is_array($item)) {
                    return true;
                }

                foreach ($item as $translation) {
                    if (!is_array($translation) || filled($translation['title'] ?? null)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $this->merge(['titles' => $titles ?? []]);
    }

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
            'type' => 'string',
            'titles' => 'present|array',
            'titles.*' => 'array',
            'titles.*.*.title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
        ];


        return $rules;
    }
}
