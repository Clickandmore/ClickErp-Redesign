<?php

namespace App\Http\Requests\Hourly;

use App\Services\Hourly\HourlyCategorySettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** إعدادات الفئة: كل حقل اختياري؛ الفارغ أو «inherit» يعني يرث الإعداد العام. */
class CategorySettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $bool = ['nullable', Rule::in(['inherit', '0', '1', 0, 1])];
        $time = ['nullable', 'regex:/^(inherit|disabled|([01]\d|2[0-3]):[0-5]\d)$/'];
        $rules = [
            'tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'show_custom_fee_separately' => $bool,
            'allow_reschedule' => $bool,
            'reschedule_hours_before' => ['nullable', 'integer', 'between:0,720'],
            'max_reschedules_per_visit' => ['nullable', 'integer', 'between:0,20'],
            'show_packages' => $bool, 'show_one_off' => $bool,
            'package_price_display' => ['nullable', Rule::in(['inherit', 'total', 'per_visit'])],
            'assign_by_product_attribute' => $bool, 'customer_worker_selection' => $bool,
            'tracking_enabled' => $bool,
            'auto_complete_visits_at' => $time,
            'dispatcher_past_days' => ['nullable', 'integer', 'between:0,365'],
            'dispatcher_future_days' => ['nullable', 'integer', 'between:0,730'],
            'customer_future_days' => ['nullable', 'integer', 'between:0,730'],
            'same_day_cutoff' => ['nullable', 'regex:/^(inherit|([01]\d|2[0-3]):[0-5]\d)$/'],
            'handpick_session_dates' => $bool, 'allow_multiple_sessions_per_day' => $bool,
            'show_material_option' => $bool, 'show_time_options_app' => $bool, 'show_service_type_field' => $bool,
            'order_form_steppers_mode' => ['required', Rule::in(['inherit', 'custom'])],
            'order_form_steppers' => ['nullable', 'array', 'max:2'],
            'order_form_steppers.*.key' => ['required', 'distinct', Rule::in(HourlyCategorySettings::STEPPER_KEYS)],
            'order_form_steppers.*.label' => ['nullable', 'string', 'max:60'],
            'order_form_steppers.*.channels' => ['nullable', 'array'],
            'order_form_steppers.*.channels.*' => [Rule::in(HourlyCategorySettings::CHANNELS)],
            'order_form_steppers.*.min' => ['required', 'integer', 'between:1,50'],
            'order_form_steppers.*.max' => ['required', 'integer', 'between:1,50'],
            'order_form_steppers.*.scale_price' => ['nullable', 'boolean'],
            'working_days' => ['nullable', 'array'],
            'working_days.*.is_on' => $bool,
            'working_days.*.order_buffer_hours' => ['nullable', 'integer', 'between:0,168'],
        ];
        foreach (['show_tax_separately', 'auto_assign', 'auto_assign_driver', 'auto_confirm_postpaid'] as $key) {
            $rules[$key] = ['nullable', 'array'];
            foreach (HourlyCategorySettings::CHANNELS as $channel) $rules["$key.$channel"] = $bool;
        }
        return $rules;
    }

    /** وقت الإغلاق التلقائي يأتي من قائمة (يرث/معطل/وقت) + حقل وقت. */
    protected function prepareForValidation(): void
    {
        if ($this->has('auto_complete_mode')) {
            $mode = $this->input('auto_complete_mode');
            $this->merge(['auto_complete_visits_at' => $mode === 'time' ? $this->input('auto_complete_time') : $mode]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('order_form_steppers', []) as $i => $stepper) {
                if (($stepper['key'] ?? null) === 'workers' && (int) ($stepper['max'] ?? 0) > 20) {
                    $validator->errors()->add("order_form_steppers.$i.max", 'الحد الأعلى لعدد العاملات هو 20.');
                }
                if (isset($stepper['min'], $stepper['max']) && (int) $stepper['max'] < (int) $stepper['min']) {
                    $validator->errors()->add("order_form_steppers.$i.max", 'الحد الأعلى يجب ألا يقل عن الأدنى.');
                }
            }
        });
    }

    /** القيم الجاهزة للحفظ (المخصصة فقط). «inherit» يتحول إلى حذف المفتاح. */
    public function settings(): array
    {
        $input = $this->validated();
        foreach (['show_tax_separately', 'auto_assign', 'auto_assign_driver', 'auto_confirm_postpaid'] as $key) {
            foreach (HourlyCategorySettings::CHANNELS as $channel) {
                if (($input[$key][$channel] ?? null) === 'inherit') unset($input[$key][$channel]);
            }
        }
        foreach ((array) ($input['working_days'] ?? []) as $day => $row) {
            if (($row['is_on'] ?? null) === 'inherit') unset($input['working_days'][$day]['is_on']);
        }
        if (($input['auto_complete_visits_at'] ?? null) === 'inherit') unset($input['auto_complete_visits_at']);
        if (($input['same_day_cutoff'] ?? null) === 'inherit') unset($input['same_day_cutoff']);
        return app(HourlyCategorySettings::class)->normalizeInput($input + ['order_form_steppers_mode' => $this->input('order_form_steppers_mode')]);
    }
}
