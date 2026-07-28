<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\HomeHeroSection;

class HomeHeroSectionAction extends MainAction
{
    public function __construct(HomeHeroSection $model)
    {
        $this->model = $model;
    }

    public function visibleSection(): ?HomeHeroSection
    {
        return $this->model
            ->where('is_active', true)
            ->with('images')
            ->first();
    }
}
