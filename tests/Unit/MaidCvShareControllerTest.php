<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidCvShareController;
use App\Jobs\SendMaidCvShare;
use App\Models\MaidCvShare;
use App\Models\auth\Admin;
use App\Services\ingaz\MaidCvShareService;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MaidCvShareControllerTest extends TestCase
{
    private MaidCvShareController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array',
            'superadmin_modules.branches.default_enabled' => true]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_08_000001_create_maid_cv_shares_tables.php'))->up();
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->integer('branch_id');
            $table->boolean('is_blocked')->default(false); $table->string('phone'); $table->softDeletes();
        });
        Schema::create('maids', function (Blueprint $table) {
            $table->id(); $table->string('submission_status')->nullable(); $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 9, 'name' => 'Client', 'branch_id' => 1, 'phone' => '0501234567']);
        DB::table('maids')->insert(['id' => 1]);
        $admin = Mockery::mock(Admin::class)->makePartial();
        $admin->setRawAttributes(['id' => 7, 'name' => 'Employee', 'branch_id' => 1]);
        $admin->shouldReceive('can')->andReturnUsing(fn ($ability) => in_array($ability, ['maids_view', 'maids_rental_view'], true))->byDefault();
        $admin->shouldReceive('hasAnyPermission', 'hasPermissionTo')->andReturn(false);
        $this->actingAs($admin, 'admin');
        Storage::fake('local');
        Bus::fake();
        $this->controller = new MaidCvShareController();
    }

    private function share(): MaidCvShare
    {
        $share = MaidCvShare::create(['admin_id' => 7, 'client_id' => 9, 'admin_name' => 'Employee', 'client_name' => 'Client',
            'branch_id' => 1, 'phone' => '966501234567', 'provider' => 'meta', 'message' => 'Details']);
        $share->messages()->create([]);
        Storage::disk('local')->put('maid-cv-shares/test.pdf', '%PDF-test');
        $share->messages()->create(['maid_id' => 1, 'file_path' => 'maid-cv-shares/test.pdf', 'filename' => 'test.pdf']);
        return $share;
    }

    public function test_confirmation_is_idempotent_and_enqueues_once(): void
    {
        $share = $this->share();
        $this->controller->confirm($share->id);
        $this->controller->confirm($share->id);
        Bus::assertDispatchedTimes(SendMaidCvShare::class, 1);
        $this->assertSame('queued', $share->fresh()->status);
        $this->assertNotNull($share->fresh()->confirmed_at);
    }

    public function test_rental_only_employee_can_read_rental_share_history(): void
    {
        auth('admin')->user()->shouldReceive('can')->andReturnUsing(fn ($ability) => $ability === 'maids_rental_view');
        $share = $this->share();
        $share->messages()->whereNotNull('maid_id')->update(['snapshot' => ['cv_type' => 'rental']]);
        $this->assertSame($share->id, $this->controller->show($share->id)->getData()['share']->id);
    }

    public function test_rental_only_employee_cannot_read_recruitment_share_history(): void
    {
        auth('admin')->user()->shouldReceive('can')->andReturnUsing(fn ($ability) => $ability === 'maids_rental_view');
        $share = $this->share();
        $share->messages()->whereNotNull('maid_id')->update(['snapshot' => ['cv_type' => 'recruitment']]);
        $this->expectException(ModelNotFoundException::class);
        $this->controller->show($share->id);
    }

    public function test_recruitment_only_employee_cannot_read_rental_share_history(): void
    {
        auth('admin')->user()->shouldReceive('can')->andReturnUsing(fn ($ability) => $ability === 'maids_view');
        $share = $this->share();
        $share->messages()->whereNotNull('maid_id')->update(['snapshot' => ['cv_type' => 'rental']]);
        $this->expectException(ModelNotFoundException::class);
        $this->controller->show($share->id);
    }

    public function test_retry_preserves_accepted_messages_and_does_not_enqueue_twice(): void
    {
        $share = $this->share();
        $share->update(['status' => 'partial', 'confirmed_at' => now()]);
        $share->messages()->first()->update(['status' => 'accepted']);
        $share->messages()->whereNotNull('maid_id')->update(['status' => 'failed']);
        $this->controller->retry($share->id);
        $this->controller->retry($share->id);
        $this->assertSame(['accepted', 'pending'], $share->messages()->pluck('status')->all());
        Bus::assertDispatchedTimes(SendMaidCvShare::class, 1);
    }

    public function test_another_employee_cannot_confirm_someone_elses_draft(): void
    {
        $share = $this->share();
        $share->update(['admin_id' => 8]);
        try {
            $this->controller->confirm($share->id);
            $this->fail('Expected forbidden');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            Bus::assertNothingDispatched();
        }
    }

    public function test_client_from_another_branch_cannot_be_accessed_in_share_history(): void
    {
        $share = $this->share();
        DB::table('users')->where('id', 9)->update(['branch_id' => 2]);
        $this->expectException(ModelNotFoundException::class);
        $this->controller->show($share->id);
    }

    public function test_recipient_phone_cannot_be_forged_for_a_different_customer(): void
    {
        $request = Request::create('/', 'POST', ['maid_ids' => [1], 'client_id' => 9, 'phone' => '0509999999']);
        $service = Mockery::mock(MaidCvShareService::class);
        $service->shouldNotReceive('prepare');
        $this->expectException(ValidationException::class);
        $this->controller->store($request, $service, app(MetaWhatsAppService::class));
    }

    public function test_a_blocked_client_keeps_history_but_cannot_receive_a_new_send(): void
    {
        $share = $this->share();
        DB::table('users')->where('id', 9)->update(['is_blocked' => true]);
        $this->assertSame($share->id, $this->controller->show($share->id)->getData()['share']->id);
        try {
            $this->controller->confirm($share->id);
            $this->fail('Expected blocked client');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            Bus::assertNothingDispatched();
        }
    }
}
