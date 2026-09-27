<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Http\Requests\StoreRentalContractRequest;
use App\Models\auth\Admin;
use App\PermKeys;
use App\Services\BranchAccessService;
use App\Services\ingaz\RentalContractFinanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RentalContractChangeMarketerTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'marketer_test');
            $app['config']->set('database.connections.marketer_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
            $app['config']->set('cache.default', 'array');
            $app['config']->set('superadmin_modules', []);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->softDeletes();
        });
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketer_id');
            $table->string('status');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });
        Schema::create('rental_contract_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id');
        });
        DB::table('admins')->insert([
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'active'],
            ['id' => 3, 'status' => 'suspended'],
        ]);
        DB::table('rental_contracts')->insert([
            'id' => 10, 'marketer_id' => 1, 'status' => 'active', 'branch_id' => 7,
        ]);
    }

    private function loginWithPermission(bool $allowed): void
    {
        $admin = Mockery::mock(Admin::class)->makePartial();
        $admin->id = 1;
        $admin->shouldReceive('can')->with(PermKeys::RENT_CONTRACT_CHANGE_MARKETER)->andReturn($allowed);
        auth('admin')->setUser($admin);
    }

    private function change(int $marketerId)
    {
        return app(RentContractController::class)->changeMarketer(
            Request::create('/', 'POST', ['marketer_id' => $marketerId]), 10
        );
    }

    public function test_authorized_change_updates_only_the_marketer(): void
    {
        $this->loginWithPermission(true);
        $response = $this->change(2);

        $this->assertTrue($response->getData(true)['success']);
        $contract = DB::table('rental_contracts')->find(10);
        $this->assertSame(2, $contract->marketer_id);
        $this->assertSame(7, $contract->branch_id);
        $this->assertSame('active', $contract->status);
    }

    public function test_change_without_permission_is_forbidden(): void
    {
        $this->loginWithPermission(false);
        try {
            $this->change(2);
            $this->fail('Expected permission denial');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            $this->assertSame(1, DB::table('rental_contracts')->where('id', 10)->value('marketer_id'));
        }
    }

    public function test_suspended_and_missing_marketers_are_rejected(): void
    {
        $this->loginWithPermission(true);
        foreach ([3, 999] as $marketerId) {
            try {
                $this->change($marketerId);
                $this->fail('Expected invalid marketer');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('marketer_id', $error->errors());
                $this->assertSame(1, DB::table('rental_contracts')->where('id', 10)->value('marketer_id'));
            }
        }
    }

    public function test_general_update_cannot_bypass_the_permission(): void
    {
        $this->loginWithPermission(false);
        $request = StoreRentalContractRequest::create('/', 'PUT', ['marketer_id' => 2]);
        try {
            app(RentContractController::class)->update($request, Mockery::mock(RentalContractFinanceService::class), 10);
            $this->fail('Expected permission denial');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            $this->assertSame(1, DB::table('rental_contracts')->where('id', 10)->value('marketer_id'));
            $this->assertSame(0, DB::transactionLevel());
        }
    }

    public function test_branch_access_is_enforced_before_the_change(): void
    {
        $this->loginWithPermission(true);
        $access = Mockery::mock(BranchAccessService::class)->makePartial();
        $access->shouldReceive('assertCanAccessModel')->once()->andThrow(new HttpException(403));
        $this->app->instance(BranchAccessService::class, $access);
        try {
            $this->change(2);
            $this->fail('Expected branch access denial');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            $this->assertSame(1, DB::table('rental_contracts')->where('id', 10)->value('marketer_id'));
        }
    }

    public function test_keeping_the_existing_marketer_does_not_require_the_new_permission(): void
    {
        $this->loginWithPermission(false);
        $contract = new \App\Models\RentalContract(['marketer_id' => 1]);
        $method = new \ReflectionMethod(RentContractController::class, 'assertCanChangeMarketer');
        $method->setAccessible(true);
        $method->invoke(app(RentContractController::class), $contract, 1);
        $this->assertSame(1, $contract->marketer_id);
    }
}
