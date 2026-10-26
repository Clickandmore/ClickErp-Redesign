<?php

namespace App\Services;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SchedulerMonitorService
{
    public const HEARTBEAT_FILE = 'framework/scheduler-heartbeat.json';
    private const CRON_MARKER = '# ClickERP Laravel Scheduler';

    public function status(): array
    {
        $heartbeat = $this->heartbeat();
        $crontab = $this->crontabStatus();
        $recommendedEntry = '';

        try {
            $recommendedEntry = $this->cronEntry();
        } catch (\RuntimeException $exception) {
            $crontab['error'] = implode(' | ', array_filter([
                $crontab['error'], $exception->getMessage(),
            ]));
        }

        return [
            'active' => $heartbeat['active'],
            'last_heartbeat_at' => $heartbeat['last_heartbeat_at'],
            'heartbeat_age_seconds' => $heartbeat['age_seconds'],
            'crontab_configured' => $crontab['configured'],
            'crontab_entries' => $crontab['entries'],
            'crontab_error' => $crontab['error'],
            'recommended_entry' => $recommendedEntry,
            'jobs' => $this->jobs($heartbeat['active']),
        ];
    }

    public function enableAndRun(): array
    {
        return Cache::lock('scheduler-crontab-install', 30)->block(5, function (): array {
            $installed = $this->installCrontabEntry();
            $this->runNow();

            clearstatcache(true, storage_path(self::HEARTBEAT_FILE));

            return [
                'installed' => $installed,
                'status' => $this->status(),
            ];
        });
    }

