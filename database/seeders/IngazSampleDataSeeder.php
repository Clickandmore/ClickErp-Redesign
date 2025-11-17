<?php

namespace Database\Seeders;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Country;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ShelterPlaces;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\Package;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class IngazSampleDataSeeder extends Seeder
{
    private const USER_PHONE_PREFIX = '05899';
    private const ORDER_CODE_PREFIX = 'DEMO-SEED-ORD-';
    private const RENTAL_NUMBER_PREFIX = 'DEMO-SEED-RNT-';
    private const PASSPORT_PREFIX = 'DEMO-SEED-PS-';

    public function run(): void
    {
        $countries = $this->seedCountries();
        $nationalities = $this->seedNationalities();
        $religions = $this->seedReligions();
        $jobTypes = $this->seedJobTypes();
        $jobTitles = $this->seedJobTitles();
        $skills = $this->seedSkills();
        $packages = $this->seedPackages();
        $shelterPlaces = $this->seedShelterPlaces();

        $users = $this->seedUsers($countries, $nationalities);
        $maids = $this->seedMaids($nationalities, $religions, $jobTypes, $jobTitles, $skills);
        $orders = $this->seedOrders($users, $maids, $jobTypes, $packages);

        $this->seedShelterContracts($orders, $maids, $users, $shelterPlaces);
        $this->seedRentalContracts($orders, $maids, $users);
    }

    private function seedCountries(): Collection
    {
        $countries = [
            [
                'code' => 'SA',
                'name_ar' => 'المملكة العربية السعودية',
                'name_en' => 'Saudi Arabia',
                'phone_code' => '+966',
                'currency' => 'SAR',
                'currency_symbol' => 'ر.س',
            ],
            [
                'code' => 'AE',
                'name_ar' => 'الإمارات العربية المتحدة',
                'name_en' => 'United Arab Emirates',
                'phone_code' => '+971',
                'currency' => 'AED',
                'currency_symbol' => 'د.إ',
            ],
            [
                'code' => 'QA',
                'name_ar' => 'قطر',
                'name_en' => 'Qatar',
                'phone_code' => '+974',
                'currency' => 'QAR',
                'currency_symbol' => 'ر.ق',
            ],
        ];

        return collect($countries)->map(function ($country, $index) {
            return Country::updateOrCreate(
                ['code' => $country['code']],
                array_merge($country, [
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ])
            );
        });
    }

    private function seedNationalities(): Collection
    {
        $data = [
            [
                'logo' => 'demo/flags/philippines.png',
                'phone_code' => '+63',
                'price' => 18500,
                'salary_with_experience' => 2800,
                'salary_with_no_experience' => 2300,
                'service_transportation_cost' => 4500,
                'rental_cost' => 3500,
                'monthly_salary' => 2500,
                'ar' => ['title' => 'الفلبين', 'sub_title' => 'خبرة عالية', 'description' => 'عاملات محترفات للرعاية المنزلية.'],
                'en' => ['title' => 'Philippines', 'sub_title' => 'High experience', 'description' => 'Professional household staff.'],
            ],
            [
                'logo' => 'demo/flags/kenya.png',
                'phone_code' => '+254',
                'price' => 16500,
                'salary_with_experience' => 2400,
                'salary_with_no_experience' => 2000,
                'service_transportation_cost' => 3800,
                'rental_cost' => 3200,
                'monthly_salary' => 2200,
                'ar' => ['title' => 'كينيا', 'sub_title' => 'مرونة عالية', 'description' => 'خيار مثالي للأسر التي تبحث عن مرونة.'],
                'en' => ['title' => 'Kenya', 'sub_title' => 'High flexibility', 'description' => 'Great option for flexible needs.'],
            ],
            [
                'logo' => 'demo/flags/ethiopia.png',
                'phone_code' => '+251',
                'price' => 14000,
                'salary_with_experience' => 2100,
                'salary_with_no_experience' => 1800,
                'service_transportation_cost' => 3000,
                'rental_cost' => 2850,
                'monthly_salary' => 2000,
                'ar' => ['title' => 'إثيوبيا', 'sub_title' => 'قيمة اقتصادية', 'description' => 'مزيج ممتاز بين التكلفة والكفاءة.'],
                'en' => ['title' => 'Ethiopia', 'sub_title' => 'Great value', 'description' => 'Balance between cost and output.'],
            ],
        ];

        return collect($data)->map(function ($item) {
            return Nationality::updateOrCreate(
                ['logo' => $item['logo']],
                [
                    'image' => $item['logo'],
                    'phone_code' => $item['phone_code'],
                    'price' => $item['price'],
                    'salary_with_experience' => $item['salary_with_experience'],
                    'salary_with_no_experience' => $item['salary_with_no_experience'],
                    'service_transportation_cost' => $item['service_transportation_cost'],
                    'rental_cost' => $item['rental_cost'],
                    'monthly_salary' => $item['monthly_salary'],
                    'is_show' => true,
                    'ar' => $item['ar'],
                    'en' => $item['en'],
                ]
            );
        });
    }

