<?php

namespace App\Services\ingaz;

use App\Models\IngazDelegation;
use App\Models\ingaz\Invoice;
use App\Support\AutomaticJournalEntries;
use App\Support\IngazDelegationTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class DelegationCostService
{
    public function __construct(private ConstraintService $journals, private YearService $years) {}

    private function setting(string $key): mixed
    {
        return ConfirmSetting::where('title', $key)->latest('updated_at')->value('value');
    }

    public function defaultAmount(): float
    {
        return round((float) ($this->setting('ingaz_delegation_fixed_cost') ?? 137), 2);
    }

    public function paymentAccounts(): array
    {
        $leaf = Account::query()->whereDoesntHave('children')
            ->when(\App\Support\BranchSharingSettings::accountsPerBranch(),
                fn ($q) => $q->assignableTo(\App\Support\BranchQueryContext::activeBranchId()));
        $custodyIds = Schema::hasTable('hr_employee_custodies')
            ? DB::table('hr_employee_custodies')->where('status', 'active')->pluck('account_id') : [];

        return [
            'bank' => (clone $leaf)->where('type', AccountTypesEnum::Bank->value)->orderBy('code')->get(),
            'custody' => (clone $leaf)->where(function ($q) use ($custodyIds) {
                $q->whereIn('id', $custodyIds)->orWhereHas('parent', fn ($p) => $p
                    ->where('type', AccountTypesEnum::Custody->value)->orWhere('code', 126));
            })->orderBy('code')->get(),
            'ingaz' => (clone $leaf)->whereKey((int) $this->setting('ingaz_delegation_ingaz_account'))->get(),
        ];
    }

    public function post(array $data): int
    {
        Validator::make($data, [
            'date' => 'required|date_format:Y-m-d', 'reference' => 'required|string|max:100',
            'confirmed' => 'accepted', 'payment_source_type' => 'required|in:bank,custody,ingaz',
            'payment_account_id' => 'required|integer',
            'delegation_ids' => 'required|array|min:1|max:200', 'delegation_ids.*' => 'required|integer|distinct',
            'amounts' => 'required|array',
        ])->validate();
        foreach ($data['delegation_ids'] as $id) {
            Validator::make(['amounts' => $data['amounts']], [
                "amounts.$id" => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            ])->validate();
        }

        return DB::transaction(function () use ($data) {
            if (!AutomaticJournalEntries::enabled()) {
                $this->fail('القيود التلقائية غير مفعلة؛ فعّلها قبل تأكيد تكلفة التفويض.');
            }
            $payment = $this->paymentAccounts()[$data['payment_source_type']]->firstWhere('id', (int) $data['payment_account_id']);
            $expense = Account::whereKey((int) $this->setting('ingaz_delegation_cost_account'))
                ->whereDoesntHave('children')->first();
            if (!$payment || !$expense || $payment->id === $expense->id) {
                $this->fail('حدد حساب صرف متاحًا من المصدر المختار وحساب تكلفة تفويض نهائيًا مختلفًا في إعدادات الحسابات.');
            }
            $year = $this->years->checkDateRange($data['date']);
            if (!$year) {
                $this->fail('لا توجد سنة مالية نشطة لتاريخ التكلفة.');
            }
            $delegations = IngazDelegation::whereIn('id', $data['delegation_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($delegations->count() !== count($data['delegation_ids'])) {
                $this->fail('بعض التفويضات غير متاحة لهذا الفرع.');
            }
            foreach ($delegations as $delegation) {
                $invoice = Invoice::where('ingaz_delegation_id', $delegation->id)->lockForUpdate()->first();
                $prefix = "قيد تكلفة تفويض إنجاز #{$delegation->id} / الفاتورة #{$invoice?->id} /";
                if ($delegation->status === IngazDelegation::STATUS_CANCELLED || !$invoice) {
                    $this->fail("التفويض #{$delegation->id} ملغي أو ليست له فاتورة متاحة.");
                }
                if ($invoice->ingaz_delegation_cost_constraint_id || Constraint::where('statement', 'like', $prefix.'%')->exists()) {
                    $this->fail("تكلفة التفويض #{$delegation->id} مسجلة مسبقًا؛ راجع قيد التكلفة الموجود.");
                }
                $amount = round((float) $data['amounts'][$delegation->id], 2);
                $source = ['bank' => 'تحويل بنكي', 'custody' => 'عهدة موظف', 'ingaz' => 'حساب إنجاز'][$data['payment_source_type']];
                $statement = $prefix . " {$source} / مرجع {$data['reference']}";
                $payload = [
                    'date' => $data['date'], 'branch_id' => $delegation->branch_id,
                    'statement' => $statement, 'value' => $amount,
                    'account_id' => [$expense->id, $payment->id],
                    'account_type' => ['debtor', 'creditor'],
                    'debtor_value' => [$amount, 0], 'creditor_value' => [0, $amount],
                    'statement_account' => [$statement, $statement],
                    'cost_center_required' => 'off', 'cost_center_id' => [null, null],
                ];
                $journal = $this->journals->storeConstraint($payload, $year);
                $this->journals->storeConstraintDetails($payload['account_id'], $journal->id, $payload);
                $invoice->update(['ingaz_delegation_cost_constraint_id' => $journal->id]);
                IngazDelegationTimeline::record($delegation, 'delegation_cost_confirmed', [
                    'summary' => "تم تأكيد تكلفة التفويض بمبلغ {$amount} من {$source}، مرجع {$data['reference']}، قيد #{$journal->id}.",
                ]);
            }
            return $delegations->count();
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cost' => $message]);
    }
}
