<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Models\ingaz\User;
use App\Models\TravelHistory;
use PHPUnit\Framework\TestCase;

class CompanyTravelHistoryTest extends TestCase
{
    public function test_company_trip_resolves_its_client_without_a_user_id(): void
    {
        $party = new User(['name' => 'Company', 'phone' => '123']);
        $profile = $this->createMock(CompanyProfile::class);
        $profile->expects($this->once())->method('party')->willReturn($party);

        $trip = new TravelHistory(['user_id' => null, 'company_profile_id' => 5]);
        $trip->setRelation('companyProfile', $profile);

        $this->assertNull($trip->user_id);
        $this->assertEquals(5, $trip->company_profile_id);
        $this->assertSame($party, $trip->client);
    }

    public function test_individual_trip_keeps_its_customer(): void
    {
        $user = new User(['name' => 'Customer']);
        $trip = new TravelHistory(['user_id' => 12]);
        $trip->setRelation('user', $user);

        $this->assertSame($user, $trip->client);
    }

}
