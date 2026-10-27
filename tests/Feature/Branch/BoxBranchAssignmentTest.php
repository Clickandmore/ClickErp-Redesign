<?php

namespace Tests\Feature\Branch;

use App\Support\BranchSharingSettings;
use App\Support\BranchQueryContext;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Http\Requests\BoxRequest;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\BoxService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class BoxBranchAssignmentTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        BranchSharingSettings::flush();
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $parent = Account::where('type', AccountTypesEnum::Boxs->value)->first();
        if ($parent) {
            $parent->update(['status' => 'debtor']);
        } else {
            Account::create(['name' => 'Cash boxes', 'type' => AccountTypesEnum::Boxs->value,
                'code' => random_int(80000000, 89999999), 'level' => 1, 'status' => 'debtor', 'operation_type' => 1]);
        }
    }

    protected function tearDown(): void
    {
        BranchSharingSettings::flush();
        parent::tearDown();
    }

    public function test_new_box_is_assigned_to_selected_branch_and_available_for_its_refunds(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $destination = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($own));
        $box = app(BoxService::class)->store(['title' => 'Branch cash ' . uniqid(),
            'account_branch_scope_present' => 1, 'branch_ids' => [$destination->id]]);
        $this->assertSame([$destination->id], $box->account->branches->pluck('id')->all());
        $this->assertFalse(SponsorshipTransferPaymentService::receiptAccounts()->contains('id', $box->account_id));
        $this->actingAsAdmin($this->branchAdmin($destination));
        BranchQueryContext::reset();
        $this->assertTrue(SponsorshipTransferPaymentService::receiptAccounts()->contains('id', $box->account_id));
    }

    public function test_edit_can_change_or_clear_branch_assignment_and_rename_preserves_it(): void
    {
        $first = $this->makeBranch(['is_main' => true]);
        $second = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($first));
        $service = app(BoxService::class);
        $box = $service->store(['title' => 'Edit cash ' . uniqid(), 'account_branch_scope_present' => 1, 'branch_ids' => [$second->id]]);
        $service->update($box->id, ['title' => 'Renamed cash ' . uniqid()]);
        $this->assertSame([$second->id], $box->fresh()->account->branches->pluck('id')->all());
        $service->update($box->id, ['title' => $box->title, 'account_branch_scope_present' => 1, 'branch_ids' => [$first->id]]);
        $this->assertSame([$first->id], $box->fresh()->account->branches->pluck('id')->all());
        $service->update($box->id, ['title' => $box->title, 'account_branch_scope_present' => 1]);
        $this->assertCount(0, $box->fresh()->account->branches);
    }

    public function test_disabled_branches_hide_selection_and_ignore_submitted_assignments(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $this->enableBranchesModule(false);
        $box = app(BoxService::class)->store(['title' => 'No branches ' . uniqid(), 'account_branch_scope_present' => 1, 'branch_ids' => [$branch->id]]);
        $this->assertCount(0, $box->account->branches);
        $controller = app(\Nami\Finance\Http\Controllers\BoxController::class);
        $method = new \ReflectionMethod($controller, 'branchAssignmentData');
        $method->setAccessible(true);
        $data = $method->invoke($controller);
        $this->assertFalse($data['accountBranchAssignmentsEnabled']);
        $this->assertStringNotContainsString('branch_ids', view('finance::finance.boxes.create', $data + ['storeRoute' => '/'])->render());
    }

    public function test_invalid_or_inactive_branches_are_rejected(): void
    {
        $inactive = $this->makeBranch(['is_active' => false]);
        $request = BoxRequest::create('/', 'POST');
        $validator = Validator::make(['title' => 'Validation ' . uniqid(), 'account_branch_scope_present' => 1,
            'branch_ids' => [$inactive->id, 999999999]], $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('branch_ids.0'));
        $this->assertTrue($validator->errors()->has('branch_ids.1'));
    }

    public function test_box_account_cannot_be_deleted_or_changed_to_another_type(): void
    {
        $box = app(BoxService::class)->store(['title' => 'Protected cash ' . uniqid()]);
        foreach (['delete', 'type'] as $operation) {
            try {
                $account = $box->fresh()->account;
                if ($operation === 'delete') {
                    $account->delete();
                } else {
                    $account->update(['type' => AccountTypesEnum::Bank->value]);
                }
                $this->fail('A linked cash account must be protected.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertSame(AccountTypesEnum::Box, $box->fresh()->account->type);
    }

    public function test_box_update_cannot_replace_its_account_with_submitted_account_id(): void
    {
        $service = app(BoxService::class);
        $first = $service->store(['title' => 'First cash ' . uniqid()]);
        $second = $service->store(['title' => 'Second cash ' . uniqid()]);
        $service->update($first->id, ['title' => $first->title, 'account_id' => $second->account_id]);
        $this->assertSame($first->account_id, $first->fresh()->account_id);
    }

    public function test_unregistered_cash_accounts_are_not_offered_or_accepted(): void
    {
        $box = app(BoxService::class)->store(['title' => 'Registered cash ' . uniqid()]);
        $account = $box->account;
        $box->delete();
        $this->assertFalse(SponsorshipTransferPaymentService::receiptAccounts()->contains('id', $account->id));
        $method = new \ReflectionMethod(SponsorshipTransferPaymentService::class, 'resolveReceiptAccount');
        $method->setAccessible(true);
        $this->expectException(\RuntimeException::class);
        $method->invoke(app(SponsorshipTransferPaymentService::class), $account->id);
    }

    public function test_chart_import_rejects_changing_a_registered_box_account_type(): void
    {
        $box = app(BoxService::class)->store(['title' => 'Import cash ' . uniqid()]);
        $account = $box->account;
        $command = new \App\Console\Commands\ImportClickAndMoreChartOfAccounts();
        $method = new \ReflectionMethod($command, 'replaceChart');
        $method->setAccessible(true);
        try {
            \Illuminate\Support\Facades\DB::transaction(fn () => $method->invoke($command, [[
                'code' => (int) $account->code, 'parent_code' => '', 'name' => 'Ordinary account',
                'nature' => 'debit', 'kind' => 'asset', 'level' => 1, 'summary' => false,
            ]], []));
            $this->fail('Import must not change a registered cash account type.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('الاستيراد يغير نوع حساب صندوق', $exception->getMessage());
        }
        $this->assertSame(AccountTypesEnum::Box, $account->fresh()->type);
        $this->assertSame($account->id, $box->fresh()->account_id);
    }
}
