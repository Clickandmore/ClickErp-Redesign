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

    public const BROWSER_PROFILES = ['default', 'no-zygote', 'single-process', 'legacy-headless'];

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

        if ($this->usesSvgRenderer()) {
            $contents = app(MaidCvSvgGenerator::class)->render($maid, $settings);
            $extension = 'svg';
        } else {
            $extension = 'pdf';

            try {
                $html = $this->renderTemplate($template, $this->viewData($maid, $settings));
                $contents = $this->generatePdfContents($html);
            } catch (\Throwable $exception) {
                report($exception);
                \Log::warning('Chromium CV generation failed; using SVG fallback.', [
                    'maid_id' => $maid->id,
                    'error' => $exception->getMessage(),
                ]);

                $contents = app(MaidCvSvgGenerator::class)->render($maid, $settings);
                $extension = 'svg';
            }
        }
        if ($contents === '') {
            throw new RuntimeException('تعذر إنشاء ملف PDF من القالب المرفوع.');
        }

        $path = self::STORAGE_DIRECTORY . '/maid-' . $maid->id . '-' . Str::uuid() . '.' . $extension;
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

    public function usesSvgRenderer(): bool
    {
        return strtolower(trim((string) config('cv.pdf.renderer', 'auto'))) === 'svg';
    }

    /**
     * Render the uploaded Blade template as PDF whenever a Chromium renderer
     * is available. The caller owns the browser-independent SVG fallback.
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
        $temporaryDirectory = $this->createBrowserTemporaryDirectory();

        $htmlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'i.html';
        $failures = [];

        try {
            File::put($htmlPath, $html);

            foreach ($this->browserProfiles() as $profile) {
                $attempt = $this->runBrowser($browserPath, $htmlPath, $temporaryDirectory, $profile);

                if ($attempt['contents'] !== null) {
                    return $attempt['contents'];
                }

                $failures[] = "[$profile] " . $attempt['error'];
            }

            throw new RuntimeException(implode(' — ', $failures));
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /**
     * @return array{contents: ?string, error: string}
     */
    private function runBrowser(
        string $browserPath,
        string $htmlPath,
        string $temporaryDirectory,
        string $profile
    ): array {
        $attemptDirectory = $temporaryDirectory . DIRECTORY_SEPARATOR . $this->browserProfileCode($profile);
        File::ensureDirectoryExists($attemptDirectory);

        $pdfPath = $attemptDirectory . DIRECTORY_SEPARATOR . 'o.pdf';
        $profilePath = $attemptDirectory . DIRECTORY_SEPARATOR . 'p';

        $process = new Process(
            $this->browserCommand($browserPath, $profilePath, $pdfPath, $htmlPath, $profile),
            $attemptDirectory,
            // Shared hosting often runs PHP without a writable HOME, and
            // Chromium aborts on startup when it cannot write its caches.
            [
                'HOME' => $attemptDirectory,
                'XDG_CONFIG_HOME' => $attemptDirectory,
                'XDG_CACHE_HOME' => $attemptDirectory,
                'TMPDIR' => $attemptDirectory,
            ]
        );
        $process->setTimeout($this->processTimeout());

        try {
            $process->run();
            $failure = $process->isSuccessful() ? null : 'خرج بالرمز ' . $process->getExitCode();
        } catch (\Throwable $exception) {
            // A crashed Chromium is signalled, and Symfony turns that into an
            // exception rather than an exit code.
            $failure = $exception->getMessage();
        }

        $output = trim($process->getErrorOutput() ?: $process->getOutput());

        if ($failure !== null || !is_file($pdfPath)) {
            return [
                'contents' => null,
                'error' => trim(($failure ?? 'لم يُنتج المتصفح أي ملف.') . ' ' . Str::limit($output, 300)),
            ];
        }

        $contents = File::get($pdfPath);

        if (!Str::startsWith($contents, '%PDF')) {
            return ['contents' => null, 'error' => 'لم يُرجع المتصفح ملف PDF صالحًا.'];
        }

        return ['contents' => $contents, 'error' => ''];
    }

    /**
     * Startup profiles ordered from fastest to most permissive. Restricted
     * shared hosting (CloudLinux/CageFS, containers without user namespaces)
     * kills Chromium's zygote process, so each profile disables one more
     * layer of process isolation until one survives.
     *
     * @return array<int, string>
     */
    public function browserProfiles(): array
    {
        $configured = strtolower(trim((string) config('cv.pdf.browser_profile', '')));

        if ($configured !== '' && in_array($configured, self::BROWSER_PROFILES, true)) {
            return [$configured];
        }

        return self::BROWSER_PROFILES;
    }

    public function browserProfileCode(string $profile): string
    {
        return match ($profile) {
            'default' => 'd',
            'no-zygote' => 'z',
            'single-process' => 's',
            'legacy-headless' => 'l',
            default => 'x',
        };
    }

    public function createBrowserTemporaryDirectory(string $prefix = 'cv'): string
    {
        $configuredPath = trim((string) config('cv.pdf.temp_path', ''));
        $candidates = array_values(array_unique(array_filter([
            $configuredPath,
            PHP_OS_FAMILY !== 'Windows' ? '/tmp' : null,
            sys_get_temp_dir(),
        ])));

        foreach ($candidates as $baseDirectory) {
            try {
                File::ensureDirectoryExists($baseDirectory);
            } catch (\Throwable $exception) {
                continue;
            }

            if (!is_dir($baseDirectory) || !is_writable($baseDirectory)) {
                continue;
            }

            $safePrefix = substr(preg_replace('/[^a-z0-9]/i', '', $prefix) ?: 'cv', 0, 3);

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $directory = rtrim($baseDirectory, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . $safePrefix
                    . '-'
                    . bin2hex(random_bytes(4));

                if (!file_exists($directory)) {
                    File::ensureDirectoryExists($directory);

                    return $directory;
                }
            }
        }

        throw new RuntimeException(
            'تعذر إنشاء مجلد مؤقت قصير وقابل للكتابة لتشغيل Chromium. '
            . 'اضبط CV_PDF_TEMP_PATH على مسار قصير مثل /tmp.'
        );
    }

    /**
     * @return array<int, string>
     */
    public function browserCommand(
        string $browserPath,
        string $profilePath,
        string $pdfPath,
        string $htmlPath,
        string $profile = 'default'
    ): array {
        $command = [
            $browserPath,
            $profile === 'legacy-headless' ? '--headless' : '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-crash-reporter',
            '--disable-breakpad',
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
        ];

        if ($profile !== 'default') {
            $command[] = '--no-zygote';
        }

        if (in_array($profile, ['single-process', 'legacy-headless'], true)) {
            $command[] = '--single-process';
        }

        return array_merge($command, [
            '--user-data-dir=' . $profilePath,
            '--crash-dumps-dir=' . $profilePath,
            '--print-to-pdf=' . $pdfPath,
            $this->fileUrl($htmlPath),
        ]);
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
