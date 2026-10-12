<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Branch;
use App\Models\RentalContract;
use App\Models\RentalContractInvoice;
use App\Models\RecruitmentOffices;
use App\Models\RentalFinancialAdjustment;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\Models\auth\Admin;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\NationalitiesTranslation;
use App\Models\ingaz\Order;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use App\Models\ingaz\User;
use App\Models\Country;
use App\Models\IngazDelegation;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nami\Finance\Models\ClientCategory;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Hr\Models\Department as HrDepartment;
use Nami\Hr\Models\Employee as HrEmployee;
use Nami\Hr\Models\EmployeeShift as HrEmployeeShift;
use Nami\Hr\Models\Job as HrJob;
use Nami\Hr\Models\Shift as HrShift;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\Role;

class DataImportExecutionService
{
    private const MAX_ERROR_DETAILS = 200;
    private const LEGACY_UNASSIGNED_ACCOUNT_CODE = 1230000000;

    private ?string $importPasswordHash = null;
    private ?array $legacyRequestCostCenterIds = null;
    private array $legacyRentalCostCenters = [];
    private array $financeAccountCostCenterEligibility = [];
    private bool $financeConstraintsMissingOnly = false;
    private ?string $currentImportOriginalName = null;

    public function __construct(private DataImportTemplateService $templates)
    {
    }

    /** Read-only adapters: these use the same readers as execution, without creating business records. */
    public function reviewRows(string $type, string $path): \Generator
    {
        $columns = $this->templates->template($type)['columns'] ?? [];
        if ($type === 'legacy_invoices') {
            yield from $this->readLegacyInvoiceRows($path, $columns);
        } elseif ($type === 'legacy_rental_orders') {
            foreach ($this->readLegacyRentalOrderRows($path) as $number => $row) {
                yield $number => $this->normalizeLegacyRentalOrderRow($row);
            }
        } elseif ($type === 'alrafedin_transfer_services') {
            foreach (app(AlrafedinTransferServicesImportService::class)->parseReport($path) as $number => $row) {
                yield $number + 1 => $row['data'];
            }
        } elseif ($type === 'arabia_recruitment_orders') {
            yield from app(ArabiaRecruitmentOrdersImportService::class)->reviewRows($path);
        } else {
            yield from $this->readRows($path, $columns);
        }
    }

    public function validateReviewRow(string $type, array $row): array
    {
        $issues = [];
        try {
            if (in_array($type, ['rental_maids', 'recruitment_offices'], true) && !$this->resolveNationalityId($row['nationality'] ?? null)) {
                $issues[] = 'الجنسية مطلوبة أو غير موجودة في النظام.';
            }
            if ($type === 'rental_maids') {
                if (!$this->resolveTranslatableId(RecruitmentOffices::class, $row['office'] ?? null)) $issues[] = 'مكتب الاستقدام غير موجود في النظام.';
                if ($this->resolveTranslatableIds(JobTitle::class, $row['job_titles'] ?? null) === []) $issues[] = 'المهنة غير موجودة في النظام.';
            }
            if ($type === 'admins') {
                $this->resolveImportedAdminRole($row['role'] ?? $row['role_id'] ?? null);
                $this->resolveImportedAdminBranchId($row['branch'] ?? $row['branch_id'] ?? null);
                if (strlen((string) ($row['password'] ?? '')) < 6 && !Admin::query()->where('email', $row['email'] ?? '')->exists()) {
                    $issues[] = 'كلمة المرور مطلوبة للمستخدم الجديد ويجب ألا تقل عن 6 أحرف.';
                }
            }
            if ($type === 'employees' && filled($row['department'] ?? null) && !$this->resolveHrDepartmentId($row['department'])) {
                $issues[] = 'قسم الموظف غير موجود في النظام.';
            }
            if (in_array($type, ['receipt_bonds', 'disbursement_bonds'], true)) {
                $date = $this->parseDate($row['date'] ?? null);
                if ($date && !$this->resolveFinanceYear($date)) $issues[] = 'لا توجد سنة مالية نشطة تشمل تاريخ السند.';
                foreach (['debit', 'credit'] as $side) {
                    $code = $row[$side . '_account_code'] ?? null;
                    if (filled($code) && !Account::query()->where('code', $code)->exists() && blank($row[$side . '_account_name'] ?? null)) {
                        $issues[] = 'الحساب ' . $code . ' غير موجود؛ استورد شجرة الحسابات أولاً أو أدخل اسم الحساب.';
                    }
                }
            }
            if ($type === 'finance_constraints') {
                if (blank($row['account_id'] ?? null) && blank($row['account_code'] ?? null) && blank($row['account_name'] ?? null)) $issues[] = 'مرجع الحساب مطلوب لكل طرف من أطراف القيد.';
                $line = $this->resolveFinanceConstraintLine($row);
                if ($line['debtor_value'] < 0 || $line['creditor_value'] < 0) $issues[] = 'طرف القيد لا يقبل مبلغاً سالباً.';
                $date = $this->parseDate($row['date'] ?? null);
                if ($date && !$this->resolveFinanceYear($date)) $issues[] = 'لا توجد سنة مالية نشطة تشمل تاريخ القيد.';
            }
            if ($type === 'sponsorship_transfers') $this->normalizeTransferStatus($row['transfer_status'] ?? null);
            if (in_array($type, ['recruitment_contracts', 'recruitment_contracts_clients_maids'], true)) {
                $status = $row['recruitment_contract_status'] ?? $row['status'] ?? null;
                if (filled($status) && !$this->normalizeRecruitmentContractStatus($status)) $issues[] = 'حالة عقد الاستقدام غير مقبولة.';
            }
            if (in_array($type, ['rent_contracts', 'rent_contracts_clients_rental_maids'], true)) {
                $status = $row['rental_contract_status'] ?? $row['status'] ?? null;
                if (filled($status) && !$this->normalizeRentalStatus($status)) $issues[] = 'حالة عقد التأجير غير مقبولة.';
            }
            if (in_array($type, ['recruitment_contracts', 'shelter_contracts', 'rent_contracts', 'sponsorship_transfers'], true)) {
                $passport = $this->normalizePassportNumber($row['maid_passport'] ?? $row['passport_num'] ?? '');
                if ($passport !== '' && !Maid::query()->whereRaw('UPPER(TRIM(passport_num)) = ?', [$passport])->exists()) {
                    $issues[] = 'سيرة العاملة غير موجودة؛ استورد السيرة أولاً أو استخدم القالب المجمع.';
                }
            }
            $identity = $this->normalizeIdNumber($row['client_id_number'] ?? $row['id_number'] ?? '');
            $phone = $this->normalizeImportPhone($row['client_phone'] ?? $row['phone'] ?? '');
            if ($phone !== '' && $identity !== '') {
                $client = $this->findUserByPhone($phone);
                if ($client && filled($client->national_num) && $this->normalizeIdNumber($client->national_num) !== $identity) {
                    $issues[] = 'الجوال مرتبط بعميل آخر بهوية مختلفة.';
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $issues[] = $exception->getMessage();
        }
        return $issues;
    }

    public function reviewConstraintLine(array $row): array
    {
        return $this->resolveFinanceConstraintLine($row);
    }

    public function import(string $type, UploadedFile $file, ?int $adminId): array
    {
        $this->prepareLargeImportRuntime();
        $this->importPasswordHash = null;
        $this->legacyRequestCostCenterIds = null;
        $this->legacyRentalCostCenters = [];
        $this->financeAccountCostCenterEligibility = [];
        $this->currentImportOriginalName = $file->getClientOriginalName();

        $storedPath = $this->templates->storeUploadedFile($type, $file, $adminId);
        $result = [
            'path' => $storedPath,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'error_count' => 0,
        ];

        $columns = $this->templates->template($type)['columns'] ?? [];

        if ($type === 'finance_constraints') {
            return $this->importFinanceConstraintsFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'finance_cost_centers') {
            return $this->importFinanceCostCentersFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'receipt_bonds') {
            return $this->importReceiptBondsFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'disbursement_bonds') {
            return $this->importDisbursementBondsFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'finance_accounts') {
            return $this->importFinanceAccountsFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'legacy_invoices') {
            return $this->importLegacyInvoicesFile($storedPath, $columns, $adminId, $result);
        }

        if ($type === 'legacy_rental_orders') {
            return $this->importLegacyRentalOrdersFile($storedPath, $adminId, $result);
        }

        if ($type === 'arabia_recruitment_orders') {
            return app(ArabiaRecruitmentOrdersImportService::class)->import(
                Storage::path($storedPath),
                $file->getClientOriginalName(),
                $adminId,
                $result
            );
        }

        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;

            try {
                $result[$this->importReviewedRow($type, $row, $adminId)]++;
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $sourceRow = $row['__review_source_row'] ?? $rowNumber;
                    $message = $exception instanceof \Illuminate\Validation\ValidationException
                        ? implode('؛ ', \Illuminate\Support\Arr::flatten($exception->errors())) : $exception->getMessage();
                    $result['errors'][] = "صف {$sourceRow}: " . $message;
                }
            }
        }

        return $result;
    }

    /** Import one independent reviewed row; failures roll back all of this row's writes. */
    public function importReviewedRow(string $type, array $row, ?int $adminId): string
    {
        return DB::transaction(function () use ($type, $row, $adminId) {
            $resolution = app(ImportReferenceResolver::class)->resolve($type, $row);
            if ($resolution['issues']) throw new \InvalidArgumentException(implode('؛ ', $resolution['issues']));
            $row = $resolution['row'];
            return match ($type) {
                'invoices' => $this->importOperationalInvoice($row),
                'clients' => $this->importClient($row, $adminId),
                'admins' => $this->importAdmin($row),
                'recruitment_offices' => $this->importRecruitmentOffice($row),
                'employees' => $this->importEmployee($row),
                'rental_maids' => $this->importRentalMaid($row, $adminId),
                'recruitment_contracts' => $this->importRecruitmentContract($row, $adminId),
                'recruitment_contracts_clients_maids' => $this->importRecruitmentBundle($row, $adminId),
                'shelter_contracts' => $this->importShelterContract($row, $adminId),
                'rent_contracts' => $this->importRentContract($row, $adminId),
                'rent_contracts_clients_rental_maids' => $this->importRentBundle($row, $adminId),
                'sponsorship_transfers' => $this->importSponsorshipTransfer($row, $adminId),
                'ingaz_delegations' => $this->importIngazDelegation($row, $adminId),
                'complaints' => $this->importComplaint($row, $adminId),
                default => throw new \InvalidArgumentException('هذا النوع يحتاج اعتماد الملف المترابط كاملاً.'),
            };
        });
    }

    public function importMissingFinanceConstraints(UploadedFile $file, ?int $adminId): array
    {
        $this->financeConstraintsMissingOnly = true;

        try {
            return $this->import('finance_constraints', $file, $adminId);
        } finally {
            $this->financeConstraintsMissingOnly = false;
        }
    }

    public function linkImportedBondsToFinanceConstraints(string $directory): array
    {
        $this->prepareLargeImportRuntime();
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.xlsx') ?: [];
        $columns = $this->templates->template('finance_constraints')['columns'] ?? [];
        $references = [];

        foreach ($files as $file) {
            foreach ($this->readRowsInChunks($file, $columns) as $rowNumber => $row) {
                $documentType = $this->normalizeLegacyBondDocumentType($row['document_type'] ?? null);
                $documentNumber = $this->optionalInteger($row['document_number'] ?? null);
                $constraintCode = $this->optionalInteger($row['constraint_ref'] ?? $row['code'] ?? null);

                if (!$documentType || !$documentNumber || !$constraintCode) {
                    continue;
                }

                $key = $documentType . '|' . $documentNumber;
                $references[$key][$constraintCode] = [
                    'file' => basename($file),
                    'row' => $rowNumber,
                ];
            }
        }

        $ambiguous = array_filter($references, fn (array $versions) => count($versions) > 1);
        $unambiguous = array_filter($references, fn (array $versions) => count($versions) === 1);
        $constraintCodes = collect($unambiguous)
            ->map(fn (array $versions) => (int) array_key_first($versions))
            ->unique()
            ->values();
        $constraints = Constraint::query()->whereIn('code', $constraintCodes)->get()->keyBy('code');
        $bonds = Bond::query()
            ->where('legacy_source', 'alrafedin_finance_bonds')
            ->get()
            ->keyBy(fn (Bond $bond) => $bond->type . '|' . $bond->code);
        $details = ConstraintDetails::query()
            ->whereIn('constraint_id', $constraints->pluck('id'))
            ->get(['constraint_id', 'type', 'account_id', 'debtor_value', 'creditor_value'])
            ->groupBy('constraint_id');
        $result = [
            'files' => count($files),
            'source_references' => count($references),
            'ambiguous_references' => count($ambiguous),
            'constraints_tagged' => 0,
            'bonds_matched' => 0,
            'bonds_linked' => 0,
            'bonds_already_linked' => 0,
            'bonds_without_source_reference' => 0,
            'source_constraints_missing' => 0,
            'date_mismatches' => 0,
            'value_mismatches' => 0,
            'account_mismatches' => 0,
            'invoice_links_updated' => 0,
        ];

        foreach ($unambiguous as $key => $versions) {
            $constraintCode = (int) array_key_first($versions);
            $source = reset($versions);
            $constraint = $constraints->get($constraintCode);

            if (!$constraint) {
                $result['source_constraints_missing']++;
                continue;
            }

            [$documentType, $documentNumber] = explode('|', $key, 2);
            $constraint->update([
                'legacy_document_type' => $documentType,
                'legacy_document_number' => (int) $documentNumber,
                'legacy_source_file' => $source['file'],
                'legacy_source_row' => $source['row'],
            ]);
            $result['constraints_tagged']++;

            /** @var Bond|null $bond */
            $bond = $bonds->get($key);
            if (!$bond) {
                continue;
            }

            $result['bonds_matched']++;
            $constraintDetails = $details->get($constraint->id, collect());
            $bondValue = round((float) $bond->value, 2);
            $hasDebitAccount = $constraintDetails->contains(
                fn ($detail) => $detail->type === 'debtor'
                    && (int) $detail->account_id === (int) $bond->debit_account_id
                    && round((float) $detail->debtor_value, 2) === $bondValue
            );
            $hasCreditAccount = $constraintDetails->contains(
                fn ($detail) => $detail->type === 'creditor'
                    && (int) $detail->account_id === (int) $bond->credit_account_id
                    && round((float) $detail->creditor_value, 2) === $bondValue
            );

            if ((string) $constraint->date !== (string) $bond->date) {
                $result['date_mismatches']++;
            }
            if (round((float) $constraint->value, 2) !== $bondValue) {
                $result['value_mismatches']++;
            }
            if (!$hasDebitAccount || !$hasCreditAccount) {
                $result['account_mismatches']++;
            }

            $linkResult = $this->attachLegacyBondToActualConstraint($bond, $constraint);
            $result[$linkResult['linked'] ? 'bonds_linked' : 'bonds_already_linked']++;
            $result['invoice_links_updated'] += $linkResult['invoice_links_updated'];
        }

        $result['bonds_without_source_reference'] = $bonds->keys()->diff(array_keys($unambiguous))->count();

        return $result;
    }

    public function repairImportedRentalConstraintLinks(): array
    {
        $this->prepareLargeImportRuntime();
        $result = [
            'constraints_tagged' => 0,
            'invoice_links_found' => 0,
            'cost_centers_created' => 0,
            'cost_center_details_relinked' => 0,
        ];

        Constraint::query()
            ->where('statement', 'like', '%تأجير%')
            ->orderBy('id')
            ->chunkById(500, function ($constraints) use (&$result) {
                foreach ($constraints as $constraint) {
                    $statement = (string) $constraint->statement;
                    $requestNumber = $this->extractLegacyRentalRequestNumber($statement);
                    $invoiceNumber = $this->extractLegacyInvoiceNumber($statement);
                    $payload = [];

                    if ($requestNumber && Schema::hasColumn('fn_constraints', 'legacy_rental_order_number')) {
                        $payload['legacy_rental_order_number'] = $requestNumber;
                    }

                    if (
                        $invoiceNumber
                        && Schema::hasColumn('fn_constraints', 'legacy_invoice_number')
                        && RentalContractInvoice::query()->where('legacy_invoice_id', $invoiceNumber)->exists()
                    ) {
                        $payload['legacy_invoice_type'] = 'rental';
                        $payload['legacy_invoice_number'] = $invoiceNumber;
                        $result['invoice_links_found']++;
                    }

                    if ($payload && collect($payload)->contains(
                        fn ($value, $key) => (string) $constraint->{$key} !== (string) $value
                    )) {
                        $constraint->update($payload);
                        $result['constraints_tagged']++;
                    }

                    if (!$requestNumber) {
                        continue;
                    }

                    [$costCenter, $created] = $this->resolveOrCreateRentalRequestCostCenter($requestNumber);
                    if (!$costCenter) {
                        continue;
                    }
                    $result['cost_centers_created'] += $created ? 1 : 0;

                    $details = ConstraintDetails::query()
                        ->with(['account:id,code,statement_type', 'costCenter:id,title'])
                        ->where('constraint_id', $constraint->id)
                        ->get();

                    foreach ($details as $detail) {
                        if (!$this->financeAccountAcceptsInferredCostCenter($detail->account_id, $detail->account?->code)) {
                            continue;
                        }

                        $currentTitle = $this->normalizeArabicText((string) $detail->costCenter?->title);
                        $hasWrongRecruitmentCenter = preg_match('/^طلب استقدام\s+\d+$/u', $currentTitle);
                        if ($detail->cost_center_id && !$hasWrongRecruitmentCenter) {
                            continue;
                        }

                        if ((int) $detail->cost_center_id !== (int) $costCenter->id) {
                            $detail->update(['cost_center_id' => $costCenter->id]);
                            $result['cost_center_details_relinked']++;
                        }
                    }
                }
            });

        return $result;
    }

    private function importReceiptBondsFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        if (!class_exists(Bond::class) || !Schema::hasTable('fn_bonds')) {
            $result['error_count']++;
            $result['errors'][] = 'جدول سندات القبض غير موجود.';

            return $result;
        }

        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            if ($this->isLegacyBondReportSummaryRow($row)) {
                continue;
            }

            $result['total']++;
            $row = $this->withLegacyBondSourceMetadata($row, $rowNumber, $storedPath);

            try {
                DB::transaction(function () use ($row, $adminId, &$result) {
                    $outcome = $this->importReceiptBond($row, $adminId);
                    $result[$outcome]++;
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: " . $exception->getMessage();
                }
            }
        }

