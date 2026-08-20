<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\ingaz\Offer;
use Illuminate\Validation\Rule;

class OfferRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $typesWithNationalities = [Offer::TYPE_NATIONALITY, Offer::TYPE_NATIONALITY_MAID];
        $typesWithMaids = [Offer::TYPE_MAID, Offer::TYPE_NATIONALITY_MAID];

        return [
            'type' => ['required', Rule::in(array_keys(Offer::types()))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            // Offer artwork is stored unchanged. Do not impose an application
            // type or size restriction; PHP/server upload limits still apply.
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'file'],
            'price_before_discount' => ['nullable', 'numeric', 'min:0'],
            'price_after_discount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // Accepts a full URL (https://...) or an internal path (/professional-workers).
            'url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                    return;
                }

                if (preg_match('#^/[^\s]*$#', $value)) {
                    return;
                }

                $fail('رابط العرض يجب أن يكون رابطًا كاملًا (https://...) أو مسارًا داخليًا يبدأ بـ /');
            }],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'nationality_ids' => [Rule::requiredIf(in_array($this->input('type'), $typesWithNationalities, true)), 'array', 'min:1'],
            'nationality_ids.*' => ['integer', 'exists:nationalities,id'],
            'maid_ids' => [Rule::requiredIf(in_array($this->input('type'), $typesWithMaids, true)), 'array', 'min:1'],
            'maid_ids.*' => ['integer', 'exists:maids,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                $this->filled('price_before_discount')
                && $this->filled('price_after_discount')
                && (float) $this->input('price_after_discount') > (float) $this->input('price_before_discount')
            ) {
                $validator->errors()->add('price_after_discount', 'السعر بعد الخصم يجب أن يكون أقل من أو يساوي السعر قبل الخصم.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'image.required' => 'اختر ملف صورة للعرض. إذا كنت قد اخترت ملفاً بالفعل، فقد يكون حجمه أكبر من الحد المسموح به على الخادم (' . ini_get('upload_max_filesize') . ').',
            'image.file' => 'تعذر قراءة الملف المرفوع. أعد اختيار الملف وحاول مرة أخرى.',
            'image.uploaded' => 'لم يكتمل رفع الملف. تحقق من اتصالك أو من أن حجمه لا يتجاوز حد الخادم (' . ini_get('upload_max_filesize') . ').',
        ];
    }
}