    private function seedReligions(): Collection
    {
        $items = [
            ['ar' => 'مسلمة', 'en' => 'Muslim'],
            ['ar' => 'مسيحية', 'en' => 'Christian'],
            ['ar' => 'هندوسية', 'en' => 'Hindu'],
        ];

        return collect($items)->map(function ($item) {
            $existing = Religion::whereTranslation('title', $item['ar'], 'ar')->first();
            if ($existing) {
                return $existing;
            }

            return Religion::create([
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedJobTypes(): Collection
    {
        $items = [
            [
                'image' => 'demo/job-types/recruitment.png',
                'ar' => ['title' => 'طلب استقدام', 'description' => 'خدمة الاستقدام الكاملة.'],
                'en' => ['title' => 'Recruitment', 'description' => 'Full recruitment journey.'],
            ],
            [
                'image' => 'demo/job-types/instant.webp',
                'ar' => ['title' => 'استلام فوري', 'description' => 'عاملة جاهزة خلال 24 ساعة.'],
                'en' => ['title' => 'Instant delivery', 'description' => 'Ready within 24h.'],
            ],
            [
                'image' => 'demo/job-types/rental.webp',
                'ar' => ['title' => 'تأجير عمالة', 'description' => 'عقود مرنة ومجدولة.'],
                'en' => ['title' => 'Labor rental', 'description' => 'Flexible rental contracts.'],
            ],
        ];

        return collect($items)->map(fn ($item) => JobType::updateOrCreate(
            ['image' => $item['image']],
            [
                'image' => $item['image'],
                'ar' => $item['ar'],
                'en' => $item['en'],
            ]
        ));
    }

    private function seedJobTitles(): Collection
    {
        $items = [
            ['image' => 'demo/job-titles/housekeeper.webp', 'ar' => 'مدبرة منزل', 'en' => 'Housekeeper'],
            ['image' => 'demo/job-titles/cook.webp', 'ar' => 'طباخة', 'en' => 'Cook'],
            ['image' => 'demo/job-titles/driver.jpg', 'ar' => 'سائقة', 'en' => 'Driver'],
        ];

        return collect($items)->map(fn ($item) => JobTitle::updateOrCreate(
            ['image' => $item['image']],
            [
                'image' => $item['image'],
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]
        ));
    }

    private function seedSkills(): Collection
    {
        $items = [
            ['ar' => 'العناية بالأطفال', 'en' => 'Child care'],
            ['ar' => 'تنظيف عميق', 'en' => 'Deep cleaning'],
            ['ar' => 'طبخ منزلي', 'en' => 'Home cooking'],
            ['ar' => 'رعاية كبار السن', 'en' => 'Elderly care'],
        ];

        return collect($items)->map(function ($item) {
            $existing = Skill::whereTranslation('title', $item['ar'], 'ar')->first();
            if ($existing) {
                return $existing;
            }

            return Skill::create([
                'is_show' => true,
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedPackages(): Collection
    {
        $items = [
            [
                'original_price' => 14999,
                'price_after_discount' => 12999,
                'price_with_tax' => 14899,
                'days_count' => 90,
                'ar' => ['title' => 'باقة الأساس'],
                'en' => ['title' => 'Starter package'],
            ],
            [
                'original_price' => 18999,
                'price_after_discount' => 15999,
                'price_with_tax' => 18200,
                'days_count' => 120,
                'ar' => ['title' => 'باقة مميزة'],
                'en' => ['title' => 'Premium package'],
            ],
        ];

        return collect($items)->map(fn ($item) => Package::updateOrCreate(
            ['original_price' => $item['original_price'], 'days_count' => $item['days_count']],
            $item
        ));
    }

    private function seedShelterPlaces(): Collection
    {
        $items = [
            ['ar' => 'إيواء الرياض المركزي', 'en' => 'Riyadh central shelter'],
            ['ar' => 'إيواء جدة الانتقالي', 'en' => 'Jeddah transit shelter'],
        ];

        return collect($items)->map(function ($item) {
            $existing = ShelterPlaces::whereTranslation('title', $item['ar'], 'ar')->first();
            if ($existing) {
                return $existing;
            }

            return ShelterPlaces::create([
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedUsers(Collection $countries, Collection $nationalities): Collection
    {
        return collect(range(1, 8))->map(function ($index) use ($countries, $nationalities) {
            $phone = self::USER_PHONE_PREFIX . str_pad((string) $index, 6, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => 'عميل تجريبي ' . $index,
                    'phone_code' => '+966',
                    'password' => Hash::make('password'),
                    'register_from' => 'demo_seed',
                    'status' => 1,
                    'nationality_id' => optional($nationalities->random())->id,
                    'email' => "demo-seed-client-{$index}@example.com",
                    'birthdate' => Carbon::now()->subYears(rand(25, 50)),
                    'national_num' => '1100' . str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                    'address' => 'طريق الملك فهد، الرياض',
                    'city' => 'Riyadh',
                    'rooms_num' => rand(3, 6),
                    'family_num' => rand(3, 8),
                    'children' => rand(0, 4),
                    'job' => 'موظف قطاع خاص',
                ]
            );

            UserAddress::updateOrCreate(
                ['user_id' => $user->id, 'title' => 'العنوان التجريبي'],
                [
                    'address' => 'حي الياسمين، شارع 10، الرياض',
                    'phone' => $user->phone,
                    'name' => $user->name,
                    'country_id' => optional($countries->random())->id,
                    'notes' => 'عنوان مخصص للبيانات التجريبية',
                ]
            );

            return $user;
        });
    }

    private function seedMaids(Collection $nationalities, Collection $religions, Collection $jobTypes, Collection $jobTitles, Collection $skills): Collection
    {
        $statuses = [
            Maid::STATUS_AVAILABLE['en'],
            Maid::STATUS_RESERVED['en'],
            Maid::STATUS_CONTRACTED['en'],
            Maid::STATUS_IN_ACCOMMODATION['en'],
        ];

        return collect(range(1, 12))->map(function ($index) use ($nationalities, $religions, $jobTypes, $jobTitles, $skills, $statuses) {
            $passport = self::PASSPORT_PREFIX . str_pad((string) $index, 4, '0', STR_PAD_LEFT);

            $maid = Maid::updateOrCreate(
                ['passport_num' => $passport],
                [
                    'name' => 'عاملة تجريبية ' . $index,
                    'nationality_id' => optional($nationalities->random())->id,
                    'religion_id' => optional($religions->random())->id,
                    'price' => rand(14000, 23000),
                    'salary' => rand(2000, 4500),
                    'age' => rand(23, 42),
                    'birth_date' => Carbon::now()->subYears(rand(23, 42)),
                    'experience_years' => (string) rand(1, 8),
                    'experience_status' => rand(0, 1) ? 'has_experience' : 'no_experience',
                    'skills' => $skills->random(rand(2, 3))->pluck('title'),
                    'languages' => ['العربية', 'الإنجليزية'],
                    'percentage' => ['العربية' => '80%', 'الإنجليزية' => '70%'],
                    'cv_file' => 'demo/cv-placeholder.webp',
                    'cv_preview' => 'demo/cv-preview.webp',
                    'image' => 'demo/maid-photo.webp',
                    'available' => true,
                    'available_at' => Carbon::now()->subDays(rand(1, 15)),
                    'is_show' => true,
                    'status' => Arr::random($statuses),
                    'request_type' => 'standard',
                    'height' => rand(150, 175) . ' cm',
                    'weight' => rand(45, 80) . ' kg',
                    'childrens_count' => rand(0, 3),
                    'education' => 'ثانوي',
                    'additional_notes' => 'بيانات لأغراض العرض داخل لوحات التحكم.',
                ]
            );

            $maid->translateOrNew('ar')->marital_status = 'عزباء';
            $maid->translateOrNew('en')->marital_status = 'Single';
            $maid->save();

            $maid->jobTypes()->syncWithoutDetaching($jobTypes->random(2)->pluck('id')->all());
            $maid->jobTitles()->syncWithoutDetaching($jobTitles->random(1)->pluck('id')->all());

            return $maid;
        });
    }

    private function seedOrders(Collection $users, Collection $maids, Collection $jobTypes, Collection $packages): Collection
    {
        $statuses = [
            OrderEnum::New->value,
            OrderEnum::UnderProcess->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
        ];

        return collect(range(1, 10))->map(function ($index) use ($users, $maids, $jobTypes, $packages, $statuses) {
            $orderCode = self::ORDER_CODE_PREFIX . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            $createdAt = Carbon::now()->subDays(rand(1, 30));
            $status = Arr::random($statuses);

            $maid = $maids->random();
            $user = $users->random();
            $jobType = $jobTypes->random();

            $order = Order::updateOrCreate(
                ['order_code' => $orderCode],
                [
                    'maid_id' => $maid->id,
                    'user_id' => $user->id,
                    'payment_method' => 'cash',
                    'job_type_id' => $jobType->id,
                    'status' => $status,
                    'status_changed_at' => $createdAt->copy()->addHours(4),
                    'deadline_at' => in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true)
                        ? $createdAt->copy()->addDays(3)
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addDays(1),
                ]
            );

            $addressId = optional($user->addresses()->first())->id;
            $package = $packages->random();

            OrderDetail::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'package_id' => $package->id,
                    'address_id' => $addressId,
                ]
            );

            return $order;
        });
    }

    private function seedShelterContracts(Collection $orders, Collection $maids, Collection $users, Collection $shelterPlaces): void
    {
        $shelterOrders = $orders->take(3);

        $shelterOrders->each(function (Order $order, int $index) use ($maids, $users, $shelterPlaces) {
            $maid = $maids->firstWhere('id', $order->maid_id) ?? $maids->random();
            $user = $users->firstWhere('id', $order->user_id) ?? $users->random();

            $contract = Contract::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'maid_id' => $maid->id,
                    'user_id' => $user->id,
                    'status' => ContractStatusEnum::Shelter->value,
                    'required_maid_nationality_id' => $maid->nationality_id,
                    'required_maid_religion_id' => $maid->religion_id,
                    'required_maid_age_min' => 25,
                    'required_maid_age_max' => 40,
                    'contract_date' => Carbon::now()->subDays(10 + $index),
                    'msaned_number' => 'DEMO-SEED-MS-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'visa_number' => 'VS-DEMO-' . $index,
                ]
            );

            ContractInfo::updateOrCreate(
                ['contract_id' => $contract->id],
                [
                    'shelter_id' => optional($shelterPlaces->random())->id,
                    'visa_number' => $contract->msaned_number,
                    'visa_hijry_date' => Carbon::now()->subDays(7),
                    'visa_milad_date' => Carbon::now()->subDays(7),
                    'working_hours_num' => '8',
                    'contract_duration' => 12,
                    'contract_value' => 48000,
                    'ingaz_from' => Carbon::now()->subDays(5),
                    'ingaz_to' => Carbon::now()->addDays(25),
                ]
            );
        });
    }

    private function seedRentalContracts(Collection $orders, Collection $maids, Collection $users): void
    {
        $rentalOrders = $orders->slice(3, 3);

        $rentalOrders->each(function (Order $order, int $index) use ($maids, $users) {
            $maid = $maids->firstWhere('id', $order->maid_id) ?? $maids->random();
            $user = $users->firstWhere('id', $order->user_id) ?? $users->random();

            $contractNumber = self::RENTAL_NUMBER_PREFIX . str_pad((string) $index, 3, '0', STR_PAD_LEFT);

            $rentalContract = RentalContract::updateOrCreate(
                ['contract_number' => $contractNumber],
                [
                    'user_id' => $user->id,
                    'maid_id' => $maid->id,
                    'order_id' => $order->id,
                    'status' => 'maid_delivered',
                    'contract_date' => Carbon::now()->subDays(5 + $index),
                    'warranty_status' => 'active',
                    'warranty_start_date' => Carbon::now()->subDays(5),
                    'warranty_end_date' => Carbon::now()->addDays(55),
                ]
            );

            RentalContractInfo::updateOrCreate(
                ['rental_contract_id' => $rentalContract->id],
                [
                    'working_hours_per_day' => 8,
                    'hourly_rate' => 60,
                    'monthly_salary' => 4000,
                    'monthly_cost' => 5500,
                    'contract_duration_months' => 6,
                    'contract_duration_days' => 180,
                    'contract_value' => 33000,
                    'ingaz_from' => Carbon::now()->subDays(3),
                    'ingaz_to' => Carbon::now()->addDays(150),
                    'additional_terms' => ['demo' => 'Generated for dashboard previews'],
                ]
            );
        });
    }
}
