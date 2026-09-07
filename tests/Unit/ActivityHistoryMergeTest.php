<?php

namespace Tests\Unit;

use App\Console\Commands\MergeDomainActivityLogs;
use App\Http\Controllers\admin\ActivityLogController;
use App\Models\AuditActivity;
use App\Models\ContractStatusLog;
use App\Support\Audit\ActivityRelations;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityHistoryMergeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.history_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'history_test', 'activitylog.database_connection' => 'history_test']);
        Schema::create('activity_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->string('event')->nullable();
            $table->text('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('legacy_source')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->unique(['legacy_source', 'legacy_id']);
            $table->timestamps();
        });
        Schema::create('contract_status_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_id');
            $table->unsignedInteger('performed_by_id')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_merge_preserves_history_skips_existing_and_is_repeatable(): void
    {
        DB::table('contract_status_logs')->insert([
            ['id' => 1, 'contract_id' => 7, 'status' => 'new', 'notes' => 'historical', 'performed_by_id' => 42, 'created_at' => '2020-01-02 03:04:05'],
            ['id' => 2, 'contract_id' => 7, 'status' => 'active', 'notes' => null, 'performed_by_id' => null, 'created_at' => '2020-01-03 03:04:05'],
        ]);
        DB::table('activity_log')->insert(['subject_type' => ContractStatusLog::class, 'subject_id' => 2, 'event' => 'created', 'description' => 'already logged']);
        Artisan::call('activitylog:merge-domain');
        $this->assertSame(1, DB::table('activity_log')->count());
        Artisan::call('activitylog:merge-domain', ['--apply' => true]);
        Artisan::call('activitylog:merge-domain', ['--apply' => true]);
        $this->assertSame(2, DB::table('activity_log')->count());
        $this->assertSame(2, DB::table('contract_status_logs')->count());
        $history = DB::table('activity_log')->where('legacy_id', 1)->first();
        $this->assertSame('2020-01-02 03:04:05', $history->created_at);
        $this->assertSame(42, $history->causer_id);
        $this->assertSame('domain:contract_status_logs', $history->legacy_source);
    }

    public function test_obsolete_model_types_and_deleted_actors_do_not_break_history(): void
    {
        $activity = new Activity(['subject_type' => 'App\\Models\\RemovedModel', 'subject_id' => 9,
            'causer_type' => 'App\\Models\\RemovedActor', 'causer_id' => 3,
            'event' => 'updated', 'properties' => ['actor_name' => 'الموظف السابق', 'attributes' => ['password' => 'secret']]]);
        ActivityRelations::load(collect([$activity]));
        $method = new \ReflectionMethod(ActivityLogController::class, 'transformActivity');
        $method->setAccessible(true);
        $result = $method->invoke(app(ActivityLogController::class), $activity);
        $this->assertNull($result['target_url']);
        $this->assertSame('الموظف السابق', $result['actor_name']);
        $this->assertStringNotContainsString('secret', $result['properties_pretty']);
    }

    public function test_clearing_role_permissions_records_revoked_ids(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedInteger('permission_id');
            $table->unsignedInteger('role_id');
        });
        DB::table('permissions')->insert(['id' => 1, 'name' => 'audit_view', 'guard_name' => 'admin']);
        $role = \App\Models\Role::create(['name' => 'audit_test', 'guard_name' => 'admin']);
        $role->syncPermissions([1]);
        $role->syncPermissions([]);
        $log = AuditActivity::where('event', 'permission_detached')->firstOrFail();
        $this->assertSame([1], $log->properties['assignment_ids']);
        $this->assertSame(0, DB::table('role_has_permissions')->count());
    }

    public function test_settings_changes_are_audited_without_exposing_secret_values(): void
    {
        Schema::create('new_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
        $setting = \App\Models\NewSettings::create(['key' => 'smtp_password', 'value' => 'secret-before']);
        $setting->update(['value' => 'secret-after']);
        $setting->save();
        $logs = AuditActivity::orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame('[محجوب]', $logs[1]->properties['attributes']['value']);
        $this->assertSame('[محجوب]', $logs[1]->properties['old']['value']);
        $this->assertStringNotContainsString('secret-before', $logs->toJson());
        $this->assertStringNotContainsString('secret-after', $logs->toJson());
        \App\Support\Audit\AuditedSettings::updateOrInsert('new_settings', ['key' => 'module_enabled'], ['value' => '1']);
        \App\Support\Audit\AuditedSettings::updateOrInsert('new_settings', ['key' => 'module_enabled'], ['value' => '1']);
        $this->assertSame(1, AuditActivity::where('log_name', 'settings')->count());
    }

    public function test_legacy_actor_matching_does_not_guess_between_duplicate_names(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });
        DB::table('admins')->insert([
            ['id' => 1, 'name' => 'Same Name', 'deleted_at' => null],
            ['id' => 2, 'name' => ' same  name ', 'deleted_at' => null],
            ['id' => 3, 'name' => 'Former Admin', 'deleted_at' => '2020-01-01 00:00:00'],
        ]);
        $method = new \ReflectionMethod(\App\Services\ingaz\LegacyActivityLogImportService::class, 'adminNameMap');
        $method->setAccessible(true);
        $map = $method->invoke(app(\App\Services\ingaz\LegacyActivityLogImportService::class));
        $this->assertArrayNotHasKey('same name', $map);
        $this->assertSame(3, $map['former admin']);
    }

    public function test_console_activity_records_its_source_without_inventing_an_employee(): void
    {
        $context = app(\App\Support\Audit\ExecutionContext::class);
        $context->start('contracts:initialize-warranty');
        try {
            activity()->event('updated')->log('تحديث الضمان');
        } finally {
            $context->finish();
        }
        $log = AuditActivity::firstOrFail();
        $this->assertNull($log->causer_id);
        $this->assertSame('النظام', $log->properties['actor_name']);
        $this->assertSame('console', $log->properties['execution_source']);
        $this->assertSame('contracts:initialize-warranty', $log->properties['execution_command']);
        $this->assertNull($context->command());
    }

    public function test_authenticated_admin_is_recorded_as_the_actor(): void
    {
        $admin = new \App\Models\auth\Admin();
        $admin->setRawAttributes(['id' => 42, 'name' => 'موظف الاختبار']);
        $admin->exists = true;
        \Illuminate\Support\Facades\Auth::guard('admin')->setUser($admin);
        \Illuminate\Support\Facades\Auth::shouldUse('admin');
        activity()->event('updated')->log('تعديل موظف');
        $log = AuditActivity::firstOrFail();
        $this->assertSame(42, $log->causer_id);
        $this->assertSame(\App\Models\auth\Admin::class, $log->causer_type);
        $this->assertSame('موظف الاختبار', $log->properties['actor_name']);
        $this->assertArrayNotHasKey('execution_source', $log->properties->all());
    }

    public function test_old_missing_actor_is_not_misrepresented_as_a_known_system_action(): void
    {
        $activity = new Activity(['event' => 'updated', 'properties' => [
            'attributes' => ['updated_at' => '2026-09-07 06:00:00'],
            'old' => ['updated_at' => '2026-09-06 18:00:00'],
        ]]);
        $activity->setRelation('causer', null)->setRelation('subject', null);
        $method = new \ReflectionMethod(ActivityLogController::class, 'transformActivity');
        $method->setAccessible(true);
        $result = $method->invoke(app(ActivityLogController::class), $activity);
        $this->assertSame('هوية المنفذ غير محفوظة', $result['actor_name']);
        $this->assertStringStartsWith('تم تحديث', $result['summary']);
        $this->assertSame('تم تحديث توقيت حفظ السجل فقط، دون تغيير بياناته.', $result['description']);
    }

    public function test_legacy_import_is_blocked_before_writing_into_another_company_database(): void
    {
        config(['legacy_activity_import.alrafedin_databases' => ['alrafedin_company_database']]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('استيراد سجل الرافدين غير مسموح لقاعدة البيانات الحالية');
        app(\App\Services\ingaz\LegacyActivityLogImportService::class)->import(
            \Illuminate\Http\UploadedFile::fake()->create('legacy.xlsx'), null
        );
    }

    public function test_authentication_events_never_persist_submitted_credentials(): void
    {
        event(new \Illuminate\Auth\Events\Failed('admin', null, ['password' => 'do-not-store', 'email' => 'private@example.test']));
        $activity = AuditActivity::firstOrFail();
        $this->assertSame('login_failed', $activity->event);
        $this->assertNull($activity->causer_id);
        $this->assertStringNotContainsString('do-not-store', $activity->toJson());
        $this->assertStringNotContainsString('private@example.test', $activity->toJson());
    }
}
