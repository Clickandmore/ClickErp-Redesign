<?php

namespace App\Console\Commands;

use App\Services\ingaz\DataImportExecutionService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Support\AccountCodeAliases;
use Nami\Finance\Support\ChartSettings;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

/**
 * بداية مالية جديدة لتوسط: يحذف كل البيانات المالية (القيود، السندات، الفواتير، مراكز التكلفة، شجرة الحسابات …)
 * ثم يرفع دليل المحاسب من ملف الرفع المصحح بنفس منطق شاشة استيراد الحسابات.
 * العقود والعملاء والوكلاء تبقى، ويُفرَّغ ربطها بالحسابات والقيود المحذوفة.
 *
 * بلا --commit: معاينة فقط. التنفيذ يحتاج اسم القاعدة وعبارة التأكيد، ويكتب نسخة احتياطية JSON قبل الحذف.
 * خذ mysqldump أيضاً قبل التنفيذ.
 */
class TawsstFinanceRestart extends Command
{
    private const CONFIRMATION = 'TAWSST-FINANCE-RESTART';

    /** جداول fn_ التي تبقى (بيانات أساسية وإعدادات). كل جدول fn_ غيرها يُفرَّغ. */
    private const KEEP_FN_TABLES = [
        'fn_years', 'fn_confirm_settings', 'fn_tax_settings', 'fn_product_num_settings', 'fn_stock_settings',
        'fn_items', 'fn_item_taxs', 'fn_item_components', 'fn_units', 'fn_categories', 'fn_stocks', 'fn_principle_websites',
        'fn_clients', 'fn_client_attachments', 'fn_client_categories', 'fn_supplier_types', 'fn_boxes',
    ];

    /**
     * أعمدة حساب إلزامية في جداول باقية: تُنقل إلى الحساب المقابل في الدليل الجديد (كود قديم => كود جديد)
     * حسب ورقة المقارنة. أي قيمة بلا مقابل هنا توقف التنفيذ.
     */
    private const REMAP_COLUMNS = ['fn_tax_settings.account_id', 'fn_items.sale_account_id', 'fn_items.stock_account_id'];
    private const REMAP_CODES = [
        '22200' => '25006',   // ضريبة الإيرادات ← ضريبة القيمة المضافة على المخرجات
        '59' => '25006',      // إعداد «ضريبة القيمة المضافة» العام كان على «تكاليف مبيعات التأجير» خطأً
        '15600' => '1203001', // ضريبة المصروفات ← ضريبة القيمة المضافة على المدخلات
        '411' => '411',       // خدمة فاتورة إنجاز
        '517' => '3103',      // تكلفة استقدام مكتب خارجي (كان على تكلفة مبيعات كينيا)
    ];

    /** مستندات مالية خارج fn_ تُحذف كاملة. */
    private const DELETE_TABLES = [
        'invoices', 'invoice_drafts', 'recruitment_invoice_credit_notes', 'recruitment_contract_refunds',
        'rental_contract_invoices', 'rental_financial_adjustments',
        'contract_return_settlements', 'contract_return_settlement_lines',
        'musaned_transfers', 'musaned_transfer_items',
        'shelter_cost_entries', 'shelter_living_expenses', 'shelter_living_expense_allocations', 'shelter_agent_cost_recoveries',
        'commission_batches', 'commission_batch_items', 'commission_balance_transactions', 'commission_payments',
        'branch_finance_accounts', 'legacy_finance_bond_source_rows', 'sales_attachments',
    ];

    /** أعمدة الربط بالبيانات المالية: تُفرَّغ في الجداول الباقية. */
    private const LINK_COLUMN_PATTERN = '(account_id|constraint_id|cost_center_id|bond_id|invoice_id|sale_id|purchase_id|settlement_id|musaned_transfer_id|refund_id|credit_note_id|commission_batch_id)$';

    /** أعمدة تطابق النمط وليست ربطاً بالبيانات المالية. */
    private const NOT_LINKS = [
        'bank_account_translations.bank_account_id', 'whatsapp_conversations.channel_account_id', 'whatsapp_messages.channel_account_id',
        'rental_order_payments.provider_invoice_id', 'sponsorship_transfers.moyasar_invoice_id',
    ];

