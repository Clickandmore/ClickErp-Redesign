<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIngazDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delegation_date_gregorian' => ['required', 'date'],
            'delegation_date_hijri' => ['required', 'string', 'max:50'],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'profession_id' => ['required', 'exists:job_titles,id'],

            'client_source_type' => ['required', 'in:existing_in_program,separate_client'],
            'existing_user_id' => ['required_if:client_source_type,existing_in_program', 'nullable', 'exists:users,id'],
            'separate_client_name' => ['required_if:client_source_type,separate_client', 'nullable', 'string', 'max:255'],
            'separate_client_identity_number' => ['required_if:client_source_type,separate_client', 'nullable', 'string', 'max:50'],
            'separate_client_address' => ['required_if:client_source_type,separate_client', 'nullable', 'string', 'max:500'],
            'separate_client_phone' => ['required_if:client_source_type,separate_client', 'nullable', 'string', 'max:50'],

            'visa_type' => ['required', 'string', 'in:electronic_ingaz_delegation'],
            'worker_name' => ['required', 'string', 'max:255'],
            'worker_passport_number' => ['required', 'string', 'max:100'],
            'worker_passport_issue_date' => ['nullable', 'date'],
            'visa_number' => ['required', 'string', 'max:100'],
            'visa_date' => ['nullable', 'date'],
            'worker_religion' => ['required', 'in:muslim,non_muslim,unspecified'],
            'worker_experience' => ['required', 'in:previously_worked,not_previously_worked,appointed_by_client,unspecified,known_service'],

            'external_office_name' => ['required', 'string', 'max:255'],
            'external_office_license_number' => ['required', 'string', 'max:100'],
            'external_office_address' => ['required', 'string', 'max:500'],
            'external_office_phone' => ['required', 'string', 'max:50'],

            'marketer_id' => ['required', 'exists:admins,id'],
            'ingaz_number' => ['required', 'string', 'max:100'],
            'msaned_contract_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'replace_attachments' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'delegation_date_gregorian.required' => 'تاريخ تفويض الإنجاز الميلادي مطلوب.',
            'delegation_date_hijri.required' => 'تاريخ تفويض الإنجاز الهجري مطلوب.',
            'nationality_id.required' => 'يرجى اختيار الجنسية.',
            'profession_id.required' => 'يرجى اختيار المهنة.',
            'client_source_type.required' => 'يرجى اختيار نوع العميل.',
            'existing_user_id.required_if' => 'يرجى اختيار العميل من قائمة العملاء.',
            'separate_client_name.required_if' => 'اسم العميل المنفصل مطلوب.',
            'separate_client_identity_number.required_if' => 'رقم هوية العميل المنفصل مطلوب.',
            'separate_client_address.required_if' => 'عنوان العميل المنفصل مطلوب.',
            'separate_client_phone.required_if' => 'جوال العميل المنفصل مطلوب.',
            'worker_name.required' => 'اسم العاملة مطلوب.',
            'visa_type.required' => 'يرجى اختيار نوع التأشيرة.',
            'worker_passport_number.required' => 'رقم جواز العاملة مطلوب.',
            'visa_number.required' => 'رقم التأشيرة مطلوب.',
            'worker_religion.required' => 'يرجى اختيار الديانة.',
            'worker_experience.required' => 'يرجى اختيار الخبرة.',
            'external_office_name.required' => 'اسم المكتب الخارجي مطلوب.',
            'external_office_license_number.required' => 'رقم ترخيص المكتب مطلوب.',
            'external_office_address.required' => 'عنوان المكتب مطلوب.',
            'external_office_phone.required' => 'هاتف المكتب مطلوب.',
            'marketer_id.required' => 'يرجى اختيار المسوق.',
            'ingaz_number.required' => 'رقم إنجاز مطلوب.',
            'msaned_contract_number.required' => 'رقم عقد مساند مطلوب.',
            'attachments.*.mimes' => 'صيغة المرفق غير مدعومة.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }
}
