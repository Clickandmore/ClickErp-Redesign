<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\SponsorshipTransferController;
use App\Models\SponsorshipTransfer;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Tests\TestCase;

class SponsorshipTransferFailureRefundMethodTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    public function test_cash_refund_requires_a_cash_box_account(): void
    {
        $errors = $this->cancelErrors([
            'failure_refund_method' => 'cash',
            'refund_account_id' => $this->account(AccountTypesEnum::Bank->value)->id,
        ]);

        $this->assertSame('عند السداد النقدي يجب اختيار صندوق كحساب الصرف.', $errors['refund_account_id'][0]);
    }

    public function test_cash_refund_does_not_require_the_client_iban(): void
    {
        $errors = $this->cancelErrors([
            'failure_refund_method' => 'cash',
            'refund_account_id' => $this->account(AccountTypesEnum::Box->value)->id,
        ]);

        $this->assertArrayNotHasKey('failure_client_iban', $errors);
        $this->assertArrayNotHasKey('refund_account_id', $errors);
    }

    public function test_bank_refund_stays_the_default_and_still_requires_the_iban(): void
    {
        $errors = $this->cancelErrors([
            'refund_account_id' => $this->account(AccountTypesEnum::Bank->value)->id,
        ]);

        $this->assertArrayHasKey('failure_client_iban', $errors);
    }

    public function test_bank_refund_rejects_a_cash_box_account(): void
    {
        $errors = $this->cancelErrors([
            'failure_refund_method' => 'bank',
            'failure_client_iban' => 'SA0000000000000000000000',
            'refund_account_id' => $this->account(AccountTypesEnum::Box->value)->id,
        ]);

        $this->assertSame('عند السداد البنكي يجب اختيار حساب بنك وليس صندوقاً.', $errors['refund_account_id'][0]);
    }

    public function test_refund_method_is_stored_and_labelled(): void
    {
        $transfer = SponsorshipTransfer::withoutEvents(fn () => SponsorshipTransfer::create([
            'trial_days' => 5,
            'status' => 'failed',
            'failure_refund_method' => 'cash',
        ]));

        $this->assertSame('cash', $transfer->fresh()->failure_refund_method);
        $this->assertSame('نقدي', $transfer->fresh()->failure_refund_method_text);
        $this->assertSame('تحويل بنكي', (new SponsorshipTransfer(['failure_refund_method' => 'bank']))->failure_refund_method_text);
    }

    /**
     * يشغّل إلغاء النقل ويعيد أخطاء التحقق فقط (إن نجحت كل الفحوصات وصل التنفيذ لما بعدها فنعيد مصفوفة فارغة).
     */
    private function cancelErrors(array $input): array
    {
        $transfer = SponsorshipTransfer::withoutEvents(fn () => SponsorshipTransfer::create([
            'trial_days' => 5,
            'contract_value' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
            'status' => 'trial',
        ]));

        $request = Request::create('/', 'POST', $input + [
            'failure_settlement_type' => 'refund',
            'failure_settlement_amount' => 500,
        ]);

        try {
            // بعد اجتياز التحقق يكمل التنفيذ لمسار المحاسبة؛ يكفينا هنا أنه لم يرفض المدخلات
            DB::transaction(function () use ($request, $transfer) {
                app(SponsorshipTransferController::class)
                    ->cancelTransfer($request, app(SponsorshipTransferPaymentService::class), $transfer->id);
                throw new \RuntimeException('rollback');
            });
        } catch (ValidationException $e) {
            return $e->errors();
        } catch (\RuntimeException $e) {
            // الوصول إلى هنا يعني أن الطلب اجتاز التحقق
        }

        return [];
    }

    private function account(int $type): Account
    {
        return Account::create([
            'name' => 'حساب اختبار طريقة الاسترجاع', 'status' => 'debtor', 'type' => $type,
            'code' => ((int) DB::table('fn_accounts')->max('code')) + 1,
            'level' => 1, 'operation_type' => 1, 'statement_type' => 'income_statement', 'created_by_id' => 1,
        ]);
    }
}
