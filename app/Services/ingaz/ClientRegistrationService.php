<?php

namespace App\Services\ingaz;

use App\Models\auth\Admin;
use App\Models\Country;
use App\Models\ingaz\User;
use App\PermKeys;
use App\Services\BranchAccessService;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\ClientCategory;

/**
 * إضافة عميل: التحقق والتطبيع وإنشاء حسابه المالي. مصدر واحد تستخدمه شاشة العملاء
 * (UserController::store/update) وأداة المساعد الذكي create_customer، حتى لا يختلف السلوك بينهما.
 */
class ClientRegistrationService
{
    /** أقل وأكبر عمر مقبول للعميل (يحدد نطاق تاريخ الميلاد) */
    public const CLIENT_MIN_AGE = 18;
    public const CLIENT_MAX_AGE = 100;

    /** الحد الأعلى لحقول الأسرة */
    public const MAX_ROOMS_NUM = 20;
    public const MAX_FAMILY_NUM = 30;
    public const MAX_CHILDREN = 20;

    /** الحالات الاجتماعية التي يُحتمل معها وجود أسرة (تظهر معها حقول الأسرة) */
    public const FAMILY_MARITAL_STATUSES = ['married', 'divorced', 'widowed'];

    /** مفاتيح الدول المتاحة لرقم الجوال الآخر */
    public const PHONE2_COUNTRY_CODES = [
        '+966' => 'السعودية',
        '+971' => 'الإمارات',
        '+965' => 'الكويت',
        '+974' => 'قطر',
        '+973' => 'البحرين',
        '+968' => 'عُمان',
        '+20' => 'مصر',
        '+962' => 'الأردن',
        '+249' => 'السودان',
        '+967' => 'اليمن',
        '+963' => 'سوريا',
        '+961' => 'لبنان',
        '+964' => 'العراق',
        '+970' => 'فلسطين',
        '+212' => 'المغرب',
        '+216' => 'تونس',
        '+213' => 'الجزائر',
        '+92' => 'باكستان',
        '+91' => 'الهند',
        '+880' => 'بنغلاديش',
        '+63' => 'الفلبين',
        '+62' => 'إندونيسيا',
        '+94' => 'سريلانكا',
        '+977' => 'نيبال',
        '+251' => 'إثيوبيا',
        '+254' => 'كينيا',
        '+256' => 'أوغندا',
        '+90' => 'تركيا',
        '+44' => 'بريطانيا',
        '+1' => 'أمريكا / كندا',
    ];

    /**
     * يتحقق من بيانات عميل جديد ويرجعها جاهزة للحفظ، أو يرمي ValidationException برسائل الشاشة نفسها.
     * لا يكتب شيئاً في القاعدة، فتستخدمه معاينة المساعد أيضاً.
     */
    public function validateNew(array $input, Admin $admin, string $registerFrom = 'dashboard'): array
    {
        $input = array_merge($input, $this->normalizeDigits(array_intersect_key($input, array_flip(['phone', 'phone2', 'national_num', 'tax_number', 'license_number']))));
        $phone2Code = (string) ($input['phone2_code'] ?? '+966');

        $rules = $this->rules($input, [
            'phone' => $this->duplicateRule('phone', 'رقم الجوال'),
            'email' => $this->duplicateRule('email', 'البريد الإلكتروني'),
            'national_num' => $this->duplicateRule('national_num', 'رقم الهوية أو الإقامة'),
            'tax_number' => $this->duplicateRule('tax_number', 'الرقم الضريبي'),
        ]);

        $canViewAll = $this->canViewAllBranches($admin);
        if ($this->branchesEnabled()) {
            $rules['branch_id'] = $canViewAll ? 'required|exists:branches,id' : 'nullable|exists:branches,id';
        }

        $data = Validator::make($input, $rules, $this->messages($phone2Code))->validate();
        $data = $this->adjust($data, $phone2Code);

        $normalizedPhone = $this->normalizeSaudiPhone($data['phone']);
        if (!$normalizedPhone) {
            throw ValidationException::withMessages(['phone' => __('ingaz.client_phone_invalid_format')]);
        }

        $phoneCandidates = array_values(array_unique(array_filter([
            $data['phone'],
            $normalizedPhone,
            '0' . $normalizedPhone,
            '966' . $normalizedPhone,
            '+966' . $normalizedPhone,
        ])));

        if (User::query()->whereIn('phone', $phoneCandidates)->exists()) {
            throw ValidationException::withMessages(['phone' => __('ingaz.client_phone_already_exists')]);
        }

        $data['phone'] = $normalizedPhone;
        $data['phone_code'] = '+966';
        $data['register_from'] = $registerFrom;
        $data['is_blocked'] = 0;
        $data['created_by'] = $admin->id;
        $data = $this->normalizeClientType($data);

        if ($this->branchesEnabled()) {
            if ($canViewAll) {
                $data['branch_id'] = (int) $data['branch_id'];
            } else {
                $data['branch_id'] = app(BranchAccessService::class)->adminBranchId($admin);

                if (!$data['branch_id']) {
                    throw ValidationException::withMessages(['branch_id' => __('ingaz.permission_denied_add_client')]);
                }
            }
        }

        return $data;
    }

