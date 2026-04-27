<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class PrincipleHistoryRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */


    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view()
        };

    }

    protected function store(): array
    {
        return [
            'from_website_id' => 'required|exists:fn_principle_websites,id',
            'to_website_id' => 'required|exists:fn_principle_websites,id',
        ];
    }

    protected function update(): array
    {
        return [
            'from_website_id' => 'required|exists:fn_principle_websites,id',
            'to_website_id' => 'required|exists:fn_principle_websites,id',
        ];
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }
}
