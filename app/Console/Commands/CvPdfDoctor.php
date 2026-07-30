<?php

namespace App\Console\Commands;

use App\Services\ingaz\MaidCvPdfGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CvPdfDoctor extends Command
{
    protected $signature = 'cv:doctor';

    protected $description = 'Diagnose why generated CV PDFs do not match the uploaded template on this machine';

    private array $problems = [];

    private bool $permissionDenied = false;

    public function handle(MaidCvPdfGenerator $generator): int
    {
        $this->line('<comment>تشخيص محرك توليد السير الذاتية</comment>');
        $this->newLine();

        $this->reportEnvironment();
        $this->reportFallbackMode($generator);

        $remoteDriver = $generator->remoteDriver();

        if ($remoteDriver !== null) {
            $this->reportRemoteRenderer($remoteDriver);
        } else {
            $this->reportLocalBrowser($generator);
        }

        $this->newLine();
        $this->reportSmokeTest($generator);

        $this->newLine();

        if ($this->problems === []) {
            $this->info('لا توجد مشاكل. السير الذاتية ستُولَّد بنفس تنسيق القالب على هذا السيرفر.');

            return self::SUCCESS;
        }

        $this->error('تم العثور على ' . count($this->problems) . ' مشكلة:');
        foreach ($this->problems as $index => $problem) {
            $this->line('  ' . ($index + 1) . '. ' . $problem);
        }

        return self::FAILURE;
    }

    private function reportEnvironment(): void
    {
        $this->line('<info>البيئة</info>');
        $this->line('  نظام التشغيل: ' . PHP_OS_FAMILY . ' (' . php_uname('r') . ')');
        $this->line('  إصدار PHP: ' . PHP_VERSION . ' [' . PHP_SAPI . ']');

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        if (!function_exists('proc_open')) {
            $this->line('  proc_open: <fg=red>معطّلة</>');
            $this->problems[] = 'الدالة proc_open معطّلة في php.ini، لذلك لا يمكن تشغيل Chromium محليًا. '
                . 'أزلها من disable_functions أو استخدم خدمة رسم خارجية عبر CV_PDF_REMOTE_DRIVER.';
        } else {
            $this->line('  proc_open: <fg=green>متاحة</>');
        }

        if ($disabled !== []) {
            $this->line('  disable_functions: ' . Str::limit(implode(', ', $disabled), 160));
        }

        $storage = storage_path('app');
        $this->line('  storage/app قابل للكتابة: ' . (is_writable($storage) ? '<fg=green>نعم</>' : '<fg=red>لا</>'));

        if (!is_writable($storage)) {
            $this->problems[] = "المجلد $storage غير قابل للكتابة، وChromium يحتاجه للملفات المؤقتة.";
        }

        $this->newLine();
    }

    private function reportFallbackMode(MaidCvPdfGenerator $generator): void
    {
        $this->line('<info>وضع التوليد</info>');

        if ($generator->mpdfFallbackEnabled()) {
            $this->line('  السقوط إلى mPDF: <fg=yellow>مفعّل</>');
            $this->problems[] = 'CV_PDF_ALLOW_MPDF_FALLBACK مفعّل. هذا هو سبب اختلاف تنسيق السيرة بين اللوكال '
                . 'والسيرفر لأن mPDF لا يدعم flexbox وgrid والتدرجات اللونية. اضبطه على false.';
        } else {
            $this->line('  السقوط إلى mPDF: <fg=green>معطّل (Chromium فقط)</>');
        }

        $this->newLine();
    }

    private function reportRemoteRenderer(string $driver): void
    {
        $this->line('<info>خدمة الرسم الخارجية</info>');
        $url = rtrim(trim((string) config('cv.pdf.remote.url', '')), '/');

        $this->line('  المحرك: ' . $driver);
        $this->line('  الرابط: ' . ($url !== '' ? $url : '<fg=red>غير مضبوط</>'));

        if (!in_array($driver, ['gotenberg', 'browserless'], true)) {
            $this->problems[] = "CV_PDF_REMOTE_DRIVER=$driver غير مدعوم. المدعوم: gotenberg أو browserless.";
        }

        if ($url === '') {
            $this->problems[] = 'CV_PDF_REMOTE_DRIVER مضبوط بينما CV_PDF_REMOTE_URL فارغ.';
        }
    }

    private function reportLocalBrowser(MaidCvPdfGenerator $generator): void
    {
        $this->line('<info>متصفح Chromium المحلي</info>');

        $configured = trim((string) config('cv.pdf.browser_path', ''));
        $this->line('  CV_PDF_BROWSER_PATH: ' . ($configured !== '' ? $configured : '(فارغ — بحث تلقائي)'));

        if ($configured !== '' && !is_file($configured)) {
            $this->problems[] = "المسار المضبوط في CV_PDF_BROWSER_PATH غير موجود: $configured";
        }

        $managed = $generator->managedBrowserExecutables();
        $this->line('  نسخ داخل storage/app/cv-browser: ' . ($managed === [] ? 'لا يوجد' : count($managed)));

        $browserPath = $generator->browserExecutable();

        if ($browserPath === null) {
            $this->line('  الملف التنفيذي: <fg=red>لم يُعثر عليه</>');
            $this->problems[] = 'لم يُعثر على Chromium. نفّذ: php artisan cv:install-browser';

            return;
        }

        $this->line('  الملف التنفيذي: <fg=green>' . $browserPath . '</>');

        // `chrome.exe --version` attaches to the developer's running Chrome and
        // pops a window open, so probe only where the diagnosis matters.
        if (!function_exists('proc_open') || PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $version = new Process([$browserPath, '--version']);
        $version->setTimeout(30);
        $version->run();

        if ($version->isSuccessful()) {
            $this->line('  الإصدار: ' . trim($version->getOutput()));

            return;
        }

        $error = trim($version->getErrorOutput() ?: $version->getOutput());
        $this->line('  الإصدار: <fg=red>فشل التشغيل</>');
        $this->line('  ' . Str::limit($error, 300));

        if (Str::contains($error, ['Permission denied', 'Text file busy'])) {
            $this->problems[] = 'الملف التنفيذي موجود لكن التشغيل ممنوع. غالبًا المجلد مركّب بخيار noexec. '
                . 'اطلب من الاستضافة السماح بالتنفيذ في home، أو استخدم خدمة رسم خارجية.';
        }

        $this->reportMissingLibraries($browserPath);
    }

    private function reportMissingLibraries(string $browserPath): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $ldd = new Process(['ldd', $browserPath]);
        $ldd->setTimeout(60);
        $ldd->run();

        if (!$ldd->isSuccessful()) {
            return;
        }

        $missing = [];
        foreach (preg_split('/\R/', $ldd->getOutput()) ?: [] as $line) {
            if (Str::contains($line, 'not found')) {
                $missing[] = trim(Str::before(trim($line), ' =>'));
            }
        }

        if ($missing === []) {
            return;
        }

        $this->line('  مكتبات ناقصة: <fg=red>' . implode(', ', $missing) . '</>');
        $this->problems[] = 'Chromium يحتاج مكتبات نظام غير مثبتة (' . implode(', ', $missing) . '). '
            . 'على استضافة مشتركة لا يمكنك تثبيتها بنفسك — اطلبها من الدعم الفني، '
            . 'أو استخدم خدمة رسم خارجية عبر CV_PDF_REMOTE_DRIVER وCV_PDF_REMOTE_URL.';
    }

    private function reportSmokeTest(MaidCvPdfGenerator $generator): void
    {
        $this->line('<info>اختبار توليد فعلي</info>');

        if ($this->problems !== []) {
            $this->line('  <fg=yellow>تم تخطيه بسبب المشاكل أعلاه.</>');

            return;
        }

        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<style>@page{size:A4;margin:0}body{margin:0}'
            . '.row{display:flex;gap:8px}.box{flex:1;background:linear-gradient(90deg,#123,#396);'
            . 'color:#fff;padding:24px;border-radius:12px}</style></head>'
            . '<body><div class="row"><div class="box">اختبار</div><div class="box">Flexbox</div></div></body></html>';

        $directory = $generator->createBrowserTemporaryDirectory('doc');

        try {
            $htmlPath = $directory . DIRECTORY_SEPARATOR . 'i.html';
            File::put($htmlPath, $html);

            $browserPath = $generator->browserExecutable();

            if ($generator->remoteDriver() !== null || $browserPath === null) {
                $this->line('  <fg=yellow>تم تخطيه (لا يوجد متصفح محلي لاختباره).</>');

                return;
            }

            // Every profile is tried even after one succeeds, so the report
            // shows exactly which ones this host tolerates.
            foreach (MaidCvPdfGenerator::BROWSER_PROFILES as $profile) {
                if ($this->attemptProfile($generator, $browserPath, $htmlPath, $directory, $profile)) {
                    $this->newLine();
                    $this->info("نمط التشغيل العامل على هذا السيرفر: $profile");

                    if ($profile !== MaidCvPdfGenerator::BROWSER_PROFILES[0]) {
                        $this->comment("ثبّته في .env لتسريع التوليد: CV_PDF_BROWSER_PROFILE=$profile");
                    }

                    return;
                }
            }

            $this->problems[] = $this->permissionDenied
                ? 'فشل تشغيل Chromium لأن أحد ملفاته المساعدة غير قابل للتنفيذ '
                    . '(Permission denied). نفّذ php artisan cv:install-browser لإصلاح الصلاحيات، '
                    . 'ثم أعد php artisan cv:doctor.'
                : 'فشل تشغيل Chromium بكل أنماط التشغيل. '
                    . 'الاستضافة تمنع تشغيله فعليًا — استخدم خدمة رسم خارجية عبر '
                    . 'CV_PDF_REMOTE_DRIVER وCV_PDF_REMOTE_URL.';
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function attemptProfile(
        MaidCvPdfGenerator $generator,
        string $browserPath,
        string $htmlPath,
        string $directory,
        string $profile
    ): bool {
        $attemptDirectory = $directory . DIRECTORY_SEPARATOR . $generator->browserProfileCode($profile);
        File::ensureDirectoryExists($attemptDirectory);

        $pdfPath = $attemptDirectory . DIRECTORY_SEPARATOR . 'o.pdf';

        $process = new Process(
            $generator->browserCommand(
                $browserPath,
                $attemptDirectory . DIRECTORY_SEPARATOR . 'p',
                $pdfPath,
                $htmlPath,
                $profile
            ),
            $attemptDirectory,
            [
                'HOME' => $attemptDirectory,
                'XDG_CONFIG_HOME' => $attemptDirectory,
                'XDG_CACHE_HOME' => $attemptDirectory,
                'TMPDIR' => $attemptDirectory,
            ]
        );
        $process->setTimeout(120);

        try {
            $process->run();
            $failure = $process->isSuccessful() ? null : 'خرج بالرمز ' . $process->getExitCode();
        } catch (\Throwable $exception) {
            // A crashed Chromium is signalled, and Symfony turns that into an
            // exception rather than an exit code.
            $failure = $exception->getMessage();
        }

        if ($failure === null && is_file($pdfPath)) {
            $this->line("  $profile: <fg=green>نجح</> — " . number_format(filesize($pdfPath)) . ' بايت');

            return true;
        }

        $output = trim($process->getErrorOutput() ?: $process->getOutput());

        if (Str::contains($output, 'Permission denied')) {
            $this->permissionDenied = true;
        }

        $this->line("  $profile: <fg=red>فشل</> — " . ($failure ?? 'لم يُنتج أي ملف'));

        if ($output !== '') {
            $this->line('    ' . Str::limit($output, 400));
        }

        return false;
    }
}
