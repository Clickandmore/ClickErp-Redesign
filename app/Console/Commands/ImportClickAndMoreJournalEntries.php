<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\FnYear;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class ImportClickAndMoreJournalEntries extends Command
{
    private const SOURCE_TYPE = 'clickandmore_journal';

    protected $signature = 'finance:import-clickandmore-journal
        {file=migration_system/data/clickandmore/finance/Journal_Entries_Import.xlsx}
        {--database=clickandmore_erp : اسم قاعدة البيانات المسموح تعديلها}
        {--commit : تنفيذ الاستيراد؛ بدونه يعمل الأمر كمعاينة فقط}';

    protected $description = 'Safely import the dedicated Click & More journal-entry workbook';

    public function handle(): int
    {
        try {
            $this->guardDatabase();
            $groups = $this->readWorkbook($this->absolutePath((string) $this->argument('file')));
            $analysis = $this->validateAgainstDatabase($groups);

            $this->table(['الفحص', 'النتيجة'], [
                ['قاعدة البيانات', DB::getDatabaseName()],
                ['عدد القيود', count($groups)],
                ['عدد السطور', $analysis['lines']],
                ['إجمالي المدين', number_format($analysis['total'], 2)],
                ['إجمالي الدائن', number_format($analysis['total'], 2)],
                ['أكواد الحسابات المستخدمة', $analysis['accounts']],
                ['أقدم تاريخ', $analysis['date_min']],
                ['أحدث تاريخ', $analysis['date_max']],
                ['قيود سبق استيرادها وسيتم تحديثها', $analysis['existing']],
                ['الوضع', $this->option('commit') ? 'تنفيذ' : 'معاينة فقط'],
            ]);

            if (!$this->option('commit')) {
                $this->info('المعاينة ناجحة. لم تتغير أي بيانات. استخدم --commit للتنفيذ.');

                return self::SUCCESS;
            }

            $backupPath = $this->backup();
            $result = DB::transaction(fn () => $this->importGroups($groups));

            $this->newLine();
            $this->info('تم استيراد قيود Click & More بنجاح.');
            $this->line('نسخة الاحتياط: storage/app/' . $backupPath);
            $this->line("تم الإنشاء: {$result['created']}، التحديث: {$result['updated']}، سطور القيود: {$result['details']}");
            $this->line('سنوات مالية أُنشئت لتغطية التواريخ: ' . $result['years_created']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function guardDatabase(): void
    {
        foreach (['fn_accounts', 'fn_constraints', 'fn_constraint_details', 'fn_years'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("الجدول {$table} غير موجود.");
            }
        }

        $expected = trim((string) $this->option('database'));
        $actual = DB::getDatabaseName();
        if ($expected === '' || $actual !== $expected) {
            throw new RuntimeException("تم إيقاف التنفيذ: قاعدة البيانات الحالية {$actual} وليست {$expected}.");
        }
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    private function readWorkbook(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('ملف القيود غير موجود: ' . $path);
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('قيود اليومية') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        $headerRow = null;

        foreach ($data as $number => $row) {
            if (trim((string) ($row['A'] ?? '')) === 'رقم القيد'
                && trim((string) ($row['C'] ?? '')) === 'كود الحساب') {
                $headerRow = (int) $number;
                break;
            }
        }
        if (!$headerRow) {
            throw new RuntimeException('تعذر العثور على صف عناوين قيود اليومية.');
        }

        $groups = [];
        foreach ($data as $number => $row) {
            if ((int) $number <= $headerRow) {
                continue;
            }

            $reference = trim((string) ($row['A'] ?? ''));
            if ($reference === '') {
                continue;
            }
            if (!preg_match('/^JE-(\d+)$/i', $reference, $matches)) {
                throw new RuntimeException("رقم القيد {$reference} غير صالح في صف {$number}.");
            }

            $date = $this->excelDate($row['B'] ?? null);
            $accountCode = preg_replace('/\D+/', '', trim((string) ($row['C'] ?? '')));
            $debit = $this->amount($row['E'] ?? null);
            $credit = $this->amount($row['F'] ?? null);
            if (!$date || $accountCode === '') {
                throw new RuntimeException("التاريخ وكود الحساب مطلوبان في صف {$number}.");
            }
            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw new RuntimeException("صف {$number} يجب أن يحتوي مبلغًا في طرف واحد فقط.");
            }

            $groups[$reference][] = [
                'reference' => $reference,
                'reference_number' => (int) $matches[1],
                'row' => (int) $number,
                'date' => $date,
                'account_code' => (int) $accountCode,
                'account_name' => trim((string) ($row['D'] ?? '')),
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'statement' => trim((string) ($row['G'] ?? '')),
                'source_reference' => trim((string) ($row['H'] ?? '')),
                'party' => trim((string) ($row['I'] ?? '')),
                'line_kind' => trim((string) ($row['J'] ?? '')),
            ];
        }

        uksort($groups, fn ($a, $b) => (int) substr($a, 3) <=> (int) substr($b, 3));

        return $groups;
    }

    private function validateAgainstDatabase(array $groups): array
    {
        if ($groups === []) {
            throw new RuntimeException('ملف القيود لا يحتوي على بيانات.');
        }

        $codes = [];
        $dates = [];
        $lines = 0;
        $total = 0.0;
        foreach ($groups as $reference => $entries) {
            if (count($entries) < 2) {
                throw new RuntimeException("القيد {$reference} يجب أن يحتوي سطرين على الأقل.");
            }

            $entryDates = array_values(array_unique(array_column($entries, 'date')));
            if (count($entryDates) !== 1) {
                throw new RuntimeException("صفوف القيد {$reference} تحمل تواريخ مختلفة.");
            }

            $debit = round(array_sum(array_column($entries, 'debit')), 2);
            $credit = round(array_sum(array_column($entries, 'credit')), 2);
            if ($debit <= 0 || $debit !== $credit) {
                throw new RuntimeException("القيد {$reference} غير متوازن: مدين {$debit}، دائن {$credit}.");
            }

            $total += $debit;
            $lines += count($entries);
            $dates[] = $entryDates[0];
            foreach ($entries as $entry) {
                $codes[$entry['account_code']] = $entry['account_name'];
            }
        }

        $accounts = DB::table('fn_accounts')
            ->whereIn('code', array_keys($codes))
            ->get(['id', 'code', 'name'])
            ->keyBy(fn ($account) => (int) $account->code);
        $missing = array_diff(array_keys($codes), $accounts->keys()->all());
        if ($missing !== []) {
            throw new RuntimeException('حسابات الملف التالية غير موجودة في الشجرة: ' . implode('، ', $missing));
        }

        $nonPosting = DB::table('fn_accounts as parent')
            ->whereIn('parent.code', array_keys($codes))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('fn_accounts as child')->whereColumn('child.parent_id', 'parent.id'))
            ->pluck('parent.code')
            ->all();
        if ($nonPosting !== []) {
            throw new RuntimeException('القيود تستخدم حسابات تجميعية لها أبناء: ' . implode('، ', $nonPosting));
        }

        $numbers = collect($groups)->map(fn ($entries) => $entries[0]['reference_number'])->all();
        $existing = Constraint::query()
            ->where('legacy_document_type', self::SOURCE_TYPE)
            ->whereIn('legacy_document_number', $numbers)
            ->count();

        return [
            'lines' => $lines,
            'total' => round($total, 2),
            'accounts' => count($codes),
            'date_min' => min($dates),
            'date_max' => max($dates),
            'existing' => $existing,
        ];
    }

    private function backup(): string
    {
        $path = 'backups/journal-entries/clickandmore-before-' . now()->format('Ymd-His') . '.json';
        Storage::disk('local')->put($path, json_encode([
            'database' => DB::getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'constraints' => DB::table('fn_constraints')->orderBy('id')->get(),
            'details' => DB::table('fn_constraint_details')->orderBy('id')->get(),
            'years' => DB::table('fn_years')->orderBy('id')->get(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $path;
    }

    private function importGroups(array $groups): array
    {
        $accountIds = DB::table('fn_accounts')->pluck('id', 'code')->map(fn ($id) => (int) $id);
        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?: 1);
        $nextCode = ((int) Constraint::withTrashed()->lockForUpdate()->max('code')) + 1;
        $created = 0;
        $updated = 0;
        $detailsCount = 0;
        $yearsCreated = 0;

        foreach ($groups as $reference => $entries) {
            [$year, $yearWasCreated] = $this->resolveYear($entries[0]['date'], $adminId);
            $yearsCreated += $yearWasCreated ? 1 : 0;
            $number = $entries[0]['reference_number'];
            $constraint = Constraint::withTrashed()
                ->where('legacy_document_type', self::SOURCE_TYPE)
                ->where('legacy_document_number', $number)
                ->lockForUpdate()
                ->first();

            $statement = $this->limited("[{$reference}] " . $entries[0]['statement']
                . ($entries[0]['source_reference'] !== '' ? ' | ' . $entries[0]['source_reference'] : ''));
            $payload = [
                'type' => ConstraintTypiesEnum::DailyEntry->value,
                'value' => round(array_sum(array_column($entries, 'debit')), 2),
                'date' => $entries[0]['date'],
                'statement' => $statement,
                'year_id' => $year->id,
                'created_by_id' => $adminId,
                'legacy_document_type' => self::SOURCE_TYPE,
                'legacy_document_number' => $number,
                'legacy_source_file' => basename((string) $this->argument('file')),
                'legacy_source_row' => $entries[0]['row'],
            ];

            if ($constraint) {
                if ($constraint->trashed()) {
                    $constraint->restore();
                }
                $constraint->update($payload);
                ConstraintDetails::withTrashed()->where('constraint_id', $constraint->id)->forceDelete();
                $updated++;
            } else {
                $constraint = Constraint::query()->create($payload + ['code' => $nextCode++]);
                $created++;
            }

            $now = now();
            $details = [];
            foreach ($entries as $entry) {
                $lineStatement = collect([
                    $entry['statement'],
                    $entry['source_reference'],
                    $entry['party'] !== '' ? 'الطرف: ' . $entry['party'] : null,
                    $entry['line_kind'] !== '' ? 'النوع: ' . $entry['line_kind'] : null,
                ])->filter()->implode(' | ');
                $details[] = [
                    'constraint_id' => $constraint->id,
                    'date' => $entry['date'],
                    'type' => $entry['debit'] > 0 ? 'debtor' : 'creditor',
                    'account_id' => $accountIds[$entry['account_code']],
                    'debtor_value' => $entry['debit'],
                    'creditor_value' => $entry['credit'],
                    'cost_center_id' => null,
                    'statement' => $this->limited($lineStatement),
                    'year_id' => $year->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            ConstraintDetails::query()->insert($details);
            $detailsCount += count($details);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'details' => $detailsCount,
            'years_created' => $yearsCreated,
        ];
    }

    private function resolveYear(string $date, int $adminId): array
    {
        $year = FnYear::query()
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->orderByDesc('status')
            ->orderBy('id')
            ->first();
        if ($year) {
            return [$year, false];
        }

        $day = Carbon::parse($date);
        $from = $day->copy()->startOfYear();
        $to = $day->copy()->endOfYear();
        $next = FnYear::query()->whereDate('from_date', '>', $date)->orderBy('from_date')->first();
        $previous = FnYear::query()->whereDate('to_date', '<', $date)->orderByDesc('to_date')->first();
        if ($next && Carbon::parse($next->from_date)->year === $day->year) {
            $to = Carbon::parse($next->from_date)->subDay();
        }
        if ($previous && Carbon::parse($previous->to_date)->year === $day->year) {
            $from = Carbon::parse($previous->to_date)->addDay();
        }
        if ($day->lt($from) || $day->gt($to)) {
            throw new RuntimeException("تعذر إنشاء سنة مالية غير متداخلة لتاريخ {$date}.");
        }

        $payload = [
            'title' => (string) $day->year,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'status' => 0,
        ];
        if (Schema::hasColumn('fn_years', 'created_by_id')) {
            $payload['created_by_id'] = $adminId;
        }

        return [FnYear::query()->create($payload), true];
    }

    private function excelDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return trim((string) $value) === '' ? null : Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function amount(mixed $value): float
    {
        $value = str_replace([',', '٬', ' '], '', trim((string) $value));

        return $value === '' ? 0.0 : (float) $value;
    }

    private function limited(string $value): string
    {
        return mb_substr(trim($value) ?: '-', 0, 500);
    }
}
