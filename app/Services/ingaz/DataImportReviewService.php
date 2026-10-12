<?php

namespace App\Services\ingaz;

use App\Models\DataImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataImportReviewService
{
    // These readers preserve multiple sheets, source IDs, or parent/child dependencies.
    public const WHOLE_FILE_TYPES = ['arabia_clients', 'alrafedin_transfer_services',
        'arabia_recruitment_orders', 'legacy_invoices', 'legacy_rental_orders',
        'finance_accounts', 'finance_cost_centers', 'finance_constraints', 'receipt_bonds', 'disbursement_bonds'];

    public function __construct(private DataImportTemplateService $templates, private DataImportExecutionService $executor)
    {
    }

    public function stage(string $type, UploadedFile $file, ?int $adminId, array $options = []): DataImportBatch
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $path = $this->templates->storeUploadedFile($type, $file, $adminId);
        try {
            return DB::transaction(function () use ($type, $path, $file, $adminId, $options) {
                DB::table('data_import_locks')->insertOrIgnore(['type' => 'all']);
                $batch = DataImportBatch::create([
                    'type' => $type, 'path' => $path, 'original_name' => $file->getClientOriginalName(),
                    'file_hash' => hash_file('sha256', Storage::path($path)),
                    'created_by' => $adminId, 'status' => 'pending', 'options' => $options,
                ]);
                $seen = [];
                foreach ($this->executor->reviewRows($type, Storage::path($path)) as $number => $payload) {
                    if (ImportValueParser::isMarkerRow($payload)) continue;
                    $resolution = app(ImportReferenceResolver::class)->resolve($type, $payload);
                    $payload = $resolution['row'];
                    $fingerprint = $this->fingerprint($type, $payload);
                    $issues = array_merge($resolution['issues'], $this->issues($type, $payload));
                    $keys = $this->identities($type, $payload);
                    foreach ($keys as $key) {
                        if (isset($seen[$key])) $issues[] = 'مكرر داخل الملف مع الصف ' . $seen[$key];
                        else $seen[$key] = (string) $number;
                    }
                    if ($this->alreadyImported($type, $fingerprint)) $issues[] = 'سبق اعتماد هذا السجل في ملف آخر.';
                    $batch->rows()->create([
                        'source_row' => (string) $number, 'payload' => $payload,
                        'fingerprint' => $fingerprint, 'status' => $issues ? 'invalid' : 'valid',
                        'issues' => array_values(array_unique($issues)),
                        'resolved_references' => $resolution['links'],
                    ]);
                }
                if (!$batch->rows()->exists()) throw ValidationException::withMessages(['file' => 'لم يتم العثور على صفوف بيانات مطابقة للقالب.']);
                if ($type === 'finance_constraints') $this->validateConstraintGroups($batch);
                return $batch;
            });
        } catch (\Throwable $exception) {
            Storage::delete($path);
            throw $exception;
        }
    }

    public function approve(DataImportBatch $batch, int $adminId): array
    {
        @set_time_limit(0);
        $temporary = null;
        try {
            return DB::transaction(function () use ($batch, $adminId, &$temporary) {
                DB::table('data_import_locks')->where('type', 'all')->lockForUpdate()->first();
                $batch = DataImportBatch::query()->lockForUpdate()->findOrFail($batch->id);
                if ($batch->status === 'approved') return $batch->result ?? [];
                if (!Storage::exists($batch->path) || !hash_equals($batch->file_hash, hash_file('sha256', Storage::path($batch->path)))) {
                    throw ValidationException::withMessages(['file' => 'تغير الملف بعد الفحص. ارفع نسخة جديدة.']);
                }
                if (!$this->requiresWholeFile($batch->type)) {
                    return \App\Support\DataImportBranchContext::run($batch->branch_id, fn () => $this->approveIndependentRows($batch, $adminId));
                }
                $rows = $batch->rows()->orderBy('id')->get();
                $failures = [];
                foreach ($rows->where('status', 'valid') as $row) {
                    $resolution = app(ImportReferenceResolver::class)->resolve($batch->type, $row->payload);
                    $issues = array_merge($resolution['issues'], $this->issues($batch->type, $resolution['row']));
                    foreach ($row->resolved_references ?? [] as $previous) {
                        $current = collect($resolution['links'])->firstWhere('label', $previous['label']);
                        if (!$current || (int) $current['id'] !== (int) $previous['id']) $issues[] = 'تغير الربط الذي تمت مراجعته؛ أعد رفع الملف لمراجعة المرجع الجديد.';
                    }
                    if ($this->alreadyImported($batch->type, $row->fingerprint)) $issues[] = 'سبق اعتماد هذا السجل بعد رفع الملف.';
                    if ($issues) {
                        $row->update(['status' => 'invalid', 'issues' => $issues]);
                        $failures[] = 'صف ' . $row->source_row . ': ' . implode('؛ ', $issues);
                    }
                }
                // Recheck results must survive this request without any business writes.
                if ($failures) return ['errors' => $failures, 'error_count' => count($failures)];
                $valid = $rows->where('status', 'valid');
                if ($valid->isEmpty()) throw ValidationException::withMessages(['file' => 'لا توجد صفوف صحيحة للاعتماد.']);
                if ($this->requiresWholeFile($batch->type) && $valid->count() !== $rows->count()) {
                    throw ValidationException::withMessages(['file' => 'هذا الملف يحتوي على بيانات مترابطة؛ صحح جميع الأخطاء وأعد رفعه قبل الاعتماد.']);
                }
                if ($this->requiresWholeFile($batch->type)) {
                    $file = new UploadedFile(Storage::path($batch->path), $batch->original_name, null, null, true);
                } else {
                    // Rebuild only the reviewed valid rows. Never execute rejected rows.
                    $temporary = tempnam(sys_get_temp_dir(), 'erp-import-');
                    $book = new Spreadsheet();
                    $sheet = $book->getActiveSheet();
                    $keys = array_keys($valid->first()->payload);
                    $keys[] = '__review_source_row';
                    $sheet->fromArray($keys, null, 'A1');
                    $line = 2;
                    foreach ($valid as $row) {
                        foreach ($keys as $column => $key) {
                            $sheet->setCellValueExplicit([$column + 1, $line], $key === '__review_source_row' ? $row->source_row : (string) ($row->payload[$key] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                        $line++;
                    }
                    (new Xlsx($book))->save($temporary);
                    $book->disconnectWorksheets();
                    $file = new UploadedFile($temporary, 'reviewed.xlsx', null, null, true);
                }
                $result = \App\Support\DataImportBranchContext::run($batch->branch_id, fn () => match ($batch->type) {
                    'arabia_clients' => app(ArabiaClientsImportService::class)->import($file, $adminId),
                    'alrafedin_transfer_services' => app(AlrafedinTransferServicesImportService::class)->import($file, $adminId),
                    'finance_constraints' => !empty($batch->options['finance_constraints_missing_only'])
                        ? $this->executor->importMissingFinanceConstraints($file, $adminId)
                        : $this->executor->import($batch->type, $file, $adminId),
                    default => $this->executor->import($batch->type, $file, $adminId),
                });
                // Existing readers catch individual errors. Escalate them to roll back the entire approval.
                if (!empty($result['error_count']) || !empty($result['errors'])) {
                    throw new ImportApprovalException($result['errors'] ?? ['تعذر تنفيذ الاستيراد.']);
                }
                $batch->rows()->where('status', 'valid')->update(['status' => 'approved', 'updated_at' => now()]);
                $batch->update(['status' => 'approved', 'approved_by' => $adminId, 'approved_at' => now(), 'result' => $result]);
                return $result;
            });
        } catch (ImportApprovalException $exception) {
            // The failed attempt commits no client, contract, invoice, or journal entry.
            foreach ($exception->errors as $message) {
                if (preg_match('/^صف\s+([^:]+):\s*(.+)$/u', $message, $match)) {
                    $batch->rows()->where('source_row', trim($match[1]))->where('status', 'valid')->update([
                        'status' => 'invalid', 'issues' => json_encode([$match[2]], JSON_UNESCAPED_UNICODE), 'updated_at' => now(),
                    ]);
                }
            }
            $batch->update(['result' => ['errors' => $exception->errors, 'error_count' => count($exception->errors)]]);
            return $batch->result;
        } finally {
            if ($temporary && is_file($temporary)) unlink($temporary);
        }
    }

    public function requiresWholeFile(string $type): bool { return in_array($type, self::WHOLE_FILE_TYPES, true); }

    private function approveIndependentRows(DataImportBatch $batch, int $adminId): array
    {
        $result = ['total' => $batch->rows()->count(), 'created' => 0, 'updated' => 0, 'approved_this_attempt' => 0];
        foreach ($batch->rows()->where('status', 'valid')->orderBy('id')->get() as $row) {
            try {
                $outcome = DB::transaction(function () use ($batch, $row, $adminId) {
                    $resolution = app(ImportReferenceResolver::class)->resolve($batch->type, $row->payload);
                    $issues = array_merge($resolution['issues'], $this->issues($batch->type, $resolution['row']));
                    foreach ($row->resolved_references ?? [] as $previous) {
                        $current = collect($resolution['links'])->firstWhere('label', $previous['label']);
                        if (!$current || (int) $current['id'] !== (int) $previous['id']) $issues[] = 'تغير الربط الذي تمت مراجعته؛ أعد رفع الصف لمراجعة المرجع الجديد.';
                    }
                    if ($this->alreadyImported($batch->type, $row->fingerprint)) $issues[] = 'سبق اعتماد هذا السجل في ملف آخر.';
                    if ($issues) throw ValidationException::withMessages(['row' => array_values(array_unique($issues))]);
                    $outcome = $this->executor->importReviewedRow($batch->type, $resolution['row'], $adminId);
                    $row->update(['status' => 'approved', 'issues' => [], 'resolved_references' => $resolution['links']]);
                    return $outcome;
                });
                $result[$outcome]++;
                $result['approved_this_attempt']++;
            } catch (\Throwable $exception) {
                $issues = $exception instanceof ValidationException
                    ? \Illuminate\Support\Arr::flatten($exception->errors()) : [$exception->getMessage()];
                $row->update(['status' => 'invalid', 'issues' => array_values(array_unique($issues))]);
            }
        }
        $failed = $batch->rows()->where('status', 'invalid')->orderBy('id')->get();
        $result['errors'] = $failed->map(fn ($row) => 'صف '.$row->source_row.': '.implode('؛ ', $row->issues))->all();
        $result['error_count'] = $failed->count();
        $result['approved_count'] = $batch->rows()->where('status', 'approved')->count();
        $result['skipped'] = $result['error_count'];
        $batch->update(['status' => $result['error_count'] ? ($result['approved_count'] ? 'partial' : 'pending') : 'approved',
            'approved_by' => $adminId, 'approved_at' => $result['approved_count'] ? now() : null, 'result' => $result]);
        return $result;
    }

    public function issues(string $type, array $row): array
    {
        $issues = $row['__parse_issues'] ?? [];
        if ($type === 'arabia_recruitment_orders') {
            foreach ($row as $key => $value) {
                if (blank($value) || trim((string) $value) === '-') continue;
                try {
                    if ((str_contains($key, 'تاريخ') || str_contains($key, 'موعد الوصول')) && !str_contains($key, 'هجري') && !str_contains($key, 'هـ')) {
                        ImportValueParser::date($value, true);
                    }
                } catch (\InvalidArgumentException $exception) { $issues[] = $key . ': ' . $exception->getMessage(); }
            }
            if (($row['__sheet'] ?? '') !== 'تقرير_الطلبات') return $issues;
        }
        foreach ($this->templates->template($type)['columns'] ?? [] as $column) {
            $key = $column['key'];
            $value = $row[$key] ?? null;
            if ($column['required'] && blank($value)) {
                $issues[] = $column['label'] . ': مطلوب أو العمود غير موجود.';
                continue;
            }
            if (blank($value)) continue;
            if (str_starts_with(trim((string) $value), '=')) {
                $issues[] = $column['label'] . ': استخدم قيمة ثابتة بدلاً من معادلة.';
                continue;
            }
            if (!empty($column['valid_values']) && !in_array((string) $value, array_map('strval', $column['valid_values']), true)) {
                $issues[] = $column['label'] . ': القيمة غير مقبولة. القيم: ' . implode('، ', $column['valid_values']);
            }
            try {
                if ($this->isDate($key)) ImportValueParser::date($value, true);
                elseif ($this->isNumber($key)) {
                    $number = ImportValueParser::decimal($value);
                    if ((str_ends_with($key, '_count') || in_array($key, ['level', 'duration_months', 'contract_duration_months', 'trial_days'], true)) && floor($number) !== $number) {
                        $issues[] = $column['label'] . ': يجب أن تكون القيمة عدداً صحيحاً.';
                    }
                }
            } catch (\InvalidArgumentException $exception) {
                $issues[] = $column['label'] . ': ' . $exception->getMessage();
            }
            if (str_contains($key, 'email') && !filter_var($value, FILTER_VALIDATE_EMAIL)) $issues[] = $column['label'] . ': بريد غير صحيح.';
        }
        $issues = array_merge($issues, $this->executor->validateReviewRow($type, $row));
        if ($type === 'invoices') $issues = array_merge($issues, $this->invoiceIssues($row));
        if ($type === 'legacy_invoices') {
            try {
                $subtotal = ImportValueParser::decimal($row['subtotal'] ?? null);
                $vat = ImportValueParser::decimal($row['vat_amount'] ?? null) ?? 0;
                $total = ImportValueParser::decimal($row['total_due'] ?? null);
                if ($subtotal !== null && $total !== null && abs($subtotal + $vat - $total) > 0.011) $issues[] = 'الإجمالي لا يساوي المبلغ قبل الضريبة مضافاً إليه الضريبة.';
            } catch (\InvalidArgumentException $exception) { $issues[] = $exception->getMessage(); }
        }
        foreach ($this->existingRecords($type, $row) as $issue) $issues[] = $issue;
        return array_values(array_unique($issues));
    }

    private function isDate(string $key): bool
    {
        return !str_contains($key, 'hijri') && (str_contains($key, 'date') || in_array($key, ['created_at', 'birthdate'], true));
    }

    private function isNumber(string $key): bool
    {
        if (in_array($key, ['passport_num', 'national_num', 'account_num'], true)) return false;
        return (bool) preg_match('/(?:^|_)(?:amount|cost|total|subtotal|discount|debit|credit|balance|salary|quantity|count|days|months|num)(?:_usd)?$/', $key)
            || in_array($key, ['total_due', 'debtor_value', 'creditor_value', 'duration', 'level', 'rooms_num', 'family_num', 'children', 'age', 'height', 'weight', 'rental_price'], true);
    }

    private function validateConstraintGroups(DataImportBatch $batch): void
    {
        foreach ($batch->rows()->get()->groupBy(fn ($row) => $row->payload['constraint_ref'] ?? '') as $reference => $rows) {
            $issues = [];
            $debit = $credit = 0.0;
            $dates = [];
            foreach ($rows as $row) {
                try {
                    $line = $this->executor->reviewConstraintLine($row->payload);
                    $debit += $line['debtor_value'];
                    $credit += $line['creditor_value'];
                    $dates[] = ImportValueParser::date($row->payload['date'] ?? null)?->toDateString();
                } catch (\InvalidArgumentException $exception) { $issues[] = $exception->getMessage(); }
            }
            if ($rows->count() < 2) $issues[] = 'القيد يحتاج طرفين على الأقل.';
            if (count(array_unique($dates)) > 1) $issues[] = 'تواريخ أطراف القيد غير متطابقة.';
            if (round($debit, 2) !== round($credit, 2)) $issues[] = 'القيد غير متوازن: المدين ' . $debit . ' والدائن ' . $credit;
            if ($issues) foreach ($rows as $row) $row->update(['status' => 'invalid', 'issues' => array_values(array_unique(array_merge($row->issues, $issues)))]);
        }
    }

    private function invoiceIssues(array $row): array
    {
        $issues = [];
        $references = array_filter(['contract_id', 'ingaz_delegation_id', 'client_id'], fn ($key) => filled($row[$key] ?? null));
        if (count($references) !== 1) return ['يجب اختيار عقد أو تفويض أو عميل واحد فقط.'];
        $key = reset($references);
        $model = ['contract_id' => \App\Models\Contract::class, 'ingaz_delegation_id' => \App\Models\IngazDelegation::class,
            'client_id' => \App\Models\ingaz\User::class][$key];
        $reference = $model::query()->find($row[$key]);
        if (!$reference) $issues[] = 'مرجع الفاتورة غير موجود أو خارج الفروع المتاحة.';
        if ($key === 'contract_id' && $reference && !$reference->isInvoiceEligible()) $issues[] = 'العقد غير مؤهل لإصدار فاتورة.';
        if ($key !== 'client_id') {
            $existingInvoices = \App\Models\ingaz\Invoice::query()->where($key, $row[$key]);
            if ($key === 'contract_id') $existingInvoices->blockingReplacement();
            if ($existingInvoices->exists()) $issues[] = $key === 'contract_id'
                ? 'توجد فاتورة سابقة لهذا العقد لم تتم تغطيتها بالكامل بإشعار دائن.'
                : 'توجد فاتورة سابقة لهذا المرجع.';
        }
        if ($key !== 'client_id') {
            try {
                if ((ImportValueParser::decimal($row['external_recruitment_cost'] ?? null) ?? 0) <= 0) $issues[] = 'قيمة الاستقدام الخارجي مطلوبة وأكبر من صفر.';
            } catch (\InvalidArgumentException $exception) { $issues[] = $exception->getMessage(); }
        }
        foreach ($row as $field => $value) {
            if (filled($value) && ($this->isNumber($field) || str_ends_with($field, '_cost'))) {
                try {
                    if (ImportValueParser::decimal($value) < 0) $issues[] = $field . ': لا تقبل قيمة سالبة.';
                } catch (\InvalidArgumentException $exception) { $issues[] = $exception->getMessage(); }
            }
        }
        return $issues;
    }

    private function identities(string $type, array $row): array
    {
        if ($type === 'finance_constraints') {
            // The source journal reference identifies the document even if its amounts change.
            // Include the line identity so legitimate debit/credit lines are not treated as duplicates.
            $reference = $row['code'] ?? '';
            if (blank($reference)) $reference = $row['constraint_ref'] ?? '';
            $account = $row['account_id'] ?? '';
            if (blank($account)) $account = $row['account_code'] ?? '';
            return ['journal-line:' . $reference . ':' . $account . ':' . ($row['line_type'] ?? '') . ':'
                . ($row['cost_center_id'] ?? $row['cost_center_code'] ?? '') . ':' . ($row['line_statement'] ?? '') . ':'
                . ($row['amount'] ?? '') . ':' . ($row['debtor_value'] ?? '') . ':' . ($row['creditor_value'] ?? '')];
        }
        if ($type === 'arabia_recruitment_orders' && ($row['__sheet'] ?? '') !== 'تقرير_الطلبات') {
            return ['auxiliary:' . hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE))];
        }
        $fields = match ($type) {
            'musaned_transfers' => ['reference_number', 'contract_id', 'ingaz_delegation_id'],
            'external_office_dispatches' => ['contract_id'],
            'clients' => ['id_number', 'phone'], 'arabia_clients' => ['arabia_client_number', 'id_number', 'phone'],
            'rental_maids' => ['passport_num'], 'invoices' => ['import_reference', 'contract_id', 'ingaz_delegation_id'],
            'legacy_invoices' => ['invoice_number'], 'finance_accounts' => ['account_code'],
            'finance_cost_centers' => ['cost_center_code'], 'receipt_bonds', 'disbursement_bonds' => ['bond_code'],
            'recruitment_contracts', 'recruitment_contracts_clients_maids' => ['contract_number'],
            'rent_contracts' => ['contract_number'], 'rent_contracts_clients_rental_maids' => ['rent_contract_number'],
            'arabia_recruitment_orders' => ['arabia_order_number'], 'legacy_rental_orders' => ['legacy_order_id'],
            'admins' => ['email', 'phone'], 'employees' => ['code', 'national_number', 'email'],
            'sponsorship_transfers' => ['request_number'], 'alrafedin_transfer_services' => ['operation_number'],
            default => [],
        };
        $keys = [];
        foreach ($fields as $field) {
            if (filled($row[$field] ?? null)) $keys[] = $field . ':' . $this->normalizeIdentity($field, $row[$field]);
        }
        // Journal lines legitimately share a reference, but an identical line is suspicious.
        return $keys ?: ['row:' . hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE))];
    }

    private function fingerprint(string $type, array $row): string
    {
        if ($type === 'finance_constraints') {
            $reference = filled($row['code'] ?? null) ? $row['code'] : ($row['constraint_ref'] ?? '');
            return hash('sha256', $type . ':journal:' . ImportValueParser::digits($reference));
        }
        $keys = $this->identities($type, $row);
        return hash('sha256', $type . ':' . $keys[0]);
    }

    private function normalizeIdentity(string $field, $value): string
    {
        $value = mb_strtoupper(ImportValueParser::digits($value));
        if (str_contains($field, 'phone')) {
            $value = preg_replace('/\D/', '', $value);
            $value = preg_replace('/^(?:00966|966|0)/', '', $value);
        }
        return preg_replace('/\s+/', '', $value);
    }

    private function alreadyImported(string $type, string $fingerprint): bool
    {
        return DB::table('data_import_rows as r')->join('data_import_batches as b', 'b.id', '=', 'r.batch_id')
            ->where('b.type', $type)->whereIn('b.status', ['approved', 'partial'])->where('r.status', 'approved')->where('r.fingerprint', $fingerprint)->exists();
    }

    private function existingRecords(string $type, array $row): array
    {
        $checks = match ($type) {
            'clients' => [['users', 'national_num', 'id_number'], ['users', 'id', 'id']],
            'arabia_clients' => [['users', 'national_num', 'id_number'], ['users', 'arabia_client_number', 'arabia_client_number']],
            'rental_maids' => [['maids', 'passport_num', 'passport_num']],
            'legacy_invoices' => [['invoices', 'id', 'invoice_number'], ['invoices', 'legacy_invoice_number', 'invoice_number']],
            'arabia_recruitment_orders' => [['contracts', 'arabia_order_number', 'arabia_order_number']],
            'finance_accounts' => [['fn_accounts', 'code', 'account_code']],
            'finance_constraints' => [['fn_constraints', 'code', 'code']],
            'recruitment_contracts', 'recruitment_contracts_clients_maids' => [['contracts', 'id', 'contract_number']],
            'rent_contracts' => [['rental_contracts', 'contract_number', 'contract_number']],
            'rent_contracts_clients_rental_maids' => [['rental_contracts', 'contract_number', 'rent_contract_number']],
            default => [],
        };
        foreach ($checks as [$table, $column, $key]) {
            if (filled($row[$key] ?? null) && \Illuminate\Support\Facades\Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $row[$key])->exists()) {
                return ['السجل موجود بالفعل في النظام (' . $key . '). لا ينشئ الاستيراد نسخة مكررة.'];
            }
        }
        if (in_array($type, ['clients', 'arabia_clients'], true) && filled($row['phone'] ?? null)) {
            $phone = $this->normalizeIdentity('phone', $row['phone']);
            if (DB::table('users')->whereIn('phone', [$phone, '0' . $phone, '966' . $phone, '+966' . $phone, '00966' . $phone])->exists()) {
                return ['الجوال موجود بالفعل؛ راجع هوية العميل قبل الاستيراد.'];
            }
        }
        return [];
    }
}
