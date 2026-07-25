<?php

namespace Tests\Unit;

use App\Models\ingaz\User;
use PHPUnit\Framework\TestCase;

class ClientNumberTest extends TestCase
{
    public function test_it_uses_the_imported_client_number_when_available(): void
    {
        $client = new User([
            'id' => 91,
            'arabia_client_number' => 1204,
        ]);

        $this->assertSame(1204, $client->clientNumber());
    }

    public function test_it_falls_back_to_the_internal_client_id(): void
    {
        $client = new User([
            'id' => 91,
            'arabia_client_number' => null,
        ]);

        $this->assertSame(91, $client->clientNumber());
    }

    public function test_it_displays_the_source_phone_instead_of_an_import_placeholder(): void
    {
        $client = new User([
            'phone_code' => '+966',
            'phone' => 'arabia-client-289',
            'source_phone' => '0503815788',
        ]);

        $this->assertSame('0503815788', $client->primaryPhoneValue());
    }

    public function test_it_hides_an_import_placeholder_when_there_is_no_source_phone(): void
    {
        $client = new User([
            'phone_code' => '+966',
            'phone' => 'arabia-client-289',
        ]);

        $this->assertSame('', $client->primaryPhoneValue());
    }

    public function test_it_displays_a_regular_operational_phone_with_its_country_code(): void
    {
        $client = new User([
            'phone_code' => '+966',
            'phone' => '503815788',
        ]);

        $this->assertSame('+966503815788', $client->primaryPhoneValue());
    }
}
