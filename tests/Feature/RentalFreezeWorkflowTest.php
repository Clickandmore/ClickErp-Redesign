<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Package;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RentalFreezeWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(string $status = 'active'): array
    {
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $maid = $this->makeMaid($nationality, $religion);
        $maid->refresh()->update(['status' => 'contracted', 'available' => false]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Rental freeze test customer', 'phone_code' => '+966',
            'phone' => '5'.random_int(10000000, 99999999), 'password' => bcrypt('test'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $package = Package::create(['nationality_id' => $nationality, 'days_count' => 30, 'ar' => ['title' => 'باقة اختبار التجميد']]);
        $contract = RentalContract::create([
            'maid_id' => $maid->id, 'user_id' => $userId, 'status' => $status,
            'contract_date' => today()->subDays(5), 'maid_delivered_at' => now()->subDays(5),
        ]);
        RentalContractInfo::create([
            'rental_contract_id' => $contract->id, 'package_id' => $package->id,
            'ingaz_from' => today()->subDays(5), 'ingaz_to' => today()->addDays(25),
            'contract_duration_days' => 30, 'monthly_cost' => 3000,
        ]);
        $shelter = $maid->contracts()->where('status', 'shelter')->firstOrFail();
        $shelter->info->update(['is_outside_shelter' => true, 'outside_shelter_reason' => 'rental_delivery']);
        return [$contract, $maid, $shelter, $package, $religion];
    }

    private function makeMaid(int $nationality, int $religion): Maid
    {
        $id = DB::table('maids')->insertGetId([
            'name' => 'Rental freeze test maid', 'passport_num' => 'FREEZE-'.uniqid(),
            'nationality_id' => $nationality, 'religion_id' => $religion,
            'cv_file' => '', 'height' => '', 'weight' => '', 'status' => 'available',
            'available' => true, 'maid_accommodation_type' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $maid = Maid::findOrFail($id);
        $method = new \ReflectionMethod(RentContractController::class, 'getRentalJobTypeId');
        $method->setAccessible(true);
        $jobTypeId = $method->invoke(app(RentContractController::class));
        if (!\App\Models\ingaz\JobType::find($jobTypeId)) {
            \App\Models\ingaz\JobType::create(['id' => $jobTypeId, 'ar' => ['title' => 'تأجير']]);
        }
        $maid->jobTypes()->attach($jobTypeId);
        $shelter = Contract::create(['maid_id' => $id, 'status' => 'shelter', 'type' => 'maid']);
        ContractInfo::create(['contract_id' => $shelter->id, 'wants_to_work' => 'yes', 'is_outside_shelter' => false]);
        return $maid;
    }

    private function freeze(RentalContract $contract, bool $release)
    {
        return app(RentContractController::class)->freeze(Request::create('/', 'POST', [
            'freeze_reason' => 'اختبار تجميد العقد', 'release_maid' => $release,
        ]), $contract->id);
    }

    private function resume(RentalContract $contract, ?int $maidId = null)
    {
        return app(RentContractController::class)->resume(Request::create('/', 'POST',
            $maidId ? ['maid_id' => $maidId] : []), $contract->id);
    }

    public function test_retained_freeze_and_resume_preserve_existing_behavior_and_dates(): void
    {
        [$contract, $maid, $shelter] = $this->fixture();
        $dates = $contract->info->only(['ingaz_from', 'ingaz_to']);
        $usage = $contract->calculateDayUsage();
        $this->assertEquals(200, $this->freeze($contract, false)->getStatusCode());
        $this->assertFalse($contract->fresh()->freeze_releases_maid);
        $this->assertFalse((bool) $maid->fresh()->available);
        $this->assertTrue((bool) $shelter->info->fresh()->is_outside_shelter);
        $this->assertTrue(RentalContract::whereKey($contract->id)->holdingMaid()->exists());
        $this->assertEquals(200, $this->resume($contract)->getStatusCode());
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertEquals($dates, $contract->info->fresh()->only(['ingaz_from', 'ingaz_to']));
        $this->assertEquals($usage, $contract->fresh()->calculateDayUsage());
    }

    public function test_release_returns_maid_to_shelter_and_requires_an_explicit_selection(): void
    {
        [$contract, $maid, $shelter] = $this->fixture();
        $response = $this->freeze($contract, true);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('frozen', $contract->fresh()->status);
        $this->assertTrue($contract->fresh()->requiresMaidOnResume());
        $this->assertTrue((bool) $maid->fresh()->available);
        $this->assertFalse((bool) $shelter->info->fresh()->is_outside_shelter);
        $this->assertFalse(RentalContract::whereKey($contract->id)->holdingMaid()->exists());
        $this->assertTrue(Maid::whereKey($maid->id)->willingToWorkInsideShelter()->exists());
        $this->assertEquals(422, $this->resume($contract)->getStatusCode());
        $method = new \ReflectionMethod(RentContractController::class, 'getAvailableRentalMaidsQuery');
        $method->setAccessible(true);
        $available = $method->invoke(app(RentContractController::class));
        $this->assertTrue($available->whereKey($maid->id)->exists(), json_encode($maid->fresh()->only(['status', 'available', 'rental_mode'])));
        $options = app(RentContractController::class)->resumeOptions($contract->id)->getData(true);
        $this->assertTrue($options['requires_maid']);
        $this->assertContains($maid->id, array_column($options['maids'], 'id'));
    }

    public function test_released_maid_can_be_rented_again_and_cannot_resume_on_old_contract(): void
    {
        [$contract, $maid] = $this->fixture();
        $this->assertEquals(200, $this->freeze($contract, true)->getStatusCode());
        $newContract = RentalContract::create(['maid_id' => $maid->id, 'user_id' => $contract->user_id, 'status' => 'contract_sent']);
        $maid->update(['status' => 'contracted', 'available' => false]);
        $this->assertEquals(422, $this->resume($contract, $maid->id)->getStatusCode());
        $this->assertSame('frozen', $contract->fresh()->status);
        $this->assertTrue(RentalContract::whereKey($newContract->id)->holdingMaid()->exists());
    }

    public function test_resume_accepts_available_matching_replacement_without_touching_old_maids_new_booking(): void
    {
        [$contract, $old, $shelter, $package, $religion] = $this->fixture();
        $replacement = $this->makeMaid($package->nationality_id, $religion);
        $this->assertEquals(200, $this->freeze($contract, true)->getStatusCode());
        RentalContract::create(['maid_id' => $old->id, 'user_id' => $contract->user_id, 'status' => 'contract_sent']);
        $old->refresh()->update(['status' => 'contracted', 'available' => false]);
        $before = $contract->info->getAttributes();
        $response = $this->resume($contract, $replacement->id);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals($replacement->id, $contract->fresh()->maid_id);
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertFalse((bool) $replacement->fresh()->available);
        $this->assertFalse((bool) $old->fresh()->available);
        $this->assertSame($before, $contract->info->fresh()->getAttributes());
        $this->assertTrue((bool) $replacement->contracts()->where('status', 'shelter')->first()->info->is_outside_shelter);
        $this->assertFalse((bool) $shelter->info->fresh()->is_outside_shelter);
    }

    public function test_wrong_nationality_and_stale_available_flag_are_rejected(): void
    {
        [$contract, $old, , $package, $religion] = $this->fixture();
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $wrong = $this->makeMaid($nationality, $religion);
        $busy = $this->makeMaid($package->nationality_id, $religion);
        $this->assertEquals(200, $this->freeze($contract, true)->getStatusCode());
        RentalContract::create(['maid_id' => $busy->id, 'user_id' => $contract->user_id, 'status' => 'active']);
        $this->assertEquals(422, $this->resume($contract, $wrong->id)->getStatusCode());
        $this->assertEquals(422, $this->resume($contract, $busy->id)->getStatusCode());
        $this->assertSame('frozen', $contract->fresh()->status);
    }

    public function test_release_is_atomic_when_worker_has_another_commitment(): void
    {
        [$contract, $maid, $shelter] = $this->fixture();
        RentalContract::create(['maid_id' => $maid->id, 'user_id' => $contract->user_id, 'status' => 'active']);
        $this->assertEquals(422, $this->freeze($contract, true)->getStatusCode());
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertFalse($contract->fresh()->freeze_releases_maid);
        $this->assertFalse((bool) $maid->fresh()->available);
        $this->assertTrue((bool) $shelter->info->fresh()->is_outside_shelter);
    }

    public function test_same_maid_can_be_selected_again_when_still_available(): void
    {
        [$contract, $maid] = $this->fixture('contract_signed');
        $this->assertEquals(200, $this->freeze($contract, true)->getStatusCode());
        $this->assertEquals(200, $this->resume($contract, $maid->id)->getStatusCode());
        $this->assertSame('contract_signed', $contract->fresh()->status);
        $this->assertFalse($contract->fresh()->freeze_releases_maid);
    }
}
