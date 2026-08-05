<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\RentalContract;
use App\Services\RentalShelterPresenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RentalShelterPresenceServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_terminal_rental_returns_maid_despite_a_stale_reference(): void
    {
        [$shelterContract, $rentalContract] = $this->makeStaleRentalShelterRecord('completed');

        $returned = app(RentalShelterPresenceService::class)->returnInside($rentalContract);

        $this->assertTrue($returned);
        $shelterContract->info->refresh();
        $this->assertFalse((bool) $shelterContract->info->is_outside_shelter);
        $this->assertNull($shelterContract->info->outside_shelter_reason);
        $this->assertNull($shelterContract->info->outside_shelter_reference_id);
        $this->assertDatabaseHas('contract_status_logs', [
            'contract_id' => $shelterContract->id,
            'status' => 'shelter',
        ]);
    }

    public function test_closing_a_rental_returns_the_maid_inside_and_willing_to_work(): void
    {
        [$shelterContract, $rentalContract] = $this->makeStaleRentalShelterRecord('completed', 'no');

        $returned = app(RentalShelterPresenceService::class)->returnInside($rentalContract);

        $this->assertTrue($returned);
        $shelterContract->info->refresh();
        $this->assertFalse((bool) $shelterContract->info->is_outside_shelter);
        $this->assertSame('yes', $shelterContract->info->wants_to_work);
    }

    public function test_another_outside_rental_prevents_returning_maid_to_shelter(): void
    {
        [$shelterContract, $rentalContract, $maidId, $userId] = $this->makeStaleRentalShelterRecord('completed');

        DB::table('rental_contracts')->insert([
            'user_id' => $userId,
            'maid_id' => $maidId,
            'status' => 'frozen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $returned = app(RentalShelterPresenceService::class)->returnInside($rentalContract);

        $this->assertFalse($returned);
        $this->assertTrue((bool) $shelterContract->info->fresh()->is_outside_shelter);
    }

    public function test_new_delivery_replaces_an_old_rental_reference(): void
    {
        [$shelterContract, $rentalContract] = $this->makeStaleRentalShelterRecord('maid_delivered');

        $updated = app(RentalShelterPresenceService::class)->markOutside($rentalContract);

        $this->assertTrue($updated);
        $shelterContract->info->refresh();
        $this->assertSame($rentalContract->id, $shelterContract->info->outside_shelter_reference_id);
    }

    private function makeStaleRentalShelterRecord(string $rentalStatus, string $wantsToWork = 'yes'): array
    {
        $suffix = str_replace('.', '', uniqid('', true));
        $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $religionId = DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $maidId = DB::table('maids')->insertGetId([
            'name' => 'Shelter sync test maid',
            'passport_num' => 'SYNC-' . $suffix,
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'maid_accommodation_type' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Shelter sync test client',
            'phone_code' => '+966',
            'phone' => '5' . substr($suffix, -8),
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contractId = DB::table('contracts')->insertGetId([
            'maid_id' => $maidId,
            'status' => 'shelter',
            'type' => 'maid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInfo::create([
            'contract_id' => $contractId,
            'is_outside_shelter' => true,
            'outside_shelter_reason' => 'rental_delivery',
            'outside_shelter_reference_type' => RentalContract::class,
            'outside_shelter_reference_id' => 999999,
            'wants_to_work' => $wantsToWork,
        ]);

        $rentalContractId = DB::table('rental_contracts')->insertGetId([
            'user_id' => $userId,
            'maid_id' => $maidId,
            'status' => $rentalStatus,
            'closure_type' => $rentalStatus === 'completed' ? 'available' : null,
            'maid_delivered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            Contract::with('info')->findOrFail($contractId),
            RentalContract::findOrFail($rentalContractId),
            $maidId,
            $userId,
        ];
    }
}
