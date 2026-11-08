<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractArchiveLog;
use App\Models\RentalContract;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\PermKeys;
use App\Services\ContractArchiveService;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContractArchiveTest extends TestCase
{
    use DatabaseTransactions;

    private ContractArchiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Contract::forgetArchiveColumnCache();
        RentalContract::forgetArchiveColumnCache();
        $this->service = app(ContractArchiveService::class);
    }

    private function recruitment(array $attributes = []): Contract
    {
        $id = DB::table('contracts')->insertGetId($attributes + [
            'type' => 'recruitment',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now()->subDays(3),
        ]);

        return Contract::query()->findOrFail($id);
    }

    private function rental(array $attributes = []): RentalContract
    {
        $id = DB::table('rental_contracts')->insertGetId($attributes + [
            'user_id' => 1,
            'status' => 'cancelled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return RentalContract::query()->findOrFail($id);
    }

    private function admin(array $permissions = []): Admin
    {
        foreach ([PermKeys::REC_CONTRACTS_ARCHIVE, PermKeys::REC_CONTRACTS_ARCHIVE_VIEW, PermKeys::RENT_CONTRACT_ARCHIVE, PermKeys::RENT_CONTRACT_ARCHIVE_VIEW] as $name) {
            Permission::findOrCreate($name, 'admin');
        }
        $admin = Admin::factory()->create();
        if ($permissions !== []) {
            $admin->givePermissionTo($permissions);
        }
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_existing_and_new_contracts_are_not_archived_and_stay_in_lists(): void
    {
        $contract = $this->recruitment(['status' => 'new']);
        $rental = $this->rental(['status' => 'active']);

        $this->assertFalse($contract->isArchived());
        $this->assertFalse($rental->isArchived());
        $this->assertTrue(Contract::query()->notArchived()->whereKey($contract->id)->exists());
        $this->assertTrue(RentalContract::query()->notArchived()->whereKey($rental->id)->exists());
    }

    public function test_unfinished_contracts_are_rejected(): void
    {
        $recruitment = $this->recruitment(['status' => 'new']);
        $rental = $this->rental(['status' => 'active']);

        $this->assertStringContainsString('غير منتهٍ', $this->service->archiveBlocker($recruitment));
        $this->assertStringContainsString('غير منتهٍ', $this->service->archiveBlocker($rental));
    }

    public function test_completed_contract_with_running_warranty_is_rejected(): void
    {
        $contract = $this->recruitment(['warranty_end_date' => now()->addMonth()->toDateString()]);

        $this->assertStringContainsString('ضمان العقد', $this->service->archiveBlocker($contract));
    }

    public function test_unpaid_invoice_blocks_archive_and_paid_invoice_does_not(): void
    {
        $contract = $this->recruitment();
        $invoiceId = DB::table('invoices')->insertGetId([
            'contract_id' => $contract->id, 'total_due' => 1000, 'is_paid' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertStringContainsString('فاتورة غير مسددة', $this->service->archiveBlocker($contract));

        DB::table('invoices')->where('id', $invoiceId)->update(['is_paid' => 1]);
        $this->transferredByMusaned($invoiceId);
        $this->assertNull($this->service->archiveBlocker($contract));
    }

    public function test_paid_invoice_not_yet_transferred_by_musaned_blocks_archive(): void
    {
        $contract = $this->recruitment();
        $invoiceId = DB::table('invoices')->insertGetId([
            'contract_id' => $contract->id, 'total_due' => 1000, 'is_paid' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertStringContainsString('مساند', $this->service->archiveBlocker($contract));

        $this->transferredByMusaned($invoiceId);
        $this->assertNull($this->service->archiveBlocker($contract));
    }

    public function test_admin_cannot_create_financial_documents_for_an_archived_contract(): void
    {
        $admin = $this->admin();
        $contract = $this->recruitment();
        $this->service->archive('recruitment', [$contract->id], 'منتهٍ', $admin->id);

        try {
            Invoice::query()->create(['contract_id' => $contract->id, 'total_due' => 100, 'is_paid' => 0]);
            $this->fail('An invoice was created for an archived contract.');
        } catch (\App\Exceptions\ArchivedContractException $e) {
            $this->assertSame($contract->id, $e->contractId);
        }

        try {
            \App\Models\FinancialRequest::query()->create([
                'request_no' => 'FR-ARCH-BLOCK-' . $contract->id, 'type' => 'refund', 'status' => 'preparing',
                'contract_id' => $contract->id, 'contract_type' => 'recruitment',
            ]);
            $this->fail('A financial request was created for an archived contract.');
        } catch (\App\Exceptions\ArchivedContractException $e) {
            $this->assertSame('recruitment', $e->contractType);
        }

        $this->assertSame(0, DB::table('invoices')->where('contract_id', $contract->id)->count());
    }

    public function test_automatic_processes_without_an_admin_are_not_blocked(): void
    {
        $admin = $this->admin();
        $contract = $this->recruitment();
        $this->service->archive('recruitment', [$contract->id], 'منتهٍ', $admin->id);
        auth('admin')->logout();

        $invoice = Invoice::query()->create(['contract_id' => $contract->id, 'total_due' => 100, 'is_paid' => 1]);

        $this->assertSame($contract->id, (int) $invoice->contract_id);
    }

    private function transferredByMusaned(int $invoiceId): void
    {
        $transferId = DB::table('musaned_transfers')->insertGetId([
            'transfer_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('musaned_transfer_items')->insert([
            'musaned_transfer_id' => $transferId, 'invoice_id' => $invoiceId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_open_financial_request_blocks_archive(): void
    {
        $contract = $this->recruitment();
        DB::table('financial_requests')->insert([
            'request_no' => 'FR-ARCH-' . $contract->id, 'type' => 'refund', 'status' => 'for_approval',
            'contract_id' => $contract->id, 'contract_type' => 'recruitment',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertStringContainsString('طلب مالي مفتوح', $this->service->archiveBlocker($contract));
    }

    public function test_bulk_archive_and_restore_keep_financial_links_and_log_each_action(): void
    {
        $admin = $this->admin();
        $done = $this->recruitment();
        $active = $this->recruitment(['status' => 'new']);
        $invoiceId = DB::table('invoices')->insertGetId([
            'contract_id' => $done->id, 'total_due' => 500, 'is_paid' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->transferredByMusaned($invoiceId);
        $updatedAtBefore = DB::table('contracts')->where('id', $done->id)->value('updated_at');

        $result = $this->service->archive('recruitment', [$done->id, $active->id], 'أرشفة سنوية', $admin->id);

        $this->assertSame([$done->id], array_column($result['done'], 'id'));
        $this->assertSame([$active->id], array_column($result['rejected'], 'id'));
        $this->assertFalse(Contract::query()->notArchived()->whereKey($done->id)->exists());
        $this->assertTrue(Contract::query()->onlyArchived()->whereKey($done->id)->exists());
        $this->assertSame($updatedAtBefore, DB::table('contracts')->where('id', $done->id)->value('updated_at'));
        // الفاتورة ما زالت تصل إلى عقدها المؤرشف.
        $this->assertSame($done->id, Invoice::query()->findOrFail($invoiceId)->contract->id);

        $restored = $this->service->restore('recruitment', [$done->id], null, $admin->id);
        $this->assertSame([$done->id], array_column($restored['done'], 'id'));
        $this->assertFalse(Contract::query()->findOrFail($done->id)->isArchived());
        $this->assertSame(
            [ContractArchiveLog::ACTION_RESTORED, ContractArchiveLog::ACTION_ARCHIVED],
            ContractArchiveLog::query()->where('contract_type', 'recruitment')->where('contract_id', $done->id)
                ->orderByDesc('id')->pluck('action')->all()
        );
    }

    public function test_rental_balance_query_runs_and_finished_rental_without_documents_is_archivable(): void
    {
        $admin = $this->admin();
        $rental = $this->rental(['status' => 'completed']);

        $result = $this->service->archive('rental', [$rental->id], 'منتهٍ', $admin->id);

        $this->assertSame([$rental->id], array_column($result['done'], 'id'));
        $this->assertTrue(RentalContract::query()->findOrFail($rental->id)->isArchived());
    }

    public function test_rental_with_outstanding_balance_is_rejected(): void
    {
        $service = new class extends ContractArchiveService {
            protected function rentalRemainingBalance(RentalContract $contract): float
            {
                return 150.5;
            }
        };

        $this->assertStringContainsString('رصيد مستحق', $service->archiveBlocker($this->rental()));
    }

    public function test_regular_model_save_cannot_change_archive_columns(): void
    {
        $contract = $this->recruitment();
        $contract->update(['archived_at' => now(), 'archive_reason' => 'bypass']);

        $this->assertNull(DB::table('contracts')->where('id', $contract->id)->value('archived_at'));

        $admin = $this->admin();
        $this->service->archive('recruitment', [$contract->id], 'سبب صحيح', $admin->id);
        Contract::query()->findOrFail($contract->id)->update(['archived_at' => null]);

        $this->assertNotNull(DB::table('contracts')->where('id', $contract->id)->value('archived_at'));
    }

    public function test_archived_contract_is_read_only_through_admin_routes(): void
    {
        $admin = $this->admin();
        $rental = $this->rental(['status' => 'completed']);
        $recruitment = $this->recruitment();
        $this->service->archive('rental', [$rental->id], 'منتهٍ', $admin->id);
        $this->service->archive('recruitment', [$recruitment->id], 'منتهٍ', $admin->id);

        $this->postJson(route('RentContract.freeze', $rental->id), [])->assertStatus(409);
        $this->postJson(route('RecruitmentContract.updateStatus'), ['row_id' => $recruitment->id])->assertStatus(409);
        $this->postJson(route('RecruitmentContract.bulkUpdateStatus'), ['row_ids' => [$recruitment->id]])->assertStatus(409);
        $this->get(route('RecruitmentContract.edit', $recruitment->id))->assertRedirect();
    }

    public function test_archive_screen_and_actions_require_their_permissions(): void
    {
        $contract = $this->recruitment();

        $this->admin([PermKeys::REC_CONTRACTS_ARCHIVE_VIEW]);
        $this->get(route('contracts-archive.recruitment.index'))->assertOk();
        $this->post(route('contracts-archive.recruitment.archive'), ['ids' => [$contract->id], 'reason' => 'محاولة'])
            ->assertStatus(403);
        $this->assertFalse(Contract::query()->findOrFail($contract->id)->isArchived());

        $this->admin([PermKeys::REC_CONTRACTS_ARCHIVE]);
        $this->post(route('contracts-archive.recruitment.archive'), ['ids' => [$contract->id], 'reason' => 'أرشفة'])
            ->assertRedirect();
        $this->assertTrue(Contract::query()->findOrFail($contract->id)->isArchived());
        $this->get(route('contracts-archive.recruitment.index'))->assertOk()->assertSee('#' . $contract->id);
    }

    public function test_permission_matrix_gates_every_archive_route(): void
    {
        foreach (ContractArchiveService::types() as $type) {
            foreach (['index', 'archive', 'restore'] as $action) {
                $this->assertNotEmpty(AdminRoutePermissionMatrix::resolve("contracts-archive.{$type}.{$action}"));
            }
        }
        $this->assertContains(PermKeys::RENT_CONTRACT_ARCHIVE, AdminRoutePermissionMatrix::resolve('contracts-archive.rental.archive'));
        $this->assertNotContains(PermKeys::RENT_CONTRACT_ARCHIVE_VIEW, AdminRoutePermissionMatrix::resolve('contracts-archive.rental.restore'));
    }
}
