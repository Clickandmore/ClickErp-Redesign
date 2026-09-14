<?php

namespace Tests\Feature;

use App\Models\ingaz\Invoice;
use App\Services\ingaz\DelegationCostService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use Tests\TestCase;

class DelegationCostTest extends TestCase
{
    private DelegationCostService $service;
    private array $lines = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'cost_test', 'database.connections.cost_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'activitylog.enabled' => false]);
        Schema::create('fn_confirm_settings', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('value'); $t->timestamps();
        });
        Schema::create('fn_accounts', function (Blueprint $t) {
            $t->id(); $t->integer('parent_id')->nullable();
        });
        Schema::create('ingaz_delegations', function (Blueprint $t) {
            $t->id(); $t->string('status'); $t->integer('branch_id')->nullable();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id(); $t->integer('ingaz_delegation_id'); $t->integer('ingaz_delegation_cost_constraint_id')->nullable(); $t->timestamps();
        });
        Schema::create('fn_constraints', function (Blueprint $t) {
            $t->id(); $t->string('statement'); $t->decimal('value'); $t->softDeletes();
        });
        DB::table('fn_accounts')->insert(['id' => 1]);
        DB::table('fn_confirm_settings')->insert(['title' => 'ingaz_delegation_cost_account', 'value' => '1']);
        foreach ([10, 20] as $id) {
            DB::table('ingaz_delegations')->insert(['id' => $id, 'status' => 'under_process']);
            DB::table('invoices')->insert(['id' => $id, 'ingaz_delegation_id' => $id]);
        }
        $journals = Mockery::mock(ConstraintService::class);
        $journals->shouldReceive('storeConstraint')->andReturnUsing(function ($payload) {
            $id = DB::table('fn_constraints')->insertGetId(['statement' => $payload['statement'], 'value' => $payload['value']]);
            return (new Constraint)->forceFill(['id' => $id]);
        });
        $journals->shouldReceive('storeConstraintDetails')->andReturnUsing(function ($accounts, $id, $payload) {
            $this->lines[] = $payload;
        });
        $years = Mockery::mock(YearService::class);
        $years->shouldReceive('checkDateRange')->andReturn((object) ['id' => 1]);
        $this->service = new class($journals, $years) extends DelegationCostService {
            public function paymentAccounts(): array
            {
                return ['bank' => collect([(new Account)->forceFill(['id' => 2])]), 'custody' => collect([(new Account)->forceFill(['id' => 3])]), 'ingaz' => collect()];
            }
        };
    }

    private function data(): array
    {
        return ['date' => '2026-09-14', 'reference' => 'TRANSFER-123', 'confirmed' => 1,
            'payment_source_type' => 'bank', 'payment_account_id' => 2,
            'delegation_ids' => [10, 20], 'amounts' => [10 => '137.25', 20 => '150.50']];
    }

    public function test_batch_posts_balanced_costs_to_selected_bank_and_prevents_reposting(): void
    {
        $this->assertSame(2, Invoice::withoutEvents(fn () => $this->service->post($this->data())));
        $this->assertSame(2, DB::table('invoices')->whereNotNull('ingaz_delegation_cost_constraint_id')->count());
        foreach ($this->lines as $line) {
            $this->assertSame([1, 2], $line['account_id']);
            $this->assertEquals(array_sum($line['debtor_value']), array_sum($line['creditor_value']));
            $this->assertStringContainsString('TRANSFER-123', $line['statement']);
        }
        $this->expectException(ValidationException::class);
        Invoice::withoutEvents(fn () => $this->service->post($this->data()));
    }

    public function test_custody_payment_uses_selected_employee_account(): void
    {
        $data = array_replace($this->data(), ['payment_source_type' => 'custody', 'payment_account_id' => 3]);
        Invoice::withoutEvents(fn () => $this->service->post($data));
        $this->assertSame([1, 3], $this->lines[0]['account_id']);
    }

    public function test_batch_rolls_back_when_second_delegation_already_has_cost(): void
    {
        DB::table('invoices')->where('id', 20)->update(['ingaz_delegation_cost_constraint_id' => 99]);
        try {
            Invoice::withoutEvents(fn () => $this->service->post($this->data()));
            $this->fail('Expected duplicate rejection');
        } catch (ValidationException $e) {
            $this->assertSame(0, DB::table('fn_constraints')->count());
            $this->assertNull(DB::table('invoices')->where('id', 10)->value('ingaz_delegation_cost_constraint_id'));
        }
    }

    public function test_cannot_use_bank_account_as_employee_custody(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->post(array_replace($this->data(), ['payment_source_type' => 'custody']));
    }

    public function test_cost_routes_are_registered_with_authorized_controller_actions(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertStringEndsWith('@createCosts', $routes->getByName('delegation-costs.create')->getActionName());
        $this->assertSame(['POST'], $routes->getByName('delegation-costs.store')->methods());
        $controller = app(\App\Http\Controllers\admin\IngazDelegationController::class);
        $this->assertNotEmpty(collect($controller->getMiddleware())->first(fn ($middleware) =>
            in_array('storeCosts', $middleware['options']['only'] ?? [], true)));
    }

    public function test_confirmation_is_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->post(array_replace($this->data(), ['confirmed' => 0]));
    }
}
