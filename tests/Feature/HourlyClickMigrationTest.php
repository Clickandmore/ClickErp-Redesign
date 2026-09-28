<?php
namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\HourlyTestCase as TestCase;

class HourlyClickMigrationTest extends TestCase
{
    public function test_installation_keeps_click_records_and_existing_shelter_coordinates(): void
    {
        config(['database.connections.hourly_install'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.default'=>'hourly_install','activitylog.enabled'=>false]);
        foreach (['countries','cities','nationalities','maids','admins','notifications','new_settings'] as $name) {
            Schema::create($name, function (Blueprint $table) { $table->id(); });
        }
        Schema::create('shelter_places', function(Blueprint $table) {$table->id();$table->decimal('latitude',10,7)->nullable();$table->decimal('longitude',10,7)->nullable();});
        Schema::create('users', function(Blueprint $table) { $table->softDeletes();$table->id();$table->string('register_from')->nullable();$table->string('national_num')->nullable();});
        Schema::create('permissions', function(Blueprint $table) {$table->id();foreach(['name','guard_name','group_name','section_name','display_name','description'] as $field)$table->string($field)->nullable();$table->timestamps();});
        Schema::create('user_addresses', function(Blueprint $table) {$table->id();$table->unsignedBigInteger('user_id');$table->string('address');});
        DB::table('maids')->insert(['id'=>91]);
        DB::table('users')->insert(['id'=>92,'national_num'=>'existing-customer']);
        DB::table('shelter_places')->insert(['id'=>93,'latitude'=>24.7,'longitude'=>46.6]);
        foreach (config('hourly_migrations') as $file) {
            if (str_contains($file, 'create_hourly_teams_tables')) {
                require_once base_path($file);
                $migration = new \CreateHourlyTeamsTables;
            } else {
                $migration = require base_path($file);
            }
            $migration->up();
        }
        $this->assertDatabaseHas('maids',['id'=>91,'rental_mode'=>'regular']);
        $this->assertDatabaseHas('users',['id'=>92,'national_num'=>'existing-customer','hourly_wallet_balance'=>0]);
        $this->assertDatabaseHas('shelter_places',['id'=>93,'latitude'=>24.7,'longitude'=>46.6]);
        $this->assertTrue(Schema::hasTable('hourly_rental_orders'));
        $this->assertTrue(Schema::hasTable('hourly_staff_notification_reads'));
        $migration = require base_path('database/migrations/2026_09_25_000002_add_start_locations_to_hourly_teams_and_shelters.php');
        $migration->down();
        $this->assertDatabaseHas('shelter_places',['id'=>93,'latitude'=>24.7,'longitude'=>46.6]);
    }
}
