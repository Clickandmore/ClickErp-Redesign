<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\WhyUs;

class WhyUsAction extends MainAction
{
    public function __construct(WhyUs $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        return $this->model->ordered()->get();
    }

    /** البطاقات المفعّلة بترتيب لوحة التحكم — ما يعرضه الموقع الخارجي */
    public function getVisible()
    {
        return $this->model->visible()->ordered()->get();
    }
}
