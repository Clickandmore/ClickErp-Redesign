<?php

namespace Tests\Feature\Finance;

use App\Models\Permission;
use App\Models\auth\Admin;
use App\Support\ShelterAccountingSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Tests\TestCase;

/**
 * قسم «حسابات الإيواء والترحيل» في صفحة إعدادات الحسابات (/finance/confirm-settings):
 * يُعرض للمدير المخوّل، وتُحفظ مفاتيحه في fn_confirm_settings ويقرأها App\Support\ShelterAccountingSettings.
 */
class ShelterAccountingSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();

        // package.route.permission:finance يقبل finance_module لكل مسارات الحزمة (عرض + حفظ).
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => 'finance_module', 'guard_name' => 'admin'],
            [
                'display_name' => 'finance_module',
                'group_name' => 'finance',
                'section_name' => 'finance',
                'is_active' => true,
            ]
        );
        $this->admin->givePermissionTo('finance_module');

        $this->actingAs($this->admin, 'admin');
    }

    public function test_settings_page_renders_shelter_and_deportation_section(): void
    {
        $response = $this->get(route('finance.confirm-settings.index'));

        $response->assertOk();
        $response->assertSee('حسابات الإيواء والترحيل');
        $response->assertSee('id="shelter-deportation-accounts-section"', false);

        foreach (ShelterAccountingSettings::confirmSettingTitles() as $key) {
            $response->assertSee('name="data[' . $key . '][value]"', false);
        }
    }

    public function test_settings_page_preselects_saved_account_and_toggle(): void
    {
        $account = $this->createAccount('خسائر إيواء', 'debtor');

        DB::table('fn_confirm_settings')->insert([
            [
                'title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT,
                'value' => (string) $account->id,
                'created_by_id' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE,
                'value' => 'off',
                'created_by_id' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get(route('finance.confirm-settings.index'));

        $response->assertOk();
        $response->assertSee('<option value="' . $account->id . '" selected>', false);
        $response->assertSee('id="shelterLivingExpenseCapitalizeOff" name="data[shelter_living_expense_capitalize][value]" value="off" checked', false);
    }

    public function test_store_persists_toggles_and_account(): void
    {
        $account = $this->createAccount('مصروف ترحيل', 'debtor');

        $this->assertTrue(ShelterAccountingSettings::livingExpenseCapitalized());
        $this->assertTrue(ShelterAccountingSettings::deportationChargesExternalOffice());
        $this->assertNull(ShelterAccountingSettings::deportationLossAccountId());

        $response = $this->postJson(route('finance.confirm-settings.store'), [
            'data' => [
                ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE => [
                    'title' => ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE,
                    'value' => 'off',
                ],
                ShelterAccountingSettings::DEPORTATION_CHARGE_EXTERNAL_OFFICE => [
                    'title' => ShelterAccountingSettings::DEPORTATION_CHARGE_EXTERNAL_OFFICE,
                    'value' => 'off',
                ],
                ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT => [
                    'title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT,
                    'value' => $account->id,
                ],
            ],
        ]);

        $response->assertSuccessful()->assertJsonPath('status', true);

        $this->assertDatabaseHas('fn_confirm_settings', [
            'title' => ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE,
            'value' => 'off',
        ]);
        $this->assertDatabaseHas('fn_confirm_settings', [
            'title' => ShelterAccountingSettings::DEPORTATION_CHARGE_EXTERNAL_OFFICE,
            'value' => 'off',
        ]);
        $this->assertDatabaseHas('fn_confirm_settings', [
            'title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT,
            'value' => (string) $account->id,
        ]);

        $this->assertFalse(ShelterAccountingSettings::livingExpenseCapitalized());
        $this->assertFalse(ShelterAccountingSettings::deportationChargesExternalOffice());
        $this->assertSame($account->id, ShelterAccountingSettings::deportationLossAccountId());
    }

    public function test_store_rejects_invalid_toggle_and_unknown_account(): void
    {
        $this->postJson(route('finance.confirm-settings.store'), [
            'data' => [
                ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE => [
                    'title' => ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE,
                    'value' => 'maybe',
                ],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['data.shelter_living_expense_capitalize.value']);

        $missingAccountId = ((int) DB::table('fn_accounts')->max('id')) + 1000;

        $this->postJson(route('finance.confirm-settings.store'), [
            'data' => [
                ShelterAccountingSettings::WRITE_OFF_ACCOUNT => [
                    'title' => ShelterAccountingSettings::WRITE_OFF_ACCOUNT,
                    'value' => $missingAccountId,
                ],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['data.shelter_write_off_account.value']);

        $this->assertDatabaseMissing('fn_confirm_settings', [
            'title' => ShelterAccountingSettings::WRITE_OFF_ACCOUNT,
        ]);
    }

    private function createAccount(string $name, string $status): Account
    {
        $code = ((int) DB::table('fn_accounts')->max('code')) + random_int(100, 999);

        return Account::query()->create([
            'name' => $name,
            'code' => $code,
            'parent_id' => null,
            'level' => 1,
            'operation_type' => 1,
            'status' => $status,
            'statement_type' => 'income_statement',
            'type' => 1,
            'created_by_id' => $this->admin->id,
        ]);
    }
}
