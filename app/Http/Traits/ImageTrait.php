<?php

namespace App\Traits;

trait ImageTrait
{
    public function ImageNamePath($name, $path)
    {
        return app(\App\Services\ManagedFileStorage::class)->uploadFile($name, $path);
    }
}
