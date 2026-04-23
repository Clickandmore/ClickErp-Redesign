<?php

namespace App\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use Throwable;

class CustomValidator extends Validator
{
    public function validateMimes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        if (in_array('jpg', $parameters, true) || in_array('jpeg', $parameters, true)) {
            $parameters = array_unique(array_merge($parameters, ['jpg', 'jpeg']));
        }

        $extension = $this->resolveFileExtension($value);

        return $value->getPath() !== ''
            && $extension !== null
            && in_array($extension, $parameters, true);
    }

    public function validateImage($attribute, $value)
    {
        return $this->validateMimes($attribute, $value, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    public function validateMimetypes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        $mimeType = $this->resolveMimeType($value);

        return $value->getPath() !== ''
            && $mimeType !== null
            && (
                in_array($mimeType, $parameters, true)
                || in_array(explode('/', $mimeType)[0] . '/*', $parameters, true)
            );
    }

    private function resolveFileExtension($value): ?string
    {
        try {
            $extension = $value->guessExtension();
        } catch (Throwable) {
            $extension = null;
        }

        if (is_string($extension) && $extension !== '') {
            return strtolower($extension);
        }

        if ($value instanceof UploadedFile) {
            $extension = $value->getClientOriginalExtension();
        } else {
            $extension = $value->getExtension();
        }

        return is_string($extension) && $extension !== ''
            ? strtolower($extension)
            : null;
    }

    private function resolveMimeType($value): ?string
    {
        try {
            $mimeType = $value->getMimeType();
        } catch (Throwable) {
            $mimeType = null;
        }

        if (is_string($mimeType) && $mimeType !== '') {
            return strtolower($mimeType);
        }

        if ($value instanceof UploadedFile) {
            $mimeType = $value->getClientMimeType();
        }

        return is_string($mimeType) && $mimeType !== ''
            ? strtolower($mimeType)
            : null;
    }
}
