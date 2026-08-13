<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Support\DatabaseBackupSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevDatabaseBackupController extends Controller
{
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
            $backup = $backups->create('manual');
            $message = DatabaseBackupSettings::all()['local_enabled']
                ? 'تم إنشاء النسخة في R2 وعلى السيرفر: '
                : 'تم إنشاء النسخة في R2: ';

            return back()->with('success', $message . $backup['filename']);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }

    public function downloadCurrent(DatabaseBackupService $backups)
    {
        try {
            $backup = $backups->create('manual');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => $exception->getMessage()]);
        }

        return response()->streamDownload(function () use ($backups, $backup): void {
            $stream = $backups->readStream($backup['key']);
            fpassthru($stream);
            fclose($stream);
        }, $backup['filename'], [
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

    public function restore(Request $request, DatabaseBackupService $backups)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'confirmation' => ['required', 'in:RESTORE'],
        ], ['confirmation.in' => 'اكتب RESTORE لتأكيد الاستعادة.']);

        try {
            $backups->restore($data['key']);
            return back()->with('success', 'تمت استعادة قاعدة البيانات بنجاح، كما تم حفظ نسخة أمان قبل الاستعادة.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }

    public function destroy(Request $request, DatabaseBackupService $backups)
    {
        $data = $request->validate(['key' => ['required', 'string']]);

        try {
            $backups->delete($data['key']);
            return back()->with('success', 'تم حذف النسخة الاحتياطية من R2.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }
}
