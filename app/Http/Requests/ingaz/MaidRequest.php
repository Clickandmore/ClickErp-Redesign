<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\Country;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Support\ExperiencePeriod;
use App\Support\MaidFieldRules;
use App\Support\MaidFormFieldSettings;
use App\Rules\InternationalMobile;
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
            'name:en' => ['required', 'string', 'max:' . MaidFieldRules::MAX_LENGTHS['name:en'], 'regex:' . MaidFieldRules::LATIN_NAME_REGEX],
            'gender' => 'nullable|in:male,female',
            'image' => [
                Rule::when($this->hasFile('image'), [
                    'file',
                    'mimes:' . implode(',', MaidFieldRules::IMAGE_MIMES),
                    'max:' . MaidFieldRules::FILE_MAX_KB,
                ]),
            ],
            'cv_file' => [
                'nullable',
                Rule::when($this->hasFile('cv_file'), [
                    'file',
                    'mimes:pdf,' . implode(',', MaidFieldRules::IMAGE_MIMES),
                    'max:' . MaidFieldRules::FILE_MAX_KB,
                ]),
            ],
            'maid_cv_file' => [
                Rule::requiredIf(fn () => $this->maidCvFileIsRequired()),
                'nullable',
                'file',
                'mimes:' . implode(',', MaidFieldRules::MAID_CV_MIMES),
                'max:' . MaidFieldRules::FILE_MAX_KB,
            ],
            'video' => 'nullable|file|mimes:mp4,mov,avi,wmv,webm,mkv,m4v|max:' . MaidFieldRules::VIDEO_MAX_KB,
            'remove_video' => 'nullable|boolean',
            'video_link' => 'nullable|url|max:2000',
            'musaned_link' => 'nullable|url|max:2000',
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => [
                'required',
                Rule::unique('maids')->ignore($this->route('maid')),
                Rule::when($this->isChanged('passport_num'), ['regex:' . MaidFieldRules::PASSPORT_REGEX]),
            ],
            // الجوالات بصيغة دولية +رمز الدولة، وتُفحص حسب قواعد ترقيم دولتها (عند إدخالها أو تعديلها).
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->isChanged('phone'), [new InternationalMobile('رقم جوال العاملة')]),
            ],
            'relative_name' => $this->lettersOnlyRules('relative_name'),
            'relative_phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->isChanged('relative_phone'), [new InternationalMobile('رقم جوال قريب العاملة')]),
            ],
            'residence_city' => $this->lettersOnlyRules('residence_city'),
            'passport_issue_place' => $this->lettersOnlyRules('passport_issue_place'),
            'education' => 'nullable|string|max:255',
            'education_en' => 'nullable|string|max:255',
            'has_driving_license' => 'nullable|boolean',
            'driving_license_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'price' => 'nullable|numeric',
            'supplier_cost_usd' => 'nullable|numeric|min:0.01|max:99999999.99',
            'salary' => [
                'nullable',
                'numeric',
                Rule::when($this->isChanged('salary', 'number'), [
                    'required',
                    'integer',
                    'between:' . MaidFieldRules::SALARY_MIN . ',' . MaidFieldRules::SALARY_MAX,
                ]),
            ],
            'experience_years' => 'nullable',
            'experience_status' => 'required|in:has_experience,no_experience',
            'cooking_experience' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('experience_status') === 'has_experience'
                    && !MaidFormFieldSettings::isRelaxed('cooking_experience')),
                Rule::in([
                    'arabic_cooking',
                    'regular_cooking',
                    'willing_to_learn',
                    'general_cooking',
                    'no_cooking_experience',
                ]),
            ],
            'religion_id' => 'nullable|exists:religions,id',
            // رقم الهوية/الإقامة الجديد بصيغته السعودية؛ القيم القديمة المحفوظة تبقى بالقاعدة السابقة.
            'id_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::when($this->isChanged('id_number'), ['regex:' . MaidFieldRules::ID_NUMBER_REGEX], ['regex:/^[0-9A-Za-z-]{5,30}$/']),
            ],
            'id_expiry_date' => [
                'nullable',
                'date',
            ],
            // القواعد الجديدة للصيغ والحدود تُطبَّق على القيم المُدخلة أو المُعدّلة فقط،
            // حتى لا تمنع البيانات القديمة المحفوظة تعديل باقي حقول السيرة.
            'border_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->isChanged('border_number'), ['regex:' . MaidFieldRules::BORDER_NUMBER_REGEX]),
            ],
            'passport_issue_date' => [
                'nullable',
                'date',
                Rule::when($this->isChanged('passport_issue_date', 'date'), [
                    'before_or_equal:' . MaidFieldRules::today()->toDateString(),
                    'after_or_equal:' . MaidFieldRules::passportIssueDateMin(),
                ]),
                // تاريخ الميلاد هو المرجع: لا يُصدر جواز قبل الميلاد
                Rule::when($this->filled('birth_date') && ($this->isChanged('passport_issue_date', 'date') || $this->isChanged('birth_date', 'date')), 'after:birth_date'),
            ],
            'passport_expiry_date' => [
                'nullable',
                'date',
                Rule::when($this->filled('passport_issue_date'), 'after_or_equal:passport_issue_date'),
                Rule::when($this->isChanged('passport_expiry_date', 'date'), [
                    'after_or_equal:' . MaidFieldRules::today()->toDateString(),
                ]),
            ],
            'age' => 'nullable|numeric|min:18|max:90',
            'childrens_count' => [
                'nullable',
                'numeric',
                Rule::when($this->isChanged('childrens_count', 'number'), [
                    'integer',
                    'between:' . MaidFieldRules::CHILDREN_MIN . ',' . MaidFieldRules::CHILDREN_MAX,
                ]),
            ],
            // الطول والوزن: القيمة "0" يرسلها النموذج بدلًا من الحقل الفارغ في الأنواع التي لا تتطلبهما.
            'height' => [
                'nullable',
                Rule::when($this->isChanged('height', 'number') && (string) $this->input('height') !== '0', [
                    'integer',
                    'between:' . MaidFieldRules::HEIGHT_MIN . ',' . MaidFieldRules::HEIGHT_MAX,
                ]),
            ],
            'weight' => [
                'nullable',
                Rule::when($this->isChanged('weight', 'number') && (string) $this->input('weight') !== '0', [
                    'integer',
                    'between:' . MaidFieldRules::WEIGHT_MIN . ',' . MaidFieldRules::WEIGHT_MAX,
                ]),
            ],
            // مدة العقد بالشهور: رقم صحيح موجب (القيمة القديمة المحفوظة مثل "24 شهر" تُقرأ رقمًا في prepareForValidation).
            'duration' => ['nullable', 'regex:' . MaidFieldRules::DURATION_REGEX, 'integer', 'between:' . MaidFieldRules::DURATION_MIN_MONTHS . ',' . MaidFieldRules::DURATION_MAX_MONTHS],
            'additional_notes' => [
                'nullable',
                'string',
                Rule::when($this->isChanged('additional_notes'), ['max:' . MaidFieldRules::MAX_LENGTHS['additional_notes']]),
            ],
            'birth_date' => [
                'nullable',
                'date',
                Rule::when($this->isChanged('birth_date', 'date'), [
                    'after_or_equal:' . MaidFieldRules::birthDateMin(),
                    'before_or_equal:' . MaidFieldRules::birthDateMax(),
                ]),
            ],
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
            'duration' => $this->normalizedDuration(),
            'cooking_experience' => $this->input('experience_status') === 'has_experience'
                ? $this->input('cooking_experience')
                : null,
        ];

        // النموذج يرسل العمر "0" عند تركه فارغًا؛ والعمر قد يُترك فارغًا عندما تجعل الشركة
        // تاريخ الميلاد اختياريًا أو تخفي العمر من إعدادات السير، فـ"0" تعني: لم يُدخل.
        if ($this->has('age') && in_array(trim((string) $this->input('age')), ['', '0'], true)) {
            $data['age'] = null;
        }

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
            'name:en.max' => 'اسم العاملة يجب ألا يتجاوز ' . MaidFieldRules::MAX_LENGTHS['name:en'] . ' حرفًا',
            'salary.required' => 'الراتب مطلوب',
            'salary.integer' => 'الراتب يجب أن يكون رقمًا صحيحًا بدون كسور عشرية',
            'salary.between' => 'الراتب يجب أن يكون بين ' . MaidFieldRules::SALARY_MIN . ' و' . MaidFieldRules::SALARY_MAX,
            'height.integer' => 'الطول يجب أن يكون رقمًا صحيحًا بالسنتيمتر',
            'height.between' => 'الطول يجب أن يكون بين ' . MaidFieldRules::HEIGHT_MIN . ' و' . MaidFieldRules::HEIGHT_MAX . ' سم',
            'weight.integer' => 'الوزن يجب أن يكون رقمًا صحيحًا بالكيلوجرام',
            'weight.between' => 'الوزن يجب أن يكون بين ' . MaidFieldRules::WEIGHT_MIN . ' و' . MaidFieldRules::WEIGHT_MAX . ' كجم',
            'childrens_count.integer' => 'عدد الأطفال يجب أن يكون رقمًا صحيحًا',
            'childrens_count.between' => 'عدد الأطفال يجب أن يكون بين ' . MaidFieldRules::CHILDREN_MIN . ' و' . MaidFieldRules::CHILDREN_MAX,
            'passport_issue_place.regex' => 'مكان إصدار جواز السفر يقبل الحروف والمسافات فقط، بدون أرقام أو رموز',
            'relative_name.regex' => 'اسم قريب العاملة يقبل الحروف والمسافات فقط، بدون أرقام أو رموز',
            'residence_city.regex' => 'مدينة سكن العاملة تقبل الحروف والمسافات فقط، بدون أرقام أو رموز',
            'image.max' => 'حجم الصورة الشخصية يجب ألا يتجاوز ' . (MaidFieldRules::FILE_MAX_KB / 1024) . ' ميجابايت',
            'cv_file.max' => 'حجم الصورة الكاملة يجب ألا يتجاوز ' . (MaidFieldRules::FILE_MAX_KB / 1024) . ' ميجابايت',
            'name:en.regex' => 'اسم العاملة بالإنجليزية يقبل الحروف الإنجليزية والمسافات فقط، بدون حروف عربية أو أرقام',
            'image.mimes' => 'صيغة الصورة الشخصية غير مدعومة. الصيغ المسموحة: JPG وPNG وGIF وBMP وWEBP',
            'cv_file.mimes' => 'صيغة الصورة الكاملة غير مدعومة. الصيغ المسموحة: PDF وJPG وPNG وGIF وBMP وWEBP',
            'passport_num.regex' => 'رقم الجواز يقبل الحروف الإنجليزية والأرقام فقط، بطول من ' . MaidFieldRules::PASSPORT_MIN_LENGTH . ' إلى ' . MaidFieldRules::PASSPORT_MAX_LENGTH . ' خانة',
            'border_number.regex' => 'رقم الحدود يقبل الأرقام فقط، بحد أقصى ' . MaidFieldRules::BORDER_NUMBER_MAX_LENGTH . ' رقمًا',
            'birth_date.after_or_equal' => 'تاريخ الميلاد غير منطقي: يجب ألا يزيد عمر العاملة عن ' . MaidFieldRules::MAX_AGE . ' سنة',
            'birth_date.before_or_equal' => 'تاريخ الميلاد غير منطقي: يجب ألا يقل عمر العاملة عن ' . MaidFieldRules::MIN_AGE . ' سنة',
            'passport_issue_date.before_or_equal' => 'تاريخ إصدار جواز السفر لا يمكن أن يكون في المستقبل',
            'passport_issue_date.after_or_equal' => 'تاريخ إصدار جواز السفر لا يمكن أن يكون أقدم من ' . MaidFieldRules::PASSPORT_ISSUE_MAX_YEARS . ' سنة',
            'passport_expiry_date.after_or_equal' => 'تاريخ انتهاء جواز السفر يجب أن يكون بعد تاريخ الإصدار ولا يكون تاريخًا منتهيًا',
            'cv_file.required' => helperTrans('web.cv_file_is_required'),
            'maid_cv_file.file' => 'ملف سيرة العاملة من المكتب الخارجي غير صالح',
            'maid_cv_file.mimes' => 'يجب أن يكون ملف سيرة العاملة بصيغة PDF أو صورة (JPG, PNG, WEBP)',
            'maid_cv_file.required' => 'ملف سيرة العاملة (صورة أو PDF) مطلوب حسب إعدادات السير الذاتية',
            'maid_cv_file.max' => 'حجم ملف سيرة العاملة من المكتب الخارجي يجب ألا يتجاوز 10 ميجابايت',
            'driving_license_file.file' => 'مرفق رخصة القيادة غير صالح',
            'driving_license_file.mimes' => 'يجب أن يكون مرفق رخصة القيادة بصيغة PDF أو صورة (JPG, PNG, WEBP)',
            'driving_license_file.max' => 'حجم مرفق رخصة القيادة يجب ألا يتجاوز 10 ميجابايت',
            'video.file' => 'ملف الفيديو المرفوع غير صالح.',
            'video.uploaded' => 'تعذّر رفع ملف الفيديو. تحقق من أن حجمه لا يتجاوز ' . (MaidFieldRules::VIDEO_MAX_KB / 1024) . ' ميجابايت ثم حاول مرة أخرى.',
            'video.mimes' => 'صيغة الفيديو غير مدعومة. الصيغ المسموحة: MP4 وMOV وAVI وWMV وWEBM وMKV وM4V.',
            'video.max' => 'حجم الفيديو يجب ألا يتجاوز ' . (MaidFieldRules::VIDEO_MAX_KB / 1024) . ' ميجابايت.',
            'video_link.url' => 'رابط الفيديو غير صالح. يرجى إدخال رابط كامل يبدأ بـ http أو https.',
            'video_link.max' => 'رابط الفيديو طويل جدًا.',
            'musaned_link.url' => 'رابط ملف العاملة على مساند غير صالح. يرجى إدخال رابط كامل يبدأ بـ http أو https.',
            'musaned_link.max' => 'رابط ملف العاملة على مساند طويل جدًا.',
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
            'id_number.regex' => $this->isChanged('id_number')
                ? 'رقم الهوية يجب أن يتكون من 10 أرقام بالضبط ويبدأ بالرقم 1 (هوية وطنية) أو 2 (إقامة)، بدون حروف أو رموز أو مسافات'
                : 'يجب إدخال رقم هوية صالح من 5 إلى 30 خانة باستخدام الأرقام أو الحروف أو الشرطة (-)',
            'duration.regex' => 'مدة العقد يجب أن تكون عددًا صحيحًا موجبًا من الشهور، بدون سالب أو كسور',
            'duration.integer' => 'مدة العقد يجب أن تكون عددًا صحيحًا موجبًا من الشهور، بدون سالب أو كسور',
            'duration.between' => 'مدة العقد يجب أن تكون بين ' . MaidFieldRules::DURATION_MIN_MONTHS . ' و' . MaidFieldRules::DURATION_MAX_MONTHS . ' شهرًا',
            'passport_issue_date.after' => 'تاريخ إصدار جواز السفر يجب أن يكون بعد تاريخ الميلاد',
            'id_expiry_date.date' => 'تاريخ انتهاء الهوية يجب أن يكون تاريخاً صالحاً',
            'border_number.max' => 'رقم الحدود يجب ألا يتجاوز 50 خانة',
            'passport_issue_date.date' => 'تاريخ إصدار جواز السفر يجب أن يكون تاريخاً صالحاً',
            'passport_expiry_date.date' => 'تاريخ انتهاء جواز السفر يجب أن يكون تاريخاً صالحاً',
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
            'musaned_link' => 'رابط ملف العاملة على مساند',
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
            'height' => 'الطول',
            'weight' => 'الوزن',
            'childrens_count' => 'عدد الأطفال',
            'duration' => 'مدة العقد',
            'additional_notes' => 'الملاحظات الإضافية',
            'passport_issue_place' => 'مكان إصدار الجواز',
            'relative_name' => 'اسم قريب العاملة',
            'residence_city' => 'مدينة سكن العاملة',
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

            $this->validateDatesAgainstBirth($validator);

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

    private ?Maid $existingMaid = null;

    /**
     * هل القيمة المُرسلة جديدة أو تختلف عن المحفوظة؟ في الإضافة تُعد كل قيمة جديدة.
     */
    /** حقل نصي اختياري يقبل الحروف والمسافات فقط، بحد أقصى للطول. */
    private function lettersOnlyRules(string $field): array
    {
        return [
            'nullable',
            'string',
            Rule::when($this->isChanged($field), [
                'max:' . MaidFieldRules::MAX_LENGTHS[$field],
                'regex:' . MaidFieldRules::LETTERS_ONLY_REGEX,
            ], ['max:255']),
        ];
    }

    /**
     * @param string $type string | date | number
     */
    /**
     * ملف السيرة الجاهزة إلزامي إذا اختارت الشركة ذلك في إعدادات السير الذاتية،
     * إلا في التعديل عندما يكون للعاملة ملف محفوظ مسبقًا.
     */
    private function maidCvFileIsRequired(): bool
    {
        if (MaidFormFieldSettings::state('maid_cv_file') !== MaidFormFieldSettings::REQUIRED) {
            return false;
        }

        $maidId = $this->route('maid');
        if (!$maidId) {
            return true;
        }

        $this->existingMaid ??= $maidId instanceof Maid ? $maidId : Maid::find($maidId);

        return !filled($this->existingMaid?->getRawOriginal('maid_cv_file'));
    }

    private function isChanged(string $field, string $type = 'string'): bool
    {
        $maidId = $this->route('maid');

        if (!$maidId) {
            return true;
        }

        $this->existingMaid ??= $maidId instanceof Maid ? $maidId : Maid::find($maidId);

        if (!$this->existingMaid) {
            return true;
        }

        $stored = trim((string) $this->existingMaid->getRawOriginal($field));
        $submitted = trim((string) $this->input($field));

        if ($type === 'date') {
            $stored = substr($stored, 0, 10);
            $submitted = substr($submitted, 0, 10);
        }

        // الأرقام تُقارن كقيم: "1500" و"1500.00" قيمة واحدة.
        if ($type === 'number' && is_numeric($stored) && is_numeric($submitted)) {
            return (float) $stored !== (float) $submitted;
        }

        return $stored !== $submitted;
    }

    /** "24" أو "٢٤" أو القيمة القديمة "24 شهر" → "24"؛ غير ذلك يُترك كما هو ليرفضه التحقق. */
    private function normalizedDuration(): ?string
    {
        $value = $this->input('duration');
        if ($value === null || !is_scalar($value)) return $value;
        $value = trim(strtr((string) $value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
        if ($value === '') return null;
        return preg_match('/^(\d+)\s*(شهر|شهور|أشهر|اشهر|months?)?$/u', $value, $m) ? $m[1] : $value;
    }

    /** مدة العقد تُحفظ نصًا للعرض في السيرة كما كانت ("24 شهر")، بعد التحقق من أنها رقم صحيح. */
    protected function passedValidation()
    {
        if (filled($this->input('duration'))) {
            $this->merge(['duration' => (int) $this->input('duration') . ' شهر']);
        }
    }

    /**
     * التواريخ المرتبطة بالعمر تُقاس من تاريخ الميلاد: بداية الخبرة والوصول للمملكة
     * لا يسبقان بلوغ العاملة MIN_WORK_AGE سنة.
     */
    private function validateDatesAgainstBirth($validator): void
    {
        $birth = $this->input('birth_date');
        if (!is_string($birth) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $birth)) return;
        try {
            $workMin = \Carbon\CarbonImmutable::parse(substr($birth, 0, 10))->addYears(MaidFieldRules::MIN_WORK_AGE);
        } catch (\Throwable $e) {
            return;
        }
        $birthChanged = $this->isChanged('birth_date', 'date');
        $minAge = MaidFieldRules::MIN_WORK_AGE;

        $stored = $this->existingMaid ?? ($this->route('maid') ? Maid::find($this->route('maid') instanceof Maid ? $this->route('maid')->id : $this->route('maid')) : null);
        $storedStarts = $stored ? $stored->expertises()->pluck('start_date')->map(fn ($d) => substr((string) $d, 0, 7))->all() : [];
        foreach ((array) $this->input('expertises', []) as $index => $expertise) {
            $start = ExperiencePeriod::normalize($expertise['start_date'] ?? null);
            if (!$start || (!$birthChanged && in_array(substr($start, 0, 7), $storedStarts, true))) continue;
            if ($start < $workMin->startOfMonth()->toDateString()) {
                $validator->errors()->add("expertises.{$index}.start_date", "بداية الخبرة يجب ألا تسبق بلوغ العاملة {$minAge} سنة (من " . $workMin->format('Y-m') . ' حسب تاريخ الميلاد).');
            }
        }

        $arrival = $this->input('arrival_date');
        if (is_string($arrival) && $arrival !== '' && ($birthChanged || $this->isChanged('arrival_date', 'date')) && substr($arrival, 0, 10) < $workMin->toDateString()) {
            $validator->errors()->add('arrival_date', "تاريخ وصول العاملة يجب ألا يسبق بلوغها {$minAge} سنة (" . $workMin->format('Y-m-d') . ' حسب تاريخ الميلاد).');
        }
    }

    private function requiresOfficeField(): bool
    {
        return $this->isSelectedRentalType();
    }

}
