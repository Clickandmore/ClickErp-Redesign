<?php

namespace Tests\Unit;

use App\Models\AuditActivity;
use App\Models\Concerns\AuditsChanges;
use App\Support\Audit\AuditProperties;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Traits\LogsActivity;
use Tests\TestCase;

class UnifiedActivityLoggingTest extends TestCase
{
    public function test_sensitive_values_are_recursively_redacted(): void
    {
        $result = AuditProperties::sanitize([
            'old' => ['key' => 'r2_secret_access_key', 'value' => 'secret'],
            'attributes' => ['value' => '{"password":"secret","enabled":true}'],
            'token' => 'secret',
        ]);
        $this->assertStringNotContainsString('secret"', json_encode($result));
        $this->assertSame('[محجوب]', $result['old']['value']);
        $this->assertTrue(json_decode($result['attributes']['value'], true)['enabled']);
    }

    public function test_business_models_cannot_silently_lose_audit_coverage(): void
    {
        $roots = [app_path('Models'), base_path('packages/crm/src/Models')];
        foreach ($roots as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $source = file_get_contents($file->getPathname());
                if (!preg_match('/namespace\s+([^;]+);/', $source, $namespace)
                    || !preg_match('/class\s+(\w+)\s+extends\s/', $source, $class)) {
                    continue;
                }
                if (array_key_exists($class[1], config('audit.excluded_models'))) {
                    continue;
                }
                $fqcn = $namespace[1] . '\\' . $class[1];
                $this->assertTrue(class_exists($fqcn), $fqcn);
                $this->assertContains(LogsActivity::class, class_uses_recursive($fqcn), $fqcn);
            }
        }
    }

    public function test_model_lifecycle_logs_only_real_changes_and_hides_secrets(): void
    {
        config(['database.connections.audit_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.database_connection' => 'audit_test']);
        Schema::connection('audit_test')->create('audit_fixtures', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('audit_test')->create('activity_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->string('event')->nullable();
            $table->text('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
        $model = AuditFixture::create(['name' => 'before', 'password' => 'must-not-log']);
        $model->update(['name' => 'after']);
        $model->update(['password' => 'another-secret']);
        $model->save();
        $model->delete();
        $model->restore();
        $model->forceDelete();
        $logs = AuditActivity::orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted', 'restored', 'force_deleted'], $logs->pluck('event')->all());
        $this->assertStringNotContainsString('must-not-log', $logs->toJson());
        $this->assertStringNotContainsString('another-secret', $logs->toJson());
        $this->assertSame('before', $logs[1]->properties['old']['name']);
        $this->assertSame('after', $logs[1]->properties['attributes']['name']);
    }
}

class AuditFixture extends Model
{
    use AuditsChanges, \Illuminate\Database\Eloquent\SoftDeletes;
    protected $connection = 'audit_test';
    protected $guarded = [];
}
