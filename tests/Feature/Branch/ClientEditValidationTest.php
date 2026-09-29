<?php

namespace Tests\Feature\Branch;

use App\Models\ingaz\User;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * شاشة تعديل العميل تطبق نفس قواعد شاشة الإضافة (قواعد مشتركة في UserController::clientFormRules).
 */
class ClientEditValidationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $admin = $this->branchAdmin($this->makeBranch());
        foreach ([PermKeys::CLIENTS_EDIT] as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'admin'], ['display_name' => $name, 'group_name' => 'clients', 'section_name' => 'users_management', 'is_active' => true]);
            $admin->givePermissionTo($name);
        }
        $this->actingAsAdmin($admin);
    }

    private function client(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'عميل تعديل',
            'phone' => '5' . random_int(10000000, 99999999),
            'phone_code' => '+966',
            'password' => bcrypt('test'),
            'client_type' => 'person',
            'national_num' => '1' . random_int(100000000, 999999999),
        ], $attributes));
    }

    private function payload(User $client, array $overrides = []): array
    {
        return array_merge([
            'name' => $client->name,
            'phone' => $client->phone,
            'client_type' => $client->client_type,
            'national_num' => $client->national_num,
        ], $overrides);
    }

    /** @dataProvider invalidEdits */
    public function test_update_applies_the_same_rules_as_create(array $input, string $field): void
    {
        $client = $this->client();

        $this->putJson(route('clients.update', $client->id), $this->payload($client, $input))
            ->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    public function invalidEdits(): array
    {
        return [
            'name with symbols' => [['name' => 'أحمد@#'], 'name'],
            'phone not starting with 5' => [['phone' => '412345678'], 'phone'],
            'phone with letters' => [['phone' => '5abc12345'], 'phone'],
            'email with arabic' => [['email' => 'عميل@example.test'], 'email'],
            'national id 11 digits' => [['national_num' => '10234567891'], 'national_num'],
            'birthdate too young' => [['birthdate' => now()->subYears(10)->toDateString()], 'birthdate'],
            'saudi phone2 invalid' => [['phone2' => '412345678'], 'phone2'],
            'job with digits' => [['job' => 'مهندس 2'], 'job'],
            'english name with arabic' => [['full_name_en' => 'Ahmed محمد'], 'full_name_en'],
            'identity date as text' => [['identity_date' => '1445/05/01هـ'], 'identity_date'],
            'children above max' => [['marital_status' => 'married', 'children' => 21], 'children'],
            'company tax not starting with 3' => [['client_type' => 'company', 'national_num' => null, 'tax_number' => '200000000000003', 'license_number' => '1010123456', 'license_expiry_date' => '2030-01-01'], 'tax_number'],
            'company license 9 digits' => [['client_type' => 'company', 'national_num' => null, 'tax_number' => '300000000000003', 'license_number' => '101012345', 'license_expiry_date' => '2030-01-01'], 'license_number'],
            'company without license expiry' => [['client_type' => 'company', 'national_num' => null, 'tax_number' => '300000000000003', 'license_number' => '1010123456'], 'license_expiry_date'],
        ];
    }

    public function test_update_saves_valid_changes_and_applies_adjustments(): void
    {
        $client = $this->client(['rooms_num' => 3, 'family_num' => 4, 'children' => 2]);

        $this->putJson(route('clients.update', $client->id), $this->payload($client, [
            'marital_status' => 'single',
            'rooms_num' => 3,
            'phone2_code' => '+20',
            'phone2' => '1001234567',
            'job' => 'محاسب',
            'identity_date' => '2021-03-15',
        ]))->assertSuccessful();

        $client->refresh();
        $this->assertSame('+201001234567', $client->phone2);
        $this->assertSame('محاسب', $client->job);
        $this->assertSame('2021-03-15', $client->identity_date);
        // أعزب: حقول الأسرة تُفرَّغ
        $this->assertNull($client->rooms_num);
        $this->assertNull($client->family_num);
        $this->assertNull($client->children);
    }

    public function test_company_update_stores_license_expiry_and_clears_residence_expiry(): void
    {
        $client = $this->client(['residence_expiry_date' => '2030-01-01']);
        $taxNumber = '3' . random_int(10000000, 99999999) . random_int(100000, 999999);

        $this->putJson(route('clients.update', $client->id), $this->payload($client, [
            'client_type' => 'company',
            'national_num' => null,
            'tax_number' => $taxNumber,
            'license_number' => '1010123456',
            'license_expiry_date' => now()->subDay()->toDateString(),
            'residence_expiry_date' => '2030-01-01',
        ]))->assertSuccessful();

        $client->refresh();
        $this->assertSame($taxNumber, $client->tax_number);
        $this->assertNull($client->residence_expiry_date);
        $this->assertTrue($client->isLicenseExpired());
    }

    public function test_client_keeps_own_national_num_and_tax_number_on_update(): void
    {
        $client = $this->client();

        $this->putJson(route('clients.update', $client->id), $this->payload($client))->assertSuccessful();
    }

    public function test_edit_page_prefills_shared_form_and_flags_legacy_values(): void
    {
        $client = $this->client([
            'phone2' => '+201001234567',
            'identity_date' => '1445/05/01',
        ]);

        $this->get(route('clients.edit', $client->id))
            ->assertOk()
            ->assertSee('value="1001234567"', false)
            ->assertSee('<option value="+20" selected>', false)
            ->assertSee('1445/05/01')
            ->assertSee('legacyDataAlert', false)
            ->assertSee('birthdate_hijri', false)
            ->assertSee('family-field', false);
    }
}