    /**
     * ينشئ العميل مع حسابه في دليل الحسابات وسجله في عملاء المالية، في معاملة واحدة.
     *
     * @throws ClientParentAccountMissing إذا لم يوجد حساب «العملاء» الأب في دليل الحسابات.
     */
    public function create(array $data): User
    {
        $data['password'] = bcrypt('123456');

        return DB::transaction(function () use ($data) {
            $parentAccount = Account::query()->where('type', '=', AccountTypesEnum::Clients->value)
                ->lockForUpdate()
                ->first();

            if (!$parentAccount) {
                throw new ClientParentAccountMissing(__('ingaz.clients_parent_account_missing'));
            }

            $lastCode = Account::where('parent_id', $parentAccount->id)->max('code');
            if (!$lastCode) {
                $lastCode = (int) ($parentAccount->code . '00000');
            }

            $newAccount = Account::create([
                'name' => $data['name'],
                'operation_type' => $parentAccount->operation_type,
                'status' => $parentAccount->status,
                'level' => $parentAccount->level + 1,
                'type' => AccountTypesEnum::Client->value,
                'parent_id' => $parentAccount->id,
                'code' => $lastCode + 1,
                'created_by_id' => $data['created_by'] ?? null,
            ]);

            $data['account_id'] = $newAccount->id;

            Client::create([
                'is_company' => ($data['client_type'] ?? 'person') === 'company' ? 1 : 0,
                'client_category_id' => $this->financeClientCategoryId($data['client_category_id'] ?? null),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? null,
                'tax_number' => $this->financeTaxNumber($data),
                'code' => $newAccount->code,
                'days_credit_period' => 30,
                'credit_limit' => 30,
                'created_by_id' => $data['created_by'] ?? null,
                'account_id' => $newAccount->id,
            ]);

            return User::create($data);
        });
    }

    // ------------------------------------------------------------------ قواعد مشتركة مع التعديل

