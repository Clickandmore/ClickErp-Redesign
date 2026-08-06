<?php

namespace App\Console\Commands;

use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * ترحيل الملفات المرفوعة قبل تفعيل R2 من التخزين المحلي إلى الـ Bucket.
 *
 * تفعيل R2 يؤثر على الرفع الجديد فقط، فتبقى الملفات القديمة (اللوجو، أعلام الجنسيات،
 * السلايدر...) بمسارات محلية وتُخدم من سيرفر الـ ERP. هذا الأمر ينسخها إلى R2 ويحدّث
 * قيمة العمود إلى "r2://..." فتُخدم من R2 مثل بقية الملفات.
 *
 * الملف المحلي لا يُحذف — يبقى للتراجع وللنسخ الاحتياطي.
 */
class MigrateStorageToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2
                            {--dry-run : عرض ما سيحدث دون رفع أو تعديل أي شيء}
                            {--only= : حصر العملية بجدول واحد}
                            {--all : مسح كل أعمدة النصوص في القاعدة بدل القائمة المعتمدة}
                            {--chunk=200 : عدد الصفوف في كل دفعة}';

    protected $description = 'نقل الملفات المحلية القديمة إلى Cloudflare R2 وتحديث مساراتها في قاعدة البيانات';

    /**
     * الأعمدة المعتمدة: ملفات الموقع العام ولوحة التحكم التي تُعرض عبر الواجهات.
     * الأعمدة التي تحمل كلاس أيقونة (steps.icon, why_us.icon, statistics.image) مستثناة عمدًا.
     *
     * @var array<string, array<int, string>>
     */
    private const MEDIA_COLUMNS = [
        'settings' => [
            'logo_header', 'logo_footer', 'favicon', 'cover_image', 'about_image',
            'experience_image', 'qr_image', 'second_qr_image', 'zatch_logo',
            'light_image', 'admin_logo', 'document_logo',
        ],
        'nationalities' => ['logo', 'image'],
        'maids' => ['image', 'cv_file', 'cv_preview', 'video', 'personal_image', 'new_image'],
        'maid_attachments' => ['path'],
        'sliders' => ['image'],
        'services' => ['image'],
        'clients' => ['image'],
        'what_dos' => ['image'],
        'abouts' => ['image', 'image_vision', 'image_message', 'image_value'],
        'about_us' => ['image', 'image_vision', 'image_message', 'image_value'],
        'features' => ['image_file'],
        'offers' => ['image'],
        'job_titles' => ['image'],
        'job_types' => ['image'],
        'testimonials' => ['image'],
        'requirements' => ['image'],
        'select_settings' => ['image'],
        'social_status' => ['image'],
        'home_hero_images' => ['path'],
        'home_hero_sections' => ['video_path'],
        'why_choose_us_images' => ['path'],
        'guideline_sections' => ['client_file_path', 'worker_file_path'],
        'nationality_guideline_versions' => ['client_file_path', 'worker_file_path'],
        'police_settings' => ['file_employee_rights', 'file_workers_rights'],
        'print_setting' => ['logo'],
        'users' => ['avatar'],
    ];

    public function handle(ManagedFileStorage $storage): int
    {
        if (!R2StorageSettings::isReady()) {
            $this->error('تخزين R2 غير جاهز. فعّله وشغّل «اختبار الاتصال» من /admin/storage-settings أولًا.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $settings = R2StorageSettings::all();

        $this->line('الـ Bucket: <info>' . $settings['bucket'] . '</info>   بادئة المسار: <info>' . ($settings['root_prefix'] ?: '—') . '</info>');

        $this->line('الملفات الموجودة في الـ Bucket أصلًا (رفع يدوي) تُربط فقط دون إعادة رفع.');
        $this->newLine();

        if ($dryRun) {
            $this->warn('وضع المعاينة (--dry-run): لن يُرفع أو يُعدَّل أي شيء.');
        } elseif (!$this->confirm('سيتم تحديث مسارات الملفات في قاعدة البيانات. هل أخذت نسخة احتياطية (mysqldump)؟')) {
            $this->line('أُلغيت العملية.');

            return self::SUCCESS;
        }

        $totals = [
            'relinked' => 0, 'uploaded' => 0, 'already' => 0,
            'missing' => 0, 'skipped' => 0, 'failed' => 0,
        ];

        foreach ($this->targets() as $table => $columns) {
            $this->migrateTable($storage, $table, $columns, $dryRun, $totals);
        }

        $this->newLine();
        $this->table(['النتيجة', 'العدد'], [
            ['موجود على R2 — ' . ($dryRun ? 'سيُربط' : 'رُبط'), $totals['relinked']],
            ['غير موجود — ' . ($dryRun ? 'سيُرفع' : 'رُفع'), $totals['uploaded']],
            ['مربوط بـ r2:// مسبقًا', $totals['already']],
            ['مفقود من R2 ومن القرص', $totals['missing']],
            ['متجاهَل (رابط/أيقونة)', $totals['skipped']],
            ['فشل', $totals['failed']],
        ]);

        if ($totals['missing'] > 0) {
            $this->warn('صفوف تشير إلى ملفات غير موجودة لا على R2 ولا على القرص — بقيت كما هي دون تغيير.');
        }

        if ($totals['failed'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function targets(): array
    {
        $map = $this->option('all') ? $this->discoverColumns() : self::MEDIA_COLUMNS;

        if ($only = $this->option('only')) {
            $map = array_intersect_key($map, [$only => true]);

            if ($map === []) {
                $this->warn('الجدول «' . $only . '» ليس ضمن الأعمدة المعتمدة. جرّب --all.');
            }
        }

        $available = [];

        foreach ($map as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($existing !== [] && Schema::hasColumn($table, 'id')) {
                $available[$table] = $existing;
            }
        }

        return $available;
    }

    /**
     * مسح المخطط بحثًا عن أي عمود نصي قد يحمل مسار ملف. معيار القبول الحقيقي يبقى
     * وجود الملف فعليًا على القرص المحلي، لذا لا ضرر من التوسع هنا.
     *
     * @return array<string, array<int, string>>
     */
    private function discoverColumns(): array
    {
        $rows = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
               AND COLUMN_NAME REGEXP 'image|logo|photo|file|attachment|avatar|video|receipt|path|qr|icon'
             ORDER BY TABLE_NAME, COLUMN_NAME",
            [DB::connection()->getDatabaseName()]
        );

        $map = [];

        foreach ($rows as $row) {
            $map[$row->t][] = $row->c;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, int>  $totals
     */
    private function migrateTable(
        ManagedFileStorage $storage,
        string $table,
        array $columns,
        bool $dryRun,
        array &$totals
    ): void {
        $local = Storage::disk('public');
        $touched = 0;

        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->where(function ($query) use ($columns): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, '!=', '');
                }
            })
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($rows) use (
                $storage, $local, $table, $columns, $dryRun, &$totals, &$touched
            ): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if (!$this->looksLikeStoredPath($value)) {
                            if (filled($value) && $storage->isR2Path((string) $value)) {
                                $totals['already']++;
                            } elseif (filled($value)) {
                                $totals['skipped']++;
                            }

                            continue;
                        }

                        // الملف قد يكون مرفوعًا إلى الـ Bucket يدويًا — عندها نربط فقط دون رفع.
                        $pointer = $storage->existingR2Pointer($value);
                        $onDisk = $local->exists($value);

                        if ($pointer === null && !$onDisk) {
                            $totals['missing']++;
                            $this->line("  <comment>مفقود</comment> {$table}#{$row->id}.{$column} → {$value}");

                            continue;
                        }

                        $action = $pointer !== null ? 'relinked' : 'uploaded';

                        if ($dryRun) {
                            $totals[$action]++;
                            $label = $pointer !== null ? '<info>سيُربط</info>' : '<comment>سيُرفع</comment>';
                            $key = $storage->expectedR2Key($value);
                            $this->line("  {$label} {$table}#{$row->id}.{$column}  المفتاح المتوقع: {$key}");

                            continue;
                        }

                        try {
                            $updates[$column] = $pointer ?? $storage->migrateLocalFileToR2($value);
                            $totals[$action]++;
                        } catch (\Throwable $exception) {
                            $totals['failed']++;
                            $this->error("  فشل {$table}#{$row->id}.{$column}: " . $exception->getMessage());
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                        $touched++;
                    }
                }
            });

        if ($touched > 0 || $dryRun) {
            $this->line('<info>' . $table . '</info> — ' . implode(', ', $columns));
        }
    }

    /**
     * القيمة تصلح للترحيل فقط إن كانت مسارًا نسبيًا لملف مخزَّن محليًا:
     * لا مؤشر R2، ولا رابط خارجي، ولا كلاس أيقونة FontAwesome، ولا JSON.
     */
    private function looksLikeStoredPath($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if ($value === '' || strtolower($value) === 'null') {
            return false;
        }

        if (str_starts_with($value, ManagedFileStorage::R2_PREFIX)) {
            return false;
        }

        // روابط خارجية، ومسارات مطلقة، وقيم JSON (أعمدة المرفقات المتعددة)
        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, '[') || str_starts_with($value, '{')) {
            return false;
        }

        // كلاسات الأيقونات: "fa fa-users" — المسارات لا تحوي مسافات
        if (preg_match('/\s/', $value)) {
            return false;
        }

        // لا بد من امتداد ملف ومن مجلد — يستبعد القيم النصية العادية
        return str_contains($value, '/') && (bool) preg_match('/\.[A-Za-z0-9]{2,5}$/', $value);
    }
}
