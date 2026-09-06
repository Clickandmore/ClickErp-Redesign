<?php

namespace App\Support;

use App\Models\ingaz\EducationQualification;
use Illuminate\Support\Facades\Schema;

class EducationQualificationCatalog
{
    public static function normalize(array $data): array
    {
        if ((!filled($data['education'] ?? null) && !filled($data['education_en'] ?? null))
            || !Schema::hasTable('education_qualifications')) {
            return $data;
        }

        $qualification = filled($data['education'] ?? null)
            ? EducationQualification::where('title_ar', $data['education'])->first()
            : null;
        $qualification ??= filled($data['education_en'] ?? null)
            ? EducationQualification::where('title_en', $data['education_en'])->first()
            : null;

        if ($qualification) {
            $data['education'] = $qualification->title_ar;
            $data['education_en'] = $qualification->title_en;
        }

        return $data;
    }
}
