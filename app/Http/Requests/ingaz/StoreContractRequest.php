<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContractRequest extends MainRequest
{
    private function canSelectAnyMarketer($admin): bool
    {
        return (bool) $admin;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $normalizedData = [];
        $currentAdmin = Auth::guard('admin')->user();

        if ($currentAdmin && (!$this->canSelectAnyMarketer($currentAdmin) || !$this->filled('marketer_id'))) {
            $normalizedData['marketer_id'] = $currentAdmin->id;
        }

        foreach (['client_id', 'new_existing_client_id'] as $clientField) {
            if ($this->filled($clientField) && !ctype_digit((string) $this->input($clientField))) {
                $normalizedData[$clientField] = null;
            }
        }

        if (
            !isset($normalizedData['new_existing_client_id'])
            && $this->filled('new_existing_client_id')
            && !$this->filled('client_id')
        ) {
            $normalizedData['client_id'] = $this->input('new_existing_client_id');
        }

        if ($this->filled('new_new_maid_id') && !$this->filled('maid_id')) {
            $normalizedData['maid_id'] = $this->input('new_new_maid_id');
        }

        foreach (['new_client_birthdate', 'client_birthdate'] as $dateField) {
            if ($this->filled($dateField)) {
                $normalizedData[$dateField] = $this->normalizeFlexibleDate($this->input($dateField));
            }
        }

        foreach ([
            'client_name',
            'client_phone',
            'client_email',
        ] as $field) {
            $newField = 'new_' . $field;
            if ($this->filled($newField) && !$this->filled($field)) {
                $normalizedData[$field] = $this->input($newField);
            }
        }

        if (
            !$this->filled('client_id')
            && !$this->filled('new_existing_client_id')
            && $this->input('client_type') === 'new_client'
            && ($normalizedData['client_phone'] ?? $this->filled('client_phone'))
        ) {
            $clientPhone = $normalizedData['client_phone'] ?? $this->input('client_phone');
            $existingClient = User::where('phone', $clientPhone)->first();

            if ($existingClient) {
                $normalizedData['client_id'] = $existingClient->id;
                $normalizedData['new_existing_client_id'] = $existingClient->id;
            }
        }

        if (!empty($normalizedData)) {
            $this->merge($normalizedData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $currentAdmin = Auth::guard('admin')->user();
        $marketerRules = ['required', 'exists:admins,id'];
        if ($currentAdmin && !$this->canSelectAnyMarketer($currentAdmin)) {
            $marketerRules[] = Rule::in([$currentAdmin->id]);
        }

        $selectedClientId = $this->input('client_id') ?: $this->input('new_existing_client_id');
        $clientPhoneRules = $selectedClientId
            ? ['nullable']
            : ['required_if:client_type,new_client', Rule::unique('users', 'phone')];

        return [
            'contract_date' => 'required',
            'msaned_number' => 'required',
            'tafwed_number' => 'nullable',
            'visa_number' => 'required',
            'visa_milad_date' => 'nullable',
            'visa_hijry_date' => 'nullable',
            'contract_duration' => 'required',
            'order_id' => 'required_if:client_type,ex_client',
            'client_type' => 'required|in:new_client,ex_client',
            'client_id' => 'nullable|exists:users,id',
            'new_existing_client_id' => 'nullable|exists:users,id',
            'client_name' => 'required_if:client_type,new_client',
            'client_phone' => $clientPhoneRules,
            'client_nationality_id' => 'required_if:client_type,new_client',
            'client_email' => 'nullable|email',
            'new_client_national_num' => 'nullable',
            'client_phone2' => 'nullable',
            'client_city' => 'nullable',
            'client_beds_num' => 'nullable',
            'client_family_num' => 'nullable',
            'client_children' => 'nullable',
            'client_job' => 'nullable',
            'client_address' => 'nullable',
            'client_birthdate' => 'nullable',
            'new_client_email' => 'nullable|email',
            'new_client_phone2' => 'nullable',
            'new_client_address' => 'nullable',
            'new_client_city' => 'nullable',
            'new_client_beds_num' => 'nullable',
            'new_client_family_num' => 'nullable',
            'new_client_children' => 'nullable',
            'new_client_job' => 'nullable',
            'new_client_birthdate' => 'nullable',
            'maid_id' => 'required_if:maid_selection_mode,existing|nullable',
            'maid_selection_mode' => 'nullable|in:existing,manual,postponed',
            'maid_selection_postponed' => 'nullable|boolean',
            'manual_maid_name' => 'required_if:maid_selection_mode,manual|nullable|string|max:255',
            'manual_maid_passport_num' => [
                'required_if:maid_selection_mode,manual',
                'nullable',
                'string',
                'max:100',
                Rule::unique('maids', 'passport_num'),
            ],
            'manual_maid_nationality_id' => 'required_if:maid_selection_mode,manual|nullable|exists:nationalities,id',
            'manual_maid_office_id' => 'required_if:maid_selection_mode,manual|nullable|exists:recruitment_offices,id',
            'manual_maid_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            'marketer_id' => $marketerRules,
            'required_maid_nationality' => 'nullable|exists:nationalities,id',
            'contract_note' => 'nullable|string|max:2000',
        ];
    }

    public function messages(){
        return [
            'contract_date.required' => 'يرجي ادخال تاريخ العقد',
            'msaned_number.required' => 'يرجي ادخال رقم مساند',
            'tafwed_number.required' => 'يرجي ادخال رقم التفويض',
            'visa_number.required' => 'يرجي ادخال رقم التأشيرة',
            'contract_duration.required' => 'يرجي ادخال مدة العقد',
            'order_id.required_if' => 'يرجي اختيار الطلب',
            'client_type.required' => 'يرجي اختيار نوع العميل',
            'client_type.in' => 'نوع العميل غير صحيح',
            'client_id.exists' => 'العميل المختار غير موجود',
            'client_name.required_if' => 'يرجي ادخال اسم العميل',
            'client_phone.required_if' => 'يرجي ادخال رقم هاتف العميل',
            'client_phone.unique' => 'رقم جوال العميل مسجل مسبقاً في النظام، يرجى اختيار العميل الموجود أو إدخال رقم آخر',

            'client_nationality_id.required_if' => 'يرجي اختيار جنسية العميل',
            'new_client_national_num.required' => 'يرجي ادخال رقم هوية العميل',
            'client_phone2.required' => 'يرجي ادخال رقم الهاتف الإضافي',
            'client_city.required' => 'يرجي ادخال المدينة',
            'client_address.required' => 'يرجي ادخال العنوان',
            'client_birthdate.required' => 'يرجي ادخال تاريخ الميلاد',
            'maid_id.required_if' => 'يرجى اختيار العاملة أو تغيير طريقة الإضافة إلى عاملة مختصرة أو تأجيل الاختيار',
            'maid_selection_mode.in' => 'طريقة إضافة العاملة غير صحيحة',
            'manual_maid_name.required_if' => 'يرجى إدخال اسم العاملة المختصر',
            'manual_maid_passport_num.required_if' => 'يرجى إدخال رقم جواز العاملة المختصر',
            'manual_maid_passport_num.unique' => 'رقم جواز العاملة المختصرة مسجل مسبقاً في النظام',
            'manual_maid_nationality_id.required_if' => 'يرجى اختيار جنسية العاملة المختصرة',
            'manual_maid_nationality_id.exists' => 'جنسية العاملة المختصرة غير موجودة',
            'manual_maid_office_id.required_if' => 'يرجى اختيار المكتب الخارجي للعاملة المختصرة',
            'manual_maid_office_id.exists' => 'المكتب الخارجي المختار غير موجود',
            'manual_maid_attachment.file' => 'مرفق العاملة المختصرة غير صالح',
            'manual_maid_attachment.max' => 'حجم مرفق العاملة المختصرة يجب ألا يتجاوز 5 ميجابايت',
            'manual_maid_attachment.mimes' => 'صيغة مرفق العاملة المختصرة غير مدعومة',
            'marketer_id.required' => 'يرجي اختيار المسوق',
            'marketer_id.exists' => 'المسوق المختار غير موجود',
            'marketer_id.in' => 'لا يمكنك اختيار مسوق مختلف عن المستخدم الحالي',
            'required_maid_nationality.exists' => 'الجنسية المختارة غير موجودة',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->ajax() || $this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'يرجى مراجعة الحقول المطلوبة',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    private function normalizeFlexibleDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '/' => '-', '.' => '-',
        ]);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        return $value;
    }
}
