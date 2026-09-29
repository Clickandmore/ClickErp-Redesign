<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use App\Support\DatabaseBackupSettings;
use App\Support\DevSuperAdminSecuritySettings;
use App\Support\R2StorageSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevDatabaseBackupController extends Controller
{
    public function requestVerificationCode(
        Request $request,
        DatabaseBackupService $backups,
        DatabaseBackupVerificationService $verification
    ) {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'action' => ['required', 'in:restore,delete'],
            'verification_channel' => ['required', 'in:email,phone'],
        ]);

        try {
            $backups->assertBackupExists($data['key']);
            $challenge = $verification->issue(
                $request,
                $data['action'],
                $data['key'],
                $data['verification_channel']
            );

            return back()->with(
                'success',
                'تم إرسال رمز التحقق إلى ' . $challenge['destination'] . '. أدخله قبل انتهاء صلاحيته لإكمال العملية.'
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['verification_code' => $exception->getMessage()]);
        }
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'local_enabled' => ['nullable', 'boolean'],
            'daily_time' => ['required', 'date_format:H:i'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'local_retention_days' => ['required', 'integer', 'min:1', 'max:30'],
            'path' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-\/]+$/'],
        ]);
        $data['enabled'] = $request->boolean('enabled');
        $data['local_enabled'] = $request->boolean('local_enabled');
        DatabaseBackupSettings::persist($data);

        return back()->with('success', 'تم حفظ إعدادات النسخ الاحتياطي اليومية.');
    }

    public function create(DatabaseBackupService $backups)
    {
        try {
            if (R2StorageSettings::isReady()) {
                $backup = $backups->create('manual');
                $message = DatabaseBackupSettings::all()['local_enabled']
                    ? 'تم إنشاء النسخة في R2 وعلى السيرفر: '
                    : 'تم إنشاء النسخة في R2: ';
            } else {
                $backup = $backups->createLocal('manual');
                $message = 'تم إنشاء النسخة محليًا على السيرفر: ';
            }

            return back()->with('success', $message . $backup['filename']);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }

    public function downloadCurrent(DatabaseBackupService $backups)
    {
        try {
            $backup = $backups->createLocal('manual');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => $exception->getMessage()]);
        }

        return response()->streamDownload(function () use ($backups, $backup): void {
            $stream = $backups->readLocalStream($backup['path']);
            fpassthru($stream);
            fclose($stream);
        }, $backup['filename'], [
            'Content-Type' => 'application/gzip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadLocal(Request $request, DatabaseBackupService $backups): StreamedResponse
    {
        $path = (string) $request->query('path');
        $filename = basename($path);

        return response()->streamDownload(function () use ($backups, $path): void {
            $stream = $backups->readLocalStream($path);
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/gzip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(Request $request, DatabaseBackupService $backups): StreamedResponse
    {
        $key = (string) $request->query('key');
        $filename = basename($key);

        return response()->streamDownload(function () use ($backups, $key): void {
            $stream = $backups->readStream($key);
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/gzip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function restore(
        Request $request,
        DatabaseBackupService $backups,
        DatabaseBackupVerificationService $verification
    )
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'confirmation' => ['required', 'in:RESTORE'],
            'verification_code' => ['required', 'digits:6'],
        ], ['confirmation.in' => 'اكتب RESTORE لتأكيد الاستعادة.']);

        $verification->verifyAndConsume($request, 'restore', $data['key'], $data['verification_code']);

        try {
            $backups->restore($data['key']);
            return back()->with('success', 'تمت استعادة قاعدة البيانات بنجاح، كما تم حفظ نسخة أمان قبل الاستعادة.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }

    public function import(
        Request $request,
        DatabaseBackupService $backups
    ) {
        $data = $request->validate([
            'database_file' => ['required', 'file', 'max:' . max(1, (int) config('database.backup_import_max_kb', 512000))],
            'confirmation' => ['required', 'in:IMPORT'],
            'current_password' => ['required', 'string'],
        ], [
            'database_file.required' => 'اختر ملف قاعدة البيانات المراد استيراده.',
            'database_file.uploaded' => 'تعذر رفع الملف. تأكد أن حجمه لا يتجاوز حدود PHP والخادم.',
            'database_file.max' => 'حجم ملف قاعدة البيانات أكبر من الحد المسموح به.',
            'confirmation.in' => 'اكتب IMPORT لتأكيد استبدال قاعدة البيانات.',
        ]);

        $filename = strtolower($data['database_file']->getClientOriginalName());
        if (!str_ends_with($filename, '.sql') && !str_ends_with($filename, '.sql.gz')) {
            throw ValidationException::withMessages([
                'database_file' => ['الملفات المقبولة هي SQL أو SQL.GZ فقط.'],
            ]);
        }

        if (!DevSuperAdminSecuritySettings::matchesPassword($data['current_password'])) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة مرور Super Admin الحالية غير صحيحة.'],
            ]);
        }

        try {
            $uploaded = $backups->importUploaded($data['database_file']);

            $message = 'تم رفع النسخة وتسجيلها محليًا ثم استيراد قاعدة البيانات بنجاح: ' . $uploaded['filename'];
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'backup' => $uploaded,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['database_file' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['database_file' => $exception->getMessage()]);
        }
    }

    public function destroy(
        Request $request,
        DatabaseBackupService $backups,
        DatabaseBackupVerificationService $verification
    )
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'confirmation' => ['required', 'in:DELETE'],
            'verification_code' => ['required', 'digits:6'],
        ], ['confirmation.in' => 'اكتب DELETE لتأكيد الحذف النهائي.']);

        $verification->verifyAndConsume($request, 'delete', $data['key'], $data['verification_code']);

        try {
            $backups->delete($data['key']);
            return back()->with('success', 'تم حذف النسخة الاحتياطية من R2.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }

    public function destroyLocalUploaded(Request $request, DatabaseBackupService $backups)
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
            'confirmation' => ['required', 'in:DELETE'],
            'current_password' => ['required', 'string'],
        ], [
            'confirmation.in' => 'اكتب DELETE لتأكيد حذف النسخة المحلية.',
        ]);

        if (!DevSuperAdminSecuritySettings::matchesPassword($data['current_password'])) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة مرور Super Admin الحالية غير صحيحة.'],
            ]);
        }

        try {
            $backups->deleteLocalUploaded($data['path']);

            return back()->with('success', 'تم حذف النسخة المحلية المرفوعة يدويًا.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }
}
