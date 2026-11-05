<?php

namespace Tests\Feature\Finance;

use App\Models\RentalContract;
use App\Models\RentalContractInvoice;
use App\Services\ingaz\RentalContractFinanceService;
use App\Services\ingaz\RentalMadaFeeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\TaxAccountResolver;
use Nami\Finance\Services\YearService;
use Nami\Finance\Support\AccountCodeAliases;
use Tests\TestCase;

class RentalMadaFeeTest extends TestCase
{
    use DatabaseTransactions;

    private Account $fee;
    private Account $tax;
    private Account $bank;
    private Account $client;
    private Bond $bond;

    protected function setUp(): void
    {
        parent::setUp();
        \Nami\Finance\Models\ConfirmSetting::query()->whereIn('title', [RentalContractFinanceService::RENTAL_MADA_FEE_ACCOUNT_SETTING, RentalContractFinanceService::RENTAL_MADA_TAX_ACCOUNT_SETTING])->delete();
        $this->fee = Account::query()->firstOrCreate(['code' => AccountCodeAliases::current('52162')], ['name' => 'رسوم نقاط بيع البنك الاهلي', 'status' => 'debtor', 'type' => 1, 'level' => 4]);
        $this->tax = $this->account('ضريبة المشتريات');
        $this->bank = $this->account('نقاط بيع الأهلي', AccountTypesEnum::Bank->value);
        $this->client = $this->account('عميل اختبار مدى');
        $resolver = Mockery::mock(TaxAccountResolver::class);
        $resolver->shouldReceive('resolve')->with('expense_tax_account')->andReturn($this->tax);
        $this->app->instance(TaxAccountResolver::class, $resolver);
        $constraint = Constraint::query()->create(['type' => 1, 'value' => 1900, 'date' => now()->toDateString(), 'code' => random_int(800000, 899999), 'statement' => 'قبض تأجير']);
        foreach ([[$this->bank->id, 1900, 0], [$this->client->id, 0, 1900]] as [$id, $debit, $credit]) {
            $constraint->constraintDetails()->create(['account_id' => $id, 'date' => $constraint->date, 'type' => $debit ? 'debtor' : 'creditor', 'debtor_value' => $debit, 'creditor_value' => $credit]);
        }
        $this->bond = Bond::query()->create(['type' => 1, 'payment_type' => 1, 'debit_account_id' => $this->bank->id, 'credit_account_id' => $this->client->id, 'value' => 1900, 'date' => now()->toDateString(), 'code' => random_int(800000, 899999), 'is_confirm' => 1, 'constraint_id' => $constraint->id]);
    }

    private function account(string $name, int $type = 1): Account
    {
        return Account::query()->create(['name' => $name, 'code' => random_int(9000000, 9999999), 'status' => 'debtor', 'type' => $type, 'level' => 4]);
    }

    private function receipt(string $method): void
    {
        $bonds = Mockery::mock(BondService::class);
        $bonds->shouldReceive('lastCode')->andReturn(1);
        $bonds->shouldReceive('storeQabdBond')->once()->andReturn($this->bond);
        $years = Mockery::mock(YearService::class);
        $years->shouldReceive('checkDateRange')->andReturn(true);
        $service = new RentalContractFinanceService(app(ConstraintService::class), $years, $bonds);
        $methodReflection = new \ReflectionMethod($service, 'storeReceiptBond');
        $methodReflection->setAccessible(true);
        $methodReflection->invoke($service, new RentalContractInvoice, new RentalContract, $this->client->id, 1900, $method, now()->toDateString(), $this->bank->id, 'دفعة اختبار');
    }

    public function test_mada_receipt_keeps_customer_gross_and_posts_fee_vat_and_bank_deduction_once(): void
    {
        $this->receipt('mada');
        app(RentalMadaFeeService::class)->post($this->bond);
        $lines = $this->bond->constraint->constraintDetails;
        $this->assertCount(5, $lines);
        $this->assertEquals(15.20, $lines->where('account_id', $this->fee->id)->sum('debtor_value'));
        $this->assertEquals(2.28, $lines->where('account_id', $this->tax->id)->sum('debtor_value'));
        $this->assertEquals(17.48, $lines->where('account_id', $this->bank->id)->sum('creditor_value'));
        $this->assertEquals(1900, $lines->where('account_id', $this->client->id)->sum('creditor_value'));
        $this->assertEquals(1900, $this->bond->fresh()->value);
        $this->assertEquals($lines->sum('debtor_value'), $lines->sum('creditor_value'));
        $this->assertEquals(1917.48, $this->bond->constraint->fresh()->value);
    }

    public function test_bank_transfer_has_no_mada_fee(): void
    {
        $this->receipt('bank_transfer');
        $this->assertCount(2, $this->bond->constraint->constraintDetails);
    }

    public function test_missing_expense_account_rejects_fees_without_partial_journal_lines(): void
    {
        $this->fee->update(['status' => 'creditor']);
        try {
            app(RentalMadaFeeService::class)->post($this->bond);
            $this->fail('Invalid fee account must reject posting.');
        } catch (\RuntimeException $e) {
            $this->assertCount(2, $this->bond->constraint->constraintDetails);
        }
    }

    public function test_configured_accounts_override_old_chart_codes(): void
    {
        $customFee = $this->account('رسوم مدى بعد ترتيب الدليل');
        $customTax = $this->account('ضريبة رسوم مدى بعد ترتيب الدليل');
        \Nami\Finance\Models\ConfirmSetting::query()->create(['title' => RentalContractFinanceService::RENTAL_MADA_FEE_ACCOUNT_SETTING, 'value' => $customFee->id]);
        \Nami\Finance\Models\ConfirmSetting::query()->create(['title' => RentalContractFinanceService::RENTAL_MADA_TAX_ACCOUNT_SETTING, 'value' => $customTax->id]);
        $this->receipt('mada');
        $lines = $this->bond->constraint->constraintDetails;
        $this->assertEquals(15.2, $lines->where('account_id', $customFee->id)->sum('debtor_value'));
        $this->assertEquals(2.28, $lines->where('account_id', $customTax->id)->sum('debtor_value'));
        $this->assertEquals(0, $lines->where('account_id', $this->fee->id)->sum('debtor_value'));
    }
}
