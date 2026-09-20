<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\Country;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Support\ExperiencePeriod;
use App\Services\BranchAccessService;
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
            'is_show' => 'sometimes|boolean',
            'name:en' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'cv_file' => 'nullable',
            'maid_cv_file' => 'nullable|file|mimes:pdf|max:10240',
            'video' => 'nullable|file|mimes:mp4,mov,avi,wmv,webm,mkv,m4v|max:102400',
            'remove_video' => 'nullable|boolean',
            'video_link' => 'nullable|url|max:2000',
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => [
                'required',
                Rule::unique('maids')->ignore($this->route('maid')),
            ],
            'phone' => 'nullable|string|max:50',
            'relative_name' => 'nullable|string|max:255',
            'relative_phone' => 'nullable|string|max:50',
            'residence_city' => 'nullable|string|max:255',
            'passport_issue_place' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'education_en' => 'nullable|string|max:255',
            'has_driving_license' => 'nullable|boolean',
            'driving_license_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'price' => 'nullable|numeric',
            'salary' => 'nullable|numeric',
            'experience_years' => 'nullable',
            'experience_status' => 'required|in:has_experience,no_experience',
            'cooking_experience' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('experience_status') === 'has_experience'),
                Rule::in([
                    'arabic_cooking',
                    'regular_cooking',
                    'willing_to_learn',
                    'general_cooking',
                    'no_cooking_experience',
                ]),
            ],
            'religion_id' => 'nullable|exists:religions,id',
            'id_number' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9A-Za-z-]{5,30}$/',
            ],
            'id_expiry_date' => [
                'nullable',
                'date',
            ],
            'border_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'passport_issue_date' => [
                'nullable',
                'date',
            ],
            'passport_expiry_date' => [
                'nullable',
                'date',
                Rule::when($this->filled('passport_issue_date'), 'after_or_equal:passport_issue_date'),
            ],
            'age' => 'nullable|numeric|min:18|max:90',
            'childrens_count' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'additional_notes' => 'nullable',
            'birth_date' => 'nullable|date',
            'available_at' => 'nullable|date',
            'job_id' => 'required|array',
            'job_id.*' => 'required|exists:job_titles,id',
            'type_id' => 'required|exists:job_types,id',
            'office_id' => [
                Rule::requiredIf(fn () => $this->requiresOfficeField()),
                'nullable',
                'exists:recruitment_offices,id',
            ],
            'branch_id' => [
                Rule::requiredIf(fn () => $this->isSelectedRentalType() && app(BranchAccessService::class)->enabled()),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'languages' => 'nullable|array',
            'percentage' => 'nullable|array',
            'percentage.*' => 'nullable|numeric|min:0|max:100',
            'language_rating_mode' => 'nullable|in:percentage,level',
            'language_levels' => 'nullable|array',
            'language_levels.*' => 'nullable|in:acceptable,average,good,excellent',
            'expertises' => 'nullable|array',
            'expertises.*.country_id' => 'nullable|exists:countries,id',
            'expertises.*.number_of_years' => 'nullable|numeric|min:0',
            'expertises.*.job_title_id' => 'nullable|exists:job_titles,id',
            'expertises.*.work_city' => 'nullable|string|max:255',
            // <input type="month"> يرسل "YYYY-MM"
            'expertises.*.start_date' => 'nullable|date_format:Y-m',
            'expertises.*.end_date' => 'nullable|date_format:Y-m',
            'rental_finance_items' => 'nullable|array',
            'rental_finance_items.*.account_id' => 'nullable|exists:fn_accounts,id',
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
            'rental_kingdom_status' => [
                Rule::requiredIf(fn () => $this->isSelectedRentalType()),
                'nullable',
                'in:inside,outside',
            ],
            'arrival_date' => [
                Rule::requiredIf(fn () => $this->isSelectedRentalType() && $this->input('rental_kingdom_status') === 'inside'),
                'nullable',
                'date',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $data = [
            'video_link' => \App\Support\MaidVideoLink::normalize($this->input('video_link')),
            'cooking_experience' => $this->input('experience_status') === 'has_experience'
                ? $this->input('cooking_experience')
                : null,
        ];

        if ($this->isSelectedRentalType()) {
            $branchAccess = app(BranchAccessService::class);
            $forcedBranchId = $branchAccess->enabled() && !$branchAccess->canViewAllBranches()
                ? $branchAccess->adminBranchId()
                : null;
            $data = array_merge($data, [
                'request_type' => Maid::REQUEST_TYPE_CUSTOM,
                'rental_kingdom_status' => $this->input('rental_kingdom_status'),
                'rental_finance_items_confirmed' => true,
                'branch_id' => $forcedBranchId ?: $this->input('branch_id'),
            ]);

            if (($data['rental_kingdom_status'] ?? null) === 'outside') {
                $data['arrival_date'] = null;
            }
        } else {
            $data = array_merge($data, [
                'rental_kingdom_status' => null,
                'arrival_date' => null,
                'branch_id' => null,
            ]);
        }

        $this->merge($data);
    }

    public function messages()
    {
        return [
            'name:en.required' => 'اسم العاملة مطلوب',
            'name:en.string' => 'اسم العاملة يجب أن يكون نص',
            'name:en.max' => 'اسم العاملة يجب أن لا يتجاوز 255 حرف',
            'cv_file.required' => helperTrans('web.cv_file_is_required'),
            'maid_cv_file.file' => 'ملف سيرة العاملة من المكتب الخارجي غير صالح',
            'maid_cv_file.mimes' => 'يجب أن يكون ملف سيرة العاملة من المكتب الخارجي بصيغة PDF',
            'maid_cv_file.max' => 'حجم ملف سيرة العاملة من المكتب الخارجي يجب ألا يتجاوز 10 ميجابايت',
            'driving_license_file.file' => 'مرفق رخصة القيادة غير صالح',
            'driving_license_file.mimes' => 'يجب أن يكون مرفق رخصة القيادة بصيغة PDF أو صورة (JPG, PNG, WEBP)',
            'driving_license_file.max' => 'حجم مرفق رخصة القيادة يجب ألا يتجاوز 10 ميجابايت',
            'video.file' => 'ملف الفيديو المرفوع غير صالح.',
            'video.uploaded' => 'تعذّر رفع ملف الفيديو. تحقق من أن حجمه لا يتجاوز 100 ميجابايت ثم حاول مرة أخرى.',
            'video.mimes' => 'صيغة الفيديو غير مدعومة. الصيغ المسموحة: MP4 وMOV وAVI وWMV وWEBM وMKV وM4V.',
            'video.max' => 'حجم الفيديو يجب ألا يتجاوز 100 ميجابايت.',
            'video_link.url' => 'رابط الفيديو غير صالح. يرجى إدخال رابط كامل يبدأ بـ http أو https.',
            'video_link.max' => 'رابط الفيديو طويل جدًا.',
            'nationality_id.required' => helperTrans('web.nationality_id_is_required'),
            'nationality_id.exists' => helperTrans('web.invalid_nationality_id'),
            'passport_num.required' => helperTrans('web.passport_num_is_required'),
            'passport_num.min' => helperTrans('web.passport_num_min_length'),
            'passport_num.unique' => 'رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر',
            'price.numeric' => helperTrans('web.price_must_be_numeric'),
            'salary.numeric' => helperTrans('web.salary_must_be_numeric'),
            'religion_id.required' => helperTrans('web.religion_id_is_required'),
            'religion_id.exists' => helperTrans('web.invalid_religion_id'),
            'id_number.max' => 'رقم الهوية يجب ألا يتجاوز 30 خانة',
            'id_number.regex' => 'يجب إدخال رقم هوية صالح من 5 إلى 30 خانة باستخدام الأرقام أو الحروف أو الشرطة (-)',
            'id_expiry_date.date' => 'تاريخ انتهاء الهوية يجب أن يكون تاريخاً صالحاً',
            'border_number.max' => 'رقم الحدود يجب ألا يتجاوز 50 خانة',
            'passport_issue_date.date' => 'تاريخ إصدار جواز السفر يجب أن يكون تاريخاً صالحاً',
            'passport_expiry_date.date' => 'تاريخ انتهاء جواز السفر يجب أن يكون تاريخاً صالحاً',
            'passport_expiry_date.after_or_equal' => 'تاريخ انتهاء جواز السفر يجب أن يكون بعد أو مساوي لتاريخ الإصدار',
            'age.numeric' => helperTrans('web.age_must_be_numeric'),
            'age.min' => __('web.age_min', ['min'=> '18']),
            'age.max' => __('web.age_max', ['max'=> '90']),
            'job_id.required' => helperTrans('web.job_id_is_required'),
            'job_id.array' => helperTrans('web.job_id_must_be_an_array'),
            'job_id.*.required' => helperTrans('web.each_job_id_is_required'),
            'job_id.*.exists' => helperTrans('web.invalid_job_id'),
            'type_id.required' => helperTrans('web.type_id_is_required'),
            'type_id.exists' => helperTrans('web.invalid_type_id'),
            'office_id.required' => 'المكتب الخارجي مطلوب لعاملة التأجير',
            'office_id.exists' => helperTrans('web.office_id not exist'),
            'offer_price.numeric' => helperTrans('web.price_must_be_numeric'),
            'offer_price.min' => helperTrans('web.price_must_be_numeric'),
            'offer_expires_at.after_or_equal' => 'تاريخ انتهاء العرض يجب أن يكون بعد أو مساوي لتاريخ بداية العرض',
            'offer_duration.max' => 'مدة العرض يجب ألا تتجاوز 255 حرفاً',
            'rental_kingdom_status.required' => 'يرجى تحديد هل العاملة داخل المملكة أو خارج المملكة',
            'rental_kingdom_status.in' => 'حالة وجود العاملة داخل/خارج المملكة غير صحيحة',
            'arrival_date.required' => 'تاريخ الوصول مطلوب عند اختيار أن العاملة داخل المملكة',
            'arrival_date.date' => 'تاريخ وصول العاملة يجب أن يكون تاريخاً صالحاً',
        ];
    }

    public function attributes()
    {
        return [
            'name:en' => 'اسم العاملة بالإنجليزية',
            'gender' => 'الجنس',
            'image' => 'صورة العاملة',
            'maid_cv_file' => 'ملف سيرة العاملة من المكتب الخارجي',
            'video' => 'ملف الفيديو',
            'video_link' => 'رابط الفيديو',
            'nationality_id' => 'الجنسية',
            'passport_num' => 'رقم جواز السفر',
            'phone' => 'رقم الهاتف',
            'price' => 'السعر',
            'salary' => 'الراتب',
            'experience_status' => 'حالة الخبرة',
            'cooking_experience' => 'خبرة الطبخ',
            'religion_id' => 'الديانة',
            'id_number' => 'رقم الهوية',
            'id_expiry_date' => 'تاريخ انتهاء الهوية',
            'border_number' => 'رقم الحدود',
            'passport_issue_date' => 'تاريخ إصدار الجواز',
            'passport_expiry_date' => 'تاريخ انتهاء الجواز',
            'age' => 'العمر',
            'birth_date' => 'تاريخ الميلاد',
            'available_at' => 'تاريخ الإتاحة',
            'job_id' => 'الوظيفة',
            'job_id.*' => 'الوظيفة المحددة',
            'type_id' => 'نوع السيرة',
            'office_id' => 'المكتب الخارجي',
            'branch_id' => 'الفرع',
            'percentage.*' => 'نسبة إتقان اللغة',
            'language_levels.*' => 'مستوى اللغة',
            'expertises.*.country_id' => 'دولة الخبرة',
            'expertises.*.number_of_years' => 'مدة الخبرة',
            'expertises.*.job_title_id' => 'وظيفة الخبرة',
            'expertises.*.work_city' => 'مدينة الخبرة',
            'expertises.*.start_date' => 'بداية الخبرة',
            'expertises.*.end_date' => 'نهاية الخبرة',
            'request_type' => 'نوع الطلب',
            'offer_price' => 'سعر العرض',
            'offer_duration' => 'مدة العرض',
            'offer_starts_at' => 'تاريخ بداية العرض',
            'offer_expires_at' => 'تاريخ انتهاء العرض',
            'rental_kingdom_status' => 'وجود العاملة داخل أو خارج المملكة',
            'arrival_date' => 'تاريخ الوصول',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->isSelectedRentalType() && app(BranchAccessService::class)->enabled()) {
                $allowedBranchIds = app(BranchAccessService::class)->selectorBranches()
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                if (!$allowedBranchIds->contains((int) $this->input('branch_id'))) {
                    $validator->errors()->add('branch_id', 'الفرع المحدد غير متاح للمستخدم الحالي.');
                }
            }

            $expertises = collect((array) $this->input('expertises', []));
            $countries = Country::query()
                ->whereIn('id', $expertises->pluck('country_id')->filter()->all())
                ->get()
                ->keyBy('id');

            foreach ($expertises as $index => $expertise) {
                $country = $countries->get($expertise['country_id'] ?? null);
                $countryText = mb_strtolower(implode(' ', array_filter([
                    $country?->name_ar,
                    $country?->name_en,
                    $country?->code,
                ])));
                $isSaudiCountry = str_contains($countryText, 'السعود')
                    || str_contains($countryText, 'saudi')
                    || preg_match('/(^|\s)sa($|\s)/i', $countryText);

                if ($isSaudiCountry && blank($expertise['work_city'] ?? null)) {
                    $validator->errors()->add(
                        "expertises.{$index}.work_city",
                        'مدينة العمل السابقة مطلوبة عند اختيار المملكة العربية السعودية.'
                    );
                }

                // مدة الخبرة تُحسب من التاريخين، فلا معنى لأحدهما بدون الآخر
                $start = ExperiencePeriod::normalize($expertise['start_date'] ?? null);
                $end = ExperiencePeriod::normalize($expertise['end_date'] ?? null);

                if ($start && ! $end) {
                    $validator->errors()->add(
                        "expertises.{$index}.end_date",
                        'يرجى تحديد نهاية الخبرة.'
                    );
                }

                if ($end && ! $start) {
                    $validator->errors()->add(
                        "expertises.{$index}.start_date",
                        'يرجى تحديد بداية الخبرة.'
                    );
                }

                if ($start && $end && $end < $start) {
                    $validator->errors()->add(
                        "expertises.{$index}.end_date",
                        'نهاية الخبرة يجب أن تكون بعد بدايتها أو مساوية لها.'
                    );
                }

                if ($start && $start > now()->startOfMonth()->toDateString()) {
                    $validator->errors()->add(
                        "expertises.{$index}.start_date",
                        'بداية الخبرة لا يمكن أن تكون في المستقبل.'
                    );
                }
            }

            if (
                !$this->isSelectedRentalType()
                || !$this->isMethod('post')
            ) {
                return;
            }

            if (!$this->boolean('rental_finance_items_confirmed')) {
                $validator->errors()->add(
                    'rental_finance_items_confirmed',
                    'يرجى تأكيد بنود التأجير من النافذة المنبثقة قبل حفظ العاملة'
                );
            }
        });
    }

    private function isSelectedRentalType(): bool
    {
        $typeId = (int) $this->input('type_id');

        if ($typeId <= 0) {
            return false;
        }

        $jobType = JobType::find($typeId);
        $title = mb_strtolower(trim((string) ($jobType?->title ?? '')));
        $normalizedTitle = str_replace(['أ', 'إ', 'آ'], 'ا', $title);

        return $typeId === 2
            || str_contains($normalizedTitle, 'ايجار')
            || str_contains($normalizedTitle, 'تاجير')
            || str_contains($normalizedTitle, 'rent');
    }

    private function requiresOfficeField(): bool
    {
        return $this->isSelectedRentalType();
    }

}
