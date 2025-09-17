<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Question;
use App\Models\Testimonial;
use App\Models\WhyUs;

class TestimonialsAction extends MainAction
{
    public function __construct(Testimonial $model)
    {
        $this->model = $model;
        $this->fileFolder = 'testimonials_images/';
    }

    public function storeTestimonial($data)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }
        return $this->store($data);
    }

    public function updateTestimonial($data, $id)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }
        return $this->find($id)->update($data);
    }
}
