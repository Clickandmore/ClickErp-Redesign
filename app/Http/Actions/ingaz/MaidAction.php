<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Actions\MainAction;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\RentalMaidAccountingService;
use App\Services\ingaz\RentalMaidFinanceService;
use App\Support\CvOptionsSettings;
use App\Support\ExperiencePeriod;
use App\Support\SystemModuleManager;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Nami\Hr\Models\Department as HrDepartment;
use Nami\Hr\Models\Employee as HrEmployee;
use Nami\Hr\Models\EmployeeShift as HrEmployeeShift;
use Nami\Hr\Models\Job as HrJob;
use Nami\Hr\Models\Shift as HrShift;

class MaidAction extends MainAction
{
    public function __construct(
        Maid $model,
        protected MaidExpertiseAction $maidExpertiseAction,
        protected RentalMaidFinanceService $rentalMaidFinanceService,
        protected RentalMaidAccountingService $rentalMaidAccountingService
    ) {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }

    /**
     * بادئة السيريال الموحّد لسير التأجير.
     */
    private const RENTAL_CV_CODE_PREFIX = 'RENT-';

    /**
     * سيريال موحّد لكل سيرة تأجير: RENT-0001.
     *
     * كود الاستقدام يُبنى على اسم المكتب الخارجي (MAK/009)، وسير التأجير غالبًا
     * بلا مكتب فكانت تُحفظ بلا كود. هنا يُولَّد لها عدّاد مستقل موحّد،
     * ويُطبَّق حتى لو كان للعاملة مكتب حتى تبقى كل سير التأجير على صيغة واحدة.
     */
    private function assignRentalCvCode(Maid $maid): void
    {
        if (! $this->maidIsRental($maid)) {
            return;
        }

        $maid->cv_code = $this->nextRentalCvCode();
        $maid->save();

        \Log::info('Rental cv_code assigned', ['maid_id' => $maid->id, 'cv_code' => $maid->cv_code]);
    }

