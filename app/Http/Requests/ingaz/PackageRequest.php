<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends MainRequest
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
            'original_price' => 'required|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0|lte:original_price',
            'price_with_tax' => 'nullable|numeric|min:0',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'days_count' => 'nullable|numeric|min:0',
            'rental_type' => 'required|string|in:hourly,daily,weekly,monthly,yearly',
            'duration_unit' => 'required|string|in:hour,day,week,month,year',
            'duration_count' => 'required|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'working_hours_per_day' => 'nullable|integer|min:1|max:24',
            'is_show' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'title:en' => 'required',
            'title:ar' => 'required',
        ];
    }
}
