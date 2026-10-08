<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\FastNew;

class FastNewAction extends MainAction
{
    public function __construct(FastNew $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/fastNew/';
    }

    public function store($data)
    {
        unset($data['remove_image']);

        if (!empty($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        } else {
            unset($data['image']);
        }

        return parent::store($data);
    }

    /**
     * تحديث الخبر مع صورته الاختيارية: رفع صورة جديدة يستبدل القديمة،
     * و remove_image يحذفها. الملف القديم لا يُحذف إلا بعد نجاح الحفظ.
     */
    public function updateFastNew($id, $data)
    {
        $row = $this->model->findOrFail($id);
        $removeImage = !empty($data['remove_image']);
        unset($data['remove_image'], $data['image']);

        $oldImage = $row->image;
        $newImage = null;

        if (request()->hasFile('image')) {
            $newImage = uploadFile(request()->file('image'), $this->fileFolder);
            $data['image'] = $newImage;
        } elseif ($removeImage) {
            $data['image'] = null;
        }

        try {
            $updated = $row->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }

        if (array_key_exists('image', $data) && $oldImage) {
            deleteFile($oldImage);
        }

        return $updated;
    }
}
