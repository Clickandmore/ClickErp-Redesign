<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AnonymizeData extends Command
{
    protected $signature = 'ingaz:anonymize-data
        {--users=200 : How many users to anonymize}
        {--maids=200 : How many maids to anonymize}
        {--dry-run : Show what would change without writing to the DB}';

    protected $description = 'Anonymize existing users and maids in place (names, phones, IDs, passports, addresses) without deleting rows';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command is blocked in production.');
            return self::FAILURE;
        }

        $faker = $this->getFaker();
        $dryRun = (bool) $this->option('dry-run');
        $usersLimit = (int) $this->option('users');
        $maidsLimit = (int) $this->option('maids');

        $this->info(sprintf(
            'Anonymizing up to %d users and %d maids%s',
            $usersLimit,
            $maidsLimit,
            $dryRun ? ' (dry-run)' : ''
        ));

        DB::transaction(function () use ($faker, $dryRun, $usersLimit, $maidsLimit) {
            $this->anonymizeUsers($faker, $dryRun, $usersLimit);
            $this->anonymizeMaids($faker, $dryRun, $maidsLimit);

            if ($dryRun) {
                DB::rollBack();
            }
        });

        $this->info($dryRun ? 'Dry-run complete. No data changed.' : 'Anonymization complete.');

        return self::SUCCESS;
    }

    private function anonymizeUsers(Generator $faker, bool $dryRun, int $limit): void
    {
        $users = User::orderBy('id')->limit($limit)->get();
        $this->line("Users to anonymize: {$users->count()}");

        $counter = 1;
        foreach ($users as $user) {
            $phone = '059' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT);
            $email = "demo-user-{$counter}@example.com";

            $payload = [
                'name' => "عميل تجريبي {$counter}",
                'phone_code' => '+966',
                'phone' => $phone,
                'password' => Hash::make('password'),
                'register_from' => 'anonymized_demo',
                'status' => 1,
                'national_num' => '110' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
                'birthdate' => Carbon::now()->subYears(rand(25, 50)),
                'address' => 'عنوان تجريبي - حي النخيل، الرياض',
                'city' => 'Riyadh',
                'rooms_num' => rand(3, 6),
                'family_num' => rand(3, 8),
                'children' => rand(0, 4),
                'job' => 'وظيفة تجريبية',
            ];

            if (Schema::hasColumn($user->getTable(), 'email')) {
                $payload['email'] = $email;
            }

            if (! $dryRun) {
                $user->update($payload);
            }

            // Addresses
            $address = $user->addresses()->first();
            if ($address) {
                $addressPayload = [
                    'title' => 'عنوان تجريبي',
                    'address' => 'شارع أنس بن مالك، الرياض',
                    'phone' => $phone,
                    'name' => "عميل تجريبي {$counter}",
                    'notes' => 'بيانات تجريبية لأغراض الاختبار.',
                ];

                if (! $dryRun) {
                    $address->update($addressPayload);
                }
            }

            $this->line(" - User #{$user->id} -> {$phone}");
            $counter++;
        }
    }

    private function anonymizeMaids(Generator $faker, bool $dryRun, int $limit): void
    {
        $maids = Maid::orderBy('id')->limit($limit)->get();
        $this->line("Maids to anonymize: {$maids->count()}");

        $counter = 1;
        foreach ($maids as $maid) {
            $passport = 'ANON-PS-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT);
            $idNumber = 'ANON-ID-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT);

            $payload = [
                'name' => "عاملة تجريبية {$counter}",
                'passport_num' => $passport,
                'id_number' => $idNumber,
                'id_expiry_date' => Carbon::now()->addYears(3),
                'price' => rand(14000, 23000),
                'salary' => rand(2000, 4500),
                'age' => rand(23, 42),
                'birth_date' => Carbon::now()->subYears(rand(23, 42)),
                'experience_years' => (string) rand(1, 8),
                'experience_status' => Arr::random(['has_experience', 'no_experience']),
                'skills' => ['تنظيف', 'رعاية'],
                'languages' => ['العربية', 'الإنجليزية'],
                'percentage' => ['العربية' => '80%', 'الإنجليزية' => '70%'],
                'cv_file' => 'demo/cv-placeholder.webp',
                'cv_preview' => 'demo/cv-preview.webp',
                'image' => 'demo/maid-photo.webp',
                'available' => true,
                'available_at' => Carbon::now()->subDays(rand(1, 10)),
                'is_show' => true,
                'status' => Arr::random([
                    Maid::STATUS_AVAILABLE['en'],
                    Maid::STATUS_RESERVED['en'],
                    Maid::STATUS_CONTRACTED['en'],
                    Maid::STATUS_IN_ACCOMMODATION['en'],
                ]),
                'request_type' => 'standard',
                'height' => rand(150, 175) . ' cm',
                'weight' => rand(45, 80) . ' kg',
                'childrens_count' => rand(0, 3),
                'education' => 'ثانوي',
                'additional_notes' => 'بيانات تجريبية لأغراض العرض.',
            ];

            if (! $dryRun) {
                $maid->update($payload);

                $maid->translateOrNew('ar')->marital_status = 'عزباء';
                $maid->translateOrNew('en')->marital_status = 'Single';
                $maid->save();
            }

            $this->line(" - Maid #{$maid->id} -> {$passport}");
            $counter++;
        }
    }

    private function getFaker(): Generator
    {
        return app(Generator::class);
    }
}
