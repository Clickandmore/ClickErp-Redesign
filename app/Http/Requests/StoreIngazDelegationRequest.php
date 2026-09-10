<?php

namespace App\Http\Requests;

use App\Models\IngazDelegation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngazDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $delegation = $this->route('ingazDelegation') ?? $this->route('ingaz_delegation');
        $delegationId = $delegation instanceof IngazDelegation ? $delegation->id : null;
        $activeUnique = fn (string $column) => Rule::unique('ingaz_delegations', $column)
            ->ignore($delegationId)
            ->where(fn ($query) => $query->where('status', '<>', IngazDelegation::STATUS_CANCELLED));

        return [
            'delegation_date_gregorian' => ['required', 'date'],
            'delegation_date_hijri' => ['nullable', 'string', 'max:50'],
            'country_id' => ['required', 'exists:countries,id'],
            'profession_id' => ['nullable', 'exists:job_titles,id'],

            'client_source_type' => ['required', 'in:existing_in_program,separate_client'],
            'existing_user_id' => ['required_if:client_source_type,existing_in_program', 'nullable', 'exists:users,id'],
            'separate_client_name' => ['required_if:client_source_type,separate_client', 'nullable', 'string', 'max:255'],
            'separate_client_identity_number' => ['nullable', 'string', 'max:50'],
            'separate_client_address' => ['nullable', 'string', 'max:500'],
            'separate_client_phone' => ['nullable', 'string', 'max:50'],

            'visa_type' => ['required', 'string', 'in:electronic_ingaz_delegation'],
            'worker_name' => ['nullable', 'string', 'max:255'],
            'worker_passport_number' => ['nullable', 'string', 'max:100'],
            'worker_passport_issue_date' => ['nullable', 'date'],
            'visa_number' => ['required', 'string', 'max:100', $activeUnique('visa_number')],
            'visa_date' => ['nullable', 'date'],
            'worker_religion' => ['nullable', 'in:muslim,non_muslim,unspecified'],
            'worker_experience' => ['nullable', 'in:previously_worked,not_previously_worked,appointed_by_client,unspecified,known_service'],

            'external_office_name' => ['nullable', 'string', 'max:255'],
            'external_office_license_number' => ['nullable', 'string', 'max:100'],
            'external_office_address' => ['nullable', 'string', 'max:500'],
            'external_office_phone' => ['nullable', 'string', 'max:50'],

            'marketer_id' => ['required', 'exists:admins,id'],
            'ingaz_number' => ['required', 'string', 'max:100', $activeUnique('ingaz_number')],
            'msaned_contract_number' => ['required', 'string', 'max:100', $activeUnique('msaned_contract_number')],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'replace_attachments' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visa_number' => trim((string) $this->input('visa_number')),
            'ingaz_number' => trim((string) $this->input('ingaz_number')),
            'msaned_contract_number' => trim((string) $this->input('msaned_contract_number')),
        ]);
    }

    public function messages(): array
    {
        return [
            'delegation_date_gregorian.required' => 'تاريخ تفويض الإنجاز الميلادي مطلوب.',
            'country_id.required' => 'يرجى اختيار الدولة.',
            'client_source_type.required' => 'يرجى اختيار نوع العميل.',
            'existing_user_id.required_if' => 'يرجى اختيار العميل من قائمة العملاء.',
            'separate_client_name.required_if' => 'اسم العميل المنفصل مطلوب.',
            'marketer_id.required' => 'يرجى اختيار المسوق.',
            'marketer_id.exists' => 'المسوق المختار غير موجود.',
            'visa_type.required' => 'يرجى اختيار نوع التأشيرة.',
            'visa_number.required' => 'رقم التأشيرة مطلوب.',
            'ingaz_number.required' => 'رقم إنجاز مطلوب.',
            'msaned_contract_number.required' => 'رقم عقد مساند مطلوب.',
            'visa_number.unique' => 'رقم التأشيرة مستخدم في تفويض نشط آخر.',
            'ingaz_number.unique' => 'رقم إنجاز مستخدم في تفويض نشط آخر.',
            'msaned_contract_number.unique' => 'رقم عقد مساند مستخدم في تفويض نشط آخر.',
            'attachments.*.mimes' => 'صيغة المرفق غير مدعومة.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }
}
