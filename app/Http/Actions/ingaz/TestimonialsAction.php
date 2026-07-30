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
        $testimonial = $this->find($id);
        $oldImage = null;
        $newImage = null;
        if (isset($data['image'])) {
            $oldImage = $testimonial->image;
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
            $newImage = $data['image'];
        }
        try {
            $updated = $testimonial->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }
        if ($newImage) {
            deleteFile($oldImage);
        }
        return $updated;
    }
}
