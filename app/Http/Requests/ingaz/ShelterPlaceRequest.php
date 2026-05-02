<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class ShelterPlaceRequest extends MainRequest
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
        foreach (config('translatable.locales') as $locale) {
            $rules["title:$locale"] = ['required'];
        }
        $rules['female_capacity'] = ['nullable', 'integer', 'min:0'];
        $rules['male_capacity'] = ['nullable', 'integer', 'min:0'];
        $rules['address'] = ['nullable', 'string', 'max:1000'];
        $rules['map_url'] = ['nullable', 'string', 'max:1000'];
        $rules['latitude'] = ['nullable', 'numeric', 'between:-90,90'];
        $rules['longitude'] = ['nullable', 'numeric', 'between:-180,180'];
        $employeeExistsRule = fn () => Rule::exists('admins', 'id')
            ->whereNull('deleted_at');

        $rules['supervisor_admin_id'] = ['nullable', 'integer', $employeeExistsRule()];
        $rules['responsible_admin_id'] = ['nullable', 'integer', $employeeExistsRule()];
        $rules['notes'] = ['nullable', 'string', 'max:5000'];
        return $rules;
    }

}
