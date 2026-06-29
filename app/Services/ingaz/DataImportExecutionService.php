<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\RecruitmentOffices;
use App\Models\RentalFinancialAdjustment;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\Models\auth\Admin;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
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
use Nami\Finance\Models\FnYear;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Hr\Models\Department as HrDepartment;
use Nami\Hr\Models\Employee as HrEmployee;
use Nami\Hr\Models\EmployeeShift as HrEmployeeShift;
use Nami\Hr\Models\Job as HrJob;
use Nami\Hr\Models\Shift as HrShift;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataImportExecutionService
{
    private const MAX_ERROR_DETAILS = 200;

    private ?string $importPasswordHash = null;

    public function __construct(private DataImportTemplateService $templates)
    {
    }

    public function import(string $type, UploadedFile $file, ?int $adminId): array
    {
        $this->prepareLargeImportRuntime();
        $this->importPasswordHash = null;

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

        if ($type === 'finance_accounts') {
            return $this->importFinanceAccountsFile($storedPath, $columns, $adminId, $result);
        }

        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;

            try {
                DB::transaction(function () use ($type, $row, $adminId, &$result) {
                    $outcome = match ($type) {
                        'clients' => $this->importClient($row, $adminId),
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
                        default => 'skipped',
                    };

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

    private function readRows(string $path, array $columns): \Generator
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);
        $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $labelMap = [];
        foreach ($columns as $column) {
            $labelMap[$this->normalizeHeader($column['label'])] = $column['key'];
            $labelMap[$this->normalizeHeader($column['key'])] = $column['key'];
        }
        foreach ($this->importHeaderAliases() as $header => $key) {
            $labelMap[$this->normalizeHeader($header)] = $key;
        }

        $headers = [];
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $header = $this->cellValue($sheet->getCell($this->cellCoordinate($column, 1))->getValue());
            $headers[$column] = $labelMap[$this->normalizeHeader($header)] ?? $header;
        }

        $rows = [];
        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $key = $headers[$column] ?? null;
                if (!$key) {
                    continue;
                }

                $row[$key] = $this->cellValue($sheet->getCell($this->cellCoordinate($column, $rowNumber))->getValue());
            }

            $values = collect($row)->filter(fn ($value) => $value !== null && $value !== '');

            if ($values->isNotEmpty() && !$values->every(fn ($value) => in_array($value, ['مطلوب', 'اختياري'], true))) {
                yield $rowNumber => $row;
            }
        }
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
        $groups = [];

        foreach ($this->readRows(Storage::path($storedPath), $columns) as $rowNumber => $row) {
            $result['total']++;
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

        return $result;
    }

    private function importFinanceConstraintGroup(string $reference, array $rows, ?int $adminId): string
    {
        if (count($rows) < 2) {
            throw new \InvalidArgumentException('القيد يجب أن يحتوي على سطرين على الأقل.');
        }

        $firstRow = $rows[0]['row'];
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
        $code = $this->resolveFinanceConstraintCode($rows);
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

            $accountId = $this->resolveFinanceAccountId($row);
            if (!$accountId) {
                throw new \InvalidArgumentException('تعذر تحديد الحساب. استخدم account_id أو account_code أو account_name صحيح.');
            }

            $line = $this->resolveFinanceConstraintLine($row);
            $costCenterId = $this->resolveFinanceCostCenterId($row);
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

        if ($debtorTotal <= 0 || round($debtorTotal, 2) !== round($creditorTotal, 2)) {
            throw new \InvalidArgumentException('إجمالي المدين يجب أن يساوي إجمالي الدائن.');
        }

        if (!collect($details)->contains('type', 'debtor') || !collect($details)->contains('type', 'creditor')) {
            throw new \InvalidArgumentException('القيد يجب أن يحتوي على طرف مدين وطرف دائن.');
        }

        $constraint = $code ? Constraint::query()->where('code', $code)->first() : null;
        $payload = [
            'type' => $type,
            'value' => round($debtorTotal, 2),
            'date' => $date,
            'statement' => $statement,
            'year_id' => $year->id,
            'code' => $code ?: $this->nextFinanceConstraintCode(),
            'created_by_id' => $adminId,
        ];

        if ($constraint) {
            $constraint->update($payload);
            ConstraintDetails::query()->where('constraint_id', $constraint->id)->delete();
            $outcome = 'updated';
        } else {
            $constraint = Constraint::query()->create($payload);
            $outcome = 'created';
        }

        foreach ($details as $detail) {
            ConstraintDetails::query()->create($detail + [
                'constraint_id' => $constraint->id,
            ]);
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

    private function importClient(array $row, ?int $adminId): string
    {
        $phone = $this->normalizeImportPhone($row['phone'] ?? '');
        $name = trim((string) ($row['name'] ?? ''));
        $idNumber = $this->normalizeIdNumber($row['id_number'] ?? $row['national_num'] ?? null);
        $databaseId = $this->optionalInteger($row['id'] ?? null);

        if ($phone === '' || $name === '' || $idNumber === '') {
            throw new \InvalidArgumentException('اسم العميل ورقم الجوال ورقم الهوية مطلوبة.');
        }

        $userByDatabaseId = $databaseId ? User::whereKey($databaseId)->first() : null;
        $userById = User::where('national_num', $idNumber)->first();
        $userByPhone = $this->findUserByPhone($phone);

        if ($userByDatabaseId && $userById && (int) $userByDatabaseId->id !== (int) $userById->id) {
            throw new \InvalidArgumentException('ID العميل ورقم الهوية يعودان لعميلين مختلفين.');
        }

        if ($userByDatabaseId && $userByPhone && (int) $userByDatabaseId->id !== (int) $userByPhone->id) {
            throw new \InvalidArgumentException('ID العميل ورقم الجوال يعودان لعميلين مختلفين.');
        }

        if ($userByPhone && $userById && (int) $userByPhone->id !== (int) $userById->id) {
            throw new \InvalidArgumentException('رقم الهوية مستخدم لعميل ورقم الجوال مستخدم لعميل آخر.');
        }

        if ($userByPhone && filled($userByPhone->national_num) && (string) $userByPhone->national_num !== $idNumber) {
            throw new \InvalidArgumentException('رقم الجوال موجود مسبقاً برقم هوية مختلف.');
        }

        $user = $userByDatabaseId ?: $userById ?: $userByPhone;
        $createdBy = $this->resolveAdminId($row['created_by'] ?? null) ?: $adminId;
        $accountId = $this->resolveAccountId($row['account_id'] ?? null);
        $payload = $this->filterColumns('users', [
            'id' => $databaseId,
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phone,
            'email' => $row['email'] ?? null,
            'national_num' => $idNumber,
            'nationality_id' => $this->resolveCountryId($row['nationality'] ?? null),
            'birthdate' => $this->parseDate($row['birthdate'] ?? null),
            'marital_status' => $this->normalizeClientMaritalStatus($row['marital_status'] ?? null),
            'phone2' => $this->normalizeOptionalPhone($row['phone2'] ?? null),
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

        if ($user) {
            unset($payload['id']);
            $user->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            return 'updated';
        }

        if (Schema::hasColumn('users', 'password')) {
            $payload['password'] = $this->defaultImportPasswordHash();
        }

        User::create($payload);

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

    private function importRentalMaid(array $row, ?int $adminId): string
    {
        return $this->importMaid($row, $adminId, true);
    }

    private function importMaid(array $row, ?int $adminId, bool $rental): string
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

        if ($this->resolveTranslatableIds(JobTitle::class, $row['job_titles'] ?? $row['job_title'] ?? null) === []) {
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
        $maidOutcome = $this->importMaid($this->maidRowFromBundle($row), $adminId, false);
        $contractOutcome = $this->importRecruitmentContract($this->recruitmentContractRowFromBundle($row), $adminId);

        return in_array('created', [$clientOutcome, $maidOutcome, $contractOutcome], true) ? 'created' : 'updated';
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
        $maid = $this->maidFromRow($row, true);
        $status = $this->normalizeRecruitmentContractStatus($row['status'] ?? null);

        if (!$status) {
            throw new \InvalidArgumentException(
                'حالة عقد الاستقدام مطلوبة ويجب أن تكون إحدى القيم: ' . implode('، ', $this->recruitmentStatusAcceptedLabels()) . '.'
            );
        }

        $order = $this->ensureOrder($user, $maid, $row, OrderEnum::Completed->value);

        $contract = $this->findContract($row['contract_number'] ?? null, $maid?->id, $user->id);
        $payload = $this->filterColumns('contracts', [
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_id' => $order?->id,
            'marketer_id' => $adminId,
            'status' => $status,
            'contract_number' => $row['contract_number'] ?? null,
            'visa_number' => $row['visa_number'] ?? null,
            'contract_date' => $this->parseDate($row['contract_date'] ?? null),
            'type' => 'recruitment',
        ]);

        $created = false;
        if ($contract) {
            $contract->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        } else {
            $contract = Contract::create($payload);
            $created = true;
        }

        $arrivalDate = $this->parseDate($row['arrival_date'] ?? null);

        $this->upsertContractInfo($contract, [
            'contract_date' => $this->parseDate($row['contract_date'] ?? null),
            'visa_number' => $row['visa_number'] ?? null,
            'arrival_date' => $arrivalDate,
            'contract_value' => $this->decimal($row['amount'] ?? null),
            'notes' => $row['notes'] ?? null,
        ]);

        $this->syncRecruitmentContractWarrantyFromArrivalDate($contract, $arrivalDate);

        return $created ? 'created' : 'updated';
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

        if ($transfer) {
            $transfer->update($payload);
            return 'updated';
        }

        SponsorshipTransfer::create($payload);

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
            'worker_passport_number' => $this->nullableImportString($row['worker_passport_number'] ?? null),
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
        ]);
    }

    private function recruitmentContractRowFromBundle(array $row): array
    {
        return array_merge($row, [
            'contract_number' => $row['contract_number'] ?? null,
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
        $userById = $idNumber !== '' ? User::where('national_num', $idNumber)->first() : null;
        $userByPhone = $phone !== '' ? $this->findUserByPhone($phone) : null;

        if ($userById && $userByPhone && (int) $userById->id !== (int) $userByPhone->id) {
            throw new \InvalidArgumentException('رقم هوية العميل ورقم جواله يعودان لعميلين مختلفين.');
        }

        if ($userById) {
            return $userById;
        }

        if ($userByPhone) {
            if ($idNumber !== '' && blank($userByPhone->national_num)) {
                $userByPhone->update(['national_num' => $idNumber]);
            }

            if (blank($userByPhone->national_num)) {
                throw new \InvalidArgumentException('رقم هوية العميل مطلوب لإكمال الربط.');
            }

            return $userByPhone;
        }

        if ($phone === '') {
            throw new \InvalidArgumentException('جوال العميل مطلوب وغير صحيح.');
        }

        if ($idNumber === '') {
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
        if (!$maid && $required) {
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
            'added_by' => $adminId,
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
        if (!$arrivalDate) {
            return;
        }

        $contract->forceFill([
            'status' => ContractStatusEnum::Arrival->value,
        ])->save();

        $contract->load('info', 'maid');
        $contract->calculateWarrantyStatus();
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
        $countryId = $this->resolveCountryId($row['country_id'] ?? $row['country'] ?? null);

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

        return Nationality::query()
            ->whereHas('translations', function ($query) use ($value) {
                $query->where('title', $value)
                    ->orWhere('sub_title', $value);
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

        return Country::query()
            ->where('name_ar', $value)
            ->orWhere('name_en', $value)
            ->value('id');
    }

    private function resolveAccountId($value): ?int
    {
        $id = $this->optionalInteger($value);

        if (!$id || !class_exists(Account::class) || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        return Account::query()->whereKey($id)->exists() ? $id : null;
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

    private function resolveFinanceCostCenterId(array $row): ?int
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
            $costCenter = CostCenter::query()->where('code', $this->normalizeDigits($code))->first();
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

        return null;
    }

    private function resolveFinanceConstraintLine(array $row): array
    {
        $debtorValue = (float) ($this->decimal($row['debtor_value'] ?? null) ?? 0);
        $creditorValue = (float) ($this->decimal($row['creditor_value'] ?? null) ?? 0);
        $amount = $this->decimal($row['amount'] ?? null);
        $type = $this->normalizeFinanceLineType($row['line_type'] ?? $row['account_type'] ?? null);

        if ($amount !== null && $amount > 0 && $debtorValue <= 0 && $creditorValue <= 0) {
            if (!$type) {
                throw new \InvalidArgumentException('عند استخدام amount يجب تحديد line_type بقيمة debtor أو creditor.');
            }

            if ($type === 'debtor') {
                $debtorValue = $amount;
            } else {
                $creditorValue = $amount;
            }
        }

        if ($debtorValue > 0 && $creditorValue > 0) {
            throw new \InvalidArgumentException('لا يمكن أن يكون السطر مدينا ودائنا في نفس الوقت.');
        }

        if ($debtorValue > 0) {
            if ($type && $type !== 'debtor') {
                throw new \InvalidArgumentException('نوع السطر لا يطابق قيمة المدين.');
            }

            return [
                'type' => 'debtor',
                'debtor_value' => round($debtorValue, 2),
                'creditor_value' => 0,
            ];
        }

        if ($creditorValue > 0) {
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
        if (!class_exists(FnYear::class) || !Schema::hasTable('fn_years')) {
            return null;
        }

        return FnYear::query()
            ->where('status', 1)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->first();
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
            ->where('name', $value)
            ->value('id');
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
            ContractStatusEnum::PendingMaidSelection->value => ['بانتظار اختيار العاملة', 'انتظار اختيار العاملة'],
            ContractStatusEnum::New->value => ['بانتظار الاعتماد', 'عقود جديدة', 'جديد', 'نشط', 'نشطة', 'ساري', 'سارية', 'active'],
            ContractStatusEnum::AccreditationContracts->value => ['اعتماد العقود'],
            ContractStatusEnum::Msaned->value => ['عقود مساند', 'مساند'],
            ContractStatusEnum::Tafwed->value => ['تفويض', 'التفويض'],
            ContractStatusEnum::Taffez->value => ['التفييز', 'تفييز'],
            ContractStatusEnum::Tazker->value => ['تذكرة', 'التذكرة'],
            ContractStatusEnum::Arrival->value => ['مرحلة الوصول', 'الوصول', 'وصول'],
            ContractStatusEnum::Completed->value => ['عقد مكتمل', 'مكتمل', 'مكتملة', 'completed'],
            ContractStatusEnum::Back->value => ['مرتجع', 'راجع'],
            ContractStatusEnum::Sari->value => ['عقد ساري'],
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

        if (in_array($normalizedStatus, ['transferred', 'تم النقل', 'منقول', 'نقل الكفاله'], true)) {
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
            default => 'pending',
        };
    }

    private function normalizeIngazDelegationStatus(?string $status): string
    {
        $normalized = $this->normalizeArabicText($status);

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
        if ($value === null || $value === '') {
            return null;
        }

        $value = $this->normalizeDigits((string) $value);

        if (preg_match('/^(19|20)\d{6}$/', trim($value))) {
            return Carbon::createFromFormat('Ymd', trim($value))->toDateString();
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'm-d-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->toDateString();
            } catch (\Throwable) {
                // Try the next common import format.
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function decimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '', $this->normalizeDigits((string) $value));
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
