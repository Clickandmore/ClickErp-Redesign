<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\admin\ContractReturnRequestController;
use App\Models\ContractReturnRequest;
use App\Services\BranchAccessService;
use App\Services\ingaz\RecruitmentContractWarrantyReturnService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ContractReturnRequestAtomicProcessingTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'return_request_test',
            'database.connections.return_request_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge('return_request_test');

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('contract_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->timestamps();
        });
        Schema::create('contract_return_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        DB::table('contracts')->insert([
            'id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchAccess = Mockery::mock(BranchAccessService::class);
        $branchAccess->shouldReceive('assertCanAccessModel')->andReturnNull();
        $this->app->instance(BranchAccessService::class, $branchAccess);
    }

    protected function tearDown(): void
    {
        DB::purge('return_request_test');
        config(['database.default' => $this->originalConnection]);

        parent::tearDown();
    }

    public function test_accounting_failure_rolls_approval_back_to_pending(): void
    {
        $returnRequest = $this->createPendingRequest();
        $this->mockAdminId(7);

        $accounting = Mockery::mock(RecruitmentContractWarrantyReturnService::class);
        $accounting->shouldReceive('record')
            ->once()
            ->andThrow(new \RuntimeException('missing finance account'));
        $this->app->instance(RecruitmentContractWarrantyReturnService::class, $accounting);

        $response = $this->controller()->process($this->processRequest('approved'), $returnRequest->id);

        $this->assertSame(422, $response->getStatusCode());
        $returnRequest->refresh();
        $this->assertSame('pending', $returnRequest->status);
        $this->assertNull($returnRequest->processed_by);
        $this->assertNull($returnRequest->processed_at);
    }

    public function test_successful_approval_commits_status_and_processor(): void
    {
        $returnRequest = $this->createPendingRequest();
        $this->mockAdminId(7);

        $accounting = Mockery::mock(RecruitmentContractWarrantyReturnService::class);
        $accounting->shouldReceive('record')->once()->andReturnNull();
        $this->app->instance(RecruitmentContractWarrantyReturnService::class, $accounting);

        $response = $this->controller()->process($this->processRequest('approved'), $returnRequest->id);

        $this->assertSame(200, $response->getStatusCode());
        $returnRequest->refresh();
        $this->assertSame('approved', $returnRequest->status);
        $this->assertSame(7, $returnRequest->processed_by);
        $this->assertNotNull($returnRequest->processed_at);
    }

    public function test_an_already_processed_request_cannot_be_processed_again(): void
    {
        $returnRequest = $this->createPendingRequest(['status' => 'rejected']);

        $accounting = Mockery::mock(RecruitmentContractWarrantyReturnService::class);
        $accounting->shouldNotReceive('record');
        $this->app->instance(RecruitmentContractWarrantyReturnService::class, $accounting);

        $response = $this->controller()->process($this->processRequest('approved'), $returnRequest->id);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('rejected', $returnRequest->fresh()->status);
    }

    private function createPendingRequest(array $overrides = []): ContractReturnRequest
    {
        return ContractReturnRequest::query()->create(array_merge([
            'contract_id' => 10,
            'user_id' => 20,
            'status' => 'pending',
            'notes' => 'test request',
        ], $overrides));
    }

    private function processRequest(string $status): Request
    {
        return Request::create('/admin/contract-return-requests/process', 'POST', [
            'status' => $status,
            'admin_notes' => 'Test processing note',
        ]);
    }

    private function mockAdminId(int $id): void
    {
        // The branch scope consults the admin guard on every query it touches,
        // so the guard has to answer check()/user() as well as id(), and
        // guard() is no longer called exactly once per request.
        $guard = Mockery::mock();
        $guard->shouldReceive('id')->andReturn($id);
        $guard->shouldReceive('check')->andReturn(false);
        $guard->shouldReceive('user')->andReturnNull();
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);
    }

    private function controller(): ContractReturnRequestController
    {
        return app(ContractReturnRequestController::class);
    }
}
