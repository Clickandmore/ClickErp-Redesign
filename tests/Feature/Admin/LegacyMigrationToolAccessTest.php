<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * أداة ترحيل البيانات القديمة كانت داخل public/ فيشغّلها أي زائر بلا تسجيل دخول،
 * ومعها ملفات عملاء مصدَّرة قابلة للتنزيل. نُقلت إلى legacy/ وتُقدَّم عبر
 * LegacyMigrationToolController خلف صلاحيات المدير.
 */
class LegacyMigrationToolAccessTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    public function test_tool_is_no_longer_inside_the_public_folder(): void
    {
        $this->assertFileDoesNotExist(public_path('migration.php'));
        $this->assertFileDoesNotExist(public_path('migration_hub.php'));
        $this->assertDirectoryDoesNotExist(public_path('migration'));
        $this->assertSame([], glob(public_path('tmp_*.php')));
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/migration/test_page.php')->assertRedirect();
        $this->get('/migration.php')->assertRedirect();
        $this->post('/migration/simple_test.php')->assertRedirect();
        $this->get('/migration')->assertRedirect();
    }

    public function test_admin_without_permission_cannot_run_scripts(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $response = $this->get('/migration/test_page.php');

        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('الصفحة تعمل', (string) $response->getContent());
    }

    public function test_admin_with_permission_can_run_scripts(): void
    {
        $this->actingAs($this->admin(PermKeys::SETTINGS_CONTROL), 'admin');

        $this->get('/migration/test_page.php')
            ->assertOk()
            ->assertSee('الصفحة تعمل');
    }

    public function test_cross_site_post_is_rejected(): void
    {
        $this->actingAs($this->admin(PermKeys::DEVELOPERS_PERMS), 'admin');

        $this->post('/migration/simple_test.php', [], ['Origin' => 'https://evil.example'])->assertForbidden();
        $this->post('/migration/simple_test.php')->assertForbidden();
    }

    public function test_uploaded_php_files_are_never_executed(): void
    {
        $directory = base_path('legacy/migration/uploads');
        $created = !File::isDirectory($directory);
        File::ensureDirectoryExists($directory);
        $file = $directory . '/probe_' . uniqid() . '.php';
        File::put($file, '<?php echo "EXECUTED";');

        try {
            $this->actingAs($this->admin(PermKeys::SETTINGS_CONTROL), 'admin');

            $response = $this->get('/migration/uploads/' . basename($file));

            $response->assertNotFound();
            $this->assertStringNotContainsString('EXECUTED', (string) $response->getContent());
        } finally {
            File::delete($file);
            if ($created) {
                File::deleteDirectory($directory);
            }
        }
    }

    public function test_path_traversal_does_not_match_the_route(): void
    {
        $this->actingAs($this->admin(PermKeys::SETTINGS_CONTROL), 'admin');

        $this->get('/migration/..%2F..%2F.env.php')->assertNotFound();
        $this->get('/migration/exports/..%2F..%2F..%2Fcomposer.json')->assertNotFound();
    }

    private function admin(?string $permission = null): Admin
    {
        $admin = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();

        if ($permission) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']));
        }

        return $admin;
    }
}
