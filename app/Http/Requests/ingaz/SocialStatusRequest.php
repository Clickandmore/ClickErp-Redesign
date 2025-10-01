<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialStatusRequest extends MainRequest
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
        $updateId = $this->route('social_status');
        foreach (config('translatable.locales') as $locale) {
            $rules["title:$locale"] = ['required', Rule::unique('social_status_translations', 'title')->where('locale', $locale)->ignore($updateId, 'social_status_id')];
        }

        return $rules;
    }
}
