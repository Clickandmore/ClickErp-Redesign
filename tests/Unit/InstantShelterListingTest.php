<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Support\CvOptionsSettings;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class InstantShelterListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // إعداد "إتاحة سيرة عاملة الإيجار لنقل الخدمات" مُعطّل افتراضيًا.
        CvOptionsSettings::fakeRentalTransferEnabled(false);
    }

    protected function tearDown(): void
    {
        CvOptionsSettings::clearCache();

        parent::tearDown();
    }

    public function test_instant_shelter_listing_is_exposed_as_transfer_only_on_site(): void
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 1, 'title' => 'استقدام']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', true),
        ]));

        $this->assertTrue($maid->isInstantShelterListing());
        $this->assertSame([3], $maid->site_listing_job_types->pluck('id')->all());
        $this->assertSame(3, $maid->effective_job_type?->id);
    }

    public function test_instant_shelter_listing_uses_transfer_price(): void
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 1, 'title' => 'استقدام']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', true),
        ]));
        $maid->setRelation('nationality', new Nationality([
            'price' => 12000,
            'rental_cost' => 6000,
            'service_transportation_cost' => 8500,
        ]));

        $this->assertSame(8500.0, $maid->resolved_price);
    }

    public function test_instant_shelter_listing_falls_back_to_transfer_type_when_original_type_is_not_synced(): void
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 1, 'title' => 'استقدام']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', true),
        ]));

        $this->assertSame([3], $maid->site_listing_job_types->pluck('id')->all());
        $this->assertSame(3, $maid->effective_job_type?->id);
    }

    public function test_available_rental_worker_inside_shelter_is_listed_for_rental_only_while_the_setting_is_disabled(): void
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 2, 'title' => 'تأجير']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', true),
        ]));
        $maid->setRelation('nationality', new Nationality([
            'rental_cost' => 6000,
            'service_transportation_cost' => 8500,
        ]));

        $this->assertTrue($maid->isInstantShelterListing());
        $this->assertSame([2], $maid->site_listing_job_types->pluck('id')->all());
        $this->assertSame(2, $maid->effective_job_type?->id);
        $this->assertSame(6000.0, $maid->resolved_price);
    }

    public function test_rental_worker_is_also_listed_for_transfer_when_the_setting_is_enabled(): void
    {
        CvOptionsSettings::fakeRentalTransferEnabled(true);

        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 2, 'title' => 'تأجير']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', true),
        ]));
        $maid->setRelation('nationality', new Nationality([
            'rental_cost' => 6000,
            'service_transportation_cost' => 8500,
        ]));

        $this->assertSame([2, 3], $maid->site_listing_job_types->pluck('id')->all());
        // الإيجار يبقى الخدمة الأساسية، لذا يبقى سعر الإيجار هو المعروض.
        $this->assertSame(2, $maid->effective_job_type?->id);
        $this->assertSame(6000.0, $maid->resolved_price);
    }

    public function test_rental_worker_without_instant_availability_stays_rental_only_when_the_setting_is_enabled(): void
    {
        CvOptionsSettings::fakeRentalTransferEnabled(true);

        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 2, 'title' => 'تأجير']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));
        $maid->setRelation('contracts', new EloquentCollection([
            $this->makeShelterContract('yes', false),
        ]));

        $this->assertFalse($maid->isInstantShelterListing());
        $this->assertSame([2], $maid->site_listing_job_types->pluck('id')->all());
    }

    private function makeShelterContract(string $wantsToWork, bool $isAvailableForInstant): Contract
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Shelter->value,
        ]);

        $contract->setRelation('info', new ContractInfo([
            'wants_to_work' => $wantsToWork,
            'is_available_for_instant' => $isAvailableForInstant,
            'is_outside_shelter' => false,
        ]));

        return $contract;
    }
}
