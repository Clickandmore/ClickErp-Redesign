<?php

namespace App\Services\ingaz;

use App\Models\auth\Admin;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LegacyActivityLogImportService
{
    public const SOURCE = 'alrafedin_daily_activity_log';
    public const LOG_NAME = 'legacy_activity';

    private const CHUNK_SIZE = 500;
    private const MAX_ERROR_DETAILS = 200;

    public function __construct(private DataImportTemplateService $templates)
    {
    }

    public function import(UploadedFile $file, ?int $adminId): array
    {
        $database = DB::connection()->getDatabaseName();
        if (!in_array($database, config('legacy_activity_import.alrafedin_databases', []), true)) {
            throw new \RuntimeException('استيراد سجل الرافدين غير مسموح لقاعدة البيانات الحالية. يجب التحقق من الشركة وتحديد قاعدة الرافدين المصرح بها صراحةً قبل الاستيراد.');
        }

        $this->ensureSchemaIsReady();

        $storedPath = $this->templates->storeUploadedFile('legacy_activity_logs', $file, $adminId);
        $result = [
            'path' => $storedPath,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'error_count' => 0,
        ];
        $existingIds = DB::table($this->table())
            ->where('legacy_source', self::SOURCE)
            ->pluck('legacy_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
        $seenIds = [];
        $adminNames = $this->adminNameMap();
        $pending = [];

        foreach ($this->readRows(Storage::path($storedPath)) as $rowNumber => $row) {
            $result['total']++;

            try {
                $legacyId = $this->normalizeLegacyId($row['legacy_id'] ?? null);

                if (isset($seenIds[$legacyId])) {
                    throw new \RuntimeException("رقم السجل {$legacyId} مكرر داخل الملف.");
                }

                $seenIds[$legacyId] = true;
                $pending[] = $this->activityPayload($row, $legacyId, $adminNames, $adminId);

                if (isset($existingIds[$legacyId])) {
                    $result['updated']++;
                } else {
                    $result['created']++;
                    $existingIds[$legacyId] = true;
                }

            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: {$exception->getMessage()}";
                }
            }

            if (count($pending) >= self::CHUNK_SIZE) {
                $this->upsert($pending);
                $pending = [];
            }
        }

        if ($pending !== []) {
            $this->upsert($pending);
        }

        return $result;
    }

    public function readRows(string $path): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();
        $headerMap = $this->resolveHeaders($sheet, $highestColumn);

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [];

            foreach ($headerMap as $column => $key) {
                $row[$key] = $sheet->getCell([$column, $rowNumber])->getFormattedValue();
            }

            if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            yield $rowNumber => $row;
        }

        $spreadsheet->disconnectWorksheets();
    }

    private function resolveHeaders($sheet, int $highestColumn): array
    {
        $aliases = [
            'م' => 'legacy_id',
            'الرقم' => 'legacy_id',
            'رقم' => 'legacy_id',
            'ملاحظة' => 'note',
            'الملاحظة' => 'note',
            'تاريخ هجري' => 'hijri_date',
            'التاريخ الهجري' => 'hijri_date',
            'تاريخ ميلادي' => 'gregorian_date',
            'التاريخ الميلادي' => 'gregorian_date',
            'الوقت' => 'time',
            'بواسطة المستخدم' => 'user_name',
            'المستخدم' => 'user_name',
        ];
        $normalizedAliases = [];

        foreach ($aliases as $header => $key) {
            $normalizedAliases[$this->normalizeHeader($header)] = $key;
        }

        $headers = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $header = $this->normalizeHeader((string) $sheet->getCell([$column, 1])->getFormattedValue());

            if (isset($normalizedAliases[$header])) {
                $headers[$column] = $normalizedAliases[$header];
            }
        }

        $required = ['legacy_id', 'note', 'gregorian_date', 'time', 'user_name'];
        $missing = array_diff($required, array_values($headers));

        if ($missing !== []) {
            throw new \RuntimeException('أعمدة سجل النشاط المطلوبة غير مكتملة: ' . implode('، ', $missing));
        }

        return $headers;
    }

    private function activityPayload(array $row, int $legacyId, array $adminNames, ?int $adminId): array
    {
        $note = trim((string) ($row['note'] ?? ''));
        $userName = trim((string) ($row['user_name'] ?? ''));

        if ($note === '') {
            throw new \RuntimeException('الملاحظة فارغة.');
        }

        if ($userName === '') {
            throw new \RuntimeException('اسم المستخدم فارغ.');
        }

        $createdAt = $this->sourceDateTime($row['gregorian_date'] ?? null, $row['time'] ?? null);
        $causerId = $adminNames[$this->normalizeName($userName)] ?? null;

        return [
            'log_name' => self::LOG_NAME,
            'description' => $note,
            'subject_type' => null,
            'event' => 'legacy',
            'subject_id' => null,
            'causer_type' => $causerId ? Admin::class : null,
            'causer_id' => $causerId,
            'properties' => json_encode([
                'legacy_serial' => $legacyId,
                'legacy_hijri_date' => trim((string) ($row['hijri_date'] ?? '')) ?: null,
                'legacy_gregorian_date' => $createdAt->format('Y-m-d'),
                'legacy_time' => $createdAt->format('H:i:s'),
                'legacy_user_name' => $userName,
                'legacy_source' => self::SOURCE,
                'imported_by_admin_id' => $adminId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'batch_uuid' => null,
            'legacy_source' => self::SOURCE,
            'legacy_id' => $legacyId,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function sourceDateTime(mixed $dateValue, mixed $timeValue): CarbonImmutable
    {
        $date = $this->normalizeDate($dateValue);
        $time = $this->normalizeTime($timeValue);

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d H:i:s', "{$date} {$time}");
        } catch (\Throwable) {
            throw new \RuntimeException("تاريخ أو وقت غير صالح: {$date} {$time}");
        }
    }

    private function normalizeDate(mixed $value): string
    {
        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        $value = trim((string) $value);

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat("!{$format}", $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        throw new \RuntimeException("تاريخ ميلادي غير صالح: {$value}");
    }

    private function normalizeTime(mixed $value): string
    {
        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('H:i:s');
        }

        $value = trim((string) $value);

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $time = CarbonImmutable::createFromFormat("!{$format}", $value);
                if ($time && $time->format($format) === $value) {
                    return $time->format('H:i:s');
                }
            } catch (\Throwable) {
            }
        }

        throw new \RuntimeException("وقت غير صالح: {$value}");
    }

    private function normalizeLegacyId(mixed $value): int
    {
        $value = trim((string) $value);

        if (!ctype_digit($value) || (int) $value < 1) {
            throw new \RuntimeException("رقم سجل غير صالح: {$value}");
        }

        return (int) $value;
    }

    private function adminNameMap(): array
    {
        return Admin::withTrashed()
            ->whereNotNull('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Admin $admin) => $this->normalizeName($admin->name))
            ->filter(fn ($admins) => $admins->count() === 1)
            ->map(fn ($admins) => $admins->first()->id)
            ->all();
    }

    private function normalizeName(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function normalizeHeader(string $value): string
    {
        $value = str_replace(['ـ', "\u{00A0}"], ['', ' '], trim($value));

        return mb_strtolower(preg_replace('/\s+/u', ' ', $value));
    }

    private function upsert(array $rows): void
    {
        DB::table($this->table())->upsert(
            $rows,
            ['legacy_source', 'legacy_id'],
            [
                'log_name',
                'description',
                'subject_type',
                'event',
                'subject_id',
                'causer_type',
                'causer_id',
                'properties',
                'batch_uuid',
                'created_at',
                'updated_at',
            ]
        );
    }

    private function ensureSchemaIsReady(): void
    {
        if (!Schema::hasColumn($this->table(), 'legacy_source') || !Schema::hasColumn($this->table(), 'legacy_id')) {
            throw new \RuntimeException('شغّل ترحيلات قاعدة البيانات قبل استيراد سجل النشاط القديم.');
        }
    }

    private function table(): string
    {
        return config('activitylog.table_name', 'activity_log');
    }
}
