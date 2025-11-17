<?php

namespace Database\Seeders;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\ComplaintFeedback;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\Country;
use App\Models\ShelterPlaces;
use App\Models\ShelterWorkers;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
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
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IngazDemoSeeder extends Seeder
{
    protected Generator $faker;
    protected int $userPhoneSequence = 50000000;

    public function run(): void
    {
        $this->faker = app(Generator::class);

        Model::unguard();

        $this->truncateTables([
            'complaint_feedbacks',
            'complaints',
            'complaint_timelines',
            'complaint_types',
            'contract_status_logs',
            'contract_infos',
            'contracts',
            'order_transfers',
            'order_details',
            'orders',
            'maid_job_titles',
            'maid_jobs',
            'maid_translations',
            'maid_expertises',
            'maids',
            'skills_translations',
            'skills',
            'job_titles_translations',
            'job_titles',
            'job_types_translations',
            'job_types',
            'nationalities_translations',
            'nationalities',
            'religions_translations',
            'religions',
            'packages_translations',
            'packages',
            'shelter_place_translations',
            'shelter_places',
            'shelter_workers',
            'user_addresses',
            'users',
        ]);

        $nationalities = $this->seedNationalities();
        $religions = $this->seedReligions();
        $jobTypes = $this->seedJobTypes();
        $jobTitles = $this->seedJobTitles();
        $skills = $this->seedSkills();
        $packages = $this->seedPackages();
        $shelterPlaces = $this->seedShelterPlaces();
        $complaintTypes = $this->seedComplaintTypes();

        $users = $this->seedUsers($nationalities);
        $maids = $this->seedMaids($nationalities, $religions, $jobTypes, $jobTitles, $skills);
        $orders = $this->seedOrders($users, $maids, $jobTypes, $packages);
        $contracts = $this->seedContracts($orders, $maids, $users, $shelterPlaces);
        $this->seedComplaints($users, $maids, $complaintTypes);
        $this->seedShelterWorkers($maids, $contracts, $nationalities);

        if ($this->command) {
            $this->command->info(sprintf(
                'Ingaz demo data ready: %d clients, %d maids, %d orders, %d contracts, %d complaints, %d shelter records.',
                $users->count(),
                $maids->count(),
                $orders->count(),
                $contracts->count(),
                Schema::hasTable('complaints') ? Complaint::count() : 0,
                Schema::hasTable('shelter_workers') ? ShelterWorkers::count() : 0
            ));
        }

        Model::reguard();
    }

    private function truncateTables(array $tables): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function seedNationalities(): Collection
    {
        if (! Schema::hasTable('nationalities')) {
            return collect();
        }

        $data = [
            [
                'code' => 'PH',
                'logo' => 'demo/flags/philippines.png',
                'phone_code' => '+63',
                'price' => 18500,
                'salary_with_experience' => 2800,
                'salary_with_no_experience' => 2300,
                'service_transportation_cost' => 4500,
                'rental_cost' => 3500,
                'monthly_salary' => 2500,
                'ar' => ['title' => 'الفلبين', 'sub_title' => 'كوادر منزلية بخبرة', 'description' => 'عاملات مدربات على رعاية الأطفال وخبرة في المنازل.'],
                'en' => ['title' => 'Philippines', 'sub_title' => 'Experienced staff', 'description' => 'Workers experienced in child care and housekeeping.'],
            ],
            [
                'code' => 'KE',
                'logo' => 'demo/flags/kenya.png',
                'phone_code' => '+254',
                'price' => 16500,
                'salary_with_experience' => 2400,
                'salary_with_no_experience' => 2000,
                'service_transportation_cost' => 3800,
                'rental_cost' => 3200,
                'monthly_salary' => 2200,
                'ar' => ['title' => 'كينيا', 'sub_title' => 'مرونة عالية', 'description' => 'عاملات يتميزن بالمرونة والقدرة على العمل لساعات طويلة.'],
                'en' => ['title' => 'Kenya', 'sub_title' => 'High flexibility', 'description' => 'Flexible workers that adapt to long shifts.'],
            ],
            [
                'code' => 'NP',
                'logo' => 'demo/flags/nepal.png',
                'phone_code' => '+977',
                'price' => 15000,
                'salary_with_experience' => 2200,
                'salary_with_no_experience' => 1900,
                'service_transportation_cost' => 3200,
                'rental_cost' => 3000,
                'monthly_salary' => 2100,
                'ar' => ['title' => 'نيبال', 'sub_title' => 'دقة في العمل', 'description' => 'عاملات يتميزن بالدقة في تنظيم المنزل والعناية بالمسنين.'],
                'en' => ['title' => 'Nepal', 'sub_title' => 'Detail oriented', 'description' => 'Neat and detail oriented workers for senior care.'],
            ],
            [
                'code' => 'ET',
                'logo' => 'demo/flags/ethiopia.png',
                'phone_code' => '+251',
                'price' => 14000,
                'salary_with_experience' => 2100,
                'salary_with_no_experience' => 1800,
                'service_transportation_cost' => 3000,
                'rental_cost' => 2850,
                'monthly_salary' => 2000,
                'ar' => ['title' => 'إثيوبيا', 'sub_title' => 'كفاءة عالية', 'description' => 'خيار مناسب للأسر التي تبحث عن كفاءة مع تكلفة منخفضة.'],
                'en' => ['title' => 'Ethiopia', 'sub_title' => 'Great value', 'description' => 'Great value option with solid productivity.'],
            ],
            [
                'code' => 'IN',
                'logo' => 'demo/flags/india.jpeg',
                'phone_code' => '+91',
                'price' => 17000,
                'salary_with_experience' => 2600,
                'salary_with_no_experience' => 2100,
                'service_transportation_cost' => 3600,
                'rental_cost' => 3300,
                'monthly_salary' => 2300,
                'ar' => ['title' => 'الهند', 'sub_title' => 'خبرة متنوعة', 'description' => 'مناسب للأعمال المنزلية والضيافة والطبخ.'],
                'en' => ['title' => 'India', 'sub_title' => 'Multi skilled', 'description' => 'Ideal for hospitality, cooking, and general help.'],
            ],
        ];

        return collect($data)->map(function (array $item) {
            return Nationality::create([
                'logo' => $item['logo'],
                'image' => $item['logo'],
                'phone_code' => $item['phone_code'],
                'price' => $item['price'],
                'salary_with_experience' => $item['salary_with_experience'],
                'salary_with_no_experience' => $item['salary_with_no_experience'],
                'service_transportation_cost' => $item['service_transportation_cost'],
                'rental_cost' => $item['rental_cost'],
                'monthly_salary' => $item['monthly_salary'],
                'is_show' => true,
                'ar' => [
                    'title' => $item['ar']['title'],
                    'sub_title' => $item['ar']['sub_title'],
                    'description' => $item['ar']['description'],
                ],
                'en' => [
                    'title' => $item['en']['title'],
                    'sub_title' => $item['en']['sub_title'],
                    'description' => $item['en']['description'],
                ],
            ]);
        });
    }

    private function seedReligions(): Collection
    {
        if (! Schema::hasTable('religions')) {
            return collect();
        }

        $data = [
            ['ar' => 'مسلمة', 'en' => 'Muslim'],
            ['ar' => 'مسيحية', 'en' => 'Christian'],
            ['ar' => 'هندوسية', 'en' => 'Hindu'],
            ['ar' => 'أديان أخرى', 'en' => 'Other'],
        ];

        return collect($data)->map(function (array $item) {
            return Religion::create([
                'is_show' => true,
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedJobTypes(): Collection
    {
        if (! Schema::hasTable('job_types')) {
            return collect();
        }

        $data = [
            [
                'image' => 'demo/job-types/recruitment.png',
                'ar' => ['title' => 'طلب استقدام', 'description' => 'خدمة الاستقدام الشامل من بلد العاملة وحتى الوصول.'],
                'en' => ['title' => 'Recruitment request', 'description' => 'Full recruitment service from sourcing to arrival.'],
            ],
            [
                'image' => 'demo/job-types/instant.webp',
                'ar' => ['title' => 'استلام فوري', 'description' => 'اختيار عاملة جاهزة للتسليم خلال 24 ساعة.'],
                'en' => ['title' => 'Instant delivery', 'description' => 'Pick a ready maid for delivery within 24h.'],
            ],
            [
                'image' => 'demo/job-types/rental.webp',
                'ar' => ['title' => 'تأجير عمالة', 'description' => 'خدمة التأجير المرن بعقود قصيرة ومجدولة.'],
                'en' => ['title' => 'Labor rental', 'description' => 'Flexible rental contracts for short term needs.'],
            ],
        ];

        return collect($data)->map(function (array $item) {
            return JobType::create([
                'image' => $item['image'],
                'ar' => [
                    'title' => $item['ar']['title'],
                    'description' => $item['ar']['description'],
                ],
                'en' => [
                    'title' => $item['en']['title'],
                    'description' => $item['en']['description'],
                ],
            ]);
        });
    }

    private function seedJobTitles(): Collection
    {
        if (! Schema::hasTable('job_titles')) {
            return collect();
        }

        $data = [
            ['image' => 'demo/job-titles/housekeeper.webp', 'ar' => 'مدبرة منزل', 'en' => 'Housekeeper'],
            ['image' => 'demo/job-titles/cook.webp', 'ar' => 'طباخة', 'en' => 'Cook'],
            ['image' => 'demo/job-titles/driver.jpg', 'ar' => 'سائقة', 'en' => 'Driver'],
            ['image' => 'demo/job-titles/housekeeper.webp', 'ar' => 'مربية أطفال', 'en' => 'Nanny'],
        ];

        return collect($data)->map(function (array $item) {
            return JobTitle::create([
                'image' => $item['image'],
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedSkills(): Collection
    {
        if (! Schema::hasTable('skills')) {
            return collect();
        }

        $data = [
            ['ar' => 'العناية بالأطفال', 'en' => 'Child care'],
            ['ar' => 'الطهي المتخصص', 'en' => 'Professional cooking'],
            ['ar' => 'تنظيف عميق', 'en' => 'Deep cleaning'],
            ['ar' => 'رعاية كبار السن', 'en' => 'Elderly support'],
            ['ar' => 'إدارة المنزل', 'en' => 'House management'],
        ];

        return collect($data)->map(function (array $item) {
            return Skill::create([
                'is_show' => true,
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedPackages(): Collection
    {
        if (! Schema::hasTable('packages')) {
            return collect();
        }

        $data = [
            [
                'prices' => ['original' => 14999, 'discounted' => 12999, 'tax' => 14899],
                'days' => 90,
                'ar' => 'باقة الأساس',
                'en' => 'Starter package',
            ],
            [
                'prices' => ['original' => 18999, 'discounted' => 15999, 'tax' => 18200],
                'days' => 120,
                'ar' => 'باقة بريميوم',
                'en' => 'Premium package',
            ],
            [
                'prices' => ['original' => 21999, 'discounted' => 18999, 'tax' => 21600],
                'days' => 180,
                'ar' => 'باقة رجال الأعمال',
                'en' => 'Executive package',
            ],
        ];

        return collect($data)->map(function (array $item) {
            return Package::create([
                'original_price' => $item['prices']['original'],
                'price_after_discount' => $item['prices']['discounted'],
                'price_with_tax' => $item['prices']['tax'],
                'days_count' => $item['days'],
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedShelterPlaces(): Collection
    {
        if (! Schema::hasTable('shelter_places')) {
            return collect();
        }

        $data = [
            ['ar' => 'إيواء الرياض المركزي', 'en' => 'Riyadh central shelter'],
            ['ar' => 'إيواء جدة الانتقالي', 'en' => 'Jeddah transit shelter'],
            ['ar' => 'إيواء الدمام السريع', 'en' => 'Dammam express shelter'],
        ];

        return collect($data)->map(function (array $item) {
            return ShelterPlaces::create([
                'ar' => ['title' => $item['ar']],
                'en' => ['title' => $item['en']],
            ]);
        });
    }

    private function seedComplaintTypes(): Collection
    {
        if (! Schema::hasTable('complaint_types')) {
            return collect();
        }

        $data = [
            'تأخر الوصول',
            'إلغاء الطلب',
            'مشاكل السكن والإيواء',
            'دعم فني',
            'استفسار مالي',
        ];

        return collect($data)->map(fn(string $name) => ComplaintType::create(['name' => $name]));
    }

    private function seedUsers(Collection $nationalities): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        $countryIds = Schema::hasTable('countries')
            ? Country::query()->limit(30)->pluck('id')
            : collect();

        return collect(range(1, 40))->map(function () use ($nationalities, $countryIds) {
            $nationality = $nationalities->random();
            $user = User::create([
                'name' => $this->faker->name(),
                'phone_code' => '+966',
                'phone' => '05' . str_pad((string) $this->userPhoneSequence++, 8, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'register_from' => 'web',
                'status' => 1,
                'nationality_id' => $nationality->id,
                'email' => $this->faker->unique()->safeEmail(),
                'birthdate' => $this->faker->dateTimeBetween('-55 years', '-22 years'),
                'national_num' => (string) $this->faker->numerify('1#########'),
                'phone2' => $this->faker->boolean(40) ? '05' . $this->faker->numerify('########') : null,
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->randomElement(['Riyadh', 'Jeddah', 'Dammam', 'Medina']),
                'rooms_num' => (string) $this->faker->numberBetween(2, 6),
                'family_num' => (string) $this->faker->numberBetween(3, 8),
                'children' => (string) $this->faker->numberBetween(0, 4),
                'job' => $this->faker->randomElement(['Engineer', 'Teacher', 'Doctor', 'Entrepreneur']),
                'beds_num' => $this->faker->numberBetween(2, 6),
                'profession' => $this->faker->jobTitle(),
                'monthly_income' => $this->faker->numberBetween(8000, 25000),
            ]);

            $addressCount = $this->faker->numberBetween(1, 2);
            foreach (range(1, $addressCount) as $idx) {
                UserAddress::create([
                    'user_id' => $user->id,
                    'country_id' => $countryIds->isNotEmpty() ? $countryIds->random() : null,
                    'address' => $this->faker->streetAddress(),
                    'title' => $idx === 1 ? 'العنوان الرئيسي' : 'مكتب العمل',
                    'phone' => $user->phone,
                    'name' => $user->name,
                    'notes' => $this->faker->sentence(),
                ]);
            }

            return $user;
        });
    }

    private function seedMaids(
        Collection $nationalities,
        Collection $religions,
        Collection $jobTypes,
        Collection $jobTitles,
        Collection $skills
    ): Collection {
        if (! Schema::hasTable('maids')) {
            return collect();
        }

        $statusPool = [
            'available',
            'reserved',
            'contracted',
            'in_accommodation',
        ];

        return collect(range(1, 45))->map(function () use ($nationalities, $religions, $jobTypes, $jobTitles, $skills, $statusPool) {
            $nationality = $nationalities->random();
            $religion = $religions->random();

            $languages = $this->faker->randomElements(
                ['العربية', 'الإنجليزية', 'الأوردو', 'التغالوغ', 'السواحلية'],
                $this->faker->numberBetween(1, 3)
            );
            $languagePercentages = collect($languages)->mapWithKeys(fn($lang) => [$lang => $this->faker->numberBetween(50, 95) . '%'])->toArray();
            $skillNames = $skills->isNotEmpty()
                ? $skills->random($this->faker->numberBetween(2, min(4, $skills->count())))->pluck('title')->toArray()
                : $this->faker->randomElements(['تنظيف', 'طبخ', 'غسيل', 'مساعدة كبار السن'], 2);

            $hasOffer = $this->faker->boolean(50);
            $availableAt = Carbon::now()->addDays($this->faker->numberBetween(-15, 20));

            $maid = Maid::create([
                'name' => $this->faker->firstNameFemale() . ' ' . $this->faker->lastName(),
                'nationality_id' => $nationality->id,
                'religion_id' => $religion->id,
                'passport_num' => strtoupper(Str::random(2)) . $this->faker->numerify('#######'),
                'id_number' => (string) $this->faker->numerify('2#########'),
                'id_expiry_date' => Carbon::now()->addYears($this->faker->numberBetween(2, 5)),
                'price' => $this->faker->numberBetween(14000, 25000),
                'salary' => $this->faker->numberBetween(2000, 4500),
                'age' => $this->faker->numberBetween(22, 42),
                'birth_date' => $this->faker->dateTimeBetween('-42 years', '-22 years'),
                'experience_years' => (string) $this->faker->numberBetween(1, 8),
                'experience_status' => $this->faker->boolean(75) ? 'has_experience' : 'no_experience',
                'skills' => $skillNames,
                'languages' => $languages,
                'percentage' => $languagePercentages,
                'cv_file' => 'demo/cv-placeholder.webp',
                'cv_preview' => 'demo/cv-preview.webp',
                'image' => 'demo/maid-photo.webp',
                'available' => $availableAt->isPast(),
                'available_at' => $availableAt,
                'is_show' => true,
                'is_pinned' => $this->faker->boolean(30),
                'status' => Arr::random($statusPool),
                'request_type' => $this->faker->randomElement(['instant', 'standard', 'replacement']),
                'height' => $this->faker->numberBetween(145, 180) . ' cm',
                'weight' => $this->faker->numberBetween(45, 85) . ' kg',
                'childrens_count' => $this->faker->numberBetween(0, 4),
                'education' => $this->faker->randomElement(['ابتدائي', 'ثانوي', 'جامعي']),
                'additional_notes' => $this->faker->sentence(),
                'duration' => $this->faker->randomElement(['12 شهر', '18 شهر', '24 شهر']),
                'offer_price' => $hasOffer ? $this->faker->numberBetween(12000, 18000) : null,
                'offer_duration' => $hasOffer ? $this->faker->randomElement(['30 يوم', '45 يوم']) : null,
                'offer_description' => $hasOffer ? 'عرض موسمي خاص بمناسبة العودة للمدارس' : null,
                'offer_starts_at' => $hasOffer ? Carbon::now()->subDays($this->faker->numberBetween(1, 10)) : null,
                'offer_expires_at' => $hasOffer ? Carbon::now()->addDays($this->faker->numberBetween(10, 25)) : null,
            ]);

            $maid->translateOrNew('ar')->marital_status = $this->faker->randomElement(['عزباء', 'متزوجة']);
            $maid->translateOrNew('en')->marital_status = $maid->marital_status === 'عزباء' ? 'Single' : 'Married';
            $maid->save();

            $jobTypeIds = $jobTypes->pluck('id')->toArray();
            if (! empty($jobTypeIds)) {
                $maid->jobTypes()->sync(Arr::random(
                    $jobTypeIds,
                    min(count($jobTypeIds), $this->faker->numberBetween(1, 2))
                ));
            }

            $jobTitleIds = $jobTitles->pluck('id')->toArray();
            if (! empty($jobTitleIds)) {
                $maid->jobTitles()->sync(Arr::random(
                    $jobTitleIds,
                    min(count($jobTitleIds), $this->faker->numberBetween(1, 2))
                ));
            }

            return $maid;
        });
    }

    private function seedOrders(
        Collection $users,
        Collection $maids,
        Collection $jobTypes,
        Collection $packages
    ): Collection {
        if (! Schema::hasTable('orders') || $users->isEmpty() || $maids->isEmpty()) {
            return collect();
        }

        $statuses = collect(OrderEnum::cases())->map->value()->toArray();

        return collect(range(1, 80))->map(function () use ($users, $maids, $jobTypes, $packages, $statuses) {
            $maid = $maids->random();
            $user = $users->random();
            $jobType = $jobTypes->random();
            $status = Arr::random($statuses);

            $createdAt = Carbon::now()->subDays($this->faker->numberBetween(0, 60));

            $order = Order::create([
                'maid_id' => $maid->id,
                'user_id' => $user->id,
                'payment_method' => $this->faker->randomElement(['cash', 'mada', 'tabby', 'tamara']),
                'payment_type' => $this->faker->randomElement(['full', 'installment', 'deposit']),
                'order_code' => strtoupper(Str::random(3)) . $this->faker->numerify('#####'),
                'job_type_id' => $jobType->id,
                'status' => $status,
                'status_changed_at' => $createdAt->copy()->addHours($this->faker->numberBetween(1, 48)),
                'deadline_at' => in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true)
                    ? $createdAt->copy()->addHours($this->faker->numberBetween(24, 72))
                    : null,
                'expiration_time' => $createdAt->copy()->addHours(72),
                'refuse_reason' => $status === OrderEnum::Refused->value ? $this->faker->sentence() : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addHours($this->faker->numberBetween(1, 12)),
            ]);

            $package = $packages->random();
            $address = $user->addresses()->inRandomOrder()->first();

            OrderDetail::create([
                'order_id' => $order->id,
                'package_id' => $package->id,
                'address_id' => optional($address)->id,
                'deliver_maid' => $this->faker->boolean(60) ? '1' : '0',
            ]);

            return $order;
        });
    }

    private function seedContracts(
        Collection $orders,
        Collection $maids,
        Collection $users,
        Collection $shelterPlaces
    ): Collection {
        if (! Schema::hasTable('contracts') || $orders->isEmpty()) {
            return collect();
        }

        $statusPool = collect(ContractStatusEnum::cases())->map->value()->toArray();
        $contractCount = min(60, $orders->count());
        $orderSample = $contractCount === 1 ? collect([$orders->random()]) : $orders->random($contractCount);

        return collect($orderSample)->map(function (Order $order) use ($maids, $users, $statusPool, $shelterPlaces) {
            $maid = $maids->firstWhere('id', $order->maid_id) ?? $maids->random();
            $user = $users->firstWhere('id', $order->user_id) ?? $users->random();
            $status = Arr::random($statusPool);
            $createdAt = Carbon::parse($order->created_at)->addDays($this->faker->numberBetween(1, 10));

            $contract = Contract::create([
                'order_id' => $order->id,
                'maid_id' => $maid->id,
                'user_id' => $user->id,
                'status' => $status,
                'type' => $this->faker->randomElement(['maid', 'rental']),
                'required_maid_nationality_id' => $maid->nationality_id,
                'required_maid_religion_id' => $maid->religion_id,
                'required_maid_age_min' => $this->faker->numberBetween(23, 30),
                'required_maid_age_max' => $this->faker->numberBetween(31, 40),
                'required_maid_experience_min' => $this->faker->numberBetween(1, 4),
                'required_maid_job_title_id' => optional($maid->jobTitles()->first())->id,
                'required_maid_notes' => $this->faker->sentence(),
                'contract_date' => $createdAt->toDateString(),
                'visa_number' => strtoupper(Str::random(2)) . $this->faker->numerify('######'),
                'msaned_number' => $this->faker->numerify('MS####'),
                'warranty_status' => $status === ContractStatusEnum::Completed->value ? 'active' : 'not_applicable',
                'warranty_start_date' => $status === ContractStatusEnum::Completed->value ? $createdAt->copy()->addDay() : null,
                'warranty_end_date' => $status === ContractStatusEnum::Completed->value ? $createdAt->copy()->addDays(90) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays($this->faker->numberBetween(1, 20)),
            ]);

            ContractInfo::create([
                'contract_id' => $contract->id,
                'visa_number' => $contract->visa_number,
                'visa_hijry_date' => $createdAt->copy()->addDays(2),
                'visa_milad_date' => $createdAt->copy()->addDays(2),
                'working_hours_num' => (string) $this->faker->numberBetween(8, 12),
                'sponsorship_price' => $this->faker->numberBetween(5000, 15000),
                'salary_after_sponsorship' => $maid->salary + $this->faker->numberBetween(200, 600),
                'monthly_cost' => $this->faker->numberBetween(3000, 8500),
                'contract_duration' => $this->faker->numberBetween(6, 24),
                'contract_value' => $this->faker->numberBetween(12000, 45000),
                'contract_date' => $contract->contract_date,
                'ingaz_from' => $createdAt->copy()->addDays(3),
                'ingaz_to' => $createdAt->copy()->addDays(50),
                'previous_sponsorship' => null,
                'msaned_number' => $contract->msaned_number,
                'tafwed_number' => $this->faker->numerify('TFW###'),
                'maid_date_back' => $status === ContractStatusEnum::Back->value ? $createdAt->copy()->addDays(70) : null,
                'salary_final_status' => $this->faker->randomElement(['paid', 'pending', 'refunded']),
                'duration_from_enter' => $this->faker->numberBetween(2, 12) . ' months',
                'back_reasons' => $status === ContractStatusEnum::Back->value ? $this->faker->sentence() : null,
                'shelter_id' => $status === ContractStatusEnum::Shelter->value && $shelterPlaces->isNotEmpty()
                    ? $shelterPlaces->random()->id
                    : null,
                'receipt_source' => $this->faker->randomElement(['office', 'shelter', 'bank transfer']),
                'wants_to_work' => $this->faker->randomElement(['yes', 'no', 'unknown']),
                'arrival_date' => $createdAt->copy()->addDays(25),
                'is_available_for_instant' => $this->faker->boolean(30),
            ]);

            $this->seedContractStatuses($contract, $status, $user);

            return $contract;
        });
    }

    private function seedContractStatuses(Contract $contract, string $finalStatus, User $user): void
    {
        if (! Schema::hasTable('contract_status_logs')) {
            return;
        }

        $journey = collect([
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            $finalStatus,
        ])->unique()->values();

        $baseDate = Carbon::parse($contract->created_at);

        $journey->each(function (string $status, int $index) use ($contract, $user, $baseDate) {
            ContractStatusLog::create([
                'contract_id' => $contract->id,
                'status' => $status,
                'date' => $baseDate->copy()->addDays($index * 5),
                'notes' => $this->faker->sentence(),
                'user_id' => $user->id,
            ]);
        });
    }

    private function seedComplaints(Collection $users, Collection $maids, Collection $types): void
    {
        if (! Schema::hasTable('complaints') || $users->isEmpty()) {
            return;
        }

        collect(range(1, 25))->each(function () use ($users, $maids, $types) {
            $user = $users->random();
            $maid = $maids->random();
            $status = $this->faker->randomElement(['pending', 'resolved', 'rejected']);
            $priority = $this->faker->randomElement(['low', 'medium', 'high', 'urgent']);

            $complaint = Complaint::create([
                'complaint_type_id' => $types->isNotEmpty() ? $types->random()->id : null,
                'address' => $this->faker->address(),
                'message' => $this->faker->sentence(10),
                'details' => $this->faker->paragraph(),
                'status' => $status,
                'priority' => $priority,
                'user_id' => $user->id,
                'is_read' => $this->faker->boolean(70),
                'complainant_name' => $user->name,
                'complainant_phone' => $user->phone,
                'maid_passport' => $maid->passport_num,
                'maid_nationality_id' => $maid->nationality_id,
                'subject' => $this->faker->sentence(5),
                'attachments' => $this->faker->boolean(35) ? ['demo/cv-placeholder.webp'] : null,
                'resolved_at' => $status === 'resolved' ? Carbon::now()->subDays($this->faker->numberBetween(1, 7)) : null,
                'resolved_by' => null,
                'closure_reason' => $status !== 'pending' ? $this->faker->sentence() : null,
                'closure_attachment' => null,
            ]);

            if (Schema::hasTable('complaint_feedbacks') && $this->faker->boolean(35)) {
                ComplaintFeedback::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $user->id,
                    'token' => ComplaintFeedback::generateToken(),
                    'overall_rating' => $this->faker->randomElement([
                        'very_satisfied',
                        'satisfied',
                        'neutral',
                        'dissatisfied',
                    ]),
                    'response_speed' => $this->faker->numberBetween(1, 5),
                    'solution_quality' => $this->faker->numberBetween(1, 5),
                    'employee_professionalism' => $this->faker->numberBetween(1, 5),
                    'communication_quality' => $this->faker->numberBetween(1, 5),
                    'would_recommend' => $this->faker->randomElement(['yes', 'maybe', 'no']),
                    'comments' => $this->faker->sentence(),
                    'phone' => $user->phone,
                    'sms_sent_at' => Carbon::now()->subDays($this->faker->numberBetween(5, 15)),
                    'submitted_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 4)),
                    'ip_address' => $this->faker->ipv4(),
                    'user_agent' => $this->faker->userAgent(),
                ]);
            }
        });
    }

    private function seedShelterWorkers(
        Collection $maids,
        Collection $contracts,
        Collection $nationalities
    ): void {
        if (! Schema::hasTable('shelter_workers')) {
            return;
        }

        collect(range(1, 15))->each(function () use ($maids, $contracts, $nationalities) {
            $contract = $contracts->isNotEmpty() ? $contracts->random() : null;
            $nationality = $nationalities->isNotEmpty() ? $nationalities->random() : null;
            $receivedBy = $this->faker->randomElement(['company', 'client', 'embassy', 'other']);

            ShelterWorkers::create([
                'name' => $this->faker->company() . ' Shelter',
                'city' => $this->faker->randomElement(['Riyadh', 'Jeddah', 'Dammam', 'Makkah']),
                'worker_name' => $maids->isNotEmpty() ? $maids->random()->name : $this->faker->name(),
                'residence_num' => $this->faker->numerify('24########'),
                'passport_num' => strtoupper(Str::random(2)) . $this->faker->numerify('#######'),
                'nationality_id' => $nationality ? (string) $nationality->id : '0',
                'shelter_status' => $this->faker->randomElement(['good', 'absent', 'under_review']),
                'received_by' => $receivedBy,
                'received_by_other' => $receivedBy === 'other' ? 'متطوع' : null,
                'status' => $this->faker->randomElement(['rent', 'deportation', 'service_transfer', 'shelter']),
                'worker_type' => $this->faker->randomElement(['instant', 'rent', 'deportation']),
                'reserve_date' => $this->faker->boolean(40) ? Carbon::now()->addDays($this->faker->numberBetween(1, 20)) : null,
                'reserve_notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
                'travel_doc_notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
                'settlement_notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
                'graduation_notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
                'travel_notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
                'contract_id' => optional($contract)->id,
            ]);
        });
    }
}
