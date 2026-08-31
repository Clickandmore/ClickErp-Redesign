<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\WhyChooseUs;

class WhyChooseUsAction extends MainAction
{
    public function __construct(WhyChooseUs $model)
    {
        $this->model = $model;
    }

    public function section(): ?WhyChooseUs
    {
        return $this->model
            ->with(['images', 'features', 'highlights'])
            ->first();
    }

    public function visibleSection(): ?WhyChooseUs
    {
        return $this->model
            ->where('is_active', true)
            ->with(['images', 'features', 'highlights'])
            ->first();
    }
}
