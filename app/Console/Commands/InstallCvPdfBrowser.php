<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class InstallCvPdfBrowser extends Command
{
    private const MANIFEST_URL = 'https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json';

    protected $signature = 'cv:install-browser {--channel=Stable : Chrome channel: Stable, Beta, Dev or Canary}';

    protected $description = 'Install a project-local Chrome for Testing binary for consistent generated CV PDFs';

    public function handle(): int
    {
        try {
            [$platform, $executableRelativePath] = $this->platformDetails();
            $channel = ucfirst(strtolower((string) $this->option('channel')));

            if (!in_array($channel, ['Stable', 'Beta', 'Dev', 'Canary'], true)) {
                throw new RuntimeException('القناة يجب أن تكون Stable أو Beta أو Dev أو Canary.');
            }

            $this->info('جاري قراءة إصدار Chrome for Testing المتاح...');
            $manifestResponse = Http::timeout(30)->get(self::MANIFEST_URL);

            if (!$manifestResponse->successful()) {
                throw new RuntimeException('تعذر قراءة قائمة إصدارات Chrome for Testing.');
            }

            $channelData = data_get($manifestResponse->json(), "channels.$channel");
            $version = trim((string) data_get($channelData, 'version'));
            $download = collect(data_get($channelData, 'downloads.chrome', []))
                ->firstWhere('platform', $platform);
            $downloadUrl = trim((string) data_get($download, 'url'));

            if ($version === '' || $downloadUrl === '') {
                throw new RuntimeException("لم يتوفر إصدار Chrome للمنصة $platform.");
            }

            if (!Str::startsWith($downloadUrl, 'https://storage.googleapis.com/chrome-for-testing-public/')) {
                throw new RuntimeException('رابط تنزيل Chrome المستلم غير موثوق.');
            }

            $installDirectory = storage_path("app/cv-browser/$version");
            $executablePath = $installDirectory . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $executableRelativePath);

            if (is_file($executablePath)) {
                $this->info("Chrome $version مثبت بالفعل:");
                $this->line($executablePath);

                return self::SUCCESS;
            }

            $temporaryRoot = storage_path('app/cv-browser/.install-' . Str::uuid());
            $archivePath = $temporaryRoot . DIRECTORY_SEPARATOR . 'chrome.zip';
            $extractDirectory = $temporaryRoot . DIRECTORY_SEPARATOR . 'extracted';

            File::ensureDirectoryExists($temporaryRoot);
            File::ensureDirectoryExists($extractDirectory);

            try {
                $this->info("جاري تنزيل Chrome $version...");
                $downloadResponse = Http::withOptions(['sink' => $archivePath])
                    ->timeout(900)
                    ->get($downloadUrl);

                if (!$downloadResponse->successful() || !is_file($archivePath) || filesize($archivePath) === 0) {
                    throw new RuntimeException('تعذر تنزيل ملف Chrome كاملًا.');
                }

                $archive = new ZipArchive();
                if ($archive->open($archivePath) !== true) {
                    throw new RuntimeException('ملف Chrome الذي تم تنزيله ليس ZIP صالحًا.');
                }

                try {
                    $this->assertSafeArchive($archive);

                    if (!$archive->extractTo($extractDirectory)) {
                        throw new RuntimeException('تعذر فك ضغط Chrome.');
                    }
                } finally {
                    $archive->close();
                }

                $extractedExecutable = $extractDirectory . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $executableRelativePath);

                if (!is_file($extractedExecutable)) {
                    throw new RuntimeException('لم يتم العثور على ملف Chrome التنفيذي بعد فك الضغط.');
                }

                File::ensureDirectoryExists(dirname($installDirectory));
                if (!File::moveDirectory($extractDirectory, $installDirectory)) {
                    throw new RuntimeException('تعذر نقل Chrome إلى مجلد التثبيت داخل storage.');
                }

                if (PHP_OS_FAMILY !== 'Windows') {
                    // Chrome spawns helper binaries of its own, and the zip
                    // does not always carry the executable bit through.
                    foreach (['chrome_crashpad_handler', 'chrome_sandbox'] as $helper) {
                        $helperPath = dirname($executablePath) . DIRECTORY_SEPARATOR . $helper;

                        if (is_file($helperPath)) {
                            @chmod($helperPath, 0755);
                        }
                    }

                    @chmod($executablePath, 0755);
                }
            } finally {
                if (is_dir($temporaryRoot)) {
                    File::deleteDirectory($temporaryRoot);
                }
            }

            if (!is_file($executablePath) || (PHP_OS_FAMILY !== 'Windows' && !is_executable($executablePath))) {
                throw new RuntimeException('تم تنزيل Chrome لكن الملف التنفيذي غير قابل للتشغيل.');
            }

            $this->newLine();
            $this->info('تم تثبيت Chrome الخاص بتوليد السير الذاتية بنجاح:');
            $this->line($executablePath);
            $this->newLine();
            $this->comment('لا تحتاج إلى تعديل CV_PDF_BROWSER_PATH؛ سيكتشفه النظام تلقائيًا.');
            $this->comment('نفّذ الآن: php artisan cv:doctor للتأكد من أن التشغيل يعمل فعليًا على هذا السيرفر.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function platformDetails(): array
    {
        return match (PHP_OS_FAMILY) {
            'Linux' => ['linux64', 'chrome-linux64/chrome'],
            'Windows' => ['win64', 'chrome-win64/chrome.exe'],
            default => throw new RuntimeException(
                'التثبيت التلقائي مدعوم حاليًا على Linux وWindows فقط.'
            ),
        };
    }

    private function assertSafeArchive(ZipArchive $archive): void
    {
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $entry = str_replace('\\', '/', (string) $archive->getNameIndex($index));

            if (
                $entry === ''
                || Str::startsWith($entry, '/')
                || preg_match('/^[a-z]:\//i', $entry)
                || in_array('..', explode('/', $entry), true)
            ) {
                throw new RuntimeException('ملف Chrome المضغوط يحتوي على مسار غير آمن.');
            }
        }
    }
}
