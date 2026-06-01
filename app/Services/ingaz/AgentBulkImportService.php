<?php

namespace App\Services\ingaz;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\AgentMaidBulkImport;
use App\Models\ingaz\AgentMaidBulkImportRow;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\RecruitmentOffices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AgentBulkImportService
{
    public function templateColumns(): array
    {
        return [
            ['key' => 'name',         'label' => 'الاسم بالإنجليزية', 'required' => true],
            ['key' => 'passport_num', 'label' => 'رقم الجواز',        'required' => true],
            ['key' => 'nationality',  'label' => 'الجنسية',           'required' => true],
            ['key' => 'religion',     'label' => 'الديانة',           'required' => false],
            ['key' => 'age',          'label' => 'العمر',             'required' => false],
            ['key' => 'birth_date',   'label' => 'تاريخ الميلاد',     'required' => false],
            ['key' => 'experience_years', 'label' => 'سنوات الخبرة',  'required' => false],
            ['key' => 'job_titles',   'label' => 'المهنة',            'required' => false],
            ['key' => 'price',        'label' => 'التكلفة',           'required' => false],
            ['key' => 'additional_notes', 'label' => 'ملاحظات',       'required' => false],
        ];
    }

    public function createImportFromUpload(UploadedFile $file, int $agentId, int $officeId): AgentMaidBulkImport
    {
        $fileType = $this->detectFileType($file);

        $storedPath = $file->storeAs(
            'agent-imports',
            now()->format('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName())
        );

        $import = AgentMaidBulkImport::create([
            'agent_id'          => $agentId,
            'office_id'         => $officeId,
            'status'            => 'processing',
            'file_type'         => $fileType,
            'original_filename' => $file->getClientOriginalName(),
            'uploaded_file_path' => $storedPath,
        ]);

        try {
            $rows = $this->parseFile(Storage::path($storedPath), $fileType);
            $this->persistRows($import, $rows);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'meta'   => ['exception' => $e->getMessage()],
            ]);
            throw $e;
        }

        return $import->fresh('rows');
    }

    public function approveImport(AgentMaidBulkImport $import, MaidAction $maidAction, int $adminId): array
    {
        $rows = $import->rows()
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('row_number')
            ->get();

        if ($rows->isEmpty()) {
            return ['imported' => 0, 'failed' => 0];
        }

        $import->update([
            'status'      => 'processing',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        $importedCount = 0;
        $failedCount   = 0;

        foreach ($rows as $row) {
            $payload = $row->normalized_data ?? [];

            try {
                if (empty($payload)) {
                    throw ValidationException::withMessages(['row' => ['لا توجد بيانات جاهزة.']]);
                }

                if (Maid::where('passport_num', $payload['passport_num'])->exists()) {
                    throw ValidationException::withMessages(['passport_num' => ['رقم الجواز موجود مسبقاً.']]);
                }

                $request = Request::create('', 'POST', $payload);
                $request->setUserResolver(fn () => auth('admin')->user());

                DB::transaction(function () use ($maidAction, $payload, $request, $row, $import, $adminId, &$importedCount) {
                    $data = array_merge($payload, [
                        'status'   => 'available',
                        'added_by' => $adminId,
                        'office_id' => $import->office_id,
                        'is_show'  => 0,
                        'preserve_null_arrival_date' => true,
                    ]);

                    $maid = $maidAction->storeMaid($data, $request);

                    $row->update([
                        'status'      => 'imported',
                        'maid_id'     => $maid->id,
                        'imported_at' => now(),
                        'error_messages' => [],
                    ]);

                    $importedCount++;
                });
            } catch (\Throwable $e) {
                $failedCount++;
                $row->update([
                    'status'         => 'failed',
                    'error_messages' => $this->extractErrors($e),
                ]);
            }
        }

        $this->refreshCounters($import->fresh());

        return ['imported' => $importedCount, 'failed' => $failedCount];
    }

    public function rejectImport(AgentMaidBulkImport $import, int $adminId, string $reason): void
    {
        $import->update([
            'status'           => 'rejected',
            'approved_by'      => $adminId,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    // -------------------------------------------------------------------------
    // File parsing
    // -------------------------------------------------------------------------

    private function detectFileType(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return 'excel';
        }

        if (in_array($ext, ['docx', 'doc'], true)) {
            return 'word';
        }

        if ($ext === 'pdf') {
            return 'pdf';
        }

        $mime = $file->getMimeType();
        if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || $mime === 'text/csv') {
            return 'excel';
        }
        if (str_contains($mime, 'wordprocessingml') || str_contains($mime, 'msword')) {
            return 'word';
        }
        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        return 'excel';
    }

    private function parseFile(string $path, string $fileType): array
    {
        return match ($fileType) {
            'excel' => $this->parseExcel($path),
            'word'  => $this->parseWord($path),
            'pdf'   => $this->parsePdf($path),
            default => throw new \InvalidArgumentException("نوع الملف غير مدعوم: {$fileType}"),
        };
    }

    // ---- Excel ----

    private function parseExcel(string $path): array
    {
        $spreadsheet = SpreadsheetFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->extractExcelHeaders($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $lookups = $this->lookupEntities();
        $rows = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            $rawRow = [];
            foreach ($headers as $colIndex => $key) {
                if ($key === null) {
                    continue;
                }
                $cell = $sheet->getCellByColumnAndRow($colIndex + 1, $r);
                $rawRow[$key] = $this->cellValue($cell->getValue());
            }

            if ($this->rowIsEmpty($rawRow)) {
                continue;
            }

            $normalized = $this->normalizeRow($rawRow, $lookups);
            $rows[] = [
                'row_number'      => $r,
                'raw_data'        => $rawRow,
                'normalized_data' => $normalized['data'],
                'error_messages'  => $normalized['errors'],
                'status'          => empty($normalized['errors']) ? 'pending' : 'invalid',
            ];
        }

        return $this->checkDuplicatePassports($rows);
    }

    private function extractExcelHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $columnKeys = collect($this->templateColumns())->pluck('key')->all();
        $columnLabels = collect($this->templateColumns())->pluck('label')->all();
        $highestCol = $sheet->getHighestDataColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $headers = [];

        for ($c = 1; $c <= $highestColIndex; $c++) {
            $headerValue = trim(str_replace('*', '', (string) $sheet->getCellByColumnAndRow($c, 1)->getValue()));
            $index = array_search($headerValue, $columnLabels, true);
            $headers[$c - 1] = $index !== false ? $columnKeys[$index] : null;
        }

        return $headers;
    }

    // ---- Word ----

    private function parseWord(string $path): array
    {
        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new \RuntimeException('مكتبة phpoffice/phpword غير مثبتة.');
        }

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $this->parseTextAsRows($text, 'word');
    }

    // ---- PDF ----

    private function parsePdf(string $path): array
    {
        $text = '';

        // Try pdftotext binary first
        if (function_exists('exec')) {
            $escaped = escapeshellarg($path);
            exec("pdftotext {$escaped} -", $output, $code);
            if ($code === 0) {
                $text = implode("\n", $output);
            }
        }

        // Fallback: read raw bytes and extract text-like content
        if (empty($text)) {
            $content = file_get_contents($path);
            $text = preg_replace('/[^\x20-\x7E\x{0600}-\x{06FF}\n\r\t ]/u', ' ', $content) ?? '';
        }

        return $this->parseTextAsRows($text, 'pdf');
    }

    // ---- Text extraction (Word/PDF) ----

    private function parseTextAsRows(string $text, string $source): array
    {
        $lookups = $this->lookupEntities();
        $records = $this->splitIntoRecords($text);

        if (empty($records)) {
            // Treat the whole document as one record
            $records = [$text];
        }

        $rows = [];
        foreach ($records as $index => $block) {
            $rawRow = $this->extractFieldsFromText($block, $lookups);

            if ($this->rowIsEmpty($rawRow)) {
                continue;
            }

            $normalized = $this->normalizeRow($rawRow, $lookups);
            $rows[] = [
                'row_number'      => $index + 1,
                'raw_data'        => $rawRow,
                'normalized_data' => $normalized['data'],
                'error_messages'  => $normalized['errors'],
                'status'          => empty($normalized['errors']) ? 'pending' : 'invalid',
            ];
        }

        return $this->checkDuplicatePassports($rows);
    }

    private function splitIntoRecords(string $text): array
    {
        // Split on lines that look like a separator (----, ====, page breaks, or repeated patterns)
        $blocks = preg_split('/(?:\r?\n){2,}|[-=]{4,}|\f/', $text);
        $blocks = array_filter(array_map('trim', $blocks ?: []), fn ($b) => strlen($b) > 10);

        return array_values($blocks);
    }

    private function extractFieldsFromText(string $text, array $lookups): array
    {
        $raw = [
            'name'             => null,
            'passport_num'     => null,
            'nationality'      => null,
            'religion'         => null,
            'age'              => null,
            'birth_date'       => null,
            'experience_years' => null,
            'job_titles'       => null,
            'price'            => null,
            'additional_notes' => null,
        ];

        // Name
        if (preg_match('/(?:name|الاسم|اسم)\s*[:\-]\s*([^\n\r,،]{2,50})/iu', $text, $m)) {
            $raw['name'] = trim($m[1]);
        }

        // Passport
        if (preg_match('/(?:passport|جواز|passport\s*no?\.?|رقم الجواز)\s*[:\-#]?\s*([A-Z0-9]{5,15})/iu', $text, $m)) {
            $raw['passport_num'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/\b([A-Z]{1,3}[0-9]{6,9})\b/', $text, $m)) {
            $raw['passport_num'] = $m[1];
        }

        // Age
        if (preg_match('/(?:age|العمر|عمر)\s*[:\-]\s*(\d{2,3})/iu', $text, $m)) {
            $raw['age'] = $m[1];
        } elseif (preg_match('/\b(1[89]|[2-4]\d|50)\s*(?:years?|سنة|سنوات)?\b/iu', $text, $m)) {
            $raw['age'] = $m[1];
        }

        // Birth date
        if (preg_match('/(?:birth|dob|ميلاد|تاريخ الميلاد)\s*[:\-]\s*(\d{1,4}[\/-]\d{1,2}[\/-]\d{2,4})/iu', $text, $m)) {
            $raw['birth_date'] = $m[1];
        }

        // Experience years
        if (preg_match('/(?:experience|خبرة|سنوات الخبرة)\s*[:\-]\s*(\d+)/iu', $text, $m)) {
            $raw['experience_years'] = $m[1];
        }

        // Price
        if (preg_match('/(?:price|cost|تكلفة|سعر|السعر)\s*[:\-]\s*([\d,\.]+)/iu', $text, $m)) {
            $raw['price'] = str_replace(',', '', $m[1]);
        }

        // Nationality — match against known nationalities
        foreach ($lookups['nationalities']['by_value'] as $normalizedLabel => $records) {
            if (stripos($this->normalizeText($text), $normalizedLabel) !== false) {
                $record = reset($records);
                $raw['nationality'] = $record->sub_title ?: $record->title ?: null;
                break;
            }
        }

        // Religion — match against known religions
        foreach ($lookups['religions']['by_value'] as $normalizedLabel => $records) {
            if (stripos($this->normalizeText($text), $normalizedLabel) !== false) {
                $record = reset($records);
                $raw['religion'] = $record->title ?: null;
                break;
            }
        }

        // Job titles — match against known job titles
        $matchedJobs = [];
        foreach ($lookups['job_titles']['by_value'] as $normalizedLabel => $records) {
            if (stripos($this->normalizeText($text), $normalizedLabel) !== false) {
                $record = reset($records);
                $matchedJobs[] = $record->title;
            }
        }
        if (!empty($matchedJobs)) {
            $raw['job_titles'] = implode('|', array_unique($matchedJobs));
        }

        return $raw;
    }

    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    private function normalizeRow(array $rawRow, array $lookups): array
    {
        $errors = [];
        $data   = [];

        $data['name']             = trim((string) ($rawRow['name'] ?? ''));
        $data['passport_num']     = strtoupper(trim((string) ($rawRow['passport_num'] ?? '')));
        $data['age']              = $this->normalizeDecimal($rawRow['age'] ?? null, false);
        $data['birth_date']       = $this->normalizeDate($rawRow['birth_date'] ?? null);
        $data['experience_years'] = $this->normalizeDecimal($rawRow['experience_years'] ?? null, false);
        $data['price']            = $this->normalizeDecimal($rawRow['price'] ?? null);
        $data['additional_notes'] = $this->trimmed($rawRow['additional_notes'] ?? null);

        if (!$data['age'] && $data['birth_date']) {
            try {
                $data['age'] = (string) Carbon::parse($data['birth_date'])->age;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Nationality
        [$data['nationality_id'], $nationalityError] = $this->resolveEntity($rawRow['nationality'] ?? null, $lookups['nationalities']);
        if ($nationalityError) {
            $errors[] = 'الجنسية: ' . $nationalityError;
        }

        // Religion
        [$data['religion_id'], $religionError] = $this->resolveEntity($rawRow['religion'] ?? null, $lookups['religions']);
        if ($religionError) {
            // religion is optional, no error
        }
        if (empty($data['religion_id'])) {
            $data['religion_id'] = $lookups['default_religion_id'];
        }

        // Job titles
        [$data['job_id'], $jobErrors] = $this->resolveManyEntities($rawRow['job_titles'] ?? null, $lookups['job_titles'], false);
        foreach ($jobErrors as $jobError) {
            $errors[] = 'المهنة: ' . $jobError;
        }

        // Validation
        $validator = Validator::make($data, [
            'name'           => 'required|string|max:255',
            'passport_num'   => 'required|unique:maids,passport_num',
            'nationality_id' => 'required|exists:nationalities,id',
            'age'            => 'nullable|numeric|min:18|max:60',
            'price'          => 'nullable|numeric|min:0',
            'birth_date'     => 'nullable|date',
            'experience_years' => 'nullable|numeric|min:0',
        ], [
            'name.required'           => 'الاسم مطلوب.',
            'passport_num.required'   => 'رقم الجواز مطلوب.',
            'passport_num.unique'     => 'رقم الجواز موجود مسبقاً في النظام.',
            'nationality_id.required' => 'الجنسية مطلوبة.',
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        return [
            'data'   => $this->cleanData($data),
            'errors' => array_values(array_unique(array_filter($errors))),
        ];
    }

    // -------------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------------

    private function lookupEntities(): array
    {
        $nationalities = Nationality::query()->with('translations')->get();
        $religions     = Religion::query()->with('translations')->get();
        $jobTitles     = JobTitle::query()->with('translations')->get();

        return [
            'nationalities'      => $this->buildEntityLookup($nationalities, ['sub_title', 'title']),
            'religions'          => $this->buildEntityLookup($religions, ['title']),
            'job_titles'         => $this->buildEntityLookup($jobTitles, ['title']),
            'default_religion_id' => Religion::query()->orderBy('id')->value('id'),
        ];
    }

    private function buildEntityLookup(\Illuminate\Support\Collection $records, array $fields): array
    {
        $byId    = [];
        $byValue = [];

        foreach ($records as $record) {
            $byId[(string) $record->id] = $record;

            foreach ($this->entityValues($record, $fields) as $value) {
                $normalized = $this->normalizeText($value);
                if ($normalized === '') {
                    continue;
                }
                $byValue[$normalized]             = $byValue[$normalized] ?? [];
                $byValue[$normalized][$record->id] = $record;
            }
        }

        return ['by_id' => $byId, 'by_value' => $byValue];
    }

    private function entityValues($record, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[] = $record->{$field} ?? null;
        }
        if ($record->relationLoaded('translations')) {
            foreach ($record->translations as $translation) {
                foreach ($fields as $field) {
                    $values[] = $translation->{$field} ?? null;
                }
            }
        }
        return array_values(array_unique(array_filter($values)));
    }

    private function resolveEntity($value, array $lookup): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [null, null];
        }
        if (isset($lookup['by_id'][(string) $value])) {
            return [(int) $value, null];
        }
        $normalized = $this->normalizeText($value);
        $matches    = $lookup['by_value'][$normalized] ?? [];
        if (count($matches) === 1) {
            return [reset($matches)->id, null];
        }
        if (count($matches) > 1) {
            return [null, 'القيمة غير واضحة وتطابق أكثر من سجل.'];
        }
        return [null, 'القيمة غير موجودة في النظام.'];
    }

    private function resolveManyEntities($value, array $lookup, bool $required = false): array
    {
        $items  = $this->splitMultiValue($value);
        $ids    = [];
        $errors = [];

        foreach ($items as $item) {
            [$id, $error] = $this->resolveEntity($item, $lookup);
            if ($error) {
                $errors[] = "\"{$item}\" {$error}";
                continue;
            }
            if ($id !== null) {
                $ids[] = (int) $id;
            }
        }

        $ids = array_values(array_unique($ids));
        if ($required && $ids === []) {
            $errors[] = 'يجب إدخال قيمة واحدة على الأقل.';
        }

        return [$ids, $errors];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function persistRows(AgentMaidBulkImport $import, array $rows): void
    {
        foreach ($rows as $row) {
            AgentMaidBulkImportRow::create([
                'agent_maid_bulk_import_id' => $import->id,
                'row_number'                => $row['row_number'],
                'status'                    => $row['status'],
                'raw_data'                  => $row['raw_data'],
                'normalized_data'           => $row['normalized_data'],
                'error_messages'            => $row['error_messages'],
            ]);
        }
        $this->refreshCounters($import->fresh());
    }

    private function refreshCounters(AgentMaidBulkImport $import): void
    {
        $rows         = $import->rows()->get();
        $importedRows = $rows->where('status', 'imported')->count();
        $failedRows   = $rows->where('status', 'failed')->count();
        $invalidRows  = $rows->where('status', 'invalid')->count();
        $pendingRows  = $rows->where('status', 'pending')->count();

        $status = 'pending_review';
        if ($pendingRows === 0 && $invalidRows === 0 && $failedRows === 0 && $importedRows > 0) {
            $status = 'completed';
        } elseif ($importedRows > 0 || $failedRows > 0) {
            $status = 'completed_with_errors';
        }

        $import->update([
            'status'        => $status,
            'total_rows'    => $rows->count(),
            'valid_rows'    => $rows->whereIn('status', ['pending', 'imported'])->count(),
            'invalid_rows'  => $invalidRows,
            'imported_rows' => $importedRows,
            'failed_rows'   => $failedRows,
            'processed_at'  => now(),
        ]);
    }

    private function checkDuplicatePassports(array $rows): array
    {
        $groups = collect($rows)
            ->filter(fn ($r) => !empty($r['normalized_data']['passport_num']))
            ->groupBy(fn ($r) => $r['normalized_data']['passport_num']);

        foreach ($groups as $passport => $group) {
            if ($group->count() <= 1) {
                continue;
            }
            $rows = array_map(function ($row) use ($group, $passport) {
                $isInGroup = $group->contains(fn ($r) => $r['row_number'] === $row['row_number']);
                if (!$isInGroup) {
                    return $row;
                }
                $row['error_messages'][] = "رقم الجواز {$passport} مكرر داخل نفس الملف.";
                $row['error_messages']   = array_values(array_unique($row['error_messages']));
                $row['status']           = 'invalid';
                return $row;
            }, $rows);
        }

        return $rows;
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace(['ة'], 'ه', $value);
        $value = str_replace(['ى'], 'ي', $value);
        $value = preg_replace('/[\s\-_\/]+/u', '', $value) ?? '';
        return $value;
    }

    private function normalizeDate($value): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function normalizeDecimal($value, bool $twoDecimals = true): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null || !is_numeric($value)) {
            return $value;
        }
        return $twoDecimals
            ? number_format((float) $value, 2, '.', '')
            : (string) (float) $value;
    }

    private function cellValue($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = is_string($value) ? trim($value) : (string) $value;
        return $value === '' ? null : $value;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function trimmed($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function splitMultiValue($value): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [];
        }
        return collect(preg_split('/[\|\n\r,،;]+/u', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function cleanData(array $data): array
    {
        return collect($data)
            ->reject(fn ($v) => $v === null || $v === '')
            ->all();
    }

    private function extractErrors(\Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())->flatten()->values()->all();
        }
        return [$exception->getMessage()];
    }
}
