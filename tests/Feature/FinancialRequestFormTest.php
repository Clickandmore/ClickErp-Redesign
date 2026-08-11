<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\FinancialRequest;
use Nami\Finance\Models\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Models\ConfirmSetting;
use Tests\TestCase;

class FinancialRequestFormTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Create the actor rather than borrowing whichever super admin happens to exist,
        // so the test is independent of the contents of the database it runs against.
        $branch = Branch::factory()->main()->create(['name_ar' => 'الفرع الرئيسي']);

        $admin = Admin::factory()->superAdmin()->forBranch($branch)->create();
        $admin->assignRole('Administrator');

        $this->actingAs($admin, 'admin');
    }

    /** @dataProvider paymentMethods */
    public function test_request_can_be_created_for_each_payment_method(string $method, array $bankData): void
    {
        $response = $this->post(route('financial-requests.store'), $this->validPayload($method, $bankData));

        $response->assertRedirect(route('financial-requests.index'));
        $request = FinancialRequest::query()->latest('id')->firstOrFail();
        $this->assertSame(FinancialRequest::STATUS_FOR_APPROVAL, $request->status);
        $this->assertSame($method, $request->payload['payment_method']);
        $this->assertCount(2, $request->statusLogs);
    }

    public function test_bank_account_details_are_optional(): void
    {
        $response = $this->post(route('financial-requests.store'), $this->validPayload('bank_transfer'));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('financial_requests', ['status' => FinancialRequest::STATUS_FOR_APPROVAL]);
    }

    public function test_request_with_attachment_is_created_and_attachment_is_stored_separately(): void
    {
        Storage::fake('public');

        $response = $this->post(route('financial-requests.store'), [
            ...$this->validPayload('bank_transfer'),
            'attachments' => [
                'main' => [UploadedFile::fake()->create('refund.pdf', 100, 'application/pdf')],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('financial-requests.index'));

        $requestItem = FinancialRequest::query()->latest('id')->firstOrFail();
        $attachment = $requestItem->attachments()->firstOrFail();

        $this->assertSame('main', $attachment->field_key);
        $this->assertSame('refund.pdf', $attachment->file_name);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_existing_leaf_account_can_be_selected_for_bank_transfer(): void
    {
        $account = Account::query()->whereDoesntHave('children')->first();
        if (! $account) {
            $this->markTestSkipped('لا يوجد حساب محاسبي نهائي للاختبار.');
        }

        $response = $this->post(route('financial-requests.store'), $this->validPayload('bank_transfer', [
            'account_id' => $account->id,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame($account->id, FinancialRequest::query()->latest('id')->firstOrFail()->payload['account_id']);
    }

    public function test_create_screen_displays_human_readable_branch_and_contract_labels(): void
    {
        app()->setLocale('ar');
        $response = $this->get(route('financial-requests.create'));

        $response->assertOk();
        $response->assertSee('الفرع الرئيسي');
        $response->assertSee('استقدام');
        $response->assertSee('تأجير');
        $response->assertDontSee('programming_code');
        $response->assertDontSee('@selected', false);
    }

    public function test_english_create_and_index_screens_do_not_contain_arabic_ui_text(): void
    {
        app()->setLocale('en');

        foreach ([route('financial-requests.create'), route('financial-requests.index')] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('Financial Request', false);
            $response->assertDontSee('إدارة الطلبات المالية');
            $response->assertDontSee('البيانات الأساسية');
            $response->assertDontSee('نوع الطلب');
        }
    }

    public function test_invalid_contract_type_is_rejected(): void
    {
        $response = $this->from(route('financial-requests.create'))->post(
            route('financial-requests.store'),
            [...$this->validPayload('cash'), 'contract_type' => 'programming_code']
        );

        $response->assertRedirect(route('financial-requests.create'));
        $response->assertSessionHasErrors('contract_type');
    }

    public function test_request_can_be_edited_while_waiting_for_approval(): void
    {
        $this->post(route('financial-requests.store'), $this->validPayload('cash'))
            ->assertSessionHasNoErrors();

        $requestItem = FinancialRequest::query()->latest('id')->firstOrFail();
        $this->assertSame(FinancialRequest::STATUS_FOR_APPROVAL, $requestItem->status);

        $this->put(route('financial-requests.update', $requestItem), [
            ...$this->validPayload('cash'),
            'amount' => 275,
            'description' => 'تم تعديل الطلب قبل الاعتماد',
        ])->assertRedirect(route('financial-requests.show', $requestItem));

        $requestItem->refresh();
        $this->assertSame(FinancialRequest::STATUS_FOR_APPROVAL, $requestItem->status);
        $this->assertSame('275.00', $requestItem->amount);
        $this->assertSame('تم تعديل الطلب قبل الاعتماد', $requestItem->description);
        $this->assertCount(2, $requestItem->statusLogs);
    }

    public function test_approved_request_cannot_be_edited(): void
    {
        $requestItem = FinancialRequest::query()->create([
            'request_no' => 'FR-APPROVED-NO-EDIT',
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_APPROVED,
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'branch_id' => auth('admin')->user()->branch_id,
            'created_by_id' => auth('admin')->id(),
        ]);

        $this->get(route('financial-requests.edit', $requestItem))->assertForbidden();
        $this->put(route('financial-requests.update', $requestItem), [
            ...$this->validPayload('cash'),
            'amount' => 300,
        ])->assertForbidden();

        $this->assertSame('100.00', $requestItem->fresh()->amount);
    }

    public function test_incomplete_request_can_be_soft_deleted_and_restored(): void
    {
        $requestItem = FinancialRequest::query()->create([
            'request_no' => 'FR-INCOMPLETE-DELETE',
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'branch_id' => auth('admin')->user()->branch_id,
            'created_by_id' => auth('admin')->id(),
        ]);

        $this->delete(route('financial-requests.destroy', $requestItem))
            ->assertRedirect(route('financial-requests.index', ['scope' => 'incomplete']));

        $this->assertSoftDeleted('financial_requests', ['id' => $requestItem->id]);

        $this->post(route('financial-requests.restore', $requestItem->id))
            ->assertRedirect(route('financial-requests.index', ['scope' => 'deleted']));

        $this->assertDatabaseHas('financial_requests', [
            'id' => $requestItem->id,
            'deleted_at' => null,
        ]);
    }

    public function test_approved_request_cannot_be_deleted(): void
    {
        $requestItem = FinancialRequest::query()->create([
            'request_no' => 'FR-APPROVED-NO-DELETE',
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_APPROVED,
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'branch_id' => auth('admin')->user()->branch_id,
            'created_by_id' => auth('admin')->id(),
        ]);

        $this->delete(route('financial-requests.destroy', $requestItem))->assertStatus(422);

        $this->assertDatabaseHas('financial_requests', [
            'id' => $requestItem->id,
            'deleted_at' => null,
        ]);
    }

    public function test_request_without_branch_can_be_opened_when_it_is_visible_in_the_list(): void
    {
        $requestItem = FinancialRequest::query()->create([
            'request_no' => 'FR-LEGACY-TEST',
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'request_date' => now()->toDateString(),
            'created_by_id' => auth('admin')->id(),
        ]);
        $requestItem->updateQuietly(['branch_id' => null]);

        $this->get(route('financial-requests.index'))->assertOk()->assertSee('FR-LEGACY-TEST');
        $this->get(route('financial-requests.show', $requestItem))
            ->assertOk()
            ->assertSee('FR-LEGACY-TEST')
            ->assertSee('تم رفع الطلب المالي.')
            ->assertSee('الحالة الحالية لطلب قديم لا يحتوي على سجل انتقالات محفوظ.');
    }

    public function test_request_closing_can_be_closed_without_official_receipt(): void
    {
        ConfirmSetting::query()->create([
            'title' => 'automatic_journal_entries',
            'value' => 'off',
            'status' => 1,
            'created_by_id' => auth('admin')->id(),
        ]);

        $requestItem = FinancialRequest::query()->create([
            'request_no' => 'FR-CLOSE-WITHOUT-RECEIPT',
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_REQUEST_CLOSING,
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'created_by_id' => auth('admin')->id(),
        ]);

        $this->post(route('financial-requests.status', $requestItem), [
            'status' => FinancialRequest::STATUS_CLOSED,
            'priority' => FinancialRequest::PRIORITY_NORMAL,
        ])->assertSessionHasNoErrors();

        $requestItem->refresh();
        $this->assertSame(FinancialRequest::STATUS_CLOSED, $requestItem->status);
        $this->assertNotNull($requestItem->closed_at);
        $this->assertFalse($requestItem->attachments()->where('field_key', 'official_receipt')->exists());
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => ['cash', []],
            'bank manual' => ['bank_transfer', [
                'bank_name' => 'بنك الاختبار',
                'account_holder_name' => 'مستفيد اختباري',
                'account_number' => '123456',
                'iban' => 'SA0000000000000000000000',
            ]],
        ];
    }

    private function validPayload(string $method, array $bankData = []): array
    {
        $admin = auth('admin')->user();

        return [
            'type' => 'other_financial',
            'priority' => 'normal',
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'description' => 'طلب اختبار يتم التراجع عنه تلقائيًا',
            'branch_id' => $admin->branch_id,
            'payload' => [
                'paid_to' => 'مستفيد اختباري',
                'accounting_name' => 'مصروف اختباري',
                'debit_credit' => 'debit',
                'payment_method' => $method,
                ...$bankData,
            ],
        ];
    }
}
