<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Enums\CategoryTypes;

class ItemImportRequest extends MainRequest
{
    /**
     * Get the validation rules based on the HTTP method.
     *
     * @return array The validation rules for the current request method.
     */

    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            'GET' => $this->view(),
            default => [],
        };
    }

    protected function store(): array
    {
        $baseRules = [
            'file' => 'required|mimes:xlsx,xls,csv',
            'main_category_id' => 'required|exists:fn_categories,id',
            'sub_category_id' => 'nullable|exists:fn_categories,id',
            'type' => 'required|string',
            'sale_account_id' => 'required|exists:fn_accounts,id',
            'is_tax' => 'required',
        ];

        $type = $this->input('type');

        $categoryType = CategoryTypes::tryFrom($type);

        if ($categoryType) {
            $additionalRules = $categoryType->rules();
            $baseRules = array_merge($baseRules, $additionalRules);
        }

        return $baseRules;
    }

    protected function update(): array
    {
        return [
            // Add your validation rules for updating resources here

        ];
    }

    protected function destroy(): array
    {
        return [
            // Add your validation rules for deleting resources here
        ];
    }

    protected function view(): array
    {
        return [
            // Add your validation rules for viewing resources here
        ];
    }
}
