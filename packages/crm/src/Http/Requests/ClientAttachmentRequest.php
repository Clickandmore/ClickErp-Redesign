<?php

namespace Nami\Crm\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ClientAttachmentRequest extends MainRequest
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
            'user_id' => 'required|exists:users,id',
            'attachment_id' => 'required|exists:crm_attachment_categories,id',
            'title' => 'required',
            'file' => [($this->isMethod('post')) ? 'required' : 'nullable', 'file'],
        ];
    }
}
