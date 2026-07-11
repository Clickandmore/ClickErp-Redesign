<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\ingaz\Offer;
use Illuminate\Validation\Rule;

class OfferRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $typesWithNationalities = [Offer::TYPE_NATIONALITY, Offer::TYPE_NATIONALITY_MAID];
        $typesWithMaids = [Offer::TYPE_MAID, Offer::TYPE_NATIONALITY_MAID];

        return [
            'type' => ['required', Rule::in(array_keys(Offer::types()))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'price_before_discount' => ['nullable', 'numeric', 'min:0'],
            'price_after_discount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'url' => ['nullable', 'url', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'nationality_ids' => [Rule::requiredIf(in_array($this->input('type'), $typesWithNationalities, true)), 'array', 'min:1'],
            'nationality_ids.*' => ['integer', 'exists:nationalities,id'],
            'maid_ids' => [Rule::requiredIf(in_array($this->input('type'), $typesWithMaids, true)), 'array', 'min:1'],
            'maid_ids.*' => ['integer', 'exists:maids,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                $this->filled('price_before_discount')
                && $this->filled('price_after_discount')
                && (float) $this->input('price_after_discount') > (float) $this->input('price_before_discount')
            ) {
                $validator->errors()->add('price_after_discount', 'السعر بعد الخصم يجب أن يكون أقل من أو يساوي السعر قبل الخصم.');
            }
        });
    }
}
