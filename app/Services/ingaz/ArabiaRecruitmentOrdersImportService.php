<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\ContractExternalOfficeDispatch;
use App\Models\ContractInfo;
use App\Models\RecruitmentOffices;
use App\Models\TravelHistory;
use App\Models\auth\Admin;
use App\Models\ingaz\ContractNote;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\Religion;
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ArabiaRecruitmentOrdersImportService
{
    private const REQUIRED_SHEETS = [
        'تقرير_الطلبات',
        'التفويض',
        'مواعيد_الوصول',
        'طلبات_وصلت',
        'مدة_الوصول',
        'العاملون',
        'رحلات_مخصص',
        'الإرساليات',
        'الملاحظات',
    ];

    private string $fileHash;
    private string $fileName;
    private ?int $adminId;
    private array $referenceCache = [];
    private array $referenceModels = [];
    private array $adminCache = [];

    public function import(string $path, string $fileName, ?int $adminId, array $result): array
    {
        @ini_set('memory_limit', '2048M');
        $this->assertReady($path);
        $this->fileHash = hash_file('sha256', $path);
        $this->fileName = $fileName;
        $this->adminId = $adminId ?: Admin::query()->value('id');

        $auxiliary = [
            'delegations' => $this->indexSheet($path, 'التفويض'),
            'arrival_schedule' => $this->indexSheet($path, 'مواعيد_الوصول'),
            'arrivals' => $this->indexSheet($path, 'طلبات_وصلت'),
            'durations' => $this->indexSheet($path, 'مدة_الوصول'),
            'workers' => $this->indexSheet($path, 'العاملون'),
            'trips' => $this->indexSheet($path, 'رحلات_مخصص'),
        ];
        $dispatches = $this->indexSheet($path, 'الإرساليات', 'رقم الإرسالية');

        foreach ($this->readSheet($path, 'تقرير_الطلبات') as $rowNumber => $main) {
            $orderNumber = $this->integer($main['رقم الطلب'] ?? null);
            if (!$orderNumber) {
                continue;
            }

            $result['total']++;

            try {
                $outcome = DB::transaction(function () use ($orderNumber, $main, $auxiliary, $dispatches, $rowNumber) {
                    $bundle = ['main' => $main];
                    foreach ($auxiliary as $key => $rows) {
                        $bundle[$key] = $rows[$orderNumber] ?? null;
                    }

                    $contract = Contract::query()->where('arabia_order_number', $orderNumber)->first();
                    $created = !$contract;
                    $client = $this->upsertClient($orderNumber, $bundle);
                    $maid = $this->upsertMaid($bundle);
                    $status = $this->resolveStatus($bundle);
                    $order = $maid ? $this->upsertOrder($orderNumber, $client, $maid, $status) : null;
                    $contract = $this->upsertContract($contract, $orderNumber, $client, $maid, $order, $status, $bundle);

                    $this->upsertTravel($contract, $client, $maid, $bundle);
                    $this->upsertDispatch($contract, $bundle, $dispatches);
                    $this->syncFinancialReferences($contract, $bundle);
                    $this->archiveRow('تقرير_الطلبات', $rowNumber, $main, $orderNumber, $contract->id);
                    return $created ? 'created' : 'updated';
                });

                $result[$outcome]++;
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;
                if (count($result['errors']) < 200) {
                    $result['errors'][] = "طلب أربيا {$orderNumber}: {$exception->getMessage()}";
                }
            }
        }

        DB::statement(
            'UPDATE arabia_recruitment_import_rows source_rows '
            . 'INNER JOIN contracts ON contracts.arabia_order_number = source_rows.arabia_order_number '
            . 'SET source_rows.contract_id = contracts.id, source_rows.updated_at = ? '
            . 'WHERE source_rows.source_file_hash = ?',
            [now(), $this->fileHash]
        );

        $notesResult = $this->importNotes($path);
        $result['notes_created'] = $notesResult['created'];
        $result['notes_skipped'] = $notesResult['skipped'];
        $result['archived_rows'] = DB::table('arabia_recruitment_import_rows')
            ->where('source_file_hash', $this->fileHash)
            ->count();
        $result['financial_references'] = DB::table('arabia_recruitment_financial_references')
            ->whereIn('contract_id', DB::table('arabia_recruitment_import_rows')
                ->where('source_file_hash', $this->fileHash)
                ->whereNotNull('contract_id')
                ->select('contract_id')
                ->distinct())
            ->count();

        return $result;
    }

    private function assertReady(string $path): void
    {
        if (!Schema::hasTable('arabia_recruitment_import_rows')
            || !Schema::hasColumn('contracts', 'arabia_order_number')
            || !Schema::hasColumn('users', 'arabia_client_number')) {
            throw new \RuntimeException('شغّل ترحيلات قاعدة البيانات أولاً قبل استيراد طلبات أربيا.');
        }
        if (!Schema::hasTable('arabia_recruitment_financial_references')) {
            throw new \RuntimeException('جدول المراجع المالية لأربيا غير موجود. شغّل ترحيلات قاعدة البيانات أولاً.');
        }

        $reader = IOFactory::createReaderForFile($path);
        $names = array_column($reader->listWorksheetInfo($path), 'worksheetName');
        $missing = array_values(array_diff(self::REQUIRED_SHEETS, $names));
        if ($missing !== []) {
            throw new \InvalidArgumentException('ملف أربيا غير مكتمل. الأوراق المفقودة: ' . implode('، ', $missing));
        }
    }

    private function indexSheet(string $path, string $sheetName, string $keyHeader = 'رقم الطلب'): array
    {
        $indexed = [];
        $archive = [];
        foreach ($this->readSheet($path, $sheetName) as $rowNumber => $row) {
            $key = $this->integer($row[$keyHeader] ?? null);
            $orderNumber = $keyHeader === 'رقم الطلب' ? $key : null;
            $archive[] = $this->archivePayload($sheetName, $rowNumber, $row, $orderNumber);
            if (count($archive) >= 500) {
                $this->archiveBatch($archive);
                $archive = [];
            }
            if ($key) {
                $indexed[$key] = $row;
            }
        }
        $this->archiveBatch($archive);

        return $indexed;
    }

    private function upsertClient(int $orderNumber, array $bundle): User
    {
        $row = $this->bestRow($bundle, ['arrivals', 'trips', 'arrival_schedule', 'main']);
        $clientNumber = $this->integer($this->first($bundle, 'رقم العميل'));
        $identity = $this->digits($this->first($bundle, 'رقم هوية العميل'));
        $phone = $this->phone($this->first($bundle, 'رقم الجوال'));
        $name = trim((string) ($this->first($bundle, 'اسم العميل') ?: 'عميل أربيا ' . ($clientNumber ?: $orderNumber)));

        $client = ($clientNumber ? User::query()->where('arabia_client_number', $clientNumber)->first() : null)
            ?: ($identity !== '' ? User::query()->where('national_num', $identity)->first() : null)
            ?: ($phone !== '' ? User::query()->where('phone', $phone)->first() : null);

        $payload = [
            'arabia_client_number' => $clientNumber,
            'name' => $name,
            'national_num' => $identity ?: null,
            'phone_code' => '+966',
            'phone' => $phone ?: 'arabia-client-' . ($clientNumber ?: $orderNumber),
            'birthdate' => $this->date($row['ميلاد العميل'] ?? null),
            'register_from' => 'arabia_recruitment_import',
            'is_blocked' => 0,
        ];

        if ($client) {
            $client->fill(array_filter($payload, fn ($value) => $value !== null && $value !== ''))->save();
            return $client;
        }

        $payload['password'] = Hash::make(bin2hex(random_bytes(16)));
        return User::query()->create($payload);
    }

    private function upsertMaid(array $bundle): ?Maid
    {
        $workerNumber = $this->integer($this->first($bundle, 'رقم العامل'));
        $passport = $this->passport($this->first($bundle, 'رقم الباسبور'));
        $name = trim((string) ($this->first($bundle, 'اسم العامل') ?: ''));

        if (!$workerNumber && $passport === '' && $name === '') {
            return null;
        }

        $maid = ($workerNumber ? Maid::withTrashed()->where('arabia_worker_number', $workerNumber)->first() : null)
            ?: ($passport !== '' ? Maid::withTrashed()->whereRaw('UPPER(REPLACE(passport_num, " ", "")) = ?', [$passport])->first() : null);

        $nationality = $this->resolveReference(Nationality::class, $this->first($bundle, 'الجنسية') ?: $this->first($bundle, 'البلد'));
        $religion = $this->resolveReference(Religion::class, $this->first($bundle, 'الديانة')) ?: Religion::query()->first();
        $office = $this->resolveReference(RecruitmentOffices::class, $this->first($bundle, 'مكتب'));
        $arrivalDate = $this->date($this->first($bundle, 'موعد الوصول') ?: $this->first($bundle, 'تاريخ الوصول'));

        $payload = array_filter([
            'arabia_worker_number' => $workerNumber,
            'name' => $name ?: ($maid?->name ?: 'ARABIA WORKER ' . $workerNumber),
            'passport_num' => $passport ?: null,
            'nationality_id' => $nationality?->id,
            'religion_id' => $religion?->id,
            'office_id' => $office?->id,
            'arrival_date' => $arrivalDate,
            'request_type' => Maid::REQUEST_TYPE_SPECIFIC,
            'status' => 'contracted',
            'available' => 0,
            'added_by' => $this->adminId,
        ], fn ($value) => $value !== null && $value !== '');

        if ($maid) {
            if ($maid->trashed()) {
                $maid->restore();
            }
            $maid->fill($payload)->save();
        } else {
            $maid = Maid::query()->create($payload);
        }

        $jobTitle = $this->resolveReference(JobTitle::class, $this->first($bundle, 'المهنة'));
        if ($jobTitle) {
            $maid->jobTitles()->syncWithoutDetaching([$jobTitle->id]);
        }
        $jobTypeId = JobType::query()->value('id');
        if ($jobTypeId) {
            $maid->jobTypes()->syncWithoutDetaching([$jobTypeId]);
        }

        return $maid;
    }

    private function upsertOrder(int $orderNumber, User $client, Maid $maid, string $status): Order
    {
        $order = Order::withTrashed()->where('order_code', 'ARB-' . $orderNumber)->first();
        $payload = [
            'user_id' => $client->id,
            'maid_id' => $maid->id,
            'job_type_id' => $maid->jobTypes()->value('job_types.id') ?: JobType::query()->value('id'),
            'payment_method' => 'cash',
            'status' => in_array($status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)
                ? OrderEnum::Completed->value
                : ($status === ContractStatusEnum::Back->value ? OrderEnum::Refused->value : OrderEnum::UnderProcess->value),
        ];

        if ($order) {
            if ($order->trashed()) {
                $order->restore();
            }
            $order->update($payload);
            return $order;
        }

        return Order::query()->create(['order_code' => 'ARB-' . $orderNumber] + $payload);
    }

    private function upsertContract(?Contract $contract, int $orderNumber, User $client, ?Maid $maid, ?Order $order, string $status, array $bundle): Contract
    {
        $main = $bundle['main'];
        $nationality = $this->resolveReference(Nationality::class, $main['الجنسية'] ?? null);
        $jobTitle = $this->resolveReference(JobTitle::class, $main['المهنة'] ?? null);
        $religion = $this->resolveReference(Religion::class, $this->first($bundle, 'الديانة'));
        $contractDate = $this->date($main['تاريخ الطلب'] ?? null);
        $supplierInvoice = $this->integer($main['فاتورة مورد'] ?? null);
        $payload = [
            'arabia_order_number' => $orderNumber,
            'arabia_status' => trim((string) ($main['حالة'] ?? '')),
            'arabia_supplier_invoice_number' => $supplierInvoice,
            'user_id' => $client->id,
            'maid_id' => $maid?->id,
            'order_id' => $order?->id,
            'marketer_id' => $this->resolveAdminId($main['مسوق'] ?? $main['أنشئ بواسطة'] ?? null),
            'status' => $status,
            'type' => 'recruitment',
            'msaned_number' => trim((string) ($main['رقم مساند'] ?? '')) ?: null,
            'visa_number' => trim((string) $this->first($bundle, 'رقم التأشيرة')) ?: null,
            'contract_date' => $contractDate,
            'contract_visa_type' => trim((string) ($main['نوع التأشيرة'] ?? '')) ?: null,
            'required_maid_nationality_id' => $nationality?->id,
            'required_maid_religion_id' => $religion?->id,
            'required_maid_job_title_id' => $jobTitle?->id,
        ];

        if ($contract) {
            $contract->fill($payload)->save();
        } else {
            $contract = Contract::query()->create($payload);
            if ($contractDate) {
                $contract->forceFill(['created_at' => $contractDate])->saveQuietly();
            }
        }

        $arrivalDate = $this->date($this->first($bundle, 'موعد الوصول') ?: $this->first($bundle, 'تاريخ الوصول'));
        $delegation = $bundle['delegations'] ?? [];
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], array_filter([
            'contract_date' => $contractDate,
            'contract_value' => $this->decimal($main['التكلفة'] ?? null),
            'visa_number' => $payload['visa_number'],
            'msaned_number' => $payload['msaned_number'] ?: '',
            'tafwed_number' => trim((string) ($delegation['رقم التفويض'] ?? '')) ?: '',
            'arrival_date' => $arrivalDate,
            'manual_maid_name' => $maid ? null : (trim((string) $this->first($bundle, 'اسم العامل')) ?: null),
            'manual_maid_passport_num' => $maid ? null : ($this->passport($this->first($bundle, 'رقم الباسبور')) ?: null),
            'manual_maid_nationality_id' => $maid ? null : $nationality?->id,
        ], fn ($value) => $value !== null));

        if (in_array($status, [ContractStatusEnum::Completed->value, ContractStatusEnum::Refunded->value], true)) {
            $contract->forceFill(['completed_at' => $arrivalDate ?: $contractDate])->saveQuietly();
        }

        return $contract;
    }

    private function upsertTravel(Contract $contract, User $client, ?Maid $maid, array $bundle): void
    {
        $row = $bundle['arrivals'] ?? $bundle['trips'] ?? $bundle['arrival_schedule'] ?? null;
        if (!$row) {
            return;
        }

        $arrivalDate = $this->date($row['موعد الوصول'] ?? $row['تاريخ الوصول'] ?? null);
        $arrivalTime = $this->time($row['وقت الوصول'] ?? $row['الوقت'] ?? $row['الوقت'] ?? null);
        $travel = TravelHistory::withTrashed()->updateOrCreate(['order_id' => $contract->id], [
            'user_id' => $client->id,
            'maid_id' => $maid?->id,
            'travel_type' => 'arrival',
            'travel_with' => 'maid',
            'status' => $arrivalDate && Carbon::parse($arrivalDate)->endOfDay()->isPast() ? 'completed' : 'scheduled',
            'arrival_airport' => trim((string) ($row['مطار'] ?? $row['محطة الوصول'] ?? '')) ?: null,
            'arrival_date' => $arrivalDate,
            'arrival_time' => $arrivalTime,
            'flight_company' => trim((string) ($row['الخطوط الناقلة'] ?? '')) ?: null,
            'flight_number' => trim((string) ($row['رقم الرحلة'] ?? '')) ?: null,
            'assigned_to' => $this->resolveAdminId($row['مستقبل'] ?? null),
            'assignment_note' => 'مستورد من ملف طلبات أربيا',
            'notes' => 'جهة القدوم: ' . trim((string) ($row['جهة القدوم'] ?? '-')),
        ]);
        if ($travel->trashed()) {
            $travel->restore();
        }
    }

    private function upsertDispatch(Contract $contract, array $bundle, array $dispatches): void
    {
        $dispatchNumber = $this->integer($this->first($bundle, 'رقم الارسالية'));
        if (!$dispatchNumber) {
            return;
        }

        $master = $dispatches[$dispatchNumber] ?? [];
        $source = $contract->buildExternalOfficeDispatchSourceData();
        $payload = array_merge($source, [
            'created_by_admin_id' => $this->adminId,
            'updated_by_admin_id' => $this->adminId,
            'dispatch_date' => $this->date($master['تاريخ'] ?? $this->first($bundle, 'تاريخ الارسالية')),
            'arrival_station' => trim((string) ($this->first($bundle, 'محطة الوصول') ?: $this->first($bundle, 'مطار'))) ?: null,
        ]);
        $payload['source_hash'] = sha1(json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        ContractExternalOfficeDispatch::query()->updateOrCreate(['contract_id' => $contract->id], $payload);
    }

    private function syncFinancialReferences(Contract $contract, array $bundle): void
    {
        $main = $bundle['main'];
        $delegation = $bundle['delegations'] ?? [];
        $references = [];

        if ($supplierInvoice = trim((string) ($main['فاتورة مورد'] ?? ''))) {
            $references[] = ['supplier_invoice', $supplierInvoice, null, null, $main];
        }
        if ($bond = trim((string) ($delegation['سند صرف'] ?? ''))) {
            $references[] = ['delegation_disbursement_bond', $bond, null, null, $delegation];
        }
        $delegationCost = trim((string) ($delegation['تكلفة التفويض'] ?? ''));
        if ($delegationCost !== '') {
            $currency = trim((string) preg_replace('/[\d.,\-]+/u', '', $this->digits($delegationCost))) ?: null;
            $references[] = ['delegation_fee', trim((string) ($delegation['رقم التفويض'] ?? '')), $this->decimal($delegationCost), $currency, $delegation];
        }
        $insuranceCompany = trim((string) ($main['شركة التأمين'] ?? ''));
        $insuranceAmount = $this->decimal($main['قيمة التأمين'] ?? null);
        if (($insuranceCompany !== '' && $insuranceCompany !== '..') || ($insuranceAmount !== null && $insuranceAmount > 0)) {
            $references[] = ['insurance', $insuranceCompany, $insuranceAmount, null, [
                'company' => $insuranceCompany,
                'status' => $main['حالة التأمين'] ?? null,
                'amount' => $main['قيمة التأمين'] ?? null,
            ]];
        }

        foreach ($references as [$type, $reference, $amount, $currency, $payload]) {
            DB::table('arabia_recruitment_financial_references')->upsert([[
                'contract_id' => $contract->id,
                'source_type' => $type,
                'legacy_reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['contract_id', 'source_type', 'legacy_reference'], ['amount', 'currency', 'payload', 'updated_at']);
        }
    }

    private function importNotes(string $path): array
    {
        $created = 0;
        $skipped = 0;
        $contracts = Contract::query()
            ->whereNotNull('arabia_order_number')
            ->get(['id', 'arabia_order_number', 'branch_id'])
            ->keyBy('arabia_order_number');
        $contractIds = $contracts->pluck('id');
        $existing = ContractNote::query()
            ->whereIn('contract_id', $contractIds)
            ->get(['contract_id', 'note', 'created_at'])
            ->mapWithKeys(fn ($note) => [$this->noteKey($note->contract_id, $note->note, $note->created_at) => true])
            ->all();
        $archive = [];
        $inserts = [];

        foreach ($this->readSheet($path, 'الملاحظات') as $rowNumber => $row) {
            $orderNumber = $this->integer($row['رقم الطلب'] ?? null);
            $contract = $orderNumber ? $contracts->get($orderNumber) : null;
            $archive[] = $this->archivePayload('الملاحظات', $rowNumber, $row, $orderNumber, $contract?->id);
            if (count($archive) >= 500) {
                $this->archiveBatch($archive);
                $archive = [];
            }
            $note = trim((string) ($row['ملاحظة'] ?? ''));
            if (!$contract || $note === '') {
                $skipped++;
                continue;
            }

            $date = $this->dateTime($row['تاريخ الملاحظة'] ?? null) ?: now();
            $adminId = $this->resolveAdminId($row['دونت بواسطة'] ?? null) ?: $this->adminId;
            $key = $this->noteKey($contract->id, $note, $date);
            if (isset($existing[$key]) || !$adminId) {
                $skipped++;
                continue;
            }

            $inserts[] = [
                'contract_id' => $contract->id,
                'branch_id' => $contract->branch_id,
                'admin_id' => $adminId,
                'note' => $note,
                'created_at' => $date,
                'updated_at' => $date,
            ];
            $existing[$key] = true;
            $created++;
            if (count($inserts) >= 500) {
                DB::table('contract_notes')->insert($inserts);
                $inserts = [];
            }
        }
        $this->archiveBatch($archive);
        if ($inserts !== []) {
            DB::table('contract_notes')->insert($inserts);
        }

        return compact('created', 'skipped');
    }

    private function resolveStatus(array $bundle): string
    {
        $raw = $this->normalize($bundle['main']['حالة'] ?? null);
        if (str_contains($raw, 'استرداد')) {
            return ContractStatusEnum::Refunded->value;
        }
        if (str_contains($raw, 'ملغي')) {
            return ContractStatusEnum::Back->value;
        }
        if (str_contains($raw, 'اغلاق')) {
            return ContractStatusEnum::Completed->value;
        }
        $travelRow = $bundle['arrivals'] ?? $bundle['trips'] ?? $bundle['arrival_schedule'] ?? null;
        if ($travelRow) {
            $arrivalDate = $this->date($travelRow['موعد الوصول'] ?? $travelRow['تاريخ الوصول'] ?? null);
            return $arrivalDate && Carbon::parse($arrivalDate)->endOfDay()->isPast()
                ? ContractStatusEnum::Arrival->value
                : ContractStatusEnum::Tazker->value;
        }
        if ($this->first($bundle, 'رقم الارسالية')) {
            return ContractStatusEnum::Taffez->value;
        }
        if ($bundle['delegations'] ?? null) {
            return ContractStatusEnum::Tafwed->value;
        }
        if (trim((string) ($bundle['main']['رقم مساند'] ?? '')) !== '') {
            return ContractStatusEnum::Msaned->value;
        }

        return ($bundle['workers'] ?? null) ? ContractStatusEnum::New->value : ContractStatusEnum::PendingMaidSelection->value;
    }

    private function resolveReference(string $class, $value)
    {
        $needle = $this->normalize($value);
        if ($needle === '') {
            return null;
        }

        $cacheKey = $class . '|' . $needle;
        if (array_key_exists($cacheKey, $this->referenceCache)) {
            return $this->referenceCache[$cacheKey];
        }

        $needles = [$needle];
        if ($class === Nationality::class) {
            foreach (['فلبين', 'اثيوب', 'كين', 'هند', 'بنجل', 'بنغل', 'اوغند', 'اندون', 'سودان', 'باكستان', 'مصر', 'مغرب'] as $stem) {
                if (str_contains($needle, $stem)) {
                    $needles[] = $stem;
                }
            }
        }

        $models = $this->referenceModels[$class] ??= $class::query()->get();
        $match = $models->first(function ($model) use ($needles) {
            $values = [];
            foreach (['title', 'sub_title', 'name_ar', 'name_en'] as $attribute) {
                $values[] = $model->{$attribute} ?? null;
            }
            foreach (($model->translations ?? []) as $translation) {
                $values[] = $translation->title ?? null;
                $values[] = $translation->sub_title ?? null;
            }

            return collect($values)->filter()->contains(function ($candidate) use ($needles) {
                $candidate = $this->normalize($candidate);
                return collect($needles)->contains(
                    fn ($needle) => $candidate === $needle || str_contains($needle, $candidate) || str_contains($candidate, $needle)
                );
            });
        });

        return $this->referenceCache[$cacheKey] = $match;
    }

    private function resolveAdminId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $this->adminId;
        }
        $name = trim((string) preg_replace('/^admin\s*\/\s*/iu', '', $value));
        return $this->adminCache[$name] ??= (
            Admin::query()->where('name', $name)->orWhere('username', $name)->value('id') ?: $this->adminId
        );
    }

    private function archiveRow(string $sheet, int $rowNumber, array $row, ?int $orderNumber = null, ?int $contractId = null): void
    {
        $this->archiveBatch([$this->archivePayload($sheet, $rowNumber, $row, $orderNumber, $contractId)]);
    }

    private function archivePayload(string $sheet, int $rowNumber, array $row, ?int $orderNumber = null, ?int $contractId = null): array
    {
        $payload = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return [
            'source_file_hash' => $this->fileHash,
            'source_file_name' => $this->fileName,
            'sheet_name' => $sheet,
            'source_row_number' => $rowNumber,
            'arabia_order_number' => $orderNumber,
            'contract_id' => $contractId,
            'row_hash' => hash('sha256', $payload),
            'payload' => $payload,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function archiveBatch(array $rows): void
    {
        if ($rows === []) {
            return;
        }
        DB::table('arabia_recruitment_import_rows')->upsert($rows, ['source_file_hash', 'sheet_name', 'source_row_number'], [
            'source_file_name', 'arabia_order_number', 'contract_id', 'row_hash', 'payload', 'updated_at',
        ]);
    }

    private function noteKey(int $contractId, string $note, $date): string
    {
        return $contractId . '|' . Carbon::parse($date)->toDateString() . '|' . sha1($note);
    }

    private function readSheet(string $path, string $sheetName): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        $reader->setLoadSheetsOnly([$sheetName]);
        $book = $reader->load($path);
        $sheet = $book->getSheetByName($sheetName);
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headers = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headers[$column] = trim((string) $sheet->getCell([$column, 1])->getValue());
        }

        try {
            for ($rowNumber = 2; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
                $row = [];
                for ($column = 1; $column <= $highestColumn; $column++) {
                    if (($headers[$column] ?? '') !== '') {
                        $row[$headers[$column]] = $sheet->getCell([$column, $rowNumber])->getValue();
                    }
                }
                if (collect($row)->contains(fn ($value) => trim((string) $value) !== '')) {
                    yield $rowNumber => $row;
                }
            }
        } finally {
            $book->disconnectWorksheets();
            unset($book);
            gc_collect_cycles();
        }
    }

    private function bestRow(array $bundle, array $keys): array
    {
        foreach ($keys as $key) {
            if (!empty($bundle[$key])) {
                return $bundle[$key];
            }
        }
        return [];
    }

    private function first(array $bundle, string $header)
    {
        foreach (['arrivals', 'trips', 'arrival_schedule', 'workers', 'delegations', 'durations', 'main'] as $key) {
            $value = $bundle[$key][$header] ?? null;
            if ($value !== null && trim((string) $value) !== '' && trim((string) $value) !== '-') {
                return $value;
            }
        }
        return null;
    }

    private function normalize($value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = strtr($value, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي', 'ـ' => '']);
        $value = preg_replace('/[\s\p{P}\p{S}_]+/u', '', $value) ?: '';
        return str_starts_with($value, 'ال') ? mb_substr($value, 2) : $value;
    }

    private function integer($value): ?int
    {
        $digits = preg_replace('/\D+/', '', $this->digits($value));
        return $digits !== '' ? (int) $digits : null;
    }

    private function digits($value): string
    {
        return strtr(trim((string) $value), ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
    }

    private function phone($value): string
    {
        $phone = preg_replace('/\D+/', '', $this->digits($value));
        if (str_starts_with($phone, '966') && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 3);
        }
        return $phone;
    }

    private function passport($value): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
    }

    private function decimal($value): ?float
    {
        $number = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $this->digits($value)));
        return $number !== '' && is_numeric($number) ? (float) $number : null;
    }

    private function date($value): ?string
    {
        $date = $this->dateTime($value);
        if (!$date) {
            return null;
        }
        $parsed = Carbon::parse($date);
        return $parsed->year >= 1900 ? $parsed->toDateString() : null;
    }

    private function dateTime($value): ?string
    {
        if ($value === null || trim((string) $value) === '' || trim((string) $value) === '-') {
            return null;
        }
        try {
            if (is_numeric($value) && (float) $value > 1000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateTimeString();
            }
            return Carbon::parse($this->digits($value))->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function time($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }
        try {
            if (is_numeric($value) && (float) $value < 1) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('H:i:s');
            }
            if (is_numeric($value) && (float) $value < 24) {
                $hours = (int) floor((float) $value);
                $minutes = (int) round(((float) $value - $hours) * 60);
                return sprintf('%02d:%02d:00', $hours, $minutes);
            }
            $value = str_replace(['ص', 'م'], [' AM', ' PM'], $this->digits($value));
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
