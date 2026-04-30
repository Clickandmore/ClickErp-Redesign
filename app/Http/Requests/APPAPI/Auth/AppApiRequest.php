<?php

namespace App\Http\Requests\APPAPI\Auth;

use App\Http\Requests\MainRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AppApiRequest extends MainRequest
{
    protected function appApiMessage(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            apiResponse(false, $validator->errors()->first(), [
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
