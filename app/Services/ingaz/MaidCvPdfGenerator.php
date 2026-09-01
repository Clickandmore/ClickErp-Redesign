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
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
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
        $html = $this->embedManagedMedia(
            $this->renderTemplate($template, $this->viewData($maid, $settings, $outputType)),
            $maid,
            $settings
        );

        if ($outputType === self::OUTPUT_IMAGE) {
            [$contents, $extension] = $this->generateImageOutput($html, $maid);
        } else {
            $contents = $this->generatePdfContents($html, $maid);
            $extension = 'pdf';
        }

        if ($contents === '') {
            throw new RuntimeException('تعذر إنشاء ملف السيرة الذاتية من القالب المرفوع.');
        }

        $storage = app(ManagedFileStorage::class);
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
     * تجربة الخطوة التي تفشل فعلياً في الإنتاج — لقطة المتصفح — دون الكتابة في
     * قاعدة البيانات ولا في التخزين. يستخدمها أمر cv:doctor.
     *
     * @return array{ok: bool, bytes: int, width: int, height: int, error: ?string}
     */
    public function probeBrowserRendering(Maid $maid): array
    {
        $failure = ['ok' => false, 'bytes' => 0, 'width' => 0, 'height' => 0, 'error' => null];

        $settings = MaidCvTemplateSettings::current();
        $template = trim((string) ($settings->cv_template_blade ?? ''));

        if (!$settings || !$settings->cv_template_enabled || $template === '') {
            return $failure + ['error' => 'قالب السيرة غير مفعّل أو غير مرفوع.'];
        }

        if (!function_exists('proc_open')) {
            return $failure + ['error' => 'proc_open معطّلة في php.ini.'];
        }

        $browserPath = $this->browserExecutable();

        if ($browserPath === null) {
            return $failure + ['error' => 'لم يُعثر على Chrome/Chromium على هذا الخادم.'];
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

        try {
            $html = $this->embedManagedMedia(
                $this->renderTemplate($template, $this->viewData($maid, $settings, self::OUTPUT_IMAGE)),
                $maid,
                $settings
            );

            $png = $this->generatePngWithBrowser($browserPath, $html);
            $dimensions = @getimagesizefromstring($png) ?: [0, 0];

            return [
                'ok' => true,
                'bytes' => strlen($png),
                'width' => (int) ($dimensions[0] ?? 0),
                'height' => (int) ($dimensions[1] ?? 0),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return $failure + ['error' => $exception->getMessage()];
        }
    }

    /** Render a PDF copy without changing the stored CV preview. */
    public function renderPdfContents(Maid $maid): string
    {
        $settings = MaidCvTemplateSettings::current();

        if (!$settings || !$settings->cv_template_enabled) {
            throw new RuntimeException('إنشاء نموذج السيرة غير مفعل.');
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

        $html = $this->embedManagedMedia(
            $this->renderTemplate($template, $this->viewData($maid, $settings, self::OUTPUT_PDF)),
            $maid,
            $settings
        );
        // Downloads and share links must respond within the normal web request
        // timeout, so use the in-process PDF renderer instead of launching Chrome.
        $contents = $this->generateWithMpdf($html);

        if ($contents === '' || !Str::startsWith($contents, '%PDF')) {
            throw new RuntimeException('تعذر إنشاء ملف PDF لنموذج السيرة.');
        }

        return $contents;
    }

    /**
     * Wrap the already-generated CV image in a PDF. SVG fallbacks containing
     * XHTML are rendered from the Blade template because mPDF cannot draw them.
     */
    public function renderStoredImageAsPdfContents(string $storedPath, ?Maid $maid = null): string
    {
        $storage = app(ManagedFileStorage::class);
        abort_unless($storage->exists($storedPath), 404);

        $contents = $storage->contents($storedPath);
        $mimeType = $storage->mimeType($storedPath);

        if (!Str::startsWith($mimeType, 'image/')) {
            throw new RuntimeException('The stored CV preview is not an image.');
        }

        // mPDF accepts SVG files, but it silently drops XHTML rendered through
        // <foreignObject>. Generated SVG fallbacks use that structure, which
        // previously produced a valid yet completely blank PDF download.
        if ($maid !== null
            && Str::startsWith($mimeType, 'image/svg+xml')
            && stripos($contents, '<foreignObject') !== false) {
            return $this->renderForeignObjectSvgAsPdfContents($contents, $maid);
        }

        return $this->renderImageContentsAsPdfContents(
            $contents,
            $mimeType,
            strtolower(pathinfo($storedPath, PATHINFO_EXTENSION))
        );
    }

    /**
     * Place an already-rendered image on a PDF page without asking the PDF
     * engine to interpret the original HTML/CSS template again.
     */
    private function renderImageContentsAsPdfContents(
        string $contents,
        string $mimeType,
        string $sourceExtension = ''
    ): string {

        if ($contents === '') {
            throw new RuntimeException('The stored CV preview image is empty.');
        }

        $temporaryDirectory = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($temporaryDirectory);
        $extension = strtolower($sourceExtension);
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'], true)
            ? $extension
            : match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                default => 'png',
            };
        $temporaryImagePath = $temporaryDirectory . DIRECTORY_SEPARATOR
            . 'cv-preview-' . Str::uuid() . '.' . $extension;
        File::put($temporaryImagePath, $contents);
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
                'tempDir' => $temporaryDirectory,
            ]);

            $pageWidth = 210.0;
            $pageHeight = 297.0;
            $imageWidth = $pageWidth;
            $imageHeight = $pageHeight;
            $imageX = 0.0;
            $imageY = 0.0;
            $dimensions = @getimagesize($temporaryImagePath);

            if (is_array($dimensions) && ($dimensions[0] ?? 0) > 0 && ($dimensions[1] ?? 0) > 0) {
                $scale = min($pageWidth / $dimensions[0], $pageHeight / $dimensions[1]);
                $imageWidth = $dimensions[0] * $scale;
                $imageHeight = $dimensions[1] * $scale;
                $imageX = ($pageWidth - $imageWidth) / 2;
                $imageY = ($pageHeight - $imageHeight) / 2;
            }

            $pdf->SetDisplayMode('fullpage');
            $pdf->AddPage();
            $pdf->Image(
                $temporaryImagePath,
                $imageX,
                $imageY,
                $imageWidth,
                $imageHeight
            );

            $pdfContents = $pdf->Output('', Destination::STRING_RETURN);
            if ($pdfContents === '' || !Str::startsWith($pdfContents, '%PDF')) {
                throw new RuntimeException('Failed to create a PDF from the stored CV image.');
            }

            return $pdfContents;
        } finally {
            error_reporting($previousErrorReporting);
            File::delete($temporaryImagePath);
        }
    }

    private function generateImageOutput(string $html, Maid $maid): array
    {
        if (!function_exists('proc_open')) {
            return [$this->generateSvgFallback($html), 'svg'];
        }

        $browserPath = $this->browserExecutable();
        if ($browserPath === null) {
            return [$this->generateSvgFallback($html), 'svg'];
        }

        try {
            return [$this->generatePngWithBrowser($browserPath, $html), 'png'];
        } catch (\Throwable $exception) {
            report($exception);
            \Log::warning('Chrome CV image generation failed; using the database-template SVG fallback.', [
                'maid_id' => $maid->id,
                'error' => $exception->getMessage(),
            ]);

            return [$this->generateSvgFallback($html), 'svg'];
        }
    }

    /**
     * Generate a high-resolution PNG using the isolated Chrome sequence used
     * by asuss, while rendering the database Blade template.
     */
    private function generatePngWithBrowser(
        string $browserPath,
        string $html,
        string $documentExtension = 'html'
    ): string
    {

        $temporaryDirectory = storage_path('app/cv-image-temp/' . Str::uuid());
        $profilePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'browser-profile';
        File::ensureDirectoryExists($profilePath);

        $documentExtension = $documentExtension === 'svg' ? 'svg' : 'html';
        $htmlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.' . $documentExtension;
        $imagePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'cv.png';
        $viewportWidth = max(320, (int) config('services.chrome.viewport_width', 794));
        $viewportHeight = max(480, (int) config('services.chrome.viewport_height', 1123));

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
            $process->start();

            $contents = null;
            while ($process->isRunning()) {
                if (is_file($imagePath)) {
                    $candidate = File::get($imagePath);
                    if (Str::startsWith($candidate, "\x89PNG\r\n\x1a\n")
                        && Str::endsWith($candidate, "\x00\x00\x00\x00IEND\xAE\x42\x60\x82")) {
                        $contents = $candidate;
                        break;
                    }
                }

                $process->checkTimeout();
                usleep(100000);
            }

            if ($contents !== null && $process->isRunning()) {
                // Chrome on macOS can leave an updater child attached to the
                // process pipes after the screenshot is already complete.
                $process->stop(1);
            }

            if ($contents === null && is_file($imagePath)) {
                $contents = File::get($imagePath);
            }

            if ($contents === null || !Str::startsWith($contents, "\x89PNG\r\n\x1a\n")) {
                throw new RuntimeException(
                    'تعذر إنشاء صورة السيرة بواسطة المتصفح: '
                    . trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            return $contents;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /**
     * Render the exact stored SVG preview in Chrome before placing it in PDF.
     * This preserves foreignObject HTML/CSS that PDF libraries cannot render.
     */
    protected function renderForeignObjectSvgAsPdfContents(string $svgContents, Maid $maid): string
    {
        $browserPath = $this->browserExecutable();

        if (function_exists('proc_open') && $browserPath !== null) {
            try {
                $imageContents = $this->generatePngWithBrowser($browserPath, $svgContents, 'svg');

                return $this->renderImageContentsAsPdfContents(
                    $imageContents,
                    'image/png',
                    'png'
                );
            } catch (\Throwable $exception) {
                report($exception);
                \Log::warning('Stored SVG CV browser rendering failed; using template PDF fallback.', [
                    'maid_id' => $maid->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $this->renderPdfContents($maid);
    }

    /**
     * Browser-independent fallback matching asuss' production strategy.
     * The rendered database template is converted to XHTML and embedded in SVG.
     */
    private function generateSvgFallback(string $html): string
    {
        $viewportWidth = max(320, (int) config('services.chrome.viewport_width', 794));
        $viewportHeight = max(480, (int) config('services.chrome.viewport_height', 1123));

        if (!class_exists(\DOMDocument::class)) {
            $plainText = e(Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? ''), 4000));

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="' . $viewportWidth . '" height="' . $viewportHeight . '" viewBox="0 0 ' . $viewportWidth . ' ' . $viewportHeight . '">'
                . '<rect width="100%" height="100%" fill="#fff"/>'
                . '<text x="40" y="70" fill="#111" font-family="Tahoma,Arial,sans-serif" font-size="16">' . $plainText . '</text>'
                . '</svg>';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
            );

            $xpath = new \DOMXPath($document);
            foreach ($xpath->query('//script') ?: [] as $script) {
                $script->parentNode?->removeChild($script);
            }

            $this->inlineLocalDocumentImages($xpath);

            $styles = '';
            foreach ($xpath->query('//style') ?: [] as $style) {
                $styles .= $document->saveXML($style);
            }

            $body = $document->getElementsByTagName('body')->item(0);
            $content = '';
            $nodes = $body ? iterator_to_array($body->childNodes) : iterator_to_array($document->childNodes);

            foreach ($nodes as $node) {
                if ($node instanceof \DOMElement && in_array(strtolower($node->tagName), ['html', 'head', 'style'], true)) {
                    if (strtolower($node->tagName) === 'html') {
                        foreach (iterator_to_array($node->childNodes) as $htmlChild) {
                            if ($htmlChild instanceof \DOMElement && strtolower($htmlChild->tagName) === 'body') {
                                foreach (iterator_to_array($htmlChild->childNodes) as $bodyChild) {
                                    $content .= $document->saveXML($bodyChild);
                                }
                            }
                        }
                    }
                    continue;
                }

                $content .= $document->saveXML($node);
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="' . $viewportWidth . '" height="' . $viewportHeight . '" viewBox="0 0 ' . $viewportWidth . ' ' . $viewportHeight . '">'
                . '<rect width="100%" height="100%" fill="#fff"/>'
                . '<foreignObject x="0" y="0" width="100%" height="100%">'
                . '<div xmlns="http://www.w3.org/1999/xhtml" style="width:' . $viewportWidth . 'px;height:' . $viewportHeight . 'px;overflow:hidden;background:#fff">'
                . $styles . $content
                . '</div></foreignObject></svg>';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    /**
     * SVG files displayed through an img tag cannot reliably load nested local
     * assets, so embed local template images just like the asuss generator does.
     */
    private function inlineLocalDocumentImages(\DOMXPath $xpath): void
    {
        $allowedRoots = array_values(array_filter([
            realpath(public_path()),
            realpath(storage_path('app/public')),
        ]));

        foreach ($xpath->query('//img[@src]') ?: [] as $image) {
            if (!$image instanceof \DOMElement) {
                continue;
            }

            $source = trim($image->getAttribute('src'));
            if ($source === '' || Str::startsWith($source, ['data:', 'blob:'])) {
                continue;
            }

            $urlPath = rawurldecode((string) (parse_url($source, PHP_URL_PATH) ?: $source));
            $relativePath = ltrim(str_replace('\\', '/', $urlPath), '/');
            $candidates = [public_path($relativePath)];

            if (Str::startsWith($relativePath, 'storage/')) {
                $candidates[] = storage_path('app/public/' . Str::after($relativePath, 'storage/'));
            }

            foreach ($candidates as $candidate) {
                $resolved = realpath($candidate);
                if (!$resolved || !is_file($resolved)) {
                    continue;
                }

                $isAllowed = collect($allowedRoots)->contains(
                    fn (string $root) => Str::startsWith(str_replace('\\', '/', $resolved), rtrim(str_replace('\\', '/', $root), '/') . '/')
                );
                if (!$isAllowed) {
                    continue;
                }

                try {
                    $mime = File::mimeType($resolved) ?: 'application/octet-stream';
                    $image->setAttribute('src', 'data:' . $mime . ';base64,' . base64_encode(File::get($resolved)));
                } catch (\Throwable) {
                    // Keep the original source when a local asset cannot be read.
                }

                break;
            }
        }
    }

    private function generatePdfContents(string $html, Maid $maid): string
    {
        $browserPath = $this->browserExecutable();

        if (function_exists('proc_open') && $browserPath !== null) {
            try {
                $imageContents = $this->generatePngWithBrowser($browserPath, $html);

                return $this->renderImageContentsAsPdfContents(
                    $imageContents,
                    'image/png',
                    'png'
                );
            } catch (\Throwable $exception) {
                report($exception);
                \Log::warning('Chrome CV screenshot-to-PDF generation failed; using mPDF fallback.', [
                    'maid_id' => $maid->id,
                    'error' => $exception->getMessage(),
                ]);
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
        $fontDirectory = public_path('cv/webfonts/tajawal');
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();
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
                'fontDir' => array_merge($defaultConfig['fontDir'], [$fontDirectory]),
                'fontdata' => $defaultFontConfig['fontdata'] + [
                    'tajawal' => [
                        'R' => 'Tajawal-Regular.ttf',
                        'B' => 'Tajawal-Bold.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                ],
                'default_font' => 'tajawal',
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

    /** عام حتى يتمكّن أمر cv:doctor من عرض المسار المكتشف. */
    public function browserExecutable(): ?string
    {
        // يُقرأ من config لا من env مباشرة: env() ترجع null بعد config:cache.
        $configuredPath = trim((string) config('services.chrome.path', ''));
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
        $previousPublicSiteUrl = config('app.public_site_url');
        $cvPublicSiteUrl = trim((string) data_get($data, 'settings.cv_public_site_url'));

        if ($cvPublicSiteUrl !== '') {
            config(['app.public_site_url' => rtrim($cvPublicSiteUrl, '/')]);
        }

        try {
            return $this->embedLocalTajawalFont(Blade::render($template, $data, true));
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'تعذر تنفيذ قالب السيرة الذاتية: ' . $exception->getMessage(),
                previous: $exception
            );
        } finally {
            config(['app.public_site_url' => $previousPublicSiteUrl]);
        }
    }

    /**
     * Embed Tajawal in the generated document so file:// rendering and servers
     * without outbound access do not fall back to a different Arabic font.
     */
    private function embedLocalTajawalFont(string $html): string
    {
        static $fontStyle = null;

        if ($fontStyle === null) {
            $faces = [];

            $fontFiles = [
                400 => 'Tajawal-Regular.ttf',
                500 => 'Tajawal-Medium.ttf',
                700 => 'Tajawal-Bold.ttf',
                800 => 'Tajawal-ExtraBold.ttf',
                900 => 'Tajawal-Black.ttf',
            ];

            foreach ($fontFiles as $weight => $filename) {
                $path = public_path('cv/webfonts/tajawal/' . $filename);

                if (!is_file($path) || !is_readable($path)) {
                    continue;
                }

                $faces[] = '@font-face{'
                    . 'font-family:"Tajawal";'
                    . 'font-style:normal;'
                    . "font-weight:{$weight};"
                    . 'font-display:block;'
                    . 'src:url("data:font/ttf;base64,' . base64_encode(File::get($path)) . '") format("truetype");'
                    . '}';
            }

            $fontStyle = $faces
                ? '<style data-cv-local-font="tajawal">' . implode('', $faces) . '</style>'
                : '';
        }

        if ($fontStyle === '') {
            return $html;
        }

        // The uploaded templates may still contain Google Fonts links. They are
        // unnecessary once Tajawal is embedded and can delay or fail offline output.
        $html = preg_replace(
            '~<link\b[^>]*href=["\']https://fonts\.(?:googleapis|gstatic)\.com[^"\']*["\'][^>]*>\s*~i',
            '',
            $html
        ) ?? $html;

        // Existing uploaded templates request Cairo. Use the locally embedded
        // Tajawal family without requiring the template to be uploaded again.
        $html = str_ireplace('Cairo', 'Tajawal', $html);

        if (stripos($html, '</head>') !== false) {
            return preg_replace('~</head>~i', $fontStyle . '</head>', $html, 1) ?? $html;
        }

        return $fontStyle . $html;
    }

    /**
     * يضمّن ملفات النظام داخل الـ HTML كـ data URI قبل التحويل.
     *
     * القالب يستدعي showFile() فيخرج رابطًا: محلي `.../storage/...` أو رابط R2 موقّع.
     * الاثنان يحتاجان طلب شبكة أثناء التحويل — المتصفح قد يفشل في جلبه ضمن مهلة اللقطة،
     * ومسار SVG لا يحمّل موارد خارجية إطلاقًا فتظهر الصورة فارغة. القراءة هنا تتم عبر
     * ManagedFileStorage فتعمل مع R2 والتخزين المحلي بنفس الطريقة.
     */
    private function embedManagedMedia(string $html, Maid $maid, Setting $settings): string
    {
        $storage = app(ManagedFileStorage::class);

        foreach ($this->mediaSourcePaths($maid, $settings) as $storedPath) {
            $url = showFile($storedPath);

            if (!filled($url)) {
                continue;
            }

            // الروابط الموقّعة تحمل توقيعًا مختلفًا في كل استدعاء، والثابت منها هو ما قبل "?".
            $base = strtok($url, '?');

            if ($base === false || $base === '' || !str_contains($html, $base)) {
                continue;
            }

            try {
                $dataUri = 'data:' . $storage->mimeType($storedPath)
                    . ';base64,' . base64_encode($storage->contents($storedPath));
            } catch (\Throwable $exception) {
                report($exception);

                continue;
            }

            $html = preg_replace(
                '~' . preg_quote($base, '~') . '(\?[^"\'\s>]*)?~',
                // القيمة قد تحوي "$" فتُفسَّر كمرجع في preg_replace
                str_replace(['\\', '$'], ['\\\\', '\\$'], $dataUri),
                $html
            ) ?? $html;
        }

        return $html;
    }

    /**
     * مسارات الملفات التي قد يعرضها قالب السيرة.
     *
     * @return array<int, string>
     */
    private function mediaSourcePaths(Maid $maid, Setting $settings): array
    {
        $paths = [
            $maid->getRawOriginal('image'),
            $maid->getRawOriginal('personal_image'),
            // The uploaded full-body photo is rendered in the CV sidebar.
            // Embed it so private/R2 URLs are not fetched during generation.
            $maid->getRawOriginal('cv_file'),
            optional($maid->nationality)->getRawOriginal('logo'),
        ];

        foreach ([
            'logo_header', 'logo_footer', 'favicon', 'experience_image',
            'qr_image', 'second_qr_image', 'zatch_logo', 'cover_image',
            'about_image', 'admin_logo', 'document_logo', 'light_image',
        ] as $column) {
            $paths[] = $settings->getRawOriginal($column);
        }

        return array_values(array_unique(array_filter(
            $paths,
            fn ($path): bool => is_string($path) && trim($path) !== '' && trim($path) !== 'null'
        )));
    }

    public function isGeneratedPath(?string $path): bool
    {
        if (!is_string($path) || trim($path) === '') {
            return false;
        }

        $normalizedPath = trim(str_replace('\\', '/', rawurldecode($path)));
        if (Str::startsWith($normalizedPath, ManagedFileStorage::R2_PREFIX)) {
            $normalizedPath = Str::after($normalizedPath, ManagedFileStorage::R2_PREFIX);
        }

        // A stored value can also be a public R2/CDN URL. Only its path is
        // relevant, and query strings or fragments must not affect detection.
        if (Str::startsWith($normalizedPath, ['http://', 'https://'])) {
            $normalizedPath = (string) (parse_url($normalizedPath, PHP_URL_PATH) ?: '');
        } else {
            $normalizedPath = preg_split('/[?#]/', $normalizedPath, 2)[0] ?? '';
        }

        $normalizedPath = '/' . ltrim($normalizedPath, '/');
        $generatedDirectory = '/' . trim(self::STORAGE_DIRECTORY, '/') . '/';
        $filename = basename($normalizedPath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Covers local paths, legacy paths, R2 pointers with any root prefix,
        // and public CDN URLs without coupling detection to storage settings.
        return $filename !== ''
            && in_array($extension, ['pdf', 'png', 'svg'], true)
            && Str::contains($normalizedPath, $generatedDirectory);
    }

    private function viewData(Maid $maid, Setting $settings, string $outputType): array
    {
        $expertises = $maid->expertises;
        $skills = Skill::query()->with('translations')->get();

        return [
            'maid' => $maid,
            'settings' => $settings,
            'skills' => $skills,
            // القيم بالعربية والإنجليزية معًا حتى تعرض قوالب السيرة اللغتين جنبًا إلى جنب.
            'i18n' => \App\Support\MaidCvTranslations::build(
                $maid,
                $skills,
                \App\Support\ApiTranslationLocales::SUPPORTED
            ),
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
