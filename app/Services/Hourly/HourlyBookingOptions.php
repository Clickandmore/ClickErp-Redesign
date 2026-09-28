<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyPackage;
use Illuminate\Support\Facades\Validator;

/** The same form contract and validation are used by the panel and customer API. */
class HourlyBookingOptions
{
    public static function rules(): array
    {
        return ['bring_materials' => ['nullable', 'boolean'], 'service_type' => ['nullable', 'in:home,company']];
    }

    public function fields(HourlyPackage $item): array
    {
        $settings = app(HourlyBookingRules::class)->settings();
        $fields = [];
        if ($settings->value($item, 'show_material_option')) {
            $fields['bring_materials'] = ['type' => 'boolean', 'label' => __('إحضار مواد من الشركة'),
                'default' => (bool) $item->materials_included, 'included' => (bool) $item->materials_included];
        }
        if ($settings->value($item, 'show_service_type_field')) {
            $options = [];
            if ($item->for_home) $options[] = ['value' => 'home', 'label' => __('منزل')];
            if ($item->for_company) $options[] = ['value' => 'company', 'label' => __('شركة')];
            $fields['service_type'] = ['type' => 'select', 'label' => __('نوع الخدمة'), 'options' => $options,
                'default' => $options[0]['value'] ?? null];
        }
        return $fields;
    }

    public function resolve(HourlyPackage $item, array $input): array
    {
        $input = Validator::make($input, self::rules())->validate();
        $fields = $this->fields($item);
        foreach ($input as $key => $value) {
            if ($value !== null && !isset($fields[$key])) {
                throw \Illuminate\Validation\ValidationException::withMessages([$key => __('هذا الخيار غير متاح لهذه الخدمة.')]);
            }
        }
        $result = [];
        foreach ($fields as $key => $field) {
            $value = $input[$key] ?? $field['default'];
            if ($key === 'service_type' && !in_array($value, array_column($field['options'], 'value'), true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([$key => __('نوع الخدمة غير متاح لهذا العنصر.')]);
            }
            $result[$key] = $key === 'bring_materials' ? (bool) $value : $value;
        }
        return $result;
    }

    /** Display rows are in cents, tax-inclusive; the tax row is informational, never added twice. */
    public function presentation($item, array $quote, string $channel): array
    {
        $settings = app(HourlyBookingRules::class)->settings();
        $separateFees = (bool) $settings->value($item, 'show_custom_fee_separately');
        $separateTax = (bool) $settings->value($item, 'show_tax_separately', $channel);
        $components = $quote['components'] ?? ['service' => $quote['total']];
        // Later operational adjustments may change the order total after its booking snapshot.
        $components['adjustment'] = ($components['adjustment'] ?? 0) + $quote['total'] - array_sum($components);
        $labels = ['service' => __('الخدمة'), 'extra_workers' => __('عاملات إضافيات'), 'transport' => __('النقل'),
            'adjustment' => __('تعديل السعر'), 'discount' => __('الخصم')];
        $lines = [];
        foreach ($separateFees ? $components : ['service' => $quote['total']] as $key => $amount) {
            if ($amount || $key === 'service') $lines[] = ['code' => $key, 'label' => $labels[$key], 'amount' => $amount];
        }
        return ['show_tax_separately' => $separateTax, 'show_custom_fee_separately' => $separateFees,
            'lines' => $lines, 'tax_line' => $separateTax ? ['label' => __('الضريبة المشمولة'), 'amount' => $quote['tax']] : null];
    }
}
