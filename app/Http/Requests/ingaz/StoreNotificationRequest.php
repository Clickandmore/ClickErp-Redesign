<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Validator;

class StoreNotificationRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'nullable|in:sms,normal',
            'sms_template_id' => 'required_unless:type,normal|integer|exists:sms_templates,id',
            'sender_name_type' => 'required_if:type,sms|in:advertising,verification',
            'template_variables' => 'nullable|array',
            'template_variables.*' => 'nullable|string|max:255',
            'message_override' => 'nullable|string|max:1000',
            'all_or_some' => 'required|in:all,some',
            'user_id' => 'required_if:all_or_some,some|array|min:1',
            'user_id.*' => 'integer|exists:users,id',
            'confirm_bulk_send' => 'nullable|boolean',
            'bulk_confirmation_text' => 'nullable|string|max:50',
            'title' => 'required_if:type,normal|array',
            'title.*' => 'nullable|string|max:255',
            'description' => 'required_if:type,normal|array',
            'description.*' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type', 'sms');

            if ($type !== 'sms') {
                return;
            }

            if ($this->input('all_or_some') !== 'all') {
                return;
            }

            if (!$this->boolean('confirm_bulk_send')) {
                $validator->errors()->add('confirm_bulk_send', 'يجب تفعيل تأكيد الإرسال الجماعي قبل المتابعة.');
            }

            $expectedKeyword = (string) config('msegat.bulk_confirmation_keyword', 'SEND_ALL_SMS');
            $providedKeyword = strtoupper(trim((string) $this->input('bulk_confirmation_text')));

            if ($providedKeyword !== strtoupper($expectedKeyword)) {
                $validator->errors()->add('bulk_confirmation_text', 'نص التأكيد غير صحيح.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'sms_template_id.required_unless' => 'اختيار قالب رسالة نصية مطلوب.',
            'sms_template_id.exists' => 'القالب المحدد غير موجود.',
            'sender_name_type.required_if' => 'اختيار نوع اسم المرسل مطلوب.',
            'sender_name_type.in' => 'نوع اسم المرسل غير صحيح.',
            'all_or_some.required' => 'حدد نطاق الإرسال.',
            'all_or_some.in' => 'نطاق الإرسال غير صحيح.',
            'user_id.required_if' => 'اختر عميلًا واحدًا على الأقل.',
            'user_id.array' => 'اختيار العملاء غير صحيح.',
            'user_id.*.exists' => 'أحد العملاء المحددين غير موجود.',
            'message_override.max' => 'نص الرسالة طويل جدًا.',
        ];
    }
}
