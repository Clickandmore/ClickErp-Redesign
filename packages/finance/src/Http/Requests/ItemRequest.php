<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Enums\CategoryTypes;

class ItemRequest extends MainRequest
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
        $baseRules = [
            'image' => 'nullable',
            'name' => 'required|string|max:255|unique:fn_items,name',
            'main_category_id' => 'required|exists:fn_categories,id',
            'sub_category_id' => 'nullable|exists:fn_categories,id',
            'type' => 'required|string',
            'code_string' => 'nullable|string|max:255',
            'code' => 'required',
            'is_tax' => 'required',
            'tax_rate' => 'required_if:is_tax,1',
            'tax_amount' => 'required_if:is_tax,1',
            'tax_setting_id' => 'required_if:is_tax,1',
            'tax_account_id' => 'required_if:is_tax,1',
            'details' => 'nullable|string|max:1000',
            'sale_account_id' => 'required|exists:fn_accounts,id',
            'sale_price' => 'required|min:0',
            'purchase_price' => 'required|min:0',
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
        $baseRules = [
            'image' => 'nullable',
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:fn_categories,id',
            'sub_category_id' => 'nullable|exists:fn_categories,id',
            'type' => 'required|string',
            'is_tax' => 'required',
            'code_string' => 'nullable|string|max:255',
            'code' => 'required|integer|unique:fn_items,code',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'tax_setting_id' => 'required|exists:fn_tax_settings,id',
            'tax_account_id' => 'required|exists:fn_accounts,id',
            'details' => 'nullable|string|max:1000',
            'sale_account_id' => 'required|exists:fn_accounts,id',
            'sale_price' => 'required|min:0',
            'purchase_price' => 'required|min:0',
        ];

        $type = $this->input('type');

        $categoryType = CategoryTypes::tryFrom($type);

        if ($categoryType) {
            $additionalRules = $categoryType->rules();
            $baseRules = array_merge($baseRules, $additionalRules);
        }

        return $baseRules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('finance.name_required'),
            'name.max' => __('finance.name_max'),
            'main_category_id.required' => __('finance.main_category_required'),
            'main_category_id.exists' => __('finance.main_category_exists'),
            'sub_category_id.exists' => __('finance.sub_category_exists'),
            'type.required' => __('finance.type_required'),
            'code.required' => __('finance.code_required'),
            'code.integer' => __('finance.code_integer'),
            'code.unique' => __('finance.code_unique'),
            'is_tax.required' => __('finance.is_tax_required'),
            'tax_rate.required_if' => __('finance.tax_rate_required'),
            'tax_rate.numeric' => __('finance.tax_rate_numeric'),
            'tax_rate.min' => __('finance.tax_rate_min'),
            'tax_rate.max' => __('finance.tax_rate_max'),
            'tax_amount.required_if' => __('finance.tax_amount_required'),
            'tax_amount.numeric' => __('finance.tax_amount_numeric'),
            'tax_setting_id.required_if' => __('finance.tax_setting_required'),
            'tax_setting_id.exists' => __('finance.tax_setting_exists'),
            'tax_account_id.required_if' => __('finance.tax_account_required'),
            'tax_account_id.exists' => __('finance.tax_account_exists'),
            'sale_account_id.required' => __('finance.sale_account_required'),
            'sale_account_id.exists' => __('finance.sale_account_exists'),
            'purchase_price.required' => __('finance.purchase_price_required'),
            'purchase_price.min' => __('finance.purchase_price_min'),
            'min_order.required' => __('finance.min_order_required'),
            'sale_unit_id.required' => __('finance.sale_unit_required'),
            'sale_unit_id.exists' => __('finance.sale_unit_exists'),
            'purchase_unit_id.required' => __('finance.purchase_unit_required'),
            'purchase_unit_id.exists' => __('finance.purchase_unit_exists'),
            'stock_account_id.required' => __('finance.stock_account_required'),
            'stock_account_id.exists' => __('finance.stock_account_exists'),
            'supplier_id.required' => __('finance.supplier_required'),
            'supplier_id.exists' => __('finance.supplier_exists'),
            'stock_tracking.required' => __('finance.stock_tracking_required'),
            'stock_tracking.boolean' => __('finance.stock_tracking_boolean'),
            'component_item_id.required' => __('finance.component_item_required'),
            'component_item_id.exists' => __('finance.component_item_exists'),
            'amount.required' => __('finance.amount_required'),
            'one_purchase_price.required' => __('finance.one_purchase_price_required'),
            'total_purchase_price.required' => __('finance.total_purchase_price_required'),
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
