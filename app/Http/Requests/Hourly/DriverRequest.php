<?php

namespace App\Http\Requests\Hourly;

use App\Models\Hourly\HourlyDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Admin authentication and route permissions apply.

    public function rules(): array
    {
        $driver = $this->route('driver');
        $id = $driver instanceof HourlyDriver ? $driver->id : null;
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'primary_team_id' => ['nullable', 'integer'],
            'secondary_team_ids' => ['nullable', 'array'],
            'secondary_team_ids.*' => ['integer', 'distinct'],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('hourly_drivers')->ignore($id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'nationality_id' => ['nullable', 'integer', 'exists:nationalities,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{5,30}$/'],
            'phone2' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{5,30}$/'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'vehicle_capacity' => ['required', 'integer', 'between:3,50'],
            'is_active' => ['required', 'boolean'],
            'account_enabled' => ['required', 'boolean'],
            'login_access' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('hourly_drivers')->ignore($id)],
            'login_phone' => ['nullable', 'regex:/^[0-9]{5,20}$/', Rule::unique('hourly_drivers')->ignore($id)],
            'password' => [Rule::requiredIf($this->boolean('account_enabled') && !$driver?->password), 'nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'work_schedule' => ['required', 'array:'.implode(',', array_keys(HourlyDriver::DAYS)), 'size:7'],
        ];
        foreach (['residency_number', 'passport_number', 'border_number'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:50'];
        }
        foreach (HourlyDriver::DATES as $field => $label) {
            $rules[$field] = ['nullable', 'date_format:Y-m-d'];
        }
        $rules['birth_date'][] = 'before:today';
        if ($this->filled('contract_start_date')) $rules['contract_end_date'][] = 'after_or_equal:contract_start_date';
        foreach (HourlyDriver::DAYS as $day => $label) {
            $base = "work_schedule.$day";
            $rules[$base] = ['required', 'array:mode,start,end'];
            $rules["$base.mode"] = ['required', Rule::in(['hours', 'all_day', 'off'])];
            $rules["$base.start"] = ["exclude_unless:$base.mode,hours", 'required', 'date_format:H:i'];
            $rules["$base.end"] = ["exclude_unless:$base.mode,hours", 'required', 'date_format:H:i', "after:$base.start"];
        }
        return $rules;
    }

    public function attributes(): array
    {
        $labels = HourlyDriver::DATES + ['name'=>'الاسم', 'employee_number'=>'رقم الموظف', 'phone'=>'الهاتف', 'phone2'=>'الهاتف الثاني', 'gender'=>'الجنس', 'nationality_id'=>'الجنسية', 'vehicle_capacity'=>'سعة السيارة', 'password'=>'كلمة المرور', 'email'=>'البريد الإلكتروني', 'login_phone'=>'هاتف تسجيل الدخول', 'work_schedule'=>'جدول العمل'];
        foreach (HourlyDriver::DAYS as $day => $label) {
            $labels["work_schedule.$day.start"] = "بداية العمل يوم $label";
            $labels["work_schedule.$day.end"] = "نهاية العمل يوم $label";
        }
        return $labels;
    }

    public function driverData(): array
    {
        $data = $this->validated();
        unset($data['primary_team_id'], $data['secondary_team_ids']);
        foreach ($data['work_schedule'] as &$day) {
            if ($day['mode'] !== 'hours') $day = ['mode' => $day['mode'], 'start' => null, 'end' => null];
        }
        unset($day);
        if (!empty($data['password'])) $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        else unset($data['password']);
        return $data;
    }
}
