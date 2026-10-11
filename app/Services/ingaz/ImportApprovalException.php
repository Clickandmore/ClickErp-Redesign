<?php

namespace App\Services\ingaz;

class ImportApprovalException extends \RuntimeException
{
    public function __construct(public array $errors)
    {
        parent::__construct('فشل الاعتماد؛ لم يتم حفظ أي تغييرات تشغيلية.');
    }
}