    protected $signature = 'finance:tawsst-restart
        {file=migration_system/data/tawsst/finance/دليل توسط - ملف الرفع المصحح.xlsx : ملف الرفع المصحح (ورقة البيانات)}
        {--database= : اسم قاعدة البيانات المقصودة؛ يجب أن يطابق القاعدة الحالية}
        {--commit : تنفيذ؛ بدونه معاينة فقط}
        {--confirm= : عبارة التأكيد للتنفيذ}';

    protected $description = 'حذف كل البيانات المالية لتوسط ورفع دليل المحاسب من جديد (معاينة افتراضياً)';

    public function handle(DataImportExecutionService $importer): int
    {
        try {
            $database = $this->guardDatabase();
            $path = $this->absolutePath((string) $this->argument('file'));
            $chart = $this->readChart($path);
            $plan = $this->plan();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->showPlan($database, $path, $chart, $plan);

        if ($plan['blockers']) {
            $this->error('لا يمكن التنفيذ:');
            foreach ($plan['blockers'] as $blocker) {
                $this->line(' - ' . $blocker);
            }

            return self::FAILURE;
        }

        if (!$this->option('commit')) {
            $this->warn('معاينة فقط؛ لم يتغير شيء. للتنفيذ: --commit --database=' . $database . ' --confirm=' . self::CONFIRMATION);

            return self::SUCCESS;
        }

        if ($this->option('confirm') !== self::CONFIRMATION) {
            $this->error('عبارة التأكيد غير صحيحة. المطلوب: --confirm=' . self::CONFIRMATION);

            return self::FAILURE;
        }

        try {
            $backup = $this->backup($plan);
            $this->info('النسخة الاحتياطية: storage/app/' . $backup);
            $result = $this->wipeAndImport($plan, $path, $chart, $importer);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->error('لم يُحفظ أي تعديل.');

            return self::FAILURE;
        } finally {
            AccountCodeAliases::reset();
            ChartSettings::reset();
        }

        $this->info("تم. حُذف {$result['deleted']} صفاً، وفُرّغ {$result['nulled']} ربطاً، ورُفع {$result['accounts']} حساباً.");
        $this->warn('نفّذ الآن: php artisan optimize:clear وأعد تشغيل queue workers.');

        return self::SUCCESS;
    }

    private function guardDatabase(): string
    {
        $actual = DB::getDatabaseName();
        $expected = trim((string) $this->option('database'));
        if ($expected === '' || $expected !== $actual) {
            throw new RuntimeException("حدد القاعدة بـ --database. القاعدة الحالية: {$actual}");
        }
        if (!Schema::hasTable('fn_accounts')) {
            throw new RuntimeException('جدول fn_accounts غير موجود.');
        }

        return $actual;
    }

    private function absolutePath(string $path): string
    {
        $path = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
        if (!is_file($path)) {
            throw new RuntimeException('ملف الدليل غير موجود: ' . $path);
        }

        return $path;
    }

    /** يقرأ الدليل ويتأكد أن لكل حساب أباً داخل الملف نفسه، بنفس طريقة الشاشة (اسم الأصل ثم بادئة الكود). */
    private function readChart(string $path): array
    {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('البيانات') ?? throw new RuntimeException('ورقة «البيانات» غير موجودة في الملف.');
        $rows = [];
        foreach ($sheet->toArray(null, true, false, false) as $i => $r) {
            if ($i === 0 || trim((string) ($r[1] ?? '')) === '') {
                continue;
            }
            $code = preg_replace('/\D+/', '', (string) $r[1]);
            if (isset($rows[$code])) {
                throw new RuntimeException("الكود {$code} مكرر في الملف.");
            }
            $rows[$code] = ['level' => (int) $r[0], 'name' => trim((string) $r[2]), 'parent_name' => trim((string) ($r[3] ?? '')), 'line' => $i + 1];
        }
        $book->disconnectWorksheets();

        $errors = [];
        foreach ($rows as $code => $row) {
            if ($row['name'] === '' || $row['level'] < 1) {
                $errors[] = "صف {$row['line']} ({$code}): الاسم أو المستوى ناقص.";
                continue;
            }
            if ($row['level'] > 1 && !$this->parentInFile((string) $code, $row, $rows)) {
                $errors[] = "صف {$row['line']} ({$code}): لا يوجد له أب في الملف بالمستوى " . ($row['level'] - 1) . '.';
            }
        }
        if ($errors) {
            throw new RuntimeException("الملف غير صالح:\n" . implode("\n", $errors));
        }

        return $rows;
    }

    private function parentInFile(string $code, array $row, array $rows): ?string
    {
        if ($row['parent_name'] !== '') {
            $matches = array_keys(array_filter($rows, fn ($r) => $r['level'] === $row['level'] - 1 && $r['name'] === $row['parent_name']));
            if (count($matches) === 1) {
                return (string) $matches[0];
            }
        }
        for ($prefix = substr($code, 0, -1); $prefix !== ''; $prefix = substr($prefix, 0, -1)) {
            if (isset($rows[$prefix]) && $rows[$prefix]['level'] === $row['level'] - 1) {
                return $prefix;
            }
        }

        return null;
    }

    private function plan(): array
    {
        $database = DB::getDatabaseName();
        $fnTables = collect(DB::select("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE 'fn\\_%'", [$database]))->pluck('t');
        $deleteTables = $fnTables->reject(fn ($t) => in_array($t, self::KEEP_FN_TABLES, true))
            ->merge(array_filter(self::DELETE_TABLES, fn ($t) => Schema::hasTable($t)))
            ->unique()->sort()->values()->all();

        $deletes = [];
        foreach ($deleteTables as $table) {
            $deletes[$table] = DB::table($table)->count();
        }

        $columns = DB::select(
            "SELECT c.TABLE_NAME AS t, c.COLUMN_NAME AS c, c.IS_NULLABLE AS n FROM information_schema.COLUMNS c
             JOIN information_schema.TABLES tb ON tb.TABLE_SCHEMA = c.TABLE_SCHEMA AND tb.TABLE_NAME = c.TABLE_NAME AND tb.TABLE_TYPE = 'BASE TABLE'
             WHERE c.TABLE_SCHEMA = ? AND c.COLUMN_NAME REGEXP ? ORDER BY c.TABLE_NAME, c.COLUMN_NAME",
            [$database, self::LINK_COLUMN_PATTERN]
        );

        $nulls = [];
        $blockers = [];
        $remaps = [];
        $codeById = DB::table('fn_accounts')->pluck('code', 'id')->map(fn ($code) => (string) $code);
        foreach (self::REMAP_COLUMNS as $key) {
            [$table, $column] = explode('.', $key);
            if (in_array($table, $deleteTables, true) || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull($column)->pluck($column, 'id') as $rowId => $accountId) {
                $old = $codeById[$accountId] ?? null;
                if ($old === null || !isset(self::REMAP_CODES[$old])) {
                    $blockers[] = "{$key} (صف {$rowId}): الحساب " . ($old ?? "#{$accountId}") . ' بلا مقابل في REMAP_CODES.';
                    continue;
                }
                $remaps[] = [$table, $column, (int) $rowId, $old, self::REMAP_CODES[$old]];
            }
        }

        foreach ($columns as $col) {
            if (in_array($col->t, $deleteTables, true) || in_array("{$col->t}.{$col->c}", array_merge(self::NOT_LINKS, self::REMAP_COLUMNS), true)) {
                continue;
            }
            $count = DB::table($col->t)->whereNotNull($col->c)->where($col->c, '<>', 0)->count();
            if (!$count) {
                continue;
            }
            if ($col->n === 'NO') {
                $blockers[] = "{$col->t}.{$col->c}: {$count} صفاً مرتبطة ببيانات مالية والعمود لا يقبل الفراغ (مثل فواتير زاتكا أو مدفوعات ميسر). راجعها قبل الحذف.";
                continue;
            }
            $nulls["{$col->t}.{$col->c}"] = $count;
        }

        $settings = DB::table('fn_confirm_settings')
            ->where(fn ($q) => $q->where('title', 'like', '%account%')->orWhere('title', 'like', 'rental\\_maid\\_prepaid\\_%'))
            ->whereNotNull('value')->where('value', '<>', '')
            ->count();

        return compact('deletes', 'nulls', 'remaps', 'settings', 'blockers');
    }

    private function showPlan(string $database, string $path, array $chart, array $plan): void
    {
        $this->table(['البند', 'القيمة'], [
            ['قاعدة البيانات', $database],
            ['ملف الدليل', basename($path)],
            ['حسابات الدليل الجديد', count($chart)],
            ['الحسابات الحالية (تُحذف)', $plan['deletes']['fn_accounts'] ?? 0],
            ['القيود (تُحذف)', $plan['deletes']['fn_constraints'] ?? 0],
            ['مراكز التكلفة (تُحذف)', $plan['deletes']['fn_cost_centers'] ?? 0],
            ['الفواتير (تُحذف)', $plan['deletes']['invoices'] ?? 0],
            ['إعدادات القيود الآلية المربوطة بحساب (تُفرَّغ)', $plan['settings']],
            ['الوضع', $this->option('commit') ? 'تنفيذ' : 'معاينة'],
        ]);
        $this->table(['جدول يُفرَّغ', 'صفوف'], collect($plan['deletes'])->filter()->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->table(['ربط يُفرَّغ (جدول.عمود)', 'صفوف'], collect($plan['nulls'])->map(fn ($n, $c) => [$c, $n])->values()->all());
        $this->table(['يُنقل إلى الدليل الجديد', 'صف', 'الكود القديم', 'الكود الجديد'], array_map(fn ($r) => ["{$r[0]}.{$r[1]}", $r[2], $r[3], $r[4]], $plan['remaps']));
    }

    private function backup(array $plan): string
    {
        $directory = 'backups/tawsst-finance-restart/' . now()->format('Ymd-His');
        foreach (array_keys(array_filter($plan['deletes'])) as $table) {
            Storage::disk('local')->put("{$directory}/{$table}.json", DB::table($table)->get()->toJson(JSON_UNESCAPED_UNICODE));
        }
        $links = [];
        foreach (array_keys($plan['nulls']) as $key) {
            [$table, $column] = explode('.', $key);
            $id = Schema::hasColumn($table, 'id') ? 'id' : null;
            $links[$key] = $id ? DB::table($table)->whereNotNull($column)->pluck($column, $id) : DB::table($table)->whereNotNull($column)->get();
        }
        Storage::disk('local')->put("{$directory}/links.json", json_encode($links, JSON_UNESCAPED_UNICODE));
        foreach (array_unique(array_merge(['fn_confirm_settings'], array_column($plan['remaps'], 0))) as $table) {
            Storage::disk('local')->put("{$directory}/{$table}.json", DB::table($table)->get()->toJson(JSON_UNESCAPED_UNICODE));
        }

        return $directory;
    }

    private function wipeAndImport(array $plan, string $path, array $chart, DataImportExecutionService $importer): array
    {
        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?: 1);
        $result = ['deleted' => 0, 'nulled' => 0, 'accounts' => 0];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::transaction(function () use ($plan, $path, $chart, $importer, $adminId, &$result) {
                foreach (array_keys($plan['deletes']) as $table) {
                    $result['deleted'] += DB::table($table)->delete();
                }
                foreach (array_keys($plan['nulls']) as $key) {
                    [$table, $column] = explode('.', $key);
                    $result['nulled'] += DB::table($table)->whereNotNull($column)->update([$column => null]);
                }
                DB::table('fn_confirm_settings')
                    ->where(fn ($q) => $q->where('title', 'like', '%account%')->orWhere('title', 'like', 'rental\\_maid\\_prepaid\\_%'))
                    ->update(['value' => null]);

                // نفس منطق شاشة استيراد الحسابات الذي اعتمده المحاسب
                $import = $importer->import('finance_accounts', new UploadedFile($path, basename($path), null, null, true), $adminId);
                if ($import['error_count'] || $import['created'] !== count($chart)) {
                    throw new RuntimeException('فشل رفع الدليل (أُنشئ ' . $import['created'] . ' من ' . count($chart) . "):\n" . implode("\n", $import['errors']));
                }
                $result['accounts'] = $import['created'];

                $newIds = DB::table('fn_accounts')->pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [(string) $code => (int) $id]);
                foreach ($plan['remaps'] as [$table, $column, $rowId, $old, $new]) {
                    $id = $newIds[$new] ?? throw new RuntimeException("الكود {$new} غير موجود في الدليل الجديد ({$table}.{$column}).");
                    DB::table($table)->where('id', $rowId)->update([$column => $id]);
                }
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $result;
    }
}
