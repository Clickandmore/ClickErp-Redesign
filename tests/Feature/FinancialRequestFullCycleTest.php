<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\FinancialRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Models\ConfirmSetting;
use Tests\TestCase;

/**
 * دورة الطلب المالي كاملة لكل نوع: النموذج، الحفظ مع حقول النوع، العرض، التعديل،
 * القائمة، الطباعة، التصدير، ثم مسار الحالات حتى الإقفال.
 */
class FinancialRequestFullCycleTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $branch = Branch::factory()->main()->create(['name_ar' => 'الفرع الرئيسي']);
        $this->admin = Admin::factory()->superAdmin()->forBranch($branch)->create();
        $this->admin->assignRole('Administrator');
        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_form_layout_keeps_client_above_summary_and_nature_apart_from_payment_method(): void
    {
        $html = $this->get(route('financial-requests.create'))->assertOk()->getContent();

        $sidebar = substr($html, strpos($html, 'class="col-lg-4"'));
        $this->assertLessThan(strpos($sidebar, 'id="sumAmount"'), strpos($sidebar, 'id="clientSearch"'), 'ربط العميل في العمود الجانبي فوق الملخص.');
        $this->assertStringNotContainsString('id="clientSearch"', substr($html, 0, strpos($html, 'class="col-lg-4"')));

        $nature = strpos($html, 'id="natureCredit"');
        $natureCol = strrpos(substr($html, 0, $nature), '<div class="col-');
        $this->assertStringStartsWith('<div class="col-md-6"', substr($html, $natureCol, 30), 'طبيعة المبلغ بنصف العرض فلا تتداخل مع طريقة الدفع.');

        $this->assertLessThan(strpos($html, 'textarea name="description"'), strpos($html, 'id="dynamicFields"'), 'حقول النوع (سبب الاسترداد) قبل وصف الدفع.');
    }

    /** @dataProvider requestTypes */
    public function test_each_type_goes_through_create_show_edit_index_print_and_export(string $type): void
    {
        $fields = FinancialRequest::typeFields()[$type];
        $typePayload = [];
        foreach (array_keys($fields) as $i => $key) {
            $typePayload[$key] = "قيمة-{$type}-{$i}";
        }
        $clientId = $this->client();

        $this->post(route('financial-requests.store'), [
            'type' => $type,
            'priority' => 'normal',
            'amount' => 1250.5,
            'request_date' => now()->toDateString(),
            'description' => "وصف دفع {$type}",
            'branch_id' => $this->admin->branch_id,
            'client_id' => $clientId,
            'client_name' => 'عميل الدورة الكاملة',
            'client_identity' => '1011223344',
            'maid_name' => 'عاملة اختبار',
            'maid_passport' => 'P1234567',
            'contract_number' => 'FC-' . $type,
            'contract_cost' => 15000,
            'contract_salary' => 1500,
            'payload' => [
                'paid_to' => 'عميل الدورة الكاملة',
                'accounting_name' => 'عميل الدورة الكاملة',
                'debit_credit' => 'credit',
                'payment_method' => 'bank_transfer',
                'bank_name' => 'بنك الاختبار',
                'iban' => 'SA0380000000608010167519',
                ...$typePayload,
            ],
            'attachments' => ['main' => [UploadedFile::fake()->create('main.pdf', 20, 'application/pdf')]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('financial-requests.index'));

        $item = FinancialRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($type, $item->type);
        $this->assertSame(FinancialRequest::STATUS_FOR_APPROVAL, $item->status);
        $this->assertEquals(1250.5, (float) $item->amount);
        $this->assertSame((int) $clientId, (int) $item->client_id);
        foreach ($typePayload as $key => $value) {
            $this->assertSame($value, $item->payload[$key] ?? null, "حقل {$key} محفوظ");
        }
        $this->assertSame('main.pdf', $item->attachments()->firstOrFail()->file_name);
        Storage::disk('public')->assertExists($item->attachments()->firstOrFail()->file_path);

        $show = $this->get(route('financial-requests.show', $item))->assertOk();
        $show->assertSee($item->request_no)->assertSee("وصف دفع {$type}")->assertSee('عميل الدورة الكاملة')->assertSee('main.pdf');
        foreach ($typePayload as $key => $value) {
            $show->assertSee($value);
            $show->assertSee($fields[$key]);
        }

        $edit = $this->get(route('financial-requests.edit', $item))->assertOk();
        $edit->assertSee("وصف دفع {$type}")->assertSee('FC-' . $type);
        foreach ($typePayload as $value) {
            // حقول النوع تُرسم بالجافاسكربت من payload المرمّز JSON داخل الصفحة.
            $edit->assertSee(trim(json_encode($value), '"'), false);
        }

        $changed = array_map(fn ($value) => $value . '-معدل', $typePayload);
        $this->put(route('financial-requests.update', $item), [
            'type' => $type,
            'priority' => FinancialRequest::PRIORITY_IMPORTANT,
            'amount' => 1250.5,
            'request_date' => now()->toDateString(),
            'description' => "وصف معدل {$type}",
            'branch_id' => $this->admin->branch_id,
            'client_id' => $clientId,
            'client_name' => 'عميل الدورة الكاملة',
            'payload' => [
                'paid_to' => 'عميل الدورة الكاملة',
                'accounting_name' => 'عميل الدورة الكاملة',
                'debit_credit' => 'credit',
                'payment_method' => 'cash',
                ...$changed,
            ],
        ])->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame(FinancialRequest::PRIORITY_IMPORTANT, $item->priority);
        $this->assertSame("وصف معدل {$type}", $item->description);
        $this->assertSame('cash', $item->payload['payment_method']);
        foreach ($changed as $key => $value) {
            $this->assertSame($value, $item->payload[$key]);
        }

        $this->get(route('financial-requests.index', ['scope' => 'all', 'q' => $item->request_no]))->assertOk()->assertSee($item->request_no);
        $this->get(route('financial-requests.print', ['scope' => 'all', 'q' => $item->request_no]))->assertOk()->assertSee($item->request_no);
        $this->get(route('financial-requests.export', ['scope' => 'all', 'q' => $item->request_no]))->assertOk();
    }

    public function test_status_path_from_approval_to_closing_and_rejection_round_trip(): void
    {
        ConfirmSetting::query()->create([
            'title' => 'automatic_journal_entries', 'value' => 'off', 'status' => 1, 'created_by_id' => $this->admin->id,
        ]);

        $this->post(route('financial-requests.store'), [
            'type' => 'client_refund', 'priority' => 'normal', 'amount' => 500,
            'request_date' => now()->toDateString(), 'description' => 'استرداد مبلغ للعميل',
            'branch_id' => $this->admin->branch_id,
            'payload' => [
                'paid_to' => 'عميل', 'accounting_name' => 'عميل', 'debit_credit' => 'credit',
                'payment_method' => 'bank_transfer', 'refund_reason' => 'إلغاء العقد قبل الوصول',
            ],
        ])->assertSessionHasNoErrors();
        $item = FinancialRequest::query()->latest('id')->firstOrFail();

        // رفض/إعادة للاستكمال يتطلب ملاحظة، ثم إعادة الإرسال.
        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_PREPARING])
            ->assertSessionHasErrors('remarks');
        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_PREPARING, 'remarks' => 'أرفق صورة الآيبان'])
            ->assertSessionHasNoErrors();
        $this->assertSame(FinancialRequest::STATUS_PREPARING, $item->refresh()->status);
        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_FOR_APPROVAL])->assertSessionHasNoErrors();

        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_APPROVED])->assertSessionHasNoErrors();
        $this->assertSame(FinancialRequest::STATUS_APPROVED, $item->refresh()->status);

        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_PAID])
            ->assertSessionHasErrors('attachments');
        $this->post(route('financial-requests.status', $item), [
            'status' => FinancialRequest::STATUS_PAID,
            'attachments' => ['payment_proof' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(FinancialRequest::STATUS_PAID, $item->refresh()->status);

        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_REQUEST_CLOSING])->assertSessionHasNoErrors();
        $this->post(route('financial-requests.status', $item), ['status' => FinancialRequest::STATUS_CLOSED])->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame(FinancialRequest::STATUS_CLOSED, $item->status);
        $this->assertNotNull($item->closed_at);

        $this->get(route('financial-requests.show', $item))->assertOk()->assertSee('إلغاء العقد قبل الوصول')->assertSee('proof.pdf');
        $this->get(route('financial-requests.edit', $item))->assertStatus(403);
    }

    public function test_search_endpoints_return_json(): void
    {
        $this->client();
        $this->getJson(route('financial-requests.search-clients', ['q' => 'عميل الدورة']))->assertOk();
        $this->getJson(route('financial-requests.search-maids', ['q' => 'x']))->assertOk();
        $this->getJson(route('financial-requests.search-accounts', ['q' => '']))->assertOk()->assertJsonStructure(['results']);
        $this->getJson(route('financial-requests.contract-details', ['contract_number' => 'NOPE-404']))->assertStatus(404);
    }

    public static function requestTypes(): array
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return collect(array_keys(trans('financial_requests.types')))->mapWithKeys(fn ($type) => [$type => [$type]])->all();
    }

    private function client(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'عميل الدورة الكاملة', 'phone_code' => '966', 'phone' => '5' . random_int(10000000, 99999999),
            'password' => 'x', 'national_num' => '1011223344', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