    /**
     * قواعد التحقق المشتركة بين إضافة العميل وتعديله.
     *
     * @param array<string, mixed> $uniqueRules قاعدة عدم التكرار لكل حقل (phone/email/national_num/tax_number)
     */
    public function rules(array $input, array $uniqueRules = []): array
    {
        // اسم الفرد: حروف ومسافات فقط، والشركة يُسمح لها بالأرقام أيضاً
        $nameRegex = ($input['client_type'] ?? null) === 'company'
            ? '/^[\p{L}\p{M}\p{N} ]+$/u'
            : '/^[\p{L}\p{M} ]+$/u';

        // الجوال الآخر: نفس قاعدة الجوال الأساسي للسعودية، وأرقام فقط (6-12) لبقية الدول
        $phone2Regex = (string) ($input['phone2_code'] ?? '+966') === '+966'
            ? '/^5\d{8}$/'
            : '/^[1-9]\d{5,11}$/';

        [$birthdateMin, $birthdateMax] = $this->birthdateBounds();

        $withUnique = function (string $field, array $rules) use ($uniqueRules): array {
            if (! empty($uniqueRules[$field])) {
                $rules[] = $uniqueRules[$field];
            }

            return $rules;
        };

        return [
            'name' => ['required', 'string', 'max:150', 'regex:' . $nameRegex],
            // رقم جوال سعودي: 9 أرقام بالضبط تبدأ بالرقم 5
            'phone' => $withUnique('phone', ['required', 'string', 'regex:/^5\d{8}$/']),
            'email' => $withUnique('email', ['nullable', 'email', 'max:100', 'regex:/^[\x21-\x7E]+$/']),
            'client_category_id' => 'nullable|exists:fn_client_categories,id',
            'client_type' => 'required|string|in:person,company',
            'nationality_id' => 'nullable|exists:countries,id',
            'national_num' => $withUnique('national_num', ['nullable', 'required_if:client_type,person', 'string', 'regex:/^\d{10}$/']),
            // الرقم الضريبي: 15 رقماً يبدأ بـ 3، ولا يتكرر لأكثر من عميل
            'tax_number' => $withUnique('tax_number', ['nullable', 'required_if:client_type,company', 'string', 'regex:/^3\d{14}$/']),
            // الترخيص / السجل التجاري: 10 أرقام
            'license_number' => ['nullable', 'required_if:client_type,company', 'string', 'regex:/^\d{10}$/'],
            // تاريخ انتهاء الترخيص: يُقبل المنتهي ويظهر للعميل بادج "ترخيص منتهي"
            'license_expiry_date' => 'nullable|required_if:client_type,company|date_format:Y-m-d|after_or_equal:1900-01-01|before_or_equal:2100-12-31',
            'birthdate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:' . $birthdateMin, 'before_or_equal:' . $birthdateMax],
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'phone2_code' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::PHONE2_COUNTRY_CODES))],
            'phone2' => ['nullable', 'string', 'regex:' . $phone2Regex],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0|max:' . self::MAX_ROOMS_NUM,
            'family_num' => 'nullable|integer|min:0|max:' . self::MAX_FAMILY_NUM,
            'children' => 'nullable|integer|min:0|max:' . self::MAX_CHILDREN,
            'job' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\p{M} ]+$/u'],
            'full_name_en' => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z ]+$/'],
            'identity_date' => 'nullable|date_format:Y-m-d|after_or_equal:1900-01-01|before_or_equal:2100-12-31',
            'identity_type' => 'nullable|string|max:50',
            'residence_expiry_date' => 'nullable|date',
            'workplace' => 'nullable|string|max:150',
            'po_box' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function messages(string $phone2Code): array
    {
        $birthdateRange = __('ingaz.client_birthdate_range', ['min' => self::CLIENT_MIN_AGE, 'max' => self::CLIENT_MAX_AGE]);

        return [
            'name.required' => __('ingaz.client_name_required'),
            'name.regex' => __('ingaz.client_name_invalid_chars'),
            'name.max' => __('ingaz.client_field_max_length', ['max' => 150]),
            'phone.required' => __('ingaz.client_phone_required'),
            'phone.regex' => __('ingaz.client_phone_saudi_mobile_format'),
            'phone2.regex' => $phone2Code === '+966'
                ? __('ingaz.client_phone2_saudi_format')
                : __('ingaz.client_phone2_international_format'),
            'phone2_code.in' => __('ingaz.client_phone2_code_invalid'),
            'email.email' => __('ingaz.client_email_invalid'),
            'email.regex' => __('ingaz.client_email_english_only'),
            'email.max' => __('ingaz.client_field_max_length', ['max' => 100]),
            'nationality_id.exists' => __('ingaz.client_nationality_not_found'),
            'national_num.regex' => __('ingaz.client_national_num_digits'),
            'national_num.required' => __('ingaz.client_national_num_required'),
            'national_num.required_if' => __('ingaz.client_national_num_required'),
            'national_num.unique' => __('ingaz.client_national_num_already_exists'),
            'tax_number.required_if' => __('ingaz.client_tax_number_required'),
            'tax_number.unique' => __('ingaz.client_tax_number_already_exists'),
            'tax_number.regex' => __('ingaz.client_tax_number_format'),
            'license_number.required_if' => __('ingaz.client_license_number_required'),
            'license_number.regex' => __('ingaz.client_license_number_format'),
            'license_expiry_date.required_if' => __('ingaz.client_license_expiry_date_required'),
            'license_expiry_date.date_format' => __('ingaz.client_license_expiry_date_invalid'),
            'license_expiry_date.after_or_equal' => __('ingaz.client_license_expiry_date_invalid'),
            'license_expiry_date.before_or_equal' => __('ingaz.client_license_expiry_date_invalid'),
            'birthdate.date_format' => __('ingaz.client_birthdate_invalid'),
            'birthdate.after_or_equal' => $birthdateRange,
            'birthdate.before_or_equal' => $birthdateRange,
            'marital_status.required' => __('ingaz.marital_status_required'),
            'marital_status.required_if' => __('ingaz.marital_status_required'),
            'marital_status.in' => 'الحالة الاجتماعية المختارة غير صحيحة',
            'rooms_num.integer' => __('ingaz.client_rooms_num_integer'),
            'rooms_num.min' => __('ingaz.client_rooms_num_min'),
            'rooms_num.max' => __('ingaz.client_number_max', ['max' => self::MAX_ROOMS_NUM]),
            'family_num.integer' => __('ingaz.client_family_num_integer'),
            'family_num.min' => __('ingaz.client_family_num_min'),
            'family_num.max' => __('ingaz.client_number_max', ['max' => self::MAX_FAMILY_NUM]),
            'children.integer' => __('ingaz.client_children_integer'),
            'children.min' => __('ingaz.client_children_min'),
            'children.max' => __('ingaz.client_number_max', ['max' => self::MAX_CHILDREN]),
            'job.regex' => __('ingaz.client_job_letters_only'),
            'job.max' => __('ingaz.client_field_max_length', ['max' => 100]),
            'full_name_en.regex' => __('ingaz.client_full_name_en_english_only'),
            'full_name_en.max' => __('ingaz.client_field_max_length', ['max' => 150]),
            'identity_date.date_format' => __('ingaz.client_identity_date_invalid'),
            'identity_date.after_or_equal' => __('ingaz.client_identity_date_invalid'),
            'identity_date.before_or_equal' => __('ingaz.client_identity_date_invalid'),
            'identity_type.max' => __('ingaz.client_field_max_length', ['max' => 50]),
            'workplace.max' => __('ingaz.client_field_max_length', ['max' => 150]),
            'address.max' => __('ingaz.client_field_max_length', ['max' => 255]),
            'city.max' => __('ingaz.client_city_max'),
            'po_box.max' => __('ingaz.client_field_max_length', ['max' => 20]),
            'postal_code.max' => __('ingaz.client_field_max_length', ['max' => 10]),
            'notes.max' => __('ingaz.client_field_max_length', ['max' => 5000]),
        ];
    }

    /**
     * تعديلات بعد التحقق مشتركة بين الإضافة والتعديل.
     */
    public function adjust(array $data, string $phone2Code): array
    {
        $data = $this->clearResidenceExpiryForSaudi($data);

        // حقول الأسرة لا تُحفظ لغير المتزوج/المطلق/الأرمل (مخفية في الواجهة)
        if (! in_array($data['marital_status'] ?? null, self::FAMILY_MARITAL_STATUSES, true)) {
            $data['rooms_num'] = null;
            $data['family_num'] = null;
            $data['children'] = null;
        }

        // لا يوجد عمود لمفتاح الجوال الآخر: السعودي يُحفظ 9 أرقام مثل الجوال الأساسي،
        // وغير السعودي يُحفظ مع مفتاح الدولة (+20xxxxxxxxxx)
        if (! empty($data['phone2']) && $phone2Code !== '+966') {
            $data['phone2'] = $phone2Code . $data['phone2'];
        }
        unset($data['phone2_code']);

        return $data;
    }

    public function normalizeClientType(array $data): array
    {
        $data['client_type'] = ($data['client_type'] ?? 'person') === 'company' ? 'company' : 'person';

        if ($data['client_type'] === 'company') {
            // الإقامة تخص الأفراد فقط، والترخيص يخص الشركات فقط
            foreach (['national_num', 'birthdate', 'marital_status', 'rooms_num', 'family_num', 'children', 'job', 'residence_expiry_date'] as $field) {
                $data[$field] = null;
            }
            return $data;
        }

        foreach (['tax_number', 'license_number', 'license_expiry_date'] as $field) {
            $data[$field] = null;
        }

        return $data;
    }

    /**
     * يحوّل الأرقام العربية/الفارسية إلى أرقام إنجليزية ويزيل المسافات الطرفية
     * قبل التحقق، حتى لا يُرفض رقم صحيح كُتب بلوحة مفاتيح عربية.
     */
    public function normalizeDigits(array $input): array
    {
        $map = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];

        return array_map(
            fn ($value) => is_string($value) ? trim(strtr($value, $map)) : $value,
            $input
        );
    }

    public function normalizeSaudiPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '05')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    public function duplicateRule(string $column, string $label): \Closure
    {
        return function ($attribute, $value, $fail) use ($column, $label) {
            $query = User::withTrashed();
            if ($column === 'phone') {
                $phone = $this->normalizeSaudiPhone((string) $value);
                if (!$phone) { return; }
                $query->whereRaw($this->canonicalPhoneSql() . ' = ?', [$phone]);
            } else {
                $query->where($column, trim((string) $value));
            }
            foreach ($query->get(['id', 'deleted_at']) as $duplicate) {
                $fail($label . ' مستخدم بالفعل للعميل رقم ' . $duplicate->id . '. '
                    . ($duplicate->trashed()
                        ? 'العميل محذوف وموجود في صفحة العملاء المحذوفين؛ استعده بدلاً من إنشاء عميل مكرر.'
                        : 'العميل موجود في قائمة العملاء؛ استخدم سجله الحالي بدلاً من إنشاء سجل مكرر.'));
            }
        };
    }

    public function canonicalPhoneSql(string $table = 'users'): string
    {
        $rawPhone = Schema::hasColumn('users', 'source_phone')
            ? "COALESCE(NULLIF(TRIM({$table}.source_phone), ''), TRIM({$table}.phone))"
            : "TRIM({$table}.phone)";
        $withoutDuplicateMarker = "REGEXP_REPLACE({$rawPhone}, '-dup-[0-9]*$', '')";
        $digits = "REGEXP_REPLACE({$withoutDuplicateMarker}, '[^0-9]', '')";

        return "(CASE
            WHEN {$digits} LIKE '00966%' THEN SUBSTRING({$digits}, 6)
            WHEN {$digits} LIKE '966%' THEN SUBSTRING({$digits}, 4)
            WHEN {$digits} LIKE '05%' THEN SUBSTRING({$digits}, 2)
            ELSE {$digits}
        END)";
    }

    public function financeTaxNumber(array $data): ?string
    {
        $value = ($data['client_type'] ?? 'person') === 'company'
            ? ($data['tax_number'] ?? null)
            : ($data['national_num'] ?? null);

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function financeClientCategoryId($selectedCategoryId = null): int
    {
        if ($selectedCategoryId) {
            return (int) $selectedCategoryId;
        }

        $existingCategoryId = ClientCategory::query()->value('id');

        if ($existingCategoryId) {
            return (int) $existingCategoryId;
        }

        $categoryData = ['name' => __('ingaz.general')];

        if (Schema::hasColumn('fn_client_categories', 'created_by_id') && auth('admin')->id()) {
            $categoryData['created_by_id'] = auth('admin')->id();
        }

        return (int) ClientCategory::query()->create($categoryData)->id;
    }

    public function branchesEnabled(): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable('users')
            && Schema::hasColumn('users', 'branch_id')
            && Schema::hasTable('branches');
    }

    public function canViewAllBranches(Admin $admin): bool
    {
        return !$this->branchesEnabled()
            || AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::BRANCHES_VIEW_ALL, PermKeys::CLIENTS_VIEW_ALL_BRANCHES]);
    }

    /**
     * تاريخ انتهاء الإقامة يخص غير السعوديين فقط.
     *
     * الحقل مخفي في الواجهة عند اختيار السعودية، وهذا يمنع بقاء قيمة قديمة
     * لو غُيّرت الجنسية إلى السعودية عند التعديل.
     */
    private function clearResidenceExpiryForSaudi(array $data): array
    {
        if (! array_key_exists('nationality_id', $data)) {
            return $data;
        }

        $country = $data['nationality_id']
            ? Country::find($data['nationality_id'])
            : null;

        if (! $country || $country->is_saudi) {
            $data['residence_expiry_date'] = null;
        }

        return $data;
    }

    /**
     * نطاق تاريخ الميلاد المقبول: عمر العميل بين CLIENT_MIN_AGE و CLIENT_MAX_AGE سنة.
     *
     * @return array{0: string, 1: string} [أقدم تاريخ, أحدث تاريخ] بصيغة Y-m-d
     */
    private function birthdateBounds(): array
    {
        return [
            now()->subYears(self::CLIENT_MAX_AGE)->toDateString(),
            now()->subYears(self::CLIENT_MIN_AGE)->toDateString(),
        ];
    }
}
