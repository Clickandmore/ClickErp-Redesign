<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class GuidelineSectionRequest extends MainRequest
{
    public function rules(): array
    {
        $fileRules = 'file|mimes:pdf,doc,docx|max:20480';
        $youtubeRules = [
            'required',
            'url',
            'max:2048',
            'regex:/^https?:\\/\\/(?:www\\.)?(?:youtube\\.com|youtu\\.be)\\//i',
        ];

        return [
            'client_file' => "nullable|$fileRules",
            'worker_file' => "nullable|$fileRules",
            'videos' => 'nullable|array|max:30',
            'videos.*.id' => 'nullable|integer|exists:guideline_videos,id',
            'videos.*.youtube_url' => $youtubeRules,
            'versions' => 'nullable|array|max:100',
            'versions.*.id' => 'nullable|integer|exists:nationality_guideline_versions,id',
            'versions.*.nationality_id' => 'required|integer|exists:nationalities,id',
            'versions.*.language' => 'required|string|max:100',
            'versions.*.client_file' => "nullable|$fileRules",
            'versions.*.worker_file' => "nullable|$fileRules",
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $seen = [];

            foreach ($this->input('versions', []) as $index => $row) {
                $nationalityId = (int) ($row['nationality_id'] ?? 0);
                $language = mb_strtolower(trim((string) ($row['language'] ?? '')));

                if (!$nationalityId || $language === '') {
                    continue;
                }

                $key = $nationalityId . ':' . $language;

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "versions.$index.language",
                        __('guidelines.duplicate_language')
                    );
                }

                $seen[$key] = true;
            }
        });
    }
}