    public static function writeHeartbeat(): void
    {
        $path = storage_path(self::HEARTBEAT_FILE);
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode([
            'timestamp' => time(),
            'ran_at' => now()->toIso8601String(),
            'hostname' => gethostname() ?: null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function jobs(bool $schedulerActive): array
    {
        $jobs = [];

        // HTTP requests do not normally resolve the console kernel. Resolving it here
        // registers the application's schedule before Schedule is read by this page.
        $consoleKernel = app(ConsoleKernel::class);
        $availableCommands = array_keys($consoleKernel->all());

        foreach (app(Schedule::class)->events() as $event) {
            try {
                $nextRun = $event->nextRunDate(now())->timezone(config('app.timezone'));
            } catch (\Throwable $exception) {
                $nextRun = null;
            }

            $environmentAllowed = empty($event->environments)
                || in_array(app()->environment(), $event->environments, true);
            $commandName = $this->artisanCommandName((string) ($event->command ?? ''));
            $commandExists = $commandName === null || in_array($commandName, $availableCommands, true);
            $name = $this->jobName($event);
            $audit = $this->auditFor($commandName ?: $name);

            $jobs[] = [
                'name' => $name,
                'command' => $this->cleanCommand((string) ($event->command ?? 'Callback')),
                'command_name' => $commandName,
                'command_exists' => $commandExists,
                'expression' => (string) $event->expression,
                'next_run_at' => $nextRun,
                'enabled' => $schedulerActive && $environmentAllowed && $commandExists,
                'configured' => $environmentAllowed && $commandExists,
                'background' => (bool) $event->runInBackground,
                'without_overlapping' => (bool) $event->withoutOverlapping,
                'output' => $event->output !== '/dev/null' ? $event->output : null,
                'purpose' => $audit['purpose'],
                'data_effect' => $audit['data_effect'],
                'deletion' => $audit['deletion'],
                'risk' => $commandExists ? $audit['risk'] : 'invalid',
                'risk_label' => $commandExists ? $audit['risk_label'] : 'أمر غير موجود',
                'safety_note' => $commandExists ? $audit['safety_note'] : 'لن تعمل المهمة لأن الأمر غير مسجل في Artisan. لا تحذف بيانات، لكنها ستسجل خطأ عند التنفيذ.',
            ];
        }

        usort($jobs, function (array $a, array $b): int {
            if ($a['name'] === 'scheduler-heartbeat') {
                return -1;
            }
            if ($b['name'] === 'scheduler-heartbeat') {
                return 1;
            }

            return ($a['next_run_at']?->getTimestamp() ?? PHP_INT_MAX)
                <=> ($b['next_run_at']?->getTimestamp() ?? PHP_INT_MAX);
        });

        return $jobs;
    }

    private function jobName($event): string
    {
        if (filled($event->description ?? null)) {
            return (string) $event->description;
        }

        $command = $this->cleanCommand((string) ($event->command ?? 'Callback'));
        $commandName = $this->artisanCommandName($command);
        if ($commandName !== null) {
            return $commandName;
        }

        return Str::limit($event->getSummaryForDisplay(), 80, '…');
    }

    private function cleanCommand(string $command): string
    {
        $command = str_replace([base_path() . '/', base_path()], '', $command);
        $command = preg_replace('/\s+>\s*[^\s]+\s+2>&1\s*&?$/', '', $command) ?: $command;

        return Str::limit(trim($command), 220, '…');
    }

    private function artisanCommandName(string $command): ?string
    {
        if (preg_match('/[\'\"]?artisan[\'\"]?\s+[\'\"]?([^\s\'\"]+)/', $command, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function auditFor(string $job): array
    {
        $catalog = [
            'scheduler-heartbeat' => [
                'purpose' => 'يثبت أن Laravel Scheduler يعمل فعليًا كل دقيقة.',
                'data_effect' => 'يحدّث ملف حالة صغير داخل storage/framework فقط.',
                'deletion' => 'لا يحذف بيانات.',
                'risk' => 'low', 'risk_label' => 'منخفض',
                'safety_note' => 'لا يقرأ أو يغيّر جداول قاعدة البيانات.',
            ],
            'contracts:update-warranty-status' => [
                'purpose' => 'إعادة حساب بداية ونهاية وحالة ضمان عقود الاستقدام بعد الوصول.',
                'data_effect' => 'يحدّث حقول الضمان وقد ينقل العقد بين الوصول والمكتمل حسب انتهاء الضمان.',
                'deletion' => 'لا يحذف عقودًا أو عملاء.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'محصور في عقود recruitment وmaid بحالة الوصول أو المكتمل.',
            ],
            'contracts:initialize-warranty' => [
                'purpose' => 'تهيئة وتصحيح حقول الضمان للعقود الجديدة أو غير المهيأة.',
                'data_effect' => 'نفس حساب الضمان؛ يكتب الحالة والتواريخ عند اختلافها فقط.',
                'deletion' => 'لا يحذف بيانات.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'العملية قابلة للتكرار، لكنها تتداخل وظيفيًا مع مهمة تحديث الضمان اليومية.',
            ],
            'update-orders-timer' => [
                'purpose' => 'رصد الطلبات المتأخرة والعاجلة لإظهارها في السجلات.',
                'data_effect' => 'قراءة فقط؛ يكتب رسائل في ملفات Log.',
                'deletion' => 'لا يحذف ولا يغيّر الطلبات.',
                'risk' => 'low', 'risk_label' => 'منخفض',
                'safety_note' => 'لا ينفذ إلغاء أو تغيير حالة.',
            ],
            'check-old-orders' => [
                'purpose' => 'عدّ الطلبات المعلقة القديمة والتنبيه عنها شهريًا.',
                'data_effect' => 'قراءة فقط مع كتابة تحذير في Log.',
                'deletion' => 'لا يحذف ولا يغيّر الطلبات.',
                'risk' => 'low', 'risk_label' => 'منخفض',
                'safety_note' => 'مهمة رقابية فقط.',
            ],
            'update-maids-availability' => [
                'purpose' => 'إعادة العاملات المحجوزات إلى متاحات عندما لا يوجد عقد نشط مرتبط بهن.',
                'data_effect' => 'يغيّر حقل حالة العاملة إلى available.',
                'deletion' => 'لا يحذف عاملات أو عقودًا.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'يعتمد على عدم وجود عقد بحالة نشطة؛ يستحسن مراقبة نتائج أول تشغيل بالإنتاج.',
            ],
            'log:clear' => [
                'purpose' => 'كان مقصودًا تنظيف ملفات السجلات الأقدم من مدة الاحتفاظ.',
                'data_effect' => 'المفترض حذف ملفات Log قديمة فقط، وليس جداول قاعدة البيانات.',
                'deletion' => 'قد يحذف ملفات سجلات قديمة لو كان الأمر موجودًا.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'الأمر غير موجود حاليًا في المشروع ولذلك لن ينفذ الحذف وسيظهر كخطأ.',
            ],
            'optimize-database' => [
                'purpose' => 'تشغيل OPTIMIZE على جداول العقود والطلبات والعاملات والمستخدمين أسبوعيًا.',
                'data_effect' => 'يعيد تنظيم تخزين وفهارس الجداول وقد يقفلها مؤقتًا.',
                'deletion' => 'لا يحذف صفوف الأعمال.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'يفضل وقت هدوء لأن MySQL قد يقفل الجداول أثناء التحسين.',
            ],
            'orders:cancel-expired' => [
                'purpose' => 'إلغاء الطلبات الجديدة التي تجاوزت deadline_at دون تأكيد.',
                'data_effect' => 'يغيّر الطلب إلى مرتجع، يحرر العاملة، ويسجل سببًا وإشعارًا للعميل.',
                'deletion' => 'لا يحذف الطلب أو العاملة.',
                'risk' => 'high', 'risk_label' => 'مرتفع',
                'safety_note' => 'قرار أعمال تلقائي. يتقاطع مع orders:handle-expired ويجب توحيد قاعدة الانتهاء مستقبلًا.',
            ],
            'orders:handle-expired' => [
                'purpose' => 'رفض الطلبات الجديدة التي تجاوزت expiration_time وإبلاغ العميل.',
                'data_effect' => 'يغيّر الحالة إلى مرفوض، ينشئ إشعارًا، وقد يرسل SMS.',
                'deletion' => 'لا يحذف الطلب.',
                'risk' => 'high', 'risk_label' => 'مرتفع',
                'safety_note' => 'يتقاطع مع orders:cancel-expired لكن يستخدم حقل انتهاء مختلفًا؛ يحتاج مراقبة لتجنب قرارات متعارضة.',
            ],
            'rental-contracts:send-expiry-reminders' => [
                'purpose' => 'تنبيه عملاء عقود الإيجار قبل تاريخ الانتهاء.',
                'data_effect' => 'ينشئ سجل تذكير ويرسل SMS أو WhatsApp عند تفعيل القنوات، مع منع التكرار لنفس الانتهاء.',
                'deletion' => 'لا يحذف ولا يغلق العقود.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'قد يستهلك رصيد الرسائل؛ لا يطبق قرار الإرجاع أو التمديد إلا بعد تفاعل العميل.',
            ],
            'notifications:run-configured-alerts' => [
                'purpose' => 'تشغيل قواعد التنبيهات المفعلة بالمواعيد والقنوات المحددة.',
                'data_effect' => 'يرسل إشعارات/SMS/WhatsApp/بريد ويسجل نتائج التسليم لمنع التكرار.',
                'deletion' => 'لا يحذف بيانات الأعمال.',
                'risk' => 'medium', 'risk_label' => 'متوسط',
                'safety_note' => 'ينفذ القواعد المفعلة فقط وبحد أقصى 500 مرشح لكل نوع في الدورة.',
            ],
            'system-updates:sync' => [
                'purpose' => 'سحب تحديثات النظام من مصدر التحديثات المضبوط في Super Admin.',
                'data_effect' => 'يضيف أو يحدّث صفوف system_updates القادمة من المصدر فقط ووسائطها كروابط.',
                'deletion' => 'يحذف التحديثات المسحوبة التي حذفها المصدر أو ألغى نشرها؛ لا يلمس ما كُتب في هذه النسخة.',
                'risk' => 'low', 'risk_label' => 'منخفض',
                'safety_note' => 'لا يعمل إلا إذا فُعّلت المزامنة وضُبط الرابط والمفتاح.',
            ],
            'database:backup-r2' => [
                'purpose' => 'إنشاء نسخة MySQL يومية مضغوطة في R2 وعلى السيرفر.',
                'data_effect' => 'يقرأ قاعدة البيانات ويكتب ملفات نسخ احتياطية.',
                'deletion' => 'يحذف ملفات النسخ الأقدم من 30 يومًا في R2 و7 أيام على السيرفر فقط.',
                'risk' => 'low', 'risk_label' => 'منخفض',
                'safety_note' => 'لا يحذف جداول أو صفوفًا من قاعدة البيانات؛ الحذف محصور بملفات النسخ القديمة المطابقة لنمط النظام.',
            ],
        ];

        return $catalog[$job] ?? [
            'purpose' => 'لا يوجد وصف أمان مخصص لهذه المهمة بعد.',
            'data_effect' => 'راجع تنفيذ الأمر قبل تفعيله في الإنتاج.',
            'deletion' => 'غير محدد.',
            'risk' => 'unknown', 'risk_label' => 'غير مصنف',
            'safety_note' => 'مهمة غير معروفة لمراقب Scheduler.',
        ];
    }

    private function heartbeat(): array
    {
        $path = storage_path(self::HEARTBEAT_FILE);
        $timestamp = is_file($path) ? (int) @filemtime($path) : 0;
        $age = $timestamp > 0 ? max(0, time() - $timestamp) : null;

        return [
            'active' => $age !== null && $age <= 180,
            'last_heartbeat_at' => $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : null,
            'age_seconds' => $age,
        ];
    }

    private function crontabStatus(): array
    {
        try {
            $result = $this->readCrontab();
            $output = $result['contents'];

            $entries = collect(preg_split('/\R/', $output) ?: [])
                ->map(fn (string $line) => trim($line))
                ->filter(fn (string $line) => $line !== '' && !Str::startsWith($line, '#'))
                ->filter(fn (string $line) => Str::contains($line, 'schedule:run')
                    && Str::contains($line, base_path())
                    && Str::contains($line, 'artisan'))
                ->values()
                ->all();

            return [
                'configured' => collect($entries)->contains(fn (string $line) => $line === $this->cronEntry()),
                'entries' => $entries,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return ['configured' => null, 'entries' => [], 'error' => $exception->getMessage()];
        }
    }

    private function installCrontabEntry(): bool
    {
        $result = $this->readCrontab();
        $contents = rtrim($result['contents']);
        $entry = $this->cronEntry();
        $lines = collect(preg_split('/\R/', $contents) ?: []);
        $marker = self::CRON_MARKER . ': ' . substr(sha1(base_path()), 0, 12);
        $projectEntry = fn (string $line): bool => Str::contains($line, 'schedule:run')
            && Str::contains($line, base_path());

        if ($lines->contains(fn (string $line) => trim($line) === $entry)) {
            return false;
        }

        // Upgrade a legacy unbounded entry for this project instead of adding a duplicate.
        $retainedContents = $lines
            ->reject(fn (string $line) => trim($line) === $marker || $projectEntry(trim($line)))
            ->implode(PHP_EOL);
        $newContents = (trim($retainedContents) !== '' ? rtrim($retainedContents) . PHP_EOL . PHP_EOL : '')
            . $marker . PHP_EOL
            . $entry . PHP_EOL;

        $process = new Process(['crontab', '-']);
        $process->setTimeout(10);
        $process->setInput($newContents);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('تعذر تثبيت Crontab: ' . trim($process->getErrorOutput()));
        }

        $verified = $this->readCrontab()['contents'];
        if (!Str::contains($verified, $marker) || !Str::contains($verified, $entry)) {
            throw new \RuntimeException('تم إرسال إعداد Crontab لكن تعذر التحقق من حفظه.');
        }

        return true;
    }

    private function runNow(): void
    {
        $process = new Process([
            $this->phpBinary(),
            '-d',
            'memory_limit=' . $this->memoryLimit(),
            base_path('artisan'),
            'schedule:run',
        ], base_path(), ['CLICKERP_SCHEDULER_RUN' => '1']);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('تم إعداد Cron لكن فشل التشغيل الفوري: ' . trim($process->getErrorOutput()));
        }
    }

    private function readCrontab(): array
    {
        $process = new Process(['crontab', '-l']);
        $process->setTimeout(5);
        $process->run();

        $error = trim($process->getErrorOutput());
        if (!$process->isSuccessful() && !Str::contains(Str::lower($error), 'no crontab')) {
            throw new \RuntimeException($error ?: 'تعذر قراءة Crontab للمستخدم الحالي.');
        }

        return ['contents' => $process->isSuccessful() ? $process->getOutput() : ''];
    }

    private function phpBinary(): string
    {
        $configured = trim((string) config('scheduler.php_binary', ''));
        $candidates = array_filter([
            $configured,
            in_array(PHP_SAPI, ['cli', 'cli-server'], true) ? PHP_BINARY : null,
            '/Applications/MAMP/bin/php/php8.3.30/bin/php',
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
        ]);

        foreach ($candidates as $candidate) {
            // FPM/CGI binaries cannot run Artisan, including versioned names.
            if (preg_match('/(?:fpm|cgi)/i', basename($candidate))) {
                continue;
            }

            // Hosting open_basedir restrictions can deny metadata probes.
            // Treat inaccessible candidates as unavailable, not as page errors.
            if (@is_file($candidate) && @is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('لم يتم العثور على PHP CLI قابل للفحص والتنفيذ. عيّن SCHEDULER_PHP_BINARY في ملف البيئة لمسار PHP CLI وتأكد أن open_basedir يسمح بفحصه.');
    }

    private function cronEntry(): string
    {
        return sprintf(
            '* * * * * cd %s && sleep %d && flock -n %s env CLICKERP_SCHEDULER_RUN=1 %s -d memory_limit=%s artisan schedule:run >> /dev/null 2>&1',
            escapeshellarg(base_path()),
            $this->delaySeconds(),
            escapeshellarg(storage_path('framework/scheduler-run.lock')),
            escapeshellarg($this->phpBinary()),
            escapeshellarg($this->memoryLimit())
        );
    }

    private function memoryLimit(): string
    {
        $limit = trim((string) config('scheduler.memory_limit', '192M'));

        return preg_match('/^[1-9][0-9]*[KMG]?$/i', $limit) ? strtoupper($limit) : '192M';
    }

    private function delaySeconds(): int
    {
        return max(0, min(50, (int) config('scheduler.delay_seconds', 0)));
    }
}
