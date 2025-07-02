<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;

class MaidAction extends MainAction
{
    public function __construct(Maid $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }

                public function storeMaid($data, $request)
    {
        // Debug: Log the raw data received
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
        $data['nationality_id'] = (int) $data['nationality_id'];
        $data['religion_id'] = (int) $data['religion_id'];

        // Ensure these required fields are always included
        $data['passport_num'] = $data['passport_num'] ?? '';
        $data['id_number'] = $data['id_number'] ?? '';
        $data['id_expiry_date'] = $data['id_expiry_date'] ?? null;

        // Robust handling for languages field
        \Log::info('Before languages processing - languages data:', [$data['languages'] ?? 'not set']);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));
        if (isset($data['languages'])) {
            if (is_array($data['languages'])) {
                // Convert associative array to simple array of values
                $languagesArray = [];
                foreach ($data['languages'] as $key => $value) {
                    if ($value === 'نعم' || $value === 'yes') {
                        $languagesArray[] = $key;
                    }
                }
                $data['languages'] = json_encode($languagesArray);
                \Log::info('Languages converted to JSON: ' . $data['languages']);
            } elseif (is_string($data['languages'])) {
                // If it's a string, check if it's valid JSON, otherwise wrap as array
                json_decode($data['languages']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data['languages'] = json_encode([$data['languages']]);
                    \Log::info('Languages string wrapped as array and converted to JSON: ' . $data['languages']);
                }
            } else {
                $data['languages'] = json_encode([]);
                \Log::info('Languages was not array or string, set to empty array.');
            }
        } else {
            $data['languages'] = json_encode([]);
            \Log::info('Languages not set, set to empty array.');
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

        // معالجة جميع الحقول التي هي array
        foreach ($data as $key => $value) {
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
            'birth_date' => $data['birth_date'] ?? null,
            'experience_years' => $data['experience_years'] ?? null,
            'skills' => $data['skills'] ?? null,
            'languages' => $data['languages'] ?? null,
            'cv_file' => $data['cv_file'] ?? '',
            'image' => $data['image'] ?? null,
            'cv_preview' => $data['cv_preview'] ?? null,
            'office_id' => $data['office_id'] ?? null,
            'status' => $data['status'],
            'available' => $data['available'],
            'available_at' => $data['available_at'],
            'is_show' => $data['is_show'],
            'name' => $data['name']
        ];

        \Log::info('Final create data:', $createData);
        \Log::info('About to create maid with data keys: ' . implode(', ', array_keys($createData)));
        \Log::info('Languages in createData: ' . ($createData['languages'] ?? 'null'));
        \Log::info('Name in createData: ' . ($createData['name'] ?? 'null'));

        try {
            $maid = $this->model->create($createData);
            \Log::info('Maid created successfully with ID: ' . $maid->id);
        } catch (\Exception $e) {
            \Log::error('Error creating maid: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            throw $e;
        }

        \Log::info('About to attach job titles: ' . print_r($request->job_id, true));
        $maid->jobTitles()->attach($request->job_id);
        \Log::info('Job titles attached successfully');

        \Log::info('About to attach job types: ' . print_r($request->type_id, true));
        $maid->jobTypes()->attach($request->type_id);
        \Log::info('Job types attached successfully');
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

        if ($request->age_from && $request->age_to) {
            $query->whereBetween('age', [$request->age_from, $request->age_to]);
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
        $maid = parent::find($id);

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

        // Robust handling for languages field
        \Log::info('Before languages processing - languages data:', [$data['languages'] ?? 'not set']);
        \Log::info('Languages data type: ' . gettype($data['languages'] ?? 'not set'));
        if (isset($data['languages'])) {
            if (is_array($data['languages'])) {
                $data['languages'] = json_encode($data['languages']);
                \Log::info('Languages converted to JSON: ' . $data['languages']);
            } elseif (is_string($data['languages'])) {
                // If it's a string, check if it's valid JSON, otherwise wrap as array
                json_decode($data['languages']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data['languages'] = json_encode([$data['languages']]);
                    \Log::info('Languages string wrapped as array and converted to JSON: ' . $data['languages']);
                }
            } else {
                $data['languages'] = json_encode([]);
                \Log::info('Languages was not array or string, set to empty array.');
            }
        } else {
            $data['languages'] = json_encode([]);
            \Log::info('Languages not set, set to empty array.');
        }

        // Convert skills array to JSON if it exists
        if (isset($data['skills']) && is_array($data['skills'])) {
            $data['skills'] = json_encode($data['skills']);
        }

        $row = parent::find($id);
        $row->update($data);
//        parent::update($id, $data);
        if ($request->job_id) {
            $maid->jobTitles()->sync($request->job_id);
        }
        if ($request->type_id) {
            $maid->jobTypes()->sync($request->type_id);
        }
        return parent::find($id);
    }

    public function togglePin($id)
    {
        $maid = parent::find($id);
        $maid->is_pinned = !$maid->is_pinned;
        $maid->save();
        return $maid;
    }
}
