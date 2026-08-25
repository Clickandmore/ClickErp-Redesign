<?php

namespace Tests\Feature;

use App\Support\SystemModuleManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemProductProfileTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function recruitment_remains_the_safe_default_profile(): void
    {
        DB::table('dev_superadmin_settings')->where('key', 'product_profile')->delete();
        Cache::flush();

        $this->assertSame('recruitment', SystemModuleManager::productProfileKey());
        $this->assertTrue(SystemModuleManager::isAvailableForProfile('operations'));
        $this->assertTrue(SystemModuleManager::isRequiredForProfile('operations'));
        $this->assertFalse(SystemModuleManager::isAvailableForProfile('general_operations'));
    }

    /** @test */
    public function general_profile_hides_recruitment_operations_without_losing_the_saved_switch(): void
    {
        DB::table('system_module_settings')->updateOrInsert(
            ['key' => 'operations'],
            ['name' => 'operations', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        SystemModuleManager::updateProductProfile('general');
        SystemModuleManager::update(['crm']);

        $this->assertTrue(SystemModuleManager::configuredMap()['operations']);
        $this->assertFalse(SystemModuleManager::isEnabled('operations'));
        $this->assertSame(
            'operations',
            SystemModuleManager::disabledModuleForRoute('orders.index', 'admin/orders')['key'] ?? null
        );
        $this->assertSame(
            'operations',
            SystemModuleManager::disabledModuleForRoute('dashboard.index', 'dashboard')['key'] ?? null
        );

        SystemModuleManager::updateProductProfile('recruitment');

        $this->assertTrue(SystemModuleManager::isEnabled('operations'));
        $this->assertNull(SystemModuleManager::disabledModuleForRoute('orders.index', 'admin/orders'));
    }

    /** @test */
    public function general_profile_keeps_its_required_finance_module_enabled(): void
    {
        DB::table('system_module_settings')->updateOrInsert(
            ['key' => 'finance'],
            ['name' => 'finance', 'is_enabled' => false, 'created_at' => now(), 'updated_at' => now()]
        );

        SystemModuleManager::updateProductProfile('general');

        $this->assertTrue(SystemModuleManager::isEnabled('finance'));
        $this->assertTrue(SystemModuleManager::isEnabled('general_operations'));
        $this->assertTrue(SystemModuleManager::isRequiredForProfile('general_operations'));
        $this->assertSame('finance', SystemModuleManager::defaultDashboardForProfile());
    }

    /** @test */
    public function changing_the_product_profile_requires_explicit_confirmation_and_password(): void
    {
        SystemModuleManager::updateProductProfile('recruitment');

        $response = $this
            ->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.modules.update'), [
                'current_product_profile' => 'recruitment',
                'product_profile' => 'general',
                'default_dashboard' => 'recruitment',
                'modules' => ['operations' => '1'],
            ]);

        $response
            ->assertSessionHasErrors(['product_profile_confirmation', 'product_profile_change_reason', 'current_password']);
        $this->assertSame('recruitment', SystemModuleManager::productProfileKey());
    }
}