    /**
     * نعتمد على العلاقة بعد المزامنة لا على الطلب، لأنها المصدر النهائي لنوع السيرة.
     */
    private function maidIsRental(Maid $maid): bool
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        return $rentalJobTypeId
            && $maid->jobTypes()->where('job_types.id', $rentalJobTypeId)->exists();
    }

    private function nextRentalCvCode(): string
    {
        $prefix = self::RENTAL_CV_CODE_PREFIX;
        $digitsFrom = strlen($prefix) + 1;

        $lastNumber = (int) $this->model->newQuery()
            ->where('cv_code', 'LIKE', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(cv_code, {$digitsFrom}) AS UNSIGNED)) as last_number")
            ->value('last_number');

        // حلقة الحماية تتفادى التصادم لو أُنشئت سيرتان في نفس اللحظة
        do {
            $lastNumber++;
            $code = $prefix . str_pad((string) $lastNumber, 4, '0', STR_PAD_LEFT);
        } while ($this->model->newQuery()->where('cv_code', $code)->exists());

        return $code;
    }

    /**
     * سنوات صف خبرة واحد. التاريخان أولى من القيمة القادمة من الواجهة لأنهما مصدرها.
     */
    private function expertiseYears(array $expertise): float
    {
        $years = ExperiencePeriod::years($expertise['start_date'] ?? null, $expertise['end_date'] ?? null);

        return (float) ($years ?? ($expertise['number_of_years'] ?? 0));
    }

    public function storeMaid($data, $request)
    {
        $data = \App\Support\EducationQualificationCatalog::normalize($data);
        \Log::info('Raw data received in storeMaid:', $data);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));
        \Log::info('Languages data: ', is_array($data['languages'] ?? null) ? $data['languages'] : [$data['languages'] ?? 'not set']);
        \Log::info('Name data: ' . ($data['name'] ?? 'not set'));
        \Log::info('Name:en data: ' . ($data['name:en'] ?? 'not set'));

        if (isset($data['cv_file']) && $data['cv_file'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_file'];
            $data['cv_file'] = uploadMaidFile($image, $this->fileFolder);
        }
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['image'];
            $data['image'] = uploadMaidFile($image, $this->fileFolder);
        }
        if (isset($data['cv_preview']) && $data['cv_preview'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_preview'];
            $data['cv_preview'] = uploadMaidFile($image, $this->fileFolder);
        }
        if (isset($data['maid_cv_file']) && $data['maid_cv_file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['maid_cv_file'];
            $data['maid_cv_file'] = uploadMaidFile($file, $this->fileFolder);
        }
        // مرفق رخصة القيادة يُحفظ فقط عندما تكون الإجابة "نعم"، وإلا يُتجاهل.
        $hasDrivingLicense = $this->normalizeNullableBoolean($data['has_driving_license'] ?? null);
        if ($hasDrivingLicense === true
            && isset($data['driving_license_file'])
            && $data['driving_license_file'] instanceof \Illuminate\Http\UploadedFile) {
            $data['driving_license_file'] = uploadMaidFile($data['driving_license_file'], $this->fileFolder);
        } else {
            unset($data['driving_license_file']);
        }
        if (isset($data['video']) && $data['video'] instanceof \Illuminate\Http\UploadedFile) {
            $video = $data['video'];
            $data['video'] = $this->uploadMaidVideo($video);
        }

        // Normalize experience values before defaults so they survive cleanup
        $experienceStatus = $data['experience_status'] ?? 'no_experience';
        if ($experienceStatus === 'has_experience') {
            $totalYears = 0;
            if (isset($data['expertises']) && is_array($data['expertises'])) {
                foreach ($data['expertises'] as $expertise) {
                    $totalYears += $this->expertiseYears($expertise);
                }
            } elseif (isset($data['experience_years']) && $data['experience_years'] !== '') {
                $totalYears = (int)$data['experience_years'];
            }
            $data['experience_years'] = (string)$totalYears;
        } else {
            $data['experience_years'] = '0';
        }
        $data['experience_status'] = $experienceStatus;

        if (isset($data['skills']) && is_array($data['skills'])) {
            $data['skills'] = array_values(array_filter($data['skills'], function ($value) {
                return $value !== null && $value !== '';
            }));
        }

        $preserveNullArrivalDate = (bool)($data['preserve_null_arrival_date'] ?? false)
            || ($this->isRentalTypeRequest($request) && $request->input('rental_kingdom_status') === 'outside');
        unset($data['preserve_null_arrival_date']);

        // Set default values for required fields if they're empty
        $data['name'] = $data['name:en'] ?? '';
        $data['nationality_id'] = $data['nationality_id'] ?? null;
        $data['religion_id'] = $data['religion_id'] ?? null;
        $data['passport_num'] = $data['passport_num'] ?? '';
        $data['phone'] = $data['phone'] ?? null;
        $data['relative_name'] = $data['relative_name'] ?? null;
        $data['relative_phone'] = $data['relative_phone'] ?? null;
        $data['residence_city'] = $data['residence_city'] ?? null;
        $data['passport_issue_place'] = $data['passport_issue_place'] ?? null;
        $data['education'] = $data['education'] ?? null;
        $data['has_driving_license'] = $this->normalizeNullableBoolean(
            $data['has_driving_license'] ?? null
        );
        $data['id_number'] = $data['id_number'] ?? '';
        $data['id_expiry_date'] = $data['id_expiry_date'] ?? null;
        $data['border_number'] = $data['border_number'] ?? null;
        $data['passport_issue_date'] = $data['passport_issue_date'] ?? null;
        $data['passport_expiry_date'] = $data['passport_expiry_date'] ?? null;
        $data['price'] = $data['price'] ?? 0;
        $data['salary'] = $data['salary'] ?? 0;
        $data['age'] = $data['age'] ?? 0;
        $data['height'] = $data['height'] ?? '160';
        $data['weight'] = $data['weight'] ?? '60';
        $data['childrens_count'] = $data['childrens_count'] ?? '0';
        $data['duration'] = $data['duration'] ?? ' ';
        $data['additional_notes'] = $data['additional_notes'] ?? null;
        $data['cv_file'] = $data['cv_file'] ?? '';
        $data['available'] = $data['available'] ?? 1;
        $data['available_at'] = $data['available_at'] ?? date('Y-m-d');
        $data['arrival_date'] = $data['arrival_date'] ?? ($preserveNullArrivalDate ? null : date('Y-m-d'));
        $data['is_show'] = $data['is_show'] ?? 1;

        // Clean the data but preserve required fields - only remove truly empty strings, not zeros or nulls
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) === '' && !in_array($key, ['nationality_id', 'religion_id', 'passport_num', 'id_number', 'id_expiry_date', 'border_number', 'passport_issue_date', 'passport_expiry_date'])) {
                unset($data[$key]);
            }
        }

        // Ensure required database fields are present and not empty
        if (!isset($data['nationality_id']) || empty($data['nationality_id'])) {
            $data['nationality_id'] = 2; // Default nationality (first available)
        }

        if (!isset($data['religion_id']) || empty($data['religion_id'])) {
            $data['religion_id'] = 1; // Default religion (first available)
        }

        // Ensure these fields are properly set as integers and included in data
        $data['nationality_id'] = (int)$data['nationality_id'];
        $data['religion_id'] = (int)$data['religion_id'];
        $data['gender'] = in_array($data['gender'] ?? null, ['male', 'female'], true) ? $data['gender'] : 'female';

        // Ensure these required fields are always included
        $data['passport_num'] = $data['passport_num'] ?? '';
        $data['id_number'] = $data['id_number'] ?? '';
        $data['id_expiry_date'] = $data['id_expiry_date'] ?? null;
        $data['border_number'] = $data['border_number'] ?? null;
        $data['passport_issue_date'] = $data['passport_issue_date'] ?? null;
        $data['passport_expiry_date'] = $data['passport_expiry_date'] ?? null;

        // Robust handling for languages field
        \Log::info('Before languages processing - languages data:', [$data['languages'] ?? 'not set']);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));

        // ✅ لو فيه لغات
        if (isset($data['languages']) && is_array($data['languages'])) {

            // أولاً فلتر النِّسب بحيث تبقى فقط اللغات اللي متعلم عليها وفيها رقم
            [$percentages, $languageLevels] = $this->normalizeLanguageRatings(
                array_values($data['languages']),
                $data['percentage'] ?? [],
                $data['language_levels'] ?? [],
                $data['language_rating_mode'] ?? []
            );

            // خزّن اللغات كـ JSON array (القيم فقط: ["arabic","amharic"])
            $data['languages'] = json_encode(array_values($data['languages']), JSON_UNESCAPED_UNICODE);

            // خزّن النِّسب كـ JSON object (مثل {"arabic":"98","amharic":"90"})
            $data['percentage'] = json_encode($percentages, JSON_UNESCAPED_UNICODE);
            $data['language_levels'] = json_encode($languageLevels, JSON_UNESCAPED_UNICODE);

        } else {
            // لو مفيش لغات أصلاً
            $data['languages'] = json_encode([], JSON_UNESCAPED_UNICODE);
            $data['percentage'] = json_encode([], JSON_UNESCAPED_UNICODE);
            $data['language_levels'] = json_encode([], JSON_UNESCAPED_UNICODE);
        }
        unset($data['language_rating_mode']);

        // Rental CVs are contractable stock, so they always fall under custom specs.
        $data['request_type'] = Maid::resolveRequestType($data['request_type'] ?? null);
        if ($this->isRentalTypeRequest($request)) {
            $data['request_type'] = Maid::REQUEST_TYPE_CUSTOM;
        }

        $offerPrice = $data['offer_price'] ?? null;
        $data['offer_price'] = ($offerPrice === '' || $offerPrice === null) ? null : round((float)$offerPrice, 2);

        $data['offer_description'] = isset($data['offer_description']) && trim((string)$data['offer_description']) !== ''
            ? $data['offer_description']
            : null;

        if (array_key_exists('offer_starts_at', $data)) {
            $data['offer_starts_at'] = $data['offer_starts_at']
                ? date('Y-m-d', strtotime($data['offer_starts_at']))
                : null;
        } else {
            unset($data['offer_starts_at']);
        }

        if (array_key_exists('offer_expires_at', $data)) {
            $data['offer_expires_at'] = $data['offer_expires_at']
                ? date('Y-m-d', strtotime($data['offer_expires_at']))
                : null;
        } else {
            unset($data['offer_expires_at']);
        }

        $offerStartsAt = isset($data['offer_starts_at']) && $data['offer_starts_at']
            ? Carbon::parse($data['offer_starts_at'])
            : null;
        $offerExpiresAt = isset($data['offer_expires_at']) && $data['offer_expires_at']
            ? Carbon::parse($data['offer_expires_at'])
            : null;

        if ($offerStartsAt && $offerExpiresAt && $offerExpiresAt->gte($offerStartsAt)) {
            $data['offer_duration'] = (string) ($offerStartsAt->diffInDays($offerExpiresAt) + 1);
        } else {
            $data['offer_duration'] = null;
        }

        // Handle birth_date field
        if (isset($data['birth_date']) && !empty($data['birth_date'])) {
            $data['birth_date'] = date('Y-m-d', strtotime($data['birth_date']));
        } else {
            unset($data['birth_date']); // Remove if empty
        }

        foreach (['id_expiry_date', 'passport_issue_date', 'passport_expiry_date'] as $dateField) {
            if (array_key_exists($dateField, $data)) {
                $data[$dateField] = $data[$dateField]
                    ? date('Y-m-d', strtotime($data[$dateField]))
                    : null;
            }
        }

        // Verify that the nationality and religion exist in the database
        $nationality = \App\Models\ingaz\Nationality::find($data['nationality_id']);
        if (!$nationality) {
            throw ValidationException::withMessages([
                'nationality_id' => 'الجنسية المحددة غير موجودة. يرجى اختيار جنسية صحيحة من القائمة.',
            ]);
        }

        if (filled($data['religion_id'] ?? null)
            && !\App\Models\ingaz\Religion::find($data['religion_id'])) {
            throw ValidationException::withMessages([
                'religion_id' => 'الديانة المحددة غير موجودة. يرجى اختيار ديانة صحيحة من القائمة.',
            ]);
        }

        $duration = $data['duration'] ?? null;
        // unset($data['duration'], $data['additional_notes'], $data['id']);
        unset($data['duration'], $data['id']);

        // معالجة جميع الحقول التي هي array
        foreach ($data as $key => $value) {
            if ($key === 'expertises') {
                continue;
            }
            if (is_array($value)) {
                if (in_array($key, ['skills', 'languages'])) {
                    $data[$key] = json_encode($value);
                } else {
                    unset($data[$key]);
                }
            }
        }


        // Debug: Log any array fields before saving
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                \Log::error("[ARRAY FIELD] $key => " . print_r($value, true));
            }
        }

        // Debug: Log the data being sent to create
        \Log::info('Maid creation data:', $data);

        // Detect optional columns to avoid SQL errors on environments without the migration
        $requestTypeColumnExists = Schema::hasColumn('maids', 'request_type');
        $offerPriceColumnExists = Schema::hasColumn('maids', 'offer_price');
        $offerDurationColumnExists = Schema::hasColumn('maids', 'offer_duration');
        $offerDescriptionColumnExists = Schema::hasColumn('maids', 'offer_description');
        $offerStartsColumnExists = Schema::hasColumn('maids', 'offer_starts_at');
        $offerExpiresColumnExists = Schema::hasColumn('maids', 'offer_expires_at');
        $borderNumberColumnExists = Schema::hasColumn('maids', 'border_number');
        $passportIssueColumnExists = Schema::hasColumn('maids', 'passport_issue_date');
        $passportExpiryColumnExists = Schema::hasColumn('maids', 'passport_expiry_date');
        $genderColumnExists = Schema::hasColumn('maids', 'gender');
        $arabiaWorkerNumberColumnExists = Schema::hasColumn('maids', 'arabia_worker_number');
        $branchColumnExists = Schema::hasColumn('maids', 'branch_id');

        // Ensure all required fields are present in the data array
        $createData = [
            'nationality_id' => $data['nationality_id'],
            'religion_id' => $data['religion_id'],
            'passport_num' => $data['passport_num'],
            'phone' => $data['phone'] ?? null,
            'relative_name' => $data['relative_name'] ?? null,
            'relative_phone' => $data['relative_phone'] ?? null,
            'residence_city' => $data['residence_city'] ?? null,
            'passport_issue_place' => $data['passport_issue_place'] ?? null,
            'education' => $data['education'] ?? null,
            'education_en' => $data['education_en'] ?? null,
            'has_driving_license' => $data['has_driving_license'] ?? null,
            'driving_license_file' => $data['driving_license_file'] ?? null,
            'id_number' => $data['id_number'],
            'id_expiry_date' => $data['id_expiry_date'],
            'price' => $data['price'],
            'salary' => $data['salary'],
            'age' => $data['age'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'duration' => $duration,
            'additional_notes' => $data['additional_notes'] ?? null,
            'childrens_count' => $data['childrens_count'] ?? 0,
            'birth_date' => $data['birth_date'] ?? null,
            'experience_years' => $data['experience_years'] ?? '0',
            'experience_status' => $data['experience_status'] ?? 'no_experience',
            'cooking_experience' => ($data['experience_status'] ?? null) === 'has_experience'
                ? ($data['cooking_experience'] ?? null)
                : null,
            'skills' => $data['skills'] ?? null,
            'languages' => $data['languages'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'language_levels' => $data['language_levels'] ?? null,
            'cv_file' => $data['cv_file'] ?? '',
            'maid_cv_file' => $data['maid_cv_file'] ?? null,
            'image' => $data['image'] ?? '',
            'video_link' => $data['video_link'] ?? null,
            'video' => $data['video'] ?? null,
            'cv_preview' => $data['cv_preview'] ?? null,
            'office_id' => $data['office_id'] ?? null,
            'status' => $data['status'],
            'added_by' => $data['added_by'] ?? null,
            'available' => $data['available'],
            'available_at' => $data['available_at'],
            'arrival_date' => $data['arrival_date'],
            'is_show' => $data['is_show'],
            'name' => $data['name'],
        ];

        foreach (['submission_status', 'submission_type'] as $submissionField) {
            if (array_key_exists($submissionField, $data)) {
                $createData[$submissionField] = $data[$submissionField];
            }
        }

        if (!empty($data['id'])) {
            $createData['id'] = $data['id'];
        }

        if (!empty($data['created_at'])) {
            $createData['created_at'] = $data['created_at'];
        }

        if ($arabiaWorkerNumberColumnExists && !empty($data['arabia_worker_number'])) {
            $createData['arabia_worker_number'] = (int) $data['arabia_worker_number'];
        }

        if ($genderColumnExists) {
            $createData['gender'] = $data['gender'];
        }

        
        if ($requestTypeColumnExists) {
            $createData['request_type'] = Maid::resolveRequestType($data['request_type'] ?? null);
        }

        if ($offerPriceColumnExists) {
            $createData['offer_price'] = $data['offer_price'] ?? null;
        }
        if ($offerDurationColumnExists) {
            $createData['offer_duration'] = $data['offer_duration'] ?? null;
        }
        if ($offerDescriptionColumnExists) {
            $createData['offer_description'] = $data['offer_description'] ?? null;
        }
        if ($offerStartsColumnExists) {
            $createData['offer_starts_at'] = $data['offer_starts_at'] ?? null;
        }
        if ($offerExpiresColumnExists) {
            $createData['offer_expires_at'] = $data['offer_expires_at'] ?? null;
        }
        if ($borderNumberColumnExists) {
            $createData['border_number'] = $data['border_number'] ?? null;
        }
        if ($passportIssueColumnExists) {
            $createData['passport_issue_date'] = $data['passport_issue_date'] ?? null;
        }
        if ($passportExpiryColumnExists) {
            $createData['passport_expiry_date'] = $data['passport_expiry_date'] ?? null;
        }
        if ($branchColumnExists) {
            $createData['branch_id'] = $data['branch_id'] ?? null;
        }


        \Log::info('Final create data:', $createData);
        \Log::info('About to create maid with data keys: ' . implode(', ', array_keys($createData)));
        \Log::info('Languages in createData: ' . ($createData['languages'] ?? 'null'));
        \Log::info('Name in createData: ' . ($data['name'] ?? 'null'));

        $jobTypeIds = $this->resolveRequiredJobTypeIds($request);

        // Handle marital_status:ar field for translation
        $maritalStatusAr = null;
        $maritalStatusEn = null;

        if (isset($data['marital_status:ar'])) {
            $maritalStatusAr = $data['marital_status:ar'];
            unset($data['marital_status:ar']);
        }
        if (isset($data['marital_status:en'])) {
            $maritalStatusEn = $data['marital_status:en'];
            unset($data['marital_status:en']);
        }

        try {
            $maid = DB::transaction(function () use ($createData, $data, $request, $experienceStatus, $jobTypeIds, $maritalStatusAr, $maritalStatusEn) {
                $maid = $this->createMaidRecord($createData);
                \Log::info('Maid created successfully with ID: ' . $maid->id);

                if (isset($maritalStatusAr)) {
                    $maid->translateOrNew('ar')->marital_status = $maritalStatusAr;
                }
                if (isset($maritalStatusEn)) {
                    $maid->translateOrNew('en')->marital_status = $maritalStatusEn;
                }
                $maid->save();

                if ($maid->office_id || $maid->office?->title) {
                    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $maid->office->title), 0, 3));
                    if ($prefix === '') {
                        $prefix = 'OFF';
                    }

                    $count = $this->model->where('office_id', $maid->office_id)
                        ->where('id', '<=', $maid->id)
                        ->count();

                    $length = strlen((string) $count);
                    $cvCode = $prefix . "/" . str_pad($count, max(3, $length), '0', STR_PAD_LEFT);

                    $maid->cv_code = $cvCode;
                    $maid->save();

                    \Log::info('Mahmod Amer $cvCode: ' . ($cvCode ?? 'no cv_code'));
                }

                if ($experienceStatus === 'has_experience' && isset($data['expertises'])) {
                    foreach ($data['expertises'] as $expertise) {
                        $expertise['maid_id'] = $maid->id;
                        $this->maidExpertiseAction->store($expertise);
                    }
                }

                if ($request->job_id) {
                    \Log::info('About to attach job titles: ' . print_r($request->job_id, true));
                    $maid->jobTitles()->attach($request->job_id);
                    \Log::info('Job titles attached successfully');
                } else {
                    \Log::info('Skipping job titles attach - no job_id provided.');
                }

                \Log::info('About to sync job types: ' . print_r($jobTypeIds, true));
                $maid->jobTypes()->sync($jobTypeIds);
                \Log::info('Job types synced successfully');

                $this->assignRentalCvCode($maid);

                $this->syncRentalMaidFinancialAdjustments($maid, $request);

                if ($this->isRentalTypeRequest($request)) {
                    $this->rentalMaidAccountingService->recordCreationEntries($maid);
                }

                if ($this->shouldCreateRentalRecruitmentContract($maid, $request)) {
                    $this->createCompanyRecruitmentContractForRentalMaid($maid, $request);
                    $request->merge(['create_shelter_contract' => '0']);
                }

                if (($request->create_shelter_contract ?? '0') === '1') {
                    $this->createShelterContractForMaid($maid);
                    $request->merge(['create_shelter_contract' => '0']);
                }

                try {
                    $createShelterContract = $request->create_shelter_contract ?? '0';

                    if ($createShelterContract === '1') {
                        DB::beginTransaction();
                        $companyName = documentSetting('document_company_name', documentSetting('website_name', config('app.name')));
                        $companyPhone = documentSetting('phone', '0500000002');
                        $companyEmail = documentSetting('email', 'company@example.test');

                        $companyClient = ClientUser::where('name', 'like', '%' . $companyName . '%')
                            ->orWhere('email', $companyEmail)
                            ->orWhere('phone', $companyPhone)
                            ->first();

                        if (!$companyClient) {
                            $companyClient = ClientUser::create([
                                'name' => $companyName,
                                'email' => $companyEmail,
                                'phone' => $companyPhone,
                                'password' => bcrypt('password123'),
                            ]);
                        }

                        $contract = Contract::create([
                            'user_id' => $companyClient->id,
                            'maid_id' => $maid->id,
                            'status' => ContractStatusEnum::Shelter->value,
                            'contract_date' => now()->toDateString(),
                        ]);

                        ContractInfo::create([
                            'contract_id' => $contract->id,
                            'contract_date' => now()->toDateString(),
                            'msaned_number' => '',
                            'shelter_id' => 1,
                            'wants_to_work' => $this->isRentalTypeRequest($request) ? 'yes' : 'unknown',
                            'receipt_source' => 'company',
                        ]);

                        $maid->update([
                            'status' => 'available',
                            'maid_accommodation_type' => 2,
                            'available' => 1,
                            'available_at' => now()->toDateString(),
                        ]);

                        DB::commit();
                        \Log::info('Shelter contract created by user choice for maid ID: ' . $maid->id . ' with contract ID: ' . $contract->id);
                    }
                } catch (\Throwable $t) {
                    DB::rollBack();
                    \Log::error('Failed to create shelter contract for maid: ' . $t->getMessage());
                }

                $maid->jobTypes()->sync($this->resolveRequiredJobTypeIds($request, $maid));

                return $maid;
            });
        } catch (\Exception $e) {
            \Log::error('Error creating maid: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            throw $e;
        }

        $this->syncHrMaidRecord($maid->fresh(['jobTypes', 'nationality', 'office']));
        return $maid;
    }

    public function storeRapidCvMaid($createData, $request)
    {
        if (isset($createData['cv_file']) && $createData['cv_file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $createData['cv_file'];
            $createData['cv_file'] = uploadMaidFile($file, $this->fileFolder);

            $clientMimeType = strtolower((string) $file->getClientMimeType());
            $clientExtension = strtolower((string) $file->getClientOriginalExtension());
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

            if (
                (str_starts_with($clientMimeType, 'image/') || in_array($clientExtension, $imageExtensions, true))
                && empty($createData['image'])
            ) {
                $createData['image'] = $createData['cv_file'];
            }
        } else {
            $createData['cv_file'] = $createData['cv_file'] ?? '';
        }

        $createData['name'] = filled($createData['name'] ?? null)
            ? trim((string) $createData['name'])
            : null;
        $createData['religion_id'] = $createData['religion_id'] ?? (\App\Models\ingaz\Religion::query()->value('id') ?? 1);
        $createData['price'] = $createData['price'] ?? 0;
        $createData['salary'] = $createData['salary'] ?? 0;
        $createData['age'] = $createData['age'] ?? 0;
        $createData['height'] = $createData['height'] ?? '160';
        $createData['weight'] = $createData['weight'] ?? '60';
        $createData['available_at'] = $createData['available_at'] ?? now()->toDateString();
        $createData['arrival_date'] = $createData['arrival_date'] ?? now()->toDateString();
        $createData['image'] = $createData['image'] ?? '';
        $createData['request_type'] = Maid::resolveRequestType($createData['request_type'] ?? null);
        $createData['gender'] = in_array($createData['gender'] ?? null, ['male', 'female'], true) ? $createData['gender'] : 'female';

        if (!Schema::hasColumn('maids', 'gender')) {
            unset($createData['gender']);
        }

        $maid = $this->createMaidRecord($createData);
        $maid->jobTypes()->attach(1); // استقدام
        $maid->jobTypes()->sync($this->resolveRequiredJobTypeIds($request, $maid));
        if ($request->job_id) {
            $maid->jobTitles()->sync((array) $request->job_id);
        }

        $this->assignRentalCvCode($maid);

        return $maid;
    }

    protected function createMaidRecord(array $createData): Maid
    {
        $createData['image'] = $createData['image'] ?? '';
        $createData['added_by'] = $this->resolveValidMaidAddedBy($createData['added_by'] ?? null);

        try {
            return $this->model->create($createData);
        } catch (QueryException $exception) {
            if (!$this->shouldRetryMaidCreationWithoutAddedBy($exception, $createData)) {
                throw $exception;
            }

            \Log::warning('Retrying maid creation without added_by because the foreign key does not match the current schema.', [
                'added_by' => $createData['added_by'] ?? null,
                'message' => $exception->getMessage(),
            ]);

            $createData['added_by'] = null;

            return $this->model->create($createData);
        }
    }

    protected function resolveRequiredJobTypeIds($request, ?Maid $maid = null): array
    {
        $jobTypeIds = $this->normalizeRelationIds($request->input('type_id'));

        if (empty($jobTypeIds) && $maid) {
            $jobTypeIds = $this->normalizeRelationIds($maid->jobTypes()->allRelatedIds()->all());
        }

        if (empty($jobTypeIds)) {
            throw ValidationException::withMessages([
                'type_id' => helperTrans('web.type_id_is_required'),
            ]);
        }

        return $jobTypeIds;
    }

    protected function normalizeRelationIds($value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveValidMaidAddedBy($addedBy): ?int
    {
        if (!Schema::hasColumn('maids', 'added_by')) {
            return null;
        }

        $addedBy = filter_var($addedBy, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$addedBy) {
            return null;
        }

        $referenceTables = $this->getMaidAddedByReferenceTables();
        if ($referenceTables === []) {
            return $addedBy;
        }

        foreach ($referenceTables as $table) {
            if (!Schema::hasTable($table) || !DB::table($table)->where('id', $addedBy)->exists()) {
                return null;
            }
        }

        return (int) $addedBy;
    }

    protected function getMaidAddedByReferenceTables(): array
    {
        static $referenceTables;

        if ($referenceTables !== null) {
            return $referenceTables;
        }

        $referenceTables = [];

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                $databaseName = $connection->getDatabaseName();
                $rows = $connection->select(
                    'SELECT REFERENCED_TABLE_NAME AS table_name
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ?
                       AND TABLE_NAME = ?
                       AND COLUMN_NAME = ?
                       AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [$databaseName, 'maids', 'added_by']
                );

                $referenceTables = collect($rows)
                    ->map(fn ($row) => data_get($row, 'table_name'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            } elseif ($driver === 'sqlite') {
                $rows = $connection->select("PRAGMA foreign_key_list('maids')");

                $referenceTables = collect($rows)
                    ->filter(fn ($row) => data_get($row, 'from') === 'added_by')
                    ->map(fn ($row) => data_get($row, 'table'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        } catch (\Throwable $exception) {
            \Log::warning('Unable to inspect maids.added_by foreign keys.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $referenceTables;
    }

    protected function shouldRetryMaidCreationWithoutAddedBy(QueryException $exception, array $createData): bool
    {
        if (empty($createData['added_by'])) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'maids')
            && str_contains($message, 'added_by')
            && (str_contains($message, 'foreign key') || str_contains($message, 'integrity constraint violation'));
    }

    private function syncRentalMaidFinancialAdjustments(Maid $maid, $request): void
    {
        if (!$this->isRentalTypeRequest($request)) {
            return;
        }

        $items = $this->resolveRentalMaidFinanceItems($request);
        if ($items === []) {
            return;
        }

        if ($maid->rentalFinancialAdjustments()->where('context', 'maid')->exists()) {
            return;
        }

        $adminId = auth('admin')->id() ?? auth()->id();

        foreach ($items as $item) {
            $maid->rentalFinancialAdjustments()->create([
                'account_id' => $item['account_id'] ?? null,
                'type' => $item['type'],
                'label' => $item['label'],
                'amount' => $item['amount'],
                'context' => 'maid',
                'created_by' => $adminId,
            ]);
        }
    }

    private function syncRentalMaidFinancialAdjustmentsOnUpdate(Maid $maid, $request): void
    {
        if (!$this->isRentalTypeRequest($request) || !$request->exists('rental_finance_items')) {
            return;
        }

        $items = $this->resolveRentalMaidFinanceItems($request);
        if ($items === []) {
            return;
        }

        $adminId = auth('admin')->id() ?? auth()->id();
        $existingAdjustments = $maid->rentalFinancialAdjustments()
            ->where('context', 'maid')
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $payload = [
                'account_id' => $item['account_id'] ?? null,
                'type' => $item['type'],
                'label' => $item['label'],
                'amount' => $item['amount'],
                'context' => 'maid',
            ];

            if (!empty($item['id']) && $existingAdjustments->has($item['id'])) {
                $existingAdjustments[$item['id']]->update($payload);
            } else {
                $maid->rentalFinancialAdjustments()->create($payload + ['created_by' => $adminId]);
            }
        }
    }

    private function resolveRentalMaidFinanceItems($request): array
    {
        if (!$request->exists('rental_finance_items')) {
            return [];
        }

        return $this->rentalMaidFinanceService->resolveRequestItems($request);
    }

    private function getRentalJobTypeId(): ?int
    {
        $jobType = JobType::whereHas('translations', function ($query) {
            $query->where('title', 'LIKE', '%ايجار%')
                ->orWhere('title', 'LIKE', '%إيجار%')
                ->orWhere('title', 'LIKE', '%تأجير%')
                ->orWhere('title', 'LIKE', '%rent%');
        })->first();

        return $jobType?->id ?? 2;
    }

    private function isRentalTypeRequest($request): bool
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();
        if (!$rentalJobTypeId) {
            return false;
        }

        $typeIds = $this->normalizeRelationIds($request->input('type_id'));

        return in_array($rentalJobTypeId, $typeIds, true);
    }

    public function syncRentalMaidToHrRecord(Maid $maid): void
    {
        $this->syncHrMaidRecord($maid->fresh(['jobTypes', 'nationality', 'office']));
    }

    public function syncAllRentalMaidsToHr(): void
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        if (!$rentalJobTypeId
            || !Schema::hasTable('maids')
            || !Schema::hasTable('maid_jobs')) {
            return;
        }

        Maid::query()
            ->whereHas('jobTypes', function ($query) use ($rentalJobTypeId) {
                $query->where('job_types.id', $rentalJobTypeId);
            })
            ->with(['jobTypes', 'nationality', 'office'])
            ->chunkById(100, function ($maids) {
                foreach ($maids as $maid) {
                    $this->syncHrMaidRecord($maid);
                }
            });
    }

    private function syncHrMaidRecord(Maid $maid): void
    {
        if (!$this->isRentalMaid($maid)) {
            return;
        }

        if (!Schema::hasTable('hr_employee')
            || !Schema::hasColumn('hr_employee', 'employee_category')
            || !Schema::hasColumn('hr_employee', 'maid_id')) {
            return;
        }

        $maid->loadMissing(['jobTypes', 'nationality', 'office']);

        $department = HrDepartment::firstOrCreate(['title' => 'قسم العاملات']);
        $job = HrJob::firstOrCreate(['title' => 'عاملة']);
        $shift = $this->resolveHrShiftForMaid();

        $employeesQuery = HrEmployee::query()->withAllCategories();
        $existingEmployee = $employeesQuery->where('maid_id', $maid->id)->first();

        $employeeData = [
            'name' => $maid->name ?: ('عاملة #' . $maid->id),
            'image' => $maid->image ?: 'default.png',
            'code' => $this->resolveHrEmployeeCode($maid, $existingEmployee),
            'address' => $this->resolveHrEmployeeAddress($maid),
            'phone' => $this->resolveHrEmployeePhone($maid, $existingEmployee),
            'birthday' => $maid->birth_date?->format('Y-m-d')
                ?: Carbon::now()->subYears(max((int) ($maid->age ?: 25), 18))->toDateString(),
            'email' => $this->resolveHrEmployeeEmail($maid, $existingEmployee),
            'national_num' => $this->resolveHrEmployeeNationalNumber($maid, $existingEmployee),
            'country_id' => $this->resolveHrCountryIdForMaid($maid),
            'deparment_id' => $department->id,
            'job_id' => $job->id,
            'hired_date' => $maid->arrival_date?->format('Y-m-d') ?: now()->toDateString(),
            'contract_end_date' => $maid->id_expiry_date?->format('Y-m-d') ?: now()->addYear()->toDateString(),
            'salary' => (float) ($maid->salary ?? 0),
            'net_salary' => (float) ($maid->salary ?? 0),
            'status' => 1,
            'employee_category' => $existingEmployee?->employee_category === HrEmployee::CATEGORY_EMPLOYEE
                ? HrEmployee::CATEGORY_EMPLOYEE
                : HrEmployee::CATEGORY_MAID,
            'maid_id' => $maid->id,
        ];

        if (Schema::hasColumn('hr_employee', 'admin_id')) {
            $employeeData['admin_id'] = $existingEmployee?->admin_id;
        }

        if ($existingEmployee) {
            $existingEmployee->update($employeeData);
            $employee = $existingEmployee->fresh();
        } else {
            $employee = HrEmployee::query()->withAllCategories()->create($employeeData);
        }

        if ($shift) {
            HrEmployeeShift::updateOrCreate(
                ['employee_id' => $employee->id],
                ['shift_id' => $shift->id]
            );
        }
    }

    private function isRentalMaid(Maid $maid): bool
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();
        if (!$rentalJobTypeId) {
            return false;
        }

        $maid->loadMissing('jobTypes');

        return $maid->jobTypes
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->contains($rentalJobTypeId);
    }

    private function resolveHrShiftForMaid(): ?HrShift
    {
        if (!Schema::hasTable('hr_shifts')) {
            return null;
        }

        $shiftQuery = HrShift::query();
        $shift = Schema::hasColumn('hr_shifts', 'status')
            ? (clone $shiftQuery)->where('status', 1)->first()
            : null;

        $shift ??= $shiftQuery->first();

        if ($shift) {
            return $shift;
        }

        $sanctionId = DB::table('hr_sanctions')->orderBy('id')->value('id');

        if (!$sanctionId) {
            $sanctionId = DB::table('hr_sanctions')->insertGetId([
                'title' => 'بدون جزاء',
                'type' => 2,
                'value' => 0,
                'is_delays' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $payload = [
            'name' => 'وردية العاملات',
            'from_hour' => '08:00:00',
            'to_hour' => '17:00:00',
            'month_days' => 30,
            'absence_sanctions_id' => $sanctionId,
        ];

        if (Schema::hasColumn('hr_shifts', 'status')) {
            $payload['status'] = 1;
        }

        return HrShift::create($payload);
    }

    private function resolveHrEmployeeAddress(Maid $maid): string
    {
        $parts = array_filter([
            'عاملة تأجير',
            $maid->office?->title,
            $maid->nationality?->sub_title,
        ]);

        return implode(' - ', $parts);
    }

    private function resolveHrEmployeeCode(Maid $maid, ?HrEmployee $existingEmployee): string
    {
        if ($existingEmployee && filled($existingEmployee->code)) {
            return $existingEmployee->code;
        }

        $baseCode = 'MAID-' . str_pad((string) $maid->id, 5, '0', STR_PAD_LEFT);

        return $this->resolveUniqueHrEmployeeValue('code', $baseCode, $existingEmployee?->id);
    }

    private function resolveHrEmployeePhone(Maid $maid, ?HrEmployee $existingEmployee): string
    {
        if ($existingEmployee && filled($existingEmployee->phone)) {
            return $existingEmployee->phone;
        }

        $basePhone = '7' . str_pad((string) $maid->id, 9, '0', STR_PAD_LEFT);

        return $this->resolveUniqueHrEmployeeValue('phone', $basePhone, $existingEmployee?->id, '');
    }

    private function resolveHrEmployeeEmail(Maid $maid, ?HrEmployee $existingEmployee): string
    {
        if ($existingEmployee && filled($existingEmployee->email)) {
            return $existingEmployee->email;
        }

        $baseLocalPart = 'maid-' . $maid->id;
        $candidate = $baseLocalPart . '@maids.hr.local';
        $suffix = 1;

        while ($this->hrEmployeeValueExists('email', $candidate, $existingEmployee?->id)) {
            $candidate = $baseLocalPart . '-' . $suffix . '@maids.hr.local';
            $suffix++;
        }

        return $candidate;
    }

    private function resolveHrEmployeeNationalNumber(Maid $maid, ?HrEmployee $existingEmployee): string
    {
        if ($existingEmployee && filled($existingEmployee->national_num)) {
            return $existingEmployee->national_num;
        }

        $candidates = array_filter([
            trim((string) ($maid->id_number ?? '')),
            trim((string) ($maid->passport_num ?? '')),
            'MAID-' . $maid->id,
        ]);

        foreach ($candidates as $candidate) {
            if (!$this->hrEmployeeValueExists('national_num', $candidate, $existingEmployee?->id)) {
                return $candidate;
            }
        }

        return $this->resolveUniqueHrEmployeeValue('national_num', 'MAID-' . $maid->id, $existingEmployee?->id);
    }

    private function resolveUniqueHrEmployeeValue(
        string $column,
        string $baseValue,
        ?int $ignoreEmployeeId = null,
        string $separator = '-'
    ): string {
        $candidate = $baseValue;
        $suffix = 1;

        while ($this->hrEmployeeValueExists($column, $candidate, $ignoreEmployeeId)) {
            $candidate = $baseValue . $separator . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function hrEmployeeValueExists(string $column, string $value, ?int $ignoreEmployeeId = null): bool
    {
        $query = HrEmployee::query()->withAllCategories()->where($column, $value);

        if ($ignoreEmployeeId) {
            $query->where('id', '!=', $ignoreEmployeeId);
        }

        return $query->exists();
    }

    public function resolveHrCountryIdForMaid(Maid $maid): int
    {
        if (!Schema::hasTable('hr_countries') || !Schema::hasTable('hr_countries_translations')) {
            return 142;
        }

        static $countryTranslations = null;

        if ($countryTranslations === null) {
            $countryTranslations = DB::table('hr_countries_translations')
                ->select('country_id', 'name', 'nationality', 'name_official')
                ->whereIn('locale', ['ar', 'en'])
                ->get();
        }

        $nationalityCandidates = array_values(array_filter(array_merge(
            [
                $maid->nationality?->sub_title,
                $maid->nationality?->title,
            ],
            collect($maid->nationality?->translations ?? [])
                ->flatMap(fn ($translation) => [$translation->title ?? null, $translation->sub_title ?? null])
                ->filter()
                ->all()
        )));

        foreach ($nationalityCandidates as $candidate) {
            $normalizedCandidate = $this->normalizeHrLookupValue($candidate);

            $match = $countryTranslations->first(function ($row) use ($normalizedCandidate) {
                return in_array($normalizedCandidate, [
                    $this->normalizeHrLookupValue($row->name ?? ''),
                    $this->normalizeHrLookupValue($row->nationality ?? ''),
                    $this->normalizeHrLookupValue($row->name_official ?? ''),
                ], true);
            });

            if ($match) {
                return (int) $match->country_id;
            }
        }

        foreach ($nationalityCandidates as $candidate) {
            $normalizedCandidate = $this->normalizeHrLookupValue($candidate);

            $match = $countryTranslations->first(function ($row) use ($normalizedCandidate) {
                $haystacks = array_filter([
                    $this->normalizeHrLookupValue($row->name ?? ''),
                    $this->normalizeHrLookupValue($row->nationality ?? ''),
                    $this->normalizeHrLookupValue($row->name_official ?? ''),
                ]);

                foreach ($haystacks as $haystack) {
                    if (Str::contains($haystack, $normalizedCandidate) || Str::contains($normalizedCandidate, $haystack)) {
                        return true;
                    }
                }

                return false;
            });

            if ($match) {
                return (int) $match->country_id;
            }
        }

        return (int) (DB::table('hr_countries')->where('id', 142)->value('id')
            ?? DB::table('hr_countries')->orderBy('id')->value('id')
            ?? 142);
    }

    private function normalizeHrLookupValue(?string $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);
        $normalized = str_replace(['ة', 'ى'], ['ه', 'ي'], $normalized);
        $normalized = preg_replace('/[^[:alnum:]\p{Arabic}\s]/u', '', $normalized);

        return preg_replace('/\s+/', ' ', $normalized);
    }

    public function getMaids($request)
    {
        $query = $this->model->query();

        if ($request->nationality_id) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->religion_id) {
            $query->where('religion_id', $request->religion_id);
        }

        if ($request->job_type_id) {
            $query->whereHas('jobTypes', function ($q) use ($request) {
                $q->where('job_type_id', $request->job_type_id);
            });
        }

        if ($request->job_title_id) {
            $query->whereHas('jobTitles', function ($q) use ($request) {
                $q->where('job_title_id', $request->job_title_id);
            });
        }

        if ($request->experience_country_id) {
            $query->whereHas('expertises', function ($q) use ($request) {
                $q->where('country_id', $request->experience_country_id);
            });
        }

        if ($request->age_from && $request->age_to) {
            $query->whereBetween('age', [$request->age_from, $request->age_to]);
        }

        if ($request->experience_status) {
            $query->where('experience_status', $request->experience_status);
        }
        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param $request
     * @return mixed
     */
    public function getMaidsSearch($request)
    {
        $keyword = $request->search;

        $results = Maid::select('maids.*')
            ->leftJoin('nationalities_translations', 'maids.nationality_id', '=', 'nationalities_translations.nationality_id')
            ->leftJoin('religions_translations', 'maids.religion_id', '=', 'religions_translations.religion_id')
            ->where(function ($query) use ($keyword) {
                $query->where('nationalities_translations.title', 'LIKE', '%' . $keyword . '%')
                    ->orWhereHas('jobTypes', function ($subQ) use ($keyword) {
                        $subQ->whereTranslationLike('title', '%' . $keyword . '%');
                    })
                    ->orWhereHas('jobTitles', function ($subQ) use ($keyword) {
                        $subQ->whereTranslationLike('title', '%' . $keyword . '%');
                    })
                    ->orWhere('religions_translations.title', 'LIKE', '%' . $keyword . '%');
            })->distinct();
        return $results;
    }

    public function updateMaid($id, $data, $request)
    {
        $data = \App\Support\EducationQualificationCatalog::normalize($data);
        \Log::info('updateMaid called with data:', $data);
        \Log::info('Request data:', $request->all());

        $maid = parent::find($id);
        $hasFinancialRelations = $maid->hasFinancialEditLock();
        $hasExternalOfficeDispatch = $maid->hasExternalOfficeDispatchEditLock();
        $canOverrideTransactionEditLock = $this->canOverrideMaidTransactionEditLock();
        $hasFinancialEditLock = $hasFinancialRelations && !$canOverrideTransactionEditLock;
        // A financially linked CV may carry more than one legacy type while the edit UI
        // submits a single type_id. Preserve all stored types so editing a harmless
        // attribute never looks like (or causes) a financial classification change.
        $jobTypeIds = $hasFinancialEditLock
            ? $maid->jobTypes()->pluck('job_types.id')->map(fn ($typeId) => (int) $typeId)->all()
            : $this->resolveRequiredJobTypeIds($request, $maid);

        if ($hasFinancialEditLock) {
            // These values feed financial/contract flows. Attribute-only edits must not
            // rewrite them as a side effect of legacy form defaults or hidden fields.
            unset($data['price'], $data['salary'], $data['arrival_date'], $data['request_type']);
        }

        $this->guardFinanciallyLinkedMaidIdentityChanges(
            $maid,
            $data,
            $jobTypeIds,
            $canOverrideTransactionEditLock
        );

        if ($canOverrideTransactionEditLock && ($hasFinancialRelations || $hasExternalOfficeDispatch)) {
            \Log::warning('Privileged linked maid edit lock override used', [
                'maid_id' => $maid->id,
                'admin_id' => auth('admin')->id(),
                'financial_relations' => $hasFinancialRelations,
                'external_office_dispatch' => $hasExternalOfficeDispatch,
                'submitted_fields' => array_keys($data),
            ]);
        }

        $requestTypeColumnExists = Schema::hasColumn('maids', 'request_type');
        $offerPriceColumnExists = Schema::hasColumn('maids', 'offer_price');
        $offerDurationColumnExists = Schema::hasColumn('maids', 'offer_duration');
        $offerDescriptionColumnExists = Schema::hasColumn('maids', 'offer_description');
        $offerStartsColumnExists = Schema::hasColumn('maids', 'offer_starts_at');
        $offerExpiresColumnExists = Schema::hasColumn('maids', 'offer_expires_at');
        $borderNumberColumnExists = Schema::hasColumn('maids', 'border_number');
        $passportIssueColumnExists = Schema::hasColumn('maids', 'passport_issue_date');
        $passportExpiryColumnExists = Schema::hasColumn('maids', 'passport_expiry_date');
        $genderColumnExists = Schema::hasColumn('maids', 'gender');

        $experienceStatusValue = $data['experience_status']  ?? 'no_experience';
        // unset($data['duration'], $data['additional_notes'], $data['id']);
        unset($data['duration'], $data['id']);
        if ($experienceStatusValue === 'has_experience') {
            $totalYears = 0;
            if (isset($data['expertises']) && is_array($data['expertises'])) {
                foreach ($data['expertises'] as $expertise) {
                    $totalYears += $this->expertiseYears($expertise);
                }
            } elseif (isset($data['experience_years']) && $data['experience_years'] !== '') {
                $totalYears = (int)$data['experience_years'];
            } else {
                $totalYears = (int)($maid->experience_years ?? 0);
            }
            $data['experience_years'] = (string)$totalYears;
        } else {
            $data['experience_years'] = '0';
        }

        // dd($data, $experienceStatusValue);

        // Handle name field
        if (isset($data['name:en'])) {
            $data['name'] = $data['name:en'];
            unset($data['name:en']);
        }

        // Handle birth_date field
        if (array_key_exists('birth_date', $data)) {
            $data['birth_date'] = !empty($data['birth_date'])
                ? date('Y-m-d', strtotime($data['birth_date']))
                : null;
        }

        if (array_key_exists('age', $data)) {
            $data['age'] = $data['age'] === '' || $data['age'] === null
                ? null
                : $data['age'];
        }

        // Handle id_expiry_date field
        if (isset($data['id_expiry_date']) && !empty($data['id_expiry_date'])) {
            $data['id_expiry_date'] = date('Y-m-d', strtotime($data['id_expiry_date']));
        }

        foreach (['passport_issue_date', 'passport_expiry_date'] as $dateField) {
            if (array_key_exists($dateField, $data)) {
                $data[$dateField] = $data[$dateField]
                    ? date('Y-m-d', strtotime($data[$dateField]))
                    : null;
            }
        }

        // Handle marital_status:ar field for translation
        if (isset($data['marital_status:ar'])) {
            $maritalStatusAr = $data['marital_status:ar'];
            unset($data['marital_status:ar']);
        }

        if (isset($data['marital_status:en'])) {
            $maritalStatusEn = $data['marital_status:en'];
            unset($data['marital_status:en']);
        }

        // Handle languages field for translation and main columns
        $languagesData = $request->input('languages', []);
        $data['languages'] = $languagesData;

        $oldMediaFiles = [];
        $newMediaFiles = [];
        $removeVideo = $request->boolean('remove_video');

        try {
            if (isset($data['cv_file'])) {
                $oldMediaFiles[] = $maid->cv_file;
                $image = $data['cv_file'];
                $data['cv_file'] = uploadMaidFile($image, $this->fileFolder);
                $newMediaFiles[] = $data['cv_file'];
            }
            if (isset($data['cv_preview'])) {
                $oldMediaFiles[] = $maid->cv_preview;
                $image = $data['cv_preview'];
                $data['cv_preview'] = uploadMaidFile($image, $this->fileFolder);
                $newMediaFiles[] = $data['cv_preview'];
            }
            if (isset($data['maid_cv_file'])) {
                $oldMediaFiles[] = $maid->maid_cv_file;
                $file = $data['maid_cv_file'];
                $data['maid_cv_file'] = uploadMaidFile($file, $this->fileFolder);
                $newMediaFiles[] = $data['maid_cv_file'];
            }
            if (isset($data['image'])) {
                $oldMediaFiles[] = $maid->image;
                $image = $data['image'];
                $data['image'] = uploadMaidFile($image, $this->fileFolder);
                $newMediaFiles[] = $data['image'];
            }
            // مرفق رخصة القيادة: يُرفع مع "نعم" فقط، ويُحذف المرفق القديم عند تغيير الإجابة إلى "لا" أو عند استبداله.
            $hasDrivingLicense = array_key_exists('has_driving_license', $data)
                ? $this->normalizeNullableBoolean($data['has_driving_license'])
                : $maid->has_driving_license;
            if (($data['gender'] ?? $maid->gender) !== 'male') {
                $hasDrivingLicense = null;
            }
            if ($hasDrivingLicense === true
                && isset($data['driving_license_file'])
                && $data['driving_license_file'] instanceof \Illuminate\Http\UploadedFile) {
                $oldMediaFiles[] = $maid->driving_license_file;
                $data['driving_license_file'] = uploadMaidFile($data['driving_license_file'], $this->fileFolder);
                $newMediaFiles[] = $data['driving_license_file'];
            } elseif ($hasDrivingLicense !== true && filled($maid->driving_license_file)) {
                $oldMediaFiles[] = $maid->driving_license_file;
                $data['driving_license_file'] = null;
            } else {
                unset($data['driving_license_file']);
            }
            if ($removeVideo) {
                $oldMediaFiles[] = $maid->video;
                $data['video'] = null;
            } elseif (isset($data['video'])) {
                $oldMediaFiles[] = $maid->video;
                $video = $data['video'];
                $data['video'] = $this->uploadMaidVideo($video);
                $newMediaFiles[] = $data['video'];
            }
        } catch (\Throwable $exception) {
            foreach (array_filter($newMediaFiles) as $newMediaFile) {
                deleteFile($newMediaFile);
            }

            throw $exception;
        }

        unset($data['remove_video']);

        // Convert skills array to JSON if it exists
        if (isset($data['skills']) && is_array($data['skills'])) {
            $filteredSkills = array_values(array_filter($data['skills'], function ($value) {
                return $value !== null && $value !== '';
            }));
            $data['skills'] = json_encode($filteredSkills);
        }

        // Normalize and persist languages + percentages
        $langs = is_array($data['languages']) ? array_values(array_filter($data['languages'], fn($v) => $v !== null && $v !== '')) : [];
        [$percs, $languageLevels] = $this->normalizeLanguageRatings(
            $langs,
            $request->input('percentage', []),
            $request->input('language_levels', []),
            $request->input('language_rating_mode', [])
        );

        $data['languages'] = json_encode($langs, JSON_UNESCAPED_UNICODE);
        $data['percentage'] = json_encode($percs, JSON_UNESCAPED_UNICODE);
        $data['language_levels'] = json_encode($languageLevels, JSON_UNESCAPED_UNICODE);

        if ($requestTypeColumnExists) {
            if ($hasFinancialEditLock) {
                $data['request_type'] = Maid::resolveRequestType($maid->request_type);
            } else {
                $data['request_type'] = array_key_exists('request_type', $data)
                    ? Maid::resolveRequestType($data['request_type'])
                    : Maid::resolveRequestType($maid->request_type);
                if (in_array($this->getRentalJobTypeId(), $jobTypeIds, true)) {
                    $data['request_type'] = Maid::REQUEST_TYPE_CUSTOM;
                }
            }
        } else {
            unset($data['request_type']);
        }

        if ($offerPriceColumnExists) {
            if (array_key_exists('offer_price', $data)) {
                $offerPrice = $data['offer_price'];
                $data['offer_price'] = ($offerPrice === '' || $offerPrice === null) ? null : round((float)$offerPrice, 2);
            } else {
                unset($data['offer_price']);
            }
        } else {
            unset($data['offer_price']);
        }

        if ($offerDescriptionColumnExists) {
            $data['offer_description'] = array_key_exists('offer_description', $data)
                ? ((isset($data['offer_description']) && trim((string)$data['offer_description']) !== '') ? $data['offer_description'] : null)
                : $maid->offer_description;
        } else {
            unset($data['offer_description']);
        }

        if ($offerStartsColumnExists) {
            if (array_key_exists('offer_starts_at', $data)) {
                $data['offer_starts_at'] = $data['offer_starts_at']
                    ? date('Y-m-d', strtotime($data['offer_starts_at']))
                    : null;
            } else {
                unset($data['offer_starts_at']);
            }
        } else {
            unset($data['offer_starts_at']);
        }

        if ($offerExpiresColumnExists) {
            if (array_key_exists('offer_expires_at', $data)) {
                $data['offer_expires_at'] = $data['offer_expires_at']
                    ? date('Y-m-d', strtotime($data['offer_expires_at']))
                    : null;
            } else {
                unset($data['offer_expires_at']);
            }
        } else {
            unset($data['offer_expires_at']);
        }

        if ($offerDurationColumnExists) {
            if (array_key_exists('offer_starts_at', $data) || array_key_exists('offer_expires_at', $data)) {
                $offerStartsAt = !empty($data['offer_starts_at']) ? Carbon::parse($data['offer_starts_at']) : null;
                $offerExpiresAt = !empty($data['offer_expires_at']) ? Carbon::parse($data['offer_expires_at']) : null;

                $data['offer_duration'] = $offerStartsAt && $offerExpiresAt && $offerExpiresAt->gte($offerStartsAt)
                    ? (string) ($offerStartsAt->diffInDays($offerExpiresAt) + 1)
                    : null;
            } else {
                unset($data['offer_duration']);
            }
        } else {
            unset($data['offer_duration']);
        }

        if (!$borderNumberColumnExists) {
            unset($data['border_number']);
        }
        if (!$passportIssueColumnExists) {
            unset($data['passport_issue_date']);
        }
        if (!$passportExpiryColumnExists) {
            unset($data['passport_expiry_date']);
        }
        if ($genderColumnExists) {
            $data['gender'] = in_array($data['gender'] ?? null, ['male', 'female'], true) ? $data['gender'] : ($maid->gender ?? 'female');
        } else {
            unset($data['gender']);
        }

        if (array_key_exists('has_driving_license', $data)) {
            $data['has_driving_license'] = $this->normalizeNullableBoolean($data['has_driving_license']);
        }

        $row = parent::find($id);

        if ($experienceStatusValue !== 'has_experience') {
            $row->expertises()->delete();
            unset($data['expertises']);
        }

        try {
            $row->update($data);
        } catch (\Throwable $exception) {
            foreach (array_filter($newMediaFiles) as $newMediaFile) {
                deleteFile($newMediaFile);
            }

            throw $exception;
        }

        foreach (array_filter($oldMediaFiles) as $oldMediaFile) {
            deleteFile($oldMediaFile);
        }

        $this->syncLegacyMaidLanguageTranslations($row, $langs);

        if (isset($data['expertises']) && $experienceStatusValue === 'has_experience') {
            $row->expertises()->delete();
            foreach ($data['expertises'] as $expertise) {
                $expertise['maid_id'] = $maid->id;
                $this->maidExpertiseAction->store($expertise);
            }
        }

        // Handle translation fields
        if (isset($maritalStatusAr)) {
            $row->translateOrNew('ar')->marital_status = $maritalStatusAr;
        }
        if (isset($maritalStatusEn)) {
            $row->translateOrNew('en')->marital_status = $maritalStatusEn;
        }

        if (isset($maritalStatusAr) || isset($maritalStatusEn)) {
            $row->save();
        }

        \Log::info('Final data to update:', $data);
        \Log::info('Maid ID:', ['id' => $id]);

        if ($request->job_id) {
            $maid->jobTitles()->sync($request->job_id);
        }
        $maid->jobTypes()->sync($jobTypeIds);
        if (!$hasFinancialEditLock) {
            $this->syncRentalMaidFinancialAdjustmentsOnUpdate($maid, $request);
        }

        // Handle shelter contract creation for rental maids
        if ($request->has('create_shelter_contract') && $request->create_shelter_contract == '1') {
            $this->createShelterContractForMaid($maid);
        }

        $this->syncHrMaidRecord($maid->fresh(['jobTypes', 'nationality', 'office']));
        return parent::find($id);
    }

    protected function guardFinanciallyLinkedMaidIdentityChanges(
        Maid $maid,
        array $data,
        array $newJobTypeIds,
        bool $canOverride = false
    ): void
    {
        if ($canOverride) {
            return;
        }

        $lockedFields = [];
        $hasFinancialEditLock = $maid->hasFinancialEditLock();
        $hasExternalOfficeDispatchEditLock = $maid->hasExternalOfficeDispatchEditLock();
        $hasIdentityEditLock = $hasFinancialEditLock || $hasExternalOfficeDispatchEditLock;

        if (
            $hasIdentityEditLock
            && array_key_exists('passport_num', $data)
            && trim((string) $data['passport_num']) !== trim((string) $maid->passport_num)
        ) {
            $lockedFields['passport_num'] = 'لا يمكن تعديل رقم جواز العاملة بعد ارتباط السيرة بفاتورة أو قيد محاسبي أو إرسالية مكتب خارجي.';
        }

        if (
            $hasIdentityEditLock
            && array_key_exists('nationality_id', $data)
            && $this->hasStoredValue($maid->nationality_id)
            && (int) $data['nationality_id'] !== (int) $maid->nationality_id
        ) {
            $lockedFields['nationality_id'] = 'لا يمكن تعديل جنسية العاملة بعد ارتباط السيرة بفاتورة أو قيد محاسبي أو إرسالية مكتب خارجي.';
        }

        if (
            $hasIdentityEditLock
            && array_key_exists('office_id', $data)
            && $this->hasStoredValue($maid->office_id)
            && $this->nullableInt($data['office_id']) !== $this->nullableInt($maid->office_id)
        ) {
            $lockedFields['office_id'] = 'لا يمكن تعديل الوكيل/المكتب الخارجي بعد ارتباط السيرة بفاتورة أو قيد محاسبي أو إرسالية مكتب خارجي.';
        }

        $currentJobTypeIds = $maid->jobTypes()
            ->pluck('job_types.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
        $submittedJobTypeIds = collect($newJobTypeIds)
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($hasFinancialEditLock && $currentJobTypeIds !== [] && $currentJobTypeIds !== $submittedJobTypeIds) {
            $lockedFields['type_id'] = 'لا يمكن تعديل نوع السيرة لوجود فاتورة مالية أو قيد محاسبي مرتبط بالسيرة أو عقدها.';
        }

        if (
            $hasFinancialEditLock
            && array_key_exists('request_type', $data)
            && Maid::normalizeRequestType($maid->request_type) !== null
            && Maid::resolveRequestType($data['request_type']) !== Maid::resolveRequestType($maid->request_type)
        ) {
            $lockedFields['request_type'] = 'لا يمكن تعديل نوع طلب السيرة لوجود فاتورة مالية أو قيد محاسبي مرتبط بالسيرة أو عقدها.';
        }

        if ($lockedFields === []) {
            return;
        }

        throw ValidationException::withMessages($lockedFields);
    }

    protected function canOverrideMaidTransactionEditLock(): bool
    {
        return CvOptionsSettings::maidTransactionEditOverrideEnabled();
    }

    protected function activeOrderStatuses(): array
    {
        return ['new', 'under_process', 'contracted', 'contracted_without_contract'];
    }

    protected function recruitmentContractStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
        ];
    }

    protected function rentalActiveContractStatuses(): array
    {
        return ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'transferred', 'suspended', 'frozen'];
    }

    protected function nullableInt($value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    protected function normalizeNullableBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function normalizeLanguageRatings(
        array $selectedLanguages,
        $percentageInput,
        $levelInput,
        $modeInput
    ): array {
        $selectedLanguages = array_values(array_unique(array_filter($selectedLanguages)));
        $selectedKeys = array_flip($selectedLanguages);
        $percentageInput = is_array($percentageInput) ? $percentageInput : [];
        $levelInput = is_array($levelInput) ? $levelInput : [];
        $mode = is_array($modeInput)
            ? (in_array('level', $modeInput, true) ? 'level' : 'percentage')
            : ($modeInput === 'level' ? 'level' : 'percentage');

        $percentages = $mode === 'percentage'
            ? array_filter(
                array_intersect_key($percentageInput, $selectedKeys),
                fn ($value) => $value !== null && $value !== ''
            )
            : [];
        $levels = [];

        foreach ($selectedLanguages as $language) {
            $level = strtolower(trim((string) ($levelInput[$language] ?? '')));
            $mappedPercentage = Maid::percentageForLanguageLevel($level);

            if ($mode === 'level' && $mappedPercentage !== null) {
                $levels[$language] = $level;
                $percentages[$language] = $mappedPercentage;
            }
        }

        return [$percentages, $levels];
    }

    protected function hasStoredValue($value): bool
    {
        $value = $this->nullableInt($value);

        return $value !== null && $value > 0;
    }

    private function syncLegacyMaidLanguageTranslations(Maid $maid, array $languages): void
    {
        $encodedLanguages = json_encode(array_values($languages), JSON_UNESCAPED_UNICODE);
        $locales = collect([app()->getLocale(), 'ar', 'en'])
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($locales as $locale) {
            $maid->translateOrNew($locale)->languages = $encodedLanguages;
        }

        $maid->save();
    }

    /**
     * Create shelter contract for a rental maid
     */
    private function createShelterContractForMaid($maid)
    {
        try {
            DB::beginTransaction();

            $existingShelterContract = Contract::where('maid_id', $maid->id)
                ->where('status', ContractStatusEnum::Shelter->value)
                ->first();

            if ($existingShelterContract) {
                DB::rollBack();
                \Log::info('Maid already has a shelter contract', ['maid_id' => $maid->id]);
                return;
            }

            $companyClient = $this->resolveShelterCompanyClient();

            $contract = Contract::create([
                'user_id' => $companyClient->id,
                'maid_id' => $maid->id,
                'status' => ContractStatusEnum::Shelter->value,
                'contract_date' => now()->toDateString(),
            ]);

            ContractInfo::create([
                'contract_id' => $contract->id,
                'contract_date' => now()->toDateString(),
                'msaned_number' => '',
                'shelter_id' => 1,
                'wants_to_work' => $this->isRentalMaid($maid) ? 'yes' : 'unknown',
                'receipt_source' => 'company',
            ]);

            $maid->update([
                'status' => 'available',
                'maid_accommodation_type' => 2,
                'available' => 1,
                'available_at' => now()->toDateString(),
            ]);

            DB::commit();
            \Log::info('Shelter contract created successfully', ['contract_id' => $contract->id, 'maid_id' => $maid->id]);
            return;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create shelter contract', [
                'maid_id' => $maid->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return;
        }
    }

    private function shouldCreateRentalRecruitmentContract(Maid $maid, $request): bool
    {
        return $this->isRentalTypeRequest($request)
            && $request->input('rental_kingdom_status') === 'outside'
            && !$this->hasActiveRecruitmentContract($maid);
    }

    private function hasActiveRecruitmentContract(Maid $maid): bool
    {
        return Contract::query()
            ->where('maid_id', $maid->id)
            ->whereIn('status', [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value,
            ])
            ->exists();
    }

    private function createCompanyRecruitmentContractForRentalMaid(Maid $maid, $request): ?Contract
    {
        try {
            $companyClient = $this->resolveShelterCompanyClient();

            $contract = Contract::create([
                'user_id' => $companyClient->id,
                'maid_id' => $maid->id,
                'status' => ContractStatusEnum::New->value,
                'contract_date' => now()->toDateString(),
                'marketer_id' => auth('admin')->id() ?? auth()->id(),
            ]);

            ContractInfo::create([
                'contract_id' => $contract->id,
                'contract_date' => now()->toDateString(),
                'msaned_number' => '',
                'tafwed_number' => '',
                'visa_number' => '',
                'contract_duration' => $request->input('duration'),
            ]);

            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::New->value,
            ]);

            $maid->update([
                'status' => 'reserved',
                'available' => 0,
                'available_at' => null,
                'maid_accommodation_type' => null,
            ]);

            \Log::info('Company recruitment contract created for not-arrived rental maid', [
                'contract_id' => $contract->id,
                'maid_id' => $maid->id,
                'company_client_id' => $companyClient->id,
            ]);

            return $contract;
        } catch (\Throwable $exception) {
            \Log::error('Failed to create company recruitment contract for rental maid', [
                'maid_id' => $maid->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    private function resolveShelterCompanyClient(): ClientUser
    {
        return app(\App\Services\ShelterCompanyClientResolver::class)->resolve();
    }

    public function togglePin($id)
    {
        $maid = parent::find($id);
        $maid->is_pinned = !$maid->is_pinned;
        $maid->save();
        return $maid;
    }

    protected function availableQuery()
    {
        $today = now()->toDateString();

        return $this->model
            ->shown()
            // أي طلب قائم على السيرة (استقدام أو إيجار أو نقل خدمات) يخفيها من الموقع
            // الخارجي، بما في ذلك سير الإيجار المتاحة للاستلام الفوري من السكن.
            ->withoutActiveOrders()
            ->where(function ($query) use ($today) {
                $query->where(function ($available) use ($today) {
                    $available->where('available', true)
                        ->whereIn('status', ['available', 'completed'])
                        ->where(function ($dateQuery) use ($today) {
                            $dateQuery->whereNull('available_at')
                                ->orWhere('available_at', '<=', $today);
                        })
                        ->whereDoesntHave('contracts', function ($contractQuery) {
                            $contractQuery->whereIn('status', [
                                ContractStatusEnum::New->value,
                                ContractStatusEnum::Msaned->value,
                                ContractStatusEnum::Tafwed->value,
                                ContractStatusEnum::Taffez->value,
                                ContractStatusEnum::Tazker->value,
                                ContractStatusEnum::Arrival->value,
                            ]);
                        });
                })->orWhere(function ($shelter) use ($today) {
                    $this->applyShelterInstantFilters($shelter, $today);
                });
            });
    }

    private function applyShelterInstantFilters($query, string $today): void
    {
        $query
            ->whereIn('status', ['in_accommodation', 'available', 'completed', 'reserved'])
            ->whereHas('contracts', function ($contractQuery) {
                $contractQuery->insideShelter()
                    ->whereHas('info', function ($infoQuery) {
                        $infoQuery->whereIn('is_available_for_instant', [true, 1, '1'])
                            ->where(function ($wantsToWorkQuery) {
                                $wantsToWorkQuery
                                    ->whereRaw('LOWER(TRIM(COALESCE(wants_to_work, ""))) = ?', ['yes'])
                                    ->orWhereIn('wants_to_work', [1, '1', true]);
                            });
                    });
            });
    }

    private function excludeShelterInstantFilters($query): void
    {
        $query->whereDoesntHave('contracts', function ($contractQuery) {
            $contractQuery->insideShelter()
                ->whereHas('info', function ($infoQuery) {
                    $infoQuery->whereIn('is_available_for_instant', [true, 1, '1'])
                        ->where(function ($wantsToWorkQuery) {
                            $wantsToWorkQuery
                                ->whereRaw('LOWER(TRIM(COALESCE(wants_to_work, ""))) = ?', ['yes'])
                                ->orWhereIn('wants_to_work', [1, '1', true]);
                        });
                });
        });
    }

    public function paginate($request, $limit = 10)
    {
        $limit = min(
            max((int) $request->input('per_page', $limit), 10),
            100
        );

        $type = match ($request->type) {
            'estqdam', 'estgdam' => 1,
            'egar' => 2,
            'transfer' => 3,
            default => null,
        };

        $today = now()->toDateString();

        $orderSeed = $this->resolvePaginationOrderSeed($request);

        return $this->availableQuery()
            ->with([
                'jobTypes',
                'jobTitles',
                'nationality',
                'religion',
                'expertises.country',
                'expertises.jobTitle',
                'contracts.info',
            ])
            ->when(SystemModuleManager::isEnabled('branches'), fn ($query) => $query->with('branch'))
            ->when(isset($type), function ($query) use ($type, $today) {
                if ($type === 3) {
                    $query->where(function ($transferQuery) use ($today) {
                        $transferQuery->whereHas('jobTypes', function ($q) {
                            $q->where('job_type_id', 3);
                        })->orWhere(function ($orQuery) use ($today) {
                            $this->applyShelterInstantFilters($orQuery, $today);
                        });
                    });

                    if (CvOptionsSettings::rentalTransferEnabled()) {
                        // عاملة الإيجار تدخل نقل الخدمات فقط عند تفعيلها للاستلام الفوري.
                        $query->where(function ($rentalQuery) use ($today) {
                            $rentalQuery->whereDoesntHave('jobTypes', function ($q) {
                                $q->where('job_type_id', 2);
                            })->orWhere(function ($instantQuery) use ($today) {
                                $this->applyShelterInstantFilters($instantQuery, $today);
                            });
                        });
                    } else {
                        $query->whereDoesntHave('jobTypes', function ($rentalQuery) {
                            $rentalQuery->where('job_type_id', 2);
                        });
                    }
                } else {
                    $query->whereHas('jobTypes', function ($q) use ($type) {
                        $q->where('job_type_id', $type);
                    });

                    // Rental keeps every rental worker, including the ones sitting
                    // in the shelter and flagged for instant pickup: those are
                    // precisely the workers a client can rent today. Recruitment
                    // still excludes them, because they are already in-country.
                    if ($type !== 2) {
                        $this->excludeShelterInstantFilters($query);
                    }
                }
            })
            ->when(isset($request->nationality_id), function ($query) use ($request) {
                $query->where('nationality_id', $request->nationality_id);
            })
            // فلاتر اختيارية من بحث الصفحة الرئيسية في المواقع الخارجية؛ لا تؤثر
            // على أي موقع لا يرسلها. رقم الجواز مطابقة كاملة فقط حتى لا يُستخدم
            // البحث الجزئي لاستعراض الجوازات.
            ->when($request->filled('religion_id'), function ($query) use ($request) {
                $query->where('religion_id', (int) $request->input('religion_id'));
            })
            ->when($request->filled('passport_num'), function ($query) use ($request) {
                $query->where('passport_num', trim((string) $request->input('passport_num')));
            })
            ->when(isset($request->experience_country_id), function ($query) use ($request) {
                $query->whereHas('expertises', function ($q) use ($request) {
                    $q->where('country_id', $request->experience_country_id);
                });
            })
            ->when(isset($request->experience_status), function ($query) use ($request) {
                $query->where('experience_status', $request->experience_status);
            })
            ->when($request->filled('marital_status'), function ($query) use ($request) {
                $maritalStatuses = match ($request->marital_status) {
                    'single' => ['single', 'عازبة', 'أعزب'],
                    'married' => ['married', 'متزوجة', 'متزوج'],
                    default => [],
                };

                if (!empty($maritalStatuses)) {
                    $query->whereHas('translations', function ($translationQuery) use ($maritalStatuses) {
                        $translationQuery->whereIn('marital_status', $maritalStatuses);
                    });
                }
            })
            ->when(isset($request->age), function ($query) use ($request) {
                if ($request->age == '20_30') {
                    $query->whereBetween('age', [20, 30]);
                } elseif ($request->age == '31_40') {
                    $query->whereBetween('age', [31, 40]);
                } elseif ($request->age == '41_50') {
                    $query->whereBetween('age', [41, 50]);
                }
            })
            ->when(isset($request->job), function ($query) use ($request) {
                $query->whereHas('jobTitles', function ($q) use ($request) {
                    $q->where('job_title_id', $request->job);
                });
            })
            ->when(
                $type === 2
                    && SystemModuleManager::isEnabled('branches')
                    && Schema::hasColumn('maids', 'branch_id')
                    && $request->filled('branch_id'),
                fn ($query) => $query->where('branch_id', (int) $request->input('branch_id'))
            )
            ->orderBy('is_pinned', 'desc')
            // Stable random order (same seed) to avoid duplicates between pagination pages.
            ->orderByRaw('MD5(CONCAT(maids.id, ?))', [$orderSeed])
            ->orderBy('id', 'desc')
            ->paginate($limit);
    }

    /**
     * Keep the randomized CV order identical while moving between pages.
     *
     * The public website stores a seed in the session. API consumers are
     * usually stateless, so their fallback seed is derived from the active
     * filters (without the page number) instead of the current timestamp.
     */
    private function resolvePaginationOrderSeed($request): string
    {
        if ($request->filled('order_seed')) {
            return (string) $request->input('order_seed');
        }

        $sessionSeed = session('all_workers_order_seed');

        if (filled($sessionSeed)) {
            return (string) $sessionSeed;
        }

        $filters = Arr::sortRecursive($request->except(['page', 'order_seed']));

        return hash('sha256', json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function findAvailable($id)
    {
        return $this->availableQuery()->find($id);
    }

    private function uploadMaidVideo(\Illuminate\Http\UploadedFile $video): string
    {
        try {
            return uploadMaidFile($video, $this->fileFolder);
        } catch (\Throwable $exception) {
            report($exception);
            \Log::error('Maid video upload failed.', [
                'admin_id' => auth('admin')->id(),
                'original_name' => $video->getClientOriginalName(),
                'size' => $video->getSize(),
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'video' => [__('ingaz.maid_video_storage_failed')],
            ]);
        }
    }
}
