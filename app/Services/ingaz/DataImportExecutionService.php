<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\RecruitmentOffices;
use App\Models\RentalFinancialAdjustment;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use App\Models\ingaz\User;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nami\Finance\Models\ClientCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataImportExecutionService
{
    public function __construct(private DataImportTemplateService $templates)
    {
    }

    public function import(string $type, UploadedFile $file, ?int $adminId): array
    {
        $storedPath = $this->templates->storeUploadedFile($type, $file, $adminId);
        $rows = $this->readRows(Storage::path($storedPath), $this->templates->template($type)['columns'] ?? []);

        $result = [
            'path' => $storedPath,
            'total' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                DB::transaction(function () use ($type, $row, $adminId, &$result) {
                    $outcome = match ($type) {
                        'clients' => $this->importClient($row),
                        'rental_maids' => $this->importRentalMaid($row, $adminId),
                        'recruitment_contracts' => $this->importRecruitmentContract($row, $adminId),
                        'shelter_contracts' => $this->importShelterContract($row, $adminId),
                        'rent_contracts' => $this->importRentContract($row, $adminId),
                        'sponsorship_transfers' => $this->importSponsorshipTransfer($row, $adminId),
                        default => 'skipped',
                    };

                    $result[$outcome]++;
                });
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['errors'][] = "صف {$rowNumber}: " . $exception->getMessage();
            }
        }

        return $result;
    }

    private function readRows(string $path, array $columns): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $labelMap = [];
        foreach ($columns as $column) {
            $labelMap[$this->normalizeHeader($column['label'])] = $column['key'];
            $labelMap[$this->normalizeHeader($column['key'])] = $column['key'];
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
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function importClient(array $row): string
    {
        $phone = $this->normalizeSaudiPhone($row['phone'] ?? '');
        $name = trim((string) ($row['name'] ?? ''));
        $idNumber = $this->normalizeIdNumber($row['id_number'] ?? $row['national_num'] ?? null);

        if (!$phone || $name === '' || $idNumber === '') {
            throw new \InvalidArgumentException('اسم العميل ورقم الجوال ورقم الهوية مطلوبة.');
        }

        $userById = User::where('national_num', $idNumber)->first();
        $userByPhone = $this->findUserByPhone($phone);

        if ($userByPhone && $userById && (int) $userByPhone->id !== (int) $userById->id) {
            throw new \InvalidArgumentException('رقم الهوية مستخدم لعميل ورقم الجوال مستخدم لعميل آخر.');
        }

        if ($userByPhone && filled($userByPhone->national_num) && (string) $userByPhone->national_num !== $idNumber) {
            throw new \InvalidArgumentException('رقم الجوال موجود مسبقاً برقم هوية مختلف.');
        }

        $user = $userById ?: $userByPhone;
        $payload = $this->filterColumns('users', [
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phone,
            'email' => $row['email'] ?? null,
            'national_num' => $idNumber,
            'nationality_id' => $this->resolveCountryId($row['nationality'] ?? null),
            'birthdate' => $this->parseDate($row['birthdate'] ?? null),
            'phone2' => $this->normalizeOptionalPhone($row['phone2'] ?? null),
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'client_category_id' => $this->resolveClientCategoryId($row['client_category'] ?? null),
            'rooms_num' => $row['rooms_num'] ?? null,
            'family_num' => $row['family_num'] ?? null,
            'children' => $row['children'] ?? null,
            'job' => $row['job'] ?? null,
            'password' => Hash::make(Str::random(16)),
            'register_from' => 'data_import',
            'is_blocked' => $this->normalizeStatus($row['status'] ?? null) === 'blocked' ? 1 : 0,
        ]);

        if ($user) {
            unset($payload['password']);
            $user->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            return 'updated';
        }

        User::create($payload);

        return 'created';
    }

    private function importRentalMaid(array $row, ?int $adminId): string
    {
        $passport = trim((string) ($row['passport_num'] ?? ''));

        if ($passport === '') {
            throw new \InvalidArgumentException('رقم جواز العاملة مطلوب.');
        }

        if (trim((string) ($row['name_en'] ?? '')) === '') {
            throw new \InvalidArgumentException('اسم العاملة بالإنجليزية مطلوب.');
        }

        if (!$this->resolveNationalityId($row['nationality'] ?? null)) {
            throw new \InvalidArgumentException('الجنسية مطلوبة أو غير موجودة في النظام.');
        }

        if (!$this->resolveTranslatableId(RecruitmentOffices::class, $row['office'] ?? null)) {
            throw new \InvalidArgumentException('مكتب الاستقدام مطلوب لعاملة التأجير أو غير موجود في النظام.');
        }

        if ($this->resolveTranslatableIds(JobTitle::class, $row['job_titles'] ?? $row['job_title'] ?? null) === []) {
            throw new \InvalidArgumentException('المهنة مطلوبة أو غير موجودة في النظام.');
        }

        $maid = Maid::where('passport_num', $passport)->first();
        $payload = $this->maidPayload($row, $adminId, true);

        if ($maid) {
            $maid->update($payload);
            $outcome = 'updated';
        } else {
            $maid = Maid::create($payload);
            $outcome = 'created';
        }

        $this->syncMaidCode($maid, $row);
        $this->syncMaidRelations($maid, $row, true);
        $this->syncRentalFinance($maid, $row, $adminId);
        $this->syncRentalShelterContract($maid, $row, $adminId);

        return $outcome;
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

        $order = $this->ensureOrder($user, $maid, $row);

        $contract = $this->findContract($row['contract_number'] ?? null, $maid?->id, $user->id);
        $payload = $this->filterColumns('contracts', [
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_id' => $order?->id,
            'marketer_id' => $adminId,
            'status' => $status,
            'contract_number' => $row['contract_number'] ?? null,
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

        $this->upsertContractInfo($contract, [
            'contract_date' => $this->parseDate($row['contract_date'] ?? null),
            'contract_value' => $this->decimal($row['amount'] ?? null),
            'notes' => $row['notes'] ?? null,
        ]);

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

    private function clientFromRow(array $row): User
    {
        $idNumber = $this->normalizeIdNumber($row['client_id_number'] ?? $row['id_number'] ?? $row['national_num'] ?? null);
        $phone = $this->normalizeSaudiPhone($row['client_phone'] ?? $row['phone'] ?? '');
        $userById = $idNumber !== '' ? User::where('national_num', $idNumber)->first() : null;
        $userByPhone = $phone ? $this->findUserByPhone($phone) : null;

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

        if (!$phone) {
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
            'password' => Hash::make(Str::random(16)),
            'register_from' => 'data_import',
            'is_blocked' => 0,
        ]));
    }

    private function maidFromRow(array $row, bool $required): ?Maid
    {
        $passport = trim((string) ($row['maid_passport'] ?? $row['passport_num'] ?? ''));
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
            'passport_num' => $row['passport_num'] ?? $row['maid_passport'] ?? null,
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

    private function ensureOrder(User $user, Maid $maid, array $row): Order
    {
        $order = Order::where('user_id', $user->id)
            ->where('maid_id', $maid->id)
            ->latest('id')
            ->first();

        if ($order) {
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
            'status' => $this->normalizeContractStatus($row['status'] ?? null),
        ]);
    }

    private function generateOrderCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (Order::where('order_code', $code)->exists());

        return $code;
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

    private function findUserByPhone(string $phone): ?User
    {
        $candidates = array_values(array_unique(array_filter([
            $phone,
            '0' . $phone,
            '966' . $phone,
            '+966' . $phone,
        ])));

        return User::whereIn('phone', $candidates)->first();
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

        return $modelClass::query()
            ->whereTranslationLike('title', $value)
            ->value('id');
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
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
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
        return preg_replace('/\s+/u', '', trim((string) $value));
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

    private function normalizeStatus(?string $status): string
    {
        return in_array(trim((string) $status), ['محظور', 'موقوف', 'blocked'], true) ? 'blocked' : 'active';
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
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

        return (float) str_replace(',', '', (string) $value);
    }

    private function cellValue($value): string
    {
        return trim((string) $value);
    }

    private function cellCoordinate(int $column, int $row): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
    }

    private function normalizeArabicText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة', 'ـ'], ['ا', 'ا', 'ا', 'ي', 'ه', ''], $value);
        $value = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeHeader(string $value): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower(trim($value)));
    }

    private function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }
}
