<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CvTemplateSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings_cv_template,admin');
    }

    public function edit()
    {
        $settings = Setting::query()->firstOrCreate([]);

        return view('admin.settings.sections.cv-template', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'cv_template_enabled' => ['nullable', 'boolean'],
            'cv_template_output_type' => ['required', 'in:pdf,image'],
            'cv_public_site_url' => ['nullable', 'url', 'max:500'],
            'cv_template_file' => ['nullable', 'file', 'max:2048'],
        ], [
            'cv_public_site_url.url' => 'رابط الموقع العام يجب أن يكون رابطًا صحيحًا يبدأ بـ http:// أو https://.',
            'cv_public_site_url.max' => 'رابط الموقع العام يجب ألا يتجاوز 500 حرف.',
            'cv_template_file.file' => 'ملف قالب السيرة الذاتية غير صالح.',
            'cv_template_file.max' => 'حجم قالب السيرة الذاتية يجب ألا يتجاوز 2MB.',
        ]);

        $settings = Setting::query()->firstOrCreate([]);
        $template = (string) ($settings->cv_template_blade ?? '');
        $filename = $settings->cv_template_filename;

        if ($request->hasFile('cv_template_file')) {
            [$template, $filename] = $this->readBladeTemplate($request->file('cv_template_file'));
        }

        $enabled = (bool) ($validated['cv_template_enabled'] ?? false);
        if ($enabled && trim($template) === '') {
            throw ValidationException::withMessages([
                'cv_template_file' => 'يجب رفع قالب Blade قبل تفعيل توليد السيرة الذاتية.',
            ]);
        }

        $settings->forceFill([
            'cv_template_enabled' => $enabled,
            'cv_template_blade' => $template !== '' ? $template : null,
            'cv_template_filename' => $filename,
            'cv_template_output_type' => $validated['cv_template_output_type'],
            'cv_public_site_url' => filled($validated['cv_public_site_url'] ?? null)
                ? rtrim(trim($validated['cv_public_site_url']), '/')
                : null,
        ])->save();

        MaidCvTemplateSettings::clearCache();

        return redirect()
            ->route('settings.cv-template.edit')
            ->with('success', 'تم حفظ إعدادات قالب السيرة الذاتية بنجاح.');
    }

    private function readBladeTemplate(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        if (!Str::endsWith(Str::lower($originalName), '.blade.php')) {
            throw ValidationException::withMessages([
                'cv_template_file' => 'يجب أن يكون امتداد القالب .blade.php',
            ]);
        }

        $contents = file_get_contents($file->getRealPath());
        if (!is_string($contents) || trim($contents) === '' || str_contains($contents, "\0")) {
            throw ValidationException::withMessages([
                'cv_template_file' => 'قالب Blade فارغ أو يحتوي على بيانات غير صالحة.',
            ]);
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages([
                'cv_template_file' => 'يجب حفظ قالب Blade بترميز UTF-8.',
            ]);
        }

        return [$contents, basename($originalName)];
    }
}
