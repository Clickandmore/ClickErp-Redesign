<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class MaidRequest extends MainRequest
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
            'name:en' => 'required|string|max:255',
            'cv_file' => 'nullable',
            'video' => 'nullable',
            'video_link' => 'nullable',
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => [
                'required',
                Rule::unique('maids')->ignore($this->route('maid')),
            ],
            'price' => 'nullable|numeric',
            'salary' => 'nullable|numeric',
            'experience_years' => 'nullable',
            'experience_status' => 'required|in:has_experience,no_experience',
            'religion_id' => 'nullable|exists:religions,id',
            'age' => 'nullable|numeric|min:20|max:50',
            'childrens_count' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'additional_notes' => 'nullable',
            'birth_date' => 'nullable|date',
            'available_at' => 'nullable|date',
            'job_id' => 'required|array',
            'job_id.*' => 'required|exists:job_titles,id',
            'type_id' => 'required|exists:job_types,id',
            'office_id' => 'nullable|exists:recruitment_offices,id',
            'languages' => 'nullable|array',
            'percentage' => 'nullable|array',
            'expertises' => 'nullable|array',
            'rental_finance_items' => 'nullable|array',
            'rental_finance_items.*.type' => 'nullable|in:income,expense',
            'rental_finance_items.*.label' => 'nullable|string|max:150',
            'rental_finance_items.*.amount' => 'nullable|numeric|min:0',
            'rental_finance_items_confirmed' => 'nullable|boolean',
            'request_type' => 'nullable|in:known,specific,custom_spec',
            'offer_price' => 'nullable|numeric|min:0',
            'offer_duration' => 'nullable|string|max:255',
            'offer_description' => 'nullable|string',
            'offer_starts_at' => 'nullable|date',
            'offer_expires_at' => 'nullable|date|after_or_equal:offer_starts_at',
            'arrival_date' => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'name:en.required' => 'اسم العاملة مطلوب',
            'name:en.string' => 'اسم العاملة يجب أن يكون نص',
            'name:en.max' => 'اسم العاملة يجب أن لا يتجاوز 255 حرف',
            'cv_file.required' => helperTrans('web.cv_file_is_required'),
            'nationality_id.required' => helperTrans('web.nationality_id_is_required'),
            'nationality_id.exists' => helperTrans('web.invalid_nationality_id'),
            'passport_num.required' => helperTrans('web.passport_num_is_required'),
            'passport_num.min' => helperTrans('web.passport_num_min_length'),
            'passport_num.unique' => 'رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر',
            'price.numeric' => helperTrans('web.price_must_be_numeric'),
            'salary.numeric' => helperTrans('web.salary_must_be_numeric'),
            'religion_id.required' => helperTrans('web.religion_id_is_required'),
            'religion_id.exists' => helperTrans('web.invalid_religion_id'),
            'age.numeric' => helperTrans('web.age_must_be_numeric'),
            'age.min' => __('web.age_min', ['min'=> '20']),
            'age.max' => __('web.age_max', ['max'=> '50']),
            'job_id.required' => helperTrans('web.job_id_is_required'),
            'job_id.array' => helperTrans('web.job_id_must_be_an_array'),
            'job_id.*.required' => helperTrans('web.each_job_id_is_required'),
            'job_id.*.exists' => helperTrans('web.invalid_job_id'),
            'type_id.required' => helperTrans('web.type_id_is_required'),
            'type_id.exists' => helperTrans('web.invalid_type_id'),
            'office_id.exists' => helperTrans('web.office_id not exist'),
            'offer_price.numeric' => helperTrans('web.price_must_be_numeric'),
            'offer_price.min' => helperTrans('web.price_must_be_numeric'),
            'offer_expires_at.after_or_equal' => 'تاريخ انتهاء العرض يجب أن يكون بعد أو مساوي لتاريخ بداية العرض',
            'offer_duration.max' => 'مدة العرض يجب ألا تتجاوز 255 حرفاً',
            'arrival_date.required' => 'تاريخ وصول العاملة مطلوب',
            'arrival_date.date' => 'تاريخ وصول العاملة يجب أن يكون تاريخاً صالحاً',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $typeId = (int) $this->input('type_id');
            if ($typeId <= 0) {
                return;
            }

            $jobType = \App\Models\ingaz\JobType::find($typeId);
            $title = mb_strtolower(trim((string) ($jobType?->title ?? '')));
            $normalizedTitle = str_replace(['أ', 'إ', 'آ'], 'ا', $title);

            $isRentalType = $typeId === 2
                || str_contains($normalizedTitle, 'ايجار')
                || str_contains($normalizedTitle, 'تاجير')
                || str_contains($normalizedTitle, 'rent');

            if ($isRentalType && !$this->boolean('rental_finance_items_confirmed')) {
                $validator->errors()->add(
                    'rental_finance_items_confirmed',
                    'يرجى تأكيد بنود التأجير من النافذة المنبثقة قبل حفظ العاملة'
                );
            }
        });
    }
}
