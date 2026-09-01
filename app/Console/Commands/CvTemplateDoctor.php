<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use App\Services\ingaz\MaidCvPdfGenerator;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Console\Command;

/**
 * تشخيص سلسلة توليد نموذج السيرة الذاتية.
 *
 * سبب وجوده: عند غياب Chrome يسقط التوليد بصمت إلى SVG يحتوي HTML داخل
 * <foreignObject>، ثم يعجز mPDF عن رسمه عند التنزيل فيخرج PDF مشوّه دون أي
 * خطأ ظاهر. هذا الأمر يكشف الحلقة المكسورة مباشرة بدل تتبّع السجلات.
 */
class CvTemplateDoctor extends Command
{
    protected $signature = 'cv:doctor
                            {--maid= : معرّف عاملة لتجربة توليد فعلي عليها}';

    protected $description = 'فحص إعدادات توليد نماذج السير الذاتية والتحقق من توفر المتصفح';

    public function handle(MaidCvPdfGenerator $generator): int
    {
        $healthy = true;

        $this->line('');
        $this->info('== إعدادات القالب ==');

        $settings = MaidCvTemplateSettings::current();
        $templateLength = strlen((string) ($settings->cv_template_blade ?? ''));

        $healthy = $this->check('القالب مفعّل', MaidCvTemplateSettings::enabled()) && $healthy;
        $this->row('نوع المخرَج', MaidCvTemplateSettings::outputType());
        $this->row('حجم القالب', $templateLength . ' حرف');

        $this->line('');
        $this->info('== المتصفح ==');

        $procOpen = function_exists('proc_open');
        $healthy = $this->check('proc_open مفعّلة', $procOpen, 'أزل proc_open من disable_functions في php.ini') && $healthy;

        $this->row('المسار المضبوط', config('services.chrome.path') ?: '(غير مضبوط — يعتمد على البحث التلقائي)');

        $browserPath = $generator->browserExecutable();
        $healthy = $this->check(
            'تم العثور على المتصفح',
            $browserPath !== null,
            'ثبّت Chromium ثم اضبط CV_PDF_BROWSER_PATH في .env'
        ) && $healthy;

        if ($browserPath !== null) {
            $this->row('المسار المكتشف', $browserPath);
        }

        $this->row(
            'مقاس العرض',
            config('services.chrome.viewport_width', 794) . 'x' . config('services.chrome.viewport_height', 1123)
        );

        $this->line('');
        $this->info('== النماذج المخزّنة ==');
        $this->reportStoredPreviews();

        $maidId = $this->option('maid');
        if ($maidId) {
            $this->line('');
            $this->info('== تجربة توليد فعلية ==');
            $healthy = $this->trialRun($generator, (int) $maidId) && $healthy;
        }

        $this->line('');

        if ($healthy) {
            $this->info('كل الفحوصات سليمة.');

            return self::SUCCESS;
        }

        $this->error('توجد مشاكل — راجع الأسطر المعلَّمة بـ [x] أعلاه.');

        return self::FAILURE;
    }

    /**
     * امتداد الملف المخزّن هو أوضح مؤشر: svg يعني أن Chrome فشل وقت التوليد،
     * وأن أي تنزيل PDF لاحق سيمرّ عبر mPDF ويخرج مشوّهاً.
     */
    private function reportStoredPreviews(): void
    {
        $rows = Maid::query()
            ->whereNotNull('cv_preview')
            ->where('cv_preview', '!=', '')
            ->where('cv_preview', 'like', '%' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '%')
            ->selectRaw("LOWER(SUBSTRING_INDEX(cv_preview, '.', -1)) AS ext, COUNT(*) AS total")
            ->groupBy('ext')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            $this->row('نماذج منشأة', 'لا يوجد');

            return;
        }

        foreach ($rows as $row) {
            $this->row('.' . $row->ext, (string) $row->total);
        }

        $svgCount = (int) ($rows->firstWhere('ext', 'svg')->total ?? 0);

        if ($svgCount > 0) {
            $this->warn("  [!] {$svgCount} نموذج محفوظ كـ SVG احتياطي — أُنشئت بدون متصفح.");
            $this->line('      تنزيلها كـ PDF سيخرج مشوّهاً. أعد إنشاءها بعد تثبيت Chromium.');
        }
    }

    private function trialRun(MaidCvPdfGenerator $generator, int $maidId): bool
    {
        $maid = Maid::find($maidId);

        if (!$maid) {
            $this->error("  [x] لا توجد عاملة بالمعرّف {$maidId}");

            return false;
        }

        $startedAt = microtime(true);

        // نجرّب لقطة المتصفح تحديداً — وهي الخطوة التي تفشل في الإنتاج. تجربة
        // مسار mPDF بدلاً منها كانت ستنجح دائماً وتخفي المشكلة الحقيقية.
        $result = $generator->probeBrowserRendering($maid);
        $duration = round(microtime(true) - $startedAt, 1);

        $this->row('المدة', $duration . ' ثانية');

        if (!$result['ok']) {
            $this->error('  [x] فشلت لقطة المتصفح: ' . ($result['error'] ?? 'سبب غير معروف'));
            $this->line('      عند هذا الفشل يُحفظ النموذج كـ SVG ويخرج تنزيل PDF مشوّهاً.');

            return false;
        }

        $this->row('حجم الصورة', number_format($result['bytes']) . ' بايت');
        $this->row('الأبعاد', $result['width'] . 'x' . $result['height']);
        $this->info('  [v] نجحت لقطة المتصفح — التوليد سينتج PNG سليمة.');

        return true;
    }

    private function check(string $label, bool $passed, string $hint = ''): bool
    {
        if ($passed) {
            $this->line("  <fg=green>[v]</> {$label}");

            return true;
        }

        $this->line("  <fg=red>[x]</> {$label}");

        if ($hint !== '') {
            $this->line("      <fg=yellow>{$hint}</>");
        }

        return false;
    }

    private function row(string $label, string $value): void
    {
        $this->line("      {$label}: <fg=cyan>{$value}</>");
    }
}
