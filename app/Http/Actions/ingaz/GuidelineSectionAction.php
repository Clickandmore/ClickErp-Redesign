<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\GuidelineSection;

class GuidelineSectionAction extends MainAction
{
    public function __construct(GuidelineSection $model)
    {
        $this->model = $model;
    }

    public function section(): ?GuidelineSection
    {
        return $this->model
            ->with([
                'videos',
                'nationalityGuidelines.nationality.translations',
                'nationalityGuidelines.versions',
            ])
            ->first();
    }
}
