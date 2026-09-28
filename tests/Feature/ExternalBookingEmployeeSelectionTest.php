<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ApiSimplifiedOrderController;
use App\Services\ExternalOrderAdminSmsNotifier;
use App\Support\ExternalOrderSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExternalBookingEmployeeSelectionTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'external_booking_test');
            $app['config']->set('database.connections.external_booking_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_mode')->default('simplified');
        });
        Schema::create('maids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('settings_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->string('locale');
        });
        Schema::create('maid_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->string('locale');
        });
        Schema::create('job_types', fn (Blueprint $table) => $table->id());
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('job_types_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_type_id');
            $table->string('locale');
        });
        Schema::create('maid_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('maid_id');
            $table->unsignedBigInteger('job_type_id');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->boolean('is_blocked')->default(false);
            $table->softDeletes();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('admin_type');
            $table->string('status')->nullable();
            $table->softDeletes();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('maid_id');
            $table->unsignedBigInteger('job_type_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
        });
        DB::table('settings')->insert(['id' => 1]);
        DB::table('maids')->insert(['id' => 1]);
        DB::table('job_types')->insert(['id' => 1]);
        DB::table('maid_jobs')->insert(['maid_id' => 1, 'job_type_id' => 1]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Client', 'phone' => '512345678']);
        DB::table('admins')->insert(['id' => 4, 'name' => 'Employee', 'admin_type' => 'customer_service', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        DB::purge('external_booking_test');
        parent::tearDown();
    }

    public function test_missing_migration_keeps_employee_selection_enabled(): void
    {
        $this->assertTrue(ExternalOrderSettings::employeeSelectionEnabled());
        $this->expectException(ValidationException::class);
        $this->book();
    }

    public function test_migration_defaults_on_and_preserves_saved_off_value_when_repeated(): void
    {
        $migration = require database_path('migrations/2026_09_28_120000_add_external_booking_employee_selection_to_settings.php');
        $migration->up();
        $this->assertTrue(ExternalOrderSettings::employeeSelectionEnabled());
        DB::table('settings')->update(['external_booking_employee_selection' => false]);
        $migration->up();
        $this->assertFalse(ExternalOrderSettings::employeeSelectionEnabled());
    }

    public function test_disabled_booking_ignores_stale_employee_and_saves_without_assignment(): void
    {
        $this->setSelection(false);
        $response = $this->book(['customer' => 999]);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertNull($response->getData(true)['data']['customer']);
        $this->assertNull(DB::table('orders')->value('customer_id'));
        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame(0, DB::table('order_details')->count());
    }

    public function test_enabled_booking_keeps_employee_on_order_and_details(): void
    {
        $this->setSelection(true);
        $response = $this->book(['customer' => 4]);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(4, (int) DB::table('orders')->value('customer_id'));
        $this->assertSame(4, (int) DB::table('order_details')->value('admin_id'));
    }

    private function setSelection(bool $enabled): void
    {
        $migration = require database_path('migrations/2026_09_28_120000_add_external_booking_employee_selection_to_settings.php');
        $migration->up();
        DB::table('settings')->update(['external_booking_employee_selection' => $enabled]);
    }

    /** @dataProvider authenticatedChannels */
    public function test_disabled_selection_applies_only_to_the_external_website_channel(?string $channel, ?int $expectedEmployee): void
    {
        $this->setSelection(false);
        auth()->setUser(\App\Models\ingaz\User::findOrFail(1));
        $controller = new class extends \App\Http\Controllers\Api\ApiOrderController {
            public function sendSMS($phone, $msg, $code = null, $senderType = null) { return ['code' => '0']; }
        };
        $maids = \Mockery::mock(\App\Http\Actions\ingaz\MaidAction::class);
        $maids->shouldReceive('find')->once()->with(1)->andReturn(\App\Models\ingaz\Maid::findOrFail(1));
        $notifier = \Mockery::mock(ExternalOrderAdminSmsNotifier::class);
        $notifier->shouldReceive('send')->andReturn(false);
        $request = Request::create('/client/orders', 'POST', [
            'maid_id' => 1, 'customer' => 4, 'service_type' => 'estqdam', 'booking_channel' => $channel,
        ]);
        $response = Model::withoutEvents(fn () => $controller->store($request, $maids, new \App\Http\Actions\auth\AdminAction(), $notifier));
        $this->assertSame(201, $response->getStatusCode());
        $this->assertEquals($expectedEmployee, DB::table('orders')->value('customer_id'));
        $this->assertSame($expectedEmployee === null ? 0 : 1, DB::table('order_details')->count());
    }

    public static function authenticatedChannels(): array
    {
        return ['website' => ['external_website', null], 'existing app' => [null, 4]];
    }

    private function book(array $extra = [])
    {
        $controller = new class extends ApiSimplifiedOrderController {
            public function sendSMS($phone, $msg, $code = null, $senderType = null) { return ['code' => '0']; }
        };
        $notifier = \Mockery::mock(ExternalOrderAdminSmsNotifier::class);
        $notifier->shouldReceive('send')->andReturn(false);
        $request = Request::create('/client/simplified-orders', 'POST', $extra + [
            'maid_id' => 1, 'name' => 'Client', 'phone' => '512345678', 'service_type' => 'estqdam',
        ]);
        // Isolate reservation behavior from unrelated audit and branch assignment listeners.
        return Model::withoutEvents(fn () => $controller->store($request, $notifier));
    }
}
