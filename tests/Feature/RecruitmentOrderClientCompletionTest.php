<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\NewPermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecruitmentOrderClientCompletionTest extends TestCase
{
    use DatabaseTransactions;

    private function client(array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Website client', 'phone' => '5' . random_int(10000000, 99999999),
            'phone_code' => '+966', 'password' => bcrypt('test'), 'client_type' => 'person',
        ]);
    }

    private function submit(User $client, array $fields = [])
    {
        $admin = Admin::factory()->create();
        foreach ([NewPermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::ORDERS_ADD_CONTRACT] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
            $admin->givePermissionTo($permission);
        }
        $this->actingAs($admin, 'admin');
        $order = Order::create([
            'user_id' => $client->id, 'job_type_id' => 1, 'status' => 'under_process',
            'order_code' => Order::generateUniqueCode(),
        ]);

        return $this->postJson(route('RecruitmentContract.store'), $fields + [
            'order_id' => $order->id, 'client_type' => 'ex_client',
            'contract_date' => '2026-09-22', 'msaned_number' => 'completion-' . $order->id,
            'visa_number' => 'completion-' . $order->id, 'contract_duration' => 2,
            'marketer_id' => $admin->id, 'maid_selection_mode' => 'postponed',
            'maid_selection_postponed' => 1,
        ]);
    }

    public function test_order_form_completion_is_persisted_on_the_contract_client(): void
    {
        $client = $this->client();
        $response = $this->submit($client, [
            'client_national_num' => '1234567890', 'client_birthdate' => '1990-05-20',
            'client_email' => 'completion@example.test', 'phone2' => '0551234567',
            'address' => 'Test address', 'city' => 'الرياض', 'beds_num' => '3',
            'family_num' => '4', 'children' => '0', 'job' => 'Teacher',
        ]);
        $response->assertOk()->assertJsonPath('success', true);
        $saved = Contract::findOrFail($response->json('contract_id'))->user;
        $this->assertSame($client->id, $saved->id);
        foreach ([
            'national_num' => '1234567890', 'birthdate' => '1990-05-20',
            'email' => 'completion@example.test', 'phone2' => '0551234567',
            'address' => 'Test address', 'city' => 'الرياض', 'rooms_num' => '3',
            'family_num' => '4', 'children' => '0', 'job' => 'Teacher',
        ] as $field => $value) {
            $this->assertSame($value, (string) $saved->$field, $field);
        }
    }

    public function test_existing_values_survive_while_prefixed_missing_fields_are_saved(): void
    {
        $client = $this->client(['national_num' => '1234567891', 'address' => 'Existing address', 'children' => 0]);
        $this->submit($client, [
            'client_name' => 'Different name', 'client_national_num' => '9999999999',
            'client_address' => '', 'client_children' => '3', 'client_city' => 'جدة',
        ])->assertOk()->assertJsonPath('success', true);
        $client->refresh();
        $this->assertSame('Website client', $client->name);
        $this->assertSame('1234567891', $client->national_num);
        $this->assertSame('Existing address', $client->address);
        $this->assertSame('0', (string) $client->children);
        $this->assertSame('جدة', $client->city);
    }

    public function test_duplicate_identity_rejects_creation_without_partial_client_changes(): void
    {
        $this->client(['national_num' => '1234567892']);
        $client = $this->client();
        $this->submit($client, ['client_national_num' => '1234567892', 'client_address' => 'Unsaved'])
            ->assertStatus(422)->assertJsonValidationErrors('client_national_num');
        $this->assertNull($client->fresh()->address);
        $this->assertFalse(Contract::where('user_id', $client->id)->exists());
    }

    public function test_company_completion_uses_tax_and_license_fields(): void
    {
        $client = $this->client(['client_type' => 'company']);
        $this->submit($client, [
            'account_client_type' => 'company', 'client_national_num' => '300000000000003',
            'client_license_number' => '1012345678', 'client_address' => 'Company address',
            'client_birthdate' => '1990-05-20', 'client_children' => '2',
        ])->assertOk()->assertJsonPath('success', true);
        $client->refresh();
        $this->assertSame('300000000000003', $client->tax_number);
        $this->assertSame('1012345678', $client->license_number);
        $this->assertSame('Company address', $client->address);
        $this->assertNull($client->national_num);
        $this->assertNull($client->birthdate);
    }

    public function test_existing_client_delivery_city_is_saved_without_order_and_used_as_supplier_arrival_station(): void
    {
        foreach ([null, 'الرياض'] as $oldCity) {
            $client = $this->client(['city' => $oldCity, 'national_num' => (string) random_int(1000000000, 1999999999)]);
            $this->submit($client, [
                'order_id' => null, 'client_type' => 'new_client', 'client_id' => $client->id, 'new_existing_client_id' => $client->id,
                'client_name' => $client->name, 'client_phone' => $client->phone,
                'new_client_national_num' => $client->national_num, 'client_nationality_id' => 1,
                'new_client_city' => 'الدمام',
            ])->assertOk()->assertJsonPath('success', true);
            $this->assertSame('الدمام', $client->fresh()->city);
            $contract = Contract::where('user_id', $client->id)->firstOrFail();
            $this->assertSame('الدمام', $contract->resolveExternalOfficeDispatchArrivalStation());
        }
    }

    public function test_order_contract_delivery_city_replaces_existing_city(): void
    {
        $client = $this->client(['city' => 'الرياض']);
        $this->submit($client, ['city' => 'الدمام'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('الدمام', $client->fresh()->city);
        $this->assertSame('الدمام', Contract::where('user_id', $client->id)->firstOrFail()->resolveExternalOfficeDispatchArrivalStation());
    }

    public function test_empty_delivery_city_does_not_clear_existing_city(): void
    {
        $client = $this->client(['city' => 'الرياض']);
        $this->submit($client, ['client_city' => ''])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('الرياض', $client->fresh()->city);
    }
}
