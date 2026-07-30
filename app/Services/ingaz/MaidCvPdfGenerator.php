<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Models\ingaz\Skill;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class MaidCvPdfGenerator
{
    public const STORAGE_DIRECTORY = 'images/Maid/generated-cvs';

    public function generate(Maid $maid): string
    {
        $settings = MaidCvTemplateSettings::current();

        if (!$settings || !$settings->cv_template_enabled) {
            throw new RuntimeException('توليد السيرة الذاتية غير مفعل.');
        }

        $template = trim((string) $settings->cv_template_blade);
        if ($template === '') {
            throw new RuntimeException('لم يتم رفع قالب السيرة الذاتية.');
        }

        $maid->loadMissing([
            'nationality',
            'religion',
            'jobTypes',
            'jobTitles',
            'expertises.country',
            'expertises.jobTitle',
            'office',
            'recruitmentOffice',
        ]);

        $html = $this->renderTemplate($template, $this->viewData($maid, $settings));
        $contents = $this->generatePdfContents($html);
        if ($contents === '') {
            throw new RuntimeException('تعذر إنشاء ملف PDF من القالب المرفوع.');
        }

        $path = self::STORAGE_DIRECTORY . '/maid-' . $maid->id . '-' . Str::uuid() . '.pdf';
        if (!Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('تعذر حفظ ملف السيرة الذاتية المولد.');
        }

        $previousPath = $maid->cv_preview;

        try {
            $maid->forceFill(['cv_preview' => $path])->save();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        if ($this->isGeneratedPath($previousPath) && $previousPath !== $path) {
            Storage::disk('public')->delete($previousPath);
        }

        return $path;
    }

    private function generatePdfContents(string $html): string
    {
        $browserPath = $this->browserExecutable();

        if ($browserPath === null) {
            if ($this->mpdfFallbackEnabled()) {
                return $this->generateWithMpdf($html);
            }

            throw new RuntimeException(
                'تعذر توليد السيرة الذاتية بنفس تنسيق القالب لأن Chromium غير مثبت أو غير مضبوط على السيرفر. '
                . 'ثبّت Google Chrome أو Chromium واضبط CV_PDF_BROWSER_PATH على مسار الملف التنفيذي.'
            );
        }

        try {
            return $this->generateWithBrowser($browserPath, $html);
        } catch (\Throwable $exception) {
            if ($this->mpdfFallbackEnabled()) {
                report($exception);

                return $this->generateWithMpdf($html);
            }

            throw new RuntimeException(
                'تعذر توليد السيرة الذاتية بواسطة Chromium: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    private function generateWithBrowser(string $browserPath, string $html): string
    {
        $temporaryDirectory = storage_path('app/cv-pdf-temp/' . Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);

        $htmlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.html';
        $pdfPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.pdf';
        $profilePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'browser-profile';

        try {
            File::put($htmlPath, $html);

            $process = new Process([
                $browserPath,
                '--headless=new',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--allow-file-access-from-files',
                '--run-all-compositor-stages-before-draw',
                '--no-pdf-header-footer',
                '--user-data-dir=' . $profilePath,
                '--print-to-pdf=' . $pdfPath,
                $this->fileUrl($htmlPath),
            ]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() || !is_file($pdfPath)) {
                throw new RuntimeException(
                    'تعذر إنشاء PDF بواسطة المتصفح: ' . trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            $contents = File::get($pdfPath);
            if (!Str::startsWith($contents, '%PDF')) {
                throw new RuntimeException('لم يُرجع المتصفح ملف PDF صالحًا.');
            }

            return $contents;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    private function generateWithMpdf(string $html): string
    {
        $temporaryDirectory = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($temporaryDirectory);
        // The page size and margins are configured directly on mPDF below.
        // Keeping an uploaded template's @page rule as well can make mPDF
        // generate many blank pages, while browser-based PDF generation needs it.
        $html = preg_replace(
            '/@page\b[^{]*\{[^{}]*\}/i',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/(?:background|background-image)\s*:\s*linear-gradient\([^;]+;/i',
            '',
            $html
        ) ?? $html;
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

        try {
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'default_font' => 'dejavusans',
                'tempDir' => $temporaryDirectory,
            ]);
            $pdf->autoScriptToLang = true;
            $pdf->autoLangToFont = true;
            $pdf->SetDisplayMode('fullpage');
            $pdf->WriteHTML($html);

            return $pdf->Output('', Destination::STRING_RETURN);
        } finally {
            error_reporting($previousErrorReporting);
        }
    }

    private function browserExecutable(): ?string
    {
        $configuredPath = trim((string) config('cv.pdf.browser_path', ''));
        $candidates = array_filter([
            $configuredPath,
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/usr/local/bin/google-chrome',
            '/usr/local/bin/google-chrome-stable',
            '/usr/local/bin/chromium',
            '/usr/local/bin/chromium-browser',
            '/opt/google/chrome/chrome',
            '/opt/google/chrome/google-chrome',
            '/snap/bin/chromium',
            '/usr/bin/microsoft-edge',
            '/usr/bin/microsoft-edge-stable',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        $finder = new ExecutableFinder();

        foreach ([
            'google-chrome',
            'google-chrome-stable',
            'chromium',
            'chromium-browser',
            'microsoft-edge',
            'microsoft-edge-stable',
        ] as $executable) {
            $path = $finder->find($executable);

            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    private function mpdfFallbackEnabled(): bool
    {
        return (bool) config('cv.pdf.allow_mpdf_fallback', false);
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . ltrim(str_replace('\\', '/', $path), '/');
    }

    public function renderTemplate(string $template, array $data): string
    {
        try {
            return Blade::render($template, $data, true);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'تعذر تنفيذ قالب السيرة الذاتية: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    public function isGeneratedPath(?string $path): bool
    {
        return is_string($path)
            && Str::startsWith($path, self::STORAGE_DIRECTORY . '/');
    }

    private function viewData(Maid $maid, Setting $settings): array
    {
        $expertises = $maid->expertises;

        return [
            'maid' => $maid,
            'settings' => $settings,
            'skills' => Skill::query()->with('translations')->get(),
            'experience' => [
                'status' => $maid->experience_status,
                'countries' => $expertises->pluck('country')->filter()->values(),
                'details' => $expertises,
            ],
            'customerServices' => collect(),
            'type' => $maid->request_type ?: 'estqdam',
            'cvPdfMode' => true,
        ];
    }
}
