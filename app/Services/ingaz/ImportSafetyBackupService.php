<?php

namespace App\Services\ingaz;

use App\Services\DatabaseBackupService;
use Illuminate\Validation\ValidationException;

class ImportSafetyBackupService
{
    public function __construct(private DatabaseBackupService $backups)
    {
    }

    public function create(): array
    {
        try {
            return $this->backups->create('pre-import');
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'backup' => 'لم يتم الاعتماد: تعذر حفظ نسخة احتياطية من قاعدة البيانات في R2. تحقق من إعدادات النسخ الاحتياطي ثم أعد المحاولة.',
            ]);
        }
    }
}
