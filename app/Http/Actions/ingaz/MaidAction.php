<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Actions\MainAction;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User as ClientUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaidAction extends MainAction
{
    public function __construct(Maid $model, protected MaidExpertiseAction $maidExpertiseAction)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }

    public function storeMaid($data, $request)
    {
        \Log::info('Raw data received in storeMaid:', $data);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));
        \Log::info('Languages data: ', is_array($data['languages'] ?? null) ? $data['languages'] : [$data['languages'] ?? 'not set']);
        \Log::info('Name data: ' . ($data['name'] ?? 'not set'));
        \Log::info('Name:en data: ' . ($data['name:en'] ?? 'not set'));

        if (isset($data['cv_file']) && $data['cv_file'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_file'];
            $data['cv_file'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['cv_preview']) && $data['cv_preview'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_preview'];
            $data['cv_preview'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['video']) && $data['video'] instanceof \Illuminate\Http\UploadedFile) {
            $video = $data['video'];
            $data['video'] = uploadFile($video, $this->fileFolder);
        }

        // Normalize experience values before defaults so they survive cleanup
        $experienceStatus = $data['experience_status'] ?? 'no_experience';
        if ($experienceStatus === 'has_experience') {
            $totalYears = 0;
            if (isset($data['expertises']) && is_array($data['expertises'])) {
                foreach ($data['expertises'] as $expertise) {
                    $totalYears += (float)($expertise['number_of_years'] ?? 0);
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

        // Set default values for required fields if they're empty
        $data['name'] = $data['name:en'] ?? '';
        $data['nationality_id'] = $data['nationality_id'] ?? null;
        $data['religion_id'] = $data['religion_id'] ?? null;
        $data['passport_num'] = $data['passport_num'] ?? '';
        $data['id_number'] = $data['id_number'] ?? '';
        $data['id_expiry_date'] = $data['id_expiry_date'] ?? null;
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
        $data['is_show'] = $data['is_show'] ?? 1;

        // Clean the data but preserve required fields - only remove truly empty strings, not zeros or nulls
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) === '' && !in_array($key, ['nationality_id', 'religion_id', 'passport_num', 'id_number', 'id_expiry_date'])) {
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

        // Ensure these required fields are always included
        $data['passport_num'] = $data['passport_num'] ?? '';
        $data['id_number'] = $data['id_number'] ?? '';
        $data['id_expiry_date'] = $data['id_expiry_date'] ?? null;

        // Robust handling for languages field
        \Log::info('Before languages processing - languages data:', [$data['languages'] ?? 'not set']);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));

        // ✅ لو فيه لغات
        if (isset($data['languages']) && is_array($data['languages'])) {

            // أولاً فلتر النِّسب بحيث تبقى فقط اللغات اللي متعلم عليها وفيها رقم
            if (isset($data['percentage']) && is_array($data['percentage'])) {
                $data['percentage'] = array_filter(
                    array_intersect_key($data['percentage'], $data['languages']),
                    fn($v) => $v !== null && $v !== ''
                );
            } else {
                $data['percentage'] = [];
            }

            // خزّن اللغات كـ JSON array (القيم فقط: ["arabic","amharic"])
            $data['languages'] = json_encode(array_values($data['languages']), JSON_UNESCAPED_UNICODE);

            // خزّن النِّسب كـ JSON object (مثل {"arabic":"98","amharic":"90"})
            $data['percentage'] = json_encode($data['percentage'], JSON_UNESCAPED_UNICODE);

        } else {
            // لو مفيش لغات أصلاً
            $data['languages'] = json_encode([], JSON_UNESCAPED_UNICODE);
            $data['percentage'] = json_encode([], JSON_UNESCAPED_UNICODE);
        }

        // Normalize request type and offer fields
        $data['request_type'] = $data['request_type'] ?? 'known';

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

        // Verify that the nationality and religion exist in the database
        $nationality = \App\Models\ingaz\Nationality::find($data['nationality_id']);
        if (!$nationality) {
            throw new \Exception('Selected nationality does not exist in the database');
        }

        $religion = \App\Models\ingaz\Religion::find($data['religion_id']);
        if (!$religion) {
            throw new \Exception('Selected religion does not exist in the database');
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
                    $data[$key] = implode(',', $value);
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

        // Ensure all required fields are present in the data array
        $createData = [
            'nationality_id' => $data['nationality_id'],
            'religion_id' => $data['religion_id'],
            'passport_num' => $data['passport_num'],
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
            'skills' => $data['skills'] ?? null,
            'languages' => $data['languages'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'cv_file' => $data['cv_file'] ?? '',
            'image' => $data['image'] ?? null,
            'video_link' => $data['video_link'] ?? null,
            'video' => $data['video'] ?? null,
            'cv_preview' => $data['cv_preview'] ?? null,
            'office_id' => $data['office_id'] ?? null,
            'status' => $data['status'],
            'added_by'=> $data['added_by'] ?? null,
            'available' => $data['available'],
            'available_at' => $data['available_at'],
            'is_show' => $data['is_show'],
            'name' => $data['name'],
        ];

        
        if ($requestTypeColumnExists) {
            $createData['request_type'] = $data['request_type'] ?? 'known';
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


        \Log::info('Final create data:', $createData);
        \Log::info('About to create maid with data keys: ' . implode(', ', array_keys($createData)));
        \Log::info('Languages in createData: ' . ($createData['languages'] ?? 'null'));
        \Log::info('Name in createData: ' . ($data['name'] ?? 'null'));

        // Handle marital_status:ar field for translation
        if (isset($data['marital_status:ar'])) {
            $maritalStatusAr = $data['marital_status:ar'];
            unset($data['marital_status:ar']);
        }
        if (isset($data['marital_status:en'])) {
            $maritalStatusEn = $data['marital_status:en'];
            unset($data['marital_status:en']);
        }

        try {
            // dd($createData, $data,$maritalStatusAr, $maritalStatusEn);
            $maid = $this->model->create($createData);
            \Log::info('Maid created successfully with ID: ' . $maid->id);

            // Handle translation fields
            if (isset($maritalStatusAr)) {
                $maid->translateOrNew('ar')->marital_status = $maritalStatusAr;
            }
            if (isset($maritalStatusEn)) {
                $maid->translateOrNew('en')->marital_status = $maritalStatusEn;
            }

            // توليد cv_code بناءً على المكتب
            if ($maid->office_id || $maid->office?->title) {
                // أول 3 أحرف من اسم المكتب
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $maid->office->title), 0, 3));
                if ($prefix === '') $prefix = 'OFF';

                // رقم تسلسلي حسب عدد العاملات في نفس المكتب
                $count = $this->model->where('office_id', $maid->office_id)
                    ->where('id', '<=', $maid->id)
                    ->count();

                $length = strlen((string) $count);
                $cvCode = $prefix . "/" . str_pad($count, max(3, $length), '0', STR_PAD_LEFT);

                $maid->cv_code = $cvCode;
                $maid->save();

                \Log::info('Mahmod Amer $cvCode: ' . ($cvCode ?? 'no cv_code'));
            }
        } catch (\Exception $e) {
            \Log::error('Error creating maid: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            throw $e;
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

        if ($request->type_id) {
            \Log::info('About to attach job types: ' . print_r($request->type_id, true));
            $maid->jobTypes()->attach($request->type_id);
            \Log::info('Job types attached successfully');
        } else {
            \Log::info('Skipping job types attach - no type_id provided.');
        }

        // Create Shelter contract if user selected the option
        try {
            $createShelterContract = $request->create_shelter_contract ?? '0';

            if ($createShelterContract === '1') {
                DB::beginTransaction();
                // Find or create company client )
                $companyClient = ClientUser::where('name', 'like', '%شركة  بلاتنيوم الشرقية %')
                    ->orWhere('email', 'company@platinumestern.com')
                    ->orWhere('phone', '0500000002')
                    ->first();

                if (!$companyClient) {
                    $companyClient = ClientUser::create([
                        'name' => ' شركة بلاتنيوم الشرقية للاستقدام ',
                        'email' => 'company@platinumestern.com',
                        'phone' => '0500000002',
                        'password' => bcrypt('password123'),
                    ]);
                }

                // Create shelter contract
                $contract = Contract::create([
                    'user_id' => $companyClient->id,
                    'maid_id' => $maid->id,
                    'status' => ContractStatusEnum::Shelter->value,
                    'contract_date' => now()->toDateString(),
                ]);

                // Create contract info with complete data
                ContractInfo::create([
                    'contract_id' => $contract->id,
                    'contract_date' => now()->toDateString(),
                    'msaned_number' => '',
                    'shelter_id' => 1, // Default shelter
                    'wants_to_work' => 'unknown',
                    'receipt_source' => 'company', // Use translatable key
                ]);

                // Update maid status to in accommodation but available for work
                $maid->update([
                    // 'status' => 'in_accommodation',
                    'status' => 'available',
                    'maid_accommodation_type' => 2,
                    'available' => 1, // متاحة للعمل
                    'available_at' => now()->toDateString(),
                ]);

                DB::commit();
                \Log::info('Shelter contract created by user choice for maid ID: ' . $maid->id . ' with contract ID: ' . $contract->id);
            }
        } catch (\Throwable $t) {
            DB::rollBack();
            \Log::error('Failed to create shelter contract for maid: ' . $t->getMessage());
        }

        return $maid;
    }

    public function storeRapidCvMaid($createData, $request){
        $maid = $this->model->create($createData);
        $maid->jobTypes()->attach(1); // استقدام
        return $maid;
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
        \Log::info('updateMaid called with data:', $data);
        \Log::info('Request data:', $request->all());

        $maid = parent::find($id);

        $requestTypeColumnExists = Schema::hasColumn('maids', 'request_type');
        $offerPriceColumnExists = Schema::hasColumn('maids', 'offer_price');
        $offerDurationColumnExists = Schema::hasColumn('maids', 'offer_duration');
        $offerDescriptionColumnExists = Schema::hasColumn('maids', 'offer_description');
        $offerStartsColumnExists = Schema::hasColumn('maids', 'offer_starts_at');
        $offerExpiresColumnExists = Schema::hasColumn('maids', 'offer_expires_at');

        $experienceStatusValue = $data['experience_status']  ?? 'no_experience';
        // unset($data['duration'], $data['additional_notes'], $data['id']);
        unset($data['duration'], $data['id']);
        if ($experienceStatusValue === 'has_experience') {
            $totalYears = 0;
            if (isset($data['expertises']) && is_array($data['expertises'])) {
                foreach ($data['expertises'] as $expertise) {
                    $totalYears += (float)($expertise['number_of_years'] ?? 0);
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
        if (isset($data['birth_date']) && !empty($data['birth_date'])) {
            $data['birth_date'] = date('Y-m-d', strtotime($data['birth_date']));
        }

        // Handle id_expiry_date field
        if (isset($data['id_expiry_date']) && !empty($data['id_expiry_date'])) {
            $data['id_expiry_date'] = date('Y-m-d', strtotime($data['id_expiry_date']));
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

        if (isset($data['cv_file'])) {
            deleteFile($maid->cv_file);
            $image = $data['cv_file'];
            $data['cv_file'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['cv_preview'])) {
            deleteFile($maid->cv_preview);
            $image = $data['cv_preview'];
            $data['cv_preview'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['image'])) {
            deleteFile($maid->image);
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['video'])) {
            deleteFile($maid->video);
            $video = $data['video'];
            $data['video'] = uploadFile($video, $this->fileFolder);
        }

        // Convert skills array to JSON if it exists
        if (isset($data['skills']) && is_array($data['skills'])) {
            $filteredSkills = array_values(array_filter($data['skills'], function ($value) {
                return $value !== null && $value !== '';
            }));
            $data['skills'] = json_encode($filteredSkills);
        }

        // Normalize and persist languages + percentages
        $langs = is_array($data['languages']) ? array_values(array_filter($data['languages'], fn($v) => $v !== null && $v !== '')) : [];
        $percentageInput = $request->input('percentage', []);
        $percs = [];
        if (is_array($percentageInput)) {
            $percs = array_filter(
                array_intersect_key($percentageInput, array_flip($langs)),
                fn($v) => $v !== null && $v !== ''
            );
        }

        $data['languages'] = json_encode($langs, JSON_UNESCAPED_UNICODE);
        $data['percentage'] = json_encode($percs, JSON_UNESCAPED_UNICODE);

        // Also store localized languages list
        $translation = $maid->translateOrNew(app()->getLocale());
        $translation->languages = json_encode($langs, JSON_UNESCAPED_UNICODE);
        $translation->save();

        if ($requestTypeColumnExists) {
            $data['request_type'] = $data['request_type'] ?? $maid->request_type ?? 'known';
        } else {
            unset($data['request_type']);
        }

        if ($offerPriceColumnExists) {
            $offerPrice = $data['offer_price'] ?? null;
            $data['offer_price'] = ($offerPrice === '' || $offerPrice === null) ? null : round((float)$offerPrice, 2);
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
            $data['offer_starts_at'] = isset($data['offer_starts_at']) && $data['offer_starts_at']
                ? date('Y-m-d', strtotime($data['offer_starts_at']))
                : null;
        } else {
            unset($data['offer_starts_at']);
        }

        if ($offerExpiresColumnExists) {
            $data['offer_expires_at'] = isset($data['offer_expires_at']) && $data['offer_expires_at']
                ? date('Y-m-d', strtotime($data['offer_expires_at']))
                : null;
        } else {
            unset($data['offer_expires_at']);
        }

        if ($offerDurationColumnExists) {
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
        } else {
            unset($data['offer_duration']);
        }


        $row = parent::find($id);

        if ($experienceStatusValue !== 'has_experience') {
            $row->expertises()->delete();
            unset($data['expertises']);
        }

        $row->update($data);

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

        if (isset($languagesData)) {
            if (is_array($languagesData)) {
                $languagesArray = [];
                foreach ($languagesData as $key => $value) {
                    if ($value === 'نعم' || $value === 'yes') {
                        $languagesArray[] = $key;
                    }
                }
                $languagesString = implode(', ', $languagesArray);
            } else {
                $languagesString = $languagesData;
            }
            $row->translateOrNew('ar')->languages = $languagesString;
        }

        if (isset($maritalStatus) || isset($languagesData)) {
            $row->save();
        }

        \Log::info('Final data to update:', $data);
        \Log::info('Maid ID:', ['id' => $id]);

        if ($request->job_id) {
            $maid->jobTitles()->sync($request->job_id);
        }
        if ($request->type_id) {
            $maid->jobTypes()->sync($request->type_id);
        }

        // Handle shelter contract creation for rental maids
        if ($request->has('create_shelter_contract') && $request->create_shelter_contract == '1') {
            $this->createShelterContractForMaid($maid);
        }

        return parent::find($id);
    }

    /**
     * Create shelter contract for a rental maid
     */
    private function createShelterContractForMaid($maid)
    {
        try {
            DB::beginTransaction();

            // Check if maid already has a shelter contract
            $existingShelterContract = Contract::where('maid_id', $maid->id)
                ->where('shelter_id', '!=', null)
                ->first();

            if ($existingShelterContract) {
                \Log::info('Maid already has a shelter contract', ['maid_id' => $maid->id]);
                return;
            }

            // Find or create the company client
            $companyClient = ClientUser::where('name', 'بلاتنيوم الشرقية')
                ->orWhere('phone', '0112345678')
                ->first();

            if (!$companyClient) {
                $companyClient = ClientUser::create([
                    'name' => 'بلاتنيوم الشرقية',
                    'phone' => '0112345678',
                    'email' => 'info@platinumeastern.com',
                    'nationality_id' => 1, // Saudi Arabia
                    'password' => bcrypt('password123'),
                    'status' => 'active'
                ]);
            }

            // Create shelter contract
            $contractData = [
                'user_id' => $companyClient->id,
                'maid_id' => $maid->id,
                'status' => ContractStatusEnum::MSANED->value,
                'type' => 'shelter',
                'shelter_id' => 1, // Default shelter
                'wants_to_work' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $contract = Contract::create($contractData);

            // Create contract info
            ContractInfo::create([
                'contract_id' => $contract->id,
                'receipt_source' => 'company',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            \Log::info('Shelter contract created successfully', ['contract_id' => $contract->id, 'maid_id' => $maid->id]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to create shelter contract', [
                'maid_id' => $maid->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
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
                        })
                        ->whereDoesntHave('orders', function ($orderQuery) {
                            $orderQuery->whereIn('status', ['new', 'under_process', 'contracted']);
                        });
                })->orWhere(function ($shelter) use ($today) {
                    $this->applyShelterInstantFilters($shelter, $today);
                });
            });
    }

    private function applyShelterInstantFilters($query, string $today): void
    {
        // $query->whereIn('status', ['in_accommodation', 'available', 'completed'])
        //     ->whereHas('contracts', function ($contractQuery) {
        //         $contractQuery->where('status', ContractStatusEnum::Shelter->value)
        //             ->whereHas('info', function ($infoQuery) {
        //                 $infoQuery->whereIn('is_available_for_instant', [true, 1, '1'])
        //                     ->where(function ($wantsToWorkQuery) {
        //                         $wantsToWorkQuery
        //                             ->whereRaw('LOWER(TRIM(COALESCE(wants_to_work, ""))) = ?', ['yes'])
        //                             ->orWhereIn('wants_to_work', [1, '1', true]);
        //                     });
        //             });
        //     })
        //     ->whereDoesntHave('orders', function ($orderQuery) {
        //         $orderQuery->whereIn('status', ['new', 'under_process', 'contracted']);
        //     });
        $query->whereIn('status', ['available', 'completed'])
            ->where('maid_accommodation_type', 2)
            ->whereHas('contracts', function ($contractQuery) {
                $contractQuery->where('status', ContractStatusEnum::Shelter->value)
                    ->whereHas('info', function ($infoQuery) {
                        $infoQuery->whereIn('is_available_for_instant', [true, 1, '1'])
                            ->where(function ($wantsToWorkQuery) {
                                $wantsToWorkQuery
                                    ->whereRaw('LOWER(TRIM(COALESCE(wants_to_work, ""))) = ?', ['yes'])
                                    ->orWhereIn('wants_to_work', [1, '1', true]);
                            });
                    });
            })
            ->whereDoesntHave('orders', function ($orderQuery) {
                $orderQuery->whereIn('status', ['new', 'under_process', 'contracted']);
            });
    }

    public function paginate($request, $limit = 10)
    {
        $type = match ($request->type) {
            'estqdam' => 1,
            'egar' => 2,
            'transfer' => 3,
            default => null,
        };

        $today = now()->toDateString();

        return $this->availableQuery()
            ->when(isset($type), function ($query) use ($type, $today) {
                if ($type === 3) {
                    $query->where(function ($transferQuery) use ($today) {
                        $transferQuery->whereHas('jobTypes', function ($q) {
                            $q->where('job_type_id', 3);
                        })->orWhere(function ($orQuery) use ($today) {
                            $this->applyShelterInstantFilters($orQuery, $today);
                        });
                    });
                } else {
                    $query->whereHas('jobTypes', function ($q) use ($type) {
                        $q->where('job_type_id', $type);
                    });
                }
            })
            ->when(isset($request->nationality_id), function ($query) use ($request) {
                $query->where('nationality_id', $request->nationality_id);
            })
            ->when(isset($request->experience_country_id), function ($query) use ($request) {
                $query->whereHas('expertises', function ($q) use ($request) {
                    $q->where('country_id', $request->experience_country_id);
                });
            })
            ->when(isset($request->experience_status), function ($query) use ($request) {
                $query->where('experience_status', $request->experience_status);
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
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    public function findAvailable($id)
    {
        return $this->availableQuery()->find($id);
    }
}







