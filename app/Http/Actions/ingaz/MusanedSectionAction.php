<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\MusanedSection;

class MusanedSectionAction extends MainAction
{
    public function __construct(MusanedSection $model)
    {
        $this->model = $model;
    }

    public function visibleSection(): ?MusanedSection
    {
        return $this->model
            ->with(['metrics' => fn ($query) => $query
                ->where('is_visible', true)
                ->with('translations')])
            ->where('is_active', true)
            ->first();
    }
}
