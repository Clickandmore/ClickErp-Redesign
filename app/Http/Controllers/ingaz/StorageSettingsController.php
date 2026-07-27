<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StorageSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW) || @AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL), 403);

        return view('admin.ingaz.storage-settings.edit', [
            'r2Settings' => R2StorageSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL), 403);

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'endpoint' => ['required', 'url', 'max:500'],
            'bucket' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/',
                'not_regex:/\.\./',
            ],
            'access_key_id' => ['required', 'string', 'max:500'],
            'secret_access_key' => ['nullable', 'string', 'max:1000'],
            'public_url' => ['nullable', 'url', 'max:500'],
            'root_prefix' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'use_path_style_endpoint' => ['nullable', 'boolean'],
        ], [
            'endpoint.required' => 'رابط R2 Endpoint مطلوب.',
            'bucket.regex' => 'اسم Bucket غير صالح. استخدم الاسم كما يظهر في Cloudflare، بدون مسافات أو شرطة في البداية أو النهاية.',
            'bucket.not_regex' => 'اسم Bucket لا يمكن أن يحتوي على نقطتين متتاليتين.',
            'bucket.min' => 'اسم Bucket يجب ألا يقل عن 3 أحرف.',
            'bucket.max' => 'اسم Bucket يجب ألا يزيد عن 63 حرفًا.',
            'public_url.url' => 'رابط النطاق العام غير صحيح.',
            'root_prefix.regex' => 'بادئة المسار تقبل الحروف والأرقام والشرطة والشرطة السفلية فقط.',
        ]);

        $validated['enabled'] = $request->boolean('enabled');
        $validated['use_path_style_endpoint'] = $request->boolean('use_path_style_endpoint');

        if (!filled($validated['secret_access_key'] ?? null) && !filled(R2StorageSettings::all()['secret_access_key'] ?? null)) {
            throw ValidationException::withMessages([
                'secret_access_key' => 'Secret Access Key مطلوب عند إعداد R2 لأول مرة.',
            ]);
        }

        R2StorageSettings::persist($validated);

        return redirect()->route('storage.settings')->with(
            'success',
            'تم حفظ إعدادات R2. اختبر الاتصال قبل استخدامه في رفع السير الذاتية.'
        );
    }

    public function test(ManagedFileStorage $storage)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL), 403);

        try {
            $storage->testConnection();
            R2StorageSettings::markVerified();

            return redirect()->route('storage.settings')->with('success', 'تم الاتصال بـ R2 والكتابة والقراءة والحذف بنجاح.');
        } catch (\Throwable $exception) {
            Log::warning('R2 connection test failed', ['error' => $exception->getMessage()]);

            return redirect()->route('storage.settings')->with('error', 'فشل اختبار R2: ' . $exception->getMessage());
        }
    }
}