        return $result;
    }

    private function isLegacyBondReportSummaryRow(array $row): bool
    {
        if ($this->optionalInteger($row['bond_code'] ?? $row['code'] ?? null)) {
            return false;
        }

        $label = $this->normalizeArabicText((string) ($row['sequence'] ?? ''));

        return str_contains($label, 'الاجمالي')
            || str_contains($label, 'عدد سندات')
            || str_contains($label, 'نهايه التقرير');
    }

    private function importReceiptBond(array $row, ?int $adminId): string
    {
        if ($this->hasImportCellValue($row['paid_amount'] ?? null) || $this->hasImportCellValue($row['spent_amount'] ?? null)) {
            return $this->importDisbursementBond($row, $adminId);
        }

        return $this->importFinanceBondRow(
            $row,
            $adminId,
            BondTypes::Qabd->value,
            ['received_amount', 'total', 'financial_movement'],
            'سند قبض مستورد'
        );
    }

    private function importDisbursementBondsFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        if (!class_exists(Bond::class) || !Schema::hasTable('fn_bonds')) {
            $result['error_count']++;
            $result['errors'][] = 'جدول سندات الصرف غير موجود.';

            return $result;
        }

        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;
            $row = $this->withLegacyBondSourceMetadata($row, $rowNumber, $storedPath);

            try {
                DB::transaction(function () use ($row, $adminId, &$result) {
                    $outcome = $this->importDisbursementBond($row, $adminId);
                    $result[$outcome]++;
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: " . $exception->getMessage();
                }
            }
        }

        return $result;
    }

    private function importDisbursementBond(array $row, ?int $adminId): string
    {
        return $this->importFinanceBondRow(
            $row,
            $adminId,
            BondTypes::Sarf->value,
            ['paid_amount', 'spent_amount', 'total', 'financial_movement'],
            'سند صرف مستورد'
        );
    }

    private function importFinanceBondRow(
        array $row,
        ?int $adminId,
        string $bondType,
        array $amountKeys,
        string $defaultStatement
    ): string
    {
        $code = $this->optionalInteger($row['bond_code'] ?? $row['code'] ?? null);
        if (!$code) {
            throw new \InvalidArgumentException('رقم السند مطلوب.');
        }

        $date = $this->parseDate($row['date'] ?? null);
        if (!$date) {
            throw new \InvalidArgumentException('تاريخ السند مطلوب أو غير صحيح.');
        }

        $year = $this->resolveFinanceYear($date);
        if (!$year) {
            throw new \InvalidArgumentException('لا توجد سنة مالية صالحة لتاريخ السند.');
        }

        $debitAccountId = $this->resolveReceiptBondAccountId($row, 'debit', $adminId);
        $creditAccountId = $this->resolveReceiptBondAccountId($row, 'credit', $adminId);

        if (!$debitAccountId || !$creditAccountId) {
            throw new \InvalidArgumentException('تعذر تحديد حساب المدين أو الدائن من أرقام الحسابات الموجودة في الشيت.');
        }

        $value = null;
        foreach ($amountKeys as $amountKey) {
            $value = $this->decimal($row[$amountKey] ?? null);
            if ($value !== null) {
                break;
            }
        }
        $value ??= 0.0;

        $statement = $this->buildReceiptBondStatement($row, $defaultStatement);
        $paymentType = $this->resolveReceiptBondPaymentType($row['payment_type'] ?? null);
        $createdById = $this->resolveImportedAdminId(
            $row['created_by_name'] ?? $row['created_by'] ?? null,
            $adminId
        );
        $bond = Bond::query()
            ->where('type', $bondType)
            ->where('code', $code)
            ->lockForUpdate()
            ->first();
        $constraintId = $bond
            && (!Schema::hasColumn('fn_bonds', 'legacy_source') || $bond->legacy_source !== 'alrafedin_finance_bonds')
                ? $bond->constraint_id
                : null;
        $operatingLinks = $this->resolveImportedBondOperatingLinks($row);

        $payload = [
            'code' => $code,
            'type' => $bondType,
            'payment_type' => $paymentType,
            'bond_book_num' => (string) $code,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'value' => round((float) $value, 2),
            'date' => $date,
            'statement' => $statement,
            'constraint_id' => $constraintId,
            'is_confirm' => 1,
            'created_by_id' => $createdById,
            'year_id' => $year->id,
            'cost_center_id' => $this->resolveFinanceCostCenterId($row),
        ];

        $legacyPayload = [
            'legacy_source' => 'alrafedin_finance_bonds',
            'legacy_source_file' => $row['__source_file'] ?? null,
            'legacy_source_sheet' => $row['__source_sheet'] ?? null,
            'legacy_source_row' => $this->optionalInteger($row['__source_row'] ?? null),
            'legacy_sequence' => $this->optionalInteger($row['sequence'] ?? $row['source_sequence'] ?? null),
            'legacy_hijri_date' => $this->nullableImportString($row['hijri_date'] ?? null),
            'legacy_total' => $this->decimal($row['total'] ?? null),
            'legacy_currency' => $this->nullableImportString($row['currency'] ?? null),
            'legacy_financial_movement' => $this->decimal($row['financial_movement'] ?? null),
            'legacy_financial_movement_currency' => $this->nullableImportString($row['financial_movement_currency'] ?? null),
            'legacy_client_number' => $this->optionalInteger($row['client_number'] ?? null),
            'legacy_order_number' => $this->optionalInteger($row['order_number'] ?? null),
            'recruitment_contract_id' => $operatingLinks['recruitment_contract_id'],
            'rental_contract_id' => $operatingLinks['rental_contract_id'],
            'legacy_source_payload' => $this->legacyBondSourcePayload($row),
        ];
        foreach ($legacyPayload as $column => $columnValue) {
            if (Schema::hasColumn('fn_bonds', $column)) {
                $payload[$column] = $columnValue;
            }
        }

        foreach ([
            'is_tax' => 0,
            'net' => round((float) $value, 2),
            'account_text' => $this->resolveImportedBondAccountText($row, $bondType),
            'general_type' => 0,
            'transfer_number' => $this->extractReceiptBondTransferNumber($row),
        ] as $column => $columnValue) {
            if (Schema::hasColumn('fn_bonds', $column)) {
                $payload[$column] = $columnValue;
            }
        }

        if ($bond) {
            if (
                Schema::hasColumn('fn_bonds', 'legacy_source')
                && !$bond->legacy_source
                && !$this->existingBondMatchesLegacyRow($bond, $payload)
            ) {
                throw new \InvalidArgumentException(
                    "رقم السند {$code} مستخدم مسبقًا في سند غير مستورد وتختلف بياناته؛ لم يتم استبداله."
                );
            }
            $bond->update($payload);
            $bond = $bond->fresh();
            $this->archiveLegacyBondSourceRow($bond, $row);
            $this->linkLegacyBondToActualConstraint($bond);
            $bond = $bond->fresh();
            $this->syncRentalInvoicesForReceiptBond($bond);
            return 'updated';
        }

        $bond = Bond::query()->create($payload);
        $this->archiveLegacyBondSourceRow($bond, $row);
        $this->linkLegacyBondToActualConstraint($bond);
        $this->syncRentalInvoicesForReceiptBond($bond->fresh());

        return 'created';
    }

    private function normalizeLegacyBondDocumentType($value): ?string
    {
        $normalized = $this->normalizeArabicText((string) $value);

        return match (true) {
            str_contains($normalized, 'سند قبض') => BondTypes::Qabd->value,
            str_contains($normalized, 'سند صرف') => BondTypes::Sarf->value,
            default => null,
        };
    }

    private function linkLegacyBondToActualConstraint(Bond $bond): bool
    {
        if (
            !Schema::hasColumn('fn_constraints', 'legacy_document_type')
            || !Schema::hasColumn('fn_constraints', 'legacy_document_number')
        ) {
            return false;
        }

        $constraints = Constraint::query()
            ->where('legacy_document_type', $bond->type)
            ->where('legacy_document_number', $bond->code)
            ->get();

        if ($constraints->count() !== 1) {
            return false;
        }

        $this->attachLegacyBondToActualConstraint($bond, $constraints->first());

        return true;
    }

    private function attachLegacyBondToActualConstraint(Bond $bond, Constraint $constraint): array
    {
        if ($bond->constraint_id && (int) $bond->constraint_id !== (int) $constraint->id) {
            $currentConstraint = Constraint::query()->find($bond->constraint_id);
            if ($currentConstraint) {
                throw new \RuntimeException(
                    "السند {$bond->code} مرتبط مسبقًا بقيد فعلي آخر رقم {$currentConstraint->code}."
                );
            }
        }

        $linked = (int) $bond->constraint_id !== (int) $constraint->id;
        if ($linked) {
            $bond->update(['constraint_id' => $constraint->id]);
        }

        $invoiceLinksUpdated = 0;
        if (
            $bond->type === BondTypes::Qabd->value
            && Schema::hasTable('rental_contract_invoices')
            && Schema::hasColumn('rental_contract_invoices', 'receipt_constraint_id')
        ) {
            $invoiceLinksUpdated = DB::table('rental_contract_invoices')
                ->where(function ($query) use ($bond) {
                    $query->where('receipt_bond_id', $bond->id);
                    if (Schema::hasColumn('rental_contract_invoices', 'legacy_receipt_code')) {
                        $query->orWhere('legacy_receipt_code', $bond->code);
                    }
                })
                ->where(function ($query) use ($constraint) {
                    $query->whereNull('receipt_constraint_id')
                        ->orWhere('receipt_constraint_id', '!=', $constraint->id);
                })
                ->update([
                    'receipt_constraint_id' => $constraint->id,
                    'updated_at' => now(),
                ]);
        }

        return [
            'linked' => $linked,
            'invoice_links_updated' => $invoiceLinksUpdated,
        ];
    }

    private function syncRentalInvoicesForReceiptBond(Bond $bond): void
    {
        if (
            $bond->type !== BondTypes::Qabd->value
            || !Schema::hasTable('rental_contract_invoices')
            || !Schema::hasColumn('rental_contract_invoices', 'legacy_receipt_code')
        ) {
            return;
        }

        $invoices = RentalContractInvoice::query()
            ->where('legacy_receipt_code', $bond->code)
            ->get();

        foreach ($invoices as $invoice) {
            $total = max(0, (float) $invoice->total_due);
            $paidAmount = min($total, max(0, (float) $bond->value));
            $remainingAmount = round(max(0, $total - $paidAmount), 2);
            $paymentStatus = $paidAmount <= 0
                ? 'pending'
                : ($remainingAmount <= 0 ? 'paid' : 'partial');

            $invoice->update([
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paymentStatus,
                'is_paid' => $paymentStatus === 'paid',
                'paid_at' => $paidAmount > 0 ? $bond->date : null,
                'receipt_bond_id' => $bond->id,
                'receipt_constraint_id' => $bond->constraint_id,
                'receipt_account_id' => $bond->debit_account_id,
            ]);

            DB::table('rental_contract_infos')
                ->where('rental_contract_id', $invoice->rental_contract_id)
                ->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $paymentStatus,
                    'payment_confirmed_at' => $paidAmount > 0 ? $bond->date : null,
                ]);
        }

        if (
            $invoices->isNotEmpty()
            && Schema::hasColumn('fn_bonds', 'rental_contract_id')
            && $invoices->pluck('rental_contract_id')->unique()->count() === 1
        ) {
            DB::table('fn_bonds')->where('id', $bond->id)->update([
                'rental_contract_id' => (int) $invoices->first()->rental_contract_id,
            ]);
        }
    }

    private function resolveReceiptBondAccountId(array $row, string $side, ?int $adminId): ?int
    {
        $prefix = $side === 'debit' ? 'debit' : 'credit';

        return $this->resolveOrCreateFinanceConstraintAccountId([
            'account_id' => $row[$prefix . '_account_id'] ?? null,
            'account_code' => $row[$prefix . '_account_code'] ?? null,
            'account_name' => $row[$prefix . '_account_name'] ?? null,
            'statement' => $row['statement'] ?? null,
        ], $adminId);
    }

    private function withLegacyBondSourceMetadata(array $row, int $rowNumber, string $storedPath): array
    {
        $row['__source_file'] = $this->currentImportOriginalName ?: basename($storedPath);
        $row['__source_sheet'] = 'Arabia2web.com';
        $row['__source_row'] = $rowNumber;

        return $row;
    }

    private function legacyBondSourcePayload(array $row): array
    {
        return collect($row)
            ->reject(fn ($value, $key) => str_starts_with((string) $key, '__'))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all();
    }

    private function archiveLegacyBondSourceRow(Bond $bond, array $row): void
    {
        if (!Schema::hasTable('legacy_finance_bond_source_rows')) {
            return;
        }

        $payload = $this->legacyBondSourcePayload($row);
        $sourceFile = (string) ($row['__source_file'] ?? 'unknown');
        $sourceSheet = (string) ($row['__source_sheet'] ?? '');
        $sourceRow = $this->optionalInteger($row['__source_row'] ?? null);
        $sourceHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $sourceKey = hash('sha256', implode('|', [$sourceFile, $sourceSheet, (string) $sourceRow, $sourceHash]));

        DB::table('legacy_finance_bond_source_rows')->updateOrInsert(
            ['source_key' => $sourceKey],
            [
                'bond_id' => $bond->id,
                'source_hash' => $sourceHash,
                'source_file' => $sourceFile,
                'source_sheet' => $sourceSheet ?: null,
                'source_row' => $sourceRow,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function existingBondMatchesLegacyRow(Bond $bond, array $payload): bool
    {
        return (string) $bond->date === (string) $payload['date']
            && (int) $bond->debit_account_id === (int) $payload['debit_account_id']
            && (int) $bond->credit_account_id === (int) $payload['credit_account_id']
            && abs((float) $bond->value - (float) $payload['value']) < 0.005;
    }

    private function resolveImportedBondOperatingLinks(array $row): array
    {
        $statement = $this->normalizeDigits((string) ($row['statement'] ?? ''));
        $rentalOrderNumber = $this->extractLegacyRentalOrderNumber($statement);
        $rentalContractId = null;
        $recruitmentContractId = null;

        if ($rentalOrderNumber && Schema::hasTable('rental_contracts')) {
            $query = RentalContract::query();
            if (Schema::hasColumn('rental_contracts', 'legacy_order_id')) {
                $query->where('legacy_order_id', $rentalOrderNumber);
            } else {
                $query->where('contract_number', (string) $rentalOrderNumber);
            }
            $rentalContractId = $query->value('id');
        }

        $orderNumber = $this->optionalInteger($row['order_number'] ?? null);
        if (!$rentalOrderNumber && $orderNumber && Schema::hasTable('contracts')) {
            $contractQuery = Contract::query();
            if (Schema::hasColumn('contracts', 'arabia_order_number')) {
                $contractQuery->where('arabia_order_number', $orderNumber);
            } else {
                $contractQuery->where('id', $orderNumber);
            }
            $contract = $contractQuery->first();
            $contract ??= Contract::query()->where('contract_number', (string) $orderNumber)->first();
            $recruitmentContractId = $contract?->id;
        }

        return [
            'recruitment_contract_id' => $recruitmentContractId ? (int) $recruitmentContractId : null,
            'rental_contract_id' => $rentalContractId ? (int) $rentalContractId : null,
        ];
    }

    private function extractLegacyRentalOrderNumber(?string $statement): ?int
    {
        $statement = $this->normalizeDigits((string) $statement);

        if (!preg_match('/طلب\s+ت[اأإآ]جير\s+رقم\s*[:：]?\s*(\d+)/u', $statement, $matches)) {
            return null;
        }

        $number = (int) ($matches[1] ?? 0);

        return $number > 0 ? $number : null;
    }

    private function resolveReceiptBondPaymentType($value): int
    {
        $value = trim((string) $value);
        $normalized = $this->normalizeArabicText($value);

        if (str_contains($normalized, 'شيك') || str_contains($normalized, 'check')) {
            return BondPaymentTypes::Check->value;
        }

        if (str_contains($normalized, 'شبكه') || str_contains($normalized, 'شبكة') || str_contains($normalized, 'نقاط بيع') || str_contains($normalized, 'network')) {
            return BondPaymentTypes::Network->value;
        }

        if (str_contains($normalized, 'تحويل') || str_contains($normalized, 'بنك') || str_contains($normalized, 'bank')) {
            return BondPaymentTypes::BankTransfer->value;
        }

        if (str_contains($normalized, 'الكتروني') || str_contains($normalized, 'محفظه') || str_contains($normalized, 'wallet')) {
            return BondPaymentTypes::ElectronicWallet->value;
        }

        return BondPaymentTypes::Cash->value;
    }

    private function resolveImportedBondAccountText(array $row, string $bondType): string
    {
        $preferredKey = $bondType === BondTypes::Sarf->value ? 'debit_account_name' : 'credit_account_name';
        $fallbackKey = $bondType === BondTypes::Sarf->value ? 'credit_account_name' : 'debit_account_name';

        return trim((string) ($row[$preferredKey] ?? $row['account_text'] ?? $row[$fallbackKey] ?? ''));
    }

    private function buildReceiptBondStatement(array $row, string $defaultStatement): string
    {
        $statement = trim((string) ($row['statement'] ?? ''));
        if ($statement === '') {
            $statement = $defaultStatement;
        }

        $extras = [];
        foreach ([
            'client_number' => 'رقم العميل',
            'order_number' => 'رقم الطلب',
            'payment_type' => 'طريقة الدفع',
            'created_by_name' => 'أنشئ بواسطة',
        ] as $key => $label) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '' && $value !== '0') {
                $extras[] = "{$label}: {$value}";
            }
        }

        $statement = $extras ? $statement . ' | ' . implode(' | ', $extras) : $statement;

        return mb_substr($statement, 0, 500);
    }

    private function extractReceiptBondTransferNumber(array $row): string
    {
        $statement = trim((string) ($row['statement'] ?? ''));
        $paymentType = trim((string) ($row['payment_type'] ?? ''));

        foreach ([$statement, $paymentType] as $value) {
            if (preg_match('/(?:رقم\s*(?:تحويل|شيك)?\s*[:：]?\s*)?(\d{4,})/u', $value, $matches)) {
                return $matches[1];
            }
        }

        return 'nothing';
    }

    private function importLegacyRentalOrdersFile(string $storedPath, ?int $adminId, array $result): array
    {
        $rows = iterator_to_array($this->readLegacyRentalOrderRows(Storage::path($storedPath)));
        // A user-uploaded partial report is never an authoritative snapshot.
        $activeSnapshot = false;
        $result += [
            'rental_maids' => 0,
            'rental_contracts' => 0,
            'rental_invoices' => 0,
            'receipt_links' => 0,
            'pending_receipt_links' => 0,
            'archived_rows' => 0,
            'unchanged_rows' => 0,
            'active_snapshot' => $activeSnapshot,
            'outside_shelter_links' => 0,
            'missing_company_contracts' => 0,
            'deactivated_contracts' => 0,
        ];
        $maidIds = [];
        $existingRows = $this->legacyRentalExistingRowsForResume();

        foreach ($rows as $rowNumber => $row) {
            $result['total']++;
            $normalizedRow = $this->normalizeLegacyRentalOrderRow($row);
            $requestId = $this->optionalInteger($normalizedRow['legacy_order_id'] ?? null);
            $existing = $requestId ? ($existingRows[$requestId] ?? null) : null;

            if (
                $existing
                && (!$activeSnapshot || $existing['status'] === 'active')
                && $this->legacyRentalSourcePayload($normalizedRow) == $existing['source_payload']
            ) {
                $result['updated']++;
                $result['rental_contracts']++;
                $result['rental_invoices']++;
                $result['archived_rows']++;
                $result['unchanged_rows']++;
                $maidIds[(int) $existing['maid_id']] = true;

                if ($existing['receipt_code']) {
                    $result[$existing['receipt_linked'] ? 'receipt_links' : 'pending_receipt_links']++;
                }

                continue;
            }

            try {
                DB::transaction(function () use ($row, $adminId, $activeSnapshot, &$result, &$maidIds) {
                    $imported = $this->importLegacyRentalOrder($row, $adminId, $activeSnapshot);
                    $result[$imported['outcome']]++;
                    $result['rental_contracts']++;
                    $result['rental_invoices']++;
                    $result['archived_rows']++;
                    $result[$imported['receipt_linked'] ? 'receipt_links' : 'pending_receipt_links'] += $imported['has_receipt_reference'] ? 1 : 0;
                    $maidIds[(int) $imported['maid_id']] = true;
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: " . $exception->getMessage();
                }
            }
        }

        $result['rental_maids'] = count($maidIds);
        $this->backfillLegacyRentalBranches($adminId);

        if ($activeSnapshot && $result['error_count'] === 0) {
            $snapshotResult = $this->reconcileLegacyRentalActiveSnapshot(
                collect($rows)->pluck('legacy_order_id')->map(fn ($id) => (int) $id)->filter()->unique()->all(),
                $adminId
            );
            $result = array_merge($result, $snapshotResult);
        }

        return $result;
    }

    private function isLegacyRentalActiveSnapshot(array $rows): bool
    {
        if ($rows === []) {
            return false;
        }

        return collect($rows)->every(function (array $row) {
            $row = $this->normalizeLegacyRentalOrderRow($row);

            return $this->normalizeArabicText($row['status'] ?? null) === '';
        });
    }

    private function legacyRentalExistingRowsForResume(): array
    {
        if (
            !Schema::hasColumn('rental_contracts', 'legacy_order_id')
            || !Schema::hasColumn('rental_contract_infos', 'legacy_source_payload')
            || !Schema::hasColumn('rental_contract_invoices', 'legacy_receipt_code')
        ) {
            return [];
        }

        return DB::table('rental_contracts as contracts')
            ->join('rental_contract_infos as infos', 'infos.rental_contract_id', '=', 'contracts.id')
            ->join('rental_contract_invoices as invoices', 'invoices.rental_contract_id', '=', 'contracts.id')
            ->where('contracts.legacy_source', 'alrafedin_rental_orders')
            ->whereNotNull('contracts.legacy_order_id')
            ->get([
                'contracts.legacy_order_id',
                'contracts.maid_id',
                'contracts.status',
                'infos.legacy_source_payload',
                'invoices.legacy_receipt_code',
                'invoices.receipt_bond_id',
            ])
            ->mapWithKeys(function ($row) {
                $sourcePayload = json_decode((string) $row->legacy_source_payload, true);

                return [(int) $row->legacy_order_id => [
                    'maid_id' => (int) $row->maid_id,
                    'status' => (string) $row->status,
                    'source_payload' => is_array($sourcePayload) ? $sourcePayload : [],
                    'receipt_code' => $this->optionalInteger($row->legacy_receipt_code),
                    'receipt_linked' => $row->receipt_bond_id !== null,
                ]];
            })
            ->all();
    }

    private function reconcileLegacyRentalActiveSnapshot(array $activeOrderIds, ?int $adminId): array
    {
        $activeContracts = RentalContract::query()
            ->with('info')
            ->where('legacy_source', 'alrafedin_rental_orders')
            ->whereIn('legacy_order_id', $activeOrderIds)
            ->get();

        $deactivatedContracts = RentalContract::query()
            ->where('legacy_source', 'alrafedin_rental_orders')
            ->where('status', 'active')
            ->whereNotIn('legacy_order_id', $activeOrderIds)
            ->update(['status' => 'completed']);

        $activeContractIds = $activeContracts->pluck('id');
        ContractInfo::query()
            ->where('is_outside_shelter', true)
            ->where('outside_shelter_reason', 'rental_delivery')
            ->where('outside_shelter_reference_type', RentalContract::class)
            ->when(
                $activeContractIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('outside_shelter_reference_id', $activeContractIds),
                fn ($query) => $query
            )
            ->update(['is_outside_shelter' => false]);

        $outsideLinks = 0;
        $missingCompanyContracts = 0;

        foreach ($activeContracts->groupBy('maid_id') as $maidId => $maidContracts) {
            $rentalContract = $maidContracts
                ->sortByDesc(fn (RentalContract $contract) => sprintf(
                    '%s-%010d',
                    optional($contract->info?->ingaz_from)->format('Y-m-d') ?: '0000-00-00',
                    (int) $contract->legacy_order_id
                ))
                ->first();

            if (!$rentalContract) {
                continue;
            }

            Maid::query()->whereKey($maidId)->update([
                'status' => 'contracted',
                'available' => false,
            ]);

            $companyContract = Contract::query()
                ->with('info')
                ->where('maid_id', $maidId)
                ->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', '%شركة%الرافدين%');
                })
                ->latest('id')
                ->first();

            if (!$companyContract) {
                $missingCompanyContracts++;
                continue;
            }

            $alreadyLinked = $companyContract->status === ContractStatusEnum::Shelter->value
                && $companyContract->info?->is_outside_shelter
                && $companyContract->info?->outside_shelter_reason === 'rental_delivery'
                && $companyContract->info?->outside_shelter_reference_type === RentalContract::class
                && (int) $companyContract->info?->outside_shelter_reference_id === (int) $rentalContract->id;

            $companyContract->update(['status' => ContractStatusEnum::Shelter->value]);
            $companyContract->info()->updateOrCreate([], [
                'is_outside_shelter' => true,
                'outside_shelter_at' => $rentalContract->info?->ingaz_from ?: $rentalContract->contract_date ?: now(),
                'outside_shelter_reason' => 'rental_delivery',
                'outside_shelter_reference_type' => RentalContract::class,
                'outside_shelter_reference_id' => $rentalContract->id,
                'outside_shelter_by' => $adminId,
            ]);

            if (!$alreadyLinked) {
                $companyContract->contract_status_logs()->create([
                    'status' => ContractStatusEnum::Shelter->value,
                    'date' => now(),
                    'notes' => 'مصالحة تقرير التأجير الساري: العاملة خارج الإيواء بسبب عقد الإيجار رقم '
                        . $rentalContract->legacy_order_id,
                ]);
            }

            $outsideLinks++;
        }

        return [
            'outside_shelter_links' => $outsideLinks,
            'missing_company_contracts' => $missingCompanyContracts,
            'deactivated_contracts' => $deactivatedContracts,
        ];
    }

    private function backfillLegacyRentalBranches(?int $fallbackAdminId): void
    {
        if (!Schema::hasColumn('rental_contracts', 'branch_id')) {
            return;
        }

        $marketerBranches = Admin::query()
            ->whereNotNull('branch_id')
            ->pluck('branch_id', 'id');
        $fallbackBranchId = $fallbackAdminId ? ($marketerBranches[$fallbackAdminId] ?? null) : null;
        $fallbackBranchId ??= Branch::query()->value('id');

        foreach ($marketerBranches->groupBy(fn ($branchId) => (int) $branchId, true) as $branchId => $adminBranchIds) {
            DB::table('rental_contracts')
                ->where('legacy_source', 'alrafedin_rental_orders')
                ->whereNull('branch_id')
                ->whereIn('marketer_id', $adminBranchIds->keys())
                ->update(['branch_id' => (int) $branchId]);
        }

        if ($fallbackBranchId) {
            DB::table('rental_contracts')
                ->where('legacy_source', 'alrafedin_rental_orders')
                ->whereNull('branch_id')
                ->update(['branch_id' => (int) $fallbackBranchId]);
        }

        $branches = DB::table('rental_contracts')
            ->where('legacy_source', 'alrafedin_rental_orders')
            ->whereNotNull('branch_id')
            ->select('branch_id')
            ->distinct()
            ->pluck('branch_id');

        foreach ($branches as $branchId) {
            $contractIds = DB::table('rental_contracts')
                ->where('legacy_source', 'alrafedin_rental_orders')
                ->where('branch_id', $branchId)
                ->pluck('id');
            $orderIds = DB::table('rental_contracts')
                ->where('legacy_source', 'alrafedin_rental_orders')
                ->where('branch_id', $branchId)
                ->whereNotNull('order_id')
                ->pluck('order_id');

            if (Schema::hasColumn('orders', 'branch_id') && $orderIds->isNotEmpty()) {
                DB::table('orders')->whereIn('id', $orderIds)->whereNull('branch_id')->update(['branch_id' => $branchId]);
            }

            if (Schema::hasColumn('rental_contract_invoices', 'branch_id') && $contractIds->isNotEmpty()) {
                DB::table('rental_contract_invoices')
                    ->whereIn('rental_contract_id', $contractIds)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $branchId]);
            }
        }
    }

    private function readLegacyRentalOrderRows(string $path): \Generator
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);
        $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $keys = [
            1 => 'sequence',
            2 => 'legacy_order_id',
            3 => 'status',
            4 => 'legacy_maid_id',
            5 => 'maid_name',
            6 => 'maid_passport',
            7 => 'legacy_client_id',
            8 => 'client_name',
            9 => 'legacy_invoice_id',
            10 => 'legacy_receipt_id',
            11 => 'nationality',
            12 => 'job_title',
            13 => 'supplier',
            14 => 'start_date',
            15 => 'start_date_hijri',
            16 => 'end_date',
            17 => 'end_date_hijri',
            18 => 'duration_days',
            19 => 'remaining_days',
            20 => 'used_days',
            21 => 'total_cost',
            22 => 'created_by',
            23 => 'created_at',
            24 => 'created_at_hijri',
        ];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [];
            foreach ($keys as $column => $key) {
                $row[$key] = $this->cellValue(
                    $sheet->getCell($this->cellCoordinate($column, $rowNumber))->getValue()
                );
            }

            if (!ImportValueParser::isMarkerRow($row) && collect($row)->contains(fn ($value) => $value !== null && $value !== '')) {
                yield $rowNumber => $row;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function importLegacyRentalOrder(array $row, ?int $adminId, bool $activeSnapshot = false): array
    {
        $row = $this->normalizeLegacyRentalOrderRow($row);
        $requestId = $this->optionalInteger($row['legacy_order_id'] ?? null);
        $clientLegacyId = $this->optionalInteger($row['legacy_client_id'] ?? null);
        $maidLegacyId = $this->optionalInteger($row['legacy_maid_id'] ?? null);
        $passport = $this->normalizePassportNumber($row['maid_passport'] ?? '');

        if (!$requestId) {
            throw new \InvalidArgumentException('رقم الطلب مطلوب.');
        }
        if (!$maidLegacyId && $passport === '') {
            throw new \InvalidArgumentException('رقم العامل أو رقم الجواز مطلوب.');
        }

        $sourceClientName = trim((string) ($row['client_name'] ?? ''));
        $placeholderClientPhone = 'data-import-rental-order-' . $requestId;
        $user = $clientLegacyId ? User::query()->find($clientLegacyId) : null;
        if (!$user && !$clientLegacyId && $sourceClientName === '') {
            $user = User::query()->where('phone', $placeholderClientPhone)->first();
        }
        if (!$user) {
            $clientName = $sourceClientName ?: ('عميل تأجير غير محدد - طلب ' . $requestId);
            $user = User::query()->create(array_filter($this->filterColumns('users', [
                'id' => $clientLegacyId,
                'name' => $clientName ?: 'عميل مستورد',
                'phone_code' => '+966',
                'phone' => $sourceClientName === ''
                    ? $placeholderClientPhone
                    : 'data-import-client-' . ($clientLegacyId ?: substr(sha1($clientName), 0, 12)),
                'password' => $this->defaultImportPasswordHash(),
                'register_from' => 'data_import',
                'is_blocked' => 0,
            ]), fn ($value) => $value !== null && $value !== ''));
        }

        $maid = $passport !== '' ? Maid::withTrashed()->where('passport_num', $passport)->first() : null;
        $maid = $maid ?: ($maidLegacyId ? Maid::withTrashed()->find($maidLegacyId) : null);
        $maidPayload = $this->filterColumns('maids', [
            'name' => trim((string) ($row['maid_name'] ?? '')) ?: 'EDIT_ME_NAME',
            'passport_num' => $passport ?: null,
            'nationality_id' => $this->resolveLegacyRentalNationalityId($row['nationality'] ?? null),
            'office_id' => $this->resolveTranslatableId(RecruitmentOffices::class, $row['supplier'] ?? null),
            'maid_accommodation_type' => 2,
            'available' => 0,
            'status' => 'contracted',
            'added_by' => $this->resolveAdminId($row['created_by'] ?? null) ?: $adminId,
        ]);

        if ($maid) {
            if ($maid->trashed()) {
                $maid->restore();
            }
            // لا نعيد حالة العاملة إلى "متعاقدة" عند المرور على عقد تاريخي أقدم لها.
            $existingMaidPayload = $maidPayload;
            unset($existingMaidPayload['status'], $existingMaidPayload['available']);
            $maid->update(array_filter($existingMaidPayload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $maid = Maid::query()->create(($maidLegacyId ? ['id' => $maidLegacyId] : []) + $maidPayload);
        }
        $this->syncMaidRelations($maid, [
            'type_id' => 'إيجار',
            'job_titles' => $row['job_title'] ?? null,
        ], true);

        $status = $activeSnapshot
            ? 'active'
            : $this->legacyRentalStatus($row['status'] ?? null, $row['end_date'] ?? null);
        $sourceCreatedAt = $this->parseDateTime($row['created_at'] ?? null);
        $marketerId = $this->resolveAdminId($row['created_by'] ?? null) ?: $adminId;
        $branchId = $marketerId ? Admin::query()->whereKey($marketerId)->value('branch_id') : null;
        $order = Order::query()->find($requestId);
        $jobTypeId = JobType::query()->whereKey(2)->value('id');
        if (!$jobTypeId) {
            throw new \InvalidArgumentException('لا يوجد نوع سيرة إيجار لربط الطلب.');
        }
        $orderPayload = [
            'maid_id' => $maid->id,
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'order_code' => (string) $requestId,
            'job_type_id' => $jobTypeId,
            'status' => $status === 'cancelled' ? OrderEnum::Refused->value : OrderEnum::Completed->value,
            'customer_id' => $marketerId,
            'branch_id' => $branchId,
            'created_at' => $sourceCreatedAt,
            'updated_at' => $sourceCreatedAt,
        ];
        $order ? $order->update($this->filterColumns('orders', $orderPayload)) : $order = Order::query()->create(['id' => $requestId] + $this->filterColumns('orders', $orderPayload));

        $contract = Schema::hasColumn('rental_contracts', 'legacy_order_id')
            ? RentalContract::query()->where('legacy_order_id', $requestId)->first()
            : null;
        $contract = $contract ?: RentalContract::query()->where('contract_number', (string) $requestId)->first()
            ?: RentalContract::query()->where('order_id', $order->id)->first();
        $contractPayload = $this->filterColumns('rental_contracts', [
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_id' => $order->id,
            'marketer_id' => $marketerId,
            'branch_id' => $branchId,
            'contract_number' => (string) $requestId,
            'contract_date' => $this->parseDate($row['start_date'] ?? $row['created_at'] ?? null),
            'status' => $status,
            'legacy_order_id' => $requestId,
            'legacy_created_by' => $this->nullableImportString($row['created_by'] ?? null),
            'legacy_created_at' => $sourceCreatedAt,
            'legacy_created_at_hijri' => $this->nullableImportString($row['created_at_hijri'] ?? null),
            'legacy_source' => 'alrafedin_rental_orders',
            'created_at' => $sourceCreatedAt,
            'updated_at' => $sourceCreatedAt,
        ]);
        $created = !$contract;
        $contract ? $contract->update($contractPayload) : $contract = RentalContract::query()->create($contractPayload);

        $total = max(0, $this->decimal($row['total_cost'] ?? null) ?: 0.0);
        $durationDays = $this->optionalInteger($row['duration_days'] ?? null);
        if (!$durationDays) {
            $startDate = $this->parseDate($row['start_date'] ?? null);
            $endDate = $this->parseDate($row['end_date'] ?? null);
            if ($startDate && $endDate) {
                $durationDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            }
        }
        $durationDays = max(1, (int) ($durationDays ?: 1));
        $durationMonths = max(1, (int) ceil($durationDays / 30));
        $receiptCode = $this->optionalInteger($row['legacy_receipt_id'] ?? null);
        $receiptBond = $this->resolveLegacyRentalReceiptBond($receiptCode);
        $hasReceiptReference = $receiptCode !== null;
        $paidAmount = $hasReceiptReference ? $total : 0.0;

        if ($receiptBond) {
            $paidAmount = min($total, max(0, (float) $receiptBond->value));
        }

        $remainingAmount = round(max(0, $total - $paidAmount), 2);
        $paymentStatus = $paidAmount <= 0
            ? 'pending'
            : ($remainingAmount <= 0 ? 'paid' : 'partial');
        $amounts = $this->legacyRentalInvoiceAmounts($total);
        $contract->info()->updateOrCreate([], $this->filterColumns('rental_contract_infos', [
            'package_original_price' => $amounts['subtotal'],
            'package_discount_amount' => 0,
            'package_price_after_discount' => $amounts['subtotal'],
            'package_vat_amount' => $amounts['vat_amount'],
            'package_total_with_tax' => $total,
            'working_hours_per_day' => 8,
            'monthly_salary' => $this->decimal($maid->salary ?? null),
            'rental_type' => 'monthly',
            'monthly_cost' => round($total / $durationMonths, 2),
            'contract_duration_days' => $durationDays,
            'contract_duration_months' => $durationMonths,
            'contract_value' => $total,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'ingaz_from' => $this->parseDate($row['start_date'] ?? null),
            'ingaz_to' => $this->parseDate($row['end_date'] ?? null),
            'payment_type' => 'cash',
            'payment_status' => $paymentStatus,
            'payment_confirmed_at' => $paidAmount > 0 ? $sourceCreatedAt : null,
            'payment_confirmed_by' => $paidAmount > 0 ? $marketerId : null,
            'legacy_start_date_hijri' => $this->nullableImportString($row['start_date_hijri'] ?? null),
            'legacy_end_date_hijri' => $this->nullableImportString($row['end_date_hijri'] ?? null),
            'legacy_remaining_days' => $this->legacyRentalDayCount($row['remaining_days'] ?? null),
            'legacy_used_days' => $this->legacyRentalDayCount($row['used_days'] ?? null),
            'legacy_source_payload' => $this->legacyRentalSourcePayload($row),
            'notes' => $this->legacyRentalNotes($row, $receiptBond !== null),
            'created_at' => $sourceCreatedAt,
            'updated_at' => $sourceCreatedAt,
        ]));

        $this->upsertLegacyRentalInvoice(
            $contract,
            $row,
            $amounts,
            $paidAmount,
            $remainingAmount,
            $paymentStatus,
            $receiptBond,
            $sourceCreatedAt
        );

        if ($receiptBond && Schema::hasColumn('fn_bonds', 'rental_contract_id')) {
            $receiptBond->update(['rental_contract_id' => $contract->id]);
        }

        return [
            'outcome' => $created ? 'created' : 'updated',
            'maid_id' => $maid->id,
            'has_receipt_reference' => $hasReceiptReference,
            'receipt_linked' => $receiptBond !== null,
        ];
    }

    private function normalizeLegacyRentalOrderRow(array $row): array
    {
        $aliases = [
            'م' => 'sequence',
            'رقم الطلب' => 'legacy_order_id',
            'الحالة' => 'status',
            'رقم العامل' => 'legacy_maid_id',
            'اسم العامل / ه' => 'maid_name',
            'اسم العاملـ / ـة' => 'maid_name',
            'باسبور العامل / ه' => 'maid_passport',
            'باسبور العاملـ / ـة' => 'maid_passport',
            'رقم العميل' => 'legacy_client_id',
            'العميل' => 'client_name',
            'رقم الفاتورة' => 'legacy_invoice_id',
            'سند قبض' => 'legacy_receipt_id',
            'الجنسية' => 'nationality',
            'المهنة' => 'job_title',
            'الشركة المزودة' => 'supplier',
            'تاريخ البدء' => 'start_date',
            'تاريخ البدء ه' => 'start_date_hijri',
            'تاريخ الانتهاء' => 'end_date',
            'تاريخ الانتهاء ه' => 'end_date_hijri',
            'مدة الايام' => 'duration_days',
            'المتبقي' => 'remaining_days',
            'الايام المستفادة' => 'used_days',
            'التكلفة' => 'total_cost',
            'بواسطة المستخدم' => 'created_by',
            'في تاريخ' => 'created_at',
            'في تاريخ ه' => 'created_at_hijri',
        ];

        $normalizedAliases = [];
        foreach ($aliases as $header => $key) {
            $normalizedAliases[$this->normalizeHeader($header)] = $key;
        }

        foreach ($row as $header => $value) {
            $key = $normalizedAliases[$this->normalizeHeader((string) $header)] ?? null;
            if ($key && (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '')) {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    private function upsertLegacyRentalInvoice(
        RentalContract $contract,
        array $row,
        array $amounts,
        float $paidAmount,
        float $remainingAmount,
        string $paymentStatus,
        ?Bond $receiptBond,
        ?Carbon $sourceCreatedAt
    ): void
    {
        $invoiceId = $this->optionalInteger($row['legacy_invoice_id'] ?? null);
        $invoice = $contract->financeInvoice()->first();
        $invoice = $invoice ?: ($invoiceId && Schema::hasColumn('rental_contract_invoices', 'legacy_invoice_id')
            ? RentalContractInvoice::query()->where('legacy_invoice_id', $invoiceId)->first()
            : null);
        $invoice = $invoice ?: ($invoiceId ? RentalContractInvoice::query()->find($invoiceId) : null);
        if ($invoice && (int) $invoice->rental_contract_id !== (int) $contract->id) {
            throw new \InvalidArgumentException("رقم الفاتورة {$invoiceId} مرتبط بعقد إيجار آخر.");
        }

        $receiptId = $this->optionalInteger($row['legacy_receipt_id'] ?? null);
        $payload = $this->filterColumns('rental_contract_invoices', [
            'rental_contract_id' => $contract->id,
            'branch_id' => $contract->branch_id,
            'legacy_invoice_id' => $invoiceId,
            'legacy_receipt_code' => $receiptId,
            'subtotal' => $amounts['subtotal'],
            'discount' => 0,
            'vat_amount' => $amounts['vat_amount'],
            'total_due' => $amounts['total_due'],
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_type' => 'cash',
            'payment_status' => $paymentStatus,
            'is_paid' => $paymentStatus === 'paid',
            'paid_at' => $paidAmount > 0 ? $sourceCreatedAt : null,
            'service_description' => 'طلب تأجير مستورد رقم ' . ($row['legacy_order_id'] ?? $contract->contract_number)
                . '؛ الفاتورة القديمة: ' . ($invoiceId ?: 'غير متوفرة')
                . '؛ سند القبض القديم: ' . ($receiptId ?: 'غير متوفر'),
            'receipt_bond_id' => $receiptBond?->id,
            'receipt_constraint_id' => $receiptBond?->constraint_id,
            'receipt_account_id' => $receiptBond?->debit_account_id,
            'created_at' => $sourceCreatedAt,
            'updated_at' => $sourceCreatedAt,
        ]);
        if ($invoice) {
            $invoice->update($payload);
            return;
        }

        $identity = $invoiceId && !RentalContractInvoice::query()->whereKey($invoiceId)->exists()
            ? ['id' => $invoiceId]
            : [];
        RentalContractInvoice::query()->create($identity + $payload);
    }

    private function resolveLegacyRentalReceiptBond(?int $receiptCode): ?Bond
    {
        if (!$receiptCode || !Schema::hasTable('fn_bonds')) {
            return null;
        }

        return Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->where('code', $receiptCode)
            ->first();
    }

    private function legacyRentalInvoiceAmounts(float $total): array
    {
        $total = round(max(0, $total), 2);
        $subtotal = $total > 0
            ? round($total / (1 + InvoiceCalculationService::VAT_RATE), 2)
            : 0.0;

        return [
            'subtotal' => $subtotal,
            'vat_amount' => round(max(0, $total - $subtotal), 2),
            'total_due' => $total,
        ];
    }

    private function legacyRentalDayCount($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || str_contains($this->normalizeArabicText($value), 'لا يوجد')) {
            return null;
        }

        return preg_match('/\d+/u', $value, $matches) ? (int) $matches[0] : null;
    }

    private function legacyRentalSourcePayload(array $row): array
    {
        return collect($row)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    private function legacyRentalNotes(array $row, bool $receiptLinked): string
    {
        $invoice = $row['legacy_invoice_id'] ?? '-';
        $receipt = $row['legacy_receipt_id'] ?? '-';
        $linkStatus = filled($row['legacy_receipt_id'] ?? null)
            ? ($receiptLinked ? 'مرتبط بالسند المالي' : 'بانتظار استيراد/مطابقة سند القبض')
            : 'لا يوجد سند قبض في المصدر';

        return "مستورد من تقرير طلبات التأجير؛ رقم الفاتورة القديم: {$invoice}؛ رقم سند القبض القديم: {$receipt}؛ {$linkStatus}.";
    }

    private function legacyRentalStatus($value, $endDate): string
    {
        $normalized = $this->normalizeArabicText($value);
        if (str_contains($normalized, 'ملغي') || str_contains($normalized, 'ملغى')) {
            return 'cancelled';
        }
        if (($date = $this->parseDate($endDate)) && Carbon::parse($date)->isPast()) {
            return 'completed';
        }

        return 'active';
    }

    private function resolveLegacyRentalNationalityId($value): ?int
    {
        $id = $this->resolveNationalityId($value);
        if ($id) {
            return $id;
        }

        $normalized = $this->normalizeArabicText($value);
        $country = match (true) {
            str_contains($normalized, 'كيني') => 'كينيا',
            str_contains($normalized, 'اثيوبي') => 'اثيوبيا',
            str_contains($normalized, 'اندونيسي') => 'اندونيسيا',
            str_contains($normalized, 'اوغندي') => 'اوغندا',
            str_contains($normalized, 'بنجلاديشي') => 'بنجلاديش',
            str_contains($normalized, 'فلبيني') => 'الفلبين',
            default => null,
        };

        return $country ? $this->resolveNationalityId($country) : null;
    }

    private function readRows(string $path, array $columns): \Generator
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);
        try {
            $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $labelMap = [];
            foreach ($this->importHeaderAliases() as $header => $key) {
                $labelMap[$this->normalizeHeader($header)] = $key;
            }
            // Template labels are more specific than the shared legacy aliases. For
            // example, "رقم الحساب" means a cost-center code in the Alrafedin file.
            foreach ($columns as $column) {
                $labelMap[$this->normalizeHeader($column['label'])] = $column['key'];
                $labelMap[$this->normalizeHeader($column['key'])] = $column['key'];
            }

            $headerRow = $this->detectImportHeaderRow($sheet, $highestColumnIndex, $labelMap);
            $headers = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $header = $this->cellValue($sheet->getCell($this->cellCoordinate($column, $headerRow))->getValue());
                $headers[$column] = $labelMap[$this->normalizeHeader($header)] ?? $header;
            }

            for ($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++) {
                $row = [];
                $hasValue = false;
                $onlyTemplateMarkers = true;

                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $key = $headers[$column] ?? null;
                    if (!$key) {
                        continue;
                    }

                    $value = $this->cellValue($sheet->getCell($this->cellCoordinate($column, $rowNumber))->getValue());
                    $row[$key] = $value;

                    if ($value !== '') {
                        $hasValue = true;
                        if (!in_array($value, ['مطلوب', 'اختياري'], true)) {
                            $onlyTemplateMarkers = false;
                        }
                    }
                }

                if ($hasValue && !$onlyTemplateMarkers) {
                    yield $rowNumber => $row;
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($sheet, $spreadsheet);
            gc_collect_cycles();
        }
    }

    private function detectImportHeaderRow($sheet, int $highestColumnIndex, array $labelMap): int
    {
        $bestRow = 1;
        $bestMatches = -1;
        $scanUntil = min(10, (int) $sheet->getHighestDataRow());

        for ($row = 1; $row <= $scanUntil; $row++) {
            $matches = 0;
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $value = $this->cellValue($sheet->getCell($this->cellCoordinate($column, $row))->getValue());
                if ($value !== '' && isset($labelMap[$this->normalizeHeader($value)])) {
                    $matches++;
                }
            }

            if ($matches > $bestMatches) {
                $bestMatches = $matches;
                $bestRow = $row;
            }
        }

        return $bestRow;
    }

    private function loadSpreadsheetForImport(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        return $reader->load($path);
    }

    private function importFinanceConstraintsFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        $accountsBeforeImport = class_exists(Account::class) && Schema::hasTable('fn_accounts')
            ? Account::query()->count()
            : 0;
        $groups = [];

        foreach ($this->readRowsInChunks(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;
            $row['__source_file'] = basename($storedPath);
            $row['__source_row'] = $rowNumber;
            $reference = trim((string) ($row['constraint_ref'] ?? $row['code'] ?? ''));

            if ($reference === '') {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: رقم مجموعة القيد مطلوب.";
                }

                continue;
            }

            $groups[$reference][] = [
                'row_number' => $rowNumber,
                'row' => $row,
            ];
        }

        foreach ($groups as $reference => $rows) {
            try {
                DB::transaction(function () use ($reference, $rows, $adminId, &$result) {
                    $outcome = $this->importFinanceConstraintGroup($reference, $rows, $adminId);
                    $result[$outcome]++;
                });
            } catch (\Throwable $exception) {
                $result['skipped'] += count($rows);
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $rowNumbers = collect($rows)->pluck('row_number')->implode(', ');
                    $result['errors'][] = "القيد {$reference} (صفوف {$rowNumbers}): " . $exception->getMessage();
                }
            }
        }

        $result['accounts_created'] = class_exists(Account::class) && Schema::hasTable('fn_accounts')
            ? max(0, Account::query()->count() - $accountsBeforeImport)
            : 0;
        $result['cost_center_links'] = $this->linkImportedRequestCostCentersToConstraints();

        return $result;
    }

    private function importFinanceCostCentersFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        if (!class_exists(CostCenter::class) || !Schema::hasTable('fn_cost_centers')) {
            $result['error_count']++;
            $result['errors'][] = 'جدول مراكز التكلفة المالية غير موجود.';

            return $result;
        }

        $rows = [];
        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;
            $code = trim($this->normalizeDigits((string) ($row['cost_center_code'] ?? '')));
            $name = trim((string) ($row['cost_center_name'] ?? ''));
            $level = $this->optionalInteger($row['level'] ?? null);

            if ($code === '' || $name === '' || !$level) {
                $result['skipped']++;
                $result['error_count']++;
                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: رقم مركز التكلفة واسمه ومستواه مطلوبة.";
                }
                continue;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'code' => $code,
                'name' => $name,
                'level' => $level,
                'parent_code' => trim($this->normalizeDigits((string) ($row['parent_code'] ?? ''))),
                'parent_name' => trim((string) ($row['parent_name'] ?? '')),
                'full_path' => trim((string) ($row['full_path'] ?? '')),
                'legacy_debtor_total' => $this->decimal($row['legacy_debtor_total'] ?? null),
                'legacy_creditor_total' => $this->decimal($row['legacy_creditor_total'] ?? null),
                'legacy_balance' => $this->decimal($row['legacy_balance'] ?? null),
                'legacy_balance_type' => $this->normalizeLegacyBalanceType($row['legacy_balance_type'] ?? null),
                'legacy_opening_balance' => $this->decimal($row['legacy_opening_balance'] ?? null),
            ];
        }

        usort($rows, fn (array $left, array $right) => [$left['level'], (int) $left['code']] <=> [$right['level'], (int) $right['code']]);

        $byCode = CostCenter::query()
            ->whereNotNull('legacy_source_code')
            ->get()
            ->keyBy(fn (CostCenter $center) => (string) $center->legacy_source_code);
        $tableColumns = array_flip(Schema::getColumnListing('fn_cost_centers'));

        foreach ($rows as $row) {
            try {
                DB::transaction(function () use ($row, $adminId, $tableColumns, &$byCode, &$result) {
                    $parent = null;
                    if ($row['level'] > 1) {
                        $parent = $row['parent_code'] !== '' ? ($byCode[$row['parent_code']] ?? null) : null;
                        if (!$parent && $row['parent_name'] !== '') {
                            $matches = CostCenter::query()->where('title', $row['parent_name'])->get();
                            if ($matches->count() === 1) {
                                $parent = $matches->first();
                            }
                        }
                        if (!$parent) {
                            throw new \InvalidArgumentException('تعذر تحديد مركز التكلفة الأب: ' . ($row['parent_code'] ?: $row['parent_name'] ?: '-'));
                        }
                    }

                    $isRecruitmentRoot = $row['code'] === '2'
                        && $this->normalizeArabicText($row['name']) === $this->normalizeArabicText('طلبات الاستقدام');
                    $center = $byCode[$row['code']] ?? null;
                    if (!$center && $isRecruitmentRoot) {
                        $center = CostCenter::query()
                            ->whereNull('parent_id')
                            ->where('title', 'عقود الاستقدام')
                            ->first();
                    }
                    $payload = array_intersect_key([
                        'title' => $isRecruitmentRoot ? 'عقود الاستقدام' : $row['name'],
                        'parent_id' => $parent?->id,
                        'table_name' => 'fn_cost_centers',
                        // The finance package defines this column as NOT NULL even
                        // for general (unassociated) cost centers.
                        'table_related_id' => 0,
                        'created_by_id' => $center?->created_by_id ?? $adminId ?? auth('admin')->id() ?? 1,
                        'legacy_source_code' => $row['code'],
                        'legacy_level' => $row['level'],
                        'legacy_full_path' => $row['full_path'] ?: null,
                        'legacy_debtor_total' => $row['legacy_debtor_total'],
                        'legacy_creditor_total' => $row['legacy_creditor_total'],
                        'legacy_balance' => $row['legacy_balance'],
                        'legacy_balance_type' => $row['legacy_balance_type'],
                        'legacy_opening_balance' => $row['legacy_opening_balance'],
                    ], $tableColumns);

                    if ($center) {
                        $center->update($payload);
                        $result['updated']++;
                    } else {
                        $center = CostCenter::query()->create($payload + ['code' => 'ALR-' . $row['code']]);
                        $result['created']++;
                    }

                    $byCode[$row['code']] = $center->fresh();
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;
                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$row['row_number']}: " . $exception->getMessage();
                }
            }
        }

        $result['cost_center_links'] = $this->linkImportedRequestCostCentersToConstraints();

        return $result;
    }

    private function normalizeLegacyBalanceType($value): ?string
    {
        $value = $this->normalizeArabicText((string) $value);

        return match ($value) {
            'مدين', 'debtor', 'debit' => 'debtor',
            'دائن', 'داين', 'creditor', 'credit' => 'creditor',
            default => null,
        };
    }

    private function readRowsInChunks(string $path, array $columns, int $chunkSize = 2000): \Generator
    {
        $infoReader = IOFactory::createReaderForFile($path);
        $worksheetInfo = $infoReader->listWorksheetInfo($path);
        $highestRow = (int) ($worksheetInfo[0]['totalRows'] ?? 0);
        unset($infoReader, $worksheetInfo);

        $labelMap = [];
        foreach ($columns as $column) {
            $labelMap[$this->normalizeHeader($column['label'])] = $column['key'];
            $labelMap[$this->normalizeHeader($column['key'])] = $column['key'];
        }
        foreach ($this->importHeaderAliases() as $header => $key) {
            $labelMap[$this->normalizeHeader($header)] = $key;
        }

        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $endRow = min($highestRow, $startRow + $chunkSize - 1);
            $reader = IOFactory::createReaderForFile($path);

            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }

            $reader->setReadFilter(new class($startRow, $endRow) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function __construct(private int $startRow, private int $endRow)
                {
                }

                public function readCell($columnAddress, $row, $worksheetName = ''): bool
                {
                    return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
                }
            });

            $spreadsheet = $reader->load($path);
            try {
                $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                    $sheet->getHighestDataColumn()
                );
                $headers = [];

                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $header = $this->cellValue($sheet->getCell($this->cellCoordinate($column, 1))->getValue());
                    $headers[$column] = $labelMap[$this->normalizeHeader($header)] ?? $header;
                }

                for ($rowNumber = $startRow; $rowNumber <= $endRow; $rowNumber++) {
                    $row = [];
                    $hasValue = false;
                    $onlyTemplateMarkers = true;

                    for ($column = 1; $column <= $highestColumnIndex; $column++) {
                        $key = $headers[$column] ?? null;
                        if (!$key) {
                            continue;
                        }

                        $value = $this->cellValue($sheet->getCell($this->cellCoordinate($column, $rowNumber))->getValue());
                        $row[$key] = $value;

                        if ($value !== '') {
                            $hasValue = true;
                            if (!in_array($value, ['مطلوب', 'اختياري'], true)) {
                                $onlyTemplateMarkers = false;
                            }
                        }
                    }

                    if ($hasValue && !$onlyTemplateMarkers) {
                        yield $rowNumber => $row;
                    }
                }
            } finally {
                $spreadsheet->disconnectWorksheets();
                unset($sheet, $spreadsheet, $reader);
                gc_collect_cycles();
            }
        }
    }

    private function importFinanceConstraintGroup(string $reference, array $rows, ?int $adminId): string
    {
        if (count($rows) < 2) {
            throw new \InvalidArgumentException('القيد يجب أن يحتوي على سطرين على الأقل.');
        }

        $code = $this->resolveFinanceConstraintCode($rows);
        if ($this->financeConstraintsMissingOnly && $code && Constraint::query()->where('code', $code)->exists()) {
            return 'skipped';
        }

        $firstRow = $rows[0]['row'];
        $legacyDocumentType = $this->normalizeLegacyBondDocumentType($firstRow['document_type'] ?? null);
        $legacyDocumentNumber = $this->optionalInteger($firstRow['document_number'] ?? null);
        $date = $this->parseDate($firstRow['date'] ?? null);

        if (!$date) {
            throw new \InvalidArgumentException('تاريخ القيد مطلوب أو غير صحيح.');
        }

        $year = $this->resolveFinanceYear($date);
        if (!$year) {
            throw new \InvalidArgumentException('لا توجد سنة مالية نشطة تشمل تاريخ القيد.');
        }

        $statement = trim((string) ($firstRow['statement'] ?? ''))
            ?: (trim((string) ($firstRow['line_statement'] ?? '')) ?: '-');
        $createdByValue = collect($rows)
            ->map(fn ($item) => $item['row']['created_by'] ?? $item['row']['created_by_name'] ?? null)
            ->first(fn ($value) => trim((string) $value) !== '');
        $type = $this->resolveFinanceConstraintType($firstRow['type'] ?? null);
        $details = [];
        $debtorTotal = 0.0;
        $creditorTotal = 0.0;

        foreach ($rows as $item) {
            $row = $item['row'];
            $rowDate = $this->parseDate($row['date'] ?? null) ?: $date;

            if ($rowDate !== $date) {
                throw new \InvalidArgumentException('كل صفوف القيد الواحد يجب أن تحمل نفس التاريخ.');
            }

            $accountId = $this->resolveOrCreateFinanceConstraintAccountId($row, $adminId);
            if (!$accountId) {
                throw new \InvalidArgumentException('تعذر تحديد الحساب أو إنشاؤه داخل شجرة الحسابات.');
            }

            $line = $this->resolveFinanceConstraintLine($row);
            $costCenterId = $this->resolveFinanceCostCenterId($row, $accountId);
            $lineStatement = trim((string) ($row['line_statement'] ?? ''))
                ?: (trim((string) ($row['statement'] ?? '')) ?: $statement);

            $details[] = [
                'type' => $line['type'],
                'account_id' => $accountId,
                'cost_center_id' => $costCenterId,
                'debtor_value' => $line['debtor_value'],
                'creditor_value' => $line['creditor_value'],
                'statement' => $lineStatement,
                'year_id' => $year->id,
                'date' => $date,
            ];

            $debtorTotal += $line['debtor_value'];
            $creditorTotal += $line['creditor_value'];
        }

        if (round($debtorTotal, 2) !== round($creditorTotal, 2)) {
            throw new \InvalidArgumentException('إجمالي المدين يجب أن يساوي إجمالي الدائن.');
        }

        if (round($debtorTotal, 2) > 0 && (!collect($details)->contains('type', 'debtor') || !collect($details)->contains('type', 'creditor'))) {
            throw new \InvalidArgumentException('القيد يجب أن يحتوي على طرف مدين وطرف دائن.');
        }

        $constraint = $code ? Constraint::query()->where('code', $code)->first() : null;
        if ($this->financeConstraintsMissingOnly && $constraint) {
            return 'skipped';
        }

        $payload = [
            'type' => $type,
            'value' => round($debtorTotal, 2),
            'date' => $date,
            'statement' => $statement,
            'year_id' => $year->id,
            'code' => $code ?: $this->nextFinanceConstraintCode(),
            'created_by_id' => $this->resolveImportedAdminId($createdByValue, $adminId),
        ];
        if (Schema::hasColumn('fn_constraints', 'legacy_document_type')) {
            $payload['legacy_document_type'] = $legacyDocumentType;
            $payload['legacy_document_number'] = $legacyDocumentNumber;
            $payload['legacy_source_file'] = $firstRow['__source_file'] ?? null;
            $payload['legacy_source_row'] = $this->optionalInteger($firstRow['__source_row'] ?? null);
        }
        if (Schema::hasColumn('fn_constraints', 'legacy_invoice_number')) {
            $rentalRequestNumber = $this->extractLegacyRentalRequestNumber($statement);
            $invoiceNumber = $this->extractLegacyInvoiceNumber($statement);
            if ($rentalRequestNumber) {
                $payload['legacy_rental_order_number'] = $rentalRequestNumber;
                if ($invoiceNumber && RentalContractInvoice::query()->where('legacy_invoice_id', $invoiceNumber)->exists()) {
                    $payload['legacy_invoice_type'] = 'rental';
                    $payload['legacy_invoice_number'] = $invoiceNumber;
                }
            }
        }

        if ($constraint) {
            $constraint->update($payload);
            ConstraintDetails::query()->where('constraint_id', $constraint->id)->delete();
            $outcome = 'updated';
        } else {
            $constraint = Constraint::query()->create($payload);
            $outcome = 'created';
        }

        $now = now();
        ConstraintDetails::query()->insert(array_map(
            fn (array $detail) => $detail + [
                'constraint_id' => $constraint->id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $details
        ));

        if ($legacyDocumentType && $legacyDocumentNumber) {
            $bond = Bond::query()
                ->where('legacy_source', 'alrafedin_finance_bonds')
                ->where('type', $legacyDocumentType)
                ->where('code', $legacyDocumentNumber)
                ->first();
            if ($bond) {
                $this->attachLegacyBondToActualConstraint($bond, $constraint);
            }
        }

        return $outcome;
    }

    private function importFinanceAccountsFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        if (!class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            $result['error_count']++;
            $result['errors'][] = 'جدول الحسابات المالية غير موجود.';

            return $result;
        }

        $rows = [];
        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;

            $code = $this->normalizeFinanceAccountCode($row['account_code'] ?? null);
            $name = trim((string) ($row['account_name'] ?? $row['name'] ?? ''));
            $level = $this->optionalInteger($row['level'] ?? null);

            if (!$code || $name === '' || !$level) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$rowNumber}: المستوى ورقم الحساب واسم الحساب مطلوبة.";
                }

                continue;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'code' => $code,
                'name' => $name,
                'level' => $level,
                'parent_name' => trim((string) ($row['parent_name'] ?? '')),
                'status' => $this->normalizeFinanceAccountStatus($row['account_status'] ?? $row['status'] ?? null),
                'statement_type' => $this->normalizeFinanceStatementType($row['statement_type'] ?? null),
            ];
        }

        usort($rows, fn ($left, $right) => [$left['level'], $left['code']] <=> [$right['level'], $right['code']]);

        $accountsByCode = Account::query()->get()->keyBy(fn ($account) => (string) $account->code);
        $accountsByLevelName = [];
        $accountTableColumns = array_flip(Schema::getColumnListing('fn_accounts'));

        foreach ($accountsByCode as $account) {
            $accountsByLevelName[(int) $account->level][$this->normalizeArabicText($account->name)][] = $account;
        }

        foreach ($rows as $row) {
            try {
                $parent = null;

                if ($row['level'] > 1) {
                    $parent = $this->resolveImportedFinanceAccountParent($row, $accountsByCode, $accountsByLevelName);

                    if (!$parent) {
                        throw new \InvalidArgumentException('تعذر تحديد الحساب الأصل: ' . ($row['parent_name'] ?: '-'));
                    }
                }

                $existing = $accountsByCode[(string) $row['code']]
                    ?? Account::query()->where('code', $row['code'])->first();
                $type = $this->resolveImportedFinanceAccountType($row, $parent, $existing);
                $payload = [
                    'name' => $row['name'],
                    'operation_type' => $parent?->operation_type ?? 1,
                    'status' => $row['status'],
                    'statement_type' => $row['statement_type'] ?: ($parent?->statement_type),
                    'level' => $row['level'],
                    'type' => $type,
                    'parent_id' => $parent?->id,
                    'created_by_id' => $existing?->created_by_id ?? $adminId ?? auth('admin')->id() ?? 1,
                ];

                $payload = array_intersect_key($payload, $accountTableColumns);

                if ($existing) {
                    $existing->update($payload);
                    $account = $existing;
                    $result['updated']++;
                } else {
                    $account = Account::query()->create($payload + ['code' => $row['code']]);
                    $result['created']++;
                }

                $accountsByCode[(string) $account->code] = $account;
                $accountsByLevelName[(int) $account->level][$this->normalizeArabicText($account->name)][] = $account;
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "صف {$row['row_number']}: " . $exception->getMessage();
                }
            }
        }

        return $result;
    }

    private function importLegacyInvoicesFile(string $storedPath, array $columns, ?int $adminId, array $result): array
    {
        if (!Schema::hasTable('invoices')) {
            $result['error_count']++;
            $result['errors'][] = 'جدول الفواتير غير موجود.';

            return $result;
        }

        $context = [
            'users_by_legacy_number' => [],
            'users_by_phone' => [],
            'users_by_name' => [],
            'admin_ids_by_source_name' => [],
            // نطابق رقم الطلب الخارجي أولاً، ولا نخلطه مع id داخلي من نوع عقد مختلف.
            'contract_ids' => Contract::query()->pluck('id')->flip()->all(),
            'has_contract_legacy_numbers' => Schema::hasColumn('contracts', 'arabia_order_number'),
            'contract_ids_by_legacy_number' => Schema::hasColumn('contracts', 'arabia_order_number')
                ? Contract::query()->whereNotNull('arabia_order_number')->pluck('id', 'arabia_order_number')->all()
                : [],
            'contract_branch_ids' => Schema::hasColumn('contracts', 'branch_id')
                ? Contract::query()->pluck('branch_id', 'id')->all()
                : [],
            'import_branch_id' => $adminId && Schema::hasColumn('admins', 'branch_id')
                ? Admin::query()->whereKey($adminId)->value('branch_id')
                : null,
            'existing_invoice_ids' => Invoice::query()->pluck('id')->flip()->all(),
            'legacy_invoice_ids' => Schema::hasColumn('invoices', 'legacy_invoice_number')
                ? Invoice::query()->whereNotNull('legacy_invoice_number')->pluck('id', 'legacy_invoice_number')->all()
                : [],
            'invoice_columns' => array_flip(Schema::getColumnListing('invoices')),
            'user_columns' => array_flip(Schema::getColumnListing('users')),
        ];

        foreach ($this->readLegacyInvoiceRows(Storage::path($storedPath), $columns) as $rowLabel => $row) {
            $result['total']++;

            try {
                DB::transaction(function () use ($row, $adminId, &$result, &$context) {
                    $outcome = $this->importLegacyInvoice($row, $adminId, $context);
                    $result[$outcome]++;
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < self::MAX_ERROR_DETAILS) {
                    $result['errors'][] = "{$rowLabel}: " . $exception->getMessage();
                }
            }
        }

        $result['source_files_attached'] = $this->attachLegacyInvoiceSourceFiles();

        return $result;
    }

    private function attachLegacyInvoiceSourceFiles(): int
    {
        if (!Schema::hasColumn('invoices', 'legacy_source_file')) {
            return 0;
        }

        $directory = base_path('migration_system/data/alrafedin/finance/invoices');
        $filesByInvoice = [
            8228 => 'فاتورة مُوّرِد.pdf',
            8229 => 'اشعار دائن ضريبي - استرداد طلب 2392 .. طلب استقدام_ 2392.pdf',
            8230 => 'اشعار مدين ضريبي - خصم الاسترداد _ استرداد طلب_ 2392 فاتورة رقم_ 8229 - العميل_ حسين عبدالكريم حسين النمر - HUSEEN _ 2634.pdf',
            8231 => 'اشعار دائن ضريبي - استرداد طلب 2372 .. طلب استقدام_ 2372.pdf',
            8232 => 'اشعار مدين ضريبي - خصم الاسترداد _ استرداد طلب_ 2372 فاتورة رقم_ 8231 - العميل_ هنيه موسى جعفر آل خزعل - HANEEH _ 2595.pdf',
            8233 => 'اشعار دائن ضريبي - استرداد طلب 2296 .. طلب استقدام_ 2296.pdf',
            8234 => 'اشعار دائن ضريبي - استرداد طلب 2056 .. طلب استقدام_ 2056.pdf',
        ];

        $attached = 0;
        foreach ($filesByInvoice as $invoiceNumber => $filename) {
            if (!is_file($directory . DIRECTORY_SEPARATOR . $filename)) {
                continue;
            }

            $attached += Invoice::query()
                ->where('legacy_invoice_number', $invoiceNumber)
                ->update(['legacy_source_file' => $filename]);
        }

        return $attached;
    }

    private function readLegacyInvoiceRows(string $path, array $columns): \Generator
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);

        $labelMap = [];
        foreach ($columns as $column) {
            $labelMap[$this->normalizeHeader($column['label'])] = $column['key'];
            $labelMap[$this->normalizeHeader($column['key'])] = $column['key'];
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetTitle = (string) $sheet->getTitle();
            if ($this->normalizeArabicText($sheetTitle) === $this->normalizeArabicText('الملخص')) {
                continue;
            }

            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();

            // قراءة صف العناوين دفعة واحدة بدل خلية بخلية (أسرع بكثير للملفات الكبيرة).
            $headerCells = $sheet->rangeToArray("A1:{$highestColumn}1", null, false, false, false)[0] ?? [];
            $headers = [];
            foreach ($headerCells as $offset => $headerValue) {
                $header = $this->cellValue($headerValue);
                $headers[$offset] = $labelMap[$this->normalizeHeader($header)] ?? null;
            }

            if (!in_array('invoice_number', $headers, true)) {
                continue;
            }

            // قراءة الصفوف على دفعات لتقليل عدد استدعاءات المكتبة مع الحفاظ على الذاكرة.
            $chunkSize = 2000;
            for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
                $endRow = min($startRow + $chunkSize - 1, $highestRow);
                $rows = $sheet->rangeToArray("A{$startRow}:{$highestColumn}{$endRow}", null, false, false, false);

                foreach ($rows as $rowOffset => $cells) {
                    $rowNumber = $startRow + $rowOffset;
                    $row = [
                        'source_sheet' => $sheetTitle,
                        'source_row' => $rowNumber,
                    ];

                    foreach ($cells as $offset => $cellValue) {
                        $key = $headers[$offset] ?? null;
                        if (!$key) {
                            continue;
                        }

                        $row[$key] = $this->cellValue($cellValue);
                    }

                    $values = collect($row)
                        ->except(['source_sheet', 'source_row'])
                        ->filter(fn ($value) => $value !== null && $value !== '');

                    if ($values->isNotEmpty() && !ImportValueParser::isMarkerRow($row)) {
                        yield "شيت {$sheetTitle} صف {$rowNumber}" => $row;
                    }
                }
            }
        }
    }

    private function importLegacyInvoice(array $row, ?int $adminId, array &$context): string
    {
        $invoiceNumber = $this->optionalInteger($row['invoice_number'] ?? null);
        if (!$invoiceNumber) {
            throw new \InvalidArgumentException('رقم الفاتورة مطلوب وغير صحيح.');
        }

        $invoiceDate = $this->parseDateTime($row['invoice_date'] ?? null) ?: now();
        $subtotal = $this->decimal($row['subtotal'] ?? null);
        $totalDue = $this->decimal($row['total_due'] ?? null);
        $vatAmount = $this->decimal($row['vat_amount'] ?? null) ?? 0.0;

        if ($subtotal === null && $totalDue === null) {
            throw new \InvalidArgumentException('المبلغ أو المبلغ شامل الضريبة مطلوب.');
        }

        $subtotal ??= $this->roundMoney((float) $totalDue - (float) $vatAmount);
        $totalDue ??= $this->roundMoney((float) $subtotal + (float) $vatAmount);

        // مطابقة الفاتورة مع العقد عن طريق رقم العقد (وهو نفسه id العقد في النظام).
        $contractId = $this->resolveLegacyInvoiceContract($row, $context);
        $client = $contractId ? null : $this->resolveLegacyInvoiceClient($row, $adminId, $context);
        $legacyZatcaShared = $this->truthy($row['legacy_zatca_shared'] ?? null, false);

        $payload = array_intersect_key([
            'contract_id' => $contractId,
            'branch_id' => $contractId
                ? ($context['contract_branch_ids'][$contractId] ?? $context['import_branch_id'])
                : $context['import_branch_id'],
            'ingaz_delegation_id' => null,
            'client_id' => $contractId ? null : $client?->id,
            'insurance_company' => null,
            'general_description' => $this->legacyInvoiceDescription($row),
            'general_direct_label' => $this->legacyInvoiceItemLabel($row),
            'general_external_label' => null,
            'general_government_label' => null,
            'general_ticket_label' => null,
            'direct_recruitment_cost' => $subtotal,
            'external_recruitment_cost' => 0,
            'government_cost' => 0,
            'internal_ticket_cost' => 0,
            'visa_recruited_count' => 0,
            'direct_recruitment_count' => $subtotal != 0.0 ? 1 : 0,
            'external_recruitment_count' => 0,
            'insured_count' => 0,
            'government_cost_count' => 0,
            'internal_ticket_count' => 0,
            'subtotal' => $subtotal,
            'discount' => 0,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
            // حسب اعتماد الإدارة، جميع فواتير الرافدين المستوردة مسددة.
            'is_paid' => 1,
            'paid_at' => $invoiceDate,
            // المشاركة السابقة تحفظ مستقلة حتى لا يعاد إرسال الفاتورة نفسها إلى الهيئة.
            'send_to_zatca' => 0,
            'legacy_invoice_number' => $invoiceNumber,
            'legacy_invoice_type' => $this->nullableTrimmedString($row['invoice_type'] ?? null),
            'legacy_document_type' => $this->nullableTrimmedString($row['document_type'] ?? null),
            'legacy_client_number' => $this->nullableTrimmedString($row['legacy_client_number'] ?? null),
            'legacy_order_number' => $this->nullableTrimmedString($row['legacy_order_number'] ?? null),
            'legacy_zatca_shared' => $legacyZatcaShared,
            'legacy_source_sheet' => $this->nullableTrimmedString($row['source_sheet'] ?? null),
            'legacy_source_row' => $this->optionalInteger($row['source_row'] ?? null),
            'legacy_source_payload' => $this->legacyInvoiceSourcePayload($row),
            'created_by' => $this->resolveLegacyInvoiceAdminId(
                $row['created_by_name'] ?? $row['created_by'] ?? null,
                $adminId,
                $context
            ),
            'created_at' => $invoiceDate,
            'updated_at' => $invoiceDate,
        ], $context['invoice_columns']);

        // إعادة رفع الملف تحدث نفس الفاتورة القديمة ولا تنشئ نسخة ثانية.
        if (isset($context['legacy_invoice_ids'][$invoiceNumber])) {
            Invoice::query()->whereKey($context['legacy_invoice_ids'][$invoiceNumber])->update($payload);

            return 'updated';
        }

        // لا نستبدل فاتورة أصلية حديثة إذا تصادف رقمها مع رقم قديم.
        if (isset($context['existing_invoice_ids'][$invoiceNumber])) {
            throw new \RuntimeException("رقم الفاتورة {$invoiceNumber} مستخدم في فاتورة غير مستوردة؛ لم يتم استبدالها.");
        }

        Invoice::query()->create(['id' => $invoiceNumber] + $payload);
        $context['existing_invoice_ids'][$invoiceNumber] = true;
        $context['legacy_invoice_ids'][$invoiceNumber] = $invoiceNumber;

        return 'created';
    }

    private function resolveLegacyInvoiceClient(array $row, ?int $adminId, array &$context): ?User
    {
        $legacyClientNumber = $this->optionalInteger($row['legacy_client_number'] ?? null);
        $name = trim((string) ($row['client_name'] ?? ''));
        $phone = $this->legacyInvoicePhone($row['client_phone'] ?? null);

        if ($legacyClientNumber && array_key_exists($legacyClientNumber, $context['users_by_legacy_number'])) {
            return $context['users_by_legacy_number'][$legacyClientNumber];
        }

        if ($phone !== '' && array_key_exists($phone, $context['users_by_phone'])) {
            return $context['users_by_phone'][$phone];
        }

        if ($name !== '' && array_key_exists($name, $context['users_by_name'])) {
            return $context['users_by_name'][$name];
        }

        if ($legacyClientNumber) {
            $user = Schema::hasColumn('users', 'arabia_client_number')
                ? User::query()->where('arabia_client_number', $legacyClientNumber)->first()
                : null;
            if ($user) {
                $this->rememberLegacyInvoiceUser($context, $user, $legacyClientNumber, $phone, $name);
                return $user;
            }
        }

        if ($phone !== '') {
            $user = $this->findUserByPhone($phone);
            if ($user) {
                $this->rememberLegacyInvoiceUser($context, $user, $legacyClientNumber, $phone, $name);
                return $user;
            }
        }

        if ($name !== '') {
            $user = User::query()->where('name', $name)->first();
            if ($user) {
                $this->rememberLegacyInvoiceUser($context, $user, $legacyClientNumber, $phone, $name);
                return $user;
            }
        }

        if ($name === '' && $phone === '') {
            if ($legacyClientNumber) {
                $context['users_by_legacy_number'][$legacyClientNumber] = null;
            }

            return null;
        }

        $fallbackPhone = $phone !== ''
            ? $phone
            : 'legacy-invoice-client-' . ($legacyClientNumber ?: substr(sha1($name), 0, 16));

        $payload = [
            'name' => $name !== '' ? $name : ('عميل فاتورة قديمة #' . ($legacyClientNumber ?: 'بدون رقم')),
            'phone_code' => '+966',
            'phone' => $fallbackPhone,
            'register_from' => 'legacy_invoices_import',
            'created_by' => $adminId,
            'arabia_client_number' => $legacyClientNumber,
        ];

        if (Schema::hasColumn('users', 'password')) {
            $payload['password'] = $this->defaultImportPasswordHash();
        }

        $user = User::query()->create(array_intersect_key($payload, $context['user_columns']));
        $this->rememberLegacyInvoiceUser($context, $user, $legacyClientNumber, $phone, $name);

        return $user;
    }

    private function rememberLegacyInvoiceUser(array &$context, ?User $user, ?int $legacyClientNumber, string $phone, string $name): void
    {
        if ($legacyClientNumber) {
            $context['users_by_legacy_number'][$legacyClientNumber] = $user;
        }

        if ($phone !== '') {
            $context['users_by_phone'][$phone] = $user;
        }

        if ($name !== '') {
            $context['users_by_name'][$name] = $user;
        }
    }

    private function resolveLegacyInvoiceAdminId($value, ?int $fallbackAdminId, array &$context): int
    {
        $sourceName = trim((string) $value);
        if (array_key_exists($sourceName, $context['admin_ids_by_source_name'])) {
            return $context['admin_ids_by_source_name'][$sourceName];
        }

        $adminId = $this->resolveAdminId($sourceName);
        if (!$adminId && str_contains($sourceName, '/')) {
            foreach (array_filter(array_map('trim', explode('/', $sourceName))) as $candidate) {
                $adminId = $this->resolveAdminId($candidate);
                if ($adminId) {
                    break;
                }
            }
        }

        return $context['admin_ids_by_source_name'][$sourceName] = $adminId
            ?? $fallbackAdminId
            ?? auth('admin')->id()
            ?? auth()->id()
            ?? 1;
    }

    private function resolveLegacyInvoiceContract(array $row, array $context): ?int
    {
        $invoiceType = $this->normalizeArabicText($row['invoice_type'] ?? null);
        $recruitmentTypes = [
            $this->normalizeArabicText('فاتورة ايراد / طلب استقدام'),
            $this->normalizeArabicText('إغلاق'),
        ];
        if (!in_array($invoiceType, $recruitmentTypes, true)) {
            return null;
        }

        $contractNumber = $this->optionalInteger($row['legacy_order_number'] ?? null);

        if ($contractNumber === null) {
            return null;
        }

        if ($context['has_contract_legacy_numbers']) {
            return $context['contract_ids_by_legacy_number'][$contractNumber] ?? null;
        }

        return isset($context['contract_ids'][$contractNumber]) ? $contractNumber : null;
    }

    private function legacyInvoiceDescription(array $row): string
    {
        return collect([
            'استيراد فواتير ',
            'نوع الفاتورة: ' . trim((string) ($row['invoice_type'] ?? '-')),
            'نوع المستند: ' . trim((string) ($row['document_type'] ?? '-')),
            'رقم العميل القديم: ' . trim((string) ($row['legacy_client_number'] ?? '-')),
            'رقم الطلب القديم: ' . trim((string) ($row['legacy_order_number'] ?? '-')),
            'بواسطة: ' . trim((string) ($row['created_by_name'] ?? '-')),
            'مشارك Zatca في الملف القديم: ' . trim((string) ($row['legacy_zatca_shared'] ?? '-')),
            'مصدر الصف: ' . trim((string) ($row['source_sheet'] ?? '-')) . ' / ' . trim((string) ($row['source_row'] ?? '-')),
        ])->implode("\n");
    }

    private function legacyInvoiceSourcePayload(array $row): array
    {
        return collect($row)
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : (string) $value)
            ->all();
    }

    private function nullableTrimmedString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function legacyInvoiceItemLabel(array $row): string
    {
        $type = trim((string) ($row['invoice_type'] ?? ''));
        $documentType = trim((string) ($row['document_type'] ?? ''));

        return trim(collect([$documentType, $type])->filter()->implode(' - ')) ?: 'فاتورة مستوردة';
    }

    private function legacyInvoicePhone($phone): string
    {
        $raw = trim((string) $phone);
        if ($raw === '' || str_contains($raw, '*')) {
            return '';
        }

        $normalized = $this->normalizeImportPhone($raw);

        return in_array($normalized, ['0', '00', '966'], true) ? '' : $normalized;
    }

    private function resolveImportedFinanceAccountParent(array $row, $accountsByCode, array $accountsByLevelName): ?Account
    {
        $parentName = $this->normalizeArabicText($row['parent_name']);
        if ($parentName !== '') {
            $matches = $accountsByLevelName[$row['level'] - 1][$parentName] ?? [];
            $matches = collect($matches)->filter(fn ($account) => (int) $account->level === $row['level'] - 1)->values();

            if ($matches->count() === 1) {
                return $matches->first();
            }

            if ($matches->count() > 1) {
                $prefixMatches = $matches->filter(fn ($account) => str_starts_with((string) $row['code'], (string) $account->code))->values();
                if ($prefixMatches->count() === 1) {
                    return $prefixMatches->first();
                }
            }
        }

        $code = (string) $row['code'];
        while (strlen($code) > 1) {
            $code = substr($code, 0, -1);
            $candidate = $accountsByCode[$code] ?? null;

            if ($candidate && (int) $candidate->level === $row['level'] - 1) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeFinanceAccountCode($value): ?int
    {
        $value = trim($this->normalizeDigits((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d]/', '', $value);

        return $value === '' ? null : (int) $value;
    }

    private function normalizeFinanceAccountStatus($value): string
    {
        $normalized = $this->normalizeArabicText((string) $value);

        return in_array($normalized, ['دائن', 'داين', 'creditor', 'credit', 'cr'], true)
            ? 'creditor'
            : 'debtor';
    }

    private function normalizeFinanceStatementType($value): ?string
    {
        $normalized = $this->normalizeArabicText((string) $value);

        return match ($normalized) {
            'قائمه دخل', 'قائمة دخل', 'دخل', 'income', 'income statement', 'income_statement' => 'income_statement',
            'ميزانيه', 'ميزانية', 'balance', 'balance sheet', 'balance_sheet' => 'balance_sheet',
            default => null,
        };
    }

    private function resolveImportedFinanceAccountType(array $row, ?Account $parent, ?Account $existing): int
    {
        $name = $this->normalizeArabicText($row['name']);

        if (str_contains($name, 'عملاء')) {
            return AccountTypesEnum::Clients->value;
        }

        if ($parent && $this->financeAccountTypeValue($parent) === AccountTypesEnum::Clients->value) {
            return AccountTypesEnum::Client->value;
        }

        if (str_contains($name, 'موردين')) {
            return AccountTypesEnum::Suppliers->value;
        }

        if ($parent && $this->financeAccountTypeValue($parent) === AccountTypesEnum::Suppliers->value) {
            return AccountTypesEnum::Supplier->value;
        }

        if (str_contains($name, 'بنوك') || str_contains($name, 'البنوك')) {
            return AccountTypesEnum::Banks->value;
        }

        if ($parent && $this->financeAccountTypeValue($parent) === AccountTypesEnum::Banks->value) {
            return AccountTypesEnum::Bank->value;
        }

        if (str_contains($name, 'النقديه') || str_contains($name, 'النقدية') || str_contains($name, 'صندوق')) {
            return $parent && $this->financeAccountTypeValue($parent) === AccountTypesEnum::Boxs->value
                ? AccountTypesEnum::Box->value
                : AccountTypesEnum::Boxs->value;
        }

        if ($parent && $this->financeAccountTypeValue($parent) === AccountTypesEnum::Boxs->value) {
            return AccountTypesEnum::Box->value;
        }

        return (int) ($existing?->getRawOriginal('type') ?? AccountTypesEnum::NormalAccount->value);
    }

    private function financeAccountTypeValue(Account $account): int
    {
        $raw = $account->getRawOriginal('type');

        return is_numeric($raw) ? (int) $raw : AccountTypesEnum::NormalAccount->value;
    }

    private function importOperationalInvoice(array $row): string
    {
        $row = array_intersect_key($row, array_flip(array_column($this->templates->template('invoices')['columns'], 'key')));
        $payload = array_filter($row, fn ($value) => $value !== '' && $value !== null);
        unset($payload['import_reference']);
        $payload['send_to_zatca'] = (bool) ($row['send_to_zatca'] ?? false);
        $payload['invoice_date'] = $this->parseDate($payload['invoice_date'] ?? null);
        foreach (['direct_recruitment_cost', 'external_recruitment_cost', 'government_cost', 'internal_ticket_cost',
            'direct_recruitment_count', 'external_recruitment_count', 'government_cost_count', 'internal_ticket_count', 'discount'] as $key) {
            if (isset($payload[$key])) $payload[$key] = $this->decimal($payload[$key]);
        }
        $request = \Illuminate\Http\Request::create('/admin/invoices', 'POST', $payload);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => auth('admin')->user());
        $response = app(\App\Http\Controllers\ingaz\InvoiceController::class)->issueImportedInvoice(
            $request,
            app(\Nami\Finance\Services\ConstraintService::class),
            app(\Nami\Finance\Services\YearService::class),
            app(\Nami\Finance\Services\BondService::class)
        );
        $data = $response->getData(true);
        if ($response->getStatusCode() >= 400 || empty($data['success']) || !empty($data['is_draft'])) {
            throw new \InvalidArgumentException($data['message'] ?? 'تعذر إصدار الفاتورة.');
        }
        return 'created';
    }

    private function importClient(array $row, ?int $adminId): string
    {
        $phone = $this->normalizeImportPhone($row['phone'] ?? '');
        $name = trim((string) ($row['name'] ?? ''));
        $idNumber = $this->normalizeIdNumber($row['id_number'] ?? $row['national_num'] ?? null);
        $databaseId = $this->optionalInteger($row['id'] ?? null);

        if ($phone === '' || $name === '' || $idNumber === '') {
            throw new \InvalidArgumentException('اسم العميل ورقم الجوال ورقم الهوية مطلوبة.');
        }

        $userByPhone = $this->findUserByPhone($phone);
        $client = $databaseId ? User::query()->whereKey($databaseId)->first() : null;
        $client = $client ?: User::query()->where('national_num', $idNumber)->first();
        if (!$client && $userByPhone
            && (blank($userByPhone->national_num)
                || $this->normalizeIdNumber($userByPhone->national_num) === $idNumber)) {
            $client = $userByPhone;
        }
        $phoneIsDuplicate = $userByPhone !== null
            && (!$client || (int) $userByPhone->id !== (int) $client->id);
        if ($phoneIsDuplicate) {
            throw new \InvalidArgumentException('الجوال مرتبط بعميل آخر بهوية مختلفة.');
        }
        if ($client && filled($client->national_num) && $this->normalizeIdNumber($client->national_num) !== $idNumber) {
            throw new \InvalidArgumentException('معرف العميل لا يطابق رقم الهوية.');
        }
        $databaseIdExists = $databaseId ? User::whereKey($databaseId)->exists() : false;
        $createdBy = $this->resolveImportedAdminId(
            $row['created_by'] ?? $row['created_by_name'] ?? null,
            $adminId
        );
        $accountId = $this->resolveOrCreateClientAccountId($row['account_id'] ?? null, $name, $adminId);
        $payload = $this->filterColumns('users', [
            'id' => $databaseIdExists ? null : $databaseId,
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phoneIsDuplicate
                ? $this->uniqueDuplicateClientImportPhone($phone, $idNumber, $databaseId)
                : $phone,
            'email' => $row['email'] ?? null,
            'national_num' => $idNumber,
            'nationality_id' => $this->resolveCountryId($row['nationality'] ?? null),
            'birthdate' => $this->parseDate($row['birthdate'] ?? null),
            'marital_status' => $this->normalizeClientMaritalStatus($row['marital_status'] ?? null),
            'phone2' => $this->normalizeOptionalPhone($row['phone2'] ?? null) ?: ($phoneIsDuplicate ? $phone : null),
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'account_id' => $accountId,
            'client_category_id' => $this->resolveClientCategoryId($row['client_category'] ?? null),
            'rooms_num' => $row['rooms_num'] ?? null,
            'family_num' => $row['family_num'] ?? null,
            'children' => $row['children'] ?? null,
            'job' => $row['job'] ?? null,
            'register_from' => 'data_import',
            'is_blocked' => $this->normalizeStatus($row['status'] ?? null) === 'blocked' ? 1 : 0,
            'created_by' => $createdBy,
        ]);

        if (Schema::hasColumn('users', 'password')) {
            $payload['password'] = $this->defaultImportPasswordHash();
        }

        $payload = array_filter($payload, fn ($value) => $value !== null && $value !== '');

        if ($client) {
            unset($payload['id'], $payload['password']);

            if ($phoneIsDuplicate) {
                unset($payload['phone'], $payload['phone_code']);
                if (Schema::hasColumn('users', 'phone2') && blank($client->phone2)) {
                    $payload['phone2'] = $phone;
                } elseif (blank($row['phone2'] ?? null)) {
                    unset($payload['phone2']);
                }
            }

            $client->update($payload);

            return 'updated';
        }

        User::create($payload);

        return 'created';
    }

    private function uniqueDuplicateClientImportPhone(string $phone, string $idNumber, ?int $databaseId): string
    {
        $base = 'data-import-duplicate-client-' . substr(sha1($phone . '|' . $idNumber . '|' . ($databaseId ?? '')), 0, 20);
        $candidate = $base;
        $suffix = 1;

        while (User::query()->where('phone', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function importComplaint(array $row, ?int $adminId): string
    {
        $externalRef = trim((string) ($row['external_ref'] ?? ''));
        $typeName = trim((string) ($row['complaint_type'] ?? '')) ?: 'شكوى مستوردة';
        $complainantName = trim((string) ($row['complainant_name'] ?? '')) ?: 'غير محدد';
        $complainantType = $this->normalizeComplaintPartyType($row['complainant_type'] ?? null);
        $againstType = $this->normalizeComplaintPartyType($row['against_type'] ?? null);
        $complainantPhone = $this->normalizeImportPhone($row['complainant_phone'] ?? '');
        $content = trim((string) ($row['details'] ?? $row['content'] ?? $row['message'] ?? ''));
        $subjectValue = trim((string) ($row['subject'] ?? ''));

        if ($content === '') {
            $content = $typeName;
        }

        if ($subjectValue === '') {
            $subjectValue = $externalRef !== '' ? "[{$externalRef}] {$typeName}" : $typeName;
        }

        $user = $this->resolveComplaintUser($row, $complainantPhone);

        $complaintType = $this->resolveComplaintType($row, $typeName);
        $createdAt = $this->parseDateTime($row['created_at'] ?? null)
            ?: $this->parseDateTime($row['source_created_at'] ?? null)
            ?: $this->parseDateTime($row['complaint_date'] ?? null)
            ?: now();
        $closedAt = $this->parseDateTime($row['closed_at'] ?? null);
        $resolvedAt = $closedAt;
        $status = $this->normalizeComplaintStatus($row['status'] ?? null);

        if (!$resolvedAt && ($status === 'resolved' || $status === 'closed')) {
            $resolvedAt = $createdAt;
        }

        $details = $this->buildImportedComplaintDetails($row, $content, $externalRef);
        $subject = Str::limit($subjectValue, 255, '');
        $complaint = $this->findExistingImportedComplaint($row, $subject, $externalRef, $user?->id);

        $payload = $this->filterColumns('complaints', [
            'complaint_type_id' => $complaintType->id,
            'external_ref' => $this->nullableImportString($externalRef),
            'source_sequence' => $this->nullableImportString($row['source_sequence'] ?? null),
            'address' => trim((string) ($row['submission_method'] ?? '')) ?: 'استيراد الشكاوي',
            'message' => Str::limit($content, 1000, ''),
            'details' => $details,
            'response' => $this->nullableImportString($row['response'] ?? $row['last_response'] ?? null),
            'against_name' => $this->nullableImportString($row['against_name'] ?? null),
            'against_type' => $againstType,
            'against_phone' => $this->nullableImportString($row['against_phone'] ?? null),
            'last_response' => $this->nullableImportString($row['last_response'] ?? $row['response'] ?? null),
            'submission_method' => $this->nullableImportString($row['submission_method'] ?? null),
            'related_to' => $this->nullableImportString($row['related_to'] ?? null),
            'status' => $status,
            'priority' => $this->normalizeComplaintPriority($row['priority'] ?? null),
            'assigned_to' => $this->resolveAdminId($row['assigned_to'] ?? $row['source_created_by'] ?? $row['created_by'] ?? null),
            'user_id' => $user?->id,
            'complainant_name' => $complainantName,
            'complainant_phone' => $complainantPhone,
            'complainant_type' => $complainantType,
            'maid_nationality_id' => $this->resolveNationalityId($row['maid_nationality'] ?? null),
            'subject' => $subject,
            'complaint_date' => $this->parseDate($row['complaint_date'] ?? null),
            'source_created_by' => $this->nullableImportString($row['source_created_by'] ?? $row['created_by'] ?? null),
            'source_created_at' => $this->parseDateTime($row['source_created_at'] ?? null),
            'source_status' => $this->nullableImportString($row['status'] ?? null),
            'closed_at' => $closedAt,
            'closed_by_name' => $this->nullableImportString($row['closed_by_name'] ?? null),
            'import_payload' => $this->complaintImportPayload($row),
            'created_at' => $createdAt,
            'resolved_at' => $resolvedAt,
            'resolved_by' => $resolvedAt
                ? ($this->resolveAdminId($row['closed_by_name'] ?? null) ?: $adminId)
                : null,
        ]);

        if ($complaint) {
            unset($payload['created_at']);
            $complaint->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));

            return 'updated';
        }

        Complaint::query()->create($payload);

        return 'created';
    }

    private function importRecruitmentOffice(array $row): string
    {
        $titleAr = trim((string) ($row['title_ar'] ?? $row['title'] ?? ''));
        $titleEn = trim((string) ($row['title_en'] ?? ''));
        $nationalityId = $this->resolveNationalityId($row['nationality'] ?? null);
        $databaseId = $this->optionalInteger($row['id'] ?? null);

        if ($titleAr === '') {
            throw new \InvalidArgumentException('اسم المكتب بالعربية مطلوب.');
        }

        if (!$nationalityId) {
            throw new \InvalidArgumentException('الجنسية مطلوبة أو غير موجودة في النظام.');
        }

        $office = $databaseId ? RecruitmentOffices::query()->whereKey($databaseId)->first() : null;
        $office = $office ?: $this->findRecruitmentOfficeByTitle($titleAr, $titleEn, $nationalityId);

        $payload = $this->filterColumns('recruitment_offices', [
            'id' => $databaseId,
            'nationality_id' => $nationalityId,
            'status' => $this->normalizeOfficeStatus($row['status'] ?? null),
            'is_show' => $this->truthy($row['is_show'] ?? null, false) ? 1 : 0,
            'phone_office' => $row['phone_office'] ?? null,
            'license_number' => $row['license_number'] ?? null,
            'street' => $row['street'] ?? null,
            'manager_name' => $row['manager_name'] ?? null,
            'manager_phone' => $row['manager_phone'] ?? null,
            'manager_email' => $row['manager_email'] ?? null,
            'bank_address' => $row['bank_address'] ?? null,
            'bank_name' => $row['bank_name'] ?? null,
            'bank_account_name' => $row['bank_account_name'] ?? null,
            'bank_swift_code' => $row['bank_swift_code'] ?? null,
            'bank_iban' => $row['bank_iban'] ?? null,
            'bank_account' => $row['bank_account'] ?? null,
            'cost_of_dollar' => $row['cost_of_dollar'] ?? null,
            'cost_of_other' => $row['cost_of_other'] ?? null,
        ]);

        $created = false;
        if ($office) {
            unset($payload['id']);
            $office->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $office = RecruitmentOffices::create($payload);
            $created = true;
        }

        $office->translateOrNew('ar')->title = $titleAr;
        $office->translateOrNew('en')->title = $titleEn !== '' ? $titleEn : $titleAr;
        $office->save();

        return $created ? 'created' : 'updated';
    }

    private function importEmployee(array $row): string
    {
        if (!Schema::hasTable('hr_employee')) {
            throw new \InvalidArgumentException('جدول الموظفين غير موجود.');
        }

        $name = trim((string) ($row['name'] ?? ''));
        $databaseId = $this->optionalInteger($row['id'] ?? null);
        $code = trim((string) ($row['code'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $nationalNumber = $this->normalizeIdNumber($row['national_num'] ?? $row['id_number'] ?? null);
        $departmentId = $this->resolveHrDepartmentId($row['department'] ?? $row['department_id'] ?? $row['deparment_id'] ?? null);
        $jobId = $this->resolveHrJobId($row['job'] ?? $row['job_id'] ?? null);
        $countryId = $this->resolveCountryId($row['country'] ?? $row['nationality'] ?? $row['country_id'] ?? null);
        $shiftId = $this->resolveHrShiftId($row['shift'] ?? $row['shift_id'] ?? null);

        if ($name === '') {
            throw new \InvalidArgumentException('اسم الموظف مطلوب.');
        }

        $employee = $this->findEmployee($databaseId, $code, $nationalNumber, $email);
        $creating = !$employee;

        if ($creating) {
            $this->assertEmployeeCreateData($row, $phone, $email, $nationalNumber, $departmentId, $jobId, $countryId, $shiftId);
        }

        $salary = $this->decimal($row['salary'] ?? null);
        $payload = $this->filterColumns('hr_employee', [
            'id' => $databaseId,
            'name' => $name,
            'admin_id' => $this->resolveAdminId($row['admin_id'] ?? $row['admin'] ?? null),
            'code' => $code !== '' ? $code : null,
            'phone' => $phone !== '' ? $phone : null,
            'birthday' => $this->parseDate($row['birthday'] ?? $row['birthdate'] ?? null),
            'email' => $email !== '' ? $email : null,
            'national_num' => $nationalNumber !== '' ? $nationalNumber : null,
            'identity_expiry_date' => $this->parseDate($row['identity_expiry_date'] ?? null),
            'address' => $row['address'] ?? null,
            'hired_date' => $this->parseDate($row['hired_date'] ?? null),
            'contract_end_date' => $this->parseDate($row['contract_end_date'] ?? null),
            'salary' => $salary,
            'net_salary' => $this->decimal($row['net_salary'] ?? null) ?? $salary,
            'status' => $this->normalizeEmployeeStatus($row['status'] ?? null),
            'employee_category' => HrEmployee::CATEGORY_EMPLOYEE,
            'deparment_id' => $departmentId,
            'job_id' => $jobId,
            'country_id' => $countryId,
            'maid_id' => $this->resolveMaidId($row['maid_id'] ?? $row['maid_passport'] ?? null),
        ]);

        if ($employee) {
            unset($payload['id']);
            $employee->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            $outcome = 'updated';
        } else {
            $employee = HrEmployee::create($payload);
            $outcome = 'created';
        }

        if ($shiftId) {
            $this->syncEmployeeShift($employee, $shiftId);
        }

        return $outcome;
    }

    private function importAdmin(array $row): string
    {
        $databaseId = $this->optionalInteger($row['id'] ?? null);
        $name = trim((string) ($row['name'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
        $phone = trim((string) ($row['phone'] ?? ''));
        $password = (string) ($row['password'] ?? '');

        if ($name === '' || $email === '' || $phone === '') {
            throw new \InvalidArgumentException('اسم المستخدم والبريد الإلكتروني ورقم الجوال مطلوبة.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('البريد الإلكتروني غير صالح.');
        }

        if ($username !== '' && !preg_match('/^[A-Za-z0-9._@-]+$/', $username)) {
            throw new \InvalidArgumentException('اسم الدخول يقبل الحروف الإنجليزية والأرقام والرموز . _ @ - فقط.');
        }

        $admin = Admin::query()
            ->when($databaseId, fn ($query) => $query->whereKey($databaseId))
            ->when(!$databaseId, function ($query) use ($email, $username) {
                $query->where(function ($query) use ($email, $username) {
                    $query->whereRaw('LOWER(email) = ?', [$email]);
                    if ($username !== '') {
                        $query->orWhere('username', $username);
                    }
                });
            })
            ->first();
        $creating = !$admin;

        if ($creating && strlen($password) < 6) {
            throw new \InvalidArgumentException('كلمة المرور مطلوبة للمستخدم الجديد ويجب ألا تقل عن 6 أحرف.');
        }

        $adminType = trim((string) ($row['admin_type'] ?? ''));
        if (!in_array($adminType, ['super_admin', 'customer_service'], true)) {
            throw new \InvalidArgumentException('نوع المستخدم يجب أن يكون super_admin أو customer_service.');
        }

        $role = $this->resolveImportedAdminRole($row['role'] ?? $row['role_id'] ?? null);
        $branchId = $this->resolveImportedAdminBranchId($row['branch'] ?? $row['branch_id'] ?? null);
        $sexType = $this->normalizeImportedAdminSex($row['sex_type'] ?? null);
        $status = $this->normalizeImportedAdminStatus($row['status'] ?? null);

        foreach (['email' => $email, 'username' => $username, 'identity_number' => trim((string) ($row['identity_number'] ?? ''))] as $column => $value) {
            if ($value !== '' && Admin::query()->where($column, $value)->when($admin, fn ($query) => $query->where('id', '<>', $admin->id))->exists()) {
                throw new \InvalidArgumentException("القيمة {$value} مستخدمة مسبقًا في حقل {$column}.");
            }
        }

        $payload = $this->filterColumns('admins', [
            'name' => $name,
            'username' => $username !== '' ? $username : null,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => trim((string) ($row['whatsapp'] ?? '')) ?: $phone,
            'verification_phone' => trim((string) ($row['verification_phone'] ?? '')) ?: $phone,
            'identity_number' => trim((string) ($row['identity_number'] ?? '')) ?: null,
            'identity_expiry_date' => $this->parseDate($row['identity_expiry_date'] ?? null),
            'admin_type' => $adminType,
            'sex_type' => $sexType,
            'role_id' => $role->id,
            'branch_id' => $branchId,
            'sort' => $this->optionalInteger($row['sort'] ?? null) ?? 999,
            'status' => $status,
            'note' => trim((string) ($row['note'] ?? '')) ?: null,
        ]);

        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new \InvalidArgumentException('كلمة المرور يجب ألا تقل عن 6 أحرف.');
            }
            $payload['password'] = Hash::make($password);
        }

        if ($admin) {
            $admin->update($payload);
            $outcome = 'updated';
        } else {
            $admin = Admin::create($payload);
            $outcome = 'created';
        }

        $admin->syncRoles([$role]);

        return $outcome;
    }

    private function resolveImportedAdminRole($value): Role
    {
        $value = trim((string) $value);
        $role = Role::query()
            ->where('guard_name', 'admin')
            ->where(function ($query) use ($value) {
                if (ctype_digit($value)) {
                    $query->whereKey((int) $value)->orWhere('name', $value);
                } else {
                    $query->where('name', $value);
                }
            })
            ->first();

        if (!$role) {
            throw new \InvalidArgumentException('الدور مطلوب أو غير موجود ضمن أدوار المستخدمين.');
        }

        return $role;
    }

    private function resolveImportedAdminBranchId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !Schema::hasColumn('admins', 'branch_id')) {
            return null;
        }

        $branch = Branch::query()
            ->where(function ($query) use ($value) {
                if (ctype_digit($value)) {
                    $query->whereKey((int) $value);
                } else {
                    $query->where('code', $value)
                        ->orWhere('name_ar', $value)
                        ->orWhere('name_en', $value);
                }
            })
            ->first();

        if (!$branch) {
            throw new \InvalidArgumentException('الفرع المحدد غير موجود.');
        }

        return (int) $branch->id;
    }

    private function normalizeImportedAdminSex($value): int
    {
        $value = $this->normalizeComparableText((string) $value);
        return match ($value) {
            '0', 'ذكر', 'male' => 0,
            '1', 'انثى', 'female' => 1,
            default => throw new \InvalidArgumentException('الجنس يجب أن يكون ذكر أو أنثى أو 0 أو 1.'),
        };
    }

    private function normalizeImportedAdminStatus($value): string
    {
        $value = $this->normalizeComparableText((string) ($value ?: 'active'));
        return match ($value) {
            'active', 'نشط' => 'active',
            'suspended', 'موقوف', 'غير نشط' => 'suspended',
            default => throw new \InvalidArgumentException('حالة المستخدم يجب أن تكون active أو suspended.'),
        };
    }

    private function importRentalMaid(array $row, ?int $adminId): string
    {
        return $this->importMaid($row, $adminId, true);
    }

    private function importMaid(array $row, ?int $adminId, bool $rental, bool $allowSparseRelations = false): string
    {
        $passport = $this->normalizePassportNumber($row['passport_num'] ?? $row['maid_passport'] ?? '');

        if ($passport === '') {
            throw new \InvalidArgumentException('رقم جواز العاملة مطلوب.');
        }

        if (trim((string) ($row['name_en'] ?? '')) === '') {
            throw new \InvalidArgumentException('اسم العاملة بالإنجليزية مطلوب.');
        }

        if (!$this->resolveNationalityId($row['nationality'] ?? null)) {
            throw new \InvalidArgumentException('الجنسية مطلوبة أو غير موجودة في النظام.');
        }

        if ($rental && !$this->resolveTranslatableId(RecruitmentOffices::class, $row['office'] ?? null)) {
            throw new \InvalidArgumentException('مكتب الاستقدام مطلوب لعاملة التأجير أو غير موجود في النظام.');
        }

        if (!$allowSparseRelations
            && $this->resolveTranslatableIds(JobTitle::class, $row['job_titles'] ?? $row['job_title'] ?? null) === []) {
            throw new \InvalidArgumentException('المهنة مطلوبة أو غير موجودة في النظام.');
        }

        $maid = Maid::where('passport_num', $passport)->first();
        $payload = $this->maidPayload($row, $adminId, $rental);

        if ($maid) {
            $maid->update($payload);
            $outcome = 'updated';
        } else {
            $maid = Maid::create($payload);
            $outcome = 'created';
        }

        $this->syncMaidCode($maid, $row);
        $this->syncMaidRelations($maid, $row, $rental);

        if ($rental) {
            $this->syncRentalFinance($maid, $row, $adminId);
            $this->syncRentalShelterContract($maid, $row, $adminId);
        }

        return $outcome;
    }

    private function importRecruitmentBundle(array $row, ?int $adminId): string
    {
        $clientOutcome = $this->importClient($this->clientRowFromBundle($row), $adminId);
        $contractRow = $this->recruitmentContractRowFromBundle($row);
        $status = $this->normalizeRecruitmentContractStatus($contractRow['status'] ?? null);
        $maidRow = $this->maidRowFromBundle($row);
        $maidOutcome = 'skipped';

        if ($this->recruitmentBundleHasMaidData($maidRow)) {
            // Historical recruitment sheets commonly contain the worker identity
            // but omit optional CV relations such as job title and experience.
            $maidOutcome = $this->importMaid($maidRow, $adminId, false, true);
        } elseif ($status !== ContractStatusEnum::PendingMaidSelection->value) {
            throw new \InvalidArgumentException(
                'بيانات العاملة مطلوبة ما لم تكن حالة العقد بانتظار اختيار العاملة.'
            );
        }

        $contractOutcome = $this->importRecruitmentContract($contractRow, $adminId);

        return in_array('created', [$clientOutcome, $maidOutcome, $contractOutcome], true) ? 'created' : 'updated';
    }

    private function recruitmentBundleHasMaidData(array $row): bool
    {
        return collect([
            $row['passport_num'] ?? $row['maid_passport'] ?? null,
            $row['name_en'] ?? $row['maid_name'] ?? null,
            $row['nationality'] ?? null,
        ])->contains(fn ($value) => filled($value));
    }

    private function importRentBundle(array $row, ?int $adminId): string
    {
        $clientOutcome = $this->importClient($this->clientRowFromBundle($row), $adminId);
        $maidOutcome = $this->importMaid($this->maidRowFromBundle($row), $adminId, true);
        $contractOutcome = $this->importRentContract($this->rentContractRowFromBundle($row), $adminId);

        return in_array('created', [$clientOutcome, $maidOutcome, $contractOutcome], true) ? 'created' : 'updated';
    }

    private function importRecruitmentContract(array $row, ?int $adminId): string
    {
        $user = $this->clientFromRow($row);
        $maid = $this->maidFromRow($row, false);
        $status = $this->normalizeRecruitmentContractStatus($row['status'] ?? null);

        if (!$status) {
            throw new \InvalidArgumentException(
                'حالة عقد الاستقدام مطلوبة ويجب أن تكون إحدى القيم: ' . implode('، ', $this->recruitmentStatusAcceptedLabels()) . '.'
            );
        }

        $order = $maid
            ? $this->ensureOrder($user, $maid, $row, OrderEnum::Completed->value)
            : null;

        $contractId = $this->optionalInteger($row['contract_number'] ?? null);

        // An explicit numeric legacy contract number is the authoritative primary
        // key. Do not merge two distinct contracts merely because the same client
        // recruited the same worker more than once.
        $contract = $contractId
            ? Contract::whereKey($contractId)->first()
            : $this->findContract($row['contract_number'] ?? null, $maid?->id, $user->id);
        $payload = $this->filterColumns('contracts', [
            'user_id' => $user->id,
            'maid_id' => $maid?->id,
            'order_id' => $order?->id,
            'marketer_id' => $this->resolveImportedAdminId(
                $row['created_by'] ?? $row['created_by_name'] ?? null,
                $adminId
            ),
            'status' => $status,
            'contract_number' => $row['contract_number'] ?? null,
            'msaned_number' => $row['msaned_number'] ?? null,
            'visa_number' => $row['visa_number'] ?? null,
            'contract_date' => $this->parseDate($row['contract_date'] ?? null),
            'type' => 'recruitment',
        ]);

        $created = false;
        if ($contract) {
            $contract->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $contract = Contract::create($contractId ? ['id' => $contractId] + $payload : $payload);
            $created = true;
        }

        $arrivalDate = $this->parseDate($row['arrival_date'] ?? null);

        $this->upsertContractInfo($contract, [
            'contract_date' => $this->parseDate($row['contract_date'] ?? null),
            'visa_number' => $row['visa_number'] ?? null,
            'msaned_number' => $row['msaned_number'] ?? null,
            'arrival_date' => $arrivalDate,
            'contract_value' => $this->decimal($row['amount'] ?? null),
            'manual_maid_name' => $maid ? null : ($row['maid_name'] ?? null),
            'manual_maid_passport_num' => $maid ? null : $this->nullableImportString(
                $row['maid_passport'] ?? $row['passport_num'] ?? null
            ),
            'manual_maid_nationality_id' => $maid ? null : $this->resolveNationalityId($row['nationality'] ?? null),
            'notes' => $row['notes'] ?? null,
        ]);

        $this->syncRecruitmentContractWarrantyFromArrivalDate($contract, $arrivalDate);
        $this->syncImportedBondsForRecruitmentContract($contract, $row);

        return $created ? 'created' : 'updated';
    }

    private function syncImportedBondsForRecruitmentContract(Contract $contract, array $row = []): void
    {
        if (!Schema::hasTable('fn_bonds') || !Schema::hasColumn('fn_bonds', 'recruitment_contract_id')) {
            return;
        }

        $numbers = collect([
            $contract->id,
            $contract->contract_number,
            $contract->arabia_order_number ?? null,
            $row['contract_number'] ?? null,
            $row['order_number'] ?? null,
        ])->map(fn ($value) => $this->optionalInteger($value))->filter()->unique()->values();

        if ($numbers->isEmpty()) {
            return;
        }

        Bond::query()
            ->where('legacy_source', 'alrafedin_finance_bonds')
            ->whereNull('rental_contract_id')
            ->whereIn('legacy_order_number', $numbers)
            ->update(['recruitment_contract_id' => $contract->id]);
    }

    private function importShelterContract(array $row, ?int $adminId): string
    {
        $user = filled($row['client_phone'] ?? null) ? $this->clientFromRow($row) : null;
        $maid = $this->maidFromRow($row, true);
        $contract = Contract::where('maid_id', $maid->id)->latest('id')->first();
        $created = false;

        if (!$contract) {
            $contract = Contract::create($this->filterColumns('contracts', [
                'user_id' => $user?->id,
                'maid_id' => $maid->id,
                'marketer_id' => $adminId,
                'status' => 'shelter',
                'contract_number' => $row['contract_number'] ?? null,
            ]));
            $created = true;
        }

        $contract->update($this->filterColumns('contracts', [
            'user_id' => $user?->id ?: ($contract->user_id ?? null),
            'status' => 'shelter',
            'contract_number' => $row['contract_number'] ?? $contract->contract_number ?? null,
        ]));

        $this->upsertContractInfo($contract, [
            'shelter_id' => $this->resolveShelterId($row['shelter_place'] ?? null),
            'receipt_source' => $row['source'] ?? null,
            'shelter_entry_date' => $this->parseDate($row['receipt_date'] ?? null),
            'wants_to_work' => 'unknown',
            'notes' => $row['notes'] ?? null,
        ]);

        return $created ? 'created' : 'updated';
    }

    private function importRentContract(array $row, ?int $adminId): string
    {
        $user = $this->clientFromRow($row);
        $maid = $this->maidFromRow($row, true);
        $order = $this->ensureOrder($user, $maid, $row);
        $number = trim((string) ($row['contract_number'] ?? ''));

        $contract = $number !== ''
            ? RentalContract::where('contract_number', $number)->first()
            : RentalContract::where('user_id', $user->id)->where('maid_id', $maid->id)->latest('id')->first();

        $duration = (int) (($row['duration_months'] ?? null) ?: 1);
        $monthly = $this->decimal($row['monthly_amount'] ?? null);
        $statusValue = $row['status'] ?? null;
        $status = $this->normalizeRentalStatus($statusValue);

        if (!$status) {
            throw new \InvalidArgumentException(
                'حالة عقد التأجير مطلوبة ويجب أن تكون إحدى القيم: ' . implode('، ', $this->rentalStatusAcceptedLabels()) . '.'
            );
        }

        $payload = [
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_id' => $order->id,
            'marketer_id' => $adminId,
            'contract_number' => $number ?: null,
            'contract_date' => $this->parseDate($row['start_date'] ?? null),
            'status' => $status,
        ];

        $created = false;
        if ($contract) {
            $contract->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $contract = RentalContract::create($payload);
            $created = true;
        }

        $this->syncRentalClosureType($contract, $statusValue, $status);

        $contract->info()->updateOrCreate([], [
            'monthly_cost' => $monthly,
            'contract_duration_months' => $duration,
            'contract_value' => $monthly !== null ? $monthly * $duration : null,
            'ingaz_from' => $this->parseDate($row['start_date'] ?? null),
            'ingaz_to' => $this->parseDate($row['end_date'] ?? null),
            'notes' => $row['notes'] ?? null,
        ]);

        return $created ? 'created' : 'updated';
    }

    private function importSponsorshipTransfer(array $row, ?int $adminId): string
    {
        if (!$this->parseDate($row['trial_start_date'] ?? null)) {
            throw new \InvalidArgumentException('تاريخ بداية التجربة مطلوب كما في إنشاء طلب النقل.');
        }
        $newSponsor = $this->clientFromRow($row);
        $maid = $this->maidFromRow($row, true);
        $contract = Contract::where('maid_id', $maid->id)->latest('id')->first();

        if (!$contract) {
            throw new \InvalidArgumentException('لا يوجد عقد استقدام مرتبط برقم جواز العاملة.');
        }

        $amount = $this->decimal($row['amount'] ?? null) ?: 0;
        $transfer = SponsorshipTransfer::where('contract_id', $contract->id)
            ->where('new_sponsor_id', $newSponsor->id)
            ->latest('id')
            ->first();

        $requestNumber = trim((string) ($row['request_number'] ?? ''));
        if ($requestNumber !== '') {
            $byNumber = SponsorshipTransfer::query()->where('external_source', 'data_import')
                ->where('external_operation_number', $requestNumber)->first();
            if ($byNumber && ((int) $byNumber->contract_id !== (int) $contract->id || (int) $byNumber->new_sponsor_id !== (int) $newSponsor->id)) {
                throw new \InvalidArgumentException('رقم طلب النقل مستخدم لعميل أو عقد آخر.');
            }
            $transfer = $byNumber ?: $transfer;
        }

        $payload = [
            'contract_id' => $contract->id,
            'old_sponsor_id' => $contract->user_id,
            'new_sponsor_id' => $newSponsor->id,
            'trial_days' => 7,
            'trial_start_date' => $this->parseDate($row['trial_start_date'] ?? null),
            'contract_value' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'status' => $this->normalizeTransferStatus($row['transfer_status'] ?? null),
            'approved_by' => $adminId,
            'notes' => $row['notes'] ?? null,
        ];

        if ($requestNumber !== '') {
            $payload['external_source'] = 'data_import';
            $payload['external_operation_number'] = $requestNumber;
        }
        $requestDate = $this->parseDate($row['request_date'] ?? null);
        if ($requestDate && !$transfer) $payload['created_at'] = $requestDate;

        if ($transfer) {
            $transfer->update($payload);
            return 'updated';
        }

        $newTransfer = new SponsorshipTransfer($payload);
        if ($requestDate) $newTransfer->created_at = $requestDate;
        $newTransfer->save();

        return 'created';
    }

    private function importIngazDelegation(array $row, ?int $adminId): string
    {
        $delegationDate = $this->parseDate($row['delegation_date_gregorian'] ?? $row['delegation_date'] ?? null);
        if (!$delegationDate) {
            throw new \InvalidArgumentException('تاريخ التفويض الميلادي مطلوب أو غير صحيح.');
        }

        $country = $this->resolveIngazDelegationCountry($row);
        if (!$country) {
            throw new \InvalidArgumentException('الدولة مطلوبة أو غير موجودة في النظام.');
        }

        $nationality = $this->resolveIngazDelegationNationality($row, $country);
        $client = $this->resolveIngazDelegationClient($row);
        $professionId = $this->resolveTranslatableId(JobTitle::class, $row['profession'] ?? $row['profession_id'] ?? null);
        $maid = $this->resolveIngazDelegationMaid($row, $nationality, $professionId, $adminId);
        $delegation = $this->findIngazDelegation($row);
        $visaNumber = trim((string) ($row['visa_number'] ?? ''));
        $ingazNumber = trim((string) ($row['ingaz_number'] ?? ''));
        $msanedContractNumber = trim((string) ($row['msaned_contract_number'] ?? ''));

        if ($visaNumber === '') {
            throw new \InvalidArgumentException('رقم التأشيرة مطلوب.');
        }

        if ($ingazNumber === '') {
            throw new \InvalidArgumentException('رقم إنجاز مطلوب.');
        }

        if ($msanedContractNumber === '') {
            throw new \InvalidArgumentException('رقم عقد مساند مطلوب.');
        }

        $payload = $this->filterColumns('ingaz_delegations', [
            'delegation_date_gregorian' => $delegationDate,
            'delegation_date_hijri' => $row['delegation_date_hijri'] ?? null,
            'nationality_id' => $nationality->id,
            'country_id' => $country->id,
            'profession_id' => $professionId,
            'user_id' => $client->id,
            'maid_id' => $maid?->id,
            'marketer_id' => $this->resolveAdminId($row['marketer_id'] ?? $row['marketer'] ?? null) ?: $adminId,
            'client_source_type' => filled($row['existing_user_id'] ?? null) ? 'existing_in_program' : 'separate_client',
            'visa_type' => 'electronic_ingaz_delegation',
            'worker_name' => $this->nullableImportString($row['worker_name'] ?? null),
            'worker_passport_number' => $this->nullableImportString($row['worker_passport_number'] ?? $row['maid_passport'] ?? null),
            'worker_passport_issue_date' => $this->parseDate($row['worker_passport_issue_date'] ?? null),
            'visa_number' => $visaNumber,
            'visa_date' => $this->parseDate($row['visa_date'] ?? null),
            'worker_religion' => $this->normalizeIngazDelegationReligion($row['worker_religion'] ?? null),
            'worker_experience' => $this->normalizeIngazDelegationExperience($row['worker_experience'] ?? null),
            'external_office_name' => $this->nullableImportString($row['external_office_name'] ?? null),
            'external_office_license_number' => $this->nullableImportString($row['external_office_license_number'] ?? null),
            'external_office_address' => $this->nullableImportString($row['external_office_address'] ?? null),
            'external_office_phone' => $this->nullableImportString($row['external_office_phone'] ?? null),
            'status' => $this->normalizeIngazDelegationStatus($row['status'] ?? null),
            'ingaz_number' => $ingazNumber,
            'msaned_contract_number' => $msanedContractNumber,
            'notes' => $row['notes'] ?? null,
        ]);

        if ($delegation) {
            $delegation->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            return 'updated';
        }

        IngazDelegation::create($payload);

        return 'created';
    }

    private function clientRowFromBundle(array $row): array
    {
        return array_merge($row, [
            'id' => $row['client_id'] ?? $row['id'] ?? null,
            'name' => $row['client_name'] ?? $row['name'] ?? null,
            'phone' => $row['client_phone'] ?? $row['phone'] ?? null,
            'id_number' => $row['client_id_number'] ?? $row['id_number'] ?? $row['national_num'] ?? null,
            'email' => $row['client_email'] ?? $row['email'] ?? null,
            'nationality' => $row['client_nationality'] ?? null,
            'birthdate' => $row['client_birthdate'] ?? $row['birthdate'] ?? null,
            'marital_status' => $row['client_marital_status'] ?? null,
            'phone2' => $row['client_phone2'] ?? null,
            'client_category' => $row['client_category'] ?? null,
            'account_id' => $row['client_account_id'] ?? $row['account_id'] ?? null,
            'city' => $row['client_city'] ?? null,
            'address' => $row['client_address'] ?? null,
            'rooms_num' => $row['client_rooms_num'] ?? null,
            'family_num' => $row['client_family_num'] ?? null,
            'children' => $row['client_children'] ?? null,
            'job' => $row['client_job'] ?? null,
            'status' => $row['client_status'] ?? null,
            'created_by' => $row['client_created_by'] ?? null,
            'notes' => $row['client_notes'] ?? null,
        ]);
    }

    private function maidRowFromBundle(array $row): array
    {
        return array_merge($row, [
            'maid_code' => $row['maid_code'] ?? null,
            'name_en' => $row['maid_name_en'] ?? $row['name_en'] ?? $row['name:en'] ?? $row['maid_name'] ?? null,
            'maid_name' => $row['maid_name_en'] ?? $row['maid_name'] ?? null,
            'passport_num' => $row['maid_passport'] ?? $row['passport_num'] ?? null,
            'maid_passport' => $row['maid_passport'] ?? $row['passport_num'] ?? null,
            'nationality' => $row['maid_nationality'] ?? $row['nationality'] ?? null,
            'office' => $row['maid_office'] ?? $row['office'] ?? null,
            'type_id' => $row['maid_type'] ?? $row['type_id'] ?? null,
            'job_titles' => $row['maid_job_titles'] ?? $row['job_titles'] ?? $row['job_title'] ?? null,
            'religion' => $row['maid_religion'] ?? $row['religion'] ?? null,
            'request_type' => $row['maid_request_type'] ?? $row['request_type'] ?? null,
            'monthly_salary' => $row['maid_salary'] ?? $row['monthly_salary'] ?? $row['salary'] ?? null,
            'salary' => $row['maid_salary'] ?? $row['salary'] ?? null,
            'rental_price' => $row['maid_price'] ?? $row['rental_price'] ?? $row['price'] ?? null,
            'price' => $row['maid_price'] ?? $row['price'] ?? null,
            'birth_date' => $row['maid_birth_date'] ?? $row['birth_date'] ?? null,
            'age' => $row['maid_age'] ?? $row['age'] ?? null,
            'height' => $row['maid_height'] ?? $row['height'] ?? null,
            'weight' => $row['maid_weight'] ?? $row['weight'] ?? null,
            'id_number' => $row['maid_id_number'] ?? null,
            'id_expiry_date' => $row['maid_id_expiry_date'] ?? null,
            'marital_status' => $row['maid_marital_status'] ?? $row['marital_status:ar'] ?? null,
            'childrens_count' => $row['maid_childrens_count'] ?? null,
            'duration' => $row['maid_duration'] ?? null,
            'experience_status' => $row['maid_experience_status'] ?? $row['experience_status'] ?? null,
            'experience_years' => $row['maid_experience_years'] ?? $row['experience_years'] ?? null,
            'skills' => $row['maid_skills'] ?? $row['skills'] ?? null,
            'languages' => $row['maid_languages'] ?? $row['languages'] ?? null,
            'language_percentages' => $row['maid_language_percentages'] ?? $row['language_percentages'] ?? null,
            'availability_status' => $row['maid_status'] ?? $row['availability_status'] ?? $row['status'] ?? null,
            'available_at' => $row['maid_available_at'] ?? null,
            'arrival_date' => $row['maid_arrival_date'] ?? null,
            'create_shelter_contract' => $row['maid_create_shelter_contract'] ?? null,
            'is_show' => $row['maid_is_show'] ?? null,
            'is_pinned' => $row['maid_is_pinned'] ?? null,
            'video_link' => $row['maid_video_link'] ?? null,
            'finance_income' => $row['maid_finance_income'] ?? null,
            'finance_expense' => $row['maid_finance_expense'] ?? null,
            'notes' => $row['maid_notes'] ?? $row['additional_notes'] ?? null,
            'additional_notes' => $row['maid_notes'] ?? $row['additional_notes'] ?? null,
            'added_by' => $row['maid_added_by'] ?? $row['added_by'] ?? null,
        ]);
    }

    private function recruitmentContractRowFromBundle(array $row): array
    {
        return array_merge($row, [
            'contract_number' => $row['contract_number'] ?? null,
            'msaned_number' => $row['msaned_number'] ?? null,
            'visa_number' => $row['visa_number'] ?? null,
            'client_phone' => $row['client_phone'] ?? $row['phone'] ?? null,
            'client_id_number' => $row['client_id_number'] ?? $row['id_number'] ?? null,
            'client_name' => $row['client_name'] ?? $row['name'] ?? null,
            'maid_passport' => $row['maid_passport'] ?? $row['passport_num'] ?? null,
            'nationality' => $row['maid_nationality'] ?? $row['nationality'] ?? null,
            'contract_date' => $row['contract_date'] ?? null,
            'arrival_date' => $row['maid_arrival_date'] ?? $row['arrival_date'] ?? null,
            'status' => $row['recruitment_contract_status'] ?? $row['contract_status'] ?? null,
            'amount' => $row['contract_amount'] ?? $row['amount'] ?? null,
            'paid_amount' => $row['contract_paid_amount'] ?? $row['paid_amount'] ?? null,
            'created_by' => $row['contract_created_by'] ?? $row['created_by'] ?? null,
            'notes' => $row['contract_notes'] ?? null,
        ]);
    }

    private function rentContractRowFromBundle(array $row): array
    {
        return array_merge($row, [
            'contract_number' => $row['rent_contract_number'] ?? $row['contract_number'] ?? null,
            'client_phone' => $row['client_phone'] ?? $row['phone'] ?? null,
            'client_id_number' => $row['client_id_number'] ?? $row['id_number'] ?? null,
            'client_name' => $row['client_name'] ?? $row['name'] ?? null,
            'maid_passport' => $row['maid_passport'] ?? $row['passport_num'] ?? null,
            'nationality' => $row['maid_nationality'] ?? $row['nationality'] ?? null,
            'start_date' => $row['rent_start_date'] ?? $row['start_date'] ?? null,
            'end_date' => $row['rent_end_date'] ?? $row['end_date'] ?? null,
            'duration_months' => $row['rent_duration_months'] ?? $row['duration_months'] ?? null,
            'monthly_amount' => $row['rent_monthly_amount'] ?? $row['monthly_amount'] ?? null,
            'status' => $row['rent_contract_status'] ?? $row['contract_status'] ?? null,
            'notes' => $row['rent_contract_notes'] ?? null,
        ]);
    }

    private function clientFromRow(array $row): User
    {
        $idNumber = $this->normalizeIdNumber($row['client_id_number'] ?? $row['id_number'] ?? $row['national_num'] ?? null);
        $phone = $this->normalizeImportPhone($row['client_phone'] ?? $row['phone'] ?? '');
        $clientName = trim((string) ($row['client_name'] ?? ''));

        if ($phone === '' && $idNumber === '' && $clientName !== '') {
            $phone = $this->generatedImportClientPhone($row, $clientName);
        }

        $userById = $idNumber !== '' ? User::where('national_num', $idNumber)->first() : null;
        $userByPhone = $phone !== '' ? $this->findUserByPhone($phone) : null;

        if ($userById) {
            $this->syncImportedClientContact($userById, $phone, $idNumber, $userByPhone);

            return $userById;
        }

        if ($userByPhone) {
            if ($idNumber !== '' && filled($userByPhone->national_num) && $this->normalizeIdNumber($userByPhone->national_num) !== $idNumber) {
                throw new \InvalidArgumentException('الجوال مرتبط بعميل آخر بهوية مختلفة.');
            }
            if ($idNumber !== '' && blank($userByPhone->national_num)) {
                $userByPhone->update(['national_num' => $idNumber]);
            }

            if (blank($userByPhone->national_num) && !$this->isGeneratedImportClientPhone($phone)) {
                throw new \InvalidArgumentException('رقم هوية العميل مطلوب لإكمال الربط.');
            }

            return $userByPhone;
        }

        if ($phone === '') {
            throw new \InvalidArgumentException('جوال العميل مطلوب وغير صحيح.');
        }

        if ($idNumber === '' && !$this->isGeneratedImportClientPhone($phone)) {
            throw new \InvalidArgumentException('رقم هوية العميل مطلوب لإنشاء العميل وربطه.');
        }

        return User::create($this->filterColumns('users', [
            'name' => trim((string) ($row['client_name'] ?? 'عميل مستورد')),
            'phone_code' => '+966',
            'phone' => $phone,
            'national_num' => $idNumber ?: null,
            'password' => $this->defaultImportPasswordHash(),
            'register_from' => 'data_import',
            'is_blocked' => 0,
        ]));
    }

    private function generatedImportClientPhone(array $row, string $clientName): string
    {
        $reference = trim((string) ($row['contract_number'] ?? ''))
            ?: trim((string) ($row['maid_passport'] ?? $row['passport_num'] ?? ''))
            ?: substr(sha1($clientName), 0, 12);

        $reference = preg_replace('/[^A-Za-z0-9]+/', '-', $this->normalizeDigits($reference));
        $reference = trim((string) $reference, '-');

        return 'data-import-client-' . ($reference !== '' ? $reference : substr(sha1($clientName), 0, 12));
    }

    private function isGeneratedImportClientPhone(string $phone): bool
    {
        return str_starts_with($phone, 'data-import-client-');
    }

    private function syncImportedClientContact(User $user, string $phone, string $idNumber, ?User $userByPhone): void
    {
        $payload = [];

        if ($idNumber !== '' && blank($user->national_num)) {
            $payload['national_num'] = $idNumber;
        }

        if ($phone !== '' && (string) $user->phone !== $phone) {
            if (!$userByPhone || (int) $userByPhone->id === (int) $user->id) {
                $payload['phone'] = $phone;
                $payload['phone_code'] = '+966';
            } elseif (Schema::hasColumn('users', 'phone2') && blank($user->phone2)) {
                $payload['phone2'] = $phone;
            }
        }

        if ($payload !== []) {
            $user->update($payload);
        }
    }

    private function maidFromRow(array $row, bool $required): ?Maid
    {
        $passport = $this->normalizePassportNumber($row['maid_passport'] ?? $row['passport_num'] ?? '');
        if ($passport === '') {
            if ($required) {
                throw new \InvalidArgumentException('رقم جواز العاملة مطلوب.');
            }

            return null;
        }

        $maid = Maid::where('passport_num', $passport)->first();
        if (!$maid) {
            $maid = Maid::create($this->maidPayload([
                'passport_num' => $passport,
                'name_en' => $row['maid_name'] ?? 'EDIT_ME_NAME',
                'nationality' => $row['nationality'] ?? null,
            ], null, false));
        }

        return $maid;
    }

    private function maidPayload(array $row, ?int $adminId, bool $rental): array
    {
        $payload = [
            'name' => $row['name_en'] ?? $row['maid_name'] ?? 'EDIT_ME_NAME',
            'passport_num' => $this->normalizePassportNumber($row['passport_num'] ?? $row['maid_passport'] ?? null),
            'nationality_id' => $this->resolveNationalityId($row['nationality'] ?? null),
            'religion_id' => $this->resolveTranslatableId(Religion::class, $row['religion'] ?? null)
                ?: Religion::query()->value('id'),
            'office_id' => $this->resolveTranslatableId(RecruitmentOffices::class, $row['office'] ?? null),
            'salary' => $this->decimal($row['monthly_salary'] ?? $row['salary'] ?? null),
            'price' => $this->decimal($row['rental_price'] ?? $row['price'] ?? null),
            'birth_date' => $this->parseDate($row['birth_date'] ?? null),
            'age' => $this->decimal($row['age'] ?? null),
            'height' => $row['height'] ?? null,
            'weight' => $row['weight'] ?? null,
            'id_number' => $row['id_number'] ?? null,
            'id_expiry_date' => $this->parseDate($row['id_expiry_date'] ?? null),
            'childrens_count' => $row['childrens_count'] ?? null,
            'duration' => $row['duration'] ?? null,
            'experience_status' => $this->normalizeExperienceStatus($row['experience_status'] ?? null),
            'experience_years' => $this->normalizeExperienceStatus($row['experience_status'] ?? null) === 'has_experience'
                ? (string) ($this->decimal($row['experience_years'] ?? null) ?? 0)
                : '0',
            'skills' => $this->resolveSkillIds($row['skills'] ?? null),
            'languages' => $this->jsonLanguages($row['languages'] ?? null),
            'percentage' => $this->jsonLanguagePercentages($row['language_percentages'] ?? null),
            'arrival_date' => $this->parseDate($row['arrival_date'] ?? null),
            'available_at' => $this->parseDate($row['available_at'] ?? null) ?: now()->toDateString(),
            'status' => $this->normalizeMaidStatus($row['availability_status'] ?? null),
            'available' => $this->normalizeMaidStatus($row['availability_status'] ?? null) === 'available' ? 1 : 0,
            'request_type' => $rental
                ? $this->normalizeRequestType($row['request_type'] ?? null)
                : Maid::REQUEST_TYPE_SPECIFIC,
            'added_by' => $this->resolveImportedAdminId(
                $row['added_by'] ?? $row['created_by'] ?? $row['created_by_name'] ?? null,
                $adminId
            ),
            'is_show' => $this->truthy($row['is_show'] ?? null, true) ? 1 : 0,
            'is_pinned' => $this->truthy($row['is_pinned'] ?? null, false) ? 1 : 0,
            'additional_notes' => $row['notes'] ?? $row['additional_notes'] ?? null,
            'video_link' => $row['video_link'] ?? null,
        ];

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    private function syncMaidRelations(Maid $maid, array $row, bool $rental): void
    {
        $jobTypeId = $this->resolveJobTypeId($row['type_id'] ?? null, $rental);
        if ($jobTypeId) {
            $maid->jobTypes()->syncWithoutDetaching([$jobTypeId]);
        }

        $jobTitleIds = $this->resolveTranslatableIds(JobTitle::class, $row['job_titles'] ?? $row['job_title'] ?? null);
        if ($jobTitleIds !== []) {
            $maid->jobTitles()->syncWithoutDetaching($jobTitleIds);
        }

        $maritalStatus = trim((string) ($row['marital_status'] ?? ''));
        if ($maritalStatus !== '') {
            $maid->translateOrNew('ar')->marital_status = $maritalStatus;
            $maid->save();
        }
    }

    private function syncMaidCode(Maid $maid, array $row): void
    {
        $code = trim((string) ($row['maid_code'] ?? ''));

        if ($code === '' || !Schema::hasColumn('maids', 'cv_code')) {
            return;
        }

        $maid->forceFill(['cv_code' => $code])->save();
    }

    private function syncRentalFinance(Maid $maid, array $row, ?int $adminId): void
    {
        if (!Schema::hasTable('rental_financial_adjustments')) {
            return;
        }

        $items = [
            ['type' => 'income', 'label' => 'إيرادات التأجير', 'amount' => $this->decimal($row['finance_income'] ?? null)],
            ['type' => 'expense', 'label' => 'مصروفات التأجير', 'amount' => $this->decimal($row['finance_expense'] ?? null)],
        ];

        foreach ($items as $item) {
            if (!$item['amount'] || $item['amount'] <= 0) {
                continue;
            }

            RentalFinancialAdjustment::updateOrCreate(
                [
                    'maid_id' => $maid->id,
                    'context' => 'maid',
                    'type' => $item['type'],
                    'label' => $item['label'],
                ],
                [
                    'amount' => $item['amount'],
                    'created_by' => $adminId,
                ]
            );
        }
    }

    private function syncRentalShelterContract(Maid $maid, array $row, ?int $adminId): void
    {
        if (!$this->truthy($row['create_shelter_contract'] ?? null, false)) {
            return;
        }

        $contract = Contract::where('maid_id', $maid->id)
            ->where('status', 'shelter')
            ->latest('id')
            ->first();

        if (!$contract) {
            $contract = Contract::create($this->filterColumns('contracts', [
                'maid_id' => $maid->id,
                'marketer_id' => $adminId,
                'status' => 'shelter',
                'contract_date' => now()->toDateString(),
            ]));
        }

        $this->upsertContractInfo($contract, [
            'contract_date' => now()->toDateString(),
            'msaned_number' => '',
            'shelter_id' => $this->resolveShelterId($row['shelter_place'] ?? null) ?: 1,
            'wants_to_work' => 'yes',
            'receipt_source' => 'company',
        ]);

        $maid->update($this->filterColumns('maids', [
            'status' => 'available',
            'maid_accommodation_type' => 2,
            'available' => 1,
            'available_at' => now()->toDateString(),
        ]));
    }

    private function ensureOrder(User $user, Maid $maid, array $row, ?string $status = null): Order
    {
        $order = Order::where('user_id', $user->id)
            ->where('maid_id', $maid->id)
            ->latest('id')
            ->first();

        if ($order) {
            $updates = [];

            if ($status && $order->status !== $status) {
                $updates['status'] = $status;
            }

            if ($status && !$this->shouldTrackOrderDeadline($status) && $order->deadline_at !== null) {
                $updates['deadline_at'] = null;
            }

            if ($updates !== []) {
                $order->update($updates);
            }

            return $order;
        }

        $jobTypeId = $maid->job_types()->value('job_type_id') ?: JobType::query()->value('id');

        if (!$jobTypeId) {
            throw new \InvalidArgumentException('لا يوجد نوع سيرة/مهنة لإنشاء الطلب وربطه.');
        }

        return Order::create([
            'maid_id' => $maid->id,
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'order_code' => $this->generateOrderCode(),
            'job_type_id' => $jobTypeId,
            'status' => $status ?: $this->normalizeContractStatus($row['status'] ?? null),
        ]);
    }

    private function generateOrderCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }

    private function shouldTrackOrderDeadline(?string $status): bool
    {
        return in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true);
    }

    private function findContract(?string $number, ?int $maidId, int $userId): ?Contract
    {
        $number = trim((string) $number);

        if ($number !== '' && Schema::hasColumn('contracts', 'contract_number')) {
            $contract = Contract::where('contract_number', $number)->first();
            if ($contract) {
                return $contract;
            }
        }

        if (!$maidId) {
            return null;
        }

        return Contract::where('user_id', $userId)
            ->when($maidId, fn ($query) => $query->where('maid_id', $maidId))
            ->latest('id')
            ->first();
    }

    private function upsertContractInfo(Contract $contract, array $payload): void
    {
        $contract->info()->updateOrCreate([], $this->filterColumns('contract_infos', $payload));
    }

    private function syncRecruitmentContractWarrantyFromArrivalDate(Contract $contract, ?string $arrivalDate): void
    {
        if (!$arrivalDate || !in_array($contract->status, [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
        ], true)) {
            return;
        }

        $importedStatus = $contract->status;
        $contract->load('info', 'maid');
        $contract->calculateWarrantyStatus();

        // The source status is authoritative for completed historical contracts.
        // Warranty calculation may otherwise reopen a recently completed contract
        // as "arrival" merely because its warranty end date is still in the future.
        if ($importedStatus === ContractStatusEnum::Completed->value
            && $contract->status !== ContractStatusEnum::Completed->value) {
            $contract->forceFill(['status' => ContractStatusEnum::Completed->value])->save();
        }
    }

    private function findIngazDelegation(array $row): ?IngazDelegation
    {
        $databaseId = $this->optionalInteger($row['id'] ?? null);
        if ($databaseId) {
            $delegation = IngazDelegation::query()->whereKey($databaseId)->first();
            if ($delegation) {
                return $delegation;
            }
        }

        $ingazNumber = trim((string) ($row['ingaz_number'] ?? ''));
        if ($ingazNumber !== '') {
            $delegation = IngazDelegation::query()->where('ingaz_number', $ingazNumber)->first();
            if ($delegation) {
                return $delegation;
            }
        }

        $msanedContractNumber = trim((string) ($row['msaned_contract_number'] ?? ''));
        if ($msanedContractNumber !== '') {
            return IngazDelegation::query()
                ->where('msaned_contract_number', $msanedContractNumber)
                ->first();
        }

        return null;
    }

    private function resolveIngazDelegationCountry(array $row): ?Country
    {
        $countryId = $this->resolveCountryId(
            $row['country_id']
                ?? $row['country']
                ?? $row['maid_nationality']
                ?? $row['nationality']
                ?? null
        );

        return $countryId ? Country::query()->find($countryId) : null;
    }

    private function resolveIngazDelegationNationality(array $row, Country $country): Nationality
    {
        $nationalityId = $this->resolveNationalityId($row['nationality'] ?? null)
            ?: $this->resolveNationalityId($country->name_ar)
            ?: $this->resolveNationalityId($country->name_en);

        if ($nationalityId) {
            return Nationality::query()->findOrFail($nationalityId);
        }

        $payload = [
            'logo' => '',
            'phone_code' => (string) ($country->phone_code ?? ''),
        ];

        if (Schema::hasColumn('nationalities', 'is_show')) {
            $payload['is_show'] = false;
        }

        $nationality = Nationality::query()->create($payload);
        $this->syncIngazNationalityTranslation($nationality, 'ar', $country->name_ar ?: $country->name_en);
        $this->syncIngazNationalityTranslation($nationality, 'en', $country->name_en ?: $country->name_ar);

        return $nationality->fresh(['translations']) ?? $nationality;
    }

    private function syncIngazNationalityTranslation(Nationality $nationality, string $locale, ?string $name): void
    {
        $name = trim((string) $name);
        if ($name === '') {
            return;
        }

        NationalitiesTranslation::query()->updateOrCreate(
            [
                'nationality_id' => $nationality->id,
                'locale' => $locale,
            ],
            [
                'title' => $name,
                'sub_title' => $name,
            ]
        );
    }

    private function resolveIngazDelegationClient(array $row): User
    {
        $databaseId = $this->optionalInteger($row['existing_user_id'] ?? $row['client_id'] ?? null);
        $name = trim((string) ($row['client_name'] ?? ''));
        $phone = $this->normalizeImportPhone($row['client_phone'] ?? '');
        $identityNumber = $this->normalizeIdNumber($row['client_id_number'] ?? $row['id_number'] ?? null);

        $userByDatabaseId = $databaseId ? User::query()->whereKey($databaseId)->first() : null;
        $userByPhone = $phone !== '' ? $this->findUserByPhone($phone) : null;
        $userByIdentity = $identityNumber !== '' ? User::query()->where('national_num', $identityNumber)->first() : null;

        $ids = collect([$userByDatabaseId?->id, $userByPhone?->id, $userByIdentity?->id])->filter()->unique()->values();
        if ($ids->count() > 1) {
            throw new \InvalidArgumentException('بيانات العميل تشير إلى أكثر من عميل. راجع ID العميل والجوال ورقم الهوية.');
        }

        $user = $userByDatabaseId ?: $userByPhone ?: $userByIdentity;

        if (!$user && $name === '') {
            throw new \InvalidArgumentException('اسم العميل مطلوب عند عدم استخدام ID عميل موجود.');
        }

        $payload = [
            'name' => $name !== '' ? $name : ($user?->name ?? null),
            'phone_code' => $phone !== '' ? ($user?->phone_code ?: '+966') : ($user?->phone_code ?: ''),
            'phone' => $phone !== '' ? $phone : ($user?->phone ?: $this->internalIngazDelegationClientPhone()),
            'national_num' => $identityNumber !== '' ? $identityNumber : ($user?->national_num ?? null),
            'address' => $row['client_address'] ?? $user?->address ?? null,
            'register_from' => 'data_import',
        ];

        if ($user) {
            $user->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            return $user;
        }

        if (Schema::hasColumn('users', 'password')) {
            $payload['password'] = $this->defaultImportPasswordHash();
        }

        return User::query()->create($payload);
    }

    private function resolveIngazDelegationMaid(array $row, Nationality $nationality, ?int $professionId, ?int $adminId): ?Maid
    {
        $passport = $this->normalizePassportNumber($row['worker_passport_number'] ?? '');
        $workerName = trim((string) ($row['worker_name'] ?? ''));

        if ($passport === '') {
            return null;
        }

        $maid = Maid::query()->where('passport_num', $passport)->first();
        if (!$maid && $workerName === '') {
            return null;
        }

        $experienceStatus = in_array($this->normalizeIngazDelegationExperience($row['worker_experience'] ?? null), ['previously_worked', 'appointed_by_client', 'known_service'], true)
            ? 'has_experience'
            : 'no_experience';

        $payload = $this->filterColumns('maids', [
            'name' => $workerName !== '' ? $workerName : ($maid?->name ?? null),
            'passport_num' => $passport,
            'nationality_id' => $maid?->nationality_id ?: $nationality->id,
            'religion_id' => $this->resolveIngazDelegationReligionId($row['worker_religion'] ?? null),
            'experience_status' => $experienceStatus,
            'experience_years' => $experienceStatus === 'has_experience' ? max((int) ($maid?->experience_years ?? 1), 1) : 0,
            'price' => $maid?->price ?? 0,
            'salary' => $maid?->salary ?? 0,
            'age' => $maid?->age ?? 0,
            'cv_file' => $maid?->cv_file ?? '',
            'image' => $maid?->image ?? '',
            'available' => $maid?->available ?? 0,
            'available_at' => $maid?->available_at ?? now()->toDateString(),
            'is_show' => $maid?->is_show ?? 0,
            'status' => $maid?->status ?? 'available',
            'request_type' => $this->normalizeIngazDelegationExperience($row['worker_experience'] ?? null) === 'known_service'
                ? Maid::REQUEST_TYPE_KNOWN
                : ($maid?->request_type ?? Maid::REQUEST_TYPE_SPECIFIC),
            'added_by' => $maid?->added_by ?? $adminId,
        ]);

        if ($maid) {
            $maid->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $maid = Maid::query()->create($payload);
        }

        if ($professionId) {
            $maid->jobTitles()->syncWithoutDetaching([$professionId]);
        }

        return $maid;
    }

    private function findUserByPhone(string $phone): ?User
    {
        $phone = trim($phone);

        if (preg_match('/\p{L}/u', $phone)) {
            return User::where('phone', $phone)->first();
        }

        $normalizedSaudiPhone = $this->normalizeSaudiPhone($phone);
        $digits = preg_replace('/\D+/', '', $this->normalizeDigits($phone));

        $candidates = array_values(array_unique(array_filter([
            $phone,
            $digits,
            $normalizedSaudiPhone,
            $normalizedSaudiPhone ? '0' . $normalizedSaudiPhone : null,
            $normalizedSaudiPhone ? '966' . $normalizedSaudiPhone : null,
            $normalizedSaudiPhone ? '+966' . $normalizedSaudiPhone : null,
            '0' . $phone,
            '966' . $phone,
            '+966' . $phone,
        ])));

        return User::whereIn('phone', $candidates)->first();
    }

    private function internalIngazDelegationClientPhone(): string
    {
        do {
            $phone = 'ingaz-delegation-' . Str::uuid();
        } while (User::query()->where('phone', $phone)->exists());

        return $phone;
    }

    private function nullableImportString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveNationalityId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && Nationality::query()->whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        $normalizedValue = $this->normalizeComparableText($value);
        $aliases = [
            'اوغنده' => 'اوغندا',
        ];
        $values = array_values(array_unique(array_filter([
            $value,
            $aliases[$normalizedValue] ?? null,
        ])));

        return Nationality::query()
            ->whereHas('translations', function ($query) use ($values) {
                $query->whereIn('title', $values)
                    ->orWhereIn('sub_title', $values);
            })
            ->value('id');
    }

    private function resolveCountryId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && Country::query()->where('id', (int) $value)->exists()) {
            return (int) $value;
        }

        $exactMatch = Country::query()
            ->where('name_ar', $value)
            ->orWhere('name_en', $value)
            ->value('id');

        if ($exactMatch) {
            return (int) $exactMatch;
        }

        $normalizedValue = $this->normalizeComparableText($value);

        return Country::query()
            ->get(['id', 'name_ar', 'name_en'])
            ->first(function (Country $country) use ($normalizedValue) {
                return $this->normalizeComparableText($country->name_ar) === $normalizedValue
                    || $this->normalizeComparableText($country->name_en) === $normalizedValue;
            })?->id;
    }

    private function resolveAccountId($value): ?int
    {
        $id = $this->optionalInteger($value);

        if (!$id || !class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        if (Account::query()->whereKey($id)->exists()) {
            return $id;
        }

        $accountIdByCode = Account::query()
            ->where('code', $this->normalizeDigits((string) $value))
            ->value('id');

        return $accountIdByCode ? (int) $accountIdByCode : null;
    }

    private function resolveOrCreateClientAccountId($value, string $clientName, ?int $adminId): ?int
    {
        $code = $this->normalizeFinanceAccountCode($value);

        if (!$code || !class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        $existingAccountId = Account::query()
            ->where('code', $code)
            ->value('id');

        if ($existingAccountId) {
            return (int) $existingAccountId;
        }

        $parentAccount = Account::query()
            ->where('type', AccountTypesEnum::Clients->value)
            ->lockForUpdate()
            ->first();

        if (!$parentAccount) {
            throw new \InvalidArgumentException('لا يوجد حساب رئيسي للعملاء في جدول الحسابات. يرجى إنشاء حساب من نوع Clients أولا.');
        }

        $existingAccountId = Account::query()
            ->where('code', $code)
            ->value('id');

        if ($existingAccountId) {
            return (int) $existingAccountId;
        }

        $account = Account::query()->create([
            'name' => $clientName,
            'operation_type' => $parentAccount->operation_type,
            'status' => $parentAccount->status,
            'level' => $parentAccount->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $parentAccount->id,
            'code' => $code,
            'created_by_id' => $adminId ?? auth('admin')->id() ?? 1,
        ]);

        return (int) $account->id;
    }

    private function resolveFinanceAccountId(array $row): ?int
    {
        if (!class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        $id = $this->optionalInteger($row['account_id'] ?? null);
        if ($id && Account::query()->whereKey($id)->exists()) {
            return $id;
        }

        $code = trim((string) ($row['account_code'] ?? ''));
        if ($code !== '') {
            $account = Account::query()->where('code', $this->normalizeDigits($code))->first();
            if ($account) {
                return (int) $account->id;
            }
        }

        $name = trim((string) ($row['account_name'] ?? ''));
        if ($name !== '') {
            $account = Account::query()->where('name', $name)->first();
            if ($account) {
                return (int) $account->id;
            }
        }

        return null;
    }

    /**
     * Resolve a constraint account, creating a missing legacy account only when
     * its numeric code and a clear parent in the current chart are available.
     */
    private function resolveOrCreateFinanceConstraintAccountId(array $row, ?int $adminId): ?int
    {
        $accountId = $this->resolveFinanceAccountId($row);
        if ($accountId) {
            return $accountId;
        }

        $rawCode = trim($this->normalizeDigits((string) ($row['account_code'] ?? '')));
        $code = $this->normalizeFinanceAccountCode($row['account_code'] ?? null);
        $name = trim((string) ($row['account_name'] ?? ''));

        if ($rawCode === '0' && $name === '') {
            return $this->resolveOrCreateLegacyUnassignedFinanceAccount($adminId);
        }

        if ($code && $name === '') {
            $name = $this->inferLegacyFinanceAccountName($row, $code);
        }

        if (!$code || $name === '' || !class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        $existing = Account::query()->where('code', $code)->lockForUpdate()->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $parent = $this->closestFinanceAccountParentByCode($code);
        if (!$parent) {
            throw new \InvalidArgumentException(
                "الحساب {$code} ({$name}) غير موجود، ولا يوجد حساب أصل واضح يمكن ربطه به."
            );
        }

        $accountColumns = array_flip(Schema::getColumnListing('fn_accounts'));
        $payload = [
            'name' => $name,
            'operation_type' => $parent->operation_type ?? 1,
            'status' => $parent->status instanceof \BackedEnum
                ? $parent->status->value
                : $parent->status,
            'statement_type' => $parent->statement_type,
            'level' => (int) $parent->level + 1,
            'type' => $this->resolveImportedFinanceAccountType(
                ['name' => $name],
                $parent,
                null
            ),
            'parent_id' => $parent->id,
            'code' => $code,
            'created_by_id' => $adminId ?? auth('admin')->id() ?? auth()->id() ?? 1,
        ];

        $account = Account::query()->create(array_intersect_key($payload, $accountColumns));

        return (int) $account->id;
    }

    private function inferLegacyFinanceAccountName(array $row, int $code): string
    {
        $context = trim(collect([
            $row['account_name'] ?? null,
            $row['line_statement'] ?? null,
            $row['statement'] ?? null,
        ])->filter()->implode(' '));

        if ($context !== '' && preg_match('/العميل\s*:\s*(.+?)(?:\s+-\s+[A-Za-z]|\s+\/|$)/u', $context, $matches)) {
            $clientName = trim($matches[1]);
            if ($clientName !== '') {
                return $clientName;
            }
        }

        return "حساب قديم مستورد رقم {$code}";
    }

    private function resolveOrCreateLegacyUnassignedFinanceAccount(?int $adminId): int
    {
        $existing = Account::query()
            ->where('code', self::LEGACY_UNASSIGNED_ACCOUNT_CODE)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $clientsParent = Account::query()
            ->where('type', AccountTypesEnum::Clients->value)
            ->orderBy('level')
            ->first();

        if (!$clientsParent) {
            throw new \InvalidArgumentException(
                'يوجد سطر بحساب قديم رقم 0 دون اسم، ولا يوجد حساب رئيسي للعملاء لعزله تحته.'
            );
        }

        $accountColumns = array_flip(Schema::getColumnListing('fn_accounts'));
        $status = $clientsParent->status instanceof \BackedEnum
            ? $clientsParent->status->value
            : $clientsParent->status;
        $payload = [
            'name' => 'حساب عميل قديم غير محدد (رمز المصدر 0)',
            'operation_type' => $clientsParent->operation_type ?? 1,
            'status' => $status,
            'statement_type' => $clientsParent->statement_type,
            'level' => (int) $clientsParent->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $clientsParent->id,
            'code' => self::LEGACY_UNASSIGNED_ACCOUNT_CODE,
            'created_by_id' => $adminId ?? auth('admin')->id() ?? auth()->id() ?? 1,
        ];

        $account = Account::query()->create(array_intersect_key($payload, $accountColumns));

        return (int) $account->id;
    }

    private function closestFinanceAccountParentByCode(int $code): ?Account
    {
        $parentCode = (string) $code;
        $candidates = collect();

        while (strlen($parentCode) > 1) {
            $parentCode = substr($parentCode, 0, -1);
            $parent = Account::query()->where('code', $parentCode)->first();

            if ($parent) {
                $candidates->push($parent);
            }
        }

        $containerTypes = [
            AccountTypesEnum::Clients->value,
            AccountTypesEnum::Suppliers->value,
            AccountTypesEnum::Banks->value,
            AccountTypesEnum::Boxs->value,
            AccountTypesEnum::Storages->value,
            AccountTypesEnum::Employees->value,
            AccountTypesEnum::Drivers->value,
            AccountTypesEnum::ElectronicWallets->value,
            AccountTypesEnum::FreeLancers->value,
            AccountTypesEnum::Marketers->value,
            AccountTypesEnum::Issues->value,
            AccountTypesEnum::Contracts->value,
        ];

        return $candidates->first(
            fn (Account $candidate) => in_array($this->financeAccountTypeValue($candidate), $containerTypes, true)
        ) ?? $candidates->first();
    }

    private function resolveFinanceCostCenterId(array $row, ?int $accountId = null): ?int
    {
        if (!class_exists(CostCenter::class) || !Schema::hasTable('fn_cost_centers')) {
            return null;
        }

        $id = $this->optionalInteger($row['cost_center_id'] ?? null);
        if ($id && CostCenter::query()->whereKey($id)->exists()) {
            return $id;
        }

        $code = trim((string) ($row['cost_center_code'] ?? ''));
        if ($code !== '' && Schema::hasColumn('fn_cost_centers', 'code')) {
            $normalizedCode = $this->normalizeDigits($code);
            $costCenter = Schema::hasColumn('fn_cost_centers', 'legacy_source_code')
                ? CostCenter::query()->where('legacy_source_code', $normalizedCode)->first()
                : null;
            $costCenter ??= CostCenter::query()->where('code', $normalizedCode)->first();
            if ($costCenter) {
                return (int) $costCenter->id;
            }
        }

        $title = trim((string) ($row['cost_center_name'] ?? ''));
        if ($title !== '') {
            $costCenter = CostCenter::query()->where('title', $title)->first();
            if ($costCenter) {
                return (int) $costCenter->id;
            }
        }

        return $this->inferRequestCostCenterId($row, $accountId);
    }

    /**
     * The Alrafedin constraint report has no cost-center column. The only safe
     * recovery available in that export is an explicit recruitment request
     * number in the statement. Limit inference to P&L accounts so the balance
     * sheet counterpart is not incorrectly allocated to the same center.
     */
    private function inferRequestCostCenterId(array $row, ?int $accountId = null): ?int
    {
        if (!$this->financeAccountAcceptsInferredCostCenter($accountId, $row['account_code'] ?? null)) {
            return null;
        }

        $statement = collect([
            $row['line_statement'] ?? null,
            $row['statement'] ?? null,
        ])->filter()->implode(' ');
        $rentalRequestNumber = $this->extractLegacyRentalRequestNumber($statement);
        if ($rentalRequestNumber) {
            [$costCenter] = $this->resolveOrCreateRentalRequestCostCenter($rentalRequestNumber);

            return $costCenter?->id;
        }

        $requestNumber = $this->extractLegacyRequestNumber($statement);

        if (!$requestNumber) {
            return null;
        }

        $this->legacyRequestCostCenterIds ??= CostCenter::query()->get(['id', 'title'])
            ->mapWithKeys(function (CostCenter $center) {
                $normalized = $this->normalizeArabicText($center->title);

                return preg_match('/^طلب استقدام (\d+)$/u', $normalized, $matches)
                    ? [(int) $matches[1] => (int) $center->id]
                    : [];
            })
            ->all();

        return $this->legacyRequestCostCenterIds[$requestNumber] ?? null;
    }

    private function extractLegacyRequestNumber(?string $statement): ?int
    {
        $statement = $this->normalizeDigits((string) $statement);
        if (preg_match('/طلب\s+ت[أا]جير/u', $statement)) {
            return null;
        }
        if (!preg_match('/طلب(?:\s+استقدام)?\s*(?:رقم\s*)?[:：]?\s*(\d+)/u', $statement, $matches)) {
            return null;
        }

        $number = (int) ($matches[1] ?? 0);

        return $number > 0 ? $number : null;
    }

    private function extractLegacyRentalRequestNumber(?string $statement): ?int
    {
        $statement = $this->normalizeDigits((string) $statement);
        if (!preg_match('/(?:انهاء\s+)?طلب\s+تاجير.*?رقم(?:\s+الطلب)?\s*[:：#-]?\s*(\d+)/iu', $this->normalizeArabicText($statement), $matches)) {
            return null;
        }

        $number = (int) ($matches[1] ?? 0);

        return $number > 0 ? $number : null;
    }

    private function extractLegacyInvoiceNumber(?string $statement): ?int
    {
        $statement = $this->normalizeDigits((string) $statement);
        foreach ([
            '/رقم\s+الفاتورة\s*[:：#-]?\s*(\d+)/u',
            '/فاتورة\s+رقم\s*[:：#-]?\s*(\d+)/u',
            '/الفاتورة\s*#\s*(\d+)/u',
        ] as $pattern) {
            if (preg_match($pattern, $statement, $matches)) {
                $number = (int) ($matches[1] ?? 0);

                return $number > 0 ? $number : null;
            }
        }

        return null;
    }

    private function resolveOrCreateRentalRequestCostCenter(int $requestNumber): array
    {
        if (isset($this->legacyRentalCostCenters[$requestNumber])) {
            return [$this->legacyRentalCostCenters[$requestNumber], false];
        }

        $contract = RentalContract::query()
            ->where('legacy_order_id', $requestNumber)
            ->orWhere('contract_number', (string) $requestNumber)
            ->first(['id', 'legacy_order_id', 'contract_number']);
        $root = CostCenter::query()
            ->whereNull('parent_id')
            ->where('title', 'عقود التأجير')
            ->first();

        if (!$root) {
            $root = CostCenter::query()->create([
                'title' => 'عقود التأجير',
                'parent_id' => null,
                'table_name' => 'fn_cost_centers',
                'table_related_id' => 0,
                'code' => 'RENTAL',
                'created_by_id' => auth('admin')->id() ?? 1,
            ]);
        }

        $title = 'طلب تأجير ' . $requestNumber;
        $query = CostCenter::query()->where('parent_id', $root->id);
        if ($contract) {
            $query->where('table_name', 'rental_contracts')
                ->where('table_related_id', $contract->id);
        } else {
            $query->where('title', $title);
        }

        $center = $query->first();
        $created = false;
        if (!$center) {
            $center = CostCenter::query()->create([
                'title' => $title,
                'parent_id' => $root->id,
                'table_name' => $contract ? 'rental_contracts' : 'fn_cost_centers',
                'table_related_id' => $contract?->id ?? 0,
                'code' => 'RENTAL-' . $requestNumber,
                'created_by_id' => auth('admin')->id() ?? 1,
            ]);
            $created = true;
        } elseif ($center->title !== $title) {
            $center->update(['title' => $title]);
        }

        $this->legacyRentalCostCenters[$requestNumber] = $center;

        return [$center, $created];
    }

    private function financeAccountAcceptsInferredCostCenter(?int $accountId, $accountCode = null): bool
    {
        if ($accountId && array_key_exists($accountId, $this->financeAccountCostCenterEligibility)) {
            return $this->financeAccountCostCenterEligibility[$accountId];
        }

        $account = $accountId ? Account::query()->find($accountId, ['code', 'statement_type']) : null;
        $code = trim((string) ($account?->code ?? $accountCode ?? ''));
        $eligible = $account?->statement_type === 'income_statement'
            || str_starts_with($code, '4')
            || str_starts_with($code, '5');

        if ($accountId) {
            $this->financeAccountCostCenterEligibility[$accountId] = $eligible;
        }

        return $eligible;
    }

    private function linkImportedRequestCostCentersToConstraints(): int
    {
        if (!Schema::hasTable('fn_constraint_details') || !Schema::hasTable('fn_constraints')) {
            return 0;
        }

        $centersByRequest = CostCenter::query()->get(['id', 'title'])
            ->mapWithKeys(function (CostCenter $center) {
                $normalized = $this->normalizeArabicText($center->title);
                if (!preg_match('/^طلب استقدام (\d+)$/u', $normalized, $matches)) {
                    return [];
                }

                return [(int) $matches[1] => (int) $center->id];
            });

        if ($centersByRequest->isEmpty()) {
            return 0;
        }

        $linked = 0;
        DB::table('fn_constraint_details as details')
            ->join('fn_constraints as constraints', 'constraints.id', '=', 'details.constraint_id')
            ->join('fn_accounts as accounts', 'accounts.id', '=', 'details.account_id')
            ->whereNull('details.cost_center_id')
            ->whereNull('details.deleted_at')
            ->where(function ($query) {
                $query->where('accounts.statement_type', 'income_statement')
                    ->orWhere('accounts.code', 'like', '4%')
                    ->orWhere('accounts.code', 'like', '5%');
            })
            ->select([
                'details.id',
                'details.statement as detail_statement',
                'constraints.statement as constraint_statement',
            ])
            ->orderBy('details.id')
            ->chunkById(1000, function ($details) use ($centersByRequest, &$linked) {
                $detailIdsByCenter = [];
                foreach ($details as $detail) {
                    $requestNumber = $this->extractLegacyRequestNumber(
                        trim((string) $detail->detail_statement . ' ' . (string) $detail->constraint_statement)
                    );
                    $centerId = $requestNumber ? $centersByRequest->get($requestNumber) : null;
                    if ($centerId) {
                        $detailIdsByCenter[$centerId][] = $detail->id;
                    }
                }

                foreach ($detailIdsByCenter as $centerId => $detailIds) {
                    $linked += DB::table('fn_constraint_details')
                        ->whereNull('cost_center_id')
                        ->whereIn('id', $detailIds)
                        ->update(['cost_center_id' => $centerId, 'updated_at' => now()]);
                }
            }, 'details.id', 'id');

        return $linked;
    }

    private function resolveFinanceConstraintLine(array $row): array
    {
        $hasDebtorValue = $this->hasImportCellValue($row['debtor_value'] ?? null);
        $hasCreditorValue = $this->hasImportCellValue($row['creditor_value'] ?? null);
        $debtorValue = (float) ($this->decimal($row['debtor_value'] ?? null) ?? 0);
        $creditorValue = (float) ($this->decimal($row['creditor_value'] ?? null) ?? 0);
        $amount = $this->decimal($row['amount'] ?? null);
        $type = $this->normalizeFinanceLineType($row['line_type'] ?? $row['account_type'] ?? null);

        if ($amount !== null && !$hasDebtorValue && !$hasCreditorValue) {
            if (!$type) {
                throw new \InvalidArgumentException('عند استخدام amount يجب تحديد line_type بقيمة debtor أو creditor.');
            }

            if ($type === 'debtor') {
                $debtorValue = $amount;
                $hasDebtorValue = true;
            } else {
                $creditorValue = $amount;
                $hasCreditorValue = true;
            }
        }

        if ($hasDebtorValue && $hasCreditorValue) {
            throw new \InvalidArgumentException('لا يمكن أن يكون السطر مدينا ودائنا في نفس الوقت.');
        }

        if ($hasDebtorValue) {
            if ($type && $type !== 'debtor') {
                throw new \InvalidArgumentException('نوع السطر لا يطابق قيمة المدين.');
            }

            return [
                'type' => 'debtor',
                'debtor_value' => round($debtorValue, 2),
                'creditor_value' => 0,
            ];
        }

        if ($hasCreditorValue) {
            if ($type && $type !== 'creditor') {
                throw new \InvalidArgumentException('نوع السطر لا يطابق قيمة الدائن.');
            }

            return [
                'type' => 'creditor',
                'debtor_value' => 0,
                'creditor_value' => round($creditorValue, 2),
            ];
        }

        throw new \InvalidArgumentException('يجب إدخال قيمة مدين أو دائن لكل سطر.');
    }

    private function hasImportCellValue($value): bool
    {
        return trim($this->normalizeDigits((string) $value)) !== '';
    }

    private function normalizeFinanceLineType($value): ?string
    {
        $normalized = $this->normalizeArabicText((string) $value);

        return match ($normalized) {
            'debtor', 'debit', 'dr', 'مدين' => 'debtor',
            'creditor', 'credit', 'cr', 'دائن', 'داين' => 'creditor',
            default => null,
        };
    }

    private function resolveFinanceConstraintCode(array $rows): ?int
    {
        $codes = collect($rows)
            ->map(fn ($item) => $this->optionalInteger($item['row']['code'] ?? null))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            $codes = collect($rows)
                ->map(fn ($item) => $this->optionalInteger($item['row']['constraint_ref'] ?? null))
                ->filter()
                ->unique()
                ->values();
        }

        if ($codes->count() > 1) {
            throw new \InvalidArgumentException('كل صفوف القيد الواحد يجب أن تحمل نفس رقم القيد.');
        }

        return $codes->first();
    }

    private function resolveFinanceConstraintType($value): ConstraintTypiesEnum
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ConstraintTypiesEnum::DailyEntry;
        }

        if (is_numeric($value)) {
            $type = ConstraintTypiesEnum::tryFrom((int) $value);
            if ($type) {
                return $type;
            }
        }

        $normalized = $this->normalizeArabicText($value);

        return match ($normalized) {
            'dailyentry', 'daily_entry', 'daily entry', 'daily', 'journal', 'journal entry', 'قيد يومي', 'يومي' => ConstraintTypiesEnum::DailyEntry,
            'firstbalance', 'first_balance', 'first balance', 'opening balance', 'رصيد افتتاحي' => ConstraintTypiesEnum::FirstBalance,
            default => throw new \InvalidArgumentException('نوع القيد غير معروف. استخدم رقم النوع أو اتركه فارغا للقيد اليومي.'),
        };
    }

    private function resolveFinanceYear(string $date): ?FnYear
    {
        if (!class_exists(FnYear::class) || !Schema::hasTable('fn_years')) return null;
        // Import must respect the same active financial-year boundary as manual posting.
        return FnYear::query()->where('status', 1)
            ->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->first();
    }
    private function nextFinanceConstraintCode(): int
    {
        return ((int) Constraint::query()->max('code')) + 1;
    }

    private function resolveAdminId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && Admin::query()->whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        return Admin::query()
            ->where(function ($query) use ($value) {
                $query->where('name', $value)
                    ->orWhere('email', $value);

                if (Schema::hasColumn('admins', 'username')) {
                    $query->orWhere('username', $value);
                }
            })
            ->value('id');
    }

    private function resolveImportedAdminId($value, ?int $fallbackAdminId): int
    {
        return $this->resolveAdminId($value)
            ?? $fallbackAdminId
            ?? auth('admin')->id()
            ?? auth()->id()
            ?? 1;
    }

    private function findEmployee(?int $databaseId, string $code, string $nationalNumber, string $email): ?HrEmployee
    {
        if ($databaseId) {
            $employee = HrEmployee::query()->whereKey($databaseId)->first();

            if ($employee) {
                return $employee;
            }
        }

        if ($code !== '' && Schema::hasColumn('hr_employee', 'code')) {
            $employee = HrEmployee::query()->where('code', $code)->first();

            if ($employee) {
                return $employee;
            }
        }

        if ($nationalNumber !== '' && Schema::hasColumn('hr_employee', 'national_num')) {
            $employee = HrEmployee::query()->where('national_num', $nationalNumber)->first();

            if ($employee) {
                return $employee;
            }
        }

        if ($email !== '' && Schema::hasColumn('hr_employee', 'email')) {
            return HrEmployee::query()->where('email', $email)->first();
        }

        return null;
    }

    private function assertEmployeeCreateData(
        array $row,
        string $phone,
        string $email,
        string $nationalNumber,
        ?int $departmentId,
        ?int $jobId,
        ?int $countryId,
        ?int $shiftId
    ): void {
        $missing = [];

        if ($phone === '') {
            $missing[] = 'رقم الجوال';
        }

        if (!$this->parseDate($row['birthday'] ?? $row['birthdate'] ?? null)) {
            $missing[] = 'تاريخ الميلاد';
        }

        if ($email === '') {
            $missing[] = 'البريد الإلكتروني';
        }

        if ($nationalNumber === '') {
            $missing[] = 'رقم الهوية/الإقامة';
        }

        if (!$this->parseDate($row['hired_date'] ?? null)) {
            $missing[] = 'تاريخ التعيين';
        }

        if (!$this->parseDate($row['contract_end_date'] ?? null)) {
            $missing[] = 'تاريخ نهاية العقد';
        }

        if ($this->decimal($row['salary'] ?? null) === null) {
            $missing[] = 'الراتب';
        }

        if (!$departmentId) {
            $missing[] = 'القسم';
        }

        if (!$jobId) {
            $missing[] = 'الوظيفة';
        }

        if (!$countryId) {
            $missing[] = 'الجنسية';
        }

        if (!$shiftId && Schema::hasTable('hr_employee_shift')) {
            $missing[] = 'الوردية';
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('بيانات إنشاء الموظف ناقصة أو غير موجودة في النظام: ' . implode('، ', $missing) . '.');
        }
    }

    private function resolveHrDepartmentId($value): ?int
    {
        return $this->resolveHrLookupId(HrDepartment::class, 'hr_department', 'title', $value);
    }

    private function resolveHrJobId($value): ?int
    {
        return $this->resolveHrLookupId(HrJob::class, 'hr_jobs', 'title', $value);
    }

    private function resolveHrShiftId($value): ?int
    {
        return $this->resolveHrLookupId(HrShift::class, 'hr_shifts', 'name', $value);
    }

    private function resolveHrLookupId(string $modelClass, string $table, string $column, $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !class_exists($modelClass) || !Schema::hasTable($table)) {
            return null;
        }

        if (is_numeric($value) && $modelClass::query()->whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        return $modelClass::query()
            ->where($column, $value)
            ->orWhere($column, 'LIKE', "%{$value}%")
            ->value('id');
    }

    private function resolveMaidId($value): ?int
    {
        $value = $this->normalizePassportNumber($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && Maid::query()->whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        return Maid::query()
            ->where('passport_num', $value)
            ->value('id');
    }

    private function syncEmployeeShift(HrEmployee $employee, int $shiftId): void
    {
        if (!Schema::hasTable('hr_employee_shift')) {
            return;
        }

        HrEmployeeShift::query()->updateOrCreate(
            ['employee_id' => $employee->id],
            ['shift_id' => $shiftId]
        );
    }

    private function normalizeClientMaritalStatus($value): ?string
    {
        $normalized = $this->normalizeArabicText($value);

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'single', 'اعزب', 'عزباء', 'غير متزوج' => 'single',
            'married', 'متزوج', 'متزوجه' => 'married',
            'divorced', 'مطلق', 'مطلقه' => 'divorced',
            'widowed', 'ارمل', 'ارمله' => 'widowed',
            default => null,
        };
    }

    private function optionalInteger($value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function resolveClientCategoryId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !class_exists(ClientCategory::class) || !Schema::hasTable('fn_client_categories')) {
            return null;
        }

        if (is_numeric($value) && ClientCategory::query()->where('id', (int) $value)->exists()) {
            return (int) $value;
        }

        return ClientCategory::query()
            ->where('name', $value)
            ->value('id');
    }

    private function resolveJobTypeId(?string $value, bool $rental): ?int
    {
        $id = $this->resolveTranslatableId(JobType::class, $value);
        if ($id) {
            return $id;
        }

        if (!$rental) {
            return JobType::query()->value('id');
        }

        return JobType::whereHas('translations', function ($query) {
            $query->where('title', 'LIKE', '%ايجار%')
                ->orWhere('title', 'LIKE', '%إيجار%')
                ->orWhere('title', 'LIKE', '%تأجير%')
                ->orWhere('title', 'LIKE', '%rent%');
        })->value('id') ?: 2;
    }

    private function resolveTranslatableId(string $modelClass, ?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && $modelClass::query()->where('id', (int) $value)->exists()) {
            return (int) $value;
        }

        $id = $modelClass::query()
            ->whereTranslationLike('title', $value)
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        $normalized = $this->normalizeArabicText($value);

        return $modelClass::query()
            ->with('translations')
            ->get()
            ->first(function ($record) use ($normalized) {
                $titles = collect([$record->title ?? null])
                    ->merge($record->translations?->pluck('title') ?? []);

                return $titles->contains(fn ($title) => $this->normalizeArabicText($title) === $normalized);
            })?->id;
    }

    private function findRecruitmentOfficeByTitle(string $titleAr, ?string $titleEn, int $nationalityId): ?RecruitmentOffices
    {
        $titles = array_values(array_filter(array_unique([$titleAr, trim((string) $titleEn)])));

        if ($titles === []) {
            return null;
        }

        return RecruitmentOffices::query()
            ->where('nationality_id', $nationalityId)
            ->whereHas('translations', function ($query) use ($titles) {
                $query->whereIn('title', $titles);
            })
            ->first();
    }

    private function resolveTranslatableIds(string $modelClass, ?string $value): array
    {
        return collect($this->splitList($value))
            ->map(fn (string $item) => $this->resolveTranslatableId($modelClass, $item))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveSkillIds(?string $value): array
    {
        return $this->resolveTranslatableIds(Skill::class, $value);
    }

    private function splitList(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        return collect(preg_split('/[|،,]+/u', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveShelterId(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return ShelterPlaces::query()
            ->whereTranslationLike('title', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->value('id');
    }

    private function normalizeSaudiPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->normalizeDigits((string) $phone));

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) > 9 && preg_match('/5\d{8}/', $digits, $matches)) {
            $digits = $matches[0];
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    private function normalizeImportPhone($phone): string
    {
        $phone = trim($this->normalizeDigits((string) $phone));
        if ($phone === '') {
            return '';
        }

        $normalizedSaudiPhone = $this->normalizeSaudiPhone($phone);
        if ($normalizedSaudiPhone) {
            return $normalizedSaudiPhone;
        }

        if (preg_match('/^(.+)\.0+$/', $phone, $matches)) {
            $phone = $matches[1];
        }

        if (preg_match('/\p{L}/u', $phone)) {
            return preg_replace('/\s+/u', '', $phone);
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits !== '') {
            return $digits;
        }

        return preg_replace('/\s+/u', '', $phone);
    }

    private function normalizeOptionalPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        return $this->normalizeSaudiPhone($phone) ?: $phone;
    }

    private function normalizeIdNumber($value): string
    {
        return preg_replace('/\D+/u', '', $this->normalizeDigits((string) $value));
    }

    private function normalizePassportNumber($value): string
    {
        $value = $this->normalizeDigits((string) $value);
        $value = preg_replace('/\s+/u', '', trim($value));

        if (preg_match('/^(.+)\.0+$/', $value, $matches)) {
            $value = $matches[1];
        }

        return mb_strtoupper($value);
    }

    private function normalizeMaidStatus(?string $status): string
    {
        return match (trim((string) $status)) {
            'محجوزة', 'محجوز', 'reserved' => 'reserved',
            'تم التعاقد', 'متعاقدة', 'contracted' => 'contracted',
            'باك اوت', 'backout' => 'backout',
            'موقوفة', 'موقوف', 'suspended' => 'suspended',
            'مرحلة', 'deported' => 'deported',
            default => 'available',
        };
    }

    private function normalizeExperienceStatus(?string $status): string
    {
        return match (trim((string) $status)) {
            'has_experience', 'سبق لها العمل', 'لديها خبرة', 'خبرة' => 'has_experience',
            default => 'no_experience',
        };
    }

    private function normalizeRequestType(?string $requestType): string
    {
        return match (trim((string) $requestType)) {
            'known', 'معروفة', 'معروف' => Maid::REQUEST_TYPE_KNOWN,
            'specific', 'محددة', 'محدد' => Maid::REQUEST_TYPE_SPECIFIC,
            default => Maid::REQUEST_TYPE_CUSTOM,
        };
    }

    private function jsonLanguages(?string $value): string
    {
        $aliases = [
            'العربية' => 'arabic',
            'عربي' => 'arabic',
            'الإنجليزية' => 'english',
            'الانجليزية' => 'english',
            'انجليزي' => 'english',
            'الأمهرية' => 'amharic',
            'الامهرية' => 'amharic',
            'امهرية' => 'amharic',
            'اللغة الأم' => 'mother',
            'اللغة الام' => 'mother',
        ];

        $languages = collect($this->splitList($value))
            ->map(function (string $language) use ($aliases) {
                $key = mb_strtolower($language);
                return $aliases[$language] ?? $aliases[$key] ?? $key;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return json_encode($languages, JSON_UNESCAPED_UNICODE);
    }

    private function jsonLanguagePercentages(?string $value): string
    {
        $percentages = [];

        foreach ($this->splitList($value) as $part) {
            [$language, $percentage] = array_pad(explode(':', $part, 2), 2, null);
            $language = trim((string) $language);
            $percentage = trim((string) $percentage);

            if ($language !== '' && $percentage !== '') {
                $percentages[$language] = $percentage;
            }
        }

        return json_encode($percentages, JSON_UNESCAPED_UNICODE);
    }

    private function truthy($value, bool $default = false): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }

        return in_array(mb_strtolower($value), ['1', 'yes', 'true', 'نعم', 'اي', 'إي'], true);
    }

    private function normalizeContractStatus(?string $status): string
    {
        return $this->normalizeRecruitmentContractStatus($status) ?: ContractStatusEnum::New->value;
    }

    private function normalizeRecruitmentContractStatus(?string $status): ?string
    {
        $normalizedStatus = $this->normalizeArabicText($status);

        if ($normalizedStatus === '') {
            return null;
        }

        foreach ($this->recruitmentStatusAliases() as $statusKey => $aliases) {
            if ($normalizedStatus === $this->normalizeArabicText($statusKey)) {
                return $statusKey;
            }

            foreach ($aliases as $alias) {
                if ($normalizedStatus === $this->normalizeArabicText($alias)) {
                    return $statusKey;
                }
            }
        }

        return null;
    }

    private function recruitmentStatusAliases(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value => [
                'بانتظار اختيار العاملة',
                'انتظار اختيار العاملة',
                'بانتظار اختيار العمالة',
                'انتظار اختيار العمالة',
            ],
            ContractStatusEnum::New->value => ['بانتظار الاعتماد', 'عقود جديدة', 'جديد', 'نشط', 'نشطة', 'ساري', 'سارية', 'active'],
            ContractStatusEnum::AccreditationContracts->value => ['اعتماد العقود'],
            ContractStatusEnum::Msaned->value => ['عقود مساند', 'مساند'],
            ContractStatusEnum::Tafwed->value => ['تفويض', 'التفويض'],
            ContractStatusEnum::Taffez->value => ['التفييز', 'تفييز'],
            ContractStatusEnum::Tazker->value => ['تذكرة', 'التذكرة'],
            ContractStatusEnum::Arrival->value => ['مرحلة الوصول', 'الوصول', 'وصول'],
            ContractStatusEnum::Completed->value => ['عقد مكتمل', 'مكتمل', 'مكتملة', 'completed'],
            ContractStatusEnum::Refunded->value => ['مسترد', 'مسترده', 'مستردة', 'تم استرداده', 'تم إسترداده', 'refunded'],
            ContractStatusEnum::Back->value => ['مرتجع', 'راجع'],
            ContractStatusEnum::Sari->value => ['عقد ساري', 'عقد سار', 'سار'],
            ContractStatusEnum::Shelter->value => ['عقد إيواء', 'عقد ايواء', 'إيواء', 'ايواء'],
            ContractStatusEnum::Trial->value => ['في فترة التجربة', 'فترة التجربة', 'تجربة'],
            ContractStatusEnum::InProgress->value => ['مرحلة الاجراءات', 'مرحلة الإجراءات', 'قيد التنفيذ'],
            ContractStatusEnum::ClientContracted->value => ['تم التعاقد مع العميل', 'التعاقد مع العميل'],
            ContractStatusEnum::DelegateSelected->value => ['تم اختيار المندوب', 'تم اختيار أحد المندوبين', 'اختيار المندوب'],
            ContractStatusEnum::UnderWarranty->value => ['تحت الضمان'],
            ContractStatusEnum::Closed->value => ['عقد مغلق', 'مغلق'],
            'cancelled' => ['ملغي', 'ملغى', 'ملغية'],
        ];
    }

    private function recruitmentStatusAcceptedLabels(): array
    {
        return [
            'بانتظار اختيار العاملة',
            'بانتظار الاعتماد',
            'اعتماد العقود',
            'عقود مساند',
            'تفويض',
            'التفييز',
            'تذكرة',
            'مرحلة الوصول',
            'عقد مكتمل',
            'مرتجع',
            'عقد ساري',
            'عقد إيواء',
            'في فترة التجربة',
            'مرحلة الاجراءات',
            'تم التعاقد مع العميل',
            'تم اختيار المندوب',
            'تحت الضمان',
            'عقد مغلق',
            'ملغي',
        ];
    }

    private function normalizeRentalStatus(?string $status): ?string
    {
        $normalizedStatus = $this->normalizeArabicText($status);

        if ($normalizedStatus === '') {
            return null;
        }

        foreach ($this->rentalStatusAliases() as $statusKey => $aliases) {
            if ($normalizedStatus === $this->normalizeArabicText($statusKey)) {
                return $statusKey;
            }

            foreach ($aliases as $alias) {
                if ($normalizedStatus === $this->normalizeArabicText($alias)) {
                    return $statusKey;
                }
            }
        }

        return null;
    }

    private function rentalStatusAliases(): array
    {
        return [
            'new' => ['جديد', 'عقد جديد', 'العقود الجديدة'],
            'waiting_contract_send' => ['بانتظار التوقيع', 'في انتظار التوقيع', 'انتظار التوقيع'],
            'contract_sent' => ['تم إرسال العقد', 'تم ارسال العقد', 'مرسل للتوقيع', 'مرسل'],
            'contract_signed' => ['تم توقيع العقد', 'موقع', 'موقّع'],
            'maid_delivered' => ['تم تسليم العاملة', 'تسليم العاملة', 'مسلمة'],
            'active' => ['نشط', 'نشطة', 'ساري', 'سارية', 'العقود النشطة'],
            'completed' => ['مكتمل', 'مكتملة', 'منتهي', 'منتهية', 'تم النقل', 'منقول', 'مغلق - متاحة', 'متاحة'],
            'cancelled' => ['ملغي', 'ملغى', 'ملغية', 'العقود الملغية'],
            'suspended' => ['معلق', 'معلقة'],
        ];
    }

    private function rentalStatusAcceptedLabels(): array
    {
        return [
            'جديد',
            'بانتظار التوقيع',
            'تم إرسال العقد',
            'تم توقيع العقد',
            'تم تسليم العاملة',
            'نشط',
            'مكتمل',
            'تم النقل',
            'ملغي',
            'معلق',
        ];
    }

    private function syncRentalClosureType(RentalContract $contract, ?string $statusValue, string $status): void
    {
        if (!Schema::hasColumn('rental_contracts', 'closure_type')) {
            return;
        }

        $closureType = $this->normalizeRentalClosureType($statusValue);

        if ($closureType) {
            $payload = ['closure_type' => $closureType];

            if (Schema::hasColumn('rental_contracts', 'closed_at')) {
                $payload['closed_at'] = now();
            }

            $contract->forceFill($payload)->save();
            return;
        }

        if ($status !== 'completed' && filled($contract->closure_type)) {
            $payload = ['closure_type' => null];

            if (Schema::hasColumn('rental_contracts', 'closed_at')) {
                $payload['closed_at'] = null;
            }

            $contract->forceFill($payload)->save();
        }
    }

    private function normalizeRentalClosureType(?string $status): ?string
    {
        $normalizedStatus = $this->normalizeArabicText($status);

        if (in_array($normalizedStatus, ['transferred', 'تم النقل', 'منقول', 'نقل الكفاله', 'نقل الكفالة', 'نقل الخدمات'], true)) {
            return 'transferred';
        }

        if (in_array($normalizedStatus, ['available', 'متاحه', 'مغلق متاحه', 'مكتمل متاحه'], true)) {
            return 'available';
        }

        return null;
    }

    private function normalizeTransferStatus(?string $status): string
    {
        return match (trim((string) $status)) {
            'موافق عليه', 'approved' => 'approved',
            'في التجربة', 'trial' => 'trial',
            'تم النقل', 'transferred' => 'transferred',
            'مكتمل', 'completed' => 'completed',
            'مرفوض', 'rejected' => 'rejected',
            '', 'pending', 'قيد الانتظار', 'بانتظار الموافقة', 'قيد المراجعة' => 'pending',
            default => throw new \InvalidArgumentException('حالة نقل الخدمات غير صحيحة.'),
        };
    }

    private function normalizeIngazDelegationStatus(?string $status): string
    {
        $normalized = $this->normalizeArabicText($status);

        foreach (IngazDelegation::statusOptions() as $key => $label) {
            if ($normalized === $this->normalizeArabicText($key) || $normalized === $this->normalizeArabicText($label)) {
                return $key;
            }
        }

        return in_array($normalized, [
            'executed',
            'done',
            'completed',
            $this->normalizeArabicText('تم تنفيذ التفويض'),
            $this->normalizeArabicText('منفذ'),
            $this->normalizeArabicText('مكتمل'),
        ], true)
            ? IngazDelegation::STATUS_EXECUTED
            : IngazDelegation::STATUS_UNDER_PROCESS;
    }

    private function normalizeIngazDelegationReligion(?string $religion): string
    {
        $normalized = $this->normalizeArabicText($religion);

        return match ($normalized) {
            'muslim', $this->normalizeArabicText('مسلم'), $this->normalizeArabicText('مسلمة') => 'muslim',
            'non_muslim', 'non muslim', $this->normalizeArabicText('غير مسلم'), $this->normalizeArabicText('غير مسلمة') => 'non_muslim',
            default => 'unspecified',
        };
    }

    private function resolveIngazDelegationReligionId(?string $religion): ?int
    {
        $normalizedReligion = $this->normalizeIngazDelegationReligion($religion);
        $labels = match ($normalizedReligion) {
            'muslim' => ['مسلم', 'مسلمة', 'Muslim'],
            'non_muslim' => ['غير مسلم', 'غير مسلمة', 'Non-Muslim'],
            default => ['غير محدد', 'Unspecified'],
        };

        foreach ($labels as $label) {
            $id = $this->resolveTranslatableId(Religion::class, $label);
            if ($id) {
                return $id;
            }
        }

        return Religion::query()->value('id');
    }

    private function normalizeIngazDelegationExperience(?string $experience): string
    {
        $normalized = $this->normalizeArabicText($experience);

        return match ($normalized) {
            'previously_worked', 'has_experience', $this->normalizeArabicText('سبق العمل'), $this->normalizeArabicText('سبق لها العمل'), $this->normalizeArabicText('لديها خبرة') => 'previously_worked',
            'not_previously_worked', 'no_experience', $this->normalizeArabicText('لم يسبق العمل'), $this->normalizeArabicText('لم يسبق لها العمل'), $this->normalizeArabicText('بدون خبرة') => 'not_previously_worked',
            'appointed_by_client', $this->normalizeArabicText('معينة من قبل العميل'), $this->normalizeArabicText('معين من قبل العميل') => 'appointed_by_client',
            'known_service', 'known', $this->normalizeArabicText('خدمة معروفة'), $this->normalizeArabicText('معروفة') => 'known_service',
            default => 'unspecified',
        };
    }

    private function normalizeStatus(?string $status): string
    {
        return in_array(trim((string) $status), ['محظور', 'موقوف', 'blocked'], true) ? 'blocked' : 'active';
    }

    private function normalizeComplaintStatus($status): string
    {
        $normalized = $this->normalizeArabicText($status);

        return match ($normalized) {
            'closed', 'close', 'مغلقه', 'مغلق', 'مقفله', 'مقفل' => 'closed',
            'resolved', 'done', 'محلوله', 'تم الحل', 'تم حلها' => 'resolved',
            'rejected', 'مرفوضه', 'مرفوض' => 'rejected',
            'in progress', 'in_progress', 'قيد المعالجه', 'تحت المعالجه', 'جاري المعالجه' => 'in_progress',
            default => 'pending',
        };
    }

    private function normalizeComplaintPriority($priority): string
    {
        $normalized = $this->normalizeArabicText($priority);

        return match ($normalized) {
            'low', 'منخفضه', 'منخفض' => 'low',
            'high', 'عاليه', 'عالي' => 'high',
            'urgent', 'عاجله', 'عاجل' => 'urgent',
            default => 'medium',
        };
    }

    private function buildImportedComplaintDetails(array $row, string $content, string $externalRef): string
    {
        $details = [
            "محتوى الشكوى: {$content}",
        ];

        if ($externalRef !== '') {
            array_unshift($details, "رقم التعريف: {$externalRef}");
        }

        $extraFields = [
            'source_sequence' => 'م',
            'complainant_type' => 'نوع الشاكي',
            'against_type' => 'نوع المشتكى عليه',
            'against_name' => 'اسم المشتكى عليه',
            'against_phone' => 'هاتف المشتكى عليه',
            'related_to' => 'متعلق',
            'submission_method' => 'طريقة التقديم',
            'maid_nationality' => 'الدولة',
            'source_created_by' => 'أنشئ بواسطة',
            'complaint_date' => 'تاريخ الشكوى',
            'source_created_at' => 'تاريخ الانشاء',
            'status' => 'الحالة الأصلية',
            'closed_at' => 'تاريخ الاغلاق',
            'closed_by_name' => 'أغلق بواسطة',
            'created_at' => 'أنشئ في',
        ];

        foreach ($extraFields as $key => $label) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $details[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $details);
    }

    private function complaintImportPayload(array $row): array
    {
        return collect($row)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    private function resolveComplaintUser(array $row, string $complainantPhone): ?User
    {
        $userId = $this->optionalInteger($row['user_id'] ?? null);
        if ($userId) {
            $user = User::query()->whereKey($userId)->first();
            if ($user) {
                return $user;
            }
        }

        return $complainantPhone !== '' ? $this->findUserByPhone($complainantPhone) : null;
    }

    private function resolveComplaintType(array $row, string $typeName): ComplaintType
    {
        $typeId = $this->optionalInteger($row['complaint_type_id'] ?? null);
        if ($typeId) {
            $type = ComplaintType::query()->whereKey($typeId)->first();
            if ($type) {
                return $type;
            }
        }

        $type = ComplaintType::query()->firstOrCreate(['name' => $typeName]);
        $complainantType = $this->normalizeComplaintPartyType($row['complainant_type'] ?? null);
        $againstType = $this->normalizeComplaintPartyType($row['against_type'] ?? null);

        if (!$complainantType || !$againstType) {
            $normalizedName = $this->normalizeArabicText($typeName);
            if (preg_match('/عميل.*ضد.*عامل/u', $normalizedName)) {
                $complainantType ??= 'client';
                $againstType ??= 'worker';
            } elseif (preg_match('/عامل.*ضد.*عميل/u', $normalizedName)) {
                $complainantType ??= 'worker';
                $againstType ??= 'client';
            }
        }

        $classification = array_filter([
            'complainant_type' => blank($type->complainant_type) ? $complainantType : null,
            'against_type' => blank($type->against_type) ? $againstType : null,
        ]);

        if ($classification !== []) {
            $type->update($classification);
        }

        return $type;
    }

    private function normalizeComplaintPartyType($value): ?string
    {
        $normalized = $this->normalizeArabicText($value);

        if ($normalized === '') {
            return null;
        }

        return match (true) {
            in_array($normalized, ['client', 'customer', 'عميل', 'العميل', 'عميله', 'العميله'], true) => 'client',
            in_array($normalized, ['worker', 'maid', 'عامله', 'العامل', 'العامله'], true),
                str_contains($normalized, 'عامل') => 'worker',
            in_array($normalized, ['internal_office', 'office', 'مكتب', 'المكتب', 'مكتب داخلي'], true) => 'internal_office',
            in_array($normalized, ['external_office', 'مكتب خارجي', 'المكتب الخارجي'], true) => 'external_office',
            in_array($normalized, ['employee', 'موظف', 'الموظف'], true) => 'employee',
            default => 'other',
        };
    }

    private function findExistingImportedComplaint(array $row, string $subject, string $externalRef, ?int $userId): ?Complaint
    {
        $complaintId = $this->optionalInteger($row['id'] ?? $row['complaint_id'] ?? null);
        if ($complaintId) {
            $complaint = Complaint::query()->whereKey($complaintId)->first();
            if ($complaint) {
                return $complaint;
            }
        }

        if ($externalRef !== '') {
            $complaint = Schema::hasColumn('complaints', 'external_ref')
                ? Complaint::query()->where('external_ref', $externalRef)->first()
                : null;

            if ($complaint) {
                return $complaint;
            }

            $complaint = Complaint::query()
                ->where('subject', 'like', "[{$externalRef}]%")
                ->first();

            return $complaint ?: null;
        }

        $query = Complaint::query()->where('subject', $subject);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }

        return $query->first();
    }

    private function normalizeEmployeeStatus($status): int
    {
        $normalized = $this->normalizeArabicText($status);

        return in_array($normalized, ['0', 'inactive', 'disabled', 'غير نشط', 'غيرنشط', 'موقوف'], true) ? 0 : 1;
    }

    private function normalizeOfficeStatus($status): string
    {
        $normalized = $this->normalizeArabicText($status);

        return in_array($normalized, ['غير نشط', 'غيرنشط', 'inactive', 'disabled', 'موقوف'], true)
            ? 'inactive'
            : 'active';
    }

    private function parseDate($value): ?string
    {
        return ImportValueParser::date($value)?->toDateString();
    }

    private function parseDateTime($value): ?Carbon
    {
        return ImportValueParser::date($value, true);
    }
    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function decimal($value): ?float
    {
        return ImportValueParser::decimal($value);
    }

    private function cellValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            $value = floor((float) $value) == (float) $value
                ? number_format((float) $value, 0, '.', '')
                : rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        return trim($this->normalizeDigits((string) $value));
    }

    private function cellCoordinate(int $column, int $row): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
    }

    private function normalizeArabicText(?string $value): string
    {
        $value = $this->normalizeDigits((string) $value);
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة', 'ـ', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'ي', 'ه', '', 'و', 'ي'], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeComparableText(?string $value): string
    {
        return $this->normalizeArabicText($value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->normalizeDigits($value);
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة', 'ـ', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'ي', 'ه', '', 'و', 'ي'], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);

        return preg_replace('/\s+/u', '', $value);
    }

    private function importHeaderAliases(): array
    {
        return [
            'قيمة الخدمة المباشرة' => 'direct_recruitment_cost',
            'ID التفويض' => 'id',
            'ID العميل الموجود' => 'existing_user_id',
            'رقم السند' => 'bond_code',
            'رقم حساب مدين' => 'debit_account_code',
            'اسم مدين' => 'debit_account_name',
            'رقم حساب دائن' => 'credit_account_code',
            'اسم دائن' => 'credit_account_name',
            'التاريخ' => 'date',
            'التاريخ هـ' => 'hijri_date',
            'طريقة الدفع' => 'payment_type',
            'الإجمالي' => 'total',
            'المبلغ المقبوض' => 'received_amount',
            'المبلغ المصروف' => 'paid_amount',
            'عملة' => 'currency',
            'الحركة المالية' => 'financial_movement',
            'عملة الحركة المالية' => 'financial_movement_currency',
            'ذلك مقابل' => 'statement',
            'رقم العميل' => 'client_number',
            'رقم الطلب' => 'order_number',
            'أنشئ بواسطة' => 'created_by_name',
            'قيد محاسبي رقم' => 'constraint_ref',
            'رقم القيد' => 'constraint_ref',
            'رقم مجموعة القيد' => 'constraint_ref',
            'العنوان' => 'statement',
            'بيان القيد' => 'statement',
            'مدين' => 'debtor_value',
            'المدين' => 'debtor_value',
            'قيمة المدين' => 'debtor_value',
            'دائن' => 'creditor_value',
            'الدائن' => 'creditor_value',
            'قيمة الدائن' => 'creditor_value',
            'اسم الحساب' => 'account_name',
            'الحساب' => 'account_name',
            'رقم الحساب' => 'account_code',
            'كود الحساب' => 'account_code',
            'المستوى' => 'level',
            'مستوى الحساب' => 'level',
            'الحساب الأصل' => 'parent_name',
            'الحساب الاصلي' => 'parent_name',
            'الحساب الأب' => 'parent_name',
            'طبيعة الحساب' => 'account_status',
            'نوع الحساب' => 'statement_type',
            'الرصيد' => 'balance',
            'رصيد افتتاحي' => 'opening_balance',
            'مركز التكلفة' => 'cost_center_name',
            'ملاحظة' => 'line_statement',
            'ملاحظات' => 'line_statement',
            'بيان السطر' => 'line_statement',
            'تاريخ' => 'date',
            'تاريخ القيد' => 'date',
            'نوع المستند' => 'document_type',
            'رقم المستند' => 'document_number',
            'م' => 'source_sequence',
            'رقم تعريف' => 'external_ref',
            'نوع الشكوى' => 'complaint_type',
            'نوع الشاكي' => 'complainant_type',
            'اسم الشاكي' => 'complainant_name',
            'هاتف الشاكي' => 'complainant_phone',
            'اسم المشتكى عليه' => 'against_name',
            'نوع المشتكى عليه' => 'against_type',
            'هاتف المشتكى عليه' => 'against_phone',
            'محتوى الشكوى' => 'content',
            'آخر رد' => 'last_response',
            'اخر رد' => 'last_response',
            'تاريخ الشكوى' => 'complaint_date',
            'أنشئ بواسطة' => 'source_created_by',
            'انشئ بواسطة' => 'source_created_by',
            'حالة' => 'status',
            'متعلق' => 'related_to',
            'طريقة التقديم' => 'submission_method',
            'الدولة' => 'maid_nationality',
            'الدوله' => 'maid_nationality',
            'تاريخ الانشاء' => 'source_created_at',
            'أنشئ في' => 'created_at',
            'انشئ في' => 'created_at',
            'تاريخ الاغلاق' => 'closed_at',
            'تاريخ الإغلاق' => 'closed_at',
            'أغلق بواسطة' => 'closed_by_name',
            'اغلق بواسطة' => 'closed_by_name',
            'ID العميل المسجل' => 'user_id',
            'رقم جواز العاملة' => 'maid_passport',
            'جنسية العاملة' => 'maid_nationality',
            'موضوع الشكوى' => 'subject',
            'الأولوية' => 'priority',
            'الاولوية' => 'priority',
            'الموظف المختص' => 'assigned_to',
            'الحالة' => 'status',
            'تفاصيل الشكوى' => 'details',
            'الرد' => 'response',
            'رقم تعريف خارجي' => 'external_ref',
            'تاريخ الإنشاء' => 'source_created_at',
            'رقم تسلسلي' => 'source_sequence',
        ];
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

    private function defaultImportPasswordHash(): string
    {
        if ($this->importPasswordHash === null) {
            $this->importPasswordHash = Hash::make(Str::random(32));
        }

        return $this->importPasswordHash;
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }

    private function prepareLargeImportRuntime(): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '0');
    }
}
