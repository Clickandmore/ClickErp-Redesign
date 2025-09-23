<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use App\Rules\DeleteOfficeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class DeleteOfficeRequest extends MainRequest
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
    protected function prepareForValidation()
    {
        if ($this->isMethod('delete')) {
            $this->merge([
                'office_id' => true,
            ]);
        }
    }
    public function rules()
    {
        if ($this->isMethod('delete')) {
            $officeId = $this->route('office');
            return [
                'office_id' => [new DeleteOfficeRule($officeId)],
            ];
        }
        return [];
    }


}
