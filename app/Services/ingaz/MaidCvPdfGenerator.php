<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Models\ingaz\Skill;
use App\Services\ManagedFileStorage;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Component\Process\Process;

class MaidCvPdfGenerator
{
    public const STORAGE_DIRECTORY = 'images/Maid/generated-cvs';
    public const OUTPUT_PDF = 'pdf';
    public const OUTPUT_IMAGE = 'image';

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

        $outputType = MaidCvTemplateSettings::outputType();
        $html = $this->renderTemplate($template, $this->viewData($maid, $settings, $outputType));
        $contents = $outputType === self::OUTPUT_IMAGE
            ? $this->generateImageContents($html)
            : $this->generatePdfContents($html);
        if ($contents === '') {
            throw new RuntimeException('تعذر إنشاء ملف PDF من القالب المرفوع.');
        }

        $storage = app(ManagedFileStorage::class);
        $extension = $outputType === self::OUTPUT_IMAGE ? 'png' : 'pdf';
        $path = $storage->storeFileContents($contents, self::STORAGE_DIRECTORY, $extension);

        $previousPath = $maid->cv_preview;

        try {
            $maid->forceFill(['cv_preview' => $path])->save();
        } catch (\Throwable $exception) {
            $storage->delete($path);
            throw $exception;
        }

        if ($this->isGeneratedPath($previousPath) && $previousPath !== $path) {
            $storage->delete($previousPath);
        }

        return $path;
    }

    /**
     * Generate a high-resolution PNG using an isolated Chrome profile and
     * the Blade template stored in the database.
     */
    private function generateImageContents(string $html): string
    {
        if (!function_exists('proc_open')) {
            throw new RuntimeException('توليد صورة السيرة يحتاج إلى تفعيل proc_open على الخادم.');
        }

        $browserPath = $this->browserExecutable();
        if ($browserPath === null) {
            throw new RuntimeException('تعذر توليد الصورة: لم يتم العثور على Google Chrome أو Microsoft Edge على الخادم.');
        }

        $temporaryDirectory = storage_path('app/cv-image-temp/' . Str::uuid());
        $profilePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'browser-profile';
        File::ensureDirectoryExists($profilePath);

        $htmlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.html';
        $imagePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.png';
        $viewportWidth = max(320, (int) env('CV_IMAGE_VIEWPORT_WIDTH', 794));
        $viewportHeight = max(480, (int) env('CV_IMAGE_VIEWPORT_HEIGHT', 1123));

        try {
            File::put($htmlPath, $html);

            $process = new Process([
                $browserPath,
                '--headless=new',
                '--user-data-dir=' . $profilePath,
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-crash-reporter',
                '--disable-breakpad',
                '--disable-features=Crashpad',
                '--no-first-run',
                '--no-default-browser-check',
                '--disable-background-networking',
                '--disable-component-update',
                '--disable-default-apps',
                '--disable-extensions',
                '--disable-sync',
                '--hide-scrollbars',
                '--allow-file-access-from-files',
                '--run-all-compositor-stages-before-draw',
                '--virtual-time-budget=3000',
                '--force-device-scale-factor=2',
                '--default-background-color=ffffffff',
                '--window-size=' . $viewportWidth . ',' . $viewportHeight,
                '--screenshot=' . $imagePath,
                $this->fileUrl($htmlPath),
            ]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() || !is_file($imagePath)) {
                throw new RuntimeException(
                    'تعذر إنشاء صورة السيرة بواسطة المتصفح: '
                    . trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            $contents = File::get($imagePath);
            if (!Str::startsWith($contents, "\x89PNG\r\n\x1a\n")) {
                throw new RuntimeException('لم يُرجع المتصفح صورة PNG صالحة.');
            }

            return $contents;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    private function generatePdfContents(string $html): string
    {
        $browserPath = $this->browserExecutable();

        if ($browserPath !== null) {
            try {
                return $this->generateWithBrowser($browserPath, $html);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $this->generateWithMpdf($html);
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
        $configuredPath = trim((string) (config('services.chrome.path') ?: env('CV_PDF_BROWSER_PATH', '')));
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
            '/usr/bin/chromium-headless-shell',
            '/usr/local/bin/google-chrome',
            '/usr/local/bin/chromium',
            '/usr/local/bin/chrome-headless-shell',
            '/opt/google/chrome/google-chrome',
            '/opt/google/chrome/chrome',
            '/usr/bin/microsoft-edge',
            '/usr/bin/microsoft-edge-stable',
            '/snap/bin/chromium',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $lookupCommand = PHP_OS_FAMILY === 'Windows' ? 'where.exe' : 'which';
        foreach ([
            'google-chrome',
            'google-chrome-stable',
            'chromium',
            'chromium-browser',
            'chromium-headless-shell',
            'chrome-headless-shell',
            'microsoft-edge',
            'microsoft-edge-stable',
            'chrome',
        ] as $binary) {
            try {
                $lookup = new Process([$lookupCommand, $binary]);
                $lookup->setTimeout(5);
                $lookup->run();

                if (!$lookup->isSuccessful()) {
                    continue;
                }

                $resolved = trim(strtok($lookup->getOutput(), "\r\n") ?: '');
                if ($resolved !== '' && is_file($resolved)) {
                    return $resolved;
                }
            } catch (\Throwable) {
                // Continue through the remaining browser names.
            }
        }

        return null;
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
            && Str::startsWith(
                Str::startsWith($path, ManagedFileStorage::R2_PREFIX)
                    ? Str::after($path, ManagedFileStorage::R2_PREFIX)
                    : $path,
                self::STORAGE_DIRECTORY . '/'
            );
    }

    private function viewData(Maid $maid, Setting $settings, string $outputType): array
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
            'cvPdfMode' => $outputType === self::OUTPUT_PDF,
            'cvImageMode' => $outputType === self::OUTPUT_IMAGE,
            'cvOutputType' => $outputType,
        ];
    }
}
