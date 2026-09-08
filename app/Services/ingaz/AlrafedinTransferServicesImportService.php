<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\SponsorshipTransfer;
use App\Models\TransferServiceContract;
use App\Models\auth\Admin;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidExternalReference;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AlrafedinTransferServicesImportService
{
    private const MAX_ERROR_DETAILS = 200;

    public function __construct(private DataImportTemplateService $templates)
    {
    }

    public function import(UploadedFile $file, ?int $adminId): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $storedPath = $this->templates->storeUploadedFile('alrafedin_transfer_services', $file, $adminId);
        $rows = $this->parseReport(Storage::path($storedPath));
        $result = [
            'path' => $storedPath,
            'total' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'error_count' => 0,
            'legacy_sponsorship' => 0,
            'transfer_service' => 0,
        ];

        foreach ($rows as $row) {
            $result[$row['source']]++;

            try {
                $outcome = DB::transaction(fn () => $this->importRow($row, $adminId));
                $result[$outcome]++;
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $operation = $row['data']['operation_number'] ?? '-';
                    $result['errors'][] = "عملية {$operation}: {$exception->getMessage()}";
                }
            }
        }

        return $result;
    }

    /**
     * Parse the HTML-based XLS exports produced by the legacy Alrafedin/Arabia system.
     * Both the combined report and the standalone transfer-service report are supported.
     */
    public function parseReport(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            throw new \InvalidArgumentException('ملف نقل الخدمات فارغ أو لا يمكن قراءته.');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">' . ltrim($contents, "\xEF\xBB\xBF"),
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new \InvalidArgumentException('صيغة تقرير نقل الخدمات غير مدعومة.');
        }

        $xpath = new DOMXPath($document);
        $parsed = [];
        $sourceRow = 0;

        foreach ($xpath->query('//table') as $table) {
            $rows = $xpath->query('.//tr', $table);
            if (!$rows || $rows->length < 2) {
                continue;
            }

            $headers = [];
            $headerIndex = null;
            foreach ($rows as $index => $tr) {
                $cells = $this->rowCells($xpath, $tr);
                $normalized = array_map(fn ($cell) => $this->normalizeHeader($cell['text']), $cells);
                if (in_array('رقم العمليه', $normalized, true)
                    && (in_array('رقم العامل', $normalized, true) || in_array('جواز سفر العامل', $normalized, true))) {
                    $headers = array_column($cells, 'text');
                    $headerIndex = $index;
                    break;
                }
            }

            if ($headerIndex === null) {
                continue;
            }

            $source = $this->detectSource($headers);
            foreach ($rows as $index => $tr) {
                if ($index <= $headerIndex) {
                    continue;
                }

                $cells = $this->rowCells($xpath, $tr);
                if ($cells === [] || trim((string) ($cells[1]['text'] ?? '')) === '') {
                    continue;
                }

                $sourceRow++;
                $raw = [];
                foreach ($headers as $column => $header) {
                    $raw[$this->cleanText($header)] = $cells[$column]['text'] ?? null;
                }

                $data = $source === 'transfer_service'
                    ? $this->mapTransferServiceRow($cells)
                    : $this->mapLegacySponsorshipRow($cells);

                $data['trial_days'] = $this->trialDaysFromCell($cells[20] ?? null);
                if (trim((string) ($data['operation_number'] ?? '')) === '') {
                    continue;
                }

                $parsed[] = [
                    'source' => $source,
                    'row_number' => $sourceRow,
                    'data' => $data,
                    'raw' => $raw,
                ];
            }
        }

        if ($parsed === []) {
            throw new \InvalidArgumentException('لم يتم العثور على جدول نقل الخدمات داخل الملف.');
        }

        return $parsed;
    }

    /**
     * Read-only readiness check used before importing a legacy report.
     */
    public function auditReport(string $path): array
    {
        $rows = $this->parseReport($path);
        $stats = [
            'rows' => count($rows),
            'already_imported' => 0,
            'missing_old_client' => 0,
            'missing_new_client' => 0,
            'missing_maid' => 0,
            'missing_recruitment_contract' => 0,
            'examples' => [],
        ];

        foreach ($rows as $row) {
            $data = $row['data'];
            $oldClient = $this->findExistingClient($data['old_client_number'] ?? null);
            $newClient = $this->findExistingClient($data['new_client_number'] ?? null);
            $maid = $this->findExistingMaid($data);
            $contract = $maid ? Contract::query()->where('maid_id', $maid->id)->latest('id')->first() : null;
            $alreadyImported = Schema::hasColumn('transfer_service_contracts', 'external_source')
                && TransferServiceContract::query()
                    ->where('external_source', $row['source'])
                    ->where('external_operation_number', $data['operation_number'])
                    ->exists();

            $stats['already_imported'] += (int) $alreadyImported;
            $stats['missing_old_client'] += (int) !$oldClient;
            $stats['missing_new_client'] += (int) !$newClient;
            $stats['missing_maid'] += (int) !$maid;
            $stats['missing_recruitment_contract'] += (int) !$contract;

            if ((!$oldClient || !$newClient || !$maid || !$contract) && count($stats['examples']) < 20) {
                $stats['examples'][] = [
                    'source' => $row['source'],
                    'operation_number' => $data['operation_number'],
                    'old_client_number' => $data['old_client_number'] ?? null,
                    'new_client_number' => $data['new_client_number'] ?? null,
                    'worker_number' => $data['worker_number'] ?? null,
                    'passport' => $data['worker_passport'] ?? null,
                    'missing' => array_keys(array_filter([
                        'old_client' => !$oldClient,
                        'new_client' => !$newClient,
                        'maid' => !$maid,
                        'recruitment_contract' => !$contract,
                    ])),
                ];
            }
        }

        return $stats;
    }

    private function rowCells(DOMXPath $xpath, \DOMNode $row): array
    {
        $cells = [];
        foreach ($xpath->query('./th|./td', $row) as $cell) {
            $title = null;
            if ($cell instanceof DOMElement) {
                $titleNode = $xpath->query('.//*[@title]', $cell)?->item(0);
                if ($titleNode instanceof DOMElement) {
                    $title = $titleNode->getAttribute('title');
                }
            }

            $cells[] = [
                'text' => $this->cleanText($cell->textContent),
                'title' => $this->cleanText($title),
            ];
        }

        return $cells;
    }

    private function detectSource(array $headers): string
    {
        $normalized = array_map(fn ($header) => $this->normalizeHeader($header), $headers);

        return in_array('التكلفه الاجماليه', $normalized, true)
            || in_array('رقم تعريف عميل من', $normalized, true)
                ? 'transfer_service'
                : 'legacy_sponsorship';
    }

    private function mapLegacySponsorshipRow(array $cells): array
    {
        return [
            'operation_number' => $this->textAt($cells, 1),
            'worker_number' => $this->integer($this->textAt($cells, 3)),
            'worker_name' => $this->textAt($cells, 4),
            'worker_passport' => $this->textAt($cells, 5),
            'old_client_number' => $this->integer($this->textAt($cells, 6)),
            'old_client_name' => $this->textAt($cells, 7),
            'new_client_number' => $this->integer($this->textAt($cells, 8)),
            'new_client_name' => $this->textAt($cells, 9),
            'nationality' => $this->textAt($cells, 10),
            'job_title' => $this->textAt($cells, 11),
            'supplier' => $this->textAt($cells, 12),
            'transfer_date' => $this->date($this->textAt($cells, 13)),
            'transfer_date_hijri' => $this->textAt($cells, 14),
            'total_cost' => $this->decimal($this->textAt($cells, 15)),
            'source_created_by' => $this->textAt($cells, 16),
            'source_status' => $this->textAt($cells, 2),
        ];
    }

    private function mapTransferServiceRow(array $cells): array
    {
        return [
            'operation_number' => $this->textAt($cells, 1),
            'worker_number' => $this->integer($this->textAt($cells, 2)),
            'worker_name' => $this->textAt($cells, 3),
            'worker_passport' => $this->textAt($cells, 4),
            'old_client_number' => $this->integer($this->textAt($cells, 5)),
            'old_client_name' => $this->textAt($cells, 6),
            'new_client_number' => $this->integer($this->textAt($cells, 7)),
            'new_client_name' => $this->textAt($cells, 8),
            'nationality' => $this->textAt($cells, 9),
            'job_title' => $this->textAt($cells, 10),
            'invoice_number' => $this->integer($this->textAt($cells, 11)),
            'total_cost' => $this->decimal($this->textAt($cells, 12)),
            'trial_cost' => $this->decimal($this->textAt($cells, 13)),
            'old_sponsor_compensation_type' => $this->textAt($cells, 14),
            'old_sponsor_compensation_amount' => $this->decimal($this->textAt($cells, 15)),
            'transfer_date' => $this->date($this->textAt($cells, 16)),
            'worker_replacement_status' => $this->textAt($cells, 17),
            'profession_change_status' => $this->textAt($cells, 18),
            'source_status' => $this->textAt($cells, 19),
            'trial_status' => $this->textAt($cells, 20),
            'musaned_status' => $this->textAt($cells, 21),
            'conversion_status' => $this->textAt($cells, 22),
            'commission_amount' => $this->decimal($this->textAt($cells, 23)),
            'refundable_tax_amount' => $this->decimal($this->textAt($cells, 24)),
            'conversion_date' => $this->date($this->textAt($cells, 25)),
            'arrival_warranty_status' => $this->textAt($cells, 26),
            'delivery_warranty_status' => $this->textAt($cells, 27),
            'source_created_by' => $this->textAt($cells, 28),
            'source_created_at' => $this->dateTime($this->textAt($cells, 29)),
            'source_marketer' => $this->textAt($cells, 30),
        ];
    }

    private function importRow(array $row, ?int $fallbackAdminId): string
    {
        $data = $row['data'];
        $source = $row['source'];
        $operation = trim((string) $data['operation_number']);
        $adminId = $this->resolveAdminId($data['source_created_by'] ?? null, $fallbackAdminId);
        $maid = $this->resolveMaid($data, $adminId);
        $oldSponsor = $this->resolveClient(
            $data['old_client_number'] ?? null,
            $data['old_client_name'] ?? null
        );
        $newSponsor = $this->resolveClient(
            $data['new_client_number'] ?? null,
            $data['new_client_name'] ?? null
        );
        $contract = $this->resolveRecruitmentContract($maid, $oldSponsor, $data, $adminId, $source, $operation);
        $transferDate = $data['transfer_date'] ?: ($data['source_created_at'] ?? null) ?: now()->toDateString();
        $amount = $data['total_cost'] ?? 0.0;
        $trialDays = max(0, (int) ($data['trial_days'] ?? 0));

        $transfer = SponsorshipTransfer::query()
            ->where('external_source', $source)
            ->where('external_operation_number', $operation)
            ->first();

        if (!$transfer) {
            $transfer = SponsorshipTransfer::query()
                // Only adopt an untagged pre-existing row. Imported operations must
                // remain one-to-one even when the same worker/client/date repeats.
                ->whereNull('external_source')
                ->whereNull('external_operation_number')
                ->where('contract_id', $contract->id)
                ->where('old_sponsor_id', $oldSponsor->id)
                ->where('new_sponsor_id', $newSponsor->id)
                ->whereDate('transferred_at', Carbon::parse($transferDate)->toDateString())
                ->first();
        }

        $transferPayload = [
            'external_source' => $source,
            'external_operation_number' => $operation,
            'contract_id' => $contract->id,
            'old_sponsor_id' => $oldSponsor->id,
            'new_sponsor_id' => $newSponsor->id,
            'trial_days' => $trialDays,
            'trial_start_date' => Carbon::parse($transferDate)->toDateString(),
            'contract_value' => $amount,
            'paid_amount' => $amount,
            'remaining_amount' => 0,
            'payment_type' => 'legacy_import',
            'payment_status' => 'paid',
            'payment_confirmed_at' => $transferDate,
            'payment_confirmed_by' => $adminId,
            'notes' => $this->importNotes($source, $operation, $data),
            'status' => 'transferred',
            'approved_by' => $adminId,
            'approved_at' => $transferDate,
            'trial_started_at' => $transferDate,
            'transferred_at' => $transferDate,
            'transferred_by' => $adminId,
        ];

        $transfer
            ? $transfer->fill($transferPayload)->save()
            : $transfer = SponsorshipTransfer::query()->create($transferPayload);

        $serviceContract = TransferServiceContract::query()
            ->where('external_source', $source)
            ->where('external_operation_number', $operation)
            ->first();
        $created = !$serviceContract;

        $servicePayload = [
            'external_source' => $source,
            'external_operation_number' => $operation,
            'legacy_worker_number' => $data['worker_number'] ?? null,
            'legacy_invoice_number' => $data['invoice_number'] ?? null,
            'trial_cost' => $data['trial_cost'] ?? null,
            'old_sponsor_compensation_type' => $data['old_sponsor_compensation_type'] ?? null,
            'old_sponsor_compensation_amount' => $data['old_sponsor_compensation_amount'] ?? null,
            'worker_replacement_status' => $data['worker_replacement_status'] ?? null,
            'profession_change_status' => $data['profession_change_status'] ?? null,
            'legacy_status' => $data['source_status'] ?? null,
            'legacy_trial_status' => $data['trial_status'] ?? null,
            'musaned_status' => $data['musaned_status'] ?? null,
            'conversion_status' => $data['conversion_status'] ?? null,
            'commission_amount' => $data['commission_amount'] ?? null,
            'refundable_tax_amount' => $data['refundable_tax_amount'] ?? null,
            'conversion_date' => $data['conversion_date'] ?? null,
            'arrival_warranty_status' => $data['arrival_warranty_status'] ?? null,
            'delivery_warranty_status' => $data['delivery_warranty_status'] ?? null,
            'source_created_by' => $data['source_created_by'] ?? null,
            'source_marketer' => $data['source_marketer'] ?? null,
            'source_created_at' => $data['source_created_at'] ?? null,
            'legacy_payload' => $row['raw'],
            'sponsorship_transfer_id' => $transfer->id,
            'contract_id' => $contract->id,
            'old_sponsor_id' => $oldSponsor->id,
            'new_sponsor_id' => $newSponsor->id,
            'maid_id' => $maid->id,
            'contract_number' => 'AR-' . ($source === 'transfer_service' ? 'TS-' : 'ST-') . $operation,
            'contract_value' => $amount,
            'paid_amount' => $amount,
            'remaining_amount' => 0,
            'trial_days' => $trialDays,
            'trial_start_date' => Carbon::parse($transferDate)->toDateString(),
            'contract_start_date' => Carbon::parse($transferDate)->toDateString(),
            'notes' => $this->importNotes($source, $operation, $data),
            'status' => 'completed',
            'activated_at' => $transferDate,
            'completed_at' => $transferDate,
            'created_by' => $adminId,
            'activated_by' => $adminId,
            'completed_by' => $adminId,
        ];

        $serviceContract
            ? $serviceContract->fill($servicePayload)->save()
            : TransferServiceContract::query()->create($servicePayload);

        $maid->forceFill(['available' => 0, 'status' => 'contracted'])->save();

        return $created ? 'created' : 'updated';
    }

    private function resolveMaid(array $data, int $adminId): Maid
    {
        $workerNumber = $this->integer($data['worker_number'] ?? null);
        $passport = strtoupper(preg_replace('/\s+/u', '', trim((string) ($data['worker_passport'] ?? ''))));
        $maid = $this->findExistingMaid($data);

        $payload = array_filter([
            'arabia_worker_number' => $workerNumber,
            'name' => trim((string) ($data['worker_name'] ?? '')) ?: 'ARABIA WORKER ' . ($workerNumber ?: $passport),
            'passport_num' => $passport ?: null,
            'nationality_id' => $this->resolveTranslatedModelId(Nationality::class, $data['nationality'] ?? null),
            'status' => 'contracted',
            'available' => 0,
            'added_by' => $adminId,
            'request_type' => Maid::REQUEST_TYPE_SPECIFIC,
            'is_show' => 0,
        ], fn ($value) => $value !== null && $value !== '');

        if ($maid) {
            if ($maid->trashed()) {
                $maid->restore();
            }
            $maid->fill($payload)->save();
        } else {
            $maid = Maid::query()->create($payload);
        }

        if ($workerNumber && Schema::hasTable('maid_external_references')) {
            MaidExternalReference::query()->updateOrCreate(
                ['source' => 'arabia', 'external_id' => (string) $workerNumber],
                ['maid_id' => $maid->id]
            );
        }

        $jobTypeId = $maid->jobTypes()->value('job_types.id') ?: JobType::query()->value('id');
        if ($jobTypeId) {
            $maid->jobTypes()->syncWithoutDetaching([$jobTypeId]);
        }
        $jobTitleId = $this->resolveTranslatedModelId(JobTitle::class, $data['job_title'] ?? null);
        if ($jobTitleId) {
            $maid->jobTitles()->syncWithoutDetaching([$jobTitleId]);
        }

        return $maid;
    }

    private function resolveClient(?int $externalNumber, ?string $name): User
    {
        $client = $this->findExistingClient($externalNumber);

        if ($client) {
            if ($externalNumber && Schema::hasColumn('users', 'arabia_client_number') && !$client->arabia_client_number) {
                $conflict = User::query()->where('arabia_client_number', $externalNumber)->exists();
                if (!$conflict) {
                    $client->forceFill(['arabia_client_number' => $externalNumber])->save();
                }
            }

            return $client;
        }

        if (!$externalNumber && trim((string) $name) === '') {
            throw new \InvalidArgumentException('تعذر تحديد العميل القديم أو الجديد.');
        }

        return User::query()->create(array_filter([
            'arabia_client_number' => $externalNumber,
            'name' => trim((string) $name) ?: 'عميل أربيا ' . $externalNumber,
            'phone_code' => '+966',
            'phone' => 'arabia-transfer-client-' . ($externalNumber ?: substr(sha1((string) $name), 0, 12)),
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'register_from' => 'alrafedin_transfer_import',
            'is_blocked' => 0,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function findExistingClient(?int $externalNumber): ?User
    {
        if (!$externalNumber) {
            return null;
        }

        $client = Schema::hasColumn('users', 'arabia_client_number')
            ? User::query()->where('arabia_client_number', $externalNumber)->first()
            : null;

        return $client ?: User::query()->find($externalNumber);
    }

    private function findExistingMaid(array $data): ?Maid
    {
        $workerNumber = $this->integer($data['worker_number'] ?? null);
        $passport = strtoupper(preg_replace('/\s+/u', '', trim((string) ($data['worker_passport'] ?? ''))));
        $maid = null;

        if ($workerNumber && Schema::hasTable('maid_external_references')) {
            $maid = MaidExternalReference::query()
                ->where('source', 'arabia')
                ->where('external_id', (string) $workerNumber)
                ->first()?->maid;
        }
        if (!$maid && $workerNumber && Schema::hasColumn('maids', 'arabia_worker_number')) {
            $maid = Maid::withTrashed()->where('arabia_worker_number', $workerNumber)->first();
        }
        if (!$maid && $passport !== '') {
            $maid = Maid::withTrashed()
                ->whereRaw('UPPER(REPLACE(passport_num, " ", "")) = ?', [$passport])
                ->first();
        }
        if (!$maid && $workerNumber) {
            $maid = Maid::withTrashed()->find($workerNumber);
        }

        return $maid;
    }

    private function resolveRecruitmentContract(
        Maid $maid,
        User $oldSponsor,
        array $data,
        int $adminId,
        string $source,
        string $operation
    ): Contract {
        $contract = Contract::query()
            ->where('maid_id', $maid->id)
            ->where('user_id', $oldSponsor->id)
            ->latest('id')
            ->first()
            ?: Contract::query()->where('maid_id', $maid->id)->latest('id')->first();

        $contractOwner = $contract?->user ?: $oldSponsor;
        $order = $contract?->order;
        if (!$order) {
            $jobTypeId = $maid->jobTypes()->value('job_types.id') ?: JobType::query()->value('id');
            if (!$jobTypeId) {
                throw new \InvalidArgumentException('لا يوجد نوع سيرة لربط طلب الاستقدام.');
            }

            $order = Order::query()->create([
                'maid_id' => $maid->id,
                'user_id' => $contractOwner->id,
                'payment_method' => 'cash',
                'order_code' => $this->uniqueOrderCode($source, $operation),
                'job_type_id' => $jobTypeId,
                'status' => OrderEnum::Completed->value,
                'customer_id' => $adminId,
            ]);
        }

        if (!$contract) {
            $contract = Contract::query()->create([
                'user_id' => $oldSponsor->id,
                'maid_id' => $maid->id,
                'order_id' => $order->id,
                'marketer_id' => $adminId,
                'status' => ContractStatusEnum::Completed->value,
                'contract_number' => 'AR-REC-' . ($source === 'transfer_service' ? 'TS-' : 'ST-') . $operation,
                'contract_date' => $data['transfer_date'] ?: now()->toDateString(),
                'type' => 'recruitment',
            ]);
        } elseif (!$contract->order_id) {
            $contract->forceFill(['order_id' => $order->id])->save();
        }

        return $contract;
    }

    private function uniqueOrderCode(string $source, string $operation): string
    {
        $base = 'AR-' . ($source === 'transfer_service' ? 'TS-' : 'ST-') . $operation;
        if (!Order::query()->where('order_code', $base)->exists()) {
            return $base;
        }

        return $base . '-' . substr(sha1($source . $operation . microtime(true)), 0, 8);
    }

    private function resolveAdminId($value, ?int $fallback): int
    {
        $value = trim((string) $value);
        $admin = $value !== ''
            ? Admin::query()->where('name', $value)->orWhere('email', $value)->first()
            : null;

        return (int) ($admin?->id ?: $fallback ?: Admin::query()->value('id') ?: 1);
    }

    private function resolveTranslatedModelId(string $model, $value): ?int
    {
        $needle = $this->normalizeLookup($value);
        if ($needle === '') {
            return null;
        }

        foreach ($model::query()->get() as $record) {
            foreach ($record->translations as $translation) {
                $candidate = $this->normalizeLookup($translation->title ?? null);
                if ($candidate === $needle || str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
                    return (int) $record->id;
                }
            }
        }

        return null;
    }

    private function importNotes(string $source, string $operation, array $data): string
    {
        $type = $source === 'transfer_service' ? 'نقل الخدمات' : 'نقل الخدمات السابق';
        $status = trim((string) ($data['source_status'] ?? ''));

        return "مستورد من تقرير الرافدين ({$type})، رقم العملية {$operation}."
            . ($status !== '' ? " الحالة الأصلية: {$status}." : '')
            . ' سُجل كمكتمل لأن العمليات منقولة فعلياً.';
    }

    private function trialDaysFromCell(?array $cell): int
    {
        $value = trim((string) (($cell['title'] ?? null) ?: ($cell['text'] ?? null)));
        if (preg_match('/مدة\s*التجربة\s*:\s*(\d+)/u', $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function textAt(array $cells, int $index): ?string
    {
        $value = trim((string) ($cells[$index]['text'] ?? ''));

        return $value === '' ? null : $value;
    }

    private function integer($value): ?int
    {
        $value = preg_replace('/[^0-9]/', '', $this->normalizeDigits((string) $value));

        return $value === '' ? null : (int) $value;
    }

    private function decimal($value): ?float
    {
        $value = str_replace([',', ' '], '', $this->normalizeDigits((string) $value));
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return $value === '' || !is_numeric($value) ? null : round((float) $value, 2);
    }

    private function date($value): ?string
    {
        $value = trim($this->normalizeDigits((string) $value));
        if ($value === '' || $value === '..') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateTime($value): ?string
    {
        $value = trim($this->normalizeDigits((string) $value));
        if ($value === '' || $value === '..') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanText($value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeHeader($value): string
    {
        $value = $this->normalizeLookup($value);
        $value = str_replace([':', '؛', '/', '-', '_'], ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeLookup($value): string
    {
        $value = mb_strtolower($this->cleanText($value), 'UTF-8');
        $value = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'ه', 'ي', 'و', 'ي'], $value);
        $value = preg_replace('/[ًٌٍَُِّْـ]/u', '', $value);
        $value = preg_replace('/\s*\/\s*[ـ\-]*ه?$/u', '', $value);

        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value));
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
