<?php

namespace App\Services\ingaz;

use App\Models\Country;
use App\Models\auth\Admin;
use App\Models\ingaz\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArabiaClientsImportService
{
    private ?array $userColumns = null;
    private ?array $sourceCreatorAdmins = null;

    public function __construct(private DataImportTemplateService $templates)
    {
    }

    public function import(UploadedFile $file, ?int $adminId): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $storedPath = $this->templates->storeUploadedFile('arabia_clients', $file, $adminId);
        $spreadsheet = IOFactory::load(Storage::path($storedPath));
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headers = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $headers[$column] = trim((string) $sheet->getCell([$column, 1])->getFormattedValue());
        }

        $result = [
            'path' => $storedPath,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'error_count' => 0,
        ];

        for ($rowNumber = 2; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
            $raw = [];
            foreach ($headers as $column => $label) {
                if ($label === '') {
                    continue;
                }

                $raw[$label] = $this->cellValue($sheet->getCell([$column, $rowNumber])->getFormattedValue());
            }

            if (collect($raw)->filter(fn ($value) => $value !== '')->isEmpty()) {
                continue;
            }

            $result['total']++;

            try {
                $outcome = DB::transaction(fn () => $this->importRow($raw, $headers, $rowNumber, $adminId));
                $result[$outcome]++;
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['error_count']++;

                if (count($result['errors']) < 200) {
                    $result['errors'][] = "صف {$rowNumber}: {$exception->getMessage()}";
                }
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $result;
    }

    private function importRow(array $raw, array $headers, int $rowNumber, ?int $adminId): string
    {
        $arabiaNumber = $this->positiveInteger($raw['رقم تعريف'] ?? null);
        $nationalNumber = $this->digits($raw['رقم الهوية'] ?? null);
        $sourcePhone = $this->cellValue($raw['الجوال'] ?? null);
        $sourcePhone2 = $this->cellValue($raw['رقم هاتف 2'] ?? null);
        $sourceCreatedBy = $this->cellValue($raw['أنشئ بواسطة'] ?? null);
        $phone = $this->normalizePhone($sourcePhone);
        $firstName = trim((string) ($raw['الإسم الأول'] ?? ''));
        $lastName = trim((string) ($raw['اسم العائلة'] ?? ''));
        $name = trim($firstName . ' ' . $lastName);

        if (!$arabiaNumber && $nationalNumber === '' && $sourcePhone === '') {
            throw new \InvalidArgumentException('رقم تعريف أربيا أو رقم الهوية أو الجوال مطلوب.');
        }

        if ($name === '') {
            $name = trim((string) ($raw['Full name english'] ?? '')) ?: 'عميل أربيا ' . ($arabiaNumber ?: $rowNumber);
        }

        $user = $arabiaNumber && $this->hasUserColumn('arabia_client_number')
            ? User::query()->where('arabia_client_number', $arabiaNumber)->first()
            : null;

        // A different Arabia number represents a separate source customer, even when
        // the source file contains a repeated identity or phone.
        if (!$user && ($nationalNumber !== '' || $phone !== '')) {
            $availableUsers = User::query()
                ->when($this->hasUserColumn('arabia_client_number'), fn ($query) => $query->whereNull('arabia_client_number'));

            $user = $nationalNumber !== ''
                ? (clone $availableUsers)->where('national_num', $nationalNumber)->first()
                : null;

            $user ??= $phone !== ''
                ? (clone $availableUsers)->whereIn('phone', $this->phoneCandidates($phone))->first()
                : null;
        }

        $isNew = !$user;
        $user ??= new User();
        $storedPhone = $this->availablePhone(
            $sourcePhone,
            $user->exists ? (int) $user->id : null,
            $arabiaNumber,
            $rowNumber,
            $user->exists ? (string) $user->phone : null,
        );
        $homeAddress = collect([
            $raw['شارع المنزل'] ?? null,
            $raw['الحي المتواجد فيه المنزل'] ?? null,
            $raw['مدينة المنزل'] ?? null,
            $raw['المنطقة'] ?? null,
        ])->map(fn ($value) => trim((string) $value))->filter()->implode('، ');

        $payload = [
            'arabia_client_number' => $arabiaNumber,
            'name' => $name,
            'full_name_en' => $raw['Full name english'] ?? null,
            // Arabia phone fields are source data, not login input. Keep them exactly
            // as exported and do not prepend a country code during display.
            'phone_code' => '',
            'phone' => $storedPhone,
            'phone2' => $sourcePhone2 ?: null,
            'source_phone' => $sourcePhone ?: null,
            'source_phone2' => $sourcePhone2 ?: null,
            'source_created_by' => $sourceCreatedBy ?: null,
            'email' => filter_var($raw['البريد الإلكتروني'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
            'national_num' => $nationalNumber ?: null,
            'nationality_id' => $this->countryId($raw['الجنسية'] ?? null),
            'marital_status' => $this->maritalStatus($raw['حالة الإجتماعية'] ?? null),
            'identity_date' => $raw['تاريخ الهوية'] ?? null,
            'identity_type' => $raw['نوع الهوية'] ?? null,
            'job' => $raw['الوظيفة'] ?? null,
            'profession' => $raw['الوظيفة'] ?? null,
            'workplace' => $raw['جهة العمل'] ?? null,
            'po_box' => $raw['صندوق بريد'] ?? null,
            'postal_code' => $raw['رقم بريدي'] ?? null,
            'monthly_income' => $this->decimal($raw['الراتب'] ?? null),
            'account_id' => $user->account_id ?: $this->financeAccountId($raw['رقم الحساب'] ?? null, $name, $adminId),
            'address' => $homeAddress ?: null,
            'city' => mb_substr((string) ($raw['مدينة المنزل'] ?: ($raw['مدينة العمل'] ?? null)), 0, 10) ?: null,
            'rooms_num' => $raw['عدد الغرف'] ?? null,
            'family_num' => $raw['عدد أفراد الأسرة'] ?? null,
            'children' => $raw['أقل من خمس سنوات'] ?? null,
            'tax_number' => $this->digits($raw['رقم ضريبي'] ?? null) ?: null,
            'notes' => $raw['ملاحظة'] ?? null,
            'is_blocked' => filled($raw['محظور'] ?? null)
                ? ($this->boolean($raw['محظور']) ? 1 : 0)
                : ($user->exists ? null : 0),
            'register_from' => 'arabia_import',
            'created_by' => $this->resolveSourceCreatorId($sourceCreatedBy)
                ?: $user->created_by
                ?: $adminId,
        ];

        if (!$user->exists) {
            $payload['password'] = Hash::make(Str::random(32));
        }

        $replaceableArabiaFields = [
            'full_name_en', 'phone_code', 'phone2', 'source_phone', 'source_phone2',
            'source_created_by', 'email', 'national_num', 'nationality_id',
            'marital_status', 'identity_date', 'identity_type', 'job', 'profession',
            'workplace', 'po_box', 'postal_code', 'monthly_income', 'address', 'city',
            'rooms_num', 'family_num', 'children', 'tax_number', 'notes',
        ];

        $payload = collect($payload)
            ->filter(fn ($value, $key) => $this->hasUserColumn($key)
                && ($value !== null && $value !== '' || in_array($key, $replaceableArabiaFields, true)))
            ->all();

        $user->fill($payload)->save();
        $this->storeAllDetails($user, $raw, $headers);

        return $isNew ? 'created' : 'updated';
    }

    private function storeAllDetails(User $user, array $raw, array $headers): void
    {
        $now = now();
        $details = [];

        // Replacement import: remove the previous Arabia snapshot for this client
        // before inserting the current file values. The user row itself is kept so
        // orders, contracts and finance links never lose their foreign key.
        DB::table('client_import_details')
            ->where('user_id', $user->id)
            ->where('source', 'arabia')
            ->delete();

        foreach (array_values($headers) as $index => $label) {
            if ($label === '') {
                continue;
            }

            $details[] = [
                'user_id' => $user->id,
                'source' => 'arabia',
                'field_key' => 'column_' . ($index + 1),
                'field_label' => $label,
                'field_value' => $raw[$label] ?? null,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('client_import_details')->insert($details);
    }

    private function availablePhone(
        string $phone,
        ?int $userId,
        ?int $arabiaNumber,
        int $rowNumber,
        ?string $existingPhone = null,
    ): string
    {
        if ($phone !== '') {
            // The source value must not be normalized or silently discarded. The
            // database still requires an exact unique operational phone, so only an
            // exact duplicate receives an internal placeholder; source_phone always
            // retains the original value for display, search, and exports.
            $query = User::query()->where('phone', $phone);
            if ($userId) {
                $query->where('id', '!=', $userId);
            }
            if (!$query->exists()) {
                return $phone;
            }
        }

        // Never replace a real existing number with an import placeholder when
        // the source value is missing, invalid, or already belongs to another user.
        if ($existingPhone && !str_starts_with($existingPhone, 'arabia-client-')) {
            return $existingPhone;
        }

        $base = 'arabia-client-' . ($arabiaNumber ?: $rowNumber);
        $candidate = $base;
        $suffix = 1;
        while (User::query()->when($userId, fn ($query) => $query->where('id', '!=', $userId))->where('phone', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    private function resolveSourceCreatorId(?string $sourceCreatedBy): ?int
    {
        $sourceCreatedBy = trim((string) $sourceCreatedBy);
        if ($sourceCreatedBy === '') {
            return null;
        }

        $source = $this->normalizeCreatorName($sourceCreatedBy);
        $admins = $this->sourceCreatorAdmins ??= Admin::withTrashed()
            ->get(['id', 'name'])
            ->map(fn (Admin $admin) => [
                'id' => (int) $admin->id,
                'name' => $this->normalizeCreatorName($admin->name),
            ])
            ->filter(fn (array $admin) => $admin['name'] !== '')
            ->sortByDesc(fn (array $admin) => mb_strlen($admin['name']))
            ->values()
            ->all();

        foreach ($admins as $admin) {
            if ($source === $admin['name']
                || preg_match('/(?:^|\\s*\\/\\s*)' . preg_quote($admin['name'], '/') . '(?:\\s*\\/\\s*|$)/u', $source)) {
                return $admin['id'];
            }
        }

        return null;
    }

    private function normalizeCreatorName(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);

        return preg_replace('/\\s+/u', ' ', $value);
    }

    private function phoneCandidates(string $phone): array
    {
        return array_values(array_unique(array_filter([$phone, '0' . $phone, '966' . $phone, '+966' . $phone])));
    }

    private function normalizePhone($value): string
    {
        $digits = $this->digits($value);
        $digits = preg_replace('/^(?:00966|966)/', '', $digits);
        if (preg_match('/^05\d{8}$/', $digits)) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : '';
    }

    private function digits($value): string
    {
        return preg_replace('/\D+/u', '', strtr((string) $value, [
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]));
    }

    private function positiveInteger($value): ?int
    {
        $digits = $this->digits($value);
        return $digits !== '' && (int) $digits > 0 ? (int) $digits : null;
    }

    private function decimal($value): ?float
    {
        $value = str_replace(',', '', trim((string) $value));
        return $value !== '' && is_numeric($value) ? (float) $value : null;
    }

    private function boolean($value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'نعم', 'yes', 'true', 'محظور'], true);
    }

    private function maritalStatus($value): ?string
    {
        $value = str_replace(['أ', 'إ', 'آ', 'ة'], ['ا', 'ا', 'ا', 'ه'], mb_strtolower(trim((string) $value)));

        return match ($value) {
            'اعزب', 'عزباء', 'single' => 'single',
            'متزوج', 'متزوجه', 'married' => 'married',
            'مطلق', 'مطلقه', 'divorced' => 'divorced',
            'ارمل', 'ارمله', 'widowed' => 'widowed',
            default => null,
        };
    }

    private function countryId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = rtrim(str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $value), 'هي');

        return Country::query()->get(['id', 'name_ar', 'name_en'])->first(function (Country $country) use ($value, $normalized) {
            $arabic = rtrim(str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], (string) $country->name_ar), 'هي');
            return $country->name_ar === $value || $country->name_en === $value || $arabic === $normalized;
        })?->id;
    }

    private function financeAccountId($value, string $clientName, ?int $adminId): ?int
    {
        $code = $this->digits($value);
        if ($code === '' || !Schema::hasTable('fn_accounts')) {
            return null;
        }

        $accountId = Account::query()->where('code', $code)->value('id');
        if ($accountId) {
            return (int) $accountId;
        }

        $parent = Account::query()
            ->where('type', AccountTypesEnum::Clients->value)
            ->lockForUpdate()
            ->first();

        if (!$parent) {
            return null;
        }

        return (int) Account::query()->create([
            'name' => $clientName,
            'operation_type' => $parent->operation_type,
            'status' => $parent->status,
            'level' => $parent->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $parent->id,
            'code' => $code,
            'created_by_id' => $adminId ?? auth('admin')->id() ?? 1,
        ])->id;
    }

    private function cellValue($value): string
    {
        return trim((string) $value);
    }

    private function hasUserColumn(string $column): bool
    {
        $this->userColumns ??= Schema::getColumnListing('users');

        return in_array($column, $this->userColumns, true);
    }
}
