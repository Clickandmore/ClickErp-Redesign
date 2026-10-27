<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Enums\AccountTypesEnum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class ImportClickAndMoreChartOfAccounts extends Command
{
    private const COMPATIBILITY_CODE = 9;

    protected $signature = 'finance:import-clickandmore-chart
        {file=migration_system/data/clickandmore/finance/Chart_of_Accounts_ClickAndMore_v2.xlsx}
        {--database=clickandmore_erp : اسم قاعدة البيانات المسموح تعديلها}
        {--commit : تنفيذ التغيير؛ بدونه يعمل الأمر كمعاينة فقط}';

    protected $description = 'Safely replace the Click & More chart of accounts from its dedicated workbook';

    public function handle(): int
    {
        try {
            $this->guardDatabase();
            $rows = $this->readAndValidateWorkbook($this->absolutePath((string) $this->argument('file')));
            $existing = DB::table('fn_accounts')->orderBy('id')->get();
            [$protectedIds, $references] = $this->referencedAccountIds($existing->pluck('id')->map(fn ($id) => (int) $id)->all());
            $sourceCodes = array_map('intval', array_keys($rows));
            $protectedLegacyIds = $existing
                ->filter(fn ($account) => in_array((int) $account->id, $protectedIds, true)
                    && !in_array((int) $account->code, $sourceCodes, true))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $overlap = $existing->whereIn('code', $sourceCodes)->count();
            $this->table(
                ['الفحص', 'النتيجة'],
                [
                    ['قاعدة البيانات', DB::getDatabaseName()],
                    ['حسابات الملف', count($rows)],
                    ['الحسابات الحالية', $existing->count()],
                    ['أكواد يعاد استخدامها', $overlap],
                    ['حسابات قديمة مرتبطة سيتم حفظها للتوافق', count($protectedLegacyIds)],
                    ['روابط حالية إلى حسابات موجودة', count($references)],
                    ['الوضع', $this->option('commit') ? 'تنفيذ' : 'معاينة فقط'],
                ]
            );

            if (!$this->option('commit')) {
                $this->info('المعاينة ناجحة. لم تتغير أي بيانات. استخدم --commit للتنفيذ.');

                return self::SUCCESS;
            }

            $backupPath = $this->backup($existing->all(), $references);
            $result = DB::transaction(fn () => $this->replaceChart($rows, $protectedLegacyIds));

            $this->newLine();
            $this->info('تم استبدال شجرة الحسابات بنجاح.');
            $this->line('نسخة الاحتياط: storage/app/' . $backupPath);
            $this->line('تم الإنشاء: ' . $result['created'] . '، التحديث: ' . $result['updated'] . '، الحذف: ' . $result['deleted']);
            $this->line('حسابات التوافق المحفوظة: ' . $result['compatibility']);
            $this->line('إجمالي الحسابات الآن: ' . DB::table('fn_accounts')->count());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function guardDatabase(): void
    {
        if (!Schema::hasTable('fn_accounts')) {
            throw new RuntimeException('جدول fn_accounts غير موجود.');
        }

        $expected = trim((string) $this->option('database'));
        $actual = DB::getDatabaseName();
        if ($expected === '' || $actual !== $expected) {
            throw new RuntimeException("تم إيقاف التنفيذ: قاعدة البيانات الحالية {$actual} وليست {$expected}.");
        }
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function readAndValidateWorkbook(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('ملف شجرة الحسابات غير موجود: ' . $path);
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('شجرة الحسابات') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        $headerRow = null;

        foreach ($data as $number => $row) {
            if (trim((string) ($row['A'] ?? '')) === 'كود الحساب') {
                $headerRow = (int) $number;
                break;
            }
        }

        if (!$headerRow) {
            throw new RuntimeException('تعذر العثور على صف عناوين شجرة الحسابات.');
        }

        $rows = [];
        foreach ($data as $number => $row) {
            if ((int) $number <= $headerRow) {
                continue;
            }

            $code = preg_replace('/\D+/', '', trim((string) ($row['A'] ?? '')));
            if ($code === '') {
                continue;
            }
            if (isset($rows[$code])) {
                throw new RuntimeException("كود الحساب {$code} مكرر في ملف Excel.");
            }

            $rows[$code] = [
                'code' => (int) $code,
                'name' => trim((string) ($row['B'] ?? '')),
                'kind' => strtolower(trim((string) ($row['D'] ?? ''))),
                'nature' => strtolower(trim((string) ($row['E'] ?? ''))),
                'parent_code' => preg_replace('/\D+/', '', trim((string) ($row['F'] ?? ''))),
                'level' => (int) ($row['G'] ?? 0),
                'summary' => strtolower(trim((string) ($row['H'] ?? ''))) === 'yes',
                'row' => (int) $number,
            ];
        }

        foreach ($rows as $code => $row) {
            if ($row['name'] === '' || !in_array($row['kind'], ['asset', 'liability', 'equity', 'revenue', 'expense'], true)
                || !in_array($row['nature'], ['debit', 'credit'], true) || $row['level'] < 1) {
                throw new RuntimeException("بيانات غير صالحة في صف Excel رقم {$row['row']}.");
            }
            if ($row['level'] === 1 && $row['parent_code'] !== '') {
                throw new RuntimeException("الحساب الرئيسي {$code} لا يجب أن يكون له أب.");
            }
            if ($row['level'] > 1) {
                $parent = $rows[$row['parent_code']] ?? null;
                if (!$parent || $parent['level'] + 1 !== $row['level']) {
                    throw new RuntimeException("الحساب الأب للحساب {$code} غير موجود أو مستواه غير صحيح.");
                }
            }
        }

        uasort($rows, fn ($a, $b) => [$a['level'], $a['code']] <=> [$b['level'], $b['code']]);

        return $rows;
    }

    private function referencedAccountIds(array $existingIds): array
    {
        if ($existingIds === []) {
            return [[], []];
        }

        $database = DB::getDatabaseName();
        $columns = DB::table('information_schema.COLUMNS as c')
            ->leftJoin('information_schema.KEY_COLUMN_USAGE as k', function ($join) use ($database) {
                $join->on('k.TABLE_SCHEMA', '=', 'c.TABLE_SCHEMA')
                    ->on('k.TABLE_NAME', '=', 'c.TABLE_NAME')
                    ->on('k.COLUMN_NAME', '=', 'c.COLUMN_NAME')
                    ->where('k.REFERENCED_TABLE_SCHEMA', '=', $database)
                    ->where('k.REFERENCED_TABLE_NAME', '=', 'fn_accounts');
            })
            ->where('c.TABLE_SCHEMA', $database)
            ->where('c.TABLE_NAME', '<>', 'fn_accounts')
            ->where(function ($query) {
                $query->where('c.COLUMN_NAME', 'like', '%account_id')
                    ->orWhereNotNull('k.REFERENCED_TABLE_NAME');
            })
            ->distinct()
            ->get(['c.TABLE_NAME as table_name', 'c.COLUMN_NAME as column_name']);

        $protected = [];
        $references = [];
        foreach ($columns as $column) {
            $ids = DB::table($column->table_name)
                ->whereIn($column->column_name, $existingIds)
                ->distinct()
                ->pluck($column->column_name)
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($ids as $id) {
                $protected[$id] = true;
                $references[] = ['table' => $column->table_name, 'column' => $column->column_name, 'account_id' => $id];
            }
        }

        return [array_keys($protected), $references];
    }

    private function backup(array $accounts, array $references): string
    {
        $path = 'backups/chart-of-accounts/clickandmore-before-' . now()->format('Ymd-His') . '.json';
        Storage::disk('local')->put($path, json_encode([
            'database' => DB::getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'accounts' => $accounts,
            'references' => $references,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $path;
    }

    private function replaceChart(array $rows, array $protectedLegacyIds): array
    {
        $existingByCode = DB::table('fn_accounts')->lockForUpdate()->get()->keyBy(fn ($row) => (int) $row->code);
        $adminId = DB::table('admins')->orderBy('id')->value('id') ?: 1;
        $idsByCode = [];
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $parentId = $row['parent_code'] === '' ? null : ($idsByCode[(int) $row['parent_code']] ?? null);
            $payload = [
                'name' => $row['name'],
                'operation_type' => 1,
                'status' => $row['nature'] === 'credit' ? 'creditor' : 'debtor',
                'statement_type' => in_array($row['kind'], ['revenue', 'expense'], true) ? 'income_statement' : 'balance_sheet',
                'level' => $row['level'],
                'type' => $this->accountType($row, $parentId ? (int) DB::table('fn_accounts')->where('id', $parentId)->value('type') : null),
                'parent_id' => $parentId,
                'created_by_id' => $adminId,
                'updated_at' => now(),
            ];

            $existing = $existingByCode->get($row['code']);
            if ($existing) {
                if (DB::table('fn_boxes')->where('account_id', $existing->id)->exists()
                    && (int) $payload['type'] !== AccountTypesEnum::Box->value) {
                    throw new RuntimeException('الاستيراد يغير نوع حساب صندوق قائم؛ راجع الكود ' . $row['code'] . ' أولاً.');
                }
                DB::table('fn_accounts')->where('id', $existing->id)->update($payload);
                $id = (int) $existing->id;
                $updated++;
            } else {
                $id = (int) DB::table('fn_accounts')->insertGetId($payload + [
                    'code' => $row['code'],
                    'created_at' => now(),
                ]);
                $created++;
            }
            $idsByCode[$row['code']] = $id;
        }

        $compatibilityId = null;
        if ($protectedLegacyIds !== []) {
            $compatibilityId = (int) DB::table('fn_accounts')->where('code', self::COMPATIBILITY_CODE)->value('id');
            $payload = [
                'name' => 'حسابات تشغيلية سابقة (محفوظة للتوافق)',
                'operation_type' => 1,
                'status' => 'debtor',
                'statement_type' => 'balance_sheet',
                'level' => 1,
                'type' => AccountTypesEnum::NormalAccount->value,
                'parent_id' => null,
                'created_by_id' => $adminId,
                'updated_at' => now(),
            ];
            if ($compatibilityId) {
                DB::table('fn_accounts')->where('id', $compatibilityId)->update($payload);
            } else {
                $compatibilityId = (int) DB::table('fn_accounts')->insertGetId($payload + [
                    'code' => self::COMPATIBILITY_CODE,
                    'created_at' => now(),
                ]);
                $created++;
            }
            DB::table('fn_accounts')->whereIn('id', $protectedLegacyIds)->update([
                'parent_id' => $compatibilityId,
                'level' => 2,
                'updated_at' => now(),
            ]);
        }

        $keepIds = array_merge(array_values($idsByCode), $protectedLegacyIds, $compatibilityId ? [$compatibilityId] : []);
        $toDelete = DB::table('fn_accounts')->whereNotIn('id', $keepIds)->orderByDesc('level')->pluck('id');
        $deleted = 0;
        foreach ($toDelete as $id) {
            if (DB::table('fn_boxes')->where('account_id', $id)->exists()) {
                throw new RuntimeException('لا يمكن استيراد شجرة تحذف حساب صندوق قائم: ' . $id);
            }
            $deleted += DB::table('fn_accounts')->where('id', $id)->delete();
        }

        $sourceCount = DB::table('fn_accounts')->whereIn('code', array_keys($idsByCode))->count();
        if ($sourceCount !== count($rows)) {
            throw new RuntimeException('فشل التحقق النهائي من عدد حسابات الشجرة؛ تم التراجع تلقائيًا.');
        }

        return compact('created', 'updated', 'deleted') + ['compatibility' => count($protectedLegacyIds)];
    }

    private function accountType(array $row, ?int $parentType): int
    {
        $name = $row['name'];
        $code = $row['code'];

        return match (true) {
            $code === 3 => AccountTypesEnum::PropertyRights->value,
            $code === 4 => AccountTypesEnum::GeneralIncome->value,
            $code === 5 => AccountTypesEnum::GeneralOutcome->value,
            $code === 11 => AccountTypesEnum::CurrentPrinciple->value,
            $code === 12 => AccountTypesEnum::FixedPrinciple->value,
            $code === 21 => AccountTypesEnum::ShortLiabilities->value,
            $code === 22 => AccountTypesEnum::LongLiabilities->value,
            str_contains($name, 'عملاء') => $row['summary'] ? AccountTypesEnum::Clients->value : AccountTypesEnum::Client->value,
            $parentType === AccountTypesEnum::Clients->value => $row['summary'] ? AccountTypesEnum::Clients->value : AccountTypesEnum::Client->value,
            str_contains($name, 'مورد') => $row['summary'] ? AccountTypesEnum::Suppliers->value : AccountTypesEnum::Supplier->value,
            $parentType === AccountTypesEnum::Suppliers->value => $row['summary'] ? AccountTypesEnum::Suppliers->value : AccountTypesEnum::Supplier->value,
            str_contains($name, 'صندوق') => $row['summary'] ? AccountTypesEnum::Boxs->value : AccountTypesEnum::Box->value,
            str_contains($name, 'محفظ') => $row['summary'] ? AccountTypesEnum::ElectronicWallets->value : AccountTypesEnum::ElectronicWallet->value,
            str_contains($name, 'بنك'), str_contains($name, 'مصرف'), str_contains($name, 'بوابات الدفع') => $row['summary'] ? AccountTypesEnum::Banks->value : AccountTypesEnum::Bank->value,
            default => AccountTypesEnum::NormalAccount->value,
        };
    }
}
