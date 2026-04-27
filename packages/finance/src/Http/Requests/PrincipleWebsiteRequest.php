<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\CheckDeletePrincipleWebiste;
use Illuminate\Validation\Rule;

class PrincipleWebsiteRequest extends MainRequest
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
            'title' => 'required|unique:fn_principle_websites,title',

        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'required|unique:fn_principle_websites,title,' . $this->route('principle_website') . ',id',
        ];
    }

    protected function destroy(): array
    {
        return [
            'principle_website_id' => [new CheckDeletePrincipleWebiste()],
        ];
    }

    public function validationData()
    {
        return array_merge($this->all(), [
            'principle_website_id' => $this->route('principle_website'),
        ]);
    }

    protected function view(): array
    {
        return [];
    }
}
