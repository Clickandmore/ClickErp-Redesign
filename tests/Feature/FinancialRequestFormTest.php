<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\FinancialRequest;
use Nami\Finance\Models\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialRequestFormTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::query()->where('admin_type', 'super_admin')->firstOrFail(), 'admin');
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
