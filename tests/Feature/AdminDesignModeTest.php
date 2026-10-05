<?php

namespace Tests\Feature;

use App\Support\SystemModuleManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDesignModeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('dev_superadmin_settings')->where('key', 'admin_design')->delete();
        Cache::flush();
    }

    /** @test */
    public function classic_design_is_the_safe_default(): void
    {
        $this->assertSame('classic', SystemModuleManager::adminDesign());
        $this->assertFalse(SystemModuleManager::usesRedesign());
    }

    /** @test */
    public function design_can_be_switched_without_changing_other_system_settings(): void
    {
        $dashboard = SystemModuleManager::defaultDashboardKey();

        $this->assertTrue(SystemModuleManager::updateAdminDesign('redesign'));
        $this->assertSame('redesign', SystemModuleManager::adminDesign());
        $this->assertTrue(SystemModuleManager::usesRedesign());
        $this->assertSame($dashboard, SystemModuleManager::defaultDashboardKey());

        $this->assertTrue(SystemModuleManager::updateAdminDesign('classic'));
        $this->assertSame('classic', SystemModuleManager::adminDesign());
    }

    /** @test */
    public function invalid_design_values_are_rejected(): void
    {
        $this->assertFalse(SystemModuleManager::updateAdminDesign('unknown'));
        $this->assertSame('classic', SystemModuleManager::adminDesign());
    }

    /** @test */
    public function artisan_command_reports_and_switches_the_design(): void
    {
        $this->artisan('admin:design')
            ->expectsOutput('Active admin design: classic')
            ->assertExitCode(0);

        $this->artisan('admin:design', ['design' => 'redesign'])
            ->expectsOutput('Admin design switched to: redesign')
            ->assertExitCode(0);

        $this->assertTrue(SystemModuleManager::usesRedesign());
    }

    /** @test */
    public function super_admin_option_switches_the_design(): void
    {
        $profile = SystemModuleManager::productProfileKey();

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.modules.update'), [
                'current_product_profile' => $profile,
                'product_profile' => $profile,
                'default_dashboard' => SystemModuleManager::defaultDashboardKey(),
                'admin_design' => 'redesign',
                'modules' => array_fill_keys(SystemModuleManager::requiredModules($profile), '1'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dev-superadmin.modules'));

        $this->assertSame('redesign', SystemModuleManager::adminDesign());
    }

    /** @test */
    public function login_uses_the_new_layout_only_when_redesign_is_active(): void
    {
        $this->get(route('admin.form.login'))
            ->assertOk()
            ->assertDontSee('clickerp-login.css', false)
            ->assertDontSee('auth-redesign-visual', false);

        SystemModuleManager::updateAdminDesign('redesign');

        $this->get(route('admin.form.login'))
            ->assertOk()
            ->assertSee('clickerp-login.css', false)
            ->assertSee('clickerp-login-redesign', false)
            ->assertSee('auth-redesign-visual', false)
            ->assertSee('دخول موظفي الإدارة');
    }

    /** @test */
    public function every_layout_using_the_admin_chrome_loads_the_redesign_assets(): void
    {
        $layoutDir = resource_path('views');
        $layouts = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($layoutDir));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $body = file_get_contents($file->getPathname());
            // A layout that renders the shared admin sidebar/header also renders the
            // redesign-only markup inside them, so it must ship the matching assets.
            $rendersChrome = str_contains($body, 'admin.layout.inc.sidebar')
                || str_contains($body, 'admin.layout.inc.header');

            // Only full layouts (the files that own the <body> tag) ship assets;
            // partials that merely reference the includes are skipped.
            if ($rendersChrome && str_contains($body, '<body')) {
                $layouts[] = $file->getPathname();
            }
        }

        $this->assertNotEmpty($layouts, 'No admin chrome layouts were discovered.');

        foreach ($layouts as $layout) {
            $body = file_get_contents($layout);
            $relative = str_replace(resource_path('views') . '/', '', $layout);

            $this->assertStringContainsString('clickerp-shell.css', $body,
                "{$relative} renders the admin chrome but never loads clickerp-shell.css.");
            $this->assertStringContainsString('clickerp-shell.js', $body,
                "{$relative} renders the admin chrome but never loads clickerp-shell.js.");
            $this->assertStringContainsString('usesRedesign()', $body,
                "{$relative} must load the redesign assets behind SystemModuleManager::usesRedesign().");
        }
    }
}
