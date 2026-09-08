<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\SmsTemplate;
use App\Support\CommunicationSettings;
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
            'type' => 'nullable|in:sms,normal,whatsapp',
            'sms_template_id' => 'nullable|string|max:50',
            'sender_name_type' => 'required_if:type,sms|in:advertising,verification',
            'whatsapp_purpose' => 'required_if:type,whatsapp|in:verification,follow_up,advertising',
            'whatsapp_sender_key' => 'nullable|string|max:100',
            'chatwoot_template' => 'nullable|string|max:255',
            'chatwoot_variables' => 'nullable|array|max:30',
            'chatwoot_variables.*' => 'nullable|string|max:1000',
            'template_variables' => 'nullable|array',
            'template_variables.*' => 'nullable|string|max:255',
            'message_override' => 'nullable|string|max:1000|required_if:type,whatsapp',
            'all_or_some' => 'required|in:all,some',
            'user_id' => 'nullable|array',
            'user_id.*' => 'integer|exists:users,id',
            'external_recipient_phones' => 'nullable|string|max:10000',
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

            if ($type === 'whatsapp') {
                $whatsAppSettings = CommunicationSettings::whatsapp();
                $purpose = (string) $this->input('whatsapp_purpose', $whatsAppSettings['default_purpose'] ?? 'verification');
                $allowAdvertising = (bool) ($whatsAppSettings['allow_advertising'] ?? false);

                if ($purpose === 'advertising' && !$allowAdvertising) {
                    $validator->errors()->add('whatsapp_purpose', 'الإرسال الدعائي عبر واتساب غير مفعل حاليًا.');
                }
            }

            if ($type === 'sms') {
                $templateId = $this->input('sms_template_id');

                if ($this->usesSmsTemplate($templateId)) {
                    if (!ctype_digit((string) $templateId) || !SmsTemplate::query()->whereKey($templateId)->where('is_active', true)->exists()) {
                        $validator->errors()->add('sms_template_id', 'القالب المحدد غير موجود أو غير نشط.');
                    }
                } elseif (trim((string) $this->input('message_override', '')) === '') {
                    $validator->errors()->add('message_override', 'نص الرسالة مطلوب عند الإرسال بدون قالب.');
                }

                if ($this->input('all_or_some') === 'some') {
                    $invalidExternalPhones = $this->invalidExternalRecipientPhones();
                    if (!empty($invalidExternalPhones)) {
                        $validator->errors()->add(
                            'external_recipient_phones',
                            'توجد أرقام خارجية غير صحيحة: ' . implode('، ', array_slice($invalidExternalPhones, 0, 5))
                        );
                    }
                }
            }

            if ($this->input('all_or_some') !== 'all') {
                $selectedUsersCount = collect((array) $this->input('user_id', []))
                    ->filter()
                    ->unique()
                    ->count();

                $externalPhonesCount = $type === 'sms' ? count($this->validExternalRecipientPhones()) : 0;

                if ($selectedUsersCount + $externalPhonesCount === 0) {
                    $validator->errors()->add(
                        'user_id',
                        $type === 'sms'
                            ? 'اختر عميلاً من النظام أو أضف رقمًا خارجيًا واحدًا على الأقل.'
                            : 'اختر عميلًا واحدًا على الأقل.'
                    );
                }

                return;
            }

            if (!in_array($type, ['sms', 'whatsapp'], true)) {
                return;
            }

            if (!$this->boolean('confirm_bulk_send')) {
                $validator->errors()->add('confirm_bulk_send', 'يجب تفعيل تأكيد الإرسال الجماعي قبل المتابعة.');
            }

            $expectedKeyword = $this->resolveExpectedKeyword($type);
            $providedKeyword = strtoupper(trim((string) $this->input('bulk_confirmation_text')));

            if ($providedKeyword !== strtoupper($expectedKeyword)) {
                $validator->errors()->add('bulk_confirmation_text', 'نص التأكيد غير صحيح.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'sms_template_id.required_if' => 'اختيار قالب رسالة نصية مطلوب.',
            'sms_template_id.exists' => 'القالب المحدد غير موجود.',
            'sender_name_type.required_if' => 'اختيار نوع اسم المرسل مطلوب.',
            'sender_name_type.in' => 'نوع اسم المرسل غير صحيح.',
            'whatsapp_purpose.required_if' => 'اختيار غرض رسالة واتساب مطلوب.',
            'whatsapp_purpose.in' => 'غرض رسالة واتساب يجب أن يكون تحقق أو متابعة أو دعائي.',
            'all_or_some.required' => 'حدد نطاق الإرسال.',
            'all_or_some.in' => 'نطاق الإرسال غير صحيح.',
            'user_id.required_if' => 'اختر عميلًا واحدًا على الأقل.',
            'user_id.array' => 'اختيار العملاء غير صحيح.',
            'user_id.*.exists' => 'أحد العملاء المحددين غير موجود.',
            'message_override.required_if' => 'نص الرسالة مطلوب لواتساب.',
            'message_override.max' => 'نص الرسالة طويل جدًا.',
        ];
    }

    private function resolveExpectedKeyword(string $type): string
    {
        if ($type === 'whatsapp') {
            return (string) (CommunicationSettings::whatsapp()['bulk_confirmation_keyword'] ?? 'SEND_ALL_WHATSAPP');
        }

        return (string) (CommunicationSettings::sms()['bulk_confirmation_keyword'] ?? 'SEND_ALL_SMS');
    }

    private function usesSmsTemplate($templateId): bool
    {
        return !in_array((string) $templateId, ['', 'none'], true);
    }

    private function validExternalRecipientPhones(): array
    {
        return collect($this->externalRecipientPhoneLines())
            ->map(fn (string $phone): string => $this->normalizeSmsPhone($phone))
            ->filter(fn (string $phone): bool => $this->isValidSmsPhone($phone))
            ->unique()
            ->values()
            ->all();
    }

    private function invalidExternalRecipientPhones(): array
    {
        return collect($this->externalRecipientPhoneLines())
            ->filter(fn (string $phone): bool => !$this->isValidSmsPhone($this->normalizeSmsPhone($phone)))
            ->values()
            ->all();
    }

    private function externalRecipientPhoneLines(): array
    {
        $phones = preg_split('/[\r\n,;]+/', (string) $this->input('external_recipient_phones', '')) ?: [];

        return array_values(array_filter(array_map('trim', $phones), fn (string $phone): bool => $phone !== ''));
    }

    private function normalizeSmsPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '05')) {
            return '966' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '966' . $digits;
        }

        return formatSaudiPhone($digits);
    }

    private function isValidSmsPhone(string $phone): bool
    {
        return (bool) preg_match('/^\d{8,15}$/', $phone);
    }
}
