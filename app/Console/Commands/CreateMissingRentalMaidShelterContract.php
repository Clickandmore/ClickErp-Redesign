<?php

namespace App\Console\Commands;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User as ClientUser;
use App\Models\RentalContract;
use App\Models\ShelterPlaces;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateMissingRentalMaidShelterContract extends Command
{
    protected $signature = 'maids:create-missing-shelter-contract
        {passport : Exact passport number of the rental maid}
        {--shelter-id=1 : Shelter place to assign}
        {--date= : Shelter entry date (Y-m-d); defaults to today}
        {--company-client-id= : Override the configured company client}
        {--force : Apply without an interactive confirmation}';

    protected $description = 'Create a missing company shelter contract for an eligible legacy rental maid';

    private const ACTIVE_RENTAL_STATUSES = [
        'new',
        'waiting_contract_send',
        'contract_sent',
        'contract_signed',
        'maid_delivered',
        'active',
        'transferred',
        'suspended',
        'frozen',
    ];

    private const BOOKED_ORDER_STATUSES = [
        OrderEnum::New->value,
        OrderEnum::UnderProcess->value,
        OrderEnum::Contracted->value,
        OrderEnum::ContractedWithoutContract->value,
    ];

    public function handle(): int
    {
        $passport = Str::upper(trim((string) $this->argument('passport')));
        if ($passport === '') {
            $this->error('Passport number cannot be empty.');

            return self::FAILURE;
        }

        try {
            $entryDate = $this->option('date')
                ? CarbonImmutable::createFromFormat('Y-m-d', (string) $this->option('date'))->toDateString()
                : now()->toDateString();
        } catch (\Throwable $exception) {
            $this->error('The --date value must use the Y-m-d format.');

            return self::FAILURE;
        }

        $maids = Maid::query()
            ->whereRaw('UPPER(TRIM(passport_num)) = ?', [$passport])
            ->get();

        if ($maids->isEmpty()) {
            $this->error("No active maid was found with passport {$passport}.");

            return self::FAILURE;
        }

        if ($maids->count() !== 1) {
            $this->error("Passport {$passport} belongs to multiple active maid records; no changes were made.");

            return self::FAILURE;
        }

        /** @var Maid $maid */
        $maid = $maids->first();
        $maid->load('jobTypes');

        if (! $maid->jobTypes->contains(fn ($jobType) => $this->isRentalJobType($jobType))) {
            $this->error("Maid {$maid->id} is not assigned to the rental job type.");

            return self::FAILURE;
        }

        $existingShelterContract = Contract::query()
            ->where('maid_id', $maid->id)
            ->where('status', ContractStatusEnum::Shelter->value)
            ->first();

        if ($existingShelterContract) {
            $this->info("Maid {$maid->id} already has shelter contract {$existingShelterContract->id}; no changes were made.");

            return self::SUCCESS;
        }

        if (Contract::query()->where('maid_id', $maid->id)->exists()) {
            $this->error('The maid has a non-shelter recruitment contract. Review it manually instead of creating a duplicate.');

            return self::FAILURE;
        }

        if (RentalContract::query()->where('maid_id', $maid->id)->whereIn('status', self::ACTIVE_RENTAL_STATUSES)->exists()) {
            $this->error('The maid has an active rental contract.');

            return self::FAILURE;
        }

        if ($maid->orders()->whereIn('status', self::BOOKED_ORDER_STATUSES)->exists()) {
            $this->error('The maid has an active order.');

            return self::FAILURE;
        }

        $shelter = ShelterPlaces::query()->find((int) $this->option('shelter-id'));
        if (! $shelter) {
            $this->error('The selected shelter does not exist.');

            return self::FAILURE;
        }

        $companyClient = $this->resolveCompanyClient();
        if (! $companyClient) {
            return self::FAILURE;
        }

        $this->table(['Maid', 'Passport', 'Company client', 'Shelter', 'Entry date'], [[
            "{$maid->id} - {$maid->name}",
            $maid->passport_num,
            "{$companyClient->id} - {$companyClient->name}",
            "{$shelter->id} - {$shelter->title}",
            $entryDate,
        ]]);

        if (! $this->option('force') && ! $this->confirm('Create this shelter contract?', false)) {
            $this->warn('Cancelled. No changes were made.');

            return self::SUCCESS;
        }

        $contract = DB::transaction(function () use ($maid, $companyClient, $shelter, $entryDate) {
            $lockedMaid = Maid::query()->lockForUpdate()->findOrFail($maid->id);

            if (Contract::query()->where('maid_id', $lockedMaid->id)->exists()) {
                throw new \RuntimeException('A recruitment/shelter contract was created concurrently; no duplicate was created.');
            }

            $contract = Contract::query()->create([
                'user_id' => $companyClient->id,
                'maid_id' => $lockedMaid->id,
                'branch_id' => $lockedMaid->branch_id,
                'status' => ContractStatusEnum::Shelter->value,
                'contract_date' => $entryDate,
            ]);

            ContractInfo::query()->create([
                'contract_id' => $contract->id,
                'contract_date' => $entryDate,
                'msaned_number' => '',
                'shelter_id' => $shelter->id,
                'wants_to_work' => 'yes',
                'receipt_source' => 'company',
                'is_outside_shelter' => false,
                'is_available_for_instant' => false,
            ]);

            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Shelter->value,
                'date' => $entryDate,
                'notes' => 'إنشاء عقد إيواء مفقود لعاملة تأجير قديمة بواسطة أمر الصيانة',
            ]);

            $lockedMaid->update([
                'status' => 'available',
                'maid_accommodation_type' => 2,
                'available' => true,
                'available_at' => $entryDate,
            ]);

            return $contract;
        });

        $this->info("Shelter contract {$contract->id} was created for maid {$maid->id}.");

        return self::SUCCESS;
    }

    private function isRentalJobType($jobType): bool
    {
        if ((int) $jobType->id === 2) {
            return true;
        }

        $title = Str::lower(trim((string) ($jobType->title ?? '')));
        $title = str_replace(['أ', 'إ', 'آ'], 'ا', $title);

        return Str::contains($title, ['ايجار', 'تاجير', 'rent', 'rental']);
    }

    private function resolveCompanyClient(): ?ClientUser
    {
        if ($this->option('company-client-id')) {
            $client = ClientUser::query()->find((int) $this->option('company-client-id'));
            if (! $client) {
                $this->error('The supplied company client does not exist.');
            }

            return $client;
        }

        $companyName = trim((string) documentSetting(
            'document_company_name',
            documentSetting('website_name', config('app.name'))
        ));
        $companyPhone = preg_replace('/\D+/', '', (string) documentSetting('phone', '')) ?: null;
        $companyEmail = trim((string) documentSetting('email', ''));

        $client = ClientUser::query()
            ->where(function ($query) use ($companyName, $companyPhone, $companyEmail) {
                if ($companyName !== '') {
                    $query->orWhere('name', 'like', '%' . $companyName . '%');
                }
                if ($companyPhone) {
                    $query->orWhere('phone', $companyPhone);
                }
                if ($companyEmail !== '') {
                    $query->orWhere('email', $companyEmail);
                }
            })
            ->first();

        if (! $client) {
            $this->error('The configured company client could not be resolved. Use --company-client-id explicitly.');
        }

        return $client;
    }
}
