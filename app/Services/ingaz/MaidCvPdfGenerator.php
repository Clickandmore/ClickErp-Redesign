<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Models\ingaz\Skill;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

    /**
     * Every environment must render with Chromium. Falling back to another
     * engine produces a PDF that no longer matches the uploaded template,
     * so an unusable renderer is a hard failure instead of a silent downgrade.
     */
    private function generatePdfContents(string $html): string
    {
        $remoteDriver = $this->remoteDriver();

        if ($remoteDriver !== null) {
            try {
                return $this->generateWithRemoteBrowser($remoteDriver, $html);
            } catch (\Throwable $exception) {
                if ($this->mpdfFallbackEnabled()) {
                    report($exception);

                    return $this->generateWithMpdf($html);
                }

                throw new RuntimeException(
                    'تعذر توليد السيرة الذاتية عبر خدمة الرسم الخارجية: ' . $exception->getMessage(),
                    previous: $exception
                );
            }
        }

        $browserPath = $this->browserExecutable();

        if ($browserPath === null) {
            if ($this->mpdfFallbackEnabled()) {
                return $this->generateWithMpdf($html);
            }

            throw new RuntimeException($this->missingBrowserMessage());
        }

        if (!function_exists('proc_open')) {
            if ($this->mpdfFallbackEnabled()) {
                return $this->generateWithMpdf($html);
            }

            throw new RuntimeException(
                'تعذر تشغيل Chromium لأن الدالة proc_open معطّلة في إعدادات PHP على السيرفر. '
                . 'أزل proc_open من disable_functions في php.ini، '
                . 'أو اضبط CV_PDF_REMOTE_DRIVER وCV_PDF_REMOTE_URL لاستخدام خدمة رسم خارجية. '
                . 'نفّذ php artisan cv:doctor لعرض التشخيص الكامل.'
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
                'تعذر توليد السيرة الذاتية بواسطة Chromium: ' . $exception->getMessage()
                . ' نفّذ php artisan cv:doctor لعرض التشخيص الكامل.',
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

            $process = new Process(
                $this->browserCommand($browserPath, $profilePath, $pdfPath, $htmlPath),
                $temporaryDirectory,
                // Shared hosting often runs PHP without a writable HOME, and
                // Chromium aborts on startup when it cannot write its caches.
                [
                    'HOME' => $temporaryDirectory,
                    'XDG_CONFIG_HOME' => $temporaryDirectory,
                    'XDG_CACHE_HOME' => $temporaryDirectory,
                    'TMPDIR' => $temporaryDirectory,
                ]
            );
            $process->setTimeout($this->processTimeout());
            $process->run();

            if (!$process->isSuccessful() || !is_file($pdfPath)) {
                throw new RuntimeException(
                    trim($process->getErrorOutput() ?: $process->getOutput())
                        ?: 'لم يُنتج المتصفح أي ملف ولم يُرجع رسالة خطأ.'
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

    /**
     * @return array<int, string>
     */
    public function browserCommand(
        string $browserPath,
        string $profilePath,
        string $pdfPath,
        string $htmlPath
    ): array {
        return [
            $browserPath,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-crash-reporter',
            '--no-first-run',
            '--no-default-browser-check',
            '--hide-scrollbars',
            '--force-color-profile=srgb',
            '--font-render-hinting=none',
            '--allow-file-access-from-files',
            '--run-all-compositor-stages-before-draw',
            // Gives remote images and web fonts time to arrive on a server
            // that is slower than a developer machine.
            '--virtual-time-budget=' . $this->virtualTimeBudget(),
            '--no-pdf-header-footer',
            '--print-to-pdf-no-header',
            '--user-data-dir=' . $profilePath,
            '--crash-dumps-dir=' . $profilePath,
            '--print-to-pdf=' . $pdfPath,
            $this->fileUrl($htmlPath),
        ];
    }

    private function generateWithRemoteBrowser(string $driver, string $html): string
    {
        $url = rtrim(trim((string) config('cv.pdf.remote.url', '')), '/');

        if ($url === '') {
            throw new RuntimeException('لم يتم ضبط CV_PDF_REMOTE_URL.');
        }

        $token = trim((string) config('cv.pdf.remote.token', ''));
        $timeout = max(30, (int) config('cv.pdf.remote.timeout', 120));

        $response = match ($driver) {
            'gotenberg' => Http::timeout($timeout)
                ->when($token !== '', fn ($request) => $request->withToken($token))
                ->attach('files', $html, 'index.html')
                ->post($url . '/forms/chromium/convert/html', [
                    'marginTop' => '0',
                    'marginBottom' => '0',
                    'marginLeft' => '0',
                    'marginRight' => '0',
                    'printBackground' => 'true',
                    'preferCssPageSize' => 'true',
                    'waitDelay' => '2s',
                ]),
            'browserless' => Http::timeout($timeout)
                ->post($url . '/pdf' . ($token !== '' ? '?token=' . urlencode($token) : ''), [
                    'html' => $html,
                    'options' => [
                        'printBackground' => true,
                        'preferCSSPageSize' => true,
                        'margin' => ['top' => '0', 'bottom' => '0', 'left' => '0', 'right' => '0'],
                    ],
                    'gotoOptions' => ['waitUntil' => 'networkidle0'],
                ]),
            default => throw new RuntimeException(
                "قيمة CV_PDF_REMOTE_DRIVER غير مدعومة: $driver. المدعوم: gotenberg أو browserless."
            ),
        };

        if (!$response->successful()) {
            throw new RuntimeException(
                'استجابة غير ناجحة (' . $response->status() . '): ' . Str::limit($response->body(), 300)
            );
        }

        $contents = $response->body();

        if (!Str::startsWith($contents, '%PDF')) {
            throw new RuntimeException('لم تُرجع الخدمة ملف PDF صالحًا.');
        }

        return $contents;
    }

    private function generateWithMpdf(string $html): string
    {
        if (!class_exists(Mpdf::class)) {
            throw new RuntimeException(
                'تم تفعيل CV_PDF_ALLOW_MPDF_FALLBACK لكن حزمة mpdf/mpdf غير مثبتة. '
                . 'نفّذ composer require mpdf/mpdf أو أعد تعطيل هذا الخيار.'
            );
        }

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

    public function browserExecutable(): ?string
    {
        $configuredPath = trim((string) config('cv.pdf.browser_path', ''));
        $candidates = array_filter([
            $configuredPath,
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            ...$this->managedBrowserExecutables(),
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

    public function remoteDriver(): ?string
    {
        $driver = strtolower(trim((string) config('cv.pdf.remote.driver', '')));

        return $driver === '' ? null : $driver;
    }

    public function mpdfFallbackEnabled(): bool
    {
        return filter_var(config('cv.pdf.allow_mpdf_fallback', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function missingBrowserMessage(): string
    {
        return 'تعذر توليد السيرة الذاتية بنفس تنسيق القالب لأن Chromium غير مثبت أو غير مضبوط على السيرفر. '
            . 'نفّذ php artisan cv:install-browser لتثبيته داخل المشروع، '
            . 'أو اضبط CV_PDF_BROWSER_PATH على مسار Chrome المثبت، '
            . 'أو اضبط CV_PDF_REMOTE_DRIVER وCV_PDF_REMOTE_URL لاستخدام خدمة رسم خارجية. '
            . 'نفّذ php artisan cv:doctor لعرض التشخيص الكامل.';
    }

    public function virtualTimeBudget(): int
    {
        return max(1000, (int) config('cv.pdf.virtual_time_budget', 15000));
    }

    private function processTimeout(): int
    {
        return max(30, (int) config('cv.pdf.timeout', 120));
    }

    /**
     * @return array<int, string>
     */
    public function managedBrowserExecutables(): array
    {
        $patterns = PHP_OS_FAMILY === 'Windows'
            ? [storage_path('app/cv-browser/*/chrome-win64/chrome.exe')]
            : [storage_path('app/cv-browser/*/chrome-linux64/chrome')];
        $executables = [];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern) ?: [];
            rsort($matches, SORT_NATURAL);
            array_push($executables, ...$matches);
        }

        return $executables;
    }

    public function fileUrl(string $path): string
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
