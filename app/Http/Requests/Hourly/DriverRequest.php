<?php

namespace App\Http\Requests\Hourly;

use App\Models\Hourly\HourlyDriver;
use App\Support\HourlyDriverFieldRules as F;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Admin authentication and route permissions apply.

    /** توحيد المدخلات قبل التحقق حتى لا يمر المكرر بصيغة مختلفة (مسافات، أرقام عربية، +966). */
    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'address'] as $key) {
            if (is_string($this->input($key))) $clean[$key] = preg_replace('/\s+/u', ' ', trim($this->input($key)));
        }
        foreach (['employee_number', 'residency_number', 'border_number'] as $key) {
            if (is_string($this->input($key))) $clean[$key] = preg_replace('/\s+/', '', F::latinDigits($this->input($key)));
        }
        foreach (['phone', 'phone2'] as $key) {
            if (is_string($this->input($key))) $clean[$key] = F::normalizePhone($this->input($key));
        }
        // هاتف الدخول يكتبه السائق كما هو عند تسجيل الدخول؛ لا نغيّر صيغته
        if (is_string($this->input('login_phone'))) $clean['login_phone'] = trim(F::latinDigits($this->input('login_phone')));
        if (is_string($this->input('passport_number'))) $clean['passport_number'] = strtoupper(preg_replace('/\s+/', '', F::latinDigits($this->input('passport_number'))));
        if (is_string($this->input('email'))) $clean['email'] = strtolower(trim($this->input('email')));
        $this->merge($clean);
    }

    public function rules(): array
    {
        $driver = $this->route('driver');
        $id = $driver instanceof HourlyDriver ? $driver->id : null;
        // يشمل السائقين المحذوفين: الاسترجاع لا يجب أن يُنتج بيانات مكررة
        $unique = fn () => Rule::unique('hourly_drivers')->ignore($id);
        $rules = [
            'name' => ['required', 'string', 'min:'.F::NAME_MIN, 'max:'.F::NAME_MAX, 'regex:'.F::NAME_REGEX],
            'primary_team_id' => ['nullable', 'integer'],
            'secondary_team_ids' => ['nullable', 'array'],
            'secondary_team_ids.*' => ['integer', 'distinct'],
            'employee_number' => ['required', 'string', 'max:'.F::EMPLOYEE_NUMBER_MAX, 'regex:'.F::EMPLOYEE_NUMBER_REGEX, $unique()],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'nationality_id' => ['nullable', 'integer', 'exists:nationalities,id'],
            'address' => ['nullable', 'string', 'max:'.F::ADDRESS_MAX],
            'phone' => ['required', 'string', 'regex:'.F::PHONE_REGEX, $this->uniquePhone($id)],
            'phone2' => ['nullable', 'string', 'regex:'.F::PHONE_REGEX, 'different:phone', $this->uniquePhone($id)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'vehicle_capacity' => ['required', 'integer', 'between:3,50'],
            'is_active' => ['required', 'boolean'],
            'account_enabled' => ['required', 'boolean'],
            'login_access' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'email' => ['nullable', 'string', 'max:'.F::EMAIL_MAX, 'regex:'.F::EMAIL_REGEX, $unique()],
            'login_phone' => ['nullable', 'regex:/^[0-9]{5,20}$/', $unique()],
            'password' => [Rule::requiredIf($this->boolean('account_enabled') && !$driver?->password), 'nullable', 'string', 'min:'.F::PASSWORD_MIN, 'max:'.F::PASSWORD_MAX, 'confirmed'],
            'work_schedule' => ['required', 'array:'.implode(',', array_keys(HourlyDriver::DAYS)), 'size:7'],
            'residency_number' => ['nullable', 'required_with:residency_expiry_date', 'regex:'.F::RESIDENCY_REGEX, $unique()],
            'passport_number' => ['nullable', 'required_with:passport_expiry_date', 'regex:'.F::PASSPORT_REGEX, $unique()],
            'border_number' => ['nullable', 'regex:'.F::BORDER_REGEX, $unique()],
        ];
        foreach (HourlyDriver::DATES as $field => $label) {
            $rules[$field] = ['nullable', 'date_format:Y-m-d'];
        }
        $rules['birth_date'] = array_merge($rules['birth_date'], ['after_or_equal:'.F::birthDateMin(), 'before_or_equal:'.F::birthDateMax()]);
        $rules['entry_date'][] = 'before_or_equal:today';
        if ($this->filled('birth_date')) $rules['entry_date'][] = 'after:birth_date';
        // تاريخ الميلاد هو المرجع: بدء العمل وبداية العقد بعد بلوغ سن الرخصة
        if ($workMin = $this->workAgeDate()) {
            $rules['work_start_date'][] = 'after_or_equal:'.$workMin;
            $rules['contract_start_date'][] = 'after_or_equal:'.$workMin;
        }
        $rules['residency_expiry_date'] = array_merge($rules['residency_expiry_date'], ['required_with:residency_number', 'before_or_equal:'.F::expiryMax()]);
        $rules['passport_expiry_date'] = array_merge($rules['passport_expiry_date'], ['required_with:passport_number', 'before_or_equal:'.F::expiryMax()]);
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

    /** التاريخ الذي يبلغ فيه السائق MIN_AGE سنة، إن كان تاريخ الميلاد صالحًا. */
    private function workAgeDate(): ?string
    {
        $birth = $this->input('birth_date');
        if (!is_string($birth) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) return null;
        try {
            return \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $birth)->addYears(F::MIN_AGE)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** رقم الجوال لا يتكرر بين سائقين، سواء سُجّل عندهم هاتفًا أساسيًا أو ثانيًا. */
    private function uniquePhone(?int $id): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($id) {
            if (blank($value)) return;
            $exists = HourlyDriver::withTrashed()->when($id, fn ($q) => $q->whereKeyNot($id))
                ->where(fn ($q) => $q->where('phone', $value)->orWhere('phone2', $value))->exists();
            if ($exists) $fail('رقم الجوال '.$value.' مسجّل لسائق آخر.');
        };
    }

    public function messages(): array
    {
        $min = F::MIN_AGE; $max = F::MAX_AGE;
        return [
            'name.required' => 'برجاء إدخال اسم السائق.',
            'name.min' => 'الاسم يجب ألا يقل عن '.F::NAME_MIN.' أحرف.',
            'name.max' => 'الاسم يجب ألا يتجاوز '.F::NAME_MAX.' حرفًا.',
            'name.regex' => 'الاسم يقبل الحروف العربية أو الإنجليزية والمسافات فقط، دون أرقام أو رموز.',
            'employee_number.required' => 'برجاء إدخال رقم الموظف.',
            'employee_number.max' => 'رقم الموظف يجب ألا يتجاوز '.F::EMPLOYEE_NUMBER_MAX.' رقمًا.',
            'employee_number.regex' => 'رقم الموظف يقبل الأرقام فقط.',
            'employee_number.unique' => 'رقم الموظف مستخدم لسائق آخر.',
            'phone.required' => 'برجاء إدخال رقم الجوال.',
            'phone.regex' => 'رقم الجوال يجب أن يكون أرقامًا فقط ويبدأ بـ 05 ويتكون من 10 أرقام.',
            'phone2.regex' => 'رقم الجوال الثاني يجب أن يكون أرقامًا فقط ويبدأ بـ 05 ويتكون من 10 أرقام.',
            'phone2.different' => 'رقم الجوال الثاني يجب أن يختلف عن رقم الجوال الأساسي.',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز '.F::EMAIL_MAX.' حرفًا.',
            'email.regex' => 'البريد الإلكتروني غير صحيح؛ يُكتب بحروف إنجليزية فقط مثل name@example.com.',
            'email.unique' => 'البريد الإلكتروني مستخدم لسائق آخر.',
            'login_phone.regex' => 'هاتف تسجيل الدخول يقبل الأرقام فقط (من 5 إلى 20 رقمًا).',
            'login_phone.unique' => 'هاتف تسجيل الدخول مستخدم لسائق آخر.',
            'password.required' => 'برجاء إدخال كلمة المرور لتفعيل حساب السائق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن '.F::PASSWORD_MIN.' أحرف.',
            'password.max' => 'كلمة المرور يجب ألا تتجاوز '.F::PASSWORD_MAX.' حرفًا.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق لكلمة المرور.',
            'birth_date.after_or_equal' => "عمر السائق يجب ألا يتجاوز $max سنة.",
            'birth_date.before_or_equal' => "عمر السائق يجب ألا يقل عن $min سنة (سن استخراج رخصة القيادة).",
            'entry_date.before_or_equal' => 'تاريخ الدخول للمملكة لا يمكن أن يكون في المستقبل.',
            'entry_date.after' => 'تاريخ الدخول للمملكة يجب أن يكون بعد تاريخ الميلاد.',
            'residency_number.required_with' => 'أدخل رقم الإقامة مع تاريخ انتهائها.',
            'residency_number.regex' => 'رقم الإقامة يتكون من 10 أرقام ويبدأ بالرقم 2.',
            'residency_number.unique' => 'رقم الإقامة مسجّل لسائق آخر.',
            'residency_expiry_date.required_with' => 'أدخل تاريخ انتهاء الإقامة مع رقمها.',
            'residency_expiry_date.before_or_equal' => 'تاريخ انتهاء الإقامة بعيد جدًا؛ تأكد من السنة.',
            'passport_number.required_with' => 'أدخل رقم الجواز مع تاريخ انتهائه.',
            'passport_number.regex' => 'رقم الجواز من 6 إلى 12 حرفًا إنجليزيًا أو رقمًا، دون مسافات أو رموز.',
            'passport_number.unique' => 'رقم الجواز مسجّل لسائق آخر.',
            'passport_expiry_date.required_with' => 'أدخل تاريخ انتهاء الجواز مع رقمه.',
            'passport_expiry_date.before_or_equal' => 'تاريخ انتهاء الجواز بعيد جدًا؛ تأكد من السنة.',
            'border_number.regex' => 'رقم الحدود يتكون من 10 أرقام ويبدأ بالرقم 3 أو 4.',
            'border_number.unique' => 'رقم الحدود مسجّل لسائق آخر.',
            'contract_end_date.after_or_equal' => 'تاريخ نهاية العقد لا يسبق تاريخ بدايته.',
            'work_start_date.after_or_equal' => "تاريخ بدء العمل يجب ألا يسبق بلوغ السائق $min سنة حسب تاريخ الميلاد.",
            'contract_start_date.after_or_equal' => "تاريخ بداية العقد يجب ألا يسبق بلوغ السائق $min سنة حسب تاريخ الميلاد.",
        ];
    }

    public function attributes(): array
    {
        $labels = HourlyDriver::DATES + ['name'=>'الاسم', 'employee_number'=>'رقم الموظف', 'phone'=>'رقم الجوال', 'phone2'=>'رقم الجوال الثاني', 'gender'=>'الجنس', 'nationality_id'=>'الجنسية', 'vehicle_capacity'=>'سعة السيارة', 'password'=>'كلمة المرور', 'email'=>'البريد الإلكتروني', 'login_phone'=>'هاتف تسجيل الدخول', 'work_schedule'=>'جدول العمل', 'residency_number'=>'رقم الإقامة', 'passport_number'=>'رقم الجواز', 'border_number'=>'رقم الحدود', 'address'=>'العنوان'];
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
