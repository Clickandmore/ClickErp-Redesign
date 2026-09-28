<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\HourlyTestCase;

class HourlyMaidImpersonationGrantTest extends HourlyTestCase
{
    public function test_grant_requires_both_permissions_in_the_same_scope_and_is_idempotent(): void
    {
        config(['database.connections.grant_test' => ['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.default'=>'grant_test']);
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('guard_name');
        });
        foreach (['hourly_drivers_impersonate', 'hourly_maids_view', 'hourly_maids_impersonate'] as $index => $name) {
            DB::table('permissions')->insert(['id'=>$index + 1, 'name'=>$name, 'guard_name'=>'admin']);
        }
        Schema::create('role_branch_permissions', function (Blueprint $table) {
            $table->unsignedInteger('role_id'); $table->unsignedInteger('branch_id'); $table->unsignedInteger('permission_id');
            $table->unique(['role_id', 'branch_id', 'permission_id']);
        });
        DB::table('role_branch_permissions')->insert([
            ['role_id'=>1,'branch_id'=>1,'permission_id'=>1],
            ['role_id'=>1,'branch_id'=>1,'permission_id'=>2],
            ['role_id'=>1,'branch_id'=>2,'permission_id'=>1],
            ['role_id'=>2,'branch_id'=>1,'permission_id'=>2],
        ]);
        $migration = require base_path('database/migrations/2026_09_28_200000_grant_hourly_maid_impersonation_to_staff_managers.php');
        $migration->up();
        $migration->up();
        $grants = DB::table('role_branch_permissions')->where('permission_id', 3)->get();
        $this->assertCount(1, $grants);
        $this->assertEquals(1, $grants->first()->role_id);
        $this->assertEquals(1, $grants->first()->branch_id);
    }
}
