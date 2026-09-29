<?php

namespace Tests\Feature\Branch;

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * قواعد إدخال بيانات العميل في شاشة إضافة عميل:
 * الجوال 9 أرقام تبدأ بـ 5، الهوية 10 أرقام، الاسم بلا رموز، والبريد بلا حروف عربية.
 */
class ClientCreateValidationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $admin = $this->branchAdmin($this->makeBranch());
        Permission::updateOrCreate(['name' => PermKeys::CLIENTS_CREATE, 'guard_name' => 'admin'], ['display_name' => PermKeys::CLIENTS_CREATE, 'group_name' => 'clients', 'section_name' => 'users_management', 'is_active' => true]);
        $admin->givePermissionTo(PermKeys::CLIENTS_CREATE);
        $this->actingAsAdmin($admin);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'عميل اختبار',
            'phone' => '5' . random_int(10000000, 99999999),
            'email' => 'client' . random_int(100000, 999999) . '@example.test',
            'national_num' => '1' . random_int(100000000, 999999999),
            'client_type' => 'person',
        ], $overrides);
    }

    /** @dataProvider invalidPhones */
    public function test_phone_must_be_nine_digits_starting_with_five(string $phone): void
    {
        $this->postJson(route('clients.store'), $this->payload(['phone' => $phone]))
            ->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function invalidPhones(): array
    {
        return [
            'letters' => ['5abc12345'], 'eight digits' => ['51234567'], 'ten digits' => ['5123456789'],
            'starts with 4' => ['412345678'], 'leading zero' => ['0512345678'], 'space' => ['51234 678'],
            'special char' => ['5123-5678'], 'plus prefix' => ['+966512345678'],
        ];
    }

    /** @dataProvider invalidNationalNums */
    public function test_national_num_must_be_ten_digits(string $nationalNum): void
    {
        $this->postJson(route('clients.store'), $this->payload(['national_num' => $nationalNum]))
            ->assertUnprocessable()->assertJsonValidationErrors(['national_num']);
    }

    public function invalidNationalNums(): array
    {
        return [
            'letters' => ['10234567ab'], 'eleven digits' => ['10234567891'], 'nine digits' => ['102345678'],
            'space' => ['10234 6789'], 'special char' => ['1023-56789'],
        ];
    }

    public function test_name_rejects_special_characters(): void
    {
        $this->postJson(route('clients.store'), $this->payload(['name' => 'أحمد@#']))
            ->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_email_rejects_arabic_letters(): void
    {
        $this->postJson(route('clients.store'), $this->payload(['email' => 'عميل@example.test']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    /** @dataProvider invalidOptionalFields */
    public function test_optional_fields_reject_invalid_values(array $input, string $field): void
    {
        $this->postJson(route('clients.store'), $this->payload($input))
            ->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    public function invalidOptionalFields(): array
    {
        return [
            'birthdate younger than min age' => [['birthdate' => now()->subYears(10)->toDateString()], 'birthdate'],
            'birthdate older than max age' => [['birthdate' => now()->subYears(120)->toDateString()], 'birthdate'],
            'saudi phone2 not starting with 5' => [['phone2' => '412345678'], 'phone2'],
            'saudi phone2 with letters' => [['phone2' => '5123abc78'], 'phone2'],
            'foreign phone2 too short' => [['phone2_code' => '+20', 'phone2' => '12345'], 'phone2'],
            'unknown phone2 code' => [['phone2_code' => '+999', 'phone2' => '1234567'], 'phone2_code'],
            'job with digits' => [['job' => 'مهندس 2'], 'job'],
            'job with special char' => [['job' => 'مهندس@'], 'job'],
            'english name with arabic' => [['full_name_en' => 'Ahmed محمد'], 'full_name_en'],
            'identity date as text' => [['identity_date' => '1445/05/01هـ'], 'identity_date'],
            'rooms above max' => [['marital_status' => 'married', 'rooms_num' => 21], 'rooms_num'],
            'family above max' => [['marital_status' => 'married', 'family_num' => 31], 'family_num'],
            'children above max' => [['marital_status' => 'married', 'children' => 21], 'children'],
            'name too long' => [['name' => str_repeat('ا', 151)], 'name'],
        ];
    }

    public function test_valid_optional_fields_pass_validation(): void
    {
        $response = $this->postJson(route('clients.store'), $this->payload([
            'birthdate' => now()->subYears(30)->toDateString(),
            'phone2_code' => '+20',
            'phone2' => '1001234567',
            'job' => 'مهندس برمجيات',
            'full_name_en' => 'Mohammed Ahmed',
            'identity_date' => '2020-05-01',
            'marital_status' => 'married',
            'rooms_num' => 4,
            'family_num' => 6,
            'children' => 3,
        ]));

        $this->assertEmpty(array_intersect(
            array_keys($response->json('errors') ?? []),
            ['birthdate', 'phone2', 'phone2_code', 'job', 'full_name_en', 'identity_date', 'rooms_num', 'family_num', 'children']
        ));
    }

    private function companyPayload(array $overrides = []): array
    {
        return $this->payload(array_merge([
            'client_type' => 'company',
            'name' => 'شركة اختبار',
            'national_num' => null,
            'tax_number' => '3' . random_int(10000000, 99999999) . random_int(100000, 999999),
            'license_number' => (string) random_int(1000000000, 9999999999),
            'license_expiry_date' => now()->addYear()->toDateString(),
        ], $overrides));
    }

    public function test_company_requires_valid_license_expiry_date(): void
    {
        $this->postJson(route('clients.store'), $this->companyPayload(['license_expiry_date' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors(['license_expiry_date']);

        $this->postJson(route('clients.store'), $this->companyPayload(['license_expiry_date' => '1445/01/01']))
            ->assertUnprocessable()->assertJsonValidationErrors(['license_expiry_date']);
    }

    public function test_expired_license_date_is_accepted_so_the_badge_can_show(): void
    {
        $response = $this->postJson(route('clients.store'), $this->companyPayload([
            'license_expiry_date' => now()->subDay()->toDateString(),
        ]));

        $this->assertArrayNotHasKey('license_expiry_date', $response->json('errors') ?? []);
    }

    public function test_license_is_expired_only_for_companies_with_past_date(): void
    {
        $make = fn (string $type, $date) => new \App\Models\ingaz\User(['client_type' => $type, 'license_expiry_date' => $date]);

        $this->assertTrue($make('company', now()->subDay()->toDateString())->isLicenseExpired());
        $this->assertFalse($make('company', today()->toDateString())->isLicenseExpired());
        $this->assertFalse($make('company', now()->addDay()->toDateString())->isLicenseExpired());
        $this->assertFalse($make('company', null)->isLicenseExpired());
        $this->assertFalse($make('person', now()->subYear()->toDateString())->isLicenseExpired());
    }

    public function test_client_page_shows_expired_license_badge(): void
    {
        $client = \App\Models\ingaz\User::create([
            'name' => 'Expired company', 'phone' => '5' . random_int(10000000, 99999999), 'phone_code' => '+966',
            'password' => bcrypt('test'), 'client_type' => 'company', 'license_number' => '1010123456',
            'license_expiry_date' => now()->subMonth()->toDateString(),
        ]);
        Permission::updateOrCreate(['name' => PermKeys::CLIENTS_SHOW, 'guard_name' => 'admin'], ['display_name' => PermKeys::CLIENTS_SHOW, 'group_name' => 'clients', 'section_name' => 'users_management', 'is_active' => true]);
        auth('admin')->user()->givePermissionTo(PermKeys::CLIENTS_SHOW);

        $this->get(route('clients.show', $client->id))
            ->assertOk()
            ->assertSee(helperTrans('ingaz.license_expired'));
    }

    /** @dataProvider invalidCompanyNumbers */
    public function test_company_tax_and_license_numbers_are_validated(array $input, string $field): void
    {
        $this->postJson(route('clients.store'), $this->companyPayload($input))
            ->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    public function invalidCompanyNumbers(): array
    {
        return [
            'tax with letters' => [['tax_number' => '3000000000000ab'], 'tax_number'],
            'tax 14 digits' => [['tax_number' => '30000000000001'], 'tax_number'],
            'tax 16 digits' => [['tax_number' => '3000000000000011'], 'tax_number'],
            'tax not starting with 3' => [['tax_number' => '200000000000003'], 'tax_number'],
            'tax with space' => [['tax_number' => '3000000 0000003'], 'tax_number'],
            'tax negative' => [['tax_number' => '-30000000000003'], 'tax_number'],
            'license with letters' => [['license_number' => '10100abc12'], 'license_number'],
            'license 9 digits' => [['license_number' => '101001234'], 'license_number'],
            'license 11 digits' => [['license_number' => '10100123456'], 'license_number'],
            'license with symbol' => [['license_number' => '1010-12345'], 'license_number'],
        ];
    }

    public function test_tax_number_cannot_repeat_for_another_client(): void
    {
        $taxNumber = '3' . random_int(10000000, 99999999) . random_int(100000, 999999);
        \App\Models\ingaz\User::create(['name' => 'Existing company', 'phone' => '5' . random_int(10000000, 99999999), 'phone_code' => '+966', 'password' => bcrypt('test'), 'client_type' => 'company', 'tax_number' => $taxNumber]);

        $this->postJson(route('clients.store'), $this->companyPayload(['tax_number' => $taxNumber]))
            ->assertUnprocessable()->assertJsonValidationErrors(['tax_number']);
    }

    public function test_valid_company_numbers_pass_validation(): void
    {
        $response = $this->postJson(route('clients.store'), $this->companyPayload());

        $this->assertEmpty(array_intersect(array_keys($response->json('errors') ?? []), ['tax_number', 'license_number']));
    }

    public function test_clients_pdf_export_does_not_auto_print(): void
    {
        Permission::updateOrCreate(['name' => PermKeys::CLIENTS_EXPORT_PDF, 'guard_name' => 'admin'], ['display_name' => PermKeys::CLIENTS_EXPORT_PDF, 'group_name' => 'clients', 'section_name' => 'users_management', 'is_active' => true]);
        auth('admin')->user()->givePermissionTo(PermKeys::CLIENTS_EXPORT_PDF);

        $content = $this->get(route('clients.export.pdf'))->assertOk()->getContent();

        $this->assertStringNotContainsString('window.onload', $content);
        $this->assertStringContainsString('printDocument()', $content);
    }

    public function test_create_page_renders_new_field_hints(): void
    {
        $this->get(route('clients.create'))
            ->assertOk()
            ->assertSee('phone2_code', false)
            ->assertSee('family-field', false)
            ->assertSee('+9665XXXXXXXX', false);
    }

    public function test_valid_input_passes_field_validation_including_arabic_digits(): void
    {
        $response = $this->postJson(route('clients.store'), $this->payload([
            'name' => 'Ahmed محمد',
            'phone' => '٥' . random_int(10000000, 99999999),
        ]));

        $this->assertEmpty(array_intersect(
            array_keys($response->json('errors') ?? []),
            ['name', 'phone', 'email', 'national_num']
        ));
    }
}
