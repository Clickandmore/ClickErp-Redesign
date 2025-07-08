<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class MainRequest extends FormRequest
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
        return [];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        try {
            $response = jsonValid($validator->errors());
            throw new \Illuminate\Validation\ValidationException($validator, $response);
        } catch (\Exception $e) {
            // إذا فشل jsonValid، استخدم الطريقة الافتراضية
            throw new \Illuminate\Validation\ValidationException($validator, response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422));
        }
    }
}
